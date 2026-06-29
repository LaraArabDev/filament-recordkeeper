<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Filament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraArabDev\Recordkeeper\Models\Audit;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'History';

    protected static ?string $icon = 'heroicon-o-clock';

    /**
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
                        $state === 'created'  => 'success',
                        $state === 'updated'  => 'warning',
                        in_array($state, ['deleted', 'forceDeleted'], true) => 'danger',
                        default              => 'gray',
                    }),

                TextColumn::make('user_id')
                    ->label('Actor')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? (class_basename((string) ($record->user_type ?? 'User')) . ' #' . $state)
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
