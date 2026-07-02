<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\Recordkeeper\Models\Audit;

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
        return [
            'all' => Tab::make('All')
                ->badge(Audit::count()),

            'created' => Tab::make('Created')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('event', 'created'))
                ->badge(Audit::where('event', 'created')->count()),

            'updated' => Tab::make('Updated')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('event', 'updated'))
                ->badge(Audit::where('event', 'updated')->count()),

            'deleted' => Tab::make('Deleted')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('event', ['deleted', 'forceDeleted']))
                ->badge(Audit::whereIn('event', ['deleted', 'forceDeleted'])->count()),

            'routes' => Tab::make('Routes')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('event', 'like', 'route.%'))
                ->badge(Audit::where('event', 'like', 'route.%')->count()),
        ];
    }
}
