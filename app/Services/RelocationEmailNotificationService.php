<?php

namespace App\Services;

use App\Models\InternshipCoordinator;
use App\Models\RelocationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RelocationEmailNotificationService
{
    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function submitted(RelocationRequest $relocation): void
    {
        $relocation->loadMissing(['enrollment.student.user', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'currentPlace', 'newPlace']);

        $this->notifyStudent(
            $relocation,
            'relocation.submitted.student',
            '[SiLAT] Permohonan Pindah Mitra Dikirim',
            [
                'Permohonan pindah mitra Anda sudah dikirim dan menunggu validasi admin/koordinator.',
                $this->contextLine($relocation),
                'Silakan pantau dashboard SiLAT untuk melihat status terbaru.',
            ],
            route('student.dashboard'),
            'student-relocation-submitted-'.$relocation->id,
        );

        $this->notifyReviewers(
            $relocation,
            'relocation.submitted.reviewer',
            '[SiLAT] Permohonan Pindah Mitra Baru',
            [
                ($relocation->enrollment?->student?->full_name ?: 'Mahasiswa').' mengirim permohonan pindah mitra.',
                $this->contextLine($relocation),
                'Mohon proses permohonan pindah mitra pada SiLAT.',
            ],
            route('management.relocations.index', ['status' => 'pending']),
            'reviewer-relocation-submitted-'.$relocation->id,
        );
    }

    public function reviewed(RelocationRequest $relocation): void
    {
        $relocation->loadMissing(['enrollment.student.user', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.lecturer.user', 'currentPlace', 'newPlace']);

        $approved = $relocation->status === 'approved';
        $subject = $approved
            ? '[SiLAT] Permohonan Pindah Mitra Disetujui'
            : '[SiLAT] Permohonan Pindah Mitra Ditolak';

        $lines = [
            $approved ? 'Permohonan pindah mitra Anda disetujui.' : 'Permohonan pindah mitra Anda ditolak.',
            $this->contextLine($relocation),
        ];

        if ($relocation->admin_note) {
            $lines[] = 'Catatan: '.$relocation->admin_note;
        }

        $lines[] = 'Silakan buka dashboard SiLAT untuk melihat detail terbaru.';

        $this->notifyStudent(
            $relocation,
            'relocation.reviewed.'.$relocation->status.'.student',
            $subject,
            $lines,
            route('student.dashboard'),
            'student-relocation-reviewed-'.$relocation->status.'-'.$relocation->id.'-'.$relocation->updated_at?->timestamp,
        );

        if ($approved) {
            $this->notifyLecturer($relocation);
        }
    }

    public function queuePendingReminders(int $hours = 48): int
    {
        $queued = 0;

        RelocationRequest::query()
            ->with(['enrollment.student.user', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'currentPlace', 'newPlace'])
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours($hours))
            ->whereDoesntHave('emailNotifications', fn (Builder $query) => $query->where('type', 'relocation.pending.reminder.reviewer'))
            ->get()
            ->each(function (RelocationRequest $relocation) use (&$queued, $hours): void {
                $queued += $this->notifyReviewers(
                    $relocation,
                    'relocation.pending.reminder.reviewer',
                    '[SiLAT] Reminder Permohonan Pindah Mitra Pending',
                    [
                        'Ada permohonan pindah mitra yang masih pending.',
                        $this->contextLine($relocation),
                        'Mohon proses permohonan pindah mitra pada SiLAT.',
                    ],
                    route('management.relocations.index', ['status' => 'pending']),
                    'reviewer-relocation-pending-reminder-'.$hours.'-'.$relocation->id,
                );
            });

        return $queued;
    }

    private function notifyStudent(RelocationRequest $relocation, string $type, string $subject, array $lines, string $actionUrl, string $eventKey): void
    {
        $student = $relocation->enrollment?->student;
        $email = $student?->student_email ?: $student?->user?->email;

        if (! $email) {
            return;
        }

        $this->emails->queue(
            type: $type,
            recipientEmail: $email,
            subject: $subject,
            bodyLines: $lines,
            recipientName: $student?->full_name,
            actionText: 'Buka SiLAT',
            actionUrl: $actionUrl,
            notifiable: $relocation,
            eventKey: $eventKey,
        );
    }

    private function notifyReviewers(RelocationRequest $relocation, string $type, string $subject, array $lines, string $actionUrl, string $eventKeyPrefix): int
    {
        $recipients = $this->reviewerRecipients($relocation);

        $recipients->each(function (array $recipient) use ($relocation, $type, $subject, $lines, $actionUrl, $eventKeyPrefix): void {
            $this->emails->queue(
                type: $type,
                recipientEmail: $recipient['email'],
                subject: $subject,
                bodyLines: $lines,
                recipientName: $recipient['name'],
                actionText: 'Buka Pindah Mitra',
                actionUrl: $actionUrl,
                notifiable: $relocation,
                eventKey: $eventKeyPrefix.'-'.$recipient['email'],
            );
        });

        return $recipients->count();
    }

    private function notifyLecturer(RelocationRequest $relocation): void
    {
        $lecturer = $relocation->enrollment?->lecturer;
        $email = $lecturer?->email ?: $lecturer?->user?->email;

        if (! $email) {
            return;
        }

        $this->emails->queue(
            type: 'relocation.lecturer.approved',
            recipientEmail: $email,
            subject: '[SiLAT] Mahasiswa Bimbingan Pindah Mitra',
            bodyLines: [
                'Mahasiswa bimbingan Anda memiliki permohonan pindah mitra yang sudah disetujui.',
                $this->contextLine($relocation),
            ],
            recipientName: $lecturer?->name,
            actionText: 'Buka SiLAT',
            actionUrl: route('dashboard'),
            notifiable: $relocation,
            eventKey: 'lecturer-relocation-approved-'.$relocation->id.'-'.$lecturer?->id,
        );
    }

    private function reviewerRecipients(RelocationRequest $relocation): Collection
    {
        $enrollment = $relocation->enrollment;

        $admins = User::query()
            ->where('role', 'admin')
            ->get(['name', 'email'])
            ->map(fn (User $user) => ['name' => $user->name, 'email' => $user->email]);

        $coordinators = InternshipCoordinator::query()
            ->with('lecturer.user')
            ->where('status', 'active')
            ->where('internship_period_id', $enrollment?->internship_period_id)
            ->where('study_program_id', $enrollment?->study_program_id)
            ->get()
            ->map(function (InternshipCoordinator $coordinator): ?array {
                $email = $coordinator->lecturer?->email ?: $coordinator->lecturer?->user?->email;

                return $email ? ['name' => $coordinator->lecturer?->name ?: $email, 'email' => $email] : null;
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

    private function contextLine(RelocationRequest $relocation): string
    {
        $enrollment = $relocation->enrollment;

        return sprintf(
            'Mahasiswa: %s (%s). Program/periode: %s. Prodi: %s. Mitra lama: %s. Mitra baru: %s.',
            $enrollment?->student?->full_name ?: '-',
            $enrollment?->student?->npm ?: '-',
            $enrollment?->internshipPeriod?->display_name ?: '-',
            $enrollment?->studyProgram?->name ?: '-',
            $relocation->currentPlace?->name ?: '-',
            $relocation->newPlace?->name ?: '-',
        );
    }
}
