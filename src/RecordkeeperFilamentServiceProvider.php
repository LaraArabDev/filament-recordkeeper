<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament;

use Illuminate\Support\ServiceProvider;
use LaraArabDev\Recordkeeper\RecordkeeperServiceProvider as CoreServiceProvider;

/** Service provider that bootstraps the Filament UI layer for Recordkeeper. */
class RecordkeeperFilamentServiceProvider extends ServiceProvider
{
    /**
     * Register the core Recordkeeper service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(CoreServiceProvider::class);
    }

    /**
     * Load package views and publish them when running in the console.
     *
     * @return void
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
