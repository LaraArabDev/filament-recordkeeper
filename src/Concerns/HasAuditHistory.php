<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Concerns;

use LaraArabDev\RecordkeeperFilament\RelationManagers\AuditsRelationManager;

trait HasAuditHistory
{
    public function getRelationManagers(): array
    {
        return array_merge(
            parent::getRelationManagers(),
            [AuditsRelationManager::class],
        );
    }
}
