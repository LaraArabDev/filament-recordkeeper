<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use LaraArabDev\Recordkeeper\Models\Audit;

class AuditTimeline extends TableWidget
{
    protected static ?int $sort = 99;

    protected int|string|array $columnSpan = 'full';

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
                    ->color(fn (string $state): string => match (true) {
                        $state === 'created' => 'success',
                        $state === 'updated' => 'warning',
                        str_starts_with($state, 'route.') => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('auditable_type')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state, $record) => class_basename((string) $state).' #'.$record->auditable_id),

                TextColumn::make('user_id')
                    ->label('Actor')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? (class_basename((string) ($record->user_type ?? 'User')).' #'.$state)
                        : 'system'),
            ])
            ->paginated(false);
    }
}
