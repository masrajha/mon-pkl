<?php

namespace App\Services;

use App\Models\EmailNotification;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\SeminarRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssessmentEmailNotificationService
{
    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function seminarScheduled(SeminarRequest $seminarRequest): void
    {
        $seminarRequest->loadMissing(['enrollment.student', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.internshipPlace', 'enrollment.lecturer.user']);
        $this->notifyLecturer(
            $seminarRequest,
            'assessment.lecturer.eligible',
            '[SiLAT] Mahasiswa Siap Dinilai',
            [
                'Mahasiswa bimbingan Anda sudah memiliki jadwal seminar dan siap dinilai setelah seminar berlangsung.',
                $this->seminarContext($seminarRequest),
                'Silakan isi nilai seminar melalui SiLAT.',
            ],
            'assessment-eligible-'.$seminarRequest->id.'-'.$seminarRequest->updated_at?->timestamp,
        );
    }

    public function lecturerScoreStored(SeminarRequest $seminarRequest): void
    {
        $seminarRequest->loadMissing(['enrollment.student', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.internshipPlace', 'enrollment.lecturer.user', 'enrollment.fieldSupervisorAssessment']);
        $score = number_format((float) $seminarRequest->seminar_score, 2, ',', '.');

        $this->notifyLecturer(
            $seminarRequest,
            'assessment.lecturer.score.stored',
            '[SiLAT] Nilai Seminar Tersimpan',
            [
                'Nilai seminar mahasiswa berhasil disimpan.',
                $this->seminarContext($seminarRequest),
                'Nilai seminar: '.$score.'.',
            ],
            'assessment-score-stored-'.$seminarRequest->id.'-'.$seminarRequest->updated_at?->timestamp,
        );

        if ($seminarRequest->enrollment?->fieldSupervisorAssessment) {
            $this->notifyReviewersReady($seminarRequest->enrollment, 'assessment-ready-after-lecturer-'.$seminarRequest->id);
        }
    }

    public function fieldSupervisorScoreStored(InternshipEnrollment $enrollment): void
    {
        $enrollment->loadMissing(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'fieldSupervisorAssessment', 'seminarRequests' => fn ($query) => $query->whereNotNull('seminar_score')]);

        if ($enrollment->fieldSupervisorAssessment && $this->hasLecturerScore($enrollment)) {
            $this->notifyReviewersReady($enrollment, 'assessment-ready-after-field-supervisor-'.$enrollment->id.'-'.$enrollment->fieldSupervisorAssessment->updated_at?->timestamp);
        }
    }

    public function queueLecturerAssessmentReminders(): int
    {
        $queuedBefore = EmailNotification::query()->count();

        SeminarRequest::query()
            ->with(['enrollment.student', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.internshipPlace', 'enrollment.lecturer.user'])
            ->where('status', 'scheduled')
            ->whereNull('seminar_score')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get()
            ->each(function (SeminarRequest $seminarRequest): void {
                $this->notifyLecturer(
                    $seminarRequest,
                    'assessment.lecturer.reminder',
                    '[SiLAT] Reminder Pengisian Nilai Seminar',
                    [
                        'Seminar mahasiswa sudah terjadwal/berlangsung dan nilai seminar belum tersimpan.',
                        $this->seminarContext($seminarRequest),
                        'Mohon isi nilai seminar melalui SiLAT.',
                    ],
                    'assessment-lecturer-reminder-'.$seminarRequest->id.'-'.now()->toDateString(),
                );
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    public function queueFinalizationAlerts(int $daysBeforeEnd = 7): int
    {
        $queuedBefore = EmailNotification::query()->count();
        $today = now()->startOfDay();

        $enrollments = InternshipEnrollment::query()
            ->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'fieldSupervisorAssessment', 'finalAssessment', 'seminarRequests' => fn ($query) => $query->whereNotNull('seminar_score')])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereDoesntHave('finalAssessment')
            ->get();

        $ready = $enrollments->filter(fn (InternshipEnrollment $enrollment) => $this->hasLecturerScore($enrollment) && $enrollment->fieldSupervisorAssessment);
        $nearEndIncomplete = $enrollments->filter(function (InternshipEnrollment $enrollment) use ($today, $daysBeforeEnd): bool {
            if (! $enrollment->internshipPeriod?->ends_at) {
                return false;
            }

            $endsAt = $enrollment->internshipPeriod->ends_at->copy()->startOfDay();

            return $today->diffInDays($endsAt, false) <= $daysBeforeEnd
                && $today->diffInDays($endsAt, false) >= 0
                && (! $this->hasLecturerScore($enrollment) || ! $enrollment->fieldSupervisorAssessment);
        });

        if ($ready->isEmpty() && $nearEndIncomplete->isEmpty()) {
            return 0;
        }

        User::query()
            ->where('role', 'admin')
            ->get(['id', 'name', 'email'])
            ->each(function (User $admin) use ($ready, $nearEndIncomplete): void {
                $this->emails->queue(
                    type: 'assessment.finalization.alert.admin',
                    recipientEmail: $admin->email,
                    subject: '[SiLAT] Rekap Kesiapan Finalisasi Nilai',
                    bodyLines: $this->finalizationLines($ready, $nearEndIncomplete),
                    recipientName: $admin->name,
                    actionText: 'Buka Finalisasi Nilai',
                    actionUrl: route('management.final-assessments.index'),
                    eventKey: 'assessment-finalization-admin-'.$admin->id.'-'.now()->toDateString(),
                );
            });

        InternshipCoordinator::query()
            ->with('lecturer.user')
            ->where('status', 'active')
            ->get()
            ->each(function (InternshipCoordinator $coordinator) use ($ready, $nearEndIncomplete): void {
                $scopedReady = $this->scopeEnrollments($ready, $coordinator);
                $scopedIncomplete = $this->scopeEnrollments($nearEndIncomplete, $coordinator);

                if ($scopedReady->isEmpty() && $scopedIncomplete->isEmpty()) {
                    return;
                }

                $email = $coordinator->lecturer?->email ?: $coordinator->lecturer?->user?->email;

                if (! $email) {
                    return;
                }

                $this->emails->queue(
                    type: 'assessment.finalization.alert.coordinator',
                    recipientEmail: $email,
                    subject: '[SiLAT] Rekap Kesiapan Finalisasi Nilai',
                    bodyLines: $this->finalizationLines($scopedReady, $scopedIncomplete),
                    recipientName: $coordinator->lecturer?->name ?: $coordinator->lecturer?->user?->name,
                    actionText: 'Buka Finalisasi Nilai',
                    actionUrl: route('management.final-assessments.index', [
                        'period_id' => $coordinator->internship_period_id,
                    ]),
                    eventKey: 'assessment-finalization-coordinator-'.$coordinator->id.'-'.now()->toDateString(),
                );
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    private function notifyReviewersReady(InternshipEnrollment $enrollment, string $keyPrefix): void
    {
        User::query()
            ->where('role', 'admin')
            ->get(['id', 'name', 'email'])
            ->each(function (User $admin) use ($enrollment, $keyPrefix): void {
                $this->emails->queue(
                    type: 'assessment.components.ready.admin',
                    recipientEmail: $admin->email,
                    subject: '[SiLAT] Komponen Nilai Lengkap',
                    bodyLines: [
                        'Nilai dosen dan nilai Pembimbing Lapangan sudah lengkap. Mahasiswa siap difinalisasi.',
                        $this->enrollmentContext($enrollment),
                    ],
                    recipientName: $admin->name,
                    actionText: 'Buka Finalisasi Nilai',
                    actionUrl: route('management.final-assessments.index'),
                    notifiable: $enrollment,
                    eventKey: $keyPrefix.'-admin-'.$admin->id,
                );
            });
    }

    private function notifyLecturer(SeminarRequest $seminarRequest, string $type, string $subject, array $lines, string $eventKey): void
    {
        $lecturer = $seminarRequest->enrollment?->lecturer;
        $email = $lecturer?->email ?: $lecturer?->user?->email;

        if (! $email) {
            return;
        }

        $this->emails->queue(
            type: $type,
            recipientEmail: $email,
            subject: $subject,
            bodyLines: $lines,
            recipientName: $lecturer?->name ?: $lecturer?->user?->name,
            actionText: 'Buka Review Seminar',
            actionUrl: route('management.seminar-requests.index'),
            notifiable: $seminarRequest,
            eventKey: $eventKey.'-'.$email,
        );
    }

    private function hasLecturerScore(InternshipEnrollment $enrollment): bool
    {
        return $enrollment->seminarRequests->contains(fn (SeminarRequest $seminarRequest) => $seminarRequest->seminar_score !== null);
    }

    private function scopeEnrollments(Collection $enrollments, InternshipCoordinator $coordinator): Collection
    {
        return $enrollments
            ->filter(fn (InternshipEnrollment $enrollment) => $enrollment->internship_period_id === $coordinator->internship_period_id
                && $enrollment->study_program_id === $coordinator->study_program_id)
            ->values();
    }

    private function finalizationLines(Collection $ready, Collection $incomplete): array
    {
        $lines = [
            'Siap finalisasi: '.$ready->count().' mahasiswa.',
            'Belum lengkap mendekati penutupan periode: '.$incomplete->count().' mahasiswa.',
        ];

        if ($ready->isNotEmpty()) {
            $lines[] = 'Siap finalisasi: '.$ready->take(5)->map(fn (InternshipEnrollment $enrollment) => $enrollment->student?->full_name ?: '-')->join('; ').'.';
        }

        if ($incomplete->isNotEmpty()) {
            $lines[] = 'Belum lengkap: '.$incomplete->take(5)->map(fn (InternshipEnrollment $enrollment) => ($enrollment->student?->full_name ?: '-').' ('.($this->hasLecturerScore($enrollment) ? 'nilai dosen ada' : 'nilai dosen kosong').', '.($enrollment->fieldSupervisorAssessment ? 'nilai Pembimbing Lapangan ada' : 'nilai Pembimbing Lapangan kosong').')')->join('; ').'.';
        }

        return $lines;
    }

    private function seminarContext(SeminarRequest $seminarRequest): string
    {
        return $this->enrollmentContext($seminarRequest->enrollment).' Judul seminar/laporan: '.($seminarRequest->title ?: '-').'. Jadwal: '.($seminarRequest->scheduled_at?->format('d/m/Y H:i') ?: '-').'.';
    }

    private function enrollmentContext(InternshipEnrollment $enrollment): string
    {
        return sprintf(
            'Mahasiswa: %s (%s). Program/periode: %s. Prodi: %s. Mitra: %s.',
            $enrollment->student?->full_name ?: '-',
            $enrollment->student?->npm ?: '-',
            $enrollment->internshipPeriod?->display_name ?: '-',
            $enrollment->studyProgram?->name ?: '-',
            $enrollment->internshipPlace?->name ?: '-',
        );
    }
}
