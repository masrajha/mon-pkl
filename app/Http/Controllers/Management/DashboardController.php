<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\InternshipEnrollment;
use App\Models\InternshipCoordinator;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeEnrollments = InternshipEnrollment::query()->where('status', 'active')->count();
        $todayCheckIns = CheckIn::query()
            ->whereDate('checked_at', today())
            ->distinct('internship_enrollment_id')
            ->count('internship_enrollment_id');

        return view('management.dashboard', [
            'counts' => [
                'users' => User::query()->count(),
                'students' => Student::query()->count(),
                'lecturers' => Lecturer::query()->count(),
                'coordinators' => InternshipCoordinator::query()->where('status', 'active')->count(),
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
        ]);
    }
}
