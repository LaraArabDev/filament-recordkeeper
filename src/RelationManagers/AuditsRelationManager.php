<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;

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
                    ->color(fn (string $state): string => AuditFormatter::eventColor($state)),

                TextColumn::make('user_id')
                    ->label('Actor')
                    ->formatStateUsing(fn ($state, $record) => AuditFormatter::actorLabel($state, $record->user_type)),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Audit $record) => AuditResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('revert')
                    ->label('Revert')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Audit $record) => AuditFormatter::canRevert($record))
                    ->action(fn (Audit $record) => $record->rollback()),
            ]);
    }
}
