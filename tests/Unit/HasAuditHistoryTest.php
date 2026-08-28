<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use LaraArabDev\RecordkeeperFilament\Concerns\HasAuditHistory;
use LaraArabDev\RecordkeeperFilament\RelationManagers\AuditsRelationManager;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasAuditHistoryTest extends TestCase
{
    #[Test]
    public function trait_includes_audits_relation_manager(): void
    {
        $stub = new class extends HasAuditHistoryTestParent
        {
            use HasAuditHistory;
        };

        $managers = $stub->getRelationManagers();

        $this->assertContains(AuditsRelationManager::class, $managers);
    }

    #[Test]
    public function trait_merges_with_parent_relation_managers(): void
    {
        $stub = new class extends HasAuditHistoryTestParentWithExisting
        {
            use HasAuditHistory;
        };

        $managers = $stub->getRelationManagers();

        $this->assertContains(AuditsRelationManager::class, $managers);
        $this->assertContains('ExistingManager', $managers);
        $this->assertCount(2, $managers);
    }
}

class HasAuditHistoryTestParent
{
    public function getRelationManagers(): array
    {
        return [];
    }
}

class HasAuditHistoryTestParentWithExisting
{
    public function getRelationManagers(): array
    {
        return ['ExistingManager'];
    }
}
