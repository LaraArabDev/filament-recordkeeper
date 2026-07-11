<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;

/** Dashboard widget showing the 20 most recent audit entries in a non-paginated table. */
class AuditTimeline extends TableWidget
{
    /** @var int|null Widget sort order on the dashboard. */
    protected static ?int $sort = 99;

    /** @var int|string|array<int, int|string> Column span across the dashboard grid. */
    protected int|string|array $columnSpan = 'full';

    /**
     * Build the recent-activity table limited to the latest 20 audit records.
     */
    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Audit Activity')
            ->query(Audit::query()->latest()->limit(20))
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
            ])
            ->paginated(false);
    }
}
