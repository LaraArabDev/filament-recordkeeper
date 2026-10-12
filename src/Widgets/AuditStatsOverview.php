<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;

class AuditStatsOverview extends StatsOverviewWidget
{
    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $counts = AuditFormatter::eventCounts();

        return [
            Stat::make(__('filament-recordkeeper::audit.widgets.total_audits'), number_format($counts->total))
                ->icon('heroicon-o-clock'),

            Stat::make(__('filament-recordkeeper::audit.widgets.created'), number_format($counts->created))
                ->color('success')
                ->icon('heroicon-o-plus-circle'),

            Stat::make(__('filament-recordkeeper::audit.widgets.updated'), number_format($counts->updated))
                ->color('warning')
                ->icon('heroicon-o-pencil'),

            Stat::make(__('filament-recordkeeper::audit.widgets.deleted'), number_format($counts->deleted))
                ->color('danger')
                ->icon('heroicon-o-trash'),

            Stat::make(__('filament-recordkeeper::audit.widgets.route_hits'), number_format($counts->routes))
                ->color('info')
                ->icon('heroicon-o-globe-alt'),

            Stat::make(__('filament-recordkeeper::audit.widgets.distinct_actors'), number_format($counts->actors))
                ->icon('heroicon-o-users'),
        ];
    }
}
