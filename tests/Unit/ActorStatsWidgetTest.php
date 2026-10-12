<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use LaraArabDev\RecordkeeperFilament\Widgets\ActorStatsWidget;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

class ActorStatsWidgetTest extends TestCase
{
    #[Test]
    public function actor_stats_returns_zeros_with_empty_database(): void
    {
        $stats = $this->callActorStats();

        $this->assertSame(0, $stats['total_actors']);
        $this->assertSame('—', $stats['most_active_id']);
        $this->assertSame(0, $stats['most_active_count']);
        $this->assertSame(0, $stats['today_actors']);
        $this->assertSame(0, $stats['system_actions']);
    }

    #[Test]
    public function actor_stats_reflects_seeded_data(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => 'App\\Models\\Order', 'auditable_id' => 1, 'user_id' => 1, 'user_type' => 'App\\Models\\User', 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'updated', 'auditable_type' => 'App\\Models\\Order', 'auditable_id' => 1, 'user_id' => 1, 'user_type' => 'App\\Models\\User', 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'created', 'auditable_type' => 'App\\Models\\Order', 'auditable_id' => 2, 'user_id' => 2, 'user_type' => 'App\\Models\\Admin', 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'route.visited', 'auditable_type' => 'App\\Models\\Order', 'auditable_id' => null, 'user_id' => null, 'user_type' => null, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'deleted', 'auditable_type' => 'App\\Models\\Order', 'auditable_id' => 3, 'user_id' => null, 'user_type' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $stats = $this->callActorStats();

        $this->assertSame(2, $stats['total_actors']);
        $this->assertSame('User #1', $stats['most_active_id']);
        $this->assertSame(2, $stats['most_active_count']);
        $this->assertSame(2, $stats['today_actors']);
        $this->assertSame(2, $stats['system_actions']);
    }

    #[Test]
    public function actor_stats_today_actors_excludes_yesterday(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => 'App\\Models\\Order', 'auditable_id' => 1, 'user_id' => 1, 'user_type' => 'App\\Models\\User', 'created_at' => now()->subDay(), 'updated_at' => now()],
            ['event' => 'created', 'auditable_type' => 'App\\Models\\Order', 'auditable_id' => 2, 'user_id' => 2, 'user_type' => 'App\\Models\\User', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $stats = $this->callActorStats();

        $this->assertSame(2, $stats['total_actors']);
        $this->assertSame(1, $stats['today_actors']);
    }

    #[Test]
    public function get_stats_returns_four_stat_instances(): void
    {
        $widget = new ActorStatsWidget;
        $method = new ReflectionMethod($widget, 'getStats');
        $stats = $method->invoke($widget);

        $this->assertCount(4, $stats);
    }

    /**
     * @return array{total_actors: int, most_active_id: string, most_active_count: int, today_actors: int, system_actions: int}
     */
    private function callActorStats(): array
    {
        $method = new ReflectionMethod(ActorStatsWidget::class, 'actorStats');

        return $method->invoke(null);
    }
}
