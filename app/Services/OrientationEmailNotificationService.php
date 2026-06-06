<?php

namespace App\Services;

use App\Models\EmailNotification;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\OrientationAttendance;
use App\Models\OrientationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OrientationEmailNotificationService
{
    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function eventOpened(OrientationEvent $event): void
    {
        if (! $event->is_active) {
            return;
        }

        $event->loadMissing(['internshipPeriod.program', 'studyProgram']);

        $this->participants($event)->each(function (InternshipEnrollment $enrollment) use ($event): void {
            $this->notifyStudent(
                $enrollment,
                'orientation.opened.student',
                '[SiLAT] Presensi Pembekalan Dibuka',
                [
                    'Presensi pembekalan sudah dibuka untuk program Anda.',
                    $this->eventContext($event),
                    'Silakan lakukan presensi pembekalan melalui SiLAT.',
                ],
                route('student.orientation-attendances.create', $event),
                'orientation-opened-'.$event->id.'-'.$enrollment->student_id,
            );
        });
    }

    public function attendanceRecorded(OrientationAttendance $attendance): void
    {
        $attendance->loadMissing(['event.internshipPeriod.program', 'event.studyProgram', 'enrollment.student.user']);

        if (! $attendance->enrollment) {
            return;
        }

        $this->notifyStudent(
            $attendance->enrollment,
            'orientation.attendance.recorded.student',
            '[SiLAT] Presensi Pembekalan Berhasil Dicatat',
            [
                'Presensi pembekalan Anda berhasil dicatat.',
                $this->eventContext($attendance->event),
                'Waktu presensi: '.$attendance->checked_at?->format('d/m/Y H:i').'.',
            ],
            route('student.dashboard'),
            'orientation-attendance-recorded-'.$attendance->id,
        );
    }

    public function queueReminders(int $hoursBefore = 24, int $closingMinutes = 60): int
    {
        $queuedBefore = EmailNotification::query()->count();
        $now = now();

        OrientationEvent::query()
            ->with(['internshipPeriod.program', 'studyProgram'])
            ->where('is_active', true)
            ->whereNotNull('starts_at')
            ->whereBetween('starts_at', [$now, $now->copy()->addHours($hoursBefore)])
            ->get()
            ->each(fn (OrientationEvent $event) => $this->queueMissingAttendanceReminder(
                $event,
                'orientation.reminder.before.student',
                '[SiLAT] Reminder Presensi Pembekalan',
                'Pembekalan akan segera dimulai dan presensi Anda belum tercatat.',
                'orientation-before-'.$event->id,
                route('student.orientation-attendances.create', $event),
            ));

        OrientationEvent::query()
            ->with(['internshipPeriod.program', 'studyProgram'])
            ->where('is_active', true)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$now, $now->copy()->addMinutes($closingMinutes)])
            ->get()
            ->each(fn (OrientationEvent $event) => $this->queueMissingAttendanceReminder(
                $event,
                'orientation.reminder.closing.student',
                '[SiLAT] Presensi Pembekalan Hampir Ditutup',
                'Presensi pembekalan akan segera ditutup dan presensi Anda belum tercatat.',
                'orientation-closing-'.$event->id,
                route('student.orientation-attendances.create', $event),
            ));

        return EmailNotification::query()->count() - $queuedBefore;
    }

    public function queueClosedSummaries(int $summaryHours = 24): int
    {
        $queuedBefore = EmailNotification::query()->count();
        $now = now();

        OrientationEvent::query()
            ->with(['internshipPeriod.program', 'studyProgram', 'attendances'])
            ->where('is_active', true)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$now->copy()->subHours($summaryHours), $now])
            ->get()
            ->each(function (OrientationEvent $event): void {
                $participants = $this->participants($event);
                $present = $event->attendances->whereIn('student_id', $participants->pluck('student_id'))->count();
                $absent = max(0, $participants->count() - $present);

                $lines = [
                    'Event pembekalan sudah ditutup.',
                    $this->eventContext($event),
                    'Jumlah peserta: '.$participants->count().'. Hadir: '.$present.'. Tidak hadir: '.$absent.'.',
                    'Silakan buka SiLAT untuk melihat rincian presensi pembekalan.',
                ];

                $this->reviewerRecipients($event)->each(function (array $recipient) use ($event, $lines): void {
                    $this->emails->queue(
                        type: 'orientation.closed.summary.reviewer',
                        recipientEmail: $recipient['email'],
                        subject: '[SiLAT] Rekap Presensi Pembekalan',
                        bodyLines: $lines,
                        recipientName: $recipient['name'],
                        actionText: 'Buka Rekap Pembekalan',
                        actionUrl: route('management.orientation-events.show', $event),
                        notifiable: $event,
                        eventKey: 'orientation-closed-summary-'.$event->id.'-'.$recipient['email'],
                    );
                });
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    private function queueMissingAttendanceReminder(
        OrientationEvent $event,
        string $type,
        string $subject,
        string $message,
        string $keyPrefix,
        string $actionUrl,
    ): void {
        $presentStudentIds = $event->attendances()->pluck('student_id');

        $this->participants($event)
            ->whereNotIn('student_id', $presentStudentIds)
            ->each(function (InternshipEnrollment $enrollment) use ($event, $type, $subject, $message, $keyPrefix, $actionUrl): void {
                $this->notifyStudent(
                    $enrollment,
                    $type,
                    $subject,
                    [
                        $message,
                        $this->eventContext($event),
                        'Silakan lakukan presensi pembekalan melalui SiLAT.',
                    ],
                    $actionUrl,
                    $keyPrefix.'-'.$enrollment->student_id,
                );
            });
    }

    private function participants(OrientationEvent $event): Collection
    {
        return InternshipEnrollment::query()
            ->with(['student.user', 'studyProgram', 'internshipPeriod.program'])
            ->where('internship_period_id', $event->internship_period_id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->when($event->study_program_id, fn (Builder $query) => $query->where('study_program_id', $event->study_program_id))
            ->get();
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

    private function reviewerRecipients(OrientationEvent $event): Collection
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
            ->where('internship_period_id', $event->internship_period_id)
            ->when($event->study_program_id, fn (Builder $query) => $query->where('study_program_id', $event->study_program_id))
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

    private function eventContext(OrientationEvent $event): string
    {
        return sprintf(
            'Kegiatan: %s. Periode: %s. Prodi: %s. Lokasi: %s. Jadwal: %s - %s.',
            $event->name ?: 'Pembekalan',
            $event->internshipPeriod?->display_name ?: '-',
            $event->studyProgram?->name ?: 'Semua prodi',
            $event->location_name ?: '-',
            $event->starts_at?->format('d/m/Y H:i') ?: '-',
            $event->ends_at?->format('d/m/Y H:i') ?: '-',
        );
    }
}
