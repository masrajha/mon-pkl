<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\InternshipPeriod;
use App\Models\Lecturer;
use App\Models\SupervisorChangeRequest;
use App\Services\SupervisorChangeEmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupervisorChangeRequestController extends Controller
{
    use InteractsWithTableControls;

    public function __construct(private readonly SupervisorChangeEmailNotificationService $supervisorEmails)
    {
    }

    public function index(Request $request): View
    {
        $query = SupervisorChangeRequest::query()
            ->with([
                'enrollment.student',
                'enrollment.internshipPeriod.program',
                'enrollment.studyProgram',
                'currentLecturer',
                'requestedLecturer',
                'reviewer',
            ]);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('reason', 'like', '%'.$search.'%')
                ->orWhere('requested_field_supervisor', 'like', '%'.$search.'%')
                ->orWhere('requested_field_supervisor_email', 'like', '%'.$search.'%')
                ->orWhereHas('enrollment.student', fn ($query) => $query->where('full_name', 'like', '%'.$search.'%')->orWhere('npm', 'like', '%'.$search.'%'))
                ->orWhereHas('requestedLecturer', fn ($query) => $query->where('name', 'like', '%'.$search.'%')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $this->scopeByCoordinator($query, $request);
        $selectedPeriodId = $request->integer('period_id') ?: null;

        if ($selectedPeriodId) {
            $query->whereHas('enrollment', fn ($enrollment) => $enrollment->where('internship_period_id', $selectedPeriodId));
        }

        return view('management.supervisor-requests.index', [
            'requests' => $this->applyTableSort($query, $request, ['id', 'status'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'lecturers' => Lecturer::query()->where('status', 'active')->orderBy('name')->get(),
            'selectedStatus' => $request->string('status')->toString(),
            'selectedPeriodId' => $selectedPeriodId,
            'periodOptions' => $this->periodOptions($request),
        ]);
    }

    public function update(Request $request, SupervisorChangeRequest $supervisorRequest): RedirectResponse
    {
        $this->authorizeScope($supervisorRequest, $request);

        if ($supervisorRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Permohonan ini sudah diproses.']);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'lecturer_supervisor_id' => ['nullable', Rule::exists('lecturers', 'id')->where('status', 'active')],
            'field_supervisor' => ['nullable', 'string', 'max:255'],
            'field_supervisor_phone' => ['nullable', 'string', 'max:50'],
            'field_supervisor_email' => ['nullable', 'email', 'max:255'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === 'approved') {
            if (empty($data['lecturer_supervisor_id']) && ! $supervisorRequest->enrollment?->lecturer_supervisor_id) {
                throw ValidationException::withMessages([
                    'lecturer_supervisor_id' => 'Dosen pembimbing wajib dipilih sebelum permohonan disetujui.',
                ]);
            }

            if (blank($data['field_supervisor'] ?? null) && blank($supervisorRequest->enrollment?->field_supervisor)) {
                throw ValidationException::withMessages([
                    'field_supervisor' => 'Pembimbing lapangan wajib diisi sebelum permohonan disetujui.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $supervisorRequest, $data): void {
            $supervisorRequest->update([
                'status' => $data['status'],
                'admin_note' => $data['admin_note'] ?? null,
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);

            if ($data['status'] !== 'approved') {
                return;
            }

            $lecturer = ! empty($data['lecturer_supervisor_id'])
                ? Lecturer::query()->where('status', 'active')->find($data['lecturer_supervisor_id'])
                : null;

            $supervisorRequest->enrollment()->update([
                'lecturer_supervisor_id' => $lecturer?->id ?? $supervisorRequest->enrollment->lecturer_supervisor_id,
                'lecturer_supervisor_user_id' => $lecturer?->user_id ?? $supervisorRequest->enrollment->lecturer_supervisor_user_id,
                'lecturer_supervisor' => $lecturer?->name ?? $supervisorRequest->enrollment->lecturer_supervisor,
                'field_supervisor' => ($data['field_supervisor'] ?? null) ?: $supervisorRequest->enrollment->field_supervisor,
                'field_supervisor_phone' => ($data['field_supervisor_phone'] ?? null) ?: $supervisorRequest->enrollment->field_supervisor_phone,
                'field_supervisor_email' => ($data['field_supervisor_email'] ?? null) ?: $supervisorRequest->enrollment->field_supervisor_email,
                'admin_note' => $data['admin_note'] ?? 'Permohonan perubahan pembimbing disetujui.',
            ]);
        });
        $this->supervisorEmails->reviewed($supervisorRequest->refresh());

        return back()->with('status', 'Permohonan perubahan pembimbing berhasil diproses.');
    }

    private function scopeByCoordinator($query, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $assignments = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->get(['internship_period_id', 'study_program_id']) ?? collect();

        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('enrollment', function ($query) use ($assignments): void {
            $query->where(function ($query) use ($assignments): void {
                foreach ($assignments as $assignment) {
                    $query->orWhere(function ($query) use ($assignment): void {
                        $query->where('internship_period_id', $assignment->internship_period_id)
                            ->where('study_program_id', $assignment->study_program_id);
                    });
                }
            });
        });
    }

    private function authorizeScope(SupervisorChangeRequest $supervisorRequest, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $enrollment = $supervisorRequest->enrollment;
        $allowed = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $enrollment?->internship_period_id)
            ->where('study_program_id', $enrollment?->study_program_id)
            ->exists();

        abort_unless($allowed, 403);
    }

    private function periodOptions(Request $request)
    {
        $query = InternshipPeriod::query()->with('program')->orderByDesc('starts_at')->orderByDesc('id');
        $user = $request->user();

        if (! $user?->hasRole('admin')) {
            $periodIds = $user?->lecturer?->coordinatorAssignments()
                ->where('status', 'active')
                ->pluck('internship_period_id')
                ->filter()
                ->unique()
                ->values() ?? collect();

            $query->whereIn('id', $periodIds);
        }

        return $query->get();
    }
}
