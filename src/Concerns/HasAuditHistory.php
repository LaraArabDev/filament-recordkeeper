<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Concerns;

use LaraArabDev\RecordkeeperFilament\RelationManagers\AuditsRelationManager;

/** Adds the AuditsRelationManager to any Filament resource page that uses this trait. */
trait HasAuditHistory
{
    /**
     * Merge the AuditsRelationManager into the page's relation manager list.
     *
     * @return array<int, class-string>
     */
    public function getRelationManagers(): array
    {
        return array_merge(
            parent::getRelationManagers(),
            [AuditsRelationManager::class],
        );
    }
}
