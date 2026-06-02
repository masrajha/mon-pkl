<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoordinatorDashboardController extends Controller
{
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

        return view('coordinator.dashboard', compact('assignments', 'stats', 'recentEnrollments', 'highestSanctions'));
    }
}
