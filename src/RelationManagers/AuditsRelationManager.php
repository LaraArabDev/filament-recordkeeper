<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Resources\Concerns\AuditTableSchema;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;

/**
 * Relation manager that adds an audit history tab to any Filament resource.
 */
class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'History';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-clock';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->columns(AuditTableSchema::coreColumns())
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make()
                    ->url(fn (Audit $record) => AuditResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),

                AuditFormatter::revertAction(),
            ]);
    }
}
