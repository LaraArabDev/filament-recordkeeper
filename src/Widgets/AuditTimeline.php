<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;

/**
 * Dashboard widget showing a compact table of recent audit activity.
 */
class AuditTimeline extends TableWidget
{
    protected static ?int $sort = 99;

    protected int|string|array $columnSpan = 'full';

    /**
     * Define the timeline table with the 20 most recent audits.
     *
     * @param  Table  $table  The Filament table builder.
     */
    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Audit Activity')
            ->query(Audit::query()->latest())
            ->paginated([20])
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since(),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => AuditFormatter::eventColor($state)),

                TextColumn::make('auditable_type')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state, $record) => AuditFormatter::subjectLabel($state, $record->auditable_id)),

                TextColumn::make('user_id')
                    ->label('Actor')
                    ->formatStateUsing(fn ($state, $record) => AuditFormatter::actorLabel($state, $record->user_type)),
            ]);
    }
}
