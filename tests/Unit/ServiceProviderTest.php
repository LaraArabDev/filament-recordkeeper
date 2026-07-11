<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use LaraArabDev\Recordkeeper\Recordkeeper;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function rollback_preview_view_is_registered(): void
    {
        $this->assertTrue(
            app('view')->exists('recordkeeper::rollback-preview')
        );
    }

    #[Test]
    public function core_recordkeeper_singleton_is_bound(): void
    {
        $this->assertTrue(app()->bound(Recordkeeper::class));
    }

    #[Test]
    public function views_namespace_resolves_correctly(): void
    {
        $hints = app('view')->getFinder()->getHints();

        $this->assertArrayHasKey('recordkeeper', $hints);
    }
}
