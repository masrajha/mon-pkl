<?php

namespace App\Services;

use App\Models\InternshipCoordinator;
use App\Models\SupervisorChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SupervisorChangeEmailNotificationService
{
    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function submitted(SupervisorChangeRequest $request): void
    {
        $request->loadMissing(['enrollment.student.user', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.internshipPlace', 'requestedLecturer']);

        $this->notifyStudent(
            $request,
            'supervisor_change.submitted.student',
            '[SiLAT] Permohonan Perubahan Pembimbing Dikirim',
            [
                'Permohonan perubahan pembimbing Anda sudah dikirim dan menunggu validasi admin/koordinator.',
                $this->contextLine($request),
                'Silakan pantau dashboard SiLAT untuk melihat status terbaru.',
            ],
            route('student.dashboard'),
            'student-supervisor-change-submitted-'.$request->id,
        );

        $this->notifyReviewers(
            $request,
            'supervisor_change.submitted.reviewer',
            '[SiLAT] Permohonan Perubahan Pembimbing Baru',
            [
                ($request->enrollment?->student?->full_name ?: 'Mahasiswa').' mengirim permohonan perubahan pembimbing.',
                $this->contextLine($request),
                'Mohon proses permohonan pada SiLAT.',
            ],
            route('management.supervisor-requests.index', ['status' => 'pending']),
            'reviewer-supervisor-change-submitted-'.$request->id,
        );
    }

    public function reviewed(SupervisorChangeRequest $request): void
    {
        $request->loadMissing(['enrollment.student.user', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.internshipPlace', 'enrollment.lecturer', 'currentLecturer', 'requestedLecturer']);

        $approved = $request->status === 'approved';
        $subject = $approved
            ? '[SiLAT] Permohonan Perubahan Pembimbing Disetujui'
            : '[SiLAT] Permohonan Perubahan Pembimbing Ditolak';

        $lines = [
            $approved ? 'Permohonan perubahan pembimbing Anda disetujui.' : 'Permohonan perubahan pembimbing Anda ditolak.',
            $this->contextLine($request),
        ];

        if ($request->admin_note) {
            $lines[] = 'Catatan: '.$request->admin_note;
        }

        $lines[] = 'Silakan buka dashboard SiLAT untuk melihat detail terbaru.';

        $this->notifyStudent(
            $request,
            'supervisor_change.reviewed.'.$request->status.'.student',
            $subject,
            $lines,
            route('student.dashboard'),
            'student-supervisor-change-reviewed-'.$request->status.'-'.$request->id.'-'.$request->updated_at?->timestamp,
        );

        if (! $approved) {
            return;
        }

        $currentLecturer = $request->currentLecturer;
        $newLecturer = $request->enrollment?->lecturer;

        if ($newLecturer && (int) $currentLecturer?->id !== (int) $newLecturer->id) {
            $this->notifyLecturer(
                lecturerName: $newLecturer->name,
                lecturerEmail: $newLecturer->email ?: $newLecturer->user?->email,
                request: $request,
                type: 'supervisor_change.lecturer.assigned',
                subject: '[SiLAT] Mahasiswa Ditetapkan sebagai Bimbingan Anda',
                firstLine: 'Anda ditetapkan sebagai dosen pembimbing mahasiswa berikut.',
                eventKey: 'lecturer-supervisor-change-assigned-'.$request->id.'-'.$newLecturer->id,
            );
        }

        if ($currentLecturer && (int) $currentLecturer->id !== (int) $newLecturer?->id) {
            $this->notifyLecturer(
                lecturerName: $currentLecturer->name,
                lecturerEmail: $currentLecturer->email ?: $currentLecturer->user?->email,
                request: $request,
                type: 'supervisor_change.lecturer.unassigned',
                subject: '[SiLAT] Mahasiswa Tidak Lagi Menjadi Bimbingan Anda',
                firstLine: 'Mahasiswa berikut tidak lagi berada dalam daftar bimbingan Anda.',
                eventKey: 'lecturer-supervisor-change-unassigned-'.$request->id.'-'.$currentLecturer->id,
            );
        }
    }

    public function queuePendingReminders(int $hours = 48): int
    {
        $queued = 0;

        SupervisorChangeRequest::query()
            ->with(['enrollment.student.user', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.internshipPlace', 'requestedLecturer'])
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours($hours))
            ->whereDoesntHave('emailNotifications', fn (Builder $query) => $query->where('type', 'supervisor_change.pending.reminder.reviewer'))
            ->get()
            ->each(function (SupervisorChangeRequest $request) use (&$queued, $hours): void {
                $queued += $this->notifyReviewers(
                    $request,
                    'supervisor_change.pending.reminder.reviewer',
                    '[SiLAT] Reminder Permohonan Perubahan Pembimbing Pending',
                    [
                        'Ada permohonan perubahan pembimbing yang masih pending.',
                        $this->contextLine($request),
                        'Mohon proses permohonan pada SiLAT.',
                    ],
                    route('management.supervisor-requests.index', ['status' => 'pending']),
                    'reviewer-supervisor-change-pending-reminder-'.$hours.'-'.$request->id,
                );
            });

        return $queued;
    }

    private function notifyStudent(SupervisorChangeRequest $request, string $type, string $subject, array $lines, string $actionUrl, string $eventKey): void
    {
        $student = $request->enrollment?->student;
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
            notifiable: $request,
            eventKey: $eventKey,
        );
    }

    private function notifyReviewers(SupervisorChangeRequest $request, string $type, string $subject, array $lines, string $actionUrl, string $eventKeyPrefix): int
    {
        $recipients = $this->reviewerRecipients($request);

        $recipients->each(function (array $recipient) use ($request, $type, $subject, $lines, $actionUrl, $eventKeyPrefix): void {
            $this->emails->queue(
                type: $type,
                recipientEmail: $recipient['email'],
                subject: $subject,
                bodyLines: $lines,
                recipientName: $recipient['name'],
                actionText: 'Buka Perubahan Pembimbing',
                actionUrl: $actionUrl,
                notifiable: $request,
                eventKey: $eventKeyPrefix.'-'.$recipient['email'],
            );
        });

        return $recipients->count();
    }

    private function notifyLecturer(string $lecturerName, ?string $lecturerEmail, SupervisorChangeRequest $request, string $type, string $subject, string $firstLine, string $eventKey): void
    {
        if (! $lecturerEmail) {
            return;
        }

        $this->emails->queue(
            type: $type,
            recipientEmail: $lecturerEmail,
            subject: $subject,
            bodyLines: [
                $firstLine,
                $this->contextLine($request),
            ],
            recipientName: $lecturerName,
            actionText: 'Buka SiLAT',
            actionUrl: route('dashboard'),
            notifiable: $request,
            eventKey: $eventKey,
        );
    }

    private function reviewerRecipients(SupervisorChangeRequest $request): Collection
    {
        $enrollment = $request->enrollment;

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

    private function contextLine(SupervisorChangeRequest $request): string
    {
        $enrollment = $request->enrollment;

        return sprintf(
            'Mahasiswa: %s (%s). Program/periode: %s. Prodi: %s. Mitra: %s.',
            $enrollment?->student?->full_name ?: '-',
            $enrollment?->student?->npm ?: '-',
            $enrollment?->internshipPeriod?->display_name ?: '-',
            $enrollment?->studyProgram?->name ?: '-',
            $enrollment?->internshipPlace?->name ?: '-',
        );
    }
}
