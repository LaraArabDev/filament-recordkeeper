<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditStatsOverview;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditTimeline;

class RecordkeeperPlugin implements Plugin
{
    private bool $rollbackEnabled = false;

    private bool $timelineEnabled = false;

    private bool $statsEnabled = false;

    private string $navigationGroup = 'Audit';

    private ?string $cluster = null;

    private ?string $pollingInterval = null;

    public static function make(): static
    {
        return app(static::class);
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

        if (! empty($widgets)) {
            $panel->widgets($widgets);
        }
    }

    public function boot(Panel $panel): void
    {
        config([
            'recordkeeper.filament.rollback_enabled' => $this->rollbackEnabled,
            'recordkeeper.filament.navigation_group' => $this->navigationGroup,
            'recordkeeper.filament.cluster' => $this->cluster,
            'recordkeeper.filament.polling_interval' => $this->pollingInterval,
        ]);
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

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function cluster(string $cluster): static
    {
        $this->cluster = $cluster;

        return $this;
    }

    public function pollingInterval(string $interval): static
    {
        $this->pollingInterval = $interval;

        return $this;
    }

    public function isRollbackEnabled(): bool
    {
        return $this->rollbackEnabled;
    }
}
