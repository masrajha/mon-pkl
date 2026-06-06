<?php

namespace App\Console\Commands;

use App\Services\SubmissionProgressEmailNotificationService;
use Illuminate\Console\Command;

class QueueSubmissionProgressNotifications extends Command
{
    protected $signature = 'silat:submission-progress-notifications:queue
        {--deadline-days=7,3,1,0 : Comma-separated deadline reminder offsets}
        {--pending-review-hours=48 : Reminder threshold for pending review}';

    protected $description = 'Queue report deadline reminders, pending review reminders, and reviewer summaries.';

    public function handle(SubmissionProgressEmailNotificationService $notifications): int
    {
        $days = collect(explode(',', (string) $this->option('deadline-days')))
            ->map(fn (string $day): int => (int) trim($day))
            ->filter(fn (int $day): bool => $day >= 0)
            ->unique()
            ->values()
            ->all();

        $deadline = $notifications->queueDeadlineReminders($days);
        $pending = $notifications->queuePendingReviewReminders(max(1, (int) $this->option('pending-review-hours')));
        $summaries = $notifications->queueReviewerSummaries();

        $this->info("Submission progress notifications queued: {$deadline} deadline reminders, {$pending} pending-review reminders, {$summaries} summaries.");

        return self::SUCCESS;
    }
}
