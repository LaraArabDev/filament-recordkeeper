<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraArabDev\Recordkeeper\Models\Audit;

/** Relation manager that renders the audit history table for an Eloquent record. */
class AuditsRelationManager extends RelationManager
{
    /** @var string Eloquent relationship name on the owner model. */
    protected static string $relationship = 'audits';

    /** @var string|null Display title shown in the relation manager tab. */
    protected static ?string $title = 'History';

    /** @var string|\BackedEnum|null Heroicon shown in the relation manager tab. */
    protected static string|\BackedEnum|null $icon = 'heroicon-o-clock';

    /**
     * Build the audit history table with event badges, actor column, and rollback action.
     *
     * @param  Table  $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable(),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state === 'created' => 'success',
                        $state === 'updated' => 'warning',
                        in_array($state, ['deleted', 'forceDeleted'], true) => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('user_id')
                    ->label('Actor')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? (class_basename((string) ($record->user_type ?? 'User')).' #'.$state)
                        : 'system'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Audit $record) => route('filament.admin.resources.audits.view', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('revert')
                    ->label('Revert')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Audit $record) => $record->isRollbackable()
                        && config('recordkeeper.filament.rollback_enabled', false)
                        && config('recordkeeper.rollback.enabled', true))
                    ->action(fn (Audit $record) => $record->rollback()),
            ]);
    }
}
