<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ListAudits;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ViewAudit;
use LaraArabDev\Recordkeeper\Models\Audit;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $slug = 'audits';

    public static function getNavigationGroup(): ?string
    {
        return config('recordkeeper.filament.navigation_group', 'Audit');
    }

    public static function getNavigationSort(): ?int
    {
        return (int) config('recordkeeper.filament.navigation_sort', 100);
    }

    public static function getNavigationIcon(): string
    {
        return config('recordkeeper.filament.navigation_icon', 'heroicon-o-clock');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Audit::query()->with(['auditable'])
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable()
                    ->tooltip(fn ($record) => $record->created_at?->toIso8601String()),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state === 'created' => 'success',
                        $state === 'updated' => 'warning',
                        in_array($state, ['deleted', 'forceDeleted'], true) => 'danger',
                        str_starts_with($state, 'route.') => 'info',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('auditable_type')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? class_basename((string) $state).' #'.$record->auditable_id
                        : '—')
                    ->searchable(),

                TextColumn::make('user_id')
                    ->label('Actor')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? class_basename((string) ($record->user_type ?? 'User')).' #'.$state
                        : 'system'),

                TextColumn::make('guard')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'web' => 'gray',
                        'api' => 'info',
                        default => 'warning',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('batch_id')
                    ->label('Batch')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('url')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
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
                        true: fn (Builder $q) => $q->whereIn('event', ['created', 'updated', 'deleted', 'restored']),
                        false: fn (Builder $q) => $q->whereNotIn('event', ['created', 'updated', 'deleted', 'restored']),
                    ),
            ])
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('revert')
                    ->label('Revert')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Audit $record) => $record->isRollbackable()
                        && config('recordkeeper.filament.rollback_enabled', false)
                        && config('recordkeeper.rollback.enabled', true))
                    ->action(fn (Audit $record) => $record->rollback()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Prune selected')
                        ->visible(fn () => auth()->user()?->can('prune_audits') ?? false),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Overview')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('event')->badge(),
                        TextEntry::make('auditable_type')
                            ->label('Subject')
                            ->formatStateUsing(fn ($state, $record) => $state
                                ? class_basename((string) $state).' #'.$record->auditable_id
                                : '—'),
                        TextEntry::make('user_id')
                            ->label('Actor')
                            ->formatStateUsing(fn ($state, $record) => $state
                                ? class_basename((string) ($record->user_type ?? 'User')).' #'.$state
                                : 'system'),
                        TextEntry::make('guard')->badge(),
                        TextEntry::make('ip_address')->label('IP'),
                        TextEntry::make('created_at')->label('Time')->dateTime(),
                        TextEntry::make('batch_id')->label('Batch'),
                    ]),

                Section::make('Changes')
                    ->schema([
                        KeyValueEntry::make('old_values')->label('Before'),
                        KeyValueEntry::make('new_values')->label('After'),
                    ]),

                Section::make('Context')
                    ->schema([KeyValueEntry::make('context')])
                    ->visible(fn (Audit $record) => ! empty($record->context)),

                Section::make('Outbound HTTP Requests')
                    ->schema([
                        RepeatableEntry::make('httpRequests')
                            ->schema([
                                TextEntry::make('method')->badge(),
                                TextEntry::make('url'),
                                TextEntry::make('status_code')
                                    ->badge()
                                    ->color(fn (?int $state): string => match (true) {
                                        $state === null => 'gray',
                                        $state < 300 => 'success',
                                        $state < 400 => 'warning',
                                        default => 'danger',
                                    }),
                                TextEntry::make('duration_ms')->suffix(' ms'),
                                TextEntry::make('failed')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Failed' : 'OK'),
                            ])
                            ->columns(5),
                    ])
                    ->visible(fn (Audit $record) => config('recordkeeper.http.enabled', false)
                        && $record->httpRequests()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAudits::route('/'),
            'view' => ViewAudit::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['event', 'auditable_type', 'batch_id'];
    }
}
