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
        $reflection = new \ReflectionMethod(HasAuditHistory::class, 'getRelationManagers');

        // The trait method exists and returns an array containing AuditsRelationManager
        $this->assertTrue($reflection->isPublic());

        // Verify the source references AuditsRelationManager
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString(AuditsRelationManager::class, $source);
    }
}
