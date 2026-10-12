<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use LaraArabDev\Recordkeeper\Models\Audit;

class ActorStatsWidget extends StatsOverviewWidget
{
    /**
     * @return array{total_actors: int, most_active_id: string, most_active_count: int, today_actors: int, system_actions: int}
     */
    protected static function actorStats(): array
    {
        $mostActive = Audit::query()
            ->selectRaw('user_type, user_id, COUNT(*) as audit_count')
            ->whereNotNull('user_id')
            ->groupBy('user_type', 'user_id')
            ->orderByDesc('audit_count')
            ->first();

        $totalActors = (int) Audit::query()
            ->whereNotNull('user_id')
            ->selectRaw('COUNT(DISTINCT CONCAT(COALESCE(user_type, \'\'), \':\', user_id)) as cnt')
            ->value('cnt');

        $todayActors = (int) Audit::query()
            ->whereNotNull('user_id')
            ->where('created_at', '>=', Carbon::today())
            ->selectRaw('COUNT(DISTINCT CONCAT(COALESCE(user_type, \'\'), \':\', user_id)) as cnt')
            ->value('cnt');

        $systemActions = (int) Audit::query()
            ->whereNull('user_id')
            ->count();

        return [
            'total_actors' => $totalActors,
            'most_active_id' => $mostActive
                ? class_basename((string) ($mostActive->user_type ?? 'User')).' #'.$mostActive->user_id
                : '—',
            'most_active_count' => $mostActive ? (int) $mostActive->audit_count : 0,
            'today_actors' => $todayActors,
            'system_actions' => $systemActions,
        ];
    }

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $stats = self::actorStats();

        return [
            Stat::make(__('filament-recordkeeper::audit.widgets.total_actors'), number_format($stats['total_actors']))
                ->icon('heroicon-o-users'),

            Stat::make(__('filament-recordkeeper::audit.widgets.most_active'), $stats['most_active_id'])
                ->description(__('filament-recordkeeper::audit.widgets.audits_count', ['count' => $stats['most_active_count']]))
                ->icon('heroicon-o-star'),

            Stat::make(__('filament-recordkeeper::audit.widgets.active_today'), number_format($stats['today_actors']))
                ->icon('heroicon-o-user-group')
                ->color('success'),

            Stat::make(__('filament-recordkeeper::audit.widgets.system_actions'), number_format($stats['system_actions']))
                ->description(__('filament-recordkeeper::audit.widgets.no_actor'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray'),
        ];
    }
}
