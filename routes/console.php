<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('silat:email-notifications:process --limit=100')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('silat:enrollment-notifications:queue-pending-reminders --days=3')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('silat:place-proposal-notifications:queue-pending-reminders --hours=48')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('silat:supervisor-change-notifications:queue-pending-reminders --hours=48')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('silat:relocation-notifications:queue-pending-reminders --hours=48')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('silat:orientation-notifications:queue --hours-before=24 --closing-minutes=60 --summary-hours=24')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('silat:attendance-digests:queue')
    ->weeklyOn(1, '07:00')
    ->withoutOverlapping();

Schedule::command('silat:submission-progress-notifications:queue --deadline-days=7,3,1,0 --pending-review-hours=48')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('silat:field-supervisor-notifications:queue --minimum-pending-days=1 --full-report-days=7,3,1')
    ->dailyAt('07:30')
    ->withoutOverlapping();

Schedule::command('silat:assessment-notifications:queue --days-before-period-end=7')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('silat:operational-notifications:queue')
    ->hourly()
    ->withoutOverlapping();
