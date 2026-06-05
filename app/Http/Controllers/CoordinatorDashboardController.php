<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\OrientationEvent;
use App\Models\PeriodDeadline;
use App\Services\ActionRequiredSummaryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoordinatorDashboardController extends Controller
{
    public function __construct(private readonly ActionRequiredSummaryService $actions)
    {
    }

    public function __invoke(Request $request): View
    {
        $assignments = InternshipCoordinator::query()
            ->with(['internshipPeriod.program', 'studyProgram'])
            ->where('status', 'active')
            ->whereHas('lecturer', fn ($query) => $query->where('user_id', $request->user()->id))
            ->orderByDesc('id')
            ->get();

        $statsQuery = InternshipEnrollment::query();

        if ($assignments->isEmpty()) {
            $statsQuery->whereRaw('1 = 0');
        } else {
            $statsQuery->where(function (Builder $query) use ($assignments): void {
                foreach ($assignments as $assignment) {
                    $query->orWhere(function (Builder $query) use ($assignment): void {
                        $query->where('internship_period_id', $assignment->internship_period_id)
                            ->where('study_program_id', $assignment->study_program_id);
                    });
                }
            });
        }

        $enrollmentIds = (clone $statsQuery)->pluck('id');

        $stats = [
            'students' => (clone $statsQuery)->count(),
            'checkInsToday' => CheckIn::query()
                ->whereIn('internship_enrollment_id', $enrollmentIds)
                ->whereDate('checked_at', today())
                ->distinct('internship_enrollment_id')
                ->count('internship_enrollment_id'),
            'completedReports' => (clone $statsQuery)->where('status', 'completed')->count(),
            'sanctions' => (clone $statsQuery)->sum('total_sanctions_points'),
        ];

        $recentEnrollments = (clone $statsQuery)
            ->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace'])
            ->latest('id')
            ->limit(6)
            ->get();

        $highestSanctions = (clone $statsQuery)
            ->with(['student', 'studyProgram', 'internshipPeriod.program'])
            ->where('total_sanctions_points', '>', 0)
            ->orderByDesc('total_sanctions_points')
            ->limit(5)
            ->get();
        $orientationEvents = OrientationEvent::query()
            ->with(['internshipPeriod.program', 'studyProgram'])
            ->withCount('attendances')
            ->when($assignments->isEmpty(), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($assignments->isNotEmpty(), function (Builder $query) use ($assignments): void {
                $query->where(function (Builder $query) use ($assignments): void {
                    foreach ($assignments as $assignment) {
                        $query->orWhere(function (Builder $query) use ($assignment): void {
                            $query->where('internship_period_id', $assignment->internship_period_id)
                                ->where(function (Builder $query) use ($assignment): void {
                                    $query->whereNull('study_program_id')
                                        ->orWhere('study_program_id', $assignment->study_program_id);
                                });
                        });
                    }
                });
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (OrientationEvent $event) use ($assignments): OrientationEvent {
                $participantsQuery = InternshipEnrollment::query()
                    ->where('internship_period_id', $event->internship_period_id)
                    ->whereNotIn('status', ['cancelled', 'rejected'])
                    ->when($event->study_program_id, fn ($query) => $query->where('study_program_id', $event->study_program_id));

                if (! $event->study_program_id) {
                    $programIds = $assignments
                        ->where('internship_period_id', $event->internship_period_id)
                        ->pluck('study_program_id');
                    $participantsQuery->whereIn('study_program_id', $programIds);
                }

                $participantIds = $participantsQuery->pluck('id');
                $present = $event->attendances()
                    ->whereIn('internship_enrollment_id', $participantIds)
                    ->count();

                $event->participants_count = $participantIds->count();
                $event->attendances_count = $present;
                $event->absent_count = max(0, $event->participants_count - $present);

                return $event;
            });

        return view('coordinator.dashboard', [
            'assignments' => $assignments,
            'stats' => $stats,
            'recentEnrollments' => $recentEnrollments,
            'highestSanctions' => $highestSanctions,
            'orientationEvents' => $orientationEvents,
            'importantDeadlines' => $this->importantDeadlinesForAssignments($assignments),
            'actionRequiredSummary' => $this->actions->forUser($request->user()),
        ]);
    }

    private function importantDeadlinesForAssignments($assignments)
    {
        $periodIds = $assignments->pluck('internship_period_id')->filter()->unique()->values();

        if ($periodIds->isEmpty()) {
            return collect();
        }

        $baseQuery = PeriodDeadline::query()
            ->with('internshipPeriod.program')
            ->whereIn('internship_period_id', $periodIds)
            ->whereDate('deadline_date', '>=', today())
            ->orderBy('deadline_date')
            ->orderBy('deadline_type');

        $withinSevenDays = (clone $baseQuery)
            ->whereDate('deadline_date', '<=', today()->addDays(7))
            ->limit(6)
            ->get();

        return $withinSevenDays->isNotEmpty()
            ? $withinSevenDays
            : $baseQuery->limit(3)->get();
    }
}
