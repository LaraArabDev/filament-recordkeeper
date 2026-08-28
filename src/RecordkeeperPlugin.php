<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament;

use BackedEnum;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditStatsOverview;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditTimeline;
use LaraArabDev\RecordkeeperFilament\Widgets\CommandMetricsWidget;
use UnitEnum;

/**
 * Filament plugin that registers the audit resource, widgets, and rollback controls.
 */
class RecordkeeperPlugin implements Plugin
{
    private bool $rollbackEnabled = false;

    private bool $timelineEnabled = false;

    private bool $statsEnabled = false;

    private bool $commandMetricsEnabled = false;

    private string|UnitEnum $navigationGroup = 'Audit';

    private int $navigationSort = 100;

    private string|BackedEnum|Htmlable $navigationIcon = 'heroicon-o-clock';

    /**
     * Create a new plugin instance from the container.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Resolve the registered plugin instance from the current Filament panel.
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * Get the unique plugin identifier.
     */
    public function getId(): string
    {
        return 'recordkeeper';
    }

    /**
     * Register the audit resource and enabled widgets with the panel.
     *
     * @param  Panel  $panel  The Filament panel being configured.
     */
    public function register(Panel $panel): void
    {
        $panel->resources([AuditResource::class]);

        $widgets = [];
        if ($this->statsEnabled) {
            $widgets[] = AuditStatsOverview::class;
        }
        if ($this->timelineEnabled) {
            $widgets[] = AuditTimeline::class;
        }
        if ($this->commandMetricsEnabled) {
            $widgets[] = CommandMetricsWidget::class;
        }

        if (! empty($widgets)) {
            $panel->widgets($widgets);
        }
    }

    /**
     * Boot the plugin after registration.
     *
     * @param  Panel  $panel  The Filament panel being booted.
     */
    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Enable or disable the rollback feature in the UI.
     *
     * @param  bool  $enabled  Whether rollback actions should be visible.
     */
    public function enableRollback(bool $enabled = true): static
    {
        $this->rollbackEnabled = $enabled;

        return $this;
    }

    /**
     * Enable or disable the audit timeline dashboard widget.
     *
     * @param  bool  $enabled  Whether to register the timeline widget.
     */
    public function enableTimeline(bool $enabled = true): static
    {
        $this->timelineEnabled = $enabled;

        return $this;
    }

    /**
     * Enable or disable the stats overview dashboard widget.
     *
     * @param  bool  $enabled  Whether to register the stats widget.
     */
    public function enableStatsWidget(bool $enabled = true): static
    {
        $this->statsEnabled = $enabled;

        return $this;
    }

    /**
     * Enable or disable the command metrics dashboard widget.
     *
     * @param  bool  $enabled  Whether to register the command metrics widget.
     */
    public function enableCommandMetrics(bool $enabled = true): static
    {
        $this->commandMetricsEnabled = $enabled;

        return $this;
    }

    /**
     * Set the navigation group for the audit resource.
     *
     * @param  string|UnitEnum  $group  The navigation group name or enum.
     */
    public function navigationGroup(string|UnitEnum $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /**
     * Set the navigation sort order for the audit resource.
     *
     * @param  int  $sort  The sort position.
     */
    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    /**
     * Set the navigation icon for the audit resource.
     *
     * @param  string|BackedEnum|Htmlable  $icon  A Heroicon identifier, enum, or Htmlable.
     */
    public function navigationIcon(string|BackedEnum|Htmlable $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    /**
     * Check whether rollback is enabled on this plugin instance.
     */
    public function isRollbackEnabled(): bool
    {
        return $this->rollbackEnabled;
    }

    /**
     * Get the configured navigation group.
     */
    public function getNavigationGroup(): string|UnitEnum
    {
        return $this->navigationGroup;
    }

    /**
     * Get the configured navigation sort order.
     */
    public function getNavigationSort(): int
    {
        return $this->navigationSort;
    }

    /**
     * Get the configured navigation icon.
     */
    public function getNavigationIcon(): string|BackedEnum|Htmlable
    {
        return $this->navigationIcon;
    }
}
