<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\RecordkeeperPlugin;
use LaraArabDev\RecordkeeperFilament\Resources\Concerns\AuditInfolistSchema;
use LaraArabDev\RecordkeeperFilament\Resources\Concerns\AuditTableFilters;
use LaraArabDev\RecordkeeperFilament\Resources\Concerns\AuditTableSchema;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ListAudits;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ViewAudit;
use UnitEnum;

/**
 * Read-only Filament resource for browsing and inspecting audit records.
 */
class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $slug = 'audits';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return RecordkeeperPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return RecordkeeperPlugin::get()->getNavigationSort();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return RecordkeeperPlugin::get()->getNavigationIcon();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Audit::query()->with(['auditable']))
            ->columns(AuditTableSchema::fullColumns())
            ->defaultSort('created_at', 'desc')
            ->filters(AuditTableFilters::make())
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->actions(AuditTableSchema::tableActions())
            ->bulkActions(AuditTableSchema::bulkActions());
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist->schema(AuditInfolistSchema::make());
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListAudits::route('/'),
            'view' => ViewAudit::route('/{record}'),
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['event', 'auditable_type', 'batch_id'];
    }
}
