<?php

namespace App\Console\Commands;

use App\Services\OrientationEmailNotificationService;
use Illuminate\Console\Command;

class QueueOrientationNotifications extends Command
{
    protected $signature = 'silat:orientation-notifications:queue
        {--hours-before=24 : Queue start reminders for events within this many hours}
        {--closing-minutes=60 : Queue missing attendance reminders this many minutes before closing}
        {--summary-hours=24 : Queue closed summaries for events closed within this many hours}';

    protected $description = 'Queue orientation event reminders and closed attendance summaries.';

    public function handle(OrientationEmailNotificationService $notifications): int
    {
        $reminders = $notifications->queueReminders(
            max(1, (int) $this->option('hours-before')),
            max(1, (int) $this->option('closing-minutes')),
        );

        $summaries = $notifications->queueClosedSummaries(max(1, (int) $this->option('summary-hours')));

        $this->info("Orientation notifications queued: {$reminders} reminders, {$summaries} summaries.");

        return self::SUCCESS;
    }
}
