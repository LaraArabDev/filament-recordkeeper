<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Concerns;

use LaraArabDev\RecordkeeperFilament\RelationManagers\AuditsRelationManager;

/**
 * Trait for Filament Resource classes to add an audit history relation manager tab.
 */
trait HasAuditHistory
{
    /** @return array<int, class-string> */
    public function getRelationManagers(): array
    {
        return array_merge(
            parent::getRelationManagers(),
            [AuditsRelationManager::class],
        );
    }
}
