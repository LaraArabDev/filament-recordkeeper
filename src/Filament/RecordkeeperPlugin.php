<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use LaraArabDev\Recordkeeper\Filament\Resources\AuditResource;
use LaraArabDev\Recordkeeper\Filament\Widgets\AuditStatsOverview;
use LaraArabDev\Recordkeeper\Filament\Widgets\AuditTimeline;

class RecordkeeperPlugin implements Plugin
{
    /** @var bool */
    private bool $rollbackEnabled   = false;

    /** @var bool */
    private bool $timelineEnabled   = false;

    /** @var bool */
    private bool $statsEnabled      = false;

    /** @var string */
    private string $navigationGroup = 'Audit';

    /** @var ?string */
    private ?string $cluster        = null;

    /** @var ?string */
    private ?string $pollingInterval = null;

    /** @return static */
    public static function make(): static
    {
        return app(static::class);
    }

    /** @return string */
    public function getId(): string
    {
        return 'recordkeeper';
    }

    /**
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
     * @param  Panel  $panel
     * @return void
     */
    public function boot(Panel $panel): void
    {
        config([
            'recordkeeper.filament.rollback_enabled'  => $this->rollbackEnabled,
            'recordkeeper.filament.navigation_group'  => $this->navigationGroup,
            'recordkeeper.filament.cluster'           => $this->cluster,
            'recordkeeper.filament.polling_interval'  => $this->pollingInterval,
        ]);
    }

    /**
     * @param  bool  $enabled
     * @return static
     */
    public function enableRollback(bool $enabled = true): static
    {
        $this->rollbackEnabled = $enabled;

        return $this;
    }

    /**
     * @param  bool  $enabled
     * @return static
     */
    public function enableTimeline(bool $enabled = true): static
    {
        $this->timelineEnabled = $enabled;

        return $this;
    }

    /**
     * @param  bool  $enabled
     * @return static
     */
    public function enableStatsWidget(bool $enabled = true): static
    {
        $this->statsEnabled = $enabled;

        return $this;
    }

    /**
     * @param  string  $group
     * @return static
     */
    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /**
     * @param  string  $cluster
     * @return static
     */
    public function cluster(string $cluster): static
    {
        $this->cluster = $cluster;

        return $this;
    }

    /**
     * @param  string  $interval
     * @return static
     */
    public function pollingInterval(string $interval): static
    {
        $this->pollingInterval = $interval;

        return $this;
    }

    /** @return bool */
    public function isRollbackEnabled(): bool
    {
        return $this->rollbackEnabled;
    }
}
