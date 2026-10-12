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
                ->multiple()
                ->options(fn () => Audit::query()
                    ->select('event')
                    ->distinct()
                    ->orderBy('event')
                    ->pluck('event', 'event')
                    ->all()),

            SelectFilter::make('auditable_type')
                ->label('Model')
                ->multiple()
                ->options(fn () => Audit::query()
                    ->select('auditable_type')
                    ->distinct()
                    ->orderBy('auditable_type')
                    ->pluck('auditable_type', 'auditable_type')
                    ->mapWithKeys(fn ($v) => [$v => class_basename((string) $v)])
                    ->all()),

            SelectFilter::make('guard')
                ->label('Guard')
                ->options(fn () => Audit::query()
                    ->select('guard')
                    ->distinct()
                    ->whereNotNull('guard')
                    ->orderBy('guard')
                    ->pluck('guard', 'guard')
                    ->all()),

            SelectFilter::make('user_type')
                ->label('Actor type')
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
                    TextInput::make('user_id')->label('Actor ID')->numeric(),
                ])
                ->query(fn (Builder $query, array $data) => $data['user_id']
                    ? $query->where('user_id', $data['user_id'])
                    : $query)
                ->indicateUsing(fn (array $data) => $data['user_id'] ? 'Actor #'.$data['user_id'] : null),

            Filter::make('period')
                ->form([
                    DatePicker::make('from')->label('From'),
                    DatePicker::make('until')->label('Until'),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                    ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until'])))
                ->indicateUsing(function (array $data): ?string {
                    if ($data['from'] && $data['until']) {
                        return $data['from'].' → '.$data['until'];
                    }

                    return $data['from'] ? 'From '.$data['from'] : ($data['until'] ? 'Until '.$data['until'] : null);
                }),

            Filter::make('batch_id')
                ->form([TextInput::make('batch_id')->label('Batch ID')])
                ->query(fn (Builder $query, array $data) => $data['batch_id']
                    ? $query->where('batch_id', $data['batch_id'])
                    : $query),

            TernaryFilter::make('rollbackable')
                ->label('Only rollbackable')
                ->queries(
                    true: fn (Builder $q) => $q->rollbackable(),
                    false: fn (Builder $q) => $q->whereNotIn('event', Audit::ROLLBACKABLE_EVENTS),
                ),
        ];
    }
}
