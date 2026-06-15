<?php

namespace App\Services;

use App\Models\CheckIn;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\LocalClock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ParticipantProgressDashboardService
{
    public function build(Request $request, User $user): array
    {
        $assignments = $this->coordinatorAssignments($user);
        $periods = $this->periodOptions($user, $assignments);
        $selectedPeriod = $request->integer('progress_period_id')
            ? $periods->firstWhere('id', $request->integer('progress_period_id'))
            : ($periods->firstWhere('is_active', true) ?? $periods->first());

        [$startDate, $endDate] = $this->dateRange($request, $selectedPeriod);

        $baseQuery = InternshipEnrollment::query()
            ->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'lecturer'])
            ->whereNotIn('status', ['cancelled', 'rejected']);

        $this->scopeForUser($baseQuery, $user, $assignments);
        $this->applyFilters($baseQuery, $request, $selectedPeriod);

        $enrollments = $baseQuery->get();
        $enrollmentIds = $enrollments->pluck('id');
        $total = $enrollments->count();

        $attendanceRows = CheckIn::query()
            ->whereIn('internship_enrollment_id', $enrollmentIds)
            ->whereBetween('checked_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->get(['id', 'internship_enrollment_id', 'action', 'checked_at', 'daily_log_validated_at', 'sanction_points']);

        $completeAttendanceEnrollmentIds = $attendanceRows
            ->groupBy('internship_enrollment_id')
            ->filter(fn (Collection $items): bool => $items
                ->groupBy(fn (CheckIn $checkIn): string => $checkIn->checked_at?->toDateString() ?: '-')
                ->contains(fn (Collection $daily): bool => $daily->contains('action', 'check_in') && $daily->contains('action', 'check_out')))
            ->keys();

        $pendingDailyValidationIds = $attendanceRows
            ->filter(fn (CheckIn $checkIn): bool => $checkIn->action === 'check_in' && ! $checkIn->daily_log_validated_at)
            ->pluck('internship_enrollment_id')
            ->unique();

        $lateReportIds = $enrollments
            ->filter(fn (InternshipEnrollment $enrollment): bool => $enrollment->submissionProgress()
                ->where('sanction_points', '>', 0)
                ->exists())
            ->pluck('id');

        $seminarMissingIds = $enrollments
            ->filter(fn (InternshipEnrollment $enrollment): bool => ! $enrollment->seminarRequests()
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->exists())
            ->pluck('id');

        $lecturerScoredIds = $enrollments
            ->filter(fn (InternshipEnrollment $enrollment): bool => $enrollment->seminarRequests()
                ->whereNotNull('seminar_score')
                ->exists())
            ->pluck('id');

        $fieldScoredIds = $enrollments
            ->filter(fn (InternshipEnrollment $enrollment): bool => $enrollment->fieldSupervisorAssessment()
                ->whereNotNull('final_score')
                ->exists())
            ->pluck('id');

        $finalizedIds = $enrollments
            ->filter(fn (InternshipEnrollment $enrollment): bool => $enrollment->finalAssessment()
                ->whereNotNull('final_score')
                ->whereNotNull('finalized_at')
                ->exists())
            ->pluck('id');

        $incompleteScoreIds = $enrollments
            ->pluck('id')
            ->diff($lecturerScoredIds->intersect($fieldScoredIds));

        $highestSanctions = $enrollments
            ->sortByDesc(fn (InternshipEnrollment $enrollment): float => (float) $enrollment->total_sanctions_points)
            ->filter(fn (InternshipEnrollment $enrollment): bool => (float) $enrollment->total_sanctions_points > 0)
            ->take(5)
            ->values();

        $statusBreakdown = $enrollments
            ->groupBy('status')
            ->map(fn (Collection $items, string $status): array => [
                'label' => $status,
                'count' => $items->count(),
                'percent' => $total > 0 ? round(($items->count() / $total) * 100, 1) : 0,
            ])
            ->sortByDesc('count')
            ->values();

        return [
            'filters' => [
                'period_id' => $selectedPeriod?->id,
                'program_id' => $request->integer('progress_program_id') ?: null,
                'study_program_id' => $request->integer('progress_study_program_id') ?: null,
                'place_id' => $request->integer('progress_place_id') ?: null,
                'lecturer_id' => $request->integer('progress_lecturer_id') ?: null,
                'status' => $request->string('progress_status')->toString(),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'options' => [
                'periods' => $periods,
                'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
                'studyPrograms' => $this->studyProgramOptions($user, $assignments),
                'places' => InternshipPlace::query()->orderBy('name')->get(['id', 'name']),
                'lecturers' => Lecturer::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
                'statuses' => ['active' => 'Aktif', 'completed' => 'Selesai', 'pending_verification' => 'Pending Verifikasi', 'revision_required' => 'Perlu Revisi'],
            ],
            'cards' => [
                'total' => $total,
                'active' => $enrollments->where('status', 'active')->count(),
                'completed' => $enrollments->where('status', 'completed')->count(),
                'incomplete_attendance' => max(0, $total - $completeAttendanceEnrollmentIds->count()),
                'pending_daily_validation' => $pendingDailyValidationIds->count(),
                'late_reports' => $lateReportIds->count(),
                'seminar_missing' => $seminarMissingIds->count(),
                'incomplete_scores' => $incompleteScoreIds->count(),
                'final_scores' => $finalizedIds->count(),
                'highest_sanction' => (float) $enrollments->max('total_sanctions_points'),
            ],
            'statusBreakdown' => $statusBreakdown,
            'highestSanctions' => $highestSanctions,
        ];
    }

    private function applyFilters(Builder $query, Request $request, ?InternshipPeriod $selectedPeriod): void
    {
        if ($selectedPeriod) {
            $query->where('internship_period_id', $selectedPeriod->id);
        }

        if ($request->filled('progress_program_id')) {
            $query->whereHas('internshipPeriod', fn (Builder $period) => $period->where('program_id', $request->integer('progress_program_id')));
        }

        if ($request->filled('progress_study_program_id')) {
            $query->where('study_program_id', $request->integer('progress_study_program_id'));
        }

        if ($request->filled('progress_place_id')) {
            $query->where('internship_place_id', $request->integer('progress_place_id'));
        }

        if ($request->filled('progress_lecturer_id')) {
            $query->where('lecturer_supervisor_id', $request->integer('progress_lecturer_id'));
        }

        if ($request->filled('progress_status')) {
            $query->where('status', $request->string('progress_status')->toString());
        }
    }

    private function coordinatorAssignments(User $user): Collection
    {
        if (! $user->hasRole('koordinator')) {
            return collect();
        }

        return $user->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->get(['internship_period_id', 'study_program_id']) ?? collect();
    }

    private function scopeForUser(Builder $query, User $user, Collection $assignments): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $query) use ($assignments): void {
            $assignments->each(function ($assignment) use ($query): void {
                $query->orWhere(function (Builder $query) use ($assignment): void {
                    $query->where('internship_period_id', $assignment->internship_period_id)
                        ->where('study_program_id', $assignment->study_program_id);
                });
            });
        });
    }

    private function periodOptions(User $user, Collection $assignments): Collection
    {
        if ($user->hasRole('admin')) {
            return InternshipPeriod::query()->with('program')->orderByDesc('is_active')->orderByDesc('id')->get();
        }

        return InternshipPeriod::query()
            ->with('program')
            ->whereIn('id', $assignments->pluck('internship_period_id')->unique())
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();
    }

    private function studyProgramOptions(User $user, Collection $assignments): Collection
    {
        if ($user->hasRole('admin')) {
            return StudyProgram::query()->where('is_active', true)->orderBy('name')->get();
        }

        return StudyProgram::query()
            ->whereIn('id', $assignments->pluck('study_program_id')->unique())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function dateRange(Request $request, ?InternshipPeriod $period): array
    {
        $start = $request->date('progress_start_date');
        $end = $request->date('progress_end_date');

        $start ??= $period?->starts_at?->copy() ?? LocalClock::today()->startOfMonth();
        $end ??= $period?->ends_at?->copy() ?? LocalClock::today();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->startOfDay(), $end->startOfDay()];
    }
}
