<?php

namespace App\Console\Commands;

use App\Services\SupervisorChangeEmailNotificationService;
use Illuminate\Console\Command;

class QueuePendingSupervisorChangeReminders extends Command
{
    protected $signature = 'silat:supervisor-change-notifications:queue-pending-reminders
        {--hours=48 : Queue reminders for supervisor change requests pending at least this many hours}';

    protected $description = 'Queue reminder emails for pending supervisor change requests.';

    public function handle(SupervisorChangeEmailNotificationService $notifications): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $queued = $notifications->queuePendingReminders($hours);

        $this->info("Pending supervisor change reminder notifications queued: {$queued}.");

        return self::SUCCESS;
    }
}
