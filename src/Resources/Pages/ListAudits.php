<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;
use LaraArabDev\RecordkeeperFilament\Widgets\ActorStatsWidget;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;

    /** @return array<class-string> */
    protected function getHeaderWidgets(): array
    {
        return [ActorStatsWidget::class];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $counts = AuditFormatter::eventCounts();

        return [
            'all' => Tab::make(__('filament-recordkeeper::audit.tabs.all'))
                ->badge($counts->total),

            'created' => Tab::make(__('filament-recordkeeper::audit.tabs.created'))
                ->modifyQueryUsing(fn (Builder $q) => $q->where('event', 'created'))
                ->badge($counts->created),

            'updated' => Tab::make(__('filament-recordkeeper::audit.tabs.updated'))
                ->modifyQueryUsing(fn (Builder $q) => $q->where('event', 'updated'))
                ->badge($counts->updated),

            'deleted' => Tab::make(__('filament-recordkeeper::audit.tabs.deleted'))
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('event', ['deleted', 'forceDeleted']))
                ->badge($counts->deleted),

            'routes' => Tab::make(__('filament-recordkeeper::audit.tabs.routes'))
                ->modifyQueryUsing(fn (Builder $q) => $q->routeHits())
                ->badge($counts->routes),
        ];
    }
}
