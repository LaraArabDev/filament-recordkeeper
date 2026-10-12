<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources\Concerns;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;

final class AuditTableSchema
{
    /**
     * @return list<TextColumn>
     */
    public static function coreColumns(): array
    {
        return [
            TextColumn::make('created_at')
                ->label(__('filament-recordkeeper::audit.columns.time'))
                ->since()
                ->sortable()
                ->tooltip(fn ($record) => $record->created_at?->toIso8601String()),

            TextColumn::make('event')
                ->label(__('filament-recordkeeper::audit.columns.event'))
                ->badge()
                ->color(fn (string $state): string => AuditFormatter::eventColor($state))
                ->searchable(),

            TextColumn::make('auditable_type')
                ->label(__('filament-recordkeeper::audit.columns.subject'))
                ->formatStateUsing(fn ($state, $record) => AuditFormatter::subjectLabel($state, $record->auditable_id))
                ->searchable(),

            TextColumn::make('user_id')
                ->label(__('filament-recordkeeper::audit.columns.actor'))
                ->formatStateUsing(fn ($state, $record) => AuditFormatter::actorLabel($state, $record->user_type)),
        ];
    }

    /**
     * @return list<TextColumn>
     */
    public static function fullColumns(): array
    {
        return [
            ...self::coreColumns(),

            TextColumn::make('guard')
                ->label(__('filament-recordkeeper::audit.columns.guard'))
                ->badge()
                ->color(fn (?string $state): string => match ($state) {
                    'web' => 'gray',
                    'api' => 'info',
                    default => 'warning',
                })
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('batch_id')
                ->label(__('filament-recordkeeper::audit.columns.batch'))
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('ip_address')
                ->label(__('filament-recordkeeper::audit.columns.ip'))
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('url')
                ->label(__('filament-recordkeeper::audit.columns.url'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->limit(50),
        ];
    }

    /**
     * @return list<ViewAction|Action>
     */
    public static function tableActions(): array
    {
        return [
            ViewAction::make(),
            AuditFormatter::revertAction(),
        ];
    }

    /**
     * @return list<BulkActionGroup>
     */
    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->label(__('filament-recordkeeper::audit.actions.prune_selected'))
                    ->visible(fn () => auth()->user()?->can('prune_audits') ?? false),
            ]),
        ];
    }
}
