<?php

namespace App\Console\Commands;

use App\Services\BrowserNotificationService;
use Illuminate\Console\Command;

class QueueBrowserAttendanceReminders extends Command
{
    protected $signature = 'silat:browser-notifications:queue-attendance-reminders
        {--minutes=15 : Menit sebelum batas akhir presensi untuk mengirim reminder}';

    protected $description = 'Queue browser notification reminders for missing check-in and check-out attendance.';

    public function handle(BrowserNotificationService $notifications): int
    {
        $queued = $notifications->queueAttendanceReminders((int) $this->option('minutes'));

        $this->info("Browser attendance reminders queued: {$queued}.");

        return self::SUCCESS;
    }
}
