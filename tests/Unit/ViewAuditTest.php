<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use Filament\Resources\Pages\ViewRecord;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ViewAudit;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ViewAuditTest extends TestCase
{
    #[Test]
    public function resource_is_audit_resource(): void
    {
        $reflection = new \ReflectionClass(ViewAudit::class);
        $property = $reflection->getProperty('resource');

        $this->assertSame(AuditResource::class, $property->getDefaultValue());
    }

    #[Test]
    public function extends_view_record(): void
    {
        $this->assertTrue(
            is_subclass_of(ViewAudit::class, ViewRecord::class)
        );
    }
}
