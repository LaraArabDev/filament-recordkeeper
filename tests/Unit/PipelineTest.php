<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Unit;

use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;
use LaraArabDev\Recordkeeper\Events\ChangeRecorded;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Pipeline\ComputeDiff;
use LaraArabDev\Recordkeeper\Pipeline\Decorate;
use LaraArabDev\Recordkeeper\Pipeline\Encrypt;
use LaraArabDev\Recordkeeper\Pipeline\Persist;
use LaraArabDev\Recordkeeper\Pipeline\Redact;
use LaraArabDev\Recordkeeper\Pipeline\Tag;
use LaraArabDev\Recordkeeper\Recordkeeper;
use LaraArabDev\Recordkeeper\Tests\TestCase;

class PipelineTest extends TestCase
{
    private function payload(array $overrides = []): AuditPayload
    {
        return new AuditPayload(
            event: $overrides['event'] ?? 'created',
            auditableType: $overrides['auditableType'] ?? 'Order',
            auditableId: $overrides['auditableId'] ?? 1,
            oldValues: $overrides['oldValues'] ?? [],
            newValues: $overrides['newValues'] ?? ['status' => 'pending'],
            batchId: $overrides['batchId'] ?? null,
        );
    }

    public function test_tag_passes_payload_through_unchanged(): void
    {
        $stage = new Tag;
        $payload = $this->payload();
        $received = null;

        $stage->handle($payload, function ($p) use (&$received) {
            $received = $p;

            return $p;
        });

        $this->assertSame($payload, $received);
    }

    public function test_compute_diff_passes_payload_through_unchanged(): void
    {
        $stage = new ComputeDiff;
        $payload = $this->payload();
        $called = false;

        $stage->handle($payload, function ($p) use (&$called) {
            $called = true;

            return $p;
        });

        $this->assertTrue($called);
    }

    public function test_encrypt_passes_payload_through_unchanged(): void
    {
        $stage = app(Encrypt::class);
        $payload = $this->payload();
        $called = false;

        $stage->handle($payload, function ($p) use (&$called) {
            $called = true;

            return $p;
        });

        $this->assertTrue($called);
    }

    public function test_redact_masks_sensitive_pattern_fields(): void
    {
        config(['recordkeeper.privacy.sensitive_patterns' => ['password', 'secret']]);
        config(['recordkeeper.privacy.mask' => '***']);

        $stage = new Redact;
        $payload = $this->payload([
            'newValues' => ['password' => 'hunter2', 'status' => 'ok', 'user_secret' => 'xyz'],
        ]);

        $stage->handle($payload, fn ($p) => $p);

        $this->assertSame('***', $payload->newValues['password']);
        $this->assertSame('***', $payload->newValues['user_secret']);
        $this->assertSame('ok', $payload->newValues['status']);
    }

    public function test_redact_uses_custom_mask_string(): void
    {
        config(['recordkeeper.privacy.sensitive_patterns' => ['token']]);
        config(['recordkeeper.privacy.mask' => '[HIDDEN]']);

        $stage = new Redact;
        $payload = $this->payload(['newValues' => ['token' => 'abc123']]);

        $stage->handle($payload, fn ($p) => $p);

        $this->assertSame('[HIDDEN]', $payload->newValues['token']);
    }

    public function test_redact_leaves_payload_unchanged_when_no_patterns(): void
    {
        config(['recordkeeper.privacy.sensitive_patterns' => []]);

        $stage = new Redact;
        $payload = $this->payload(['newValues' => ['status' => 'active', 'total' => 99]]);

        $stage->handle($payload, fn ($p) => $p);

        $this->assertSame('active', $payload->newValues['status']);
        $this->assertSame(99, $payload->newValues['total']);
    }

    public function test_decorate_injects_batch_id_when_payload_has_none(): void
    {
        $rk = app(Recordkeeper::class);
        $rk->batch('my-batch', function () use ($rk): void {
            $stage = new Decorate($rk);
            $payload = $this->payload(['batchId' => null]);

            $stage->handle($payload, fn ($p) => $p);

            $this->assertSame('my-batch', $payload->batchId);
        });
    }

    public function test_decorate_does_not_overwrite_existing_batch_id(): void
    {
        $rk = app(Recordkeeper::class);
        $rk->batch('global-batch', function () use ($rk): void {
            $stage = new Decorate($rk);
            $payload = $this->payload(['batchId' => 'specific-batch']);

            $stage->handle($payload, fn ($p) => $p);

            $this->assertSame('specific-batch', $payload->batchId);
        });
    }

    public function test_persist_saves_audit_and_fires_change_recorded(): void
    {
        $fired = [];
        $this->app['events']->listen(ChangeRecorded::class, function ($e) use (&$fired): void {
            $fired[] = $e->audit;
        });

        $stage = app(Persist::class);
        $payload = $this->payload();

        $result = $stage->handle($payload, fn ($audit) => $audit);

        $this->assertInstanceOf(Audit::class, $result);
        $this->assertDatabaseHas('audits', ['event' => 'created']);
        $this->assertCount(1, $fired);
        $this->assertInstanceOf(Audit::class, $fired[0]);
    }
}
