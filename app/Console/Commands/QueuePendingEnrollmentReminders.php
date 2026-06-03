<?php

namespace App\Console\Commands;

use App\Services\EnrollmentEmailNotificationService;
use Illuminate\Console\Command;

class QueuePendingEnrollmentReminders extends Command
{
    protected $signature = 'silat:enrollment-notifications:queue-pending-reminders
        {--days=3 : Queue reminders for registration deadlines within this many days}';

    protected $description = 'Queue reminder emails for pending enrollment validations near registration deadline.';

    public function handle(EnrollmentEmailNotificationService $notifications): int
    {
        $days = max(0, (int) $this->option('days'));
        $queued = $notifications->queuePendingValidationReminders($days);

        $this->info("Pending enrollment reminder notifications queued: {$queued}.");

        return self::SUCCESS;
    }
}
