<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPlaceProposal;
use App\Models\OrientationEvent;
use App\Models\PeriodDeadline;
use App\Models\RelocationRequest;
use App\Models\SupervisorChangeRequest;
use App\Support\LocalClock;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user()->student()->first();
        $enrollments = $student
            ? InternshipEnrollment::query()
                ->with(['internshipPeriod.program', 'internshipPeriod.deadlines', 'studyProgram', 'internshipPlace', 'lecturer', 'checkIns', 'seminarRequests'])
                ->where('student_id', $student->id)
                ->latest('id')
                ->get()
            : collect();
        $activeEnrollment = $enrollments
            ->first(fn (InternshipEnrollment $enrollment) => $enrollment->status === 'active' && (bool) $enrollment->internshipPeriod?->is_active)
            ?? $enrollments->first();
        $orientationEvents = $activeEnrollment
            ? OrientationEvent::query()
                ->with(['internshipPeriod.program', 'studyProgram'])
                ->with(['attendances' => fn ($query) => $query->where('student_id', $student->id)])
                ->forEnrollment($activeEnrollment)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->get()
            : collect();
        $today = LocalClock::today()->toDateString();
        $nearestDeadline = $activeEnrollment?->internshipPeriod?->deadlines
            ?->filter(fn ($deadline) => $deadline->deadline_date && $deadline->deadline_date->toDateString() >= $today)
            ->sortBy('deadline_date')
            ->first();

        $relocationRequests = $student
            ? RelocationRequest::query()
                ->with(['enrollment.internshipPeriod.program', 'currentPlace', 'newPlace'])
                ->whereHas('enrollment', fn ($query) => $query->where('student_id', $student->id))
                ->latest('id')
                ->get()
            : collect();
        $supervisorChangeRequests = $student
            ? SupervisorChangeRequest::query()
                ->with(['enrollment.internshipPeriod.program', 'currentLecturer', 'requestedLecturer'])
                ->whereHas('enrollment', fn ($query) => $query->where('student_id', $student->id))
                ->latest('id')
                ->get()
            : collect();

        return view('student.dashboard', [
            'student' => $student,
            'enrollments' => $enrollments,
            'activeEnrollment' => $activeEnrollment,
            'attendanceDays' => $activeEnrollment?->checkIns
                ?->groupBy(fn ($checkIn) => $checkIn->checked_at?->toDateString())
                ->filter(fn ($items) => $items->contains('action', 'check_in') && $items->contains('action', 'check_out'))
                ->count() ?? 0,
            'sanctionsPoints' => (int) ($activeEnrollment?->total_sanctions_points ?? 0),
            'nearestDeadline' => $nearestDeadline,
            'importantDeadlines' => $this->importantDeadlinesForEnrollments($enrollments),
            'orientationEvents' => $orientationEvents,
            'reportProgress' => $activeEnrollment?->final_report_path ? 100 : 0,
            'proposals' => $student
                ? InternshipPlaceProposal::query()
                    ->with(['internshipPeriod.program', 'studyProgram', 'approvedPlace'])
                    ->where('student_id', $student->id)
                    ->latest('id')
                    ->get()
                : collect(),
            'relocationRequests' => $relocationRequests,
            'supervisorChangeRequests' => $supervisorChangeRequests,
            'hasPendingRelocationRequest' => $relocationRequests->contains('status', 'pending'),
            'hasPendingSupervisorChangeRequest' => $supervisorChangeRequests->contains('status', 'pending'),
        ]);
    }

    private function importantDeadlinesForEnrollments($enrollments)
    {
        $periodIds = $enrollments
            ->filter(fn (InternshipEnrollment $enrollment) => $enrollment->status === 'active' && (bool) $enrollment->internshipPeriod?->is_active)
            ->pluck('internship_period_id')
            ->filter()
            ->unique()
            ->values();

        if ($periodIds->isEmpty()) {
            $periodIds = $enrollments->pluck('internship_period_id')->filter()->unique()->values();
        }

        if ($periodIds->isEmpty()) {
            return collect();
        }

        $baseQuery = PeriodDeadline::query()
            ->with('internshipPeriod.program')
            ->whereIn('internship_period_id', $periodIds)
            ->whereDate('deadline_date', '>=', LocalClock::today())
            ->orderBy('deadline_date')
            ->orderBy('deadline_type');

        $withinSevenDays = (clone $baseQuery)
            ->whereDate('deadline_date', '<=', LocalClock::today()->addDays(7))
            ->limit(6)
            ->get();

        return $withinSevenDays->isNotEmpty()
            ? $withinSevenDays
            : $baseQuery->limit(3)->get();
    }
}
