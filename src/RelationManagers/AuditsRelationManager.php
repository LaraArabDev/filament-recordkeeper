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

/**
 * Relation manager that adds an audit history tab to any Filament resource.
 */
class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'History';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-clock';

    /**
     * Define the table columns and actions for the audit history tab.
     *
     * @param  Table  $table  The Filament table builder.
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

                AuditFormatter::revertAction(),
            ]);
    }
}
