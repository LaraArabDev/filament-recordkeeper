<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Feature;

use Illuminate\Support\Facades\Event;
use LaraArabDev\Recordkeeper\Events\ChangeRecorded;
use LaraArabDev\Recordkeeper\Facades\Recordkeeper;
use LaraArabDev\Recordkeeper\Support\AttributeResolver;
use LaraArabDev\Recordkeeper\Tests\TestCase;

class ChangeRecordedEventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AttributeResolver::clearCache();
    }

    public function test_change_recorded_event_is_dispatched_on_log(): void
    {
        Event::fake([ChangeRecorded::class]);

        Recordkeeper::log('test.event', null, ['key' => 'value']);

        Event::assertDispatched(ChangeRecorded::class, function (ChangeRecorded $event): bool {
            return $event->audit->event === 'test.event';
        });
    }

    public function test_change_recorded_event_carries_audit_instance(): void
    {
        Event::fake([ChangeRecorded::class]);

        Recordkeeper::log('audit.check');

        Event::assertDispatched(ChangeRecorded::class, function (ChangeRecorded $event): bool {
            return $event->audit !== null && $event->audit->id !== null;
        });
    }
}
