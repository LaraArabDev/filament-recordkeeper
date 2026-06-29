<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Unit;

use LaraArabDev\Recordkeeper\Facades\Recordkeeper;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Support\AttributeResolver;
use LaraArabDev\Recordkeeper\Support\AuditQuery;
use LaraArabDev\Recordkeeper\Tests\Fixtures\Order;
use LaraArabDev\Recordkeeper\Tests\TestCase;

class AuditQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AttributeResolver::clearCache();
    }

    private function seedAudits(): void
    {
        // model audit
        Recordkeeper::batch('batch-a', function (): void {
            Order::create(['status' => 'pending']);
        });

        $order = Order::create(['status' => 'active']);
        $order->update(['status' => 'shipped']);

        // route audit for web guard
        Audit::create([
            'event'          => 'route.get',
            'auditable_type' => 'route',
            'auditable_id'   => null,
            'old_values'     => [],
            'new_values'     => [],
            'guard'          => 'web',
            'batch_id'       => null,
        ]);

        // route audit for api guard
        Audit::create([
            'event'          => 'route.post',
            'auditable_type' => 'route',
            'auditable_id'   => null,
            'old_values'     => [],
            'new_values'     => [],
            'guard'          => 'api',
            'user_type'      => 'App\\Models\\Admin',
            'user_id'        => 99,
            'batch_id'       => 'batch-b',
        ]);
    }

    public function test_model_filter_by_short_name(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->model('Order')->builder()->get();

        $this->assertTrue($results->every(fn ($a) => $a->auditable_type === Order::class));
        $this->assertGreaterThan(0, $results->count());
    }

    public function test_model_filter_by_fqcn(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->model(Order::class)->builder()->get();

        $this->assertTrue($results->every(fn ($a) => $a->auditable_type === Order::class));
    }

    public function test_event_filter_single(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->event('updated')->builder()->get();

        $this->assertTrue($results->every(fn ($a) => $a->event === 'updated'));
    }

    public function test_event_filter_multiple(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->event(['created', 'updated'])->builder()->get();

        foreach ($results as $r) {
            $this->assertContains($r->event, ['created', 'updated']);
        }
    }

    public function test_guard_filter_hits_indexed_column(): void
    {
        $this->seedAudits();

        $web = (new AuditQuery())->guard('web')->builder()->get();
        $api = (new AuditQuery())->guard('api')->builder()->get();

        $this->assertTrue($web->every(fn ($a) => $a->guard === 'web'));
        $this->assertTrue($api->every(fn ($a) => $a->guard === 'api'));
    }

    public function test_guard_filter_excludes_other_guards(): void
    {
        $this->seedAudits();

        $webOnly = (new AuditQuery())->guard('web')->builder()->get();

        $this->assertFalse($webOnly->contains(fn ($a) => $a->guard === 'api'));
    }

    public function test_actor_filter_by_id_only(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->actor(99)->builder()->get();

        $this->assertTrue($results->every(fn ($a) => $a->user_id == 99));
    }

    public function test_actor_filter_by_id_and_type_short_name(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->actor(99, 'Admin')->builder()->get();

        $this->assertGreaterThan(0, $results->count());
        $this->assertTrue($results->every(fn ($a) => $a->user_id == 99));
    }

    public function test_actor_filter_by_id_and_type_fqcn(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->actor(99, 'App\\Models\\Admin')->builder()->get();

        $this->assertGreaterThan(0, $results->count());
    }

    public function test_actor_type_filter_by_short_name(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->actorType('Admin')->builder()->get();

        $this->assertTrue($results->every(fn ($a) => str_contains((string) $a->user_type, 'Admin')));
    }

    public function test_batch_filter(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->batch('batch-a')->builder()->get();

        $this->assertTrue($results->every(fn ($a) => $a->batch_id === 'batch-a'));
    }

    public function test_rollbackable_excludes_route_events(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->rollbackable()->builder()->get();

        $this->assertFalse($results->contains(fn ($a) => str_starts_with((string) $a->event, 'route.')));
    }

    public function test_search_by_event_name(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->search('updated')->builder()->get();

        $this->assertGreaterThan(0, $results->count());
    }

    public function test_latest_orders_newest_first(): void
    {
        $this->seedAudits();

        $results = (new AuditQuery())->latest()->builder()->get();

        $timestamps = $results->pluck('created_at')->map(fn ($d) => $d->timestamp)->all();
        $sorted     = $timestamps;
        rsort($sorted);

        $this->assertSame($sorted, $timestamps);
    }

    public function test_limit_and_offset(): void
    {
        $this->seedAudits();

        $total = Audit::count();
        $this->assertGreaterThan(2, $total);

        $page1 = (new AuditQuery())->latest()->limit(2)->offset(0)->builder()->get();
        $page2 = (new AuditQuery())->latest()->limit(2)->offset(2)->builder()->get();

        $this->assertCount(2, $page1);
        $this->assertFalse($page1->pluck('id')->intersect($page2->pluck('id'))->isNotEmpty());
    }
}
