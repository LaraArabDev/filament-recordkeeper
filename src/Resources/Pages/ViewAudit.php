<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use LaraArabDev\RecordkeeperFilament\Resources\AuditResource;
use LaraArabDev\Recordkeeper\Models\Audit;

class ViewAudit extends ViewRecord
{
    protected static string $resource = AuditResource::class;

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
                    && $this->getRecord()->isRollbackable()
                    && config('recordkeeper.filament.rollback_enabled', false)
                    && config('recordkeeper.rollback.enabled', true))
                ->action(fn () => $this->getRecord()->rollback()),
        ];
    }
}
