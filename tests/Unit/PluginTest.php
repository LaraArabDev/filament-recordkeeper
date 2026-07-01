<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentRecordkeeper\Tests\Unit;

use LaraArabDev\FilamentRecordkeeper\RecordkeeperPlugin;
use LaraArabDev\FilamentRecordkeeper\Tests\TestCase;
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
    public function navigation_group_returns_static(): void
    {
        $plugin = RecordkeeperPlugin::make();

        $this->assertSame($plugin, $plugin->navigationGroup('System'));
    }

    #[Test]
    public function cluster_returns_static(): void
    {
        $plugin = RecordkeeperPlugin::make();

        $this->assertSame($plugin, $plugin->cluster('MyCluster'));
    }

    #[Test]
    public function polling_interval_returns_static(): void
    {
        $plugin = RecordkeeperPlugin::make();

        $this->assertSame($plugin, $plugin->pollingInterval('5s'));
    }
}
