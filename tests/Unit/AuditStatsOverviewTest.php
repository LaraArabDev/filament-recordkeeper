<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditStatsOverview;
use PHPUnit\Framework\Attributes\Test;

class AuditStatsOverviewTest extends TestCase
{
    #[Test]
    public function extends_stats_overview_widget(): void
    {
        $this->assertTrue(
            is_subclass_of(AuditStatsOverview::class, StatsOverviewWidget::class)
        );
    }

    #[Test]
    public function get_stats_returns_six_stats(): void
    {
        $widget = new AuditStatsOverview;
        $stats = $this->invokeMethod($widget, 'getStats');

        $this->assertCount(6, $stats);
        $this->assertContainsOnlyInstancesOf(Stat::class, $stats);
    }

    #[Test]
    public function stats_return_zero_counts_with_empty_database(): void
    {
        $widget = new AuditStatsOverview;
        $stats = $this->invokeMethod($widget, 'getStats');

        $labels = array_map(fn (Stat $s) => $s->getLabel(), $stats);

        $this->assertContains('Total Audits', $labels);
        $this->assertContains('Created', $labels);
        $this->assertContains('Updated', $labels);
        $this->assertContains('Deleted', $labels);
        $this->assertContains('Route Hits', $labels);
        $this->assertContains('Distinct Actors', $labels);
    }

    #[Test]
    public function stats_reflect_seeded_data(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 1, 'user_id' => 1, 'user_type' => 'App\\Models\\User', 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'created', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 2, 'user_id' => 1, 'user_type' => 'App\\Models\\User', 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'updated', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 1, 'user_id' => 2, 'user_type' => 'App\\Models\\User', 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'deleted', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 3, 'user_id' => null, 'user_type' => null, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'route.visited', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => null, 'user_id' => 3, 'user_type' => 'App\\Models\\User', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $widget = new AuditStatsOverview;
        $stats = $this->invokeMethod($widget, 'getStats');

        $map = [];
        foreach ($stats as $stat) {
            $map[$stat->getLabel()] = $stat->getValue();
        }

        $this->assertSame('5', $map['Total Audits']);
        $this->assertSame('2', $map['Created']);
        $this->assertSame('1', $map['Updated']);
        $this->assertSame('1', $map['Deleted']);
        $this->assertSame('1', $map['Route Hits']);
        $this->assertSame('3', $map['Distinct Actors']);
    }

    private function invokeMethod(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);

        return $reflection->invoke($object, ...$args);
    }
}
