<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Filament\Concerns;

use LaraArabDev\Recordkeeper\Filament\RelationManagers\AuditsRelationManager;

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
