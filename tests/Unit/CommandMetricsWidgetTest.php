<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use Filament\Widgets\ChartWidget;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use LaraArabDev\RecordkeeperFilament\Widgets\CommandMetricsWidget;
use PHPUnit\Framework\Attributes\Test;

class CommandMetricsWidgetTest extends TestCase
{
    #[Test]
    public function extends_chart_widget(): void
    {
        $this->assertTrue(
            is_subclass_of(CommandMetricsWidget::class, ChartWidget::class)
        );
    }

    #[Test]
    public function heading_is_command_performance(): void
    {
        $reflection = new \ReflectionClass(CommandMetricsWidget::class);
        $property = $reflection->getProperty('heading');

        $this->assertSame('Command Performance', $property->getDefaultValue());
    }

    #[Test]
    public function sort_order_is_3(): void
    {
        $reflection = new \ReflectionClass(CommandMetricsWidget::class);
        $property = $reflection->getProperty('sort');

        $this->assertSame(3, $property->getDefaultValue());
    }

    #[Test]
    public function can_view_returns_true_when_memory_metrics_enabled(): void
    {
        config(['recordkeeper.commands.metrics.memory' => true]);
        config(['recordkeeper.commands.metrics.audit_count' => false]);

        $this->assertTrue(CommandMetricsWidget::canView());
    }

    #[Test]
    public function can_view_returns_true_when_audit_count_enabled(): void
    {
        config(['recordkeeper.commands.metrics.memory' => false]);
        config(['recordkeeper.commands.metrics.audit_count' => true]);

        $this->assertTrue(CommandMetricsWidget::canView());
    }

    #[Test]
    public function can_view_returns_false_when_both_disabled(): void
    {
        config(['recordkeeper.commands.metrics.memory' => false]);
        config(['recordkeeper.commands.metrics.audit_count' => false]);

        $this->assertFalse(CommandMetricsWidget::canView());
    }

    #[Test]
    public function get_type_returns_bar(): void
    {
        $widget = new CommandMetricsWidget;
        $method = new \ReflectionMethod($widget, 'getType');

        $this->assertSame('bar', $method->invoke($widget));
    }

    #[Test]
    public function get_filters_returns_null_when_no_command_audits(): void
    {
        $widget = new CommandMetricsWidget;
        $method = new \ReflectionMethod($widget, 'getFilters');

        $this->assertNull($method->invoke($widget));
    }

    #[Test]
    public function get_filters_returns_command_names_from_audits(): void
    {
        Audit::insert([
            ['event' => 'command.finished', 'auditable_type' => Audit::class, 'auditable_id' => null, 'source' => 'inspire', 'context' => json_encode(['command' => 'inspire']), 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'command.finished', 'auditable_type' => Audit::class, 'auditable_id' => null, 'source' => 'migrate', 'context' => json_encode(['command' => 'migrate']), 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'command.finished', 'auditable_type' => Audit::class, 'auditable_id' => null, 'source' => 'inspire', 'context' => json_encode(['command' => 'inspire']), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $widget = new CommandMetricsWidget;
        $method = new \ReflectionMethod($widget, 'getFilters');

        $filters = $method->invoke($widget);

        $this->assertIsArray($filters);
        $this->assertArrayHasKey('inspire', $filters);
        $this->assertArrayHasKey('migrate', $filters);
        $this->assertCount(2, $filters);
    }

    #[Test]
    public function get_data_returns_empty_datasets_when_no_filter(): void
    {
        $widget = new CommandMetricsWidget;
        $widget->filter = null;
        $method = new \ReflectionMethod($widget, 'getData');

        $data = $method->invoke($widget);

        $this->assertSame([], $data['datasets']);
        $this->assertSame([], $data['labels']);
    }

    #[Test]
    public function get_data_returns_run_data_for_filtered_command(): void
    {
        Audit::insert([
            [
                'event' => 'command.finished',
                'auditable_type' => Audit::class,
                'auditable_id' => null,
                'source' => 'inspire',
                'context' => json_encode(['command' => 'inspire', 'duration_ms' => 150, 'audit_count' => 3]),
                'created_at' => now()->subMinutes(2),
                'updated_at' => now()->subMinutes(2),
            ],
            [
                'event' => 'command.finished',
                'auditable_type' => Audit::class,
                'auditable_id' => null,
                'source' => 'inspire',
                'context' => json_encode(['command' => 'inspire', 'duration_ms' => 200, 'audit_count' => 5, 'anomaly' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $widget = new CommandMetricsWidget;
        $widget->filter = 'inspire';
        $method = new \ReflectionMethod($widget, 'getData');

        $data = $method->invoke($widget);

        $this->assertCount(2, $data['labels']);
        $this->assertCount(2, $data['datasets']);
        $this->assertSame('Duration (ms)', $data['datasets'][0]['label']);
        $this->assertSame('Audit Impact', $data['datasets'][1]['label']);
        $this->assertSame([150, 200], $data['datasets'][0]['data']);
        $this->assertSame([3, 5], $data['datasets'][1]['data']);
    }

    #[Test]
    public function anomaly_runs_get_red_bar_color(): void
    {
        Audit::insert([
            [
                'event' => 'command.finished',
                'auditable_type' => Audit::class,
                'auditable_id' => null,
                'source' => 'test',
                'context' => json_encode(['command' => 'test', 'duration_ms' => 100, 'audit_count' => 1]),
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'event' => 'command.finished',
                'auditable_type' => Audit::class,
                'auditable_id' => null,
                'source' => 'test',
                'context' => json_encode(['command' => 'test', 'duration_ms' => 999, 'audit_count' => 50, 'anomaly' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $widget = new CommandMetricsWidget;
        $widget->filter = 'test';
        $method = new \ReflectionMethod($widget, 'getData');

        $data = $method->invoke($widget);

        $colors = $data['datasets'][0]['backgroundColor'];
        $this->assertSame('rgba(99,102,241,0.7)', $colors[0]);
        $this->assertSame('rgba(239,68,68,0.7)', $colors[1]);
    }

    #[Test]
    public function get_options_returns_dual_y_axis_config(): void
    {
        $widget = new CommandMetricsWidget;
        $method = new \ReflectionMethod($widget, 'getOptions');

        $options = $method->invoke($widget);

        $this->assertArrayHasKey('scales', $options);
        $this->assertArrayHasKey('y', $options['scales']);
        $this->assertArrayHasKey('y1', $options['scales']);
        $this->assertSame('left', $options['scales']['y']['position']);
        $this->assertSame('right', $options['scales']['y1']['position']);
    }
}
