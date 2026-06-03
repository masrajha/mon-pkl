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
