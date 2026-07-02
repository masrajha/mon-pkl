<?php

namespace App\Providers;

use App\Models\CheckIn;
use App\Models\FieldSupervisorAssessment;
use App\Models\FinalAssessment;
use App\Models\ForgottenAttendanceRequest;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPeriodSetting;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Organization;
use App\Models\PeriodDeadline;
use App\Models\Program;
use App\Models\ReportViewerAssignment;
use App\Models\Sanction;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\SubmissionProgress;
use App\Models\User;
use App\Models\WfaRequest;
use App\Observers\AuditLogObserver;
use App\Services\ActionRequiredSummaryService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ($this->auditedModels() as $model) {
            if (class_exists($model)) {
                $model::observe(AuditLogObserver::class);
            }
        }

        View::composer('layouts.navigation', function ($view): void {
            $view->with('actionRequiredSummary', app(ActionRequiredSummaryService::class)->forUser(auth()->user()));
        });
    }

    private function auditedModels(): array
    {
        return [
            InternshipPeriodSetting::class,
            Sanction::class,
            CheckIn::class,
            FinalAssessment::class,
            FieldSupervisorAssessment::class,
            InternshipEnrollment::class,
            ForgottenAttendanceRequest::class,
            User::class,
            Student::class,
            Lecturer::class,
            Program::class,
            StudyProgram::class,
            Organization::class,
            InternshipPlace::class,
            InternshipPeriod::class,
            PeriodDeadline::class,
            InternshipCoordinator::class,
            SubmissionProgress::class,
            ReportViewerAssignment::class,
            WfaRequest::class,
        ];
    }
}
