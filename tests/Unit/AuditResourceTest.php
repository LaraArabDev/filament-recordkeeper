<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ListAudits;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ViewAudit;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditResourceTest extends TestCase
{
    #[Test]
    public function model_is_audit(): void
    {
        $reflection = new \ReflectionClass(AuditResource::class);
        $property = $reflection->getProperty('model');

        $this->assertSame(Audit::class, $property->getDefaultValue());
    }

    #[Test]
    public function slug_is_audits(): void
    {
        $reflection = new \ReflectionClass(AuditResource::class);
        $property = $reflection->getProperty('slug');

        $this->assertSame('audits', $property->getDefaultValue());
    }

    #[Test]
    public function navigation_group_delegates_to_plugin(): void
    {
        $this->assertSame('Audit', AuditResource::getNavigationGroup());
    }

    #[Test]
    public function navigation_sort_delegates_to_plugin(): void
    {
        $this->assertSame(100, AuditResource::getNavigationSort());
    }

    #[Test]
    public function navigation_icon_delegates_to_plugin(): void
    {
        $this->assertSame('heroicon-o-clock', AuditResource::getNavigationIcon());
    }

    #[Test]
    public function can_create_returns_false(): void
    {
        $this->assertFalse(AuditResource::canCreate());
    }

    #[Test]
    public function get_pages_includes_index_and_view(): void
    {
        $pages = AuditResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('view', $pages);
        $this->assertCount(2, $pages);
    }

    #[Test]
    public function index_page_uses_list_audits(): void
    {
        $pages = AuditResource::getPages();

        $this->assertSame(ListAudits::class, $pages['index']->getPage());
    }

    #[Test]
    public function view_page_uses_view_audit(): void
    {
        $pages = AuditResource::getPages();

        $this->assertSame(ViewAudit::class, $pages['view']->getPage());
    }

    #[Test]
    public function globally_searchable_attributes_are_defined(): void
    {
        $attrs = AuditResource::getGloballySearchableAttributes();

        $this->assertContains('event', $attrs);
        $this->assertContains('auditable_type', $attrs);
        $this->assertContains('batch_id', $attrs);
    }
}
