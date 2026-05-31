<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InternshipPlaceController;
use App\Http\Controllers\Management\CoordinatorController as ManagementCoordinatorController;
use App\Http\Controllers\Management\DashboardController as ManagementDashboardController;
use App\Http\Controllers\Management\EnrollmentController as ManagementEnrollmentController;
use App\Http\Controllers\Management\LecturerController as ManagementLecturerController;
use App\Http\Controllers\Management\PeriodController as ManagementPeriodController;
use App\Http\Controllers\Management\PlaceController as ManagementPlaceController;
use App\Http\Controllers\Management\PlaceProposalController as ManagementPlaceProposalController;
use App\Http\Controllers\Management\StudentController as ManagementStudentController;
use App\Http\Controllers\Management\StudyProgramController as ManagementStudyProgramController;
use App\Http\Controllers\Management\UserController as ManagementUserController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;
use App\Http\Controllers\Student\PlaceProposalController as StudentPlaceProposalController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ReportController as StudentReportController;
use App\Http\Controllers\SystemConfigurationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/maps/places', [MapController::class, 'places'])->name('maps.places');
    Route::get('/maps/places/data', [MapController::class, 'placesData'])->name('maps.places.data');
    Route::get('/maps/monitoring', [MapController::class, 'monitoring'])->name('maps.monitoring');
    Route::get('/maps/monitoring/data', [MapController::class, 'monitoringData'])->name('maps.monitoring.data');
    Route::get('/reports/monitoring', [ReportController::class, 'monitoring'])->name('reports.monitoring');

    Route::middleware('role:mahasiswa')->group(function () {
        Route::get('/student', StudentDashboardController::class)->name('student.dashboard');
        Route::get('/student/profile', [StudentProfileController::class, 'edit'])->name('student.profile.edit');
        Route::patch('/student/profile', [StudentProfileController::class, 'update'])->name('student.profile.update');
        Route::get('/student/enrollments/create', [StudentEnrollmentController::class, 'create'])->name('student.enrollments.create');
        Route::post('/student/enrollments', [StudentEnrollmentController::class, 'store'])->name('student.enrollments.store');
        Route::get('/student/place-proposals/create', [StudentPlaceProposalController::class, 'create'])->name('student.proposals.create');
        Route::post('/student/place-proposals', [StudentPlaceProposalController::class, 'store'])->name('student.proposals.store');
        Route::get('/student/reports/{enrollment}', [StudentReportController::class, 'show'])->name('student.reports.show');
        Route::get('/student/reports/{enrollment}/print', [StudentReportController::class, 'print'])->name('student.reports.print');
        Route::get('/check-ins/create', [CheckInController::class, 'create'])->name('check-ins.create');
        Route::post('/check-ins', [CheckInController::class, 'store'])->name('check-ins.store');
    });

    Route::middleware('role:koordinator')->group(function () {
        Route::get('/coordinator', CoordinatorDashboardController::class)->name('coordinator.dashboard');
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

        Route::get('/management/periods', [ManagementPeriodController::class, 'index'])->name('management.periods.index');
        Route::post('/management/periods', [ManagementPeriodController::class, 'store'])->name('management.periods.store');
        Route::get('/management/periods/{period}/edit', [ManagementPeriodController::class, 'edit'])->name('management.periods.edit');
        Route::patch('/management/periods/{period}', [ManagementPeriodController::class, 'update'])->name('management.periods.update');

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
