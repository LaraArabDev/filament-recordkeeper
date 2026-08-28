<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ListAudits;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ListAuditsTest extends TestCase
{
    #[Test]
    public function resource_is_audit_resource(): void
    {
        $reflection = new \ReflectionClass(ListAudits::class);
        $property = $reflection->getProperty('resource');

        $this->assertSame(AuditResource::class, $property->getDefaultValue());
    }

    #[Test]
    public function get_tabs_returns_all_expected_tabs(): void
    {
        $page = new ListAudits;
        $tabs = $page->getTabs();

        $this->assertArrayHasKey('all', $tabs);
        $this->assertArrayHasKey('created', $tabs);
        $this->assertArrayHasKey('updated', $tabs);
        $this->assertArrayHasKey('deleted', $tabs);
        $this->assertArrayHasKey('routes', $tabs);
        $this->assertCount(5, $tabs);
    }

    #[Test]
    public function tabs_show_correct_badge_counts_with_empty_database(): void
    {
        $page = new ListAudits;
        $tabs = $page->getTabs();

        $this->assertEquals(0, $tabs['all']->getBadge());
        $this->assertEquals(0, $tabs['created']->getBadge());
        $this->assertEquals(0, $tabs['updated']->getBadge());
        $this->assertEquals(0, $tabs['deleted']->getBadge());
        $this->assertEquals(0, $tabs['routes']->getBadge());
    }

    #[Test]
    public function tabs_show_correct_badge_counts_with_seeded_data(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'created', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'updated', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'deleted', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'forceDeleted', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'route.visited', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'route.api.called', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $page = new ListAudits;
        $tabs = $page->getTabs();

        $this->assertEquals(7, $tabs['all']->getBadge());
        $this->assertEquals(2, $tabs['created']->getBadge());
        $this->assertEquals(1, $tabs['updated']->getBadge());
        $this->assertEquals(2, $tabs['deleted']->getBadge());
        $this->assertEquals(2, $tabs['routes']->getBadge());
    }
}
