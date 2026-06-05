<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\InternshipPlaceProposal;
use App\Models\Lecturer;
use App\Models\PeriodDeadline;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\ActionRequiredSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ActionRequiredSummaryService $actions)
    {
    }

    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('pembimbing_lapangan')) {
            return redirect()->route('field-supervisor.index');
        }

        $student = $user->student()->with(['studyProgram'])->first();
        $lecturer = $user->lecturer()->with(['coordinatorAssignments.internshipPeriod.program', 'coordinatorAssignments.studyProgram'])->first();

        $studentActiveEnrollments = $student ? $this->studentActiveEnrollments($student) : collect();
        $supervisedEnrollments = $lecturer ? $this->supervisedEnrollments($lecturer) : collect();

        return view('dashboard', [
            'student' => $student,
            'studentProfileComplete' => $student && $student->npm && $student->student_email && $student->phone && $student->study_program_id,
            'studentEnrollment' => $student ? $this->studentEnrollment($student) : null,
            'studentActiveEnrollments' => $studentActiveEnrollments,
            'studentEnrollmentSummary' => $student ? $this->studentEnrollmentSummary($student) : ['total' => 0, 'statuses' => collect()],
            'studentImportantDeadlines' => $student ? $this->importantDeadlinesForStudent($student, $studentActiveEnrollments) : collect(),
            'studentProposalCount' => $student ? InternshipPlaceProposal::query()->where('student_id', $student->id)->count() : 0,
            'lecturer' => $lecturer,
            'lecturerStats' => $lecturer ? $this->lecturerStats($lecturer) : null,
            'supervisedEnrollments' => $supervisedEnrollments,
            'lecturerImportantDeadlines' => $lecturer ? $this->importantDeadlinesForPeriodIds($supervisedEnrollments->pluck('internship_period_id')) : collect(),
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

    private function studentActiveEnrollments(Student $student)
    {
        return InternshipEnrollment::query()
            ->with(['internshipPeriod.program', 'studyProgram', 'internshipPlace', 'lecturer'])
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereHas('internshipPeriod', fn ($query) => $query->where('is_active', true))
            ->orderByDesc('internship_period_id')
            ->latest('id')
            ->get();
    }

    private function studentEnrollmentSummary(Student $student): array
    {
        $statusCounts = InternshipEnrollment::query()
            ->with('internshipPeriod')
            ->where('student_id', $student->id)
            ->get()
            ->map(fn (InternshipEnrollment $enrollment) => $this->effectiveEnrollmentStatus($enrollment))
            ->countBy();

        return [
            'total' => $statusCounts->sum(),
            'statuses' => $statusCounts,
        ];
    }

    private function effectiveEnrollmentStatus(InternshipEnrollment $enrollment): string
    {
        $periodIsActive = (bool) $enrollment->internshipPeriod?->is_active;

        if ($enrollment->status === 'active') {
            return $periodIsActive ? 'active' : 'period_inactive';
        }

        if (! $periodIsActive && in_array($enrollment->status, ['draft', 'pending_verification', 'revision_required'], true)) {
            return 'period_unavailable';
        }

        return $enrollment->status;
    }

    private function importantDeadlinesForStudent(Student $student, $activeEnrollments)
    {
        $periodIds = $activeEnrollments->pluck('internship_period_id');

        if ($periodIds->isEmpty()) {
            $periodIds = InternshipEnrollment::query()
                ->where('student_id', $student->id)
                ->pluck('internship_period_id');
        }

        return $this->importantDeadlinesForPeriodIds($periodIds);
    }

    private function importantDeadlinesForPeriodIds($periodIds)
    {
        $periodIds = collect($periodIds)->filter()->unique()->values();

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
            ->orderByDesc('internship_period_id')
            ->latest('id')
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
