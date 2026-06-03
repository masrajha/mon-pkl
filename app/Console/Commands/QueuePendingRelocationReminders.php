<?php

namespace App\Console\Commands;

use App\Services\RelocationEmailNotificationService;
use Illuminate\Console\Command;

class QueuePendingRelocationReminders extends Command
{
    protected $signature = 'silat:relocation-notifications:queue-pending-reminders
        {--hours=48 : Queue reminders for relocation requests pending at least this many hours}';

    protected $description = 'Queue reminder emails for pending relocation requests.';

    public function handle(RelocationEmailNotificationService $notifications): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $queued = $notifications->queuePendingReminders($hours);

        $this->info("Pending relocation reminder notifications queued: {$queued}.");

        return self::SUCCESS;
    }
}
