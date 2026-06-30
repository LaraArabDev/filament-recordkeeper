<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Unit;

use LaraArabDev\Recordkeeper\Actions\PruneAudits;
use LaraArabDev\Recordkeeper\Actions\RecordAudit;
use LaraArabDev\Recordkeeper\Actions\RedactValues;
use LaraArabDev\Recordkeeper\Actions\RestoreDeleted;
use LaraArabDev\Recordkeeper\Actions\RevertAudit;
use LaraArabDev\Recordkeeper\Actions\RevertBatch;
use LaraArabDev\Recordkeeper\Actions\SearchAudits;
use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;
use LaraArabDev\Recordkeeper\Facades\Recordkeeper;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Support\AttributeResolver;
use LaraArabDev\Recordkeeper\Tests\Fixtures\Order;
use LaraArabDev\Recordkeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AttributeResolver::clearCache();
    }

    // ── RecordAudit ───────────────────────────────────────────────────────

    #[Test]
    public function record_audit_persists_payload_to_database(): void
    {
        $payload = new AuditPayload(
            event: 'created',
            auditableType: 'Order',
            auditableId: 1,
            oldValues: [],
            newValues: ['status' => 'pending'],
        );

        $audit = app(RecordAudit::class)($payload);

        $this->assertInstanceOf(Audit::class, $audit);
        $this->assertTrue($audit->exists);
        $this->assertDatabaseHas('audits', ['event' => 'created', 'auditable_type' => 'Order']);
    }

    #[Test]
    public function record_audit_handle_is_equivalent_to_invoke(): void
    {
        $payload = new AuditPayload(
            event: 'updated',
            auditableType: 'Order',
            auditableId: 2,
            oldValues: ['status' => 'a'],
            newValues: ['status' => 'b'],
        );

        $audit = app(RecordAudit::class)->handle($payload);

        $this->assertInstanceOf(Audit::class, $audit);
        $this->assertSame('updated', $audit->event);
    }

    #[Test]
    public function record_audit_stores_context(): void
    {
        $payload = new AuditPayload(
            event: 'system.custom',
            auditableType: 'system',
            auditableId: null,
            oldValues: [],
            newValues: [],
            context: ['reason' => 'test'],
        );

        $audit = app(RecordAudit::class)($payload);

        $this->assertSame('test', $audit->context['reason'] ?? null);
    }

    // ── SearchAudits ──────────────────────────────────────────────────────

    #[Test]
    public function search_returns_empty_when_no_audits(): void
    {
        $results = app(SearchAudits::class)();

        $this->assertCount(0, $results);
    }

    #[Test]
    public function search_returns_audits_without_filters(): void
    {
        Order::create(['status' => 'a']);
        Order::create(['status' => 'b']);

        $results = app(SearchAudits::class)();

        $this->assertGreaterThanOrEqual(2, $results->count());
    }

    #[Test]
    public function search_filters_by_event(): void
    {
        $order = Order::create(['status' => 'pending']);
        $order->update(['status' => 'shipped']);

        $results = app(SearchAudits::class)(['event' => 'created']);

        foreach ($results as $audit) {
            $this->assertSame('created', $audit->event);
        }
    }

    #[Test]
    public function search_filters_by_model_short_name(): void
    {
        Order::create(['status' => 'ok']);

        $results = app(SearchAudits::class)(['model' => 'Order']);

        $this->assertGreaterThanOrEqual(1, $results->count());
        foreach ($results as $audit) {
            $this->assertStringContainsString('Order', $audit->auditable_type);
        }
    }

    #[Test]
    public function search_respects_limit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Order::create(['status' => "s{$i}"]);
        }

        $results = app(SearchAudits::class)([], 3);

        $this->assertCount(3, $results);
    }

    #[Test]
    public function search_respects_offset(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Order::create(['status' => "step{$i}"]);
        }

        $all = app(SearchAudits::class)([], 100, 0);
        $paged = app(SearchAudits::class)([], 100, 2);

        $this->assertCount(max(0, $all->count() - 2), $paged);
    }

    #[Test]
    public function search_filters_by_batch(): void
    {
        Recordkeeper::batch('batch-xyz', fn () => Order::create(['status' => 'batched']));
        Order::create(['status' => 'no-batch']);

        $results = app(SearchAudits::class)(['batch' => 'batch-xyz']);

        $this->assertCount(1, $results);
        $this->assertSame('batch-xyz', $results->first()->batch_id);
    }

    #[Test]
    public function search_handle_method_delegates_to_invoke(): void
    {
        Order::create(['status' => 'ok']);

        $action = app(SearchAudits::class);
        $results = $action->handle([], 25, 0);

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    // ── RedactValues ──────────────────────────────────────────────────────

    #[Test]
    public function redact_values_handle_is_equivalent_to_invoke(): void
    {
        config(['recordkeeper.privacy.sensitive_patterns' => ['token']]);

        $action = app(RedactValues::class);
        $result = $action->handle(['token' => 'abc', 'name' => 'Joe']);

        $this->assertSame('***', $result['token']);
        $this->assertSame('Joe', $result['name']);
    }

    #[Test]
    public function redact_values_uses_explicit_patterns(): void
    {
        $action = app(RedactValues::class);
        $result = $action(['secret' => 'x', 'other' => 'y'], ['secret']);

        $this->assertSame('***', $result['secret']);
        $this->assertSame('y', $result['other']);
    }

    // ── PruneAudits ───────────────────────────────────────────────────────

    #[Test]
    public function prune_audits_handle_is_equivalent_to_invoke(): void
    {
        $action = app(PruneAudits::class);
        $count = $action->handle(0, true); // dry-run with 0 days = would prune all

        $this->assertIsInt($count);
    }

    // ── RevertAudit ───────────────────────────────────────────────────────

    #[Test]
    public function revert_audit_handle_performs_rollback(): void
    {
        $order = Order::create(['status' => 'pending']);
        $order->update(['status' => 'shipped']);

        $audit = Audit::where('event', 'updated')->first();
        $action = app(RevertAudit::class);
        $action->handle($audit, false);

        $this->assertSame('pending', $order->fresh()->status);
    }

    // ── RevertBatch ───────────────────────────────────────────────────────

    #[Test]
    public function revert_batch_handle_rolls_back_batch(): void
    {
        Recordkeeper::batch('unit-batch', fn () => Order::create(['status' => 'batched']));

        $action = app(RevertBatch::class);
        $action->handle('unit-batch', false);

        $this->assertDatabaseCount('orders', 0);
    }

    // ── RestoreDeleted ────────────────────────────────────────────────────

    #[Test]
    public function restore_deleted_handle_restores_soft_deleted(): void
    {
        $order = Order::create(['status' => 'active']);
        $order->delete();

        $deletedAudit = Audit::where('event', 'deleted')->first();

        $action = app(RestoreDeleted::class);
        $action->handle($deletedAudit, false);

        $this->assertNotNull(Order::find($order->id));
    }

    // ── SearchAudits guard filter ─────────────────────────────────────────
    #[Test]
    public function search_filters_by_guard(): void
    {
        $payload = new AuditPayload(
            event: 'route.get',
            auditableType: 'route',
            auditableId: null,
            oldValues: [],
            newValues: [],
        );
        $audit = app(RecordAudit::class)($payload);
        $audit->guard = 'api';
        $audit->save();

        $results = app(SearchAudits::class)(['guard' => 'api']);

        $this->assertGreaterThanOrEqual(1, $results->count());
        foreach ($results as $a) {
            $this->assertSame('api', $a->guard);
        }
    }
}
