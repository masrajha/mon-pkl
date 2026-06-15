<?php

namespace App\Services;

use App\Models\CheckIn;
use App\Models\InternshipEnrollment;
use App\Support\LocalClock;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ParticipantRiskScoringService
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function score(InternshipEnrollment $enrollment): array
    {
        $settings = $this->configurations->forPeriod($enrollment->internshipPeriod);
        $attendanceWindow = $this->attendanceWindow($enrollment);
        $expectedDates = $this->expectedWorkingDates($attendanceWindow['start'], $attendanceWindow['end'], $settings);
        $attendanceSummary = $this->attendanceSummary($enrollment->checkIns, $expectedDates);

        $pendingForgotten = $enrollment->forgottenAttendanceRequests
            ->where('status', 'pending')
            ->count();
        $unvalidatedDailyLogs = $enrollment->checkIns
            ->filter(fn (CheckIn $checkIn): bool => filled($checkIn->note) && blank($checkIn->daily_log_validated_at))
            ->groupBy(fn (CheckIn $checkIn): string => $checkIn->checked_at?->toDateString() ?? '')
            ->filter(fn (Collection $rows, string $date): bool => filled($date))
            ->count();
        $revisionCount = $enrollment->submissionProgress
            ->where('status', 'revision_required')
            ->count();
        $reportSanctions = (int) $enrollment->submissionProgress->sum('sanction_points');
        $totalSanctions = max((int) $enrollment->total_sanctions_points, $reportSanctions + (int) $enrollment->checkIns->sum('sanction_points'));
        $fullReportApproved = $enrollment->submissionProgress
            ->where('deadline_type', 'full_report')
            ->where('status', 'approved')
            ->isNotEmpty();
        $seminarReady = $enrollment->seminarRequests
            ->filter(fn ($seminar): bool => in_array($seminar->status, ['scheduled', 'completed'], true)
                || filled($seminar->scheduled_at)
                || filled($seminar->completed_at))
            ->isNotEmpty();
        $lecturerScoreMissing = $seminarReady && $enrollment->seminarRequests
            ->filter(fn ($seminar): bool => filled($seminar->seminar_score))
            ->isEmpty();
        $fieldSupervisorScoreMissing = $attendanceWindow['ended'] && blank($enrollment->fieldSupervisorAssessment?->final_score);
        $finalScoreMissing = filled($enrollment->fieldSupervisorAssessment?->final_score)
            && $enrollment->seminarRequests->filter(fn ($seminar): bool => filled($seminar->seminar_score))->isNotEmpty()
            && blank($enrollment->finalAssessment?->finalized_at);

        $indicators = [
            'incomplete_attendance' => [
                'label' => 'Presensi tidak lengkap',
                'value' => $attendanceSummary['incomplete_days'],
                'points' => min(30, $attendanceSummary['incomplete_days'] * 6),
            ],
            'consecutive_absence' => [
                'label' => 'Tidak hadir beruntun',
                'value' => $attendanceSummary['max_absence_streak'],
                'points' => match (true) {
                    $attendanceSummary['max_absence_streak'] >= 5 => 35,
                    $attendanceSummary['max_absence_streak'] >= 3 => 20,
                    $attendanceSummary['max_absence_streak'] >= 1 => 8,
                    default => 0,
                },
            ],
            'forgotten_attendance_pending' => [
                'label' => 'Lupa Presensi pending',
                'value' => $pendingForgotten,
                'points' => min(20, $pendingForgotten * 10),
            ],
            'daily_logs_unvalidated' => [
                'label' => 'Catatan harian belum divalidasi',
                'value' => $unvalidatedDailyLogs,
                'points' => min(20, $unvalidatedDailyLogs * 4),
            ],
            'late_reports' => [
                'label' => 'Laporan terlambat / revisi berulang',
                'value' => $reportSanctions + $revisionCount,
                'points' => min(25, $reportSanctions * 2) + ($revisionCount >= 2 ? 15 : ($revisionCount === 1 ? 7 : 0)),
            ],
            'seminar_missing' => [
                'label' => 'Seminar belum diajukan/dijadwalkan',
                'value' => $fullReportApproved && ! $seminarReady ? 1 : 0,
                'points' => $fullReportApproved && ! $seminarReady ? ($attendanceWindow['ended'] ? 20 : 15) : 0,
            ],
            'scores_incomplete' => [
                'label' => 'Nilai belum lengkap',
                'value' => (int) $lecturerScoreMissing + (int) $fieldSupervisorScoreMissing + (int) $finalScoreMissing,
                'points' => ($lecturerScoreMissing ? 12 : 0) + ($fieldSupervisorScoreMissing ? 15 : 0) + ($finalScoreMissing ? 10 : 0),
            ],
            'total_sanctions' => [
                'label' => 'Total sanksi',
                'value' => $totalSanctions,
                'points' => min(25, $totalSanctions),
            ],
        ];

        $score = min(100, collect($indicators)->sum('points'));
        $category = $this->category($score);
        $mainIssues = collect($indicators)
            ->filter(fn (array $indicator): bool => $indicator['points'] > 0)
            ->sortByDesc('points')
            ->take(3)
            ->pluck('label')
            ->values();

        return [
            'enrollment' => $enrollment,
            'score' => $score,
            'category' => $category['label'],
            'category_key' => $category['key'],
            'category_tone' => $category['tone'],
            'main_issues' => $mainIssues,
            'indicators' => $indicators,
            'expected_working_days' => $expectedDates->count(),
            'valid_attendance_days' => $attendanceSummary['valid_days'],
            'incomplete_attendance_days' => $attendanceSummary['incomplete_days'],
            'absence_streak' => $attendanceSummary['max_absence_streak'],
            'total_sanctions' => $totalSanctions,
        ];
    }

    private function attendanceWindow(InternshipEnrollment $enrollment): array
    {
        $start = $enrollment->effectiveAttendanceStartsAt()?->copy()
            ?? $enrollment->internshipPeriod?->starts_at?->copy()
            ?? LocalClock::today()->startOfDay();
        $configuredEnd = $enrollment->effectiveAttendanceEndsAt()?->copy()
            ?? $enrollment->internshipPeriod?->ends_at?->copy()
            ?? LocalClock::now();
        $today = LocalClock::today()->startOfDay();
        $end = $configuredEnd->lessThan($today) ? $configuredEnd : $today;

        return [
            'start' => $start->startOfDay(),
            'end' => $end->startOfDay(),
            'ended' => $configuredEnd->startOfDay()->lessThanOrEqualTo($today),
        ];
    }

    private function expectedWorkingDates(Carbon $start, Carbon $end, array $settings): Collection
    {
        if ($end->lessThan($start)) {
            return collect();
        }

        $holidays = collect($settings['calendar']['holidays'] ?? [])->filter()->flip();

        return collect(CarbonPeriod::create($start, $end))
            ->filter(fn (Carbon $date): bool => ! $date->isWeekend() && ! $holidays->has($date->toDateString()))
            ->map(fn (Carbon $date): string => $date->toDateString())
            ->values();
    }

    private function attendanceSummary(Collection $checkIns, Collection $expectedDates): array
    {
        $byDate = $checkIns
            ->filter(fn (CheckIn $checkIn): bool => filled($checkIn->checked_at))
            ->groupBy(fn (CheckIn $checkIn): string => $checkIn->checked_at->toDateString());

        $validDays = 0;
        $incompleteDays = 0;
        $currentAbsenceStreak = 0;
        $maxAbsenceStreak = 0;

        foreach ($expectedDates as $date) {
            $rows = $byDate->get($date, collect());
            $hasCheckIn = $rows->contains(fn (CheckIn $checkIn): bool => $checkIn->action === 'check_in');
            $hasCheckOut = $rows->contains(fn (CheckIn $checkIn): bool => $checkIn->action === 'check_out');
            $legacyPair = ! $hasCheckIn && ! $hasCheckOut && $rows->whereNull('action')->count() >= 2;
            $isValid = ($hasCheckIn && $hasCheckOut) || $legacyPair;

            if ($isValid) {
                $validDays++;
                $currentAbsenceStreak = 0;

                continue;
            }

            if ($rows->isNotEmpty()) {
                $incompleteDays++;
                $currentAbsenceStreak = 0;

                continue;
            }

            $currentAbsenceStreak++;
            $maxAbsenceStreak = max($maxAbsenceStreak, $currentAbsenceStreak);
        }

        return [
            'valid_days' => $validDays,
            'incomplete_days' => $incompleteDays,
            'max_absence_streak' => $maxAbsenceStreak,
        ];
    }

    private function category(int $score): array
    {
        return match (true) {
            $score >= 70 => ['key' => 'critical', 'label' => 'Kritis', 'tone' => 'bg-red-50 text-red-700 ring-red-200'],
            $score >= 40 => ['key' => 'risky', 'label' => 'Berisiko', 'tone' => 'bg-orange-50 text-orange-700 ring-orange-200'],
            $score >= 20 => ['key' => 'watch', 'label' => 'Perlu Dipantau', 'tone' => 'bg-amber-50 text-amber-700 ring-amber-200'],
            default => ['key' => 'safe', 'label' => 'Aman', 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        };
    }
}
