<?php

namespace App\Console\Commands;

use App\Services\AttendanceDigestEmailNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class QueueAttendanceDigestNotifications extends Command
{
    protected $signature = 'silat:attendance-digests:queue
        {--week-start= : Start date for digest range}
        {--week-end= : End date for digest range}';

    protected $description = 'Queue weekly attendance digest emails for students, lecturers, and coordinators.';

    public function handle(AttendanceDigestEmailNotificationService $digests): int
    {
        $weekStart = $this->option('week-start') ? Carbon::parse($this->option('week-start'))->startOfDay() : null;
        $weekEnd = $this->option('week-end') ? Carbon::parse($this->option('week-end'))->endOfDay() : null;

        $queued = $digests->queueWeeklyDigests($weekStart, $weekEnd);

        $this->info("Attendance digest notifications queued: {$queued}.");

        return self::SUCCESS;
    }
}
