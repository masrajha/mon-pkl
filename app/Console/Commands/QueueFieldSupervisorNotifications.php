<?php

namespace App\Console\Commands;

use App\Services\FieldSupervisorEmailNotificationService;
use Illuminate\Console\Command;

class QueueFieldSupervisorNotifications extends Command
{
    protected $signature = 'silat:field-supervisor-notifications:queue
        {--minimum-pending-days=1 : Minimum pending daily-log rows before reminder is queued}
        {--full-report-days=7,3,1 : Comma-separated H-day offsets before full_report deadline}';

    protected $description = 'Queue field supervisor token replacements, reminders, and reviewer alerts.';

    public function handle(FieldSupervisorEmailNotificationService $notifications): int
    {
        $tokens = $notifications->queueExpiredTokenReplacements();
        $dailyLogs = $notifications->queueDailyLogValidationReminders(max(1, (int) $this->option('minimum-pending-days')));
        $assessments = $notifications->queueAssessmentReminders();
        $fullReport = $notifications->queueFullReportDeadlineReminders($this->fullReportDays());
        $forgottenAttendance = $notifications->queueForgottenAttendanceReminders();
        $alerts = $notifications->queueReviewerAlerts();

        $this->info("Field supervisor notifications queued: {$tokens} token emails, {$dailyLogs} daily-log reminders, {$assessments} assessment reminders, {$fullReport} full-report deadline reminders, {$forgottenAttendance} forgotten-attendance reminders, {$alerts} reviewer alerts.");

        return self::SUCCESS;
    }

    private function fullReportDays(): array
    {
        return collect(explode(',', (string) $this->option('full-report-days')))
            ->map(fn (string $day): int => (int) trim($day))
            ->filter(fn (int $day): bool => $day > 0)
            ->unique()
            ->values()
            ->all();
    }
}
