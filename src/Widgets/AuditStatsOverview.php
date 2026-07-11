<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use LaraArabDev\Recordkeeper\Models\Audit;

/** Dashboard widget displaying aggregate audit counts broken down by event type and actor. */
class AuditStatsOverview extends StatsOverviewWidget
{
    /**
     * Return the stat cards with counts for each audit category.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $counts = Audit::query()
            ->selectRaw(implode(', ', [
                'COUNT(*) as total',
                "SUM(CASE WHEN event = 'created' THEN 1 ELSE 0 END) as created",
                "SUM(CASE WHEN event = 'updated' THEN 1 ELSE 0 END) as updated",
                "SUM(CASE WHEN event IN ('deleted','forceDeleted') THEN 1 ELSE 0 END) as deleted",
                "SUM(CASE WHEN event LIKE 'route.%' THEN 1 ELSE 0 END) as routes",
            ]))
            ->first();

        $actors = Audit::whereNotNull('user_id')->distinct('user_id')->count();

        return [
            Stat::make('Total Audits', number_format((int) $counts->total))
                ->icon('heroicon-o-clock'),

            Stat::make('Created', number_format((int) $counts->created))
                ->color('success')
                ->icon('heroicon-o-plus-circle'),

            Stat::make('Updated', number_format((int) $counts->updated))
                ->color('warning')
                ->icon('heroicon-o-pencil'),

            Stat::make('Deleted', number_format((int) $counts->deleted))
                ->color('danger')
                ->icon('heroicon-o-trash'),

            Stat::make('Route Hits', number_format((int) $counts->routes))
                ->color('info')
                ->icon('heroicon-o-globe-alt'),

            Stat::make('Distinct Actors', number_format($actors))
                ->icon('heroicon-o-users'),
        ];
    }
}
