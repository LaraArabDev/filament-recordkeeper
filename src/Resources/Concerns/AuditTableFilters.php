<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources\Concerns;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use LaraArabDev\Recordkeeper\Models\Audit;

final class AuditTableFilters
{
    /**
     * @return list<SelectFilter|Filter|TernaryFilter>
     */
    public static function make(): array
    {
        return [
            SelectFilter::make('event')
                ->label(__('filament-recordkeeper::audit.columns.event'))
                ->multiple()
                ->options(fn () => Audit::query()
                    ->select('event')
                    ->distinct()
                    ->orderBy('event')
                    ->pluck('event', 'event')
                    ->all()),

            SelectFilter::make('auditable_type')
                ->label(__('filament-recordkeeper::audit.filters.model'))
                ->multiple()
                ->options(fn () => Audit::query()
                    ->select('auditable_type')
                    ->distinct()
                    ->orderBy('auditable_type')
                    ->pluck('auditable_type', 'auditable_type')
                    ->mapWithKeys(fn ($v) => [$v => class_basename((string) $v)])
                    ->all()),

            SelectFilter::make('guard')
                ->label(__('filament-recordkeeper::audit.columns.guard'))
                ->options(fn () => Audit::query()
                    ->select('guard')
                    ->distinct()
                    ->whereNotNull('guard')
                    ->orderBy('guard')
                    ->pluck('guard', 'guard')
                    ->all()),

            SelectFilter::make('user_type')
                ->label(__('filament-recordkeeper::audit.filters.actor_type'))
                ->options(fn () => Audit::query()
                    ->select('user_type')
                    ->distinct()
                    ->whereNotNull('user_type')
                    ->orderBy('user_type')
                    ->pluck('user_type', 'user_type')
                    ->mapWithKeys(fn ($v) => [$v => class_basename((string) $v)])
                    ->all()),

            Filter::make('actor')
                ->form([
                    TextInput::make('user_id')
                        ->label(__('filament-recordkeeper::audit.filters.actor_id'))
                        ->numeric(),
                ])
                ->query(fn (Builder $query, array $data) => $data['user_id']
                    ? $query->where('user_id', $data['user_id'])
                    : $query)
                ->indicateUsing(fn (array $data) => $data['user_id']
                    ? __('filament-recordkeeper::audit.columns.actor').' #'.$data['user_id']
                    : null),

            Filter::make('period')
                ->form([
                    DatePicker::make('from')
                        ->label(__('filament-recordkeeper::audit.filters.from')),
                    DatePicker::make('until')
                        ->label(__('filament-recordkeeper::audit.filters.until')),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                    ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until'])))
                ->indicateUsing(function (array $data): ?string {
                    if ($data['from'] && $data['until']) {
                        return $data['from'].' → '.$data['until'];
                    }

                    return $data['from'] ?? $data['until'] ?? null;
                }),

            Filter::make('batch_id')
                ->form([
                    TextInput::make('batch_id')
                        ->label(__('filament-recordkeeper::audit.filters.batch_id')),
                ])
                ->query(fn (Builder $query, array $data) => $data['batch_id']
                    ? $query->where('batch_id', $data['batch_id'])
                    : $query),

            TernaryFilter::make('rollbackable')
                ->label(__('filament-recordkeeper::audit.filters.only_rollbackable'))
                ->queries(
                    true: fn (Builder $q) => $q->rollbackable(),
                    false: fn (Builder $q) => $q->whereNotIn('event', Audit::ROLLBACKABLE_EVENTS),
                ),
        ];
    }
}
