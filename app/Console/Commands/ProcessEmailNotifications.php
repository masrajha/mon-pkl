<?php

namespace App\Console\Commands;

use App\Models\EmailNotification;
use App\Services\EmailNotificationService;
use Illuminate\Console\Command;

class ProcessEmailNotifications extends Command
{
    protected $signature = 'silat:email-notifications:process
        {--limit=100 : Maximum pending notifications to process}
        {--retry-failed : Move failed notifications back to pending before processing}';

    protected $description = 'Process scheduled SiLAT email notifications.';

    public function handle(EmailNotificationService $notifications): int
    {
        $limit = max(1, (int) $this->option('limit'));

        if ($this->option('retry-failed')) {
            $reset = EmailNotification::query()
                ->where('status', 'failed')
                ->where('attempts', '<', 3)
                ->update([
                    'status' => 'pending',
                    'failed_at' => null,
                    'error_message' => null,
                    'updated_at' => now(),
                ]);

            $this->info("Failed notifications reset: {$reset}");
        }

        $result = $notifications->processDue($limit);

        $skipped = $result['skipped'] ?? 0;

        $this->info("Email notifications processed. Sent: {$result['sent']}. Failed: {$result['failed']}. Skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
