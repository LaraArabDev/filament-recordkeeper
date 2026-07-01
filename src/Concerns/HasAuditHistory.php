<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentRecordkeeper\Concerns;

use LaraArabDev\FilamentRecordkeeper\RelationManagers\AuditsRelationManager;

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
