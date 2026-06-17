<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\WfaRequest;
use App\Support\LocalClock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WfaRequestController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.wfa-requests.index', [
            'requests' => $this->studentRequests($request),
            'hasPendingRequest' => $this->hasPendingRequest($request),
        ]);
    }

    public function create(Request $request): View
    {
        return view('student.wfa-requests.create', [
            'enrollments' => $this->activeEnrollments($request),
            'selectedEnrollmentId' => (int) $request->integer('enrollment_id'),
            'hasPendingRequest' => $this->hasPendingRequest($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $enrollmentIds = $this->activeEnrollments($request)->pluck('id');

        $data = $request->validate([
            'internship_enrollment_id' => ['required', Rule::in($enrollmentIds)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'planned_location' => ['required', 'string', 'max:255'],
            'planned_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'planned_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'planned_activity' => ['required', 'string', 'max:2000'],
            'reason' => ['required', 'string', 'max:2000'],
            'evidence_file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx'],
        ]);

        $enrollment = InternshipEnrollment::query()
            ->with(['internshipPeriod', 'wfaRequests' => fn ($query) => $query->whereIn('status', ['pending', 'approved'])])
            ->findOrFail($data['internship_enrollment_id']);

        $this->ensureDatesWithinAttendanceRange($enrollment, $data['starts_at'], $data['ends_at']);

        if ($this->hasPendingRequest($request)) {
            throw ValidationException::withMessages([
                'internship_enrollment_id' => 'Masih ada pengajuan WFA yang menunggu persetujuan. Batalkan atau tunggu keputusan terlebih dahulu.',
            ]);
        }

        if ($this->hasOverlappingRequest($enrollment, $data['starts_at'], $data['ends_at'])) {
            throw ValidationException::withMessages([
                'starts_at' => 'Tanggal WFA bertabrakan dengan pengajuan WFA lain yang masih menunggu atau sudah disetujui.',
            ]);
        }

        $evidencePath = $request->file('evidence_file')->store('wfa-evidence', 'public');

        WfaRequest::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'planned_location' => $data['planned_location'],
            'planned_latitude' => $data['planned_latitude'] ?? null,
            'planned_longitude' => $data['planned_longitude'] ?? null,
            'planned_activity' => $data['planned_activity'],
            'reason' => $data['reason'],
            'evidence_path' => $evidencePath,
            'status' => 'pending',
        ]);

        return redirect()->route('student.wfa-requests.index')->with('status', 'Pengajuan WFA berhasil dikirim.');
    }

    public function cancel(Request $request, WfaRequest $wfaRequest): RedirectResponse
    {
        $this->authorizeStudentRequest($request, $wfaRequest);

        if ($wfaRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Pengajuan hanya dapat dibatalkan saat masih menunggu.']);
        }

        $wfaRequest->update([
            'status' => 'cancelled',
            'review_note' => 'Dibatalkan oleh mahasiswa.',
        ]);

        return redirect()->route('student.wfa-requests.index')->with('status', 'Pengajuan WFA dibatalkan.');
    }

    private function activeEnrollments(Request $request)
    {
        return InternshipEnrollment::query()
            ->with(['internshipPeriod.program', 'studyProgram', 'internshipPlace'])
            ->where('status', 'active')
            ->whereHas('internshipPeriod', fn ($query) => $query->where('is_active', true)->where('is_locked', false))
            ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->latest('id')
            ->get();
    }

    private function studentRequests(Request $request)
    {
        return WfaRequest::query()
            ->with(['enrollment.internshipPeriod.program', 'enrollment.studyProgram', 'enrollment.internshipPlace', 'reviewer'])
            ->whereHas('enrollment.student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->latest('id')
            ->get();
    }

    private function hasPendingRequest(Request $request): bool
    {
        return WfaRequest::query()
            ->where('status', 'pending')
            ->whereHas('enrollment.student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->exists();
    }

    private function hasOverlappingRequest(InternshipEnrollment $enrollment, string $startsAt, string $endsAt): bool
    {
        return $enrollment->wfaRequests
            ->contains(fn (WfaRequest $request): bool => $request->starts_at->toDateString() <= $endsAt
                && $request->ends_at->toDateString() >= $startsAt);
    }

    private function ensureDatesWithinAttendanceRange(InternshipEnrollment $enrollment, string $startsAt, string $endsAt): void
    {
        $period = $enrollment->internshipPeriod;
        $attendanceStart = $enrollment->attendance_starts_at ?? $period?->starts_at;
        $attendanceEnd = $enrollment->attendance_ends_at ?? $period?->ends_at;
        $today = LocalClock::today()->toDateString();

        if ($startsAt < $today) {
            throw ValidationException::withMessages(['starts_at' => 'Tanggal mulai WFA tidak boleh sebelum hari ini.']);
        }

        if ($attendanceStart && $startsAt < $attendanceStart->toDateString()) {
            throw ValidationException::withMessages(['starts_at' => 'Tanggal mulai WFA berada di luar rentang presensi periode.']);
        }

        if ($attendanceEnd && $endsAt > $attendanceEnd->toDateString()) {
            throw ValidationException::withMessages(['ends_at' => 'Tanggal selesai WFA berada di luar rentang presensi periode.']);
        }
    }

    private function authorizeStudentRequest(Request $request, WfaRequest $wfaRequest): void
    {
        abort_unless(
            $wfaRequest->enrollment()
                ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()?->id))
                ->exists(),
            403,
        );
    }
}
