<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;

/** Paginated list page for audit records with event-based tabs. */
class ListAudits extends ListRecords
{
    /** @var string The resource this page belongs to. */
    protected static string $resource = AuditResource::class;

    /**
     * Return the filterable tabs shown above the audit table.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
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

        return [
            'all' => Tab::make('All')
                ->badge((int) $counts->total),

            'created' => Tab::make('Created')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('event', 'created'))
                ->badge((int) $counts->created),

            'updated' => Tab::make('Updated')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('event', 'updated'))
                ->badge((int) $counts->updated),

            'deleted' => Tab::make('Deleted')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('event', ['deleted', 'forceDeleted']))
                ->badge((int) $counts->deleted),

            'routes' => Tab::make('Routes')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('event', 'like', 'route.%'))
                ->badge((int) $counts->routes),
        ];
    }
}
