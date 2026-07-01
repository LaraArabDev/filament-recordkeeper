<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament;

use Illuminate\Support\ServiceProvider;
use LaraArabDev\Recordkeeper\RecordkeeperServiceProvider as CoreServiceProvider;

class RecordkeeperFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(CoreServiceProvider::class);
    }

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
