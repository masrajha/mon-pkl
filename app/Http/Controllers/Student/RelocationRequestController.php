<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPlace;
use App\Models\RelocationRequest;
use App\Services\RelocationEmailNotificationService;
use App\Services\StudentWorkflowAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RelocationRequestController extends Controller
{
    public function __construct(
        private readonly RelocationEmailNotificationService $relocationEmails,
        private readonly StudentWorkflowAccessService $workflowAccess,
    )
    {
    }

    public function index(Request $request): View
    {
        return view('student.relocations.index', [
            'requests' => $this->studentRequests($request),
            'hasPendingRequest' => $this->hasPendingRequest($request),
        ]);
    }

    public function create(Request $request): View
    {
        $selectedEnrollmentId = (int) $request->integer('enrollment_id');

        return view('student.relocations.create', [
            'enrollments' => $this->activeEnrollments($request),
            'places' => InternshipPlace::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedEnrollmentId' => $selectedEnrollmentId,
            'hasPendingRequest' => $this->hasPendingRequest($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $enrollmentIds = $this->activeEnrollments($request)->pluck('id');

        $data = $request->validate([
            'internship_enrollment_id' => ['required', Rule::in($enrollmentIds)],
            'new_internship_place_id' => ['required', Rule::exists('internship_places', 'id')->where('is_active', true)],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $enrollment = InternshipEnrollment::query()->findOrFail($data['internship_enrollment_id']);
        $enrollment->loadMissing('internshipPeriod.setting');

        if (! $this->workflowAccess->relocationOpen($enrollment)) {
            throw ValidationException::withMessages([
                'internship_enrollment_id' => 'Pengajuan pindah mitra untuk periode ini sedang ditutup.',
            ]);
        }

        if ($this->hasPendingRequest($request)) {
            throw ValidationException::withMessages([
                'internship_enrollment_id' => 'Masih ada permohonan pindah mitra yang menunggu persetujuan. Batalkan atau tunggu keputusan terlebih dahulu.',
            ]);
        }

        if ((int) $enrollment->internship_place_id === (int) $data['new_internship_place_id']) {
            throw ValidationException::withMessages(['new_internship_place_id' => 'Mitra tujuan harus berbeda dari mitra saat ini.']);
        }

        $relocation = RelocationRequest::query()->create($data + [
            'current_internship_place_id' => $enrollment->internship_place_id,
            'status' => 'pending',
        ]);
        $this->relocationEmails->submitted($relocation);

        return redirect()->route('student.dashboard')->with('status', 'Permohonan pindah mitra berhasil dikirim.');
    }

    public function cancel(Request $request, RelocationRequest $relocation): RedirectResponse
    {
        $this->authorizeStudentRequest($request, $relocation);

        if ($relocation->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Permohonan hanya dapat dibatalkan saat masih menunggu.']);
        }

        $relocation->update([
            'status' => 'cancelled',
            'admin_note' => 'Dibatalkan oleh mahasiswa.',
        ]);

        return redirect()->route('student.relocations.index')->with('status', 'Permohonan pindah mitra dibatalkan.');
    }

    private function activeEnrollments(Request $request)
    {
        return InternshipEnrollment::query()
            ->with(['internshipPeriod.program', 'internshipPeriod.setting', 'studyProgram', 'internshipPlace'])
            ->where('status', 'active')
            ->whereHas('internshipPeriod', fn ($query) => $query->where('is_active', true)->where('is_locked', false))
            ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->latest('id')
            ->get()
            ->filter(fn (InternshipEnrollment $enrollment) => $this->workflowAccess->relocationOpen($enrollment))
            ->values();
    }

    private function studentRequests(Request $request)
    {
        return RelocationRequest::query()
            ->with(['enrollment.internshipPeriod.program', 'enrollment.studyProgram', 'currentPlace', 'newPlace', 'reviewer'])
            ->whereHas('enrollment.student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->latest('id')
            ->get();
    }

    private function hasPendingRequest(Request $request): bool
    {
        return RelocationRequest::query()
            ->where('status', 'pending')
            ->whereHas('enrollment.student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->exists();
    }

    private function authorizeStudentRequest(Request $request, RelocationRequest $relocation): void
    {
        abort_unless(
            $relocation->enrollment()
                ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()?->id))
                ->exists(),
            403,
        );
    }
}
