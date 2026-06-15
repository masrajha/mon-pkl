<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BrowserNotificationController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\EmailNotificationConfigurationController;
use App\Http\Controllers\FieldSupervisorPortalController;
use App\Http\Controllers\FinalAssessmentVerificationController;
use App\Http\Controllers\ForgottenAttendanceApprovalController;
use App\Http\Controllers\InternshipPlaceController;
use App\Http\Controllers\LocationSuggestionController;
use App\Http\Controllers\Management\AuditLogController as ManagementAuditLogController;
use App\Http\Controllers\Management\CoordinatorController as ManagementCoordinatorController;
use App\Http\Controllers\Management\DashboardController as ManagementDashboardController;
use App\Http\Controllers\Management\EnrollmentController as ManagementEnrollmentController;
use App\Http\Controllers\Management\FinalAssessmentController as ManagementFinalAssessmentController;
use App\Http\Controllers\Management\ForgottenAttendanceRequestController as ManagementForgottenAttendanceRequestController;
use App\Http\Controllers\Management\FieldSupervisorAccessController as ManagementFieldSupervisorAccessController;
use App\Http\Controllers\Management\FieldSupervisorController as ManagementFieldSupervisorController;
use App\Http\Controllers\Management\LecturerController as ManagementLecturerController;
use App\Http\Controllers\Management\OrientationEventController as ManagementOrientationEventController;
use App\Http\Controllers\Management\OrganizationController as ManagementOrganizationController;
use App\Http\Controllers\Management\PeriodController as ManagementPeriodController;
use App\Http\Controllers\Management\PlaceController as ManagementPlaceController;
use App\Http\Controllers\Management\PlaceProposalController as ManagementPlaceProposalController;
use App\Http\Controllers\Management\ProgramController as ManagementProgramController;
use App\Http\Controllers\Management\RelocationRequestController as ManagementRelocationRequestController;
use App\Http\Controllers\Management\ReportViewerController as ManagementReportViewerController;
use App\Http\Controllers\Management\StudentController as ManagementStudentController;
use App\Http\Controllers\Management\StudyProgramController as ManagementStudyProgramController;
use App\Http\Controllers\Management\SubmissionProgressController as ManagementSubmissionProgressController;
use App\Http\Controllers\Management\SupervisorChangeRequestController as ManagementSupervisorChangeRequestController;
use App\Http\Controllers\Management\UserController as ManagementUserController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicStorageFileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;
use App\Http\Controllers\Student\ForgottenAttendanceRequestController as StudentForgottenAttendanceRequestController;
use App\Http\Controllers\Student\OrientationAttendanceController as StudentOrientationAttendanceController;
use App\Http\Controllers\Student\PlaceController as StudentPlaceController;
use App\Http\Controllers\Student\PlaceProposalController as StudentPlaceProposalController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ReportController as StudentReportController;
use App\Http\Controllers\Student\RelocationRequestController as StudentRelocationRequestController;
use App\Http\Controllers\Student\SeminarRequestController as StudentSeminarRequestController;
use App\Http\Controllers\Student\SupervisorChangeRequestController as StudentSupervisorChangeRequestController;
use App\Http\Controllers\Management\SeminarRequestController as ManagementSeminarRequestController;
use App\Http\Controllers\SubmissionProgressFileController;
use App\Http\Controllers\SeminarRequestFileController;
use App\Http\Controllers\SystemConfigurationController;
use App\Models\CheckIn;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Student;
use App\Support\LocalClock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    $safeCount = function (string $table, string $model, ?callable $query = null): int {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $builder = $model::query();

        if ($query) {
            $query($builder);
        }

        return $builder->count();
    };

    $stats = [
        [
            'label' => 'Mahasiswa Terdaftar',
            'value' => $safeCount('students', Student::class),
            'icon' => 'fa-user-graduate',
            'tone' => 'bg-blue-600 text-white',
        ],
        [
            'label' => 'Dosen Pembimbing',
            'value' => $safeCount('lecturers', Lecturer::class),
            'icon' => 'fa-chalkboard-user',
            'tone' => 'bg-teal-600 text-white',
        ],
        [
            'label' => 'Mitra/Instansi',
            'value' => $safeCount('internship_places', InternshipPlace::class, fn ($query) => $query->where('is_active', true)),
            'icon' => 'fa-building',
            'tone' => 'bg-amber-500 text-slate-950',
        ],
        [
            'label' => 'Total Check-in',
            'value' => $safeCount('check_ins', CheckIn::class),
            'icon' => 'fa-location-dot',
            'tone' => 'bg-slate-800 text-white',
        ],
    ];

    $programIcon = function (string $name): string {
        $lowerName = strtolower($name);

        return match (true) {
            str_contains($lowerName, 'kerja') => 'fa-briefcase',
            str_contains($lowerName, 'magang') => 'fa-id-badge',
            str_contains($lowerName, 'riset') => 'fa-flask',
            str_contains($lowerName, 'wirausaha') => 'fa-lightbulb',
            str_contains($lowerName, 'proyek') => 'fa-people-carry-box',
            default => 'fa-layer-group',
        };
    };

    $programs = collect();

    if (Schema::hasTable('programs')) {
        $programs = Program::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(4)
            ->get()
            ->map(fn (Program $program) => [
                'name' => $program->name,
                'description' => $program->description ?: 'Program kegiatan akademik yang dapat dikelola melalui alur SiLAT.',
                'icon' => $programIcon($program->name),
            ]);
    }

    if ($programs->isEmpty()) {
        $programs = collect([
            ['name' => 'Kerja Praktik', 'description' => 'Kelola pendaftaran, pembimbingan, check-in, dan laporan kegiatan kerja praktik.', 'icon' => 'fa-briefcase'],
            ['name' => 'Magang', 'description' => 'Dukungan monitoring aktivitas mahasiswa di mitra industri atau instansi.', 'icon' => 'fa-id-badge'],
            ['name' => 'Riset', 'description' => 'Pencatatan aktivitas penelitian, lokasi kegiatan, dan progres laporan.', 'icon' => 'fa-flask'],
            ['name' => 'Wirausaha', 'description' => 'Ruang pemantauan kegiatan MBKM yang dapat disesuaikan dengan rule program.', 'icon' => 'fa-lightbulb'],
        ]);
    }

    $periods = collect();

    if (Schema::hasTable('internship_periods')) {
        $periods = InternshipPeriod::query()
            ->with('program')
            ->where(function ($query) {
                $today = LocalClock::today();

                $query
                    ->where('is_active', true)
                    ->orWhereDate('starts_at', '>=', $today)
                    ->orWhere(function ($activeDateQuery) {
                        $today = LocalClock::today();

                        $activeDateQuery
                            ->whereDate('starts_at', '<=', $today)
                            ->whereDate('ends_at', '>=', $today);
                    });
            })
            ->orderByDesc('is_active')
            ->orderBy('starts_at')
            ->limit(6)
            ->get();
    }

    return view('welcome', compact('stats', 'programs', 'periods'));
})->name('landing');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::get('/docs', [DocumentationController::class, 'index'])->name('docs.index');
Route::get('/docs/{role}', [DocumentationController::class, 'show'])->name('docs.show');
Route::get('/field-supervisor/access/{token}', [FieldSupervisorPortalController::class, 'token'])->name('field-supervisor.token');
Route::post('/field-supervisor/access/{token}/daily-logs/{checkIn}/validate', [FieldSupervisorPortalController::class, 'validateWithToken'])->name('field-supervisor.token.daily-logs.validate');
Route::post('/field-supervisor/access/{token}/daily-logs/bulk-validate', [FieldSupervisorPortalController::class, 'bulkValidateWithToken'])->name('field-supervisor.token.daily-logs.bulk-validate');
Route::post('/field-supervisor/access/{token}/enrollments/{enrollment}/assessment', [FieldSupervisorPortalController::class, 'assessWithToken'])->name('field-supervisor.token.assessment.store');
Route::get('/verify/final-assessments/{token}', [FinalAssessmentVerificationController::class, 'show'])->name('final-assessments.verify');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/media/public/{path}', PublicStorageFileController::class)
        ->where('path', '.*')
        ->name('media.public');

    Route::get('/browser-notifications/unread', [BrowserNotificationController::class, 'unread'])
        ->middleware('throttle:30,1')
        ->name('browser-notifications.unread');
    Route::post('/browser-notifications/{browserNotification}/shown', [BrowserNotificationController::class, 'markShown'])
        ->middleware('throttle:60,1')
        ->name('browser-notifications.mark-shown');
    Route::post('/browser-notifications/{browserNotification}/read', [BrowserNotificationController::class, 'markRead'])
        ->middleware('throttle:60,1')
        ->name('browser-notifications.mark-read');
    Route::post('/browser-notifications/subscribe', [BrowserNotificationController::class, 'subscribe'])
        ->middleware('throttle:10,1')
        ->name('browser-notifications.subscribe');
    Route::post('/browser-notifications/unsubscribe', [BrowserNotificationController::class, 'unsubscribe'])
        ->middleware('throttle:10,1')
        ->name('browser-notifications.unsubscribe');

    Route::post('/active-role', function (Request $request) {
        $user = $request->user();
        $role = $request->validate([
            'role' => ['required', 'string', 'in:admin,dosen,koordinator,report_viewer,mahasiswa,pembimbing_lapangan'],
        ])['role'];

        abort_unless($user->hasRole($role), 403);

        $request->session()->put('active_role', $role);

        $homeRoutes = [
            'admin' => 'management.dashboard',
            'dosen' => 'dashboard',
            'koordinator' => 'coordinator.dashboard',
            'report_viewer' => 'reports.progress-funnel',
            'mahasiswa' => 'student.dashboard',
            'pembimbing_lapangan' => 'field-supervisor.index',
        ];

        return redirect()->route($homeRoutes[$role]);
    })->name('active-role.update');

    Route::get('/maps/places', [MapController::class, 'places'])->name('maps.places');
    Route::get('/maps/places/data', [MapController::class, 'placesData'])->name('maps.places.data');
    Route::get('/maps/places/route', [MapController::class, 'placesRoute'])->name('maps.places.route');
    Route::get('/maps/monitoring', [MapController::class, 'monitoring'])->name('maps.monitoring');
    Route::get('/maps/monitoring/data', [MapController::class, 'monitoringData'])->name('maps.monitoring.data');
    Route::get('/locations/search', LocationSuggestionController::class)->name('locations.search');
    Route::get('/reports/monitoring', [ReportController::class, 'monitoring'])->name('reports.monitoring');
    Route::get('/submission-progress/{progress}/file', SubmissionProgressFileController::class)->name('submission-progress.file');
    Route::get('/seminar-requests/{seminarRequest}/file/{type}', SeminarRequestFileController::class)->name('seminar-requests.file');

    Route::middleware('role:pembimbing_lapangan')->group(function () {
        Route::get('/field-supervisor', [FieldSupervisorPortalController::class, 'index'])->name('field-supervisor.index');
        Route::get('/field-supervisor/enrollments', [FieldSupervisorPortalController::class, 'enrollments'])->name('field-supervisor.enrollments.index');
        Route::get('/field-supervisor/enrollments/{enrollment}', [FieldSupervisorPortalController::class, 'show'])->name('field-supervisor.enrollments.show');
        Route::post('/field-supervisor/daily-logs/{checkIn}/validate', [FieldSupervisorPortalController::class, 'validateDailyLogForLogin'])->name('field-supervisor.daily-logs.validate');
        Route::post('/field-supervisor/enrollments/{enrollment}/daily-logs/bulk-validate', [FieldSupervisorPortalController::class, 'bulkValidateDailyLogsForLogin'])->name('field-supervisor.daily-logs.bulk-validate');
        Route::post('/field-supervisor/enrollments/{enrollment}/assessment', [FieldSupervisorPortalController::class, 'assessForLogin'])->name('field-supervisor.assessment.store');
        Route::post('/field-supervisor/forgotten-attendance-requests/{forgottenAttendanceRequest}/approve', [ForgottenAttendanceApprovalController::class, 'approveAsFieldSupervisor'])->name('forgotten-attendance-requests.field-supervisor.approve');
        Route::post('/field-supervisor/forgotten-attendance-requests/{forgottenAttendanceRequest}/reject', [ForgottenAttendanceApprovalController::class, 'rejectAsFieldSupervisor'])->name('forgotten-attendance-requests.field-supervisor.reject');
    });

    Route::middleware('role:mahasiswa')->group(function () {
        Route::get('/student', StudentDashboardController::class)->name('student.dashboard');
        Route::get('/student/profile', [StudentProfileController::class, 'edit'])->name('student.profile.edit');
        Route::patch('/student/profile', [StudentProfileController::class, 'update'])->name('student.profile.update');
        Route::get('/student/enrollments/create', [StudentEnrollmentController::class, 'create'])->name('student.enrollments.create');
        Route::post('/student/enrollments', [StudentEnrollmentController::class, 'store'])->name('student.enrollments.store');
        Route::get('/student/enrollments/{enrollment}/edit', [StudentEnrollmentController::class, 'edit'])->name('student.enrollments.edit');
        Route::patch('/student/enrollments/{enrollment}', [StudentEnrollmentController::class, 'update'])->name('student.enrollments.update');
        Route::get('/student/orientation-events/{orientationEvent}/attendance', [StudentOrientationAttendanceController::class, 'create'])->name('student.orientation-attendances.create');
        Route::post('/student/orientation-events/{orientationEvent}/attendance', [StudentOrientationAttendanceController::class, 'store'])->name('student.orientation-attendances.store');
        Route::get('/student/places', [StudentPlaceController::class, 'index'])->name('student.places.index');
        Route::get('/student/places/search', [StudentPlaceController::class, 'search'])->name('student.places.search');
        Route::get('/student/places/data', [StudentPlaceController::class, 'data'])->name('student.places.data');
        Route::get('/student/place-proposals', [StudentPlaceProposalController::class, 'index'])->name('student.proposals.index');
        Route::get('/student/place-proposals/create', [StudentPlaceProposalController::class, 'create'])->name('student.proposals.create');
        Route::post('/student/place-proposals', [StudentPlaceProposalController::class, 'store'])->name('student.proposals.store');
        Route::get('/student/place-proposals/{proposal}/edit', [StudentPlaceProposalController::class, 'edit'])->name('student.proposals.edit');
        Route::patch('/student/place-proposals/{proposal}', [StudentPlaceProposalController::class, 'update'])->name('student.proposals.update');
        Route::patch('/student/place-proposals/{proposal}/cancel', [StudentPlaceProposalController::class, 'cancel'])->name('student.proposals.cancel');
        Route::get('/student/relocations', [StudentRelocationRequestController::class, 'index'])->name('student.relocations.index');
        Route::get('/student/relocations/create', [StudentRelocationRequestController::class, 'create'])->name('student.relocations.create');
        Route::post('/student/relocations', [StudentRelocationRequestController::class, 'store'])->name('student.relocations.store');
        Route::patch('/student/relocations/{relocation}/cancel', [StudentRelocationRequestController::class, 'cancel'])->name('student.relocations.cancel');
        Route::get('/student/supervisor-requests', [StudentSupervisorChangeRequestController::class, 'index'])->name('student.supervisor-requests.index');
        Route::get('/student/supervisor-requests/create', [StudentSupervisorChangeRequestController::class, 'create'])->name('student.supervisor-requests.create');
        Route::post('/student/supervisor-requests', [StudentSupervisorChangeRequestController::class, 'store'])->name('student.supervisor-requests.store');
        Route::patch('/student/supervisor-requests/{supervisorRequest}/cancel', [StudentSupervisorChangeRequestController::class, 'cancel'])->name('student.supervisor-requests.cancel');
        Route::get('/student/reports/{enrollment}', [StudentReportController::class, 'show'])->name('student.reports.show');
        Route::get('/student/reports/{enrollment}/final-assessment/print', [StudentReportController::class, 'printFinalAssessment'])->name('student.reports.final-assessment.print');
        Route::post('/student/reports/{enrollment}/progress', [StudentReportController::class, 'storeProgress'])->name('student.reports.progress.store');
        Route::post('/student/reports/{enrollment}/seminar-requests', [StudentSeminarRequestController::class, 'store'])->name('student.seminar-requests.store');
        Route::patch('/student/seminar-requests/{seminarRequest}/cancel', [StudentSeminarRequestController::class, 'cancel'])->name('student.seminar-requests.cancel');
        Route::patch('/student/seminar-requests/{seminarRequest}/manual-assessment', [StudentSeminarRequestController::class, 'submitManualAssessment'])->name('student.seminar-requests.manual-assessment');
        Route::get('/student/reports/{enrollment}/daily-logs/print', [StudentReportController::class, 'printDailyLogs'])->name('student.reports.daily-logs.print');
        Route::get('/student/reports/{enrollment}/print', [StudentReportController::class, 'print'])->name('student.reports.print');
        Route::get('/check-ins/create', [CheckInController::class, 'create'])->name('check-ins.create');
        Route::post('/check-ins/location-samples', [CheckInController::class, 'storeLocationSample'])->middleware('throttle:30,1')->name('check-ins.location-samples.store');
        Route::post('/check-ins', [CheckInController::class, 'store'])->middleware('throttle:10,1')->name('check-ins.store');
        Route::post('/student/enrollments/{enrollment}/forgotten-attendance-requests', [StudentForgottenAttendanceRequestController::class, 'store'])->middleware('throttle:5,1')->name('student.forgotten-attendance-requests.store');
    });

    Route::middleware('role:koordinator')->group(function () {
        Route::get('/coordinator', CoordinatorDashboardController::class)->name('coordinator.dashboard');
    });

    Route::middleware('role:admin,dosen,koordinator,report_viewer')->group(function () {
        Route::get('/reports/progress-funnel', [ReportController::class, 'progressFunnel'])->name('reports.progress-funnel');
        Route::get('/reports/risk-scoring', [ReportController::class, 'riskScoring'])->name('reports.risk-scoring');
        Route::get('/reports/attendance-heatmap', [ReportController::class, 'attendanceHeatmap'])->name('reports.attendance-heatmap');
        Route::get('/reports/operational-charts', [ReportController::class, 'operationalCharts'])->name('reports.operational-charts');
        Route::get('/reports/snapshot', [ReportController::class, 'snapshot'])->name('reports.snapshot');
        Route::get('/reports/sanctions', [ReportController::class, 'sanctions'])->name('reports.sanctions');
        Route::get('/reports/final-scores', [ReportController::class, 'finalScores'])->name('reports.final-scores');
        Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/reports/drill-down', [ReportController::class, 'drillDown'])->name('reports.drill-down');
        Route::get('/reports/final-scores/{enrollment}/print', [StudentReportController::class, 'printFinalAssessment'])->name('reports.final-scores.print');
    });

    Route::middleware('role:admin,koordinator')->group(function () {
        Route::get('/management/enrollment-validations', [ManagementEnrollmentController::class, 'validations'])->name('management.enrollment-validations.index');
        Route::post('/management/enrollment-validations/bulk', [ManagementEnrollmentController::class, 'bulkValidateEnrollments'])->name('management.enrollment-validations.bulk');
        Route::get('/management/enrollment-validations/{enrollment}/registration-document', [ManagementEnrollmentController::class, 'registrationDocument'])->name('management.enrollment-validations.document');
        Route::patch('/management/enrollment-validations/{enrollment}', [ManagementEnrollmentController::class, 'validateEnrollment'])->name('management.enrollment-validations.update');
        Route::get('/management/final-assessments', [ManagementFinalAssessmentController::class, 'index'])->name('management.final-assessments.index');
        Route::post('/management/final-assessments/{enrollment}', [ManagementFinalAssessmentController::class, 'store'])->name('management.final-assessments.store');
        Route::get('/management/forgotten-attendance-requests', [ManagementForgottenAttendanceRequestController::class, 'index'])->name('management.forgotten-attendance-requests.index');
        Route::post('/management/forgotten-attendance-requests/{forgottenAttendanceRequest}/approve', [ForgottenAttendanceApprovalController::class, 'approveAsManagement'])->name('forgotten-attendance-requests.management.approve');
        Route::post('/management/forgotten-attendance-requests/{forgottenAttendanceRequest}/reject', [ForgottenAttendanceApprovalController::class, 'rejectAsManagement'])->name('forgotten-attendance-requests.management.reject');
        Route::get('/management/place-proposals', [ManagementPlaceProposalController::class, 'index'])->name('management.place-proposals.index');
        Route::post('/management/place-proposals/{proposal}/approve', [ManagementPlaceProposalController::class, 'approve'])->name('management.place-proposals.approve');
        Route::post('/management/place-proposals/{proposal}/reject', [ManagementPlaceProposalController::class, 'reject'])->name('management.place-proposals.reject');
        Route::get('/management/places/search', [ManagementPlaceController::class, 'search'])->name('management.places.search');
        Route::get('/management/orientation-events', [ManagementOrientationEventController::class, 'index'])->name('management.orientation-events.index');
        Route::get('/management/orientation-events/locations/search', [ManagementOrientationEventController::class, 'locationSuggestions'])->name('management.orientation-events.locations.search');
        Route::post('/management/orientation-events', [ManagementOrientationEventController::class, 'store'])->name('management.orientation-events.store');
        Route::get('/management/orientation-events/{orientationEvent}/edit', [ManagementOrientationEventController::class, 'edit'])->name('management.orientation-events.edit');
        Route::patch('/management/orientation-events/{orientationEvent}', [ManagementOrientationEventController::class, 'update'])->name('management.orientation-events.update');
        Route::delete('/management/orientation-events/{orientationEvent}', [ManagementOrientationEventController::class, 'destroy'])->name('management.orientation-events.destroy');
        Route::get('/management/orientation-events/{orientationEvent}', [ManagementOrientationEventController::class, 'show'])->name('management.orientation-events.show');
        Route::get('/management/supervisor-requests', [ManagementSupervisorChangeRequestController::class, 'index'])->name('management.supervisor-requests.index');
        Route::patch('/management/supervisor-requests/{supervisorRequest}', [ManagementSupervisorChangeRequestController::class, 'update'])->name('management.supervisor-requests.update');
        Route::get('/management/relocations', [ManagementRelocationRequestController::class, 'index'])->name('management.relocations.index');
        Route::patch('/management/relocations/{relocation}', [ManagementRelocationRequestController::class, 'update'])->name('management.relocations.update');
        Route::get('/management/field-supervisors', [ManagementFieldSupervisorController::class, 'index'])->name('management.field-supervisors.index');
        Route::post('/management/field-supervisors/account', [ManagementFieldSupervisorController::class, 'createAccount'])->name('management.field-supervisors.create-account');
        Route::post('/management/field-supervisors/portal-access', [ManagementFieldSupervisorAccessController::class, 'sendPortalAccess'])->name('management.field-supervisors.portal-access');
    });

    Route::middleware('role:admin,dosen,koordinator')->group(function () {
        Route::get('/management/submission-progress', [ManagementSubmissionProgressController::class, 'index'])->name('management.submission-progress.index');
        Route::patch('/management/submission-progress/{progress}', [ManagementSubmissionProgressController::class, 'update'])->name('management.submission-progress.update');
        Route::get('/management/seminar-requests', [ManagementSeminarRequestController::class, 'index'])->name('management.seminar-requests.index');
        Route::patch('/management/seminar-requests/{seminarRequest}/lecturer-decision', [ManagementSeminarRequestController::class, 'lecturerDecision'])->name('management.seminar-requests.lecturer-decision');
        Route::patch('/management/seminar-requests/{seminarRequest}/manual-acc', [ManagementSeminarRequestController::class, 'validateManualAcc'])->name('management.seminar-requests.manual-acc');
        Route::patch('/management/seminar-requests/{seminarRequest}/manual-assessment', [ManagementSeminarRequestController::class, 'validateManualAssessment'])->name('management.seminar-requests.manual-assessment');
        Route::patch('/management/seminar-requests/{seminarRequest}/schedule', [ManagementSeminarRequestController::class, 'schedule'])->name('management.seminar-requests.schedule');
        Route::patch('/management/seminar-requests/{seminarRequest}/score', [ManagementSeminarRequestController::class, 'score'])->name('management.seminar-requests.score');
    });

    Route::middleware('role:admin,dosen')->group(function () {
        Route::get('/internship-places/create', [InternshipPlaceController::class, 'create'])->name('internship-places.create');
        Route::post('/internship-places', [InternshipPlaceController::class, 'store'])->name('internship-places.store');
        Route::get('/internship-places/{internshipPlace}/edit', [InternshipPlaceController::class, 'edit'])->name('internship-places.edit');
        Route::patch('/internship-places/{internshipPlace}', [InternshipPlaceController::class, 'update'])->name('internship-places.update');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/management', ManagementDashboardController::class)->name('management.dashboard');
        Route::get('/management/audit-logs', [ManagementAuditLogController::class, 'index'])->name('management.audit-logs.index');
        Route::get('/management/users', [ManagementUserController::class, 'index'])->name('management.users.index');
        Route::post('/management/users', [ManagementUserController::class, 'store'])->name('management.users.store');
        Route::get('/management/users/{user}/edit', [ManagementUserController::class, 'edit'])->name('management.users.edit');
        Route::patch('/management/users/{user}', [ManagementUserController::class, 'update'])->name('management.users.update');

        Route::get('/management/study-programs', [ManagementStudyProgramController::class, 'index'])->name('management.study-programs.index');
        Route::post('/management/study-programs', [ManagementStudyProgramController::class, 'store'])->name('management.study-programs.store');
        Route::get('/management/study-programs/{studyProgram}/edit', [ManagementStudyProgramController::class, 'edit'])->name('management.study-programs.edit');
        Route::patch('/management/study-programs/{studyProgram}', [ManagementStudyProgramController::class, 'update'])->name('management.study-programs.update');
        Route::delete('/management/study-programs/{studyProgram}', [ManagementStudyProgramController::class, 'destroy'])->name('management.study-programs.destroy');

        Route::get('/management/organizations', [ManagementOrganizationController::class, 'index'])->name('management.organizations.index');
        Route::post('/management/organizations', [ManagementOrganizationController::class, 'store'])->name('management.organizations.store');
        Route::get('/management/organizations/{organization}/edit', [ManagementOrganizationController::class, 'edit'])->name('management.organizations.edit');
        Route::patch('/management/organizations/{organization}', [ManagementOrganizationController::class, 'update'])->name('management.organizations.update');
        Route::delete('/management/organizations/{organization}', [ManagementOrganizationController::class, 'destroy'])->name('management.organizations.destroy');

        Route::get('/management/programs', [ManagementProgramController::class, 'index'])->name('management.programs.index');
        Route::post('/management/programs', [ManagementProgramController::class, 'store'])->name('management.programs.store');
        Route::get('/management/programs/{program}/edit', [ManagementProgramController::class, 'edit'])->name('management.programs.edit');
        Route::patch('/management/programs/{program}', [ManagementProgramController::class, 'update'])->name('management.programs.update');

        Route::get('/management/periods', [ManagementPeriodController::class, 'index'])->name('management.periods.index');
        Route::post('/management/periods', [ManagementPeriodController::class, 'store'])->name('management.periods.store');
        Route::get('/management/periods/{period}/edit', [ManagementPeriodController::class, 'edit'])->name('management.periods.edit');
        Route::patch('/management/periods/{period}', [ManagementPeriodController::class, 'update'])->name('management.periods.update');
        Route::post('/management/periods/{period}/complete', [ManagementPeriodController::class, 'complete'])->name('management.periods.complete');

        Route::get('/management/students', [ManagementStudentController::class, 'index'])->name('management.students.index');
        Route::post('/management/students', [ManagementStudentController::class, 'store'])->name('management.students.store');
        Route::get('/management/students/{student}/edit', [ManagementStudentController::class, 'edit'])->name('management.students.edit');
        Route::patch('/management/students/{student}', [ManagementStudentController::class, 'update'])->name('management.students.update');

        Route::get('/management/lecturers', [ManagementLecturerController::class, 'index'])->name('management.lecturers.index');
        Route::post('/management/lecturers', [ManagementLecturerController::class, 'store'])->name('management.lecturers.store');
        Route::get('/management/lecturers/{lecturer}/edit', [ManagementLecturerController::class, 'edit'])->name('management.lecturers.edit');
        Route::patch('/management/lecturers/{lecturer}', [ManagementLecturerController::class, 'update'])->name('management.lecturers.update');

        Route::get('/management/coordinators', [ManagementCoordinatorController::class, 'index'])->name('management.coordinators.index');
        Route::post('/management/coordinators', [ManagementCoordinatorController::class, 'store'])->name('management.coordinators.store');
        Route::get('/management/coordinators/{coordinator}/edit', [ManagementCoordinatorController::class, 'edit'])->name('management.coordinators.edit');
        Route::patch('/management/coordinators/{coordinator}', [ManagementCoordinatorController::class, 'update'])->name('management.coordinators.update');

        Route::get('/management/report-viewers', [ManagementReportViewerController::class, 'index'])->name('management.report-viewers.index');
        Route::post('/management/report-viewers', [ManagementReportViewerController::class, 'store'])->name('management.report-viewers.store');
        Route::get('/management/report-viewers/{reportViewer}/edit', [ManagementReportViewerController::class, 'edit'])->name('management.report-viewers.edit');
        Route::patch('/management/report-viewers/{reportViewer}', [ManagementReportViewerController::class, 'update'])->name('management.report-viewers.update');
        Route::delete('/management/report-viewers/{reportViewer}', [ManagementReportViewerController::class, 'destroy'])->name('management.report-viewers.destroy');

        Route::get('/management/places', [ManagementPlaceController::class, 'index'])->name('management.places.index');
        Route::get('/management/places/export', [ManagementPlaceController::class, 'export'])->name('management.places.export');
        Route::get('/management/places/create', [InternshipPlaceController::class, 'create'])->name('management.places.create');
        Route::post('/management/places', [InternshipPlaceController::class, 'store'])->name('management.places.store');
        Route::post('/management/places/bulk', [ManagementPlaceController::class, 'bulk'])->name('management.places.bulk');
        Route::get('/management/places/{internshipPlace}/edit', [InternshipPlaceController::class, 'edit'])->name('management.places.edit');
        Route::patch('/management/places/{internshipPlace}', [InternshipPlaceController::class, 'update'])->name('management.places.update');
        Route::get('/management/enrollments', [ManagementEnrollmentController::class, 'index'])->name('management.enrollments.index');
        Route::post('/management/enrollments/{enrollment}/field-supervisor-access', [ManagementFieldSupervisorAccessController::class, 'store'])->name('management.enrollments.field-supervisor-access.store');
        Route::delete('/management/enrollments/{enrollment}/field-supervisor-access', [ManagementFieldSupervisorAccessController::class, 'destroy'])->name('management.enrollments.field-supervisor-access.destroy');
        Route::get('/management/enrollments/students/search', [ManagementEnrollmentController::class, 'studentSearch'])->name('management.enrollments.students.search');
        Route::post('/management/enrollments', [ManagementEnrollmentController::class, 'store'])->name('management.enrollments.store');
        Route::get('/management/enrollments/{enrollment}/edit', [ManagementEnrollmentController::class, 'edit'])->name('management.enrollments.edit');
        Route::patch('/management/enrollments/{enrollment}', [ManagementEnrollmentController::class, 'update'])->name('management.enrollments.update');

        Route::get('/system-configurations', [SystemConfigurationController::class, 'index'])->name('system-configurations.index');
        Route::get('/system-configurations/{period}/edit', [SystemConfigurationController::class, 'edit'])->name('system-configurations.edit');
        Route::patch('/system-configurations/{period}', [SystemConfigurationController::class, 'update'])->name('system-configurations.update');
        Route::get('/email-notifications', [EmailNotificationConfigurationController::class, 'index'])->name('email-notifications.index');
        Route::patch('/email-notifications/status', [EmailNotificationConfigurationController::class, 'updateStatus'])->name('email-notifications.status.update');
        Route::patch('/email-notifications/coverage', [EmailNotificationConfigurationController::class, 'updateCoverage'])->name('email-notifications.coverage.update');
        Route::patch('/email-notifications/mail', [EmailNotificationConfigurationController::class, 'updateMail'])->name('email-notifications.mail.update');
        Route::post('/email-notifications/process', [EmailNotificationConfigurationController::class, 'processPending'])->name('email-notifications.process');
        Route::post('/email-notifications/retry-failed', [EmailNotificationConfigurationController::class, 'retryFailed'])->name('email-notifications.retry-failed');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
