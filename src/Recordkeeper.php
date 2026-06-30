<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper;

use Closure;
use Illuminate\Database\Eloquent\Model;
use LaraArabDev\Recordkeeper\Actions\RecordAudit;
use LaraArabDev\Recordkeeper\Actions\RevertAudit;
use LaraArabDev\Recordkeeper\Actions\RevertBatch;
use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;
use LaraArabDev\Recordkeeper\Events\ChangeRecorded;
use LaraArabDev\Recordkeeper\Models\Audit;

class Recordkeeper
{
    private ?string $currentBatchId = null;

    private array $currentTags = [];

    private array $context = [];

    private ?Closure $actorResolver = null;

    public function batch(string $id, Closure $callback): mixed
    {
        $previous = $this->currentBatchId;
        $this->currentBatchId = $id;

        try {
            return $callback();
        } finally {
            $this->currentBatchId = $previous;
        }
    }

    public function currentBatchId(): ?string
    {
        return $this->currentBatchId;
    }

    public function currentTags(): array
    {
        return $this->currentTags;
    }

    public function withTags(array $tags): static
    {
        $this->currentTags = $tags;

        return $this;
    }

    public function decorate(array $auditRow): array
    {
        if ($this->currentBatchId !== null) {
            $auditRow['batch_id'] = $this->currentBatchId;
        }

        if (! empty($this->context)) {
            $existing = $auditRow['context'] ?? [];
            if (is_string($existing)) {
                $existing = json_decode($existing, true) ?? [];
            }
            $auditRow['context'] = array_merge((array) $existing, $this->context);
        }

        return $auditRow;
    }

    public function pushContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    public function clearContext(): void
    {
        $this->context = [];
    }

    public function log(string $event, ?Model $subject = null, array $context = []): Audit
    {
        $payload = new AuditPayload(
            event: $event,
            auditableType: $subject ? $subject::class : 'system',
            auditableId: $subject?->getKey(),
            oldValues: [],
            newValues: $context,
            batchId: $this->currentBatchId,
            context: array_merge($this->context, $context),
        );

        $audit = app(RecordAudit::class)($payload);

        ChangeRecorded::dispatch($audit);

        return $audit;
    }

    public function rollback(int|string|Audit $auditOrId, bool $dryRun = false): mixed
    {
        $audit = $auditOrId instanceof Audit ? $auditOrId : Audit::findOrFail($auditOrId);

        return app(RevertAudit::class)->handle($audit, $dryRun);
    }

    public function rollbackBatch(string $id, bool $dryRun = false): array
    {
        return app(RevertBatch::class)->handle($id, $dryRun);
    }

    public function resolveActorUsing(Closure $resolver): static
    {
        $this->actorResolver = $resolver;

        return $this;
    }

    public function resolveActor(): mixed
    {
        if ($this->actorResolver !== null) {
            return ($this->actorResolver)();
        }

        return auth()->user();
    }

    public function isEnabled(): bool
    {
        return (bool) config('recordkeeper.enabled', true);
    }
}
