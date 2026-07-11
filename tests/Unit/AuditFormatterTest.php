<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class AuditFormatterTest extends TestCase
{
    #[Test]
    #[DataProvider('eventColorProvider')]
    public function event_color_returns_correct_color(string $event, string $expected): void
    {
        $this->assertSame($expected, AuditFormatter::eventColor($event));
    }

    /** @return array<string, array{string, string}> */
    public static function eventColorProvider(): array
    {
        return [
            'created' => ['created', 'success'],
            'updated' => ['updated', 'warning'],
            'deleted' => ['deleted', 'danger'],
            'forceDeleted' => ['forceDeleted', 'danger'],
            'route.visited' => ['route.visited', 'info'],
            'route.api.called' => ['route.api.called', 'info'],
            'restored' => ['restored', 'gray'],
            'custom' => ['custom', 'gray'],
        ];
    }

    #[Test]
    public function actor_label_with_user(): void
    {
        $this->assertSame('User #5', AuditFormatter::actorLabel(5, 'App\\Models\\User'));
    }

    #[Test]
    public function actor_label_with_null_type_defaults_to_user(): void
    {
        $this->assertSame('User #3', AuditFormatter::actorLabel(3, null));
    }

    #[Test]
    public function actor_label_without_user_returns_system(): void
    {
        $this->assertSame('system', AuditFormatter::actorLabel(null, null));
    }

    #[Test]
    public function actor_label_with_zero_id_returns_system(): void
    {
        $this->assertSame('system', AuditFormatter::actorLabel(0, 'App\\Models\\User'));
    }

    #[Test]
    public function subject_label_with_type(): void
    {
        $this->assertSame('Order #42', AuditFormatter::subjectLabel('App\\Models\\Order', 42));
    }

    #[Test]
    public function subject_label_without_type_returns_dash(): void
    {
        $this->assertSame('—', AuditFormatter::subjectLabel(null, 1));
    }

    #[Test]
    public function can_revert_returns_false_when_rollback_config_disabled(): void
    {
        config([
            'recordkeeper.filament.rollback_enabled' => false,
            'recordkeeper.rollback.enabled' => true,
        ]);

        $audit = $this->createMock(Audit::class);
        $audit->method('isRollbackable')->willReturn(true);

        $this->assertFalse(AuditFormatter::canRevert($audit));
    }

    #[Test]
    public function can_revert_returns_false_when_core_rollback_disabled(): void
    {
        config([
            'recordkeeper.filament.rollback_enabled' => true,
            'recordkeeper.rollback.enabled' => false,
        ]);

        $audit = $this->createMock(Audit::class);
        $audit->method('isRollbackable')->willReturn(true);

        $this->assertFalse(AuditFormatter::canRevert($audit));
    }

    #[Test]
    public function can_revert_returns_false_when_audit_not_rollbackable(): void
    {
        config([
            'recordkeeper.filament.rollback_enabled' => true,
            'recordkeeper.rollback.enabled' => true,
        ]);

        $audit = $this->createMock(Audit::class);
        $audit->method('isRollbackable')->willReturn(false);

        $this->assertFalse(AuditFormatter::canRevert($audit));
    }

    #[Test]
    public function can_revert_returns_true_when_all_conditions_met(): void
    {
        config([
            'recordkeeper.filament.rollback_enabled' => true,
            'recordkeeper.rollback.enabled' => true,
        ]);

        $audit = $this->createMock(Audit::class);
        $audit->method('isRollbackable')->willReturn(true);

        $this->assertTrue(AuditFormatter::canRevert($audit));
    }
}
