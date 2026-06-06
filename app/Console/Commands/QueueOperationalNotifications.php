<?php

namespace App\Console\Commands;

use App\Services\OperationalEmailNotificationService;
use Illuminate\Console\Command;

class QueueOperationalNotifications extends Command
{
    protected $signature = 'silat:operational-notifications:queue';

    protected $description = 'Queue operational notification digests such as email delivery failures.';

    public function handle(OperationalEmailNotificationService $notifications): int
    {
        $failures = $notifications->queueEmailFailureDigest();

        $this->info("Operational notifications queued: {$failures} email failure digests.");

        return self::SUCCESS;
    }
}
