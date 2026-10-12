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

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        /** @var Audit $audit */
        $audit = $this->getRecord();

        return [
            Action::make('revert')
                ->label(__('filament-recordkeeper::audit.actions.revert_this_change'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription(fn () => view('recordkeeper::rollback-preview', [
                    'modified' => $audit->getModified(),
                    'event' => $audit->event,
                    'createdAt' => $audit->created_at?->diffForHumans(),
                ]))
                ->visible(fn () => AuditFormatter::canRevert($audit))
                ->action(fn () => $audit->rollback()),
        ];
    }
}
