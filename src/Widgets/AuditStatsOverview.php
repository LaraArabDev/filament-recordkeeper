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
        $total = Audit::count();
        $created = Audit::where('event', 'created')->count();
        $updated = Audit::where('event', 'updated')->count();
        $deleted = Audit::whereIn('event', ['deleted', 'forceDeleted'])->count();
        $routes = Audit::where('event', 'like', 'route.%')->count();
        $actors = Audit::whereNotNull('user_id')->distinct('user_id')->count();

        return [
            Stat::make('Total Audits', number_format($total))
                ->icon('heroicon-o-clock'),

            Stat::make('Created', number_format($created))
                ->color('success')
                ->icon('heroicon-o-plus-circle'),

            Stat::make('Updated', number_format($updated))
                ->color('warning')
                ->icon('heroicon-o-pencil'),

            Stat::make('Deleted', number_format($deleted))
                ->color('danger')
                ->icon('heroicon-o-trash'),

            Stat::make('Route Hits', number_format($routes))
                ->color('info')
                ->icon('heroicon-o-globe-alt'),

            Stat::make('Distinct Actors', number_format($actors))
                ->icon('heroicon-o-users'),
        ];
    }
}
