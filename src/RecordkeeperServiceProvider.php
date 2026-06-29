<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use LaraArabDev\Recordkeeper\Actions\RecordAudit;
use LaraArabDev\Recordkeeper\Actions\RevertAudit;
use LaraArabDev\Recordkeeper\Actions\RevertBatch;
use LaraArabDev\Recordkeeper\Console\InstallCommand;
use LaraArabDev\Recordkeeper\Console\PruneCommand;
use LaraArabDev\Recordkeeper\Console\RollbackCommand;
use LaraArabDev\Recordkeeper\Console\SearchCommand;
use LaraArabDev\Recordkeeper\Console\ShowCommand;
use LaraArabDev\Recordkeeper\Console\StatsCommand;
use LaraArabDev\Recordkeeper\Console\SyncCommand;
use LaraArabDev\Recordkeeper\Console\TailCommand;
use LaraArabDev\Recordkeeper\Contracts\AuditQueryContract;
use LaraArabDev\Recordkeeper\Contracts\Rollbacker;
use LaraArabDev\Recordkeeper\Http\Middleware\AuditApi;
use LaraArabDev\Recordkeeper\Http\Middleware\AuditRoute;
use LaraArabDev\Recordkeeper\Listeners\RecordCommandAudit;
use LaraArabDev\Recordkeeper\Listeners\RecordEventAudit;
use LaraArabDev\Recordkeeper\Listeners\RecordJobAudit;
use LaraArabDev\Recordkeeper\Listeners\RecordOutboundHttp;
use LaraArabDev\Recordkeeper\Support\AuditQuery;
use LaraArabDev\Recordkeeper\Support\HttpTracker;
use LaraArabDev\Recordkeeper\Support\Rollback;

class RecordkeeperServiceProvider extends ServiceProvider
{
    /** @return void */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/recordkeeper.php', 'recordkeeper');

        $this->app->singleton(Recordkeeper::class);
        $this->app->singleton(HttpTracker::class);

        $this->app->bind(Rollbacker::class, Rollback::class);
        $this->app->bind(AuditQueryContract::class, AuditQuery::class);

        $this->app->bind(RecordAudit::class);
        $this->app->bind(RevertAudit::class);
        $this->app->bind(RevertBatch::class);
    }

    /** @return void */
    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerMiddlewareAliases();
        $this->registerAuditModel();
        $this->registerAuditListeners();

        if ($this->app->runningInConsole()) {
            $this->offerPublishing();
            $this->registerCommands();
        }
    }

    /** @return void */
    private function offerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/recordkeeper.php' => config_path('recordkeeper.php'),
        ], 'recordkeeper-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'recordkeeper-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/recordkeeper'),
        ], 'recordkeeper-views');
    }

    /** @return void */
    private function registerCommands(): void
    {
        $this->commands([
            InstallCommand::class,
            SyncCommand::class,
            SearchCommand::class,
            ShowCommand::class,
            TailCommand::class,
            StatsCommand::class,
            PruneCommand::class,
            RollbackCommand::class,
        ]);
    }

    /** @return void */
    private function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /** @return void */
    private function registerMiddlewareAliases(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('audit', AuditRoute::class);
        $router->aliasMiddleware('audit.api', AuditApi::class);
    }

    /** @return void */
    private function registerAuditModel(): void
    {
        $this->app['config']->set(
            'audit.implementation',
            \LaraArabDev\Recordkeeper\Models\Audit::class
        );

        Relation::morphMap([
            'route'   => \LaraArabDev\Recordkeeper\Models\Audit::class,
            'system'  => \LaraArabDev\Recordkeeper\Models\Audit::class,
            'job'     => \LaraArabDev\Recordkeeper\Models\Audit::class,
            'command' => \LaraArabDev\Recordkeeper\Models\Audit::class,
            'event'   => \LaraArabDev\Recordkeeper\Models\Audit::class,
        ]);
    }

    /** @return void */
    private function registerAuditListeners(): void
    {
        $this->app['events']->subscribe(RecordJobAudit::class);

        if (config('recordkeeper.commands.enabled', false)) {
            $this->app['events']->subscribe(RecordCommandAudit::class);
        }

        $this->app['events']->listen('*', [RecordEventAudit::class, 'handle']);

        if (config('recordkeeper.http.enabled', false)) {
            $this->app['events']->subscribe(RecordOutboundHttp::class);
        }
    }
}
