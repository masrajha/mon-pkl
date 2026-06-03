<?php

namespace App\Services;

use App\Models\EmailNotification;
use App\Models\InternshipPlaceProposal;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PlaceProposalEmailNotificationService
{
    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function submitted(InternshipPlaceProposal $proposal): void
    {
        $proposal->loadMissing(['student.user', 'studyProgram', 'internshipPeriod.program']);

        $this->notifyStudent(
            $proposal,
            'place_proposal.submitted.student',
            '[SiLAT] Usulan Mitra Dikirim',
            [
                'Usulan mitra Anda sudah dikirim dan menunggu validasi admin.',
                $this->contextLine($proposal),
                'Silakan pantau dashboard SiLAT untuk melihat status terbaru.',
            ],
            route('student.dashboard'),
            'student-place-proposal-submitted-'.$proposal->id,
        );

        $this->notifyAdmins(
            $proposal,
            'place_proposal.submitted.admin',
            '[SiLAT] Usulan Mitra Baru Menunggu Validasi',
            [
                ($proposal->student?->full_name ?: 'Mahasiswa').' mengirim usulan mitra baru.',
                $this->contextLine($proposal),
                'Mohon lakukan validasi usulan mitra pada SiLAT.',
            ],
            route('management.place-proposals.index', ['status' => 'pending']),
            'admin-place-proposal-submitted-'.$proposal->id,
        );
    }

    public function reviewed(InternshipPlaceProposal $proposal, string $status): void
    {
        $proposal->loadMissing(['student.user', 'studyProgram', 'internshipPeriod.program', 'approvedPlace']);

        $subject = match ($status) {
            'approved' => '[SiLAT] Usulan Mitra Disetujui',
            'merged' => '[SiLAT] Usulan Mitra Digabung ke Master Mitra',
            'rejected' => '[SiLAT] Usulan Mitra Ditolak',
            default => '[SiLAT] Status Usulan Mitra Diperbarui',
        };

        $statusLine = match ($status) {
            'approved' => 'Usulan mitra Anda disetujui sebagai master mitra baru.',
            'merged' => 'Usulan mitra Anda disetujui dan digabung ke master mitra yang sudah ada.',
            'rejected' => 'Usulan mitra Anda ditolak.',
            default => 'Status usulan mitra Anda diperbarui.',
        };

        $lines = [
            $statusLine,
            $this->contextLine($proposal),
        ];

        if ($proposal->approvedPlace?->name && in_array($status, ['approved', 'merged'], true)) {
            $lines[] = 'Master mitra: '.$proposal->approvedPlace->name.'.';
        }

        if ($proposal->admin_note) {
            $lines[] = 'Catatan: '.$proposal->admin_note;
        }

        $lines[] = 'Silakan buka dashboard SiLAT untuk melihat detail terbaru.';

        $this->notifyStudent(
            $proposal,
            'place_proposal.reviewed.'.$status.'.student',
            $subject,
            $lines,
            route('student.dashboard'),
            'student-place-proposal-reviewed-'.$status.'-'.$proposal->id.'-'.$proposal->updated_at?->timestamp,
        );
    }

    public function queuePendingReminders(int $pendingHours = 48): int
    {
        $queuedBefore = EmailNotification::query()->count();

        InternshipPlaceProposal::query()
            ->with(['student', 'studyProgram', 'internshipPeriod.program'])
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours($pendingHours))
            ->chunkById(100, function (Collection $proposals) use ($pendingHours): void {
                $proposals->each(function (InternshipPlaceProposal $proposal) use ($pendingHours): void {
                    $this->notifyAdmins(
                        $proposal,
                        'place_proposal.pending.reminder.admin',
                        '[SiLAT] Reminder Usulan Mitra Belum Diproses',
                        [
                            'Usulan mitra masih berstatus pending lebih dari '.$pendingHours.' jam.',
                            $this->contextLine($proposal),
                            'Mohon segera proses usulan mitra pada SiLAT.',
                        ],
                        route('management.place-proposals.index', ['status' => 'pending']),
                        'admin-place-proposal-pending-reminder-'.$proposal->id,
                    );
                });
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    private function notifyStudent(InternshipPlaceProposal $proposal, string $type, string $subject, array $lines, string $actionUrl, string $eventKey): void
    {
        $email = $proposal->student?->student_email ?: $proposal->student?->user?->email;

        if (! $email) {
            return;
        }

        $this->emails->queue(
            type: $type,
            recipientEmail: $email,
            subject: $subject,
            bodyLines: $lines,
            recipientName: $proposal->student?->full_name,
            actionText: 'Buka SiLAT',
            actionUrl: $actionUrl,
            notifiable: $proposal,
            eventKey: $eventKey,
        );
    }

    private function notifyAdmins(InternshipPlaceProposal $proposal, string $type, string $subject, array $lines, string $actionUrl, string $eventKeyPrefix): void
    {
        $this->adminRecipients()->each(function (array $recipient) use ($proposal, $type, $subject, $lines, $actionUrl, $eventKeyPrefix): void {
            $this->emails->queue(
                type: $type,
                recipientEmail: $recipient['email'],
                subject: $subject,
                bodyLines: $lines,
                recipientName: $recipient['name'],
                actionText: 'Buka Validasi Usulan Mitra',
                actionUrl: $actionUrl,
                notifiable: $proposal,
                eventKey: $eventKeyPrefix.'-'.$recipient['email'],
            );
        });
    }

    private function adminRecipients(): Collection
    {
        return User::query()
            ->where('role', 'admin')
            ->get(['name', 'email'])
            ->filter(fn (User $user) => filled($user->email))
            ->map(fn (User $user) => [
                'name' => $user->name ?: $user->email,
                'email' => Str::lower(trim($user->email)),
            ])
            ->unique('email')
            ->values();
    }

    private function contextLine(InternshipPlaceProposal $proposal): string
    {
        return sprintf(
            'Mitra: %s. Mahasiswa: %s (%s). Program/periode: %s. Prodi: %s. Lokasi: %s.',
            $proposal->name,
            $proposal->student?->full_name ?: '-',
            $proposal->student?->npm ?: '-',
            $proposal->internshipPeriod?->display_name ?: '-',
            $proposal->studyProgram?->name ?: '-',
            trim(($proposal->city_name ?: $proposal->city?->name ?: '-').' / '.$proposal->latitude.', '.$proposal->longitude),
        );
    }
}
