<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\RecordkeeperPlugin;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ListAudits;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ViewAudit;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;
use UnitEnum;

/**
 * Filament resource for browsing and inspecting audit records.
 */
class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $slug = 'audits';

    /**
     * Get the navigation group from the plugin configuration.
     */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return RecordkeeperPlugin::get()->getNavigationGroup();
    }

    /**
     * Get the navigation sort order from the plugin configuration.
     */
    public static function getNavigationSort(): ?int
    {
        return RecordkeeperPlugin::get()->getNavigationSort();
    }

    /**
     * Get the navigation icon from the plugin configuration.
     */
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return RecordkeeperPlugin::get()->getNavigationIcon();
    }

    /**
     * Audits are read-only; creation is always disabled.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Define the form schema (empty — audits are not editable).
     *
     * @param  Schema  $schema  The Filament schema builder.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    /**
     * Define the table columns, filters, and actions for the audit listing.
     *
     * @param  Table  $table  The Filament table builder.
     */
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
                    ->color(fn (string $state): string => AuditFormatter::eventColor($state))
                    ->searchable(),

                TextColumn::make('auditable_type')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state, $record) => AuditFormatter::subjectLabel($state, $record->auditable_id))
                    ->searchable(),

                TextColumn::make('user_id')
                    ->label('Actor')
                    ->formatStateUsing(fn ($state, $record) => AuditFormatter::actorLabel($state, $record->user_type)),

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
                        true: fn (Builder $q) => $q->rollbackable(),
                        false: fn (Builder $q) => $q->whereNotIn('event', Audit::ROLLBACKABLE_EVENTS),
                    ),
            ])
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make(),
                AuditFormatter::revertAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Prune selected')
                        ->visible(fn () => auth()->user()?->can('prune_audits') ?? false),
                ]),
            ]);
    }

    /**
     * Define the infolist schema for viewing a single audit record.
     *
     * @param  Schema  $infolist  The Filament infolist schema builder.
     */
    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Overview')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('event')->badge(),
                        TextEntry::make('auditable_type')
                            ->label('Subject')
                            ->formatStateUsing(fn ($state, $record) => AuditFormatter::subjectLabel($state, $record->auditable_id)),
                        TextEntry::make('user_id')
                            ->label('Actor')
                            ->formatStateUsing(fn ($state, $record) => AuditFormatter::actorLabel($state, $record->user_type)),
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

    /**
     * Get the registered pages for this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListAudits::route('/'),
            'view' => ViewAudit::route('/{record}'),
        ];
    }

    /**
     * Get the attributes used for global search.
     *
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['event', 'auditable_type', 'batch_id'];
    }
}
