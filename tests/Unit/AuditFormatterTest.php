<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Unit;

use Filament\Facades\Filament;
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
    public function can_revert_returns_false_when_audit_not_rollbackable(): void
    {
        $audit = $this->createMock(Audit::class);
        $audit->method('isRollbackable')->willReturn(false);

        $this->assertFalse(AuditFormatter::canRevert($audit));
    }

    #[Test]
    public function can_revert_returns_false_when_core_rollback_disabled(): void
    {
        config(['recordkeeper.rollback.enabled' => false]);

        $audit = $this->createMock(Audit::class);
        $audit->method('isRollbackable')->willReturn(true);

        $this->assertFalse(AuditFormatter::canRevert($audit));
    }

    #[Test]
    public function can_revert_returns_false_without_panel_context(): void
    {
        config(['recordkeeper.rollback.enabled' => true]);

        Filament::setCurrentPanel(null);

        $audit = $this->createMock(Audit::class);
        $audit->method('isRollbackable')->willReturn(true);

        $this->assertFalse(AuditFormatter::canRevert($audit));
    }

    #[Test]
    public function event_counts_returns_zero_counts_with_empty_database(): void
    {
        $counts = AuditFormatter::eventCounts();

        $this->assertSame(0, $counts->total);
        $this->assertSame(0, $counts->created);
        $this->assertSame(0, $counts->updated);
        $this->assertSame(0, $counts->deleted);
        $this->assertSame(0, $counts->routes);
    }

    #[Test]
    public function event_counts_reflects_seeded_data(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'created', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'updated', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'deleted', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'forceDeleted', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'route.visited', 'auditable_type' => 'App\\Models\\User', 'auditable_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $counts = AuditFormatter::eventCounts();

        $this->assertSame(6, $counts->total);
        $this->assertSame(2, $counts->created);
        $this->assertSame(1, $counts->updated);
        $this->assertSame(2, $counts->deleted);
        $this->assertSame(1, $counts->routes);
    }
}
