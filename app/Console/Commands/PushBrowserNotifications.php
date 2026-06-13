<?php

namespace App\Console\Commands;

use App\Services\BrowserNotificationService;
use Illuminate\Console\Command;

class PushBrowserNotifications extends Command
{
    protected $signature = 'silat:browser-notifications:push
        {--limit=100 : Maksimal antrean notifikasi yang diproses}';

    protected $description = 'Send due browser notifications through Web Push subscriptions.';

    public function handle(BrowserNotificationService $notifications): int
    {
        $result = $notifications->pushDueNotifications((int) $this->option('limit'));

        if ($result['message']) {
            $this->warn($result['message']);
        }

        $this->info("Browser notifications pushed: {$result['sent']} sent, {$result['failed']} failed, {$result['skipped']} skipped.");

        return self::SUCCESS;
    }
}
