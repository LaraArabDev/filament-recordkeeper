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

    public function getId(): string
    {
        return 'recordkeeper';
    }

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

    public function boot(Panel $panel): void
    {
        //
    }

    public function enableRollback(bool $enabled = true): static
    {
        $this->rollbackEnabled = $enabled;

        return $this;
    }

    public function enableTimeline(bool $enabled = true): static
    {
        $this->timelineEnabled = $enabled;

        return $this;
    }

    public function enableStatsWidget(bool $enabled = true): static
    {
        $this->statsEnabled = $enabled;

        return $this;
    }

    public function enableCommandMetrics(bool $enabled = true): static
    {
        $this->commandMetricsEnabled = $enabled;

        return $this;
    }

    public function navigationGroup(string|UnitEnum $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function navigationIcon(string|BackedEnum|Htmlable $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function isRollbackEnabled(): bool
    {
        return $this->rollbackEnabled;
    }

    public function getNavigationGroup(): string|UnitEnum
    {
        return $this->navigationGroup;
    }

    public function getNavigationSort(): int
    {
        return $this->navigationSort;
    }

    public function getNavigationIcon(): string|BackedEnum|Htmlable
    {
        return $this->navigationIcon;
    }
}
