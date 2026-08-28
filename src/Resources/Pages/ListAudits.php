<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;

/**
 * List page for audit records with event-type tabs.
 */
class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;

    /**
     * Get the tab definitions with event-based filtering and badge counts.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $counts = AuditFormatter::eventCounts();

        return [
            'all' => Tab::make('All')
                ->badge($counts->total),

            'created' => Tab::make('Created')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('event', 'created'))
                ->badge($counts->created),

            'updated' => Tab::make('Updated')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('event', 'updated'))
                ->badge($counts->updated),

            'deleted' => Tab::make('Deleted')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('event', ['deleted', 'forceDeleted']))
                ->badge($counts->deleted),

            'routes' => Tab::make('Routes')
                ->modifyQueryUsing(fn (Builder $q) => $q->routeHits())
                ->badge($counts->routes),
        ];
    }
}
