<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;

/**
 * View page for a single audit record with optional revert header action.
 */
class ViewAudit extends ViewRecord
{
    protected static string $resource = AuditResource::class;

    /**
     * Get the header actions including the conditional revert action.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('revert')
                ->label('Revert this change')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription(fn () => view('recordkeeper::rollback-preview', [
                    'modified' => $this->getRecord()->getModified(),
                    'event' => $this->getRecord()->event,
                    'createdAt' => $this->getRecord()->created_at?->diffForHumans(),
                ]))
                ->visible(fn () => $this->getRecord() instanceof Audit
                    && AuditFormatter::canRevert($this->getRecord()))
                ->action(fn () => $this->getRecord()->rollback()),
        ];
    }
}
