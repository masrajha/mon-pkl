<?php

namespace App\Console\Commands;

use App\Services\PlaceProposalEmailNotificationService;
use Illuminate\Console\Command;

class QueuePendingPlaceProposalReminders extends Command
{
    protected $signature = 'silat:place-proposal-notifications:queue-pending-reminders
        {--hours=48 : Queue reminders for proposals pending at least this many hours}';

    protected $description = 'Queue reminder emails for pending internship place proposals.';

    public function handle(PlaceProposalEmailNotificationService $notifications): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $queued = $notifications->queuePendingReminders($hours);

        $this->info("Pending place proposal reminder notifications queued: {$queued}.");

        return self::SUCCESS;
    }
}
