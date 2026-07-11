<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use LaraArabDev\RecordkeeperFilament\RelationManagers\AuditsRelationManager;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditsRelationManagerTest extends TestCase
{
    #[Test]
    public function relationship_name_is_audits(): void
    {
        $reflection = new \ReflectionClass(AuditsRelationManager::class);
        $property = $reflection->getProperty('relationship');

        $this->assertSame('audits', $property->getDefaultValue());
    }

    #[Test]
    public function title_is_history(): void
    {
        $reflection = new \ReflectionClass(AuditsRelationManager::class);
        $property = $reflection->getProperty('title');

        $this->assertSame('History', $property->getDefaultValue());
    }

    #[Test]
    public function icon_is_clock(): void
    {
        $reflection = new \ReflectionClass(AuditsRelationManager::class);
        $property = $reflection->getProperty('icon');

        $this->assertSame('heroicon-o-clock', $property->getDefaultValue());
    }
}
