<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentRecordkeeper\Resources\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use LaraArabDev\FilamentRecordkeeper\Resources\AuditResource;
use LaraArabDev\Recordkeeper\Models\Audit;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;

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
