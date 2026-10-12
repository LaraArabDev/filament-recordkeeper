<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Widgets;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Resources\Concerns\AuditTableSchema;

class AuditTimeline extends TableWidget
{
    protected static ?int $sort = 99;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('filament-recordkeeper::audit.widgets.recent_activity'))
            ->query(Audit::query()->latest())
            ->paginated([20])
            ->columns(AuditTableSchema::coreColumns());
    }
}
