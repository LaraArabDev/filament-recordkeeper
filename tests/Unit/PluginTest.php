<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use Filament\Panel;
use LaraArabDev\RecordkeeperFilament\RecordkeeperPlugin;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditStatsOverview;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditTimeline;
use LaraArabDev\RecordkeeperFilament\Widgets\CommandMetricsWidget;
use PHPUnit\Framework\Attributes\Test;

class PluginTest extends TestCase
{
    #[Test]
    public function make_returns_plugin_instance(): void
    {
        $this->assertInstanceOf(RecordkeeperPlugin::class, RecordkeeperPlugin::make());
    }

    #[Test]
    public function id_is_recordkeeper(): void
    {
        $this->assertSame('recordkeeper', RecordkeeperPlugin::make()->getId());
    }

    #[Test]
    public function rollback_is_disabled_by_default(): void
    {
        $this->assertFalse(RecordkeeperPlugin::make()->isRollbackEnabled());
    }

    #[Test]
    public function enable_rollback_sets_flag_and_returns_static(): void
    {
        $plugin = RecordkeeperPlugin::make();

        $result = $plugin->enableRollback();

        $this->assertSame($plugin, $result);
        $this->assertTrue($plugin->isRollbackEnabled());
    }

    #[Test]
    public function enable_rollback_false_disables_it(): void
    {
        $plugin = RecordkeeperPlugin::make()->enableRollback();
        $plugin->enableRollback(false);

        $this->assertFalse($plugin->isRollbackEnabled());
    }

    #[Test]
    public function enable_timeline_returns_static(): void
    {
        $plugin = RecordkeeperPlugin::make();

        $this->assertSame($plugin, $plugin->enableTimeline());
    }

    #[Test]
    public function enable_stats_widget_returns_static(): void
    {
        $plugin = RecordkeeperPlugin::make();

        $this->assertSame($plugin, $plugin->enableStatsWidget());
    }

    #[Test]
    public function enable_command_metrics_returns_static(): void
    {
        $plugin = RecordkeeperPlugin::make();

        $this->assertSame($plugin, $plugin->enableCommandMetrics());
    }

    #[Test]
    public function enable_command_metrics_false_disables_it(): void
    {
        $plugin = RecordkeeperPlugin::make()->enableCommandMetrics();
        $result = $plugin->enableCommandMetrics(false);

        $this->assertSame($plugin, $result);
    }

    #[Test]
    public function navigation_group_defaults_to_audit(): void
    {
        $this->assertSame('Audit', RecordkeeperPlugin::make()->getNavigationGroup());
    }

    #[Test]
    public function navigation_group_returns_custom_value(): void
    {
        $plugin = RecordkeeperPlugin::make()->navigationGroup('System');

        $this->assertSame('System', $plugin->getNavigationGroup());
    }

    #[Test]
    public function navigation_sort_defaults_to_100(): void
    {
        $this->assertSame(100, RecordkeeperPlugin::make()->getNavigationSort());
    }

    #[Test]
    public function navigation_sort_returns_custom_value(): void
    {
        $plugin = RecordkeeperPlugin::make()->navigationSort(5);

        $this->assertSame(5, $plugin->getNavigationSort());
    }

    #[Test]
    public function navigation_icon_defaults_to_clock(): void
    {
        $this->assertSame('heroicon-o-clock', RecordkeeperPlugin::make()->getNavigationIcon());
    }

    #[Test]
    public function navigation_icon_returns_custom_value(): void
    {
        $plugin = RecordkeeperPlugin::make()->navigationIcon('heroicon-o-shield-check');

        $this->assertSame('heroicon-o-shield-check', $plugin->getNavigationIcon());
    }

    #[Test]
    public function register_always_registers_audit_resource(): void
    {
        $plugin = RecordkeeperPlugin::make();
        $panel = $this->createMock(Panel::class);

        $panel->expects($this->once())
            ->method('resources')
            ->with([AuditResource::class]);

        $panel->expects($this->never())
            ->method('widgets');

        $plugin->register($panel);
    }

