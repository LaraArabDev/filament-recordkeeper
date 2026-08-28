<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests;

use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\RecordkeeperServiceProvider;
use LaraArabDev\RecordkeeperFilament\RecordkeeperFilamentServiceProvider;
use LaraArabDev\RecordkeeperFilament\RecordkeeperPlugin;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use OwenIt\Auditing\AuditingServiceProvider;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->setUpPanel();
        AuditFormatter::resetEventCountsCache();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            TablesServiceProvider::class,
            InfolistsServiceProvider::class,
            ActionsServiceProvider::class,
            NotificationsServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            AuditingServiceProvider::class,
            RecordkeeperServiceProvider::class,
            RecordkeeperFilamentServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('audit.implementation', Audit::class);
        $app['config']->set('audit.console', true);
    }

    protected function setUpPanel(): void
    {
        $panel = Panel::make()
            ->id('test')
            ->plugin(
                RecordkeeperPlugin::make()
                    ->enableRollback()
            );

        Filament::registerPanel($panel);
        Filament::setCurrentPanel($panel);
    }

    private function setUpDatabase(): void
    {
        Schema::create('audits', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->index(['user_type', 'user_id']);
            $table->string('event');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->index(['auditable_type', 'auditable_id']);
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->string('guard', 30)->nullable()->index();
            $table->string('batch_id', 100)->nullable();
            $table->json('context')->nullable();
            $table->string('source', 255)->nullable()->index();
            $table->index('event');
            $table->index('created_at');
            $table->index('deleted_at');
            $table->index('user_id');
            $table->index(['batch_id', 'event']);
            $table->index(['event', 'created_at']);
            $table->index(['auditable_type', 'auditable_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id', 'event'], 'audits_auditable_event_index');
        });

        Schema::create('audit_http_requests', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('audit_id')->nullable()->index();
            $table->string('method', 10)->index();
            $table->text('url');
            $table->string('host', 255)->nullable()->index();
            $table->integer('status_code')->nullable()->index();
            $table->integer('duration_ms')->nullable();
            $table->boolean('failed')->default(false)->index();
            $table->text('exception')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('response_headers')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('audit_tag', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('audit_id');
            $table->foreign('audit_id')->references('id')->on('audits')->cascadeOnDelete();
            $table->string('tag', 100)->index();
            $table->index(['audit_id', 'tag']);
        });
    }
}
