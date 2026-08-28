<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;

/**
 * Dashboard widget displaying aggregate audit event statistics.
 */
class AuditStatsOverview extends StatsOverviewWidget
{
    /**
     * Get the stat cards for the overview widget.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $counts = AuditFormatter::eventCounts();
        $actors = Audit::whereNotNull('user_id')->distinct('user_id')->count();

        return [
            Stat::make('Total Audits', number_format($counts->total))
                ->icon('heroicon-o-clock'),

            Stat::make('Created', number_format($counts->created))
                ->color('success')
                ->icon('heroicon-o-plus-circle'),

            Stat::make('Updated', number_format($counts->updated))
                ->color('warning')
                ->icon('heroicon-o-pencil'),

            Stat::make('Deleted', number_format($counts->deleted))
                ->color('danger')
                ->icon('heroicon-o-trash'),

            Stat::make('Route Hits', number_format($counts->routes))
                ->color('info')
                ->icon('heroicon-o-globe-alt'),

            Stat::make('Distinct Actors', number_format($actors))
                ->icon('heroicon-o-users'),
        ];
    }
}
