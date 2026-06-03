<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\InternshipPlaceController;
use App\Http\Controllers\LocationSuggestionController;
use App\Http\Controllers\Management\CoordinatorController as ManagementCoordinatorController;
use App\Http\Controllers\Management\DashboardController as ManagementDashboardController;
use App\Http\Controllers\Management\EnrollmentController as ManagementEnrollmentController;
use App\Http\Controllers\Management\LecturerController as ManagementLecturerController;
use App\Http\Controllers\Management\OrientationEventController as ManagementOrientationEventController;
use App\Http\Controllers\Management\PeriodController as ManagementPeriodController;
use App\Http\Controllers\Management\PlaceController as ManagementPlaceController;
use App\Http\Controllers\Management\PlaceProposalController as ManagementPlaceProposalController;
use App\Http\Controllers\Management\ProgramController as ManagementProgramController;
use App\Http\Controllers\Management\RelocationRequestController as ManagementRelocationRequestController;
use App\Http\Controllers\Management\StudentController as ManagementStudentController;
use App\Http\Controllers\Management\StudyProgramController as ManagementStudyProgramController;
use App\Http\Controllers\Management\SubmissionProgressController as ManagementSubmissionProgressController;
use App\Http\Controllers\Management\SupervisorChangeRequestController as ManagementSupervisorChangeRequestController;
use App\Http\Controllers\Management\UserController as ManagementUserController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;
use App\Http\Controllers\Student\OrientationAttendanceController as StudentOrientationAttendanceController;
use App\Http\Controllers\Student\PlaceController as StudentPlaceController;
use App\Http\Controllers\Student\PlaceProposalController as StudentPlaceProposalController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ReportController as StudentReportController;
use App\Http\Controllers\Student\RelocationRequestController as StudentRelocationRequestController;
use App\Http\Controllers\Student\SupervisorChangeRequestController as StudentSupervisorChangeRequestController;
use App\Http\Controllers\SubmissionProgressFileController;
use App\Http\Controllers\SystemConfigurationController;
use App\Models\CheckIn;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Student;
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
                $query
                    ->where('is_active', true)
                    ->orWhereDate('starts_at', '>=', today())
                    ->orWhere(function ($activeDateQuery) {
                        $activeDateQuery
                            ->whereDate('starts_at', '<=', today())
                            ->whereDate('ends_at', '>=', today());
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

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/maps/places', [MapController::class, 'places'])->name('maps.places');
    Route::get('/maps/places/data', [MapController::class, 'placesData'])->name('maps.places.data');
    Route::get('/maps/monitoring', [MapController::class, 'monitoring'])->name('maps.monitoring');
    Route::get('/maps/monitoring/data', [MapController::class, 'monitoringData'])->name('maps.monitoring.data');
    Route::get('/locations/search', LocationSuggestionController::class)->name('locations.search');
    Route::get('/reports/monitoring', [ReportController::class, 'monitoring'])->name('reports.monitoring');
    Route::get('/submission-progress/{progress}/file', SubmissionProgressFileController::class)->name('submission-progress.file');

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
        Route::get('/student/places/data', [StudentPlaceController::class, 'data'])->name('student.places.data');
        Route::get('/student/place-proposals/create', [StudentPlaceProposalController::class, 'create'])->name('student.proposals.create');
        Route::post('/student/place-proposals', [StudentPlaceProposalController::class, 'store'])->name('student.proposals.store');
        Route::get('/student/relocations/create', [StudentRelocationRequestController::class, 'create'])->name('student.relocations.create');
        Route::post('/student/relocations', [StudentRelocationRequestController::class, 'store'])->name('student.relocations.store');
        Route::get('/student/supervisor-requests/create', [StudentSupervisorChangeRequestController::class, 'create'])->name('student.supervisor-requests.create');
        Route::post('/student/supervisor-requests', [StudentSupervisorChangeRequestController::class, 'store'])->name('student.supervisor-requests.store');
        Route::get('/student/reports/{enrollment}', [StudentReportController::class, 'show'])->name('student.reports.show');
        Route::post('/student/reports/{enrollment}/progress', [StudentReportController::class, 'storeProgress'])->name('student.reports.progress.store');
        Route::get('/student/reports/{enrollment}/daily-logs/print', [StudentReportController::class, 'printDailyLogs'])->name('student.reports.daily-logs.print');
        Route::get('/student/reports/{enrollment}/print', [StudentReportController::class, 'print'])->name('student.reports.print');
        Route::get('/check-ins/create', [CheckInController::class, 'create'])->name('check-ins.create');
        Route::post('/check-ins', [CheckInController::class, 'store'])->middleware('throttle:10,1')->name('check-ins.store');
    });

    Route::middleware('role:koordinator')->group(function () {
        Route::get('/coordinator', CoordinatorDashboardController::class)->name('coordinator.dashboard');
    });

    Route::middleware('role:admin,koordinator')->group(function () {
        Route::get('/management/enrollment-validations', [ManagementEnrollmentController::class, 'validations'])->name('management.enrollment-validations.index');
        Route::get('/management/enrollment-validations/{enrollment}/registration-document', [ManagementEnrollmentController::class, 'registrationDocument'])->name('management.enrollment-validations.document');
        Route::patch('/management/enrollment-validations/{enrollment}', [ManagementEnrollmentController::class, 'validateEnrollment'])->name('management.enrollment-validations.update');
        Route::get('/management/orientation-events', [ManagementOrientationEventController::class, 'index'])->name('management.orientation-events.index');
        Route::get('/management/orientation-events/locations/search', [ManagementOrientationEventController::class, 'locationSuggestions'])->name('management.orientation-events.locations.search');
        Route::post('/management/orientation-events', [ManagementOrientationEventController::class, 'store'])->name('management.orientation-events.store');
        Route::get('/management/orientation-events/{orientationEvent}', [ManagementOrientationEventController::class, 'show'])->name('management.orientation-events.show');
        Route::get('/management/supervisor-requests', [ManagementSupervisorChangeRequestController::class, 'index'])->name('management.supervisor-requests.index');
        Route::patch('/management/supervisor-requests/{supervisorRequest}', [ManagementSupervisorChangeRequestController::class, 'update'])->name('management.supervisor-requests.update');
        Route::get('/management/relocations', [ManagementRelocationRequestController::class, 'index'])->name('management.relocations.index');
        Route::patch('/management/relocations/{relocation}', [ManagementRelocationRequestController::class, 'update'])->name('management.relocations.update');
    });

    Route::middleware('role:admin,dosen,koordinator')->group(function () {
        Route::get('/management/submission-progress', [ManagementSubmissionProgressController::class, 'index'])->name('management.submission-progress.index');
        Route::patch('/management/submission-progress/{progress}', [ManagementSubmissionProgressController::class, 'update'])->name('management.submission-progress.update');
    });

    Route::middleware('role:admin,dosen')->group(function () {
        Route::get('/internship-places/create', [InternshipPlaceController::class, 'create'])->name('internship-places.create');
        Route::post('/internship-places', [InternshipPlaceController::class, 'store'])->name('internship-places.store');
        Route::get('/internship-places/{internshipPlace}/edit', [InternshipPlaceController::class, 'edit'])->name('internship-places.edit');
        Route::patch('/internship-places/{internshipPlace}', [InternshipPlaceController::class, 'update'])->name('internship-places.update');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/management', ManagementDashboardController::class)->name('management.dashboard');
        Route::get('/management/users', [ManagementUserController::class, 'index'])->name('management.users.index');
        Route::post('/management/users', [ManagementUserController::class, 'store'])->name('management.users.store');
        Route::get('/management/users/{user}/edit', [ManagementUserController::class, 'edit'])->name('management.users.edit');
        Route::patch('/management/users/{user}', [ManagementUserController::class, 'update'])->name('management.users.update');

        Route::get('/management/study-programs', [ManagementStudyProgramController::class, 'index'])->name('management.study-programs.index');
        Route::post('/management/study-programs', [ManagementStudyProgramController::class, 'store'])->name('management.study-programs.store');
        Route::get('/management/study-programs/{studyProgram}/edit', [ManagementStudyProgramController::class, 'edit'])->name('management.study-programs.edit');
        Route::patch('/management/study-programs/{studyProgram}', [ManagementStudyProgramController::class, 'update'])->name('management.study-programs.update');

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

        Route::get('/management/places', [ManagementPlaceController::class, 'index'])->name('management.places.index');
        Route::post('/management/places/bulk', [ManagementPlaceController::class, 'bulk'])->name('management.places.bulk');
        Route::get('/management/place-proposals', [ManagementPlaceProposalController::class, 'index'])->name('management.place-proposals.index');
        Route::post('/management/place-proposals/{proposal}/approve', [ManagementPlaceProposalController::class, 'approve'])->name('management.place-proposals.approve');
        Route::post('/management/place-proposals/{proposal}/reject', [ManagementPlaceProposalController::class, 'reject'])->name('management.place-proposals.reject');
        Route::get('/management/enrollments', [ManagementEnrollmentController::class, 'index'])->name('management.enrollments.index');
        Route::get('/management/enrollments/students/search', [ManagementEnrollmentController::class, 'studentSearch'])->name('management.enrollments.students.search');
        Route::post('/management/enrollments', [ManagementEnrollmentController::class, 'store'])->name('management.enrollments.store');
        Route::get('/management/enrollments/{enrollment}/edit', [ManagementEnrollmentController::class, 'edit'])->name('management.enrollments.edit');
        Route::patch('/management/enrollments/{enrollment}', [ManagementEnrollmentController::class, 'update'])->name('management.enrollments.update');

        Route::get('/system-configurations', [SystemConfigurationController::class, 'index'])->name('system-configurations.index');
        Route::get('/system-configurations/{period}/edit', [SystemConfigurationController::class, 'edit'])->name('system-configurations.edit');
        Route::patch('/system-configurations/{period}', [SystemConfigurationController::class, 'update'])->name('system-configurations.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