    #[Test]
    public function register_includes_stats_widget_when_enabled(): void
    {
        $plugin = RecordkeeperPlugin::make()->enableStatsWidget();
        $panel = $this->createMock(Panel::class);

        $panel->method('resources')->willReturnSelf();

        $panel->expects($this->once())
            ->method('widgets')
            ->with($this->callback(function (array $widgets) {
                return in_array(AuditStatsOverview::class, $widgets, true);
            }));

        $plugin->register($panel);
    }

    #[Test]
    public function register_includes_timeline_widget_when_enabled(): void
    {
        $plugin = RecordkeeperPlugin::make()->enableTimeline();
        $panel = $this->createMock(Panel::class);

        $panel->method('resources')->willReturnSelf();

        $panel->expects($this->once())
            ->method('widgets')
            ->with($this->callback(function (array $widgets) {
                return in_array(AuditTimeline::class, $widgets, true);
            }));

        $plugin->register($panel);
    }

    #[Test]
    public function register_includes_command_metrics_widget_when_enabled(): void
    {
        $plugin = RecordkeeperPlugin::make()->enableCommandMetrics();
        $panel = $this->createMock(Panel::class);

        $panel->method('resources')->willReturnSelf();

        $panel->expects($this->once())
            ->method('widgets')
            ->with($this->callback(function (array $widgets) {
                return in_array(CommandMetricsWidget::class, $widgets, true);
            }));

        $plugin->register($panel);
    }

    #[Test]
    public function register_includes_all_widgets_when_all_enabled(): void
    {
        $plugin = RecordkeeperPlugin::make()
            ->enableStatsWidget()
            ->enableTimeline()
            ->enableCommandMetrics();

        $panel = $this->createMock(Panel::class);
        $panel->method('resources')->willReturnSelf();

        $panel->expects($this->once())
            ->method('widgets')
            ->with($this->callback(function (array $widgets) {
                return count($widgets) === 3
                    && in_array(AuditStatsOverview::class, $widgets, true)
                    && in_array(AuditTimeline::class, $widgets, true)
                    && in_array(CommandMetricsWidget::class, $widgets, true);
            }));

        $plugin->register($panel);
    }

    #[Test]
    public function register_does_not_call_widgets_when_none_enabled(): void
    {
        $plugin = RecordkeeperPlugin::make();
        $panel = $this->createMock(Panel::class);

        $panel->method('resources')->willReturnSelf();

        $panel->expects($this->never())
            ->method('widgets');

        $plugin->register($panel);
    }

    #[Test]
    public function fluent_api_allows_full_chaining(): void
    {
        $plugin = RecordkeeperPlugin::make()
            ->enableRollback()
            ->enableTimeline()
            ->enableStatsWidget()
            ->enableCommandMetrics()
            ->navigationGroup('Admin')
            ->navigationSort(10)
            ->navigationIcon('heroicon-o-eye');

        $this->assertInstanceOf(RecordkeeperPlugin::class, $plugin);
        $this->assertTrue($plugin->isRollbackEnabled());
        $this->assertSame('Admin', $plugin->getNavigationGroup());
        $this->assertSame(10, $plugin->getNavigationSort());
        $this->assertSame('heroicon-o-eye', $plugin->getNavigationIcon());
    }

    #[Test]
    public function get_resolves_plugin_from_panel(): void
    {
        $plugin = RecordkeeperPlugin::get();

        $this->assertInstanceOf(RecordkeeperPlugin::class, $plugin);
        $this->assertTrue($plugin->isRollbackEnabled());
    }

    #[Test]
    public function boot_accepts_panel_without_side_effects(): void
    {
        $plugin = RecordkeeperPlugin::make();
        $panel = $this->createMock(Panel::class);

        $plugin->boot($panel);

        $this->assertInstanceOf(RecordkeeperPlugin::class, $plugin);
    }
}
