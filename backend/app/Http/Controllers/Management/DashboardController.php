<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
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
        return view('management.dashboard', [
            'counts' => [
                'users' => User::query()->count(),
                'students' => Student::query()->count(),
                'lecturers' => Lecturer::query()->count(),
                'studyPrograms' => StudyProgram::query()->count(),
                'periods' => InternshipPeriod::query()->count(),
                'places' => InternshipPlace::query()->count(),
                'enrollments' => InternshipEnrollment::query()->count(),
            ],
        ]);
    }
}
