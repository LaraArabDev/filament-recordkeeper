<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Feature;

use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Recordkeeper;
use LaraArabDev\Recordkeeper\Support\AttributeResolver;
use LaraArabDev\Recordkeeper\Tests\Fixtures\Order;
use LaraArabDev\Recordkeeper\Tests\TestCase;

class ModelAuditingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AttributeResolver::clearCache();
        app(Recordkeeper::class)->clearContext();
    }

    // ------------------------------------------------------------------
    // Basic event recording — delegates to laravel-auditing
    // ------------------------------------------------------------------

    public function test_records_created_event_via_laravel_auditing(): void
    {
        $order = Order::create(['status' => 'pending', 'total' => 100.00]);

        $this->assertDatabaseCount('audits', 1);
        $this->assertDatabaseHas('audits', [
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'event' => 'created',
        ]);
    }

    public function test_records_updated_event_with_diff(): void
    {
        $order = Order::create(['status' => 'pending', 'total' => 100.00]);
        $order->update(['status' => 'shipped']);

        $audit = Audit::where('event', 'updated')->first();
        $this->assertNotNull($audit);

        $modified = $audit->getModified();
        $this->assertArrayHasKey('status', $modified);
        $this->assertSame('pending', $modified['status']['old']);
        $this->assertSame('shipped', $modified['status']['new']);
    }

    public function test_records_deleted_event(): void
    {
        $order = Order::create(['status' => 'pending']);
        $order->delete();

        $this->assertDatabaseHas('audits', ['event' => 'deleted']);
    }

    public function test_update_diff_does_not_contain_unchanged_fields(): void
    {
        $order = Order::create(['status' => 'pending', 'total' => 50]);
        $order->update(['status' => 'active']); // total unchanged

        $audit = Audit::where('event', 'updated')->first();
        $modified = $audit->getModified();

        $this->assertArrayHasKey('status', $modified);
        $this->assertArrayNotHasKey('total', $modified);
    }

    // ------------------------------------------------------------------
    // Excluded attributes (#[AuditExclude])
    // ------------------------------------------------------------------

    public function test_excluded_attribute_not_present_in_created_audit(): void
    {
        Order::create(['status' => 'ok', 'internal_notes' => 'private']);

        $audit = Audit::first();
        $modified = $audit->getModified();

        $this->assertArrayNotHasKey('internal_notes', $modified);
    }

    public function test_excluded_attribute_not_present_in_updated_audit(): void
    {
        $order = Order::create(['status' => 'ok']);
        $order->update(['internal_notes' => 'changed']);

        $updatedAudits = Audit::where('event', 'updated')->get();
        $allModified = $updatedAudits->flatMap(fn ($a) => array_keys($a->getModified()))->all();

        $this->assertNotContains('internal_notes', $allModified);
    }

    // ------------------------------------------------------------------
    // Context via transformAudit
    // ------------------------------------------------------------------

    public function test_context_attached_via_push_context(): void
    {
        app(Recordkeeper::class)->pushContext(['reason' => 'admin-override', 'ticket' => 'T-99']);

        Order::create(['status' => 'pending']);

        $audit = Audit::first();
        $this->assertSame('admin-override', $audit->context['reason'] ?? null);
        $this->assertSame('T-99', $audit->context['ticket'] ?? null);

        app(Recordkeeper::class)->clearContext();
    }

    public function test_context_cleared_after_test(): void
    {
        app(Recordkeeper::class)->clearContext();
        Order::create(['status' => 'clean']);

        $audit = Audit::first();
        $this->assertEmpty(array_filter((array) ($audit->context ?? [])));
    }

    // ------------------------------------------------------------------
    // Tags via generateTags()
    // ------------------------------------------------------------------

    public function test_tags_field_is_present_on_audit(): void
    {
        Order::create(['status' => 'pending']);

        $audit = Audit::first();
        // tags is a string (comma-separated) or null
        $this->assertTrue(is_string($audit->tags) || $audit->tags === null);
    }

    public function test_custom_tags_injected_via_with_tags(): void
    {
        app(Recordkeeper::class)->withTags(['finance', 'q4']);

        Order::create(['status' => 'paid']);

        $audit = Audit::first();
        $this->assertStringContainsString('finance', (string) $audit->tags);

        app(Recordkeeper::class)->withTags([]);
    }

    // ------------------------------------------------------------------
    // isRollbackable()
    // ------------------------------------------------------------------

    public function test_is_rollbackable_true_for_model_events(): void
    {
        $order = Order::create(['status' => 'a']);
        $order->update(['status' => 'b']);
        $order->delete();

        $audits = Audit::whereIn('event', ['created', 'updated', 'deleted'])->get();

        foreach ($audits as $audit) {
            $this->assertTrue($audit->isRollbackable(), "Expected {$audit->event} to be rollbackable");
        }
    }

    public function test_is_rollbackable_false_for_route_events(): void
    {
        $routeAudit = new Audit;
        $routeAudit->fill([
            'event' => 'route.get',
            'auditable_type' => 'route',
            'old_values' => [],
            'new_values' => [],
        ]);
        $routeAudit->save();

        $this->assertFalse($routeAudit->isRollbackable());
    }

    // ------------------------------------------------------------------
    // Audit model not audited (no recursion)
    // ------------------------------------------------------------------

    public function test_audit_model_writes_do_not_create_recursive_audits(): void
    {
        // Create one order → 1 audit row
        Order::create(['status' => 'pending']);
        $countAfterFirst = Audit::count();

        // Creating the audit row itself must NOT trigger another audit
        $this->assertSame(1, $countAfterFirst);
    }
}
