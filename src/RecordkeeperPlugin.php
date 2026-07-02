<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditStatsOverview;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditTimeline;

/** Filament plugin that registers the AuditResource and optional widgets on a panel. */
class RecordkeeperPlugin implements Plugin
{
    /** @var bool Whether the rollback action is exposed in the UI. */
    private bool $rollbackEnabled = false;

    /** @var bool Whether the AuditTimeline widget is registered on the panel. */
    private bool $timelineEnabled = false;

    /** @var bool Whether the AuditStatsOverview widget is registered on the panel. */
    private bool $statsEnabled = false;

    /** @var string Navigation group label used for the audit resource. */
    private string $navigationGroup = 'Audit';

    /** @var string|null Fully-qualified cluster class to assign the resource to, or null for none. */
    private ?string $cluster = null;

    /** @var string|null Livewire polling interval (e.g. "30s"), or null to disable polling. */
    private ?string $pollingInterval = null;

    /**
     * Create a new plugin instance via the service container.
     *
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Return the unique identifier for this plugin.
     *
     * @return string
     */
    public function getId(): string
    {
        return 'recordkeeper';
    }

    /**
     * Register the audit resource and any enabled widgets on the panel.
     *
     * @param  Panel  $panel
     * @return void
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

        if (! empty($widgets)) {
            $panel->widgets($widgets);
        }
    }

    /**
     * Publish plugin configuration values into the application config at boot time.
     *
     * @param  Panel  $panel
     * @return void
     */
    public function boot(Panel $panel): void
    {
        config([
            'recordkeeper.filament.rollback_enabled' => $this->rollbackEnabled,
            'recordkeeper.filament.navigation_group' => $this->navigationGroup,
            'recordkeeper.filament.cluster' => $this->cluster,
            'recordkeeper.filament.polling_interval' => $this->pollingInterval,
        ]);
    }

    /**
     * Enable or disable the rollback action in the audit UI.
     *
     * @param  bool  $enabled
     * @return static
     */
    public function enableRollback(bool $enabled = true): static
    {
        $this->rollbackEnabled = $enabled;

        return $this;
    }

    /**
     * Enable or disable the AuditTimeline dashboard widget.
     *
     * @param  bool  $enabled
     * @return static
     */
    public function enableTimeline(bool $enabled = true): static
    {
        $this->timelineEnabled = $enabled;

        return $this;
    }

    /**
     * Enable or disable the AuditStatsOverview dashboard widget.
     *
     * @param  bool  $enabled
     * @return static
     */
    public function enableStatsWidget(bool $enabled = true): static
    {
        $this->statsEnabled = $enabled;

        return $this;
    }

    /**
     * Set the navigation group label for the audit resource.
     *
     * @param  string  $group
     * @return static
     */
    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /**
     * Assign the audit resource to a Filament cluster.
     *
     * @param  string  $cluster  Fully-qualified cluster class name.
     * @return static
     */
    public function cluster(string $cluster): static
    {
        $this->cluster = $cluster;

        return $this;
    }

    /**
     * Set the Livewire polling interval for widgets (e.g. "30s").
     *
     * @param  string  $interval
     * @return static
     */
    public function pollingInterval(string $interval): static
    {
        $this->pollingInterval = $interval;

        return $this;
    }

    /**
     * Return whether the rollback action is currently enabled.
     *
     * @return bool
     */
    public function isRollbackEnabled(): bool
    {
        return $this->rollbackEnabled;
    }
}
