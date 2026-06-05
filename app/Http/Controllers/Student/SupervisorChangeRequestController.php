<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\Lecturer;
use App\Models\SupervisorChangeRequest;
use App\Services\SupervisorChangeEmailNotificationService;
use App\Services\StudentWorkflowAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupervisorChangeRequestController extends Controller
{
    public function __construct(
        private readonly SupervisorChangeEmailNotificationService $supervisorEmails,
        private readonly StudentWorkflowAccessService $workflowAccess,
    )
    {
    }

    public function index(Request $request): View
    {
        return view('student.supervisor-requests.index', [
            'requests' => $this->studentRequests($request),
            'hasPendingRequest' => $this->hasPendingRequest($request),
        ]);
    }

    public function create(Request $request): View
    {
        $selectedEnrollmentId = (int) $request->integer('enrollment_id');

        return view('student.supervisor-requests.create', [
            'enrollments' => $this->activeEnrollments($request),
            'lecturers' => Lecturer::query()->where('status', 'active')->orderBy('name')->get(),
            'selectedEnrollmentId' => $selectedEnrollmentId,
            'hasPendingRequest' => $this->hasPendingRequest($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $enrollmentIds = $this->activeEnrollments($request)->pluck('id');

        $data = $request->validate([
            'internship_enrollment_id' => ['required', Rule::in($enrollmentIds)],
            'requested_lecturer_supervisor_id' => ['nullable', Rule::exists('lecturers', 'id')->where('status', 'active')],
            'requested_field_supervisor' => ['nullable', 'string', 'max:255'],
            'requested_field_supervisor_phone' => ['nullable', 'string', 'max:50'],
            'requested_field_supervisor_email' => ['nullable', 'email', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $enrollment = InternshipEnrollment::query()
            ->with([
                'internshipPeriod.setting',
                'supervisorChangeRequests' => fn ($query) => $query->where('status', 'pending'),
            ])
            ->findOrFail($data['internship_enrollment_id']);

        if (! $this->workflowAccess->supervisorChangeOpen($enrollment)) {
            throw ValidationException::withMessages([
                'internship_enrollment_id' => 'Pengajuan perubahan pembimbing untuk periode ini sedang ditutup.',
            ]);
        }

        if ($enrollment->supervisorChangeRequests->isNotEmpty() || $this->hasPendingRequest($request)) {
            throw ValidationException::withMessages([
                'internship_enrollment_id' => 'Masih ada permohonan perubahan pembimbing yang menunggu persetujuan. Batalkan atau tunggu keputusan terlebih dahulu.',
            ]);
        }

        if (
            empty($data['requested_lecturer_supervisor_id'])
            && blank($data['requested_field_supervisor'])
            && blank($data['requested_field_supervisor_phone'])
            && blank($data['requested_field_supervisor_email'])
        ) {
            throw ValidationException::withMessages([
                'requested_field_supervisor' => 'Isi minimal salah satu data pembimbing yang ingin diajukan.',
            ]);
        }

        $supervisorRequest = SupervisorChangeRequest::query()->create($data + [
            'current_lecturer_supervisor_id' => $enrollment->lecturer_supervisor_id,
            'current_field_supervisor' => $enrollment->field_supervisor,
            'current_field_supervisor_phone' => $enrollment->field_supervisor_phone,
            'current_field_supervisor_email' => $enrollment->field_supervisor_email,
            'status' => 'pending',
        ]);
        $this->supervisorEmails->submitted($supervisorRequest);

        return redirect()->route('student.dashboard')->with('status', 'Permohonan perubahan pembimbing berhasil dikirim.');
    }

    public function cancel(Request $request, SupervisorChangeRequest $supervisorRequest): RedirectResponse
    {
        $this->authorizeStudentRequest($request, $supervisorRequest);

        if ($supervisorRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Permohonan hanya dapat dibatalkan saat masih menunggu.']);
        }

        $supervisorRequest->update([
            'status' => 'cancelled',
            'admin_note' => 'Dibatalkan oleh mahasiswa.',
        ]);

        return redirect()->route('student.supervisor-requests.index')->with('status', 'Permohonan perubahan pembimbing dibatalkan.');
    }

    private function activeEnrollments(Request $request)
    {
        return InternshipEnrollment::query()
            ->with(['internshipPeriod.program', 'internshipPeriod.setting', 'studyProgram', 'internshipPlace', 'lecturer'])
            ->where('status', 'active')
            ->whereHas('internshipPeriod', fn ($query) => $query->where('is_active', true)->where('is_locked', false))
            ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->latest('id')
            ->get()
            ->filter(fn (InternshipEnrollment $enrollment) => $this->workflowAccess->supervisorChangeOpen($enrollment))
            ->values();
    }

    private function studentRequests(Request $request)
    {
        return SupervisorChangeRequest::query()
            ->with(['enrollment.internshipPeriod.program', 'enrollment.studyProgram', 'enrollment.internshipPlace', 'currentLecturer', 'requestedLecturer', 'reviewer'])
            ->whereHas('enrollment.student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->latest('id')
            ->get();
    }

    private function hasPendingRequest(Request $request): bool
    {
        return SupervisorChangeRequest::query()
            ->where('status', 'pending')
            ->whereHas('enrollment.student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->exists();
    }

    private function authorizeStudentRequest(Request $request, SupervisorChangeRequest $supervisorRequest): void
    {
        abort_unless(
            $supervisorRequest->enrollment()
                ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()?->id))
                ->exists(),
            403,
        );
    }
}
