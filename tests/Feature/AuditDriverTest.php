<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Feature;

use Illuminate\Support\Facades\Log;
use LaraArabDev\Recordkeeper\Actions\RecordAudit;
use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;
use LaraArabDev\Recordkeeper\Drivers\DatabaseDriver;
use LaraArabDev\Recordkeeper\Drivers\LogDriver;
use LaraArabDev\Recordkeeper\Drivers\NullDriver;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Support\AuditDriverManager;
use LaraArabDev\Recordkeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

class AuditDriverTest extends TestCase
{
    private function payload(string $event = 'created'): AuditPayload
    {
        return new AuditPayload(
            event: $event,
            auditableType: 'system',
            auditableId: null,
            oldValues: [],
            newValues: ['key' => 'value'],
        );
    }

    #[Test]
    public function database_driver_persists_to_db(): void
    {
        $driver = new DatabaseDriver;
        $audit = $driver->persist($this->payload());

        $this->assertDatabaseHas('audits', ['event' => 'created']);
        $this->assertNotNull($audit->id);
    }

    #[Test]
    public function database_driver_find_returns_audit(): void
    {
        $driver = new DatabaseDriver;
        $created = $driver->persist($this->payload());

        $found = $driver->find($created->id);

        $this->assertNotNull($found);
        $this->assertSame((string) $created->id, (string) $found->id);
    }

    #[Test]
    public function database_driver_find_returns_null_for_missing(): void
    {
        $driver = new DatabaseDriver;

        $this->assertNull($driver->find(99999));
    }

    #[Test]
    public function database_driver_flush_deletes_all(): void
    {
        $driver = new DatabaseDriver;
        $driver->persist($this->payload());
        $driver->persist($this->payload('updated'));

        $driver->flush();

        $this->assertSame(0, Audit::count());
    }

    #[Test]
    public function null_driver_does_not_write_to_db(): void
    {
        $driver = new NullDriver;
        $audit = $driver->persist($this->payload());

        $this->assertSame(0, Audit::count());
        $this->assertSame('created', $audit->event);
    }

    #[Test]
    public function null_driver_find_always_returns_null(): void
    {
        $driver = new NullDriver;

        $this->assertNull($driver->find(1));
    }

    #[Test]
    public function null_driver_flush_is_noop(): void
    {
        $driver = new NullDriver;
        $driver->flush();

        $this->assertTrue(true);
    }

    #[Test]
    public function log_driver_writes_to_log_channel(): void
    {
        $logChannel = \Mockery::mock(LoggerInterface::class);
        $logChannel->shouldReceive('info')->once();

        Log::shouldReceive('channel')->once()->with('stack')->andReturn($logChannel);

        $driver = new LogDriver('stack', 'info');
        $audit = $driver->persist($this->payload('order.placed'));

        $this->assertSame(0, Audit::count());
        $this->assertSame('order.placed', $audit->event);
    }

    #[Test]
    public function log_driver_find_always_returns_null(): void
    {
        $driver = new LogDriver;

        $this->assertNull($driver->find(1));
    }

    #[Test]
    public function record_audit_action_uses_configured_driver(): void
    {
        config(['recordkeeper.driver' => 'database']);

        $action = app(RecordAudit::class);
        $audit = $action($this->payload('system.boot'));

        $this->assertDatabaseHas('audits', ['event' => 'system.boot']);
        $this->assertNotNull($audit->id);
    }

    #[Test]
    public function switching_to_null_driver_skips_db_write(): void
    {
        config(['recordkeeper.driver' => 'null']);

        $manager = app(AuditDriverManager::class);
        $manager->forgetDrivers();

        $audit = app(RecordAudit::class)($this->payload('test.event'));

        $this->assertSame(0, Audit::count());
        $this->assertSame('test.event', $audit->event);
    }

    #[Test]
    public function driver_manager_resolves_database_driver(): void
    {
        $manager = app(AuditDriverManager::class);

        $this->assertInstanceOf(DatabaseDriver::class, $manager->driver('database'));
    }

    #[Test]
    public function driver_manager_resolves_null_driver(): void
    {
        $manager = app(AuditDriverManager::class);

        $this->assertInstanceOf(NullDriver::class, $manager->driver('null'));
    }

    #[Test]
    public function driver_manager_resolves_log_driver(): void
    {
        $manager = app(AuditDriverManager::class);

        $this->assertInstanceOf(LogDriver::class, $manager->driver('log'));
    }

    #[Test]
    public function driver_manager_supports_custom_extension(): void
    {
        $manager = app(AuditDriverManager::class);
        $manager->extend('custom', fn () => new NullDriver);

        $this->assertInstanceOf(NullDriver::class, $manager->driver('custom'));
    }

    #[Test]
    public function audit_payload_includes_guard_field(): void
    {
        $payload = new AuditPayload(
            event: 'route.get',
            auditableType: 'route',
            auditableId: null,
            oldValues: [],
            newValues: [],
            guard: 'web',
        );

        $driver = new DatabaseDriver;
        $audit = $driver->persist($payload);

        $this->assertSame('web', $audit->fresh()->guard);
    }
}
