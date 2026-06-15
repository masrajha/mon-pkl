<?php

namespace App\Services;

use App\Models\EmailNotification;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\PeriodDeadline;
use App\Models\User;
use App\Support\LocalClock;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EnrollmentEmailNotificationService
{
    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function studentSubmitted(InternshipEnrollment $enrollment, bool $revision = false): void
    {
        $enrollment->loadMissing(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace']);

        $this->notifyStudent(
            $enrollment,
            $revision ? 'enrollment.revision.submitted.student' : 'enrollment.submitted.student',
            $revision ? '[SiLAT] Revisi Pendaftaran Program Dikirim' : '[SiLAT] Pendaftaran Program Dikirim',
            [
                $revision
                    ? 'Revisi pendaftaran program Anda sudah dikirim ulang dan menunggu validasi admin/koordinator.'
                    : 'Pendaftaran program Anda sudah dikirim dan menunggu validasi admin/koordinator.',
                $this->contextLine($enrollment),
                'Silakan pantau dashboard SiLAT untuk melihat status terbaru.',
            ],
            route('student.dashboard'),
            $revision ? 'student-revision-submitted-'.$enrollment->id.'-'.$enrollment->updated_at?->timestamp : 'student-submitted-'.$enrollment->id,
        );

        $this->notifyReviewers(
            $enrollment,
            $revision ? 'enrollment.revision.submitted.reviewer' : 'enrollment.submitted.reviewer',
            $revision ? '[SiLAT] Revisi Pendaftaran Menunggu Validasi' : '[SiLAT] Pendaftaran Baru Menunggu Validasi',
            [
                ($enrollment->student?->full_name ?: 'Mahasiswa').' mengirim '.($revision ? 'revisi pendaftaran' : 'pendaftaran baru').'.',
                $this->contextLine($enrollment),
                'Mohon lakukan validasi pendaftaran pada SiLAT.',
            ],
            route('management.enrollment-validations.index', [
                'period_id' => $enrollment->internship_period_id,
                'study_program_id' => $enrollment->study_program_id,
            ]),
            $revision ? 'reviewer-revision-submitted-'.$enrollment->id.'-'.$enrollment->updated_at?->timestamp : 'reviewer-submitted-'.$enrollment->id,
        );
    }

    public function validationProcessed(InternshipEnrollment $enrollment, string $status): void
    {
        $enrollment->loadMissing(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'lecturer']);

        $label = match ($status) {
            'active' => 'Disetujui',
            'revision_required' => 'Perlu Revisi',
            'rejected' => 'Ditolak',
            default => Str::headline($status),
        };

        $subject = match ($status) {
            'active' => '[SiLAT] Pendaftaran Program Anda Disetujui',
            'revision_required' => '[SiLAT] Pendaftaran Program Perlu Revisi',
            'rejected' => '[SiLAT] Pendaftaran Program Ditolak',
            default => '[SiLAT] Status Pendaftaran Program Diperbarui',
        };

        $lines = [
            'Status pendaftaran program Anda: '.$label.'.',
            $this->contextLine($enrollment),
        ];

        if ($enrollment->lecturer?->name) {
            $lines[] = 'Dosen pembimbing: '.$enrollment->lecturer->name.'.';
        }

        if ($enrollment->admin_note) {
            $lines[] = 'Catatan: '.$enrollment->admin_note;
        }

        $lines[] = $status === 'revision_required'
            ? 'Silakan perbaiki pendaftaran melalui dashboard SiLAT.'
            : 'Silakan buka dashboard SiLAT untuk melihat detail terbaru.';

        $this->notifyStudent(
            $enrollment,
            'enrollment.validation.'.$status.'.student',
            $subject,
            $lines,
            $status === 'revision_required'
                ? route('student.enrollments.edit', $enrollment)
                : route('student.dashboard'),
            'student-validation-'.$status.'-'.$enrollment->id.'-'.$enrollment->updated_at?->timestamp,
        );
    }

    public function enrollmentChangedByAdmin(InternshipEnrollment $enrollment, array $changed): void
    {
        $changed = array_values(array_intersect($changed, [
            'internship_period_id',
            'internship_place_id',
            'lecturer_supervisor_id',
            'field_supervisor',
            'field_supervisor_phone',
            'field_supervisor_email',
            'contact_student_phone',
            'has_krs_pkl',
            'total_sks',
            'current_semester',
            'gpa',
            'status',
            'admin_note',
        ]));

        if ($changed === []) {
            return;
        }

        $enrollment->loadMissing(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'lecturer']);

        $lines = [
            'Data peserta periode Anda diperbarui oleh admin.',
            $this->contextLine($enrollment),
            'Perubahan: '.collect($changed)->map(fn (string $field) => Str::headline($field))->join(', ').'.',
            'Silakan buka dashboard SiLAT untuk melihat detail terbaru.',
        ];

        if ($enrollment->admin_note) {
            $lines[] = 'Catatan: '.$enrollment->admin_note;
        }

        $this->notifyStudent(
            $enrollment,
            'enrollment.admin.updated.student',
            '[SiLAT] Data Peserta Periode Diperbarui',
            $lines,
            route('student.dashboard'),
            'student-admin-updated-'.$enrollment->id.'-'.$enrollment->updated_at?->timestamp.'-'.hash('sha256', implode('|', $changed)),
        );
    }

    public function queuePendingValidationReminders(int $daysBeforeDeadline = 3): int
    {
        $periodIds = PeriodDeadline::query()
            ->where('deadline_type', 'registration_end')
            ->whereDate('deadline_date', '>=', LocalClock::today()->toDateString())
            ->whereDate('deadline_date', '<=', LocalClock::today()->addDays($daysBeforeDeadline)->toDateString())
            ->pluck('internship_period_id');

        if ($periodIds->isEmpty()) {
            return 0;
        }

        $queuedBefore = EmailNotification::query()->count();

        InternshipEnrollment::query()
            ->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace'])
            ->whereIn('internship_period_id', $periodIds)
            ->whereIn('status', ['pending_verification', 'revision_required'])
            ->chunkById(100, function (Collection $enrollments): void {
                $enrollments->each(function (InternshipEnrollment $enrollment): void {
                    $deadline = PeriodDeadline::query()
                        ->where('internship_period_id', $enrollment->internship_period_id)
                        ->where('deadline_type', 'registration_end')
                        ->first();

                    $dateLabel = $deadline?->deadline_date?->format('d/m/Y') ?: 'mendekati batas pendaftaran';

                    $this->notifyReviewers(
                        $enrollment,
                        'enrollment.pending.reminder.reviewer',
                        '[SiLAT] Reminder Pendaftaran Belum Diproses',
                        [
                            'Pendaftaran mahasiswa masih menunggu validasi mendekati batas pendaftaran.',
                            $this->contextLine($enrollment),
                            'Batas pendaftaran: '.$dateLabel.'.',
                            'Mohon segera proses pendaftaran pada SiLAT.',
                        ],
                        route('management.enrollment-validations.index', [
                            'period_id' => $enrollment->internship_period_id,
                            'study_program_id' => $enrollment->study_program_id,
                        ]),
                        'reviewer-pending-reminder-'.$enrollment->id.'-'.$deadline?->deadline_date?->toDateString(),
                    );
                });
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    private function notifyStudent(InternshipEnrollment $enrollment, string $type, string $subject, array $lines, string $actionUrl, string $eventKey): void
    {
        $email = $enrollment->student?->student_email ?: $enrollment->student?->user?->email;

        if (! $email) {
            return;
        }

        $this->emails->queue(
            type: $type,
            recipientEmail: $email,
            subject: $subject,
            bodyLines: $lines,
            recipientName: $enrollment->student?->full_name,
            actionText: 'Buka SiLAT',
            actionUrl: $actionUrl,
            notifiable: $enrollment,
            eventKey: $eventKey,
        );
    }

    private function notifyReviewers(InternshipEnrollment $enrollment, string $type, string $subject, array $lines, string $actionUrl, string $eventKeyPrefix): void
    {
        $this->reviewerRecipients($enrollment)->each(function (array $recipient) use ($enrollment, $type, $subject, $lines, $actionUrl, $eventKeyPrefix): void {
            $this->emails->queue(
                type: $type,
                recipientEmail: $recipient['email'],
                subject: $subject,
                bodyLines: $lines,
                recipientName: $recipient['name'],
                actionText: 'Buka Validasi Pendaftaran',
                actionUrl: $actionUrl,
                notifiable: $enrollment,
                eventKey: $eventKeyPrefix.'-'.$recipient['email'],
            );
        });
    }

    private function reviewerRecipients(InternshipEnrollment $enrollment): Collection
    {
        $admins = User::query()
            ->where('role', 'admin')
            ->get(['name', 'email'])
            ->map(fn (User $user) => [
                'name' => $user->name,
                'email' => $user->email,
            ]);

        $coordinators = InternshipCoordinator::query()
            ->with('lecturer.user')
            ->where('status', 'active')
            ->where('internship_period_id', $enrollment->internship_period_id)
            ->where('study_program_id', $enrollment->study_program_id)
            ->get()
            ->map(function (InternshipCoordinator $coordinator): ?array {
                $email = $coordinator->lecturer?->email ?: $coordinator->lecturer?->user?->email;

                if (! $email) {
                    return null;
                }

                return [
                    'name' => $coordinator->lecturer?->name ?: $coordinator->lecturer?->user?->name,
                    'email' => $email,
                ];
            })
            ->filter();

        return $admins
            ->merge($coordinators)
            ->filter(fn (array $recipient) => filled($recipient['email']))
            ->map(fn (array $recipient) => [
                'name' => $recipient['name'] ?: $recipient['email'],
                'email' => Str::lower(trim($recipient['email'])),
            ])
            ->unique('email')
            ->values();
    }

    private function contextLine(InternshipEnrollment $enrollment): string
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
