<?php

namespace App\Console\Commands;

use App\Services\AssessmentEmailNotificationService;
use Illuminate\Console\Command;

class QueueAssessmentNotifications extends Command
{
    protected $signature = 'silat:assessment-notifications:queue
        {--days-before-period-end=7 : Alert threshold for incomplete assessment components}';

    protected $description = 'Queue lecturer assessment reminders and finalization readiness alerts.';

    public function handle(AssessmentEmailNotificationService $notifications): int
    {
        $lecturer = $notifications->queueLecturerAssessmentReminders();
        $finalization = $notifications->queueFinalizationAlerts(max(0, (int) $this->option('days-before-period-end')));

        $this->info("Assessment notifications queued: {$lecturer} lecturer reminders, {$finalization} finalization alerts.");

        return self::SUCCESS;
    }
}
