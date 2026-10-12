<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Widgets;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Resources\Concerns\AuditTableSchema;

/**
 * Dashboard widget showing a compact table of recent audit activity.
 */
class AuditTimeline extends TableWidget
{
    protected static ?int $sort = 99;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Audit Activity')
            ->query(Audit::query()->latest())
            ->paginated([20])
            ->columns(AuditTableSchema::coreColumns());
    }
}
