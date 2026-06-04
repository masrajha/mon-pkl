<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPlaceProposal;
use App\Models\OrientationEvent;
use App\Models\RelocationRequest;
use App\Models\SupervisorChangeRequest;
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
        $activeEnrollment = $enrollments->firstWhere('status', 'active') ?? $enrollments->first();
        $orientationEvents = $activeEnrollment
            ? OrientationEvent::query()
                ->with(['internshipPeriod.program', 'studyProgram'])
                ->with(['attendances' => fn ($query) => $query->where('student_id', $student->id)])
                ->forEnrollment($activeEnrollment)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->get()
            : collect();
        $nearestDeadline = $activeEnrollment?->internshipPeriod?->deadlines
            ?->filter(fn ($deadline) => $deadline->deadline_date?->isFuture() || $deadline->deadline_date?->isToday())
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
}
