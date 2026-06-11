<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\InternshipEnrollment;
use App\Models\InternshipCoordinator;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Organization;
use App\Models\OrientationEvent;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\ActionRequiredSummaryService;
use App\Services\ParticipantProgressDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ActionRequiredSummaryService $actions,
        private readonly ParticipantProgressDashboardService $progressDashboard,
    )
    {
    }

    public function __invoke(Request $request): View
    {
        $activeEnrollments = InternshipEnrollment::query()->where('status', 'active')->count();
        $todayCheckIns = CheckIn::query()
            ->whereDate('checked_at', today())
            ->distinct('internship_enrollment_id')
            ->count('internship_enrollment_id');
        $orientationEvents = OrientationEvent::query()
            ->with(['internshipPeriod.program', 'studyProgram'])
            ->withCount('attendances')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (OrientationEvent $event): OrientationEvent {
                $participantIds = InternshipEnrollment::query()
                    ->where('internship_period_id', $event->internship_period_id)
                    ->whereNotIn('status', ['cancelled', 'rejected'])
                    ->when($event->study_program_id, fn ($query) => $query->where('study_program_id', $event->study_program_id))
                    ->pluck('id');
                $present = $event->attendances()
                    ->whereIn('internship_enrollment_id', $participantIds)
                    ->count();

                $event->participants_count = $participantIds->count();
                $event->attendances_count = $present;
                $event->absent_count = max(0, $event->participants_count - $present);

                return $event;
            });

        return view('management.dashboard', [
            'counts' => [
                'users' => User::query()->count(),
                'students' => Student::query()->count(),
                'lecturers' => Lecturer::query()->count(),
                'coordinators' => InternshipCoordinator::query()->where('status', 'active')->count(),
                'organizations' => Organization::query()->count(),
                'studyPrograms' => StudyProgram::query()->count(),
                'periods' => InternshipPeriod::query()->count(),
                'places' => InternshipPlace::query()->count(),
                'enrollments' => InternshipEnrollment::query()->count(),
                'pendingEnrollments' => InternshipEnrollment::query()->where('status', 'pending_verification')->count(),
                'attendanceRate' => $activeEnrollments > 0 ? round(($todayCheckIns / $activeEnrollments) * 100) : 0,
            ],
            'pendingEnrollments' => InternshipEnrollment::query()
                ->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace'])
                ->where('status', 'pending_verification')
                ->latest('id')
                ->limit(5)
                ->get(),
            'activePeriods' => InternshipPeriod::query()
                ->with('program')
                ->withCount(['enrollments' => fn ($query) => $query->whereNotIn('status', ['cancelled', 'rejected'])])
                ->where('is_active', true)
                ->orderByDesc('id')
                ->limit(4)
                ->get(),
            'orientationEvents' => $orientationEvents,
            'participantProgress' => $this->progressDashboard->build($request, $request->user()),
            'actionRequiredSummary' => $this->actions->forUser($request->user()),
        ]);
    }
}
