<?php

namespace App\Services;

use App\Models\ForgottenAttendanceRequest;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPlaceProposal;
use App\Models\RelocationRequest;
use App\Models\SeminarRequest;
use App\Models\SubmissionProgress;
use App\Models\SupervisorChangeRequest;
use App\Models\User;
use App\Models\WfaRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ActionRequiredSummaryService
{
    public function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $activeRole = session('active_role');

        if ($activeRole === 'pembimbing_lapangan' && $user->hasRole('pembimbing_lapangan')) {
            return $this->fieldSupervisorActions($user);
        }

        if (in_array($activeRole, ['admin', 'koordinator'], true) && $user->hasRole($activeRole)) {
            return $this->managementActions($user);
        }

        if ($activeRole === 'dosen' && $user->hasRole('dosen')) {
            return $this->lecturerActions($user);
        }

        if ($user->hasRole('dosen')) {
            return $this->lecturerActions($user);
        }

        if ($user->hasRole('pembimbing_lapangan')) {
            return $this->fieldSupervisorActions($user);
        }

        if ($user->hasRole(['admin', 'koordinator'])) {
            return $this->managementActions($user);
        }

        return [];
    }

    public function total(array $summary): int
    {
        return collect($summary)->sum('count');
    }

    private function managementActions(User $user): array
    {
        return [
            'enrollment_validations' => [
                'label' => 'Validasi Pendaftaran',
                'count' => $this->enrollmentValidationCount($user),
                'route' => 'management.enrollment-validations.index',
                'params' => [],
                'icon' => 'fa-user-check',
                'description' => 'Pendaftaran mahasiswa menunggu validasi.',
            ],
            'place_proposals' => [
                'label' => 'Usulan Mitra',
                'count' => $this->placeProposalCount($user),
                'route' => 'management.place-proposals.index',
                'params' => ['status' => 'pending'],
                'icon' => 'fa-building-circle-check',
                'description' => 'Usulan mitra baru menunggu keputusan.',
            ],
            'relocations' => [
                'label' => 'Pindah Mitra',
                'count' => $this->relocationCount($user),
                'route' => 'management.relocations.index',
                'params' => ['status' => 'pending'],
                'icon' => 'fa-route',
                'description' => 'Permohonan pindah mitra menunggu persetujuan.',
            ],
            'supervisor_changes' => [
                'label' => 'Perubahan Pembimbing',
                'count' => $this->supervisorChangeCount($user),
                'route' => 'management.supervisor-requests.index',
                'params' => ['status' => 'pending'],
                'icon' => 'fa-user-pen',
                'description' => 'Permohonan perubahan pembimbing menunggu persetujuan.',
            ],
            'forgotten_attendance' => [
                'label' => 'Lupa Presensi',
                'count' => $this->forgottenAttendanceCount($user),
                'route' => 'management.forgotten-attendance-requests.index',
                'params' => ['status' => 'pending'],
                'icon' => 'fa-calendar-xmark',
                'description' => 'Pengajuan lupa presensi menunggu persetujuan.',
            ],
            'wfa_requests' => [
                'label' => 'Pengajuan WFA',
                'count' => $this->wfaRequestCount($user),
                'route' => 'management.wfa-requests.index',
                'params' => ['status' => 'pending'],
                'icon' => 'fa-laptop-house',
                'description' => 'Pengajuan Work from anywhere menunggu keputusan.',
            ],
        ];
    }

    private function enrollmentValidationCount(User $user): int
    {
        $query = InternshipEnrollment::query()->where('status', 'pending_verification');
        $this->scopeByCoordinator($query, $user);

        return $query->count();
    }

    private function placeProposalCount(User $user): int
    {
        $query = InternshipPlaceProposal::query()->where('status', 'pending');
        $this->scopeByCoordinator($query, $user);

        return $query->count();
    }

    private function relocationCount(User $user): int
    {
        $query = RelocationRequest::query()
            ->where('status', 'pending')
            ->whereHas('enrollment', fn (Builder $query) => $this->scopeByCoordinator($query, $user));

        return $query->count();
    }

    private function supervisorChangeCount(User $user): int
    {
        $query = SupervisorChangeRequest::query()
            ->where('status', 'pending')
            ->whereHas('enrollment', fn (Builder $query) => $this->scopeByCoordinator($query, $user));

        return $query->count();
    }

    private function forgottenAttendanceCount(User $user): int
    {
        return ForgottenAttendanceRequest::query()
            ->where('status', 'pending')
            ->whereHas('enrollment', fn (Builder $query) => $this->scopeByCoordinator($query, $user))
            ->count();
    }

    private function wfaRequestCount(User $user): int
    {
        $query = WfaRequest::query()
            ->where('status', 'pending')
            ->whereHas('enrollment', fn (Builder $query) => $this->scopeByCoordinator($query, $user));

        return $query->count();
    }

    private function lecturerActions(User $user): array
    {
        return [
            'lecturer_report_reviews' => [
                'label' => 'Review Laporan',
                'count' => $this->lecturerReportReviewCount($user),
                'route' => 'management.submission-progress.index',
                'params' => ['status' => 'pending'],
                'icon' => 'fa-file-circle-check',
                'description' => 'Unggahan laporan mahasiswa bimbingan menunggu review.',
            ],
            'lecturer_seminar_reviews' => [
                'label' => 'Review Seminar',
                'count' => $this->lecturerSeminarReviewCount($user),
                'route' => 'management.seminar-requests.index',
                'params' => [],
                'icon' => 'fa-person-chalkboard',
                'description' => 'ACC seminar atau penilaian seminar menunggu tindakan dosen.',
            ],
        ];
    }

    private function lecturerReportReviewCount(User $user): int
    {
        return SubmissionProgress::query()
            ->where('status', 'pending')
            ->whereHas('enrollment', fn (Builder $query) => $query->where('lecturer_supervisor_user_id', $user->id))
            ->count();
    }

    private function lecturerSeminarReviewCount(User $user): int
    {
        return SeminarRequest::query()
            ->whereIn('status', ['waiting_lecturer_approval', 'scheduled'])
            ->whereHas('enrollment', fn (Builder $query) => $query->where('lecturer_supervisor_user_id', $user->id))
            ->count();
    }

    private function fieldSupervisorActions(User $user): array
    {
        return [
            'field_supervisor_forgotten_attendance' => [
                'label' => 'Lupa Presensi',
                'count' => $this->fieldSupervisorForgottenAttendanceCount($user),
                'route' => 'field-supervisor.enrollments.index',
                'params' => [],
                'icon' => 'fa-calendar-xmark',
                'description' => 'Pengajuan lupa presensi mahasiswa bimbingan menunggu review.',
            ],
        ];
    }

    private function fieldSupervisorForgottenAttendanceCount(User $user): int
    {
        $email = Str::lower(trim((string) $user->email));

        if ($email === '') {
            return 0;
        }

        return ForgottenAttendanceRequest::query()
            ->where('status', 'pending')
            ->whereHas('enrollment', fn (Builder $query) => $query
                ->whereRaw('LOWER(field_supervisor_email) = ?', [$email])
                ->whereNotIn('status', ['cancelled', 'rejected']))
            ->count();
    }

    private function scopeByCoordinator(Builder $query, User $user): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        $assignments = $user->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->get(['internship_period_id', 'study_program_id']) ?? collect();

        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $query) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $query->orWhere(function (Builder $query) use ($assignment): void {
                    $query->where('internship_period_id', $assignment->internship_period_id)
                        ->where('study_program_id', $assignment->study_program_id);
                });
            }
        });
    }
}
