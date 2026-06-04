<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\InternshipPlaceProposal;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\ActionRequiredSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ActionRequiredSummaryService $actions)
    {
    }

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $student = $user->student()->with(['studyProgram'])->first();
        $lecturer = $user->lecturer()->with(['coordinatorAssignments.internshipPeriod.program', 'coordinatorAssignments.studyProgram'])->first();

        return view('dashboard', [
            'student' => $student,
            'studentProfileComplete' => $student && $student->npm && $student->student_email && $student->phone && $student->study_program_id,
            'studentEnrollment' => $student ? $this->studentEnrollment($student) : null,
            'studentProposalCount' => $student ? InternshipPlaceProposal::query()->where('student_id', $student->id)->count() : 0,
            'lecturer' => $lecturer,
            'lecturerStats' => $lecturer ? $this->lecturerStats($lecturer) : null,
            'supervisedEnrollments' => $lecturer ? $this->supervisedEnrollments($lecturer) : collect(),
            'coordinatorAssignments' => $lecturer
                ? $lecturer->coordinatorAssignments->where('status', 'active')->values()
                : collect(),
            'adminStats' => $user->hasRole('admin') ? $this->adminStats() : null,
            'actionRequiredSummary' => $this->actions->forUser($user),
        ]);
    }

    private function studentEnrollment(Student $student): ?InternshipEnrollment
    {
        return InternshipEnrollment::query()
            ->with(['internshipPeriod.program', 'studyProgram', 'internshipPlace', 'lecturer'])
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();
    }

    private function lecturerStats(Lecturer $lecturer): array
    {
        $base = InternshipEnrollment::query()->where('lecturer_supervisor_id', $lecturer->id);

        return [
            'active_enrollments' => (clone $base)->where('status', 'active')->count(),
            'pending_enrollments' => (clone $base)->where('status', 'pending_verification')->count(),
            'completed_enrollments' => (clone $base)->where('status', 'completed')->count(),
        ];
    }

    private function supervisedEnrollments(Lecturer $lecturer)
    {
        return InternshipEnrollment::query()
            ->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace'])
            ->where('lecturer_supervisor_id', $lecturer->id)
            ->latest('id')
            ->limit(6)
            ->get();
    }

    private function adminStats(): array
    {
        $activeEnrollments = InternshipEnrollment::query()->where('status', 'active')->count();
        $todayCheckIns = CheckIn::query()
            ->whereDate('checked_at', today())
            ->distinct('internship_enrollment_id')
            ->count('internship_enrollment_id');

        return [
            'users' => User::query()->count(),
            'students' => Student::query()->count(),
            'lecturers' => Lecturer::query()->count(),
            'study_programs' => StudyProgram::query()->count(),
            'periods' => InternshipPeriod::query()->count(),
            'places' => InternshipPlace::query()->count(),
            'enrollments' => InternshipEnrollment::query()->count(),
            'pending_enrollments' => InternshipEnrollment::query()->where('status', 'pending_verification')->count(),
            'pending_proposals' => InternshipPlaceProposal::query()->where('status', 'pending')->count(),
            'coordinators' => InternshipCoordinator::query()->where('status', 'active')->count(),
            'attendance_rate' => $activeEnrollments > 0 ? round(($todayCheckIns / $activeEnrollments) * 100) : 0,
        ];
    }
}
