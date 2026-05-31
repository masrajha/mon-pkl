<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPlaceProposal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user()->student()->first();

        return view('student.dashboard', [
            'student' => $student,
            'enrollments' => $student
                ? InternshipEnrollment::query()
                    ->with(['internshipPeriod', 'studyProgram', 'internshipPlace', 'lecturer'])
                    ->where('student_id', $student->id)
                    ->latest('id')
                    ->get()
                : collect(),
            'proposals' => $student
                ? InternshipPlaceProposal::query()
                    ->with(['internshipPeriod', 'studyProgram', 'approvedPlace'])
                    ->where('student_id', $student->id)
                    ->latest('id')
                    ->get()
                : collect(),
        ]);
    }
}
