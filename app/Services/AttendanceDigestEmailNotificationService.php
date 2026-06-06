<?php

namespace App\Services;

use App\Models\EmailNotification;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AttendanceDigestEmailNotificationService
{
    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function queueWeeklyDigests(?Carbon $weekStart = null, ?Carbon $weekEnd = null): int
    {
        $weekStart ??= now()->subWeek()->startOfWeek();
        $weekEnd ??= $weekStart->copy()->endOfWeek();
        $queuedBefore = EmailNotification::query()->count();

        $enrollments = InternshipEnrollment::query()
            ->with(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'lecturer.user'])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereHas('checkIns', fn (Builder $query) => $query->whereBetween('checked_at', [$weekStart, $weekEnd]))
            ->get();

        $summaries = $enrollments
            ->map(fn (InternshipEnrollment $enrollment) => $this->summary($enrollment, $weekStart, $weekEnd))
            ->filter();

        $summaries->each(fn (array $summary) => $this->queueStudentDigest($summary, $weekStart, $weekEnd));
        $this->queueLecturerDigests($summaries, $weekStart, $weekEnd);
        $this->queueCoordinatorDigests($summaries, $weekStart, $weekEnd);

        return EmailNotification::query()->count() - $queuedBefore;
    }

    private function summary(InternshipEnrollment $enrollment, Carbon $weekStart, Carbon $weekEnd): ?array
    {
        $checkIns = $enrollment->checkIns()
            ->whereBetween('checked_at', [$weekStart, $weekEnd])
            ->orderBy('checked_at')
            ->get();

        if ($checkIns->isEmpty()) {
            return null;
        }

        $daily = $checkIns->groupBy(fn ($checkIn) => $checkIn->checked_at?->toDateString());
        $completeDays = $daily->filter(fn (Collection $items) => $items->contains('action', 'check_in') && $items->contains('action', 'check_out'))->count();
        $incompleteDays = max(0, $daily->count() - $completeDays);
        $durationMinutes = (int) $checkIns->whereNotNull('duration_minutes')->sum('duration_minutes');
        $sanctions = (float) $checkIns->sum(fn ($checkIn) => (float) ($checkIn->sanction_points ?? 0));
        $lateCount = $checkIns->filter(fn ($checkIn) => Str::contains(Str::lower((string) $checkIn->type), 'terlambat'))->count();
        $shortDurationDays = $daily->filter(fn (Collection $items) => (int) $items->max('duration_minutes') > 0 && (int) $items->max('duration_minutes') < 420)->count();
        $distanceOutliers = $checkIns->filter(fn ($checkIn) => (float) ($checkIn->distance_meters ?? 0) > 500)->count();
        $averageDistance = $checkIns->whereNotNull('distance_meters')->avg('distance_meters');

        return [
            'enrollment' => $enrollment,
            'total_records' => $checkIns->count(),
            'total_days' => $daily->count(),
            'complete_days' => $completeDays,
            'incomplete_days' => $incompleteDays,
            'duration_minutes' => $durationMinutes,
            'sanctions' => $sanctions,
            'late_count' => $lateCount,
            'short_duration_days' => $shortDurationDays,
            'distance_outliers' => $distanceOutliers,
            'average_distance' => $averageDistance,
            'problematic' => $incompleteDays > 0 || $sanctions > 0 || $lateCount > 0 || $shortDurationDays > 0 || $distanceOutliers > 0,
        ];
    }

    private function queueStudentDigest(array $summary, Carbon $weekStart, Carbon $weekEnd): void
    {
        /** @var InternshipEnrollment $enrollment */
        $enrollment = $summary['enrollment'];
        $email = $enrollment->student?->student_email ?: $enrollment->student?->user?->email;

        if (! $email) {
            return;
        }

        $this->emails->queue(
            type: 'attendance_digest.weekly.student',
            recipientEmail: $email,
            subject: '[SiLAT] Digest Presensi Mingguan',
            bodyLines: [
                'Ringkasan presensi Anda untuk periode '.$weekStart->format('d/m/Y').' - '.$weekEnd->format('d/m/Y').'.',
                $this->enrollmentContext($enrollment),
                'Hari dengan presensi lengkap: '.$summary['complete_days'].' dari '.$summary['total_days'].' hari tercatat.',
                'Durasi tercatat: '.$this->minutesLabel($summary['duration_minutes']).'. Rata-rata jarak: '.$this->metersLabel($summary['average_distance']).'.',
                'Sanksi minggu ini: '.number_format($summary['sanctions'], 2, ',', '.').'.',
                $summary['problematic'] ? 'Catatan sistem: ada pola yang perlu diperiksa, seperti presensi tidak berpasangan, terlambat, durasi kurang, atau jarak tidak wajar.' : 'Tidak ada pola bermasalah yang terdeteksi pada minggu ini.',
            ],
            recipientName: $enrollment->student?->full_name,
            actionText: 'Buka Presensi',
            actionUrl: route('check-ins.create', ['enrollment_id' => $enrollment->id]),
            notifiable: $enrollment,
            eventKey: 'attendance-digest-student-'.$enrollment->id.'-'.$weekStart->toDateString(),
        );
    }

    private function queueLecturerDigests(Collection $summaries, Carbon $weekStart, Carbon $weekEnd): void
    {
        $summaries
            ->where('problematic', true)
            ->groupBy(fn (array $summary) => $summary['enrollment']->lecturer_supervisor_id ?: 'none')
            ->each(function (Collection $items, mixed $lecturerId) use ($weekStart, $weekEnd): void {
                if ($lecturerId === 'none') {
                    return;
                }

                /** @var InternshipEnrollment $firstEnrollment */
                $firstEnrollment = $items->first()['enrollment'];
                $lecturer = $firstEnrollment->lecturer;
                $email = $lecturer?->email ?: $lecturer?->user?->email;

                if (! $email) {
                    return;
                }

                $this->emails->queue(
                    type: 'attendance_digest.weekly.lecturer',
                    recipientEmail: $email,
                    subject: '[SiLAT] Digest Presensi Mahasiswa Bimbingan',
                    bodyLines: $this->reviewerLines($items, $weekStart, $weekEnd),
                    recipientName: $lecturer?->name ?: $lecturer?->user?->name,
                    actionText: 'Buka Dashboard',
                    actionUrl: route('reports.monitoring'),
                    eventKey: 'attendance-digest-lecturer-'.$lecturerId.'-'.$weekStart->toDateString(),
                );
            });
    }

    private function queueCoordinatorDigests(Collection $summaries, Carbon $weekStart, Carbon $weekEnd): void
    {
        InternshipCoordinator::query()
            ->with('lecturer.user')
            ->where('status', 'active')
            ->get()
            ->each(function (InternshipCoordinator $coordinator) use ($summaries, $weekStart, $weekEnd): void {
                $items = $summaries
                    ->where('problematic', true)
                    ->filter(fn (array $summary) => $summary['enrollment']->internship_period_id === $coordinator->internship_period_id
                        && $summary['enrollment']->study_program_id === $coordinator->study_program_id)
                    ->values();

                if ($items->isEmpty()) {
                    return;
                }

                $email = $coordinator->lecturer?->email ?: $coordinator->lecturer?->user?->email;

                if (! $email) {
                    return;
                }

                $this->emails->queue(
                    type: 'attendance_digest.weekly.coordinator',
                    recipientEmail: $email,
                    subject: '[SiLAT] Digest Presensi Peserta Program',
                    bodyLines: $this->reviewerLines($items, $weekStart, $weekEnd),
                    recipientName: $coordinator->lecturer?->name ?: $coordinator->lecturer?->user?->name,
                    actionText: 'Buka Rekap Presensi',
                    actionUrl: route('reports.monitoring', [
                        'period_id' => $coordinator->internship_period_id,
                        'study_program_id' => $coordinator->study_program_id,
                    ]),
                    eventKey: 'attendance-digest-coordinator-'.$coordinator->id.'-'.$weekStart->toDateString(),
                );
            });
    }

    private function reviewerLines(Collection $items, Carbon $weekStart, Carbon $weekEnd): array
    {
        $lines = [
            'Ringkasan mahasiswa dengan pola presensi bermasalah pada '.$weekStart->format('d/m/Y').' - '.$weekEnd->format('d/m/Y').'.',
        ];

        $items->take(10)->each(function (array $summary) use (&$lines): void {
            /** @var InternshipEnrollment $enrollment */
            $enrollment = $summary['enrollment'];
            $lines[] = sprintf(
                '%s (%s): tidak lengkap %d hari, terlambat %d kali, durasi kurang %d hari, jarak tidak wajar %d kali, sanksi %s.',
                $enrollment->student?->full_name ?: '-',
                $enrollment->student?->npm ?: '-',
                $summary['incomplete_days'],
                $summary['late_count'],
                $summary['short_duration_days'],
                $summary['distance_outliers'],
                number_format($summary['sanctions'], 2, ',', '.'),
            );
        });

        if ($items->count() > 10) {
            $lines[] = 'Dan '.($items->count() - 10).' mahasiswa lain. Silakan buka SiLAT untuk rincian lengkap.';
        }

        return $lines;
    }

    private function enrollmentContext(InternshipEnrollment $enrollment): string
    {
        return sprintf(
            'Program/periode: %s. Prodi: %s. Mitra: %s.',
            $enrollment->internshipPeriod?->display_name ?: '-',
            $enrollment->studyProgram?->name ?: '-',
            $enrollment->internshipPlace?->name ?: '-',
        );
    }

    private function minutesLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return "{$hours} jam {$remaining} menit";
    }

    private function metersLabel(mixed $meters): string
    {
        if ($meters === null) {
            return '-';
        }

        return number_format((float) $meters, 0, ',', '.').' m';
    }
}
