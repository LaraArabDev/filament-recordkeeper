<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament;

use Illuminate\Support\ServiceProvider;

/** Service provider that bootstraps the Filament UI layer for Recordkeeper. */
class RecordkeeperFilamentServiceProvider extends ServiceProvider
{
    /**
     * Load package views and publish them when running in the console.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'recordkeeper');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/recordkeeper'),
            ], 'recordkeeper-filament-views');
        }
    }
}
