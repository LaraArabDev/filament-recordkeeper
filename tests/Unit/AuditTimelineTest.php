<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use Filament\Widgets\TableWidget;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditTimeline;
use PHPUnit\Framework\Attributes\Test;

class AuditTimelineTest extends TestCase
{
    #[Test]
    public function sort_order_is_99(): void
    {
        $reflection = new \ReflectionClass(AuditTimeline::class);
        $property = $reflection->getProperty('sort');

        $this->assertSame(99, $property->getDefaultValue());
    }

    #[Test]
    public function column_span_defaults_to_full(): void
    {
        $reflection = new \ReflectionClass(AuditTimeline::class);
        $property = $reflection->getProperty('columnSpan');

        $this->assertSame('full', $property->getDefaultValue());
    }

    #[Test]
    public function extends_table_widget(): void
    {
        $this->assertTrue(
            is_subclass_of(AuditTimeline::class, TableWidget::class)
        );
    }
}
