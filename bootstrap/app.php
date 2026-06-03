<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\ImportFirebaseJson::class,
        \App\Console\Commands\ProcessEmailNotifications::class,
        \App\Console\Commands\QueuePendingEnrollmentReminders::class,
        \App\Console\Commands\QueuePendingPlaceProposalReminders::class,
        \App\Console\Commands\QueuePendingSupervisorChangeReminders::class,
        \App\Console\Commands\QueuePendingRelocationReminders::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
