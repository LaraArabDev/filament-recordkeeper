<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Feature;

use LaraArabDev\Recordkeeper\Attributes\AuditEvent;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Tests\TestCase;

#[AuditEvent(tags: ['user'], capturePayload: true)]
class UserRegistered
{
    public function __construct(public readonly int $userId) {}

    public function toArray(): array
    {
        return ['user_id' => $this->userId];
    }
}

class NonAuditedLaravelEvent {}

final class EventAuditingTest extends TestCase
{
    public function test_event_with_attribute_creates_audit(): void
    {
        event(new UserRegistered(42));

        $audit = Audit::where('event', 'event.UserRegistered')->first();

        $this->assertNotNull($audit);
        $this->assertSame('event', $audit->auditable_type);
        $this->assertSame(UserRegistered::class, $audit->context['event']);
    }

    public function test_event_tags_are_stored(): void
    {
        event(new UserRegistered(42));

        $audit = Audit::where('event', 'event.UserRegistered')->first();

        $this->assertSame('user', $audit->tags);
    }

    public function test_payload_is_captured_when_enabled(): void
    {
        event(new UserRegistered(42));

        $audit = Audit::where('event', 'event.UserRegistered')->first();

        $this->assertArrayHasKey('payload', $audit->context);
        $this->assertSame(42, $audit->context['payload'][0]['user_id']);
    }

    public function test_event_without_attribute_is_not_audited(): void
    {
        event(new NonAuditedLaravelEvent());

        $this->assertSame(0, Audit::where('event', 'like', 'event.%')->count());
    }

    public function test_event_listed_in_config_is_audited(): void
    {
        config(['recordkeeper.listen' => [NonAuditedLaravelEvent::class]]);

        event(new NonAuditedLaravelEvent());

        $this->assertSame(1, Audit::where('event', 'like', 'event.%')->count());
    }

    public function test_illuminate_events_are_ignored(): void
    {
        event('Illuminate\\SomeInternalEvent', []);

        $this->assertSame(0, Audit::where('event', 'like', 'event.%')->count());
    }

    public function test_model_scope_event_audits(): void
    {
        event(new UserRegistered(1));
        Audit::create(['event' => 'updated', 'auditable_type' => 'system', 'old_values' => [], 'new_values' => []]);

        $this->assertSame(1, Audit::eventAudits()->count());
    }

    public function test_audit_query_events_method(): void
    {
        event(new UserRegistered(1));

        $results = app(\LaraArabDev\Recordkeeper\Support\AuditQuery::class)
            ->events()
            ->builder()
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('event.UserRegistered', $results->first()->event);
    }
}
