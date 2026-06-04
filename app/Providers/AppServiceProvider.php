<?php

namespace App\Providers;

use App\Services\ActionRequiredSummaryService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.navigation', function ($view): void {
            $view->with('actionRequiredSummary', app(ActionRequiredSummaryService::class)->forUser(auth()->user()));
        });
    }
}
