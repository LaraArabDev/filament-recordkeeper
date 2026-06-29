<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper;

use Closure;
use Illuminate\Database\Eloquent\Model;
use LaraArabDev\Recordkeeper\Actions\RevertAudit;
use LaraArabDev\Recordkeeper\Actions\RevertBatch;
use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;
use LaraArabDev\Recordkeeper\Events\ChangeRecorded;
use LaraArabDev\Recordkeeper\Models\Audit;

class Recordkeeper
{
    /** @var ?string */
    private ?string $currentBatchId = null;

    /** @var array */
    private array $currentTags = [];

    /** @var array */
    private array $context = [];

    /** @var ?Closure */
    private ?Closure $actorResolver = null;

    /**
     * @param  string   $id
     * @param  Closure  $callback
     * @return mixed
     */
    public function batch(string $id, Closure $callback): mixed
    {
        $previous             = $this->currentBatchId;
        $this->currentBatchId = $id;

        try {
            return $callback();
        } finally {
            $this->currentBatchId = $previous;
        }
    }

    /** @return ?string */
    public function currentBatchId(): ?string
    {
        return $this->currentBatchId;
    }

    /** @return array */
    public function currentTags(): array
    {
        return $this->currentTags;
    }

    /**
     * @param  array  $tags
     * @return static
     */
    public function withTags(array $tags): static
    {
        $this->currentTags = $tags;

        return $this;
    }

    /**
     * @param  array  $auditRow
     * @return array
     */
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

    /**
     * @param  array  $context
     * @return void
     */
    public function pushContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    /** @return void */
    public function clearContext(): void
    {
        $this->context = [];
    }

    /**
     * @param  string  $event
     * @param  ?Model  $subject
     * @param  array   $context
     * @return Audit
     */
    public function log(string $event, ?Model $subject = null, array $context = []): Audit
    {
        $payload = new AuditPayload(
            event:         $event,
            auditableType: $subject ? $subject::class : 'system',
            auditableId:   $subject?->getKey(),
            oldValues:     [],
            newValues:     $context,
            batchId:       $this->currentBatchId,
            context:       array_merge($this->context, $context),
        );

        $audit = new Audit();
        $audit->fill($payload->toArray());
        $audit->save();

        ChangeRecorded::dispatch($audit);

        return $audit;
    }

    /**
     * @param  int|string|Audit  $auditOrId
     * @param  bool              $dryRun
     * @return mixed
     */
    public function rollback(int|string|Audit $auditOrId, bool $dryRun = false): mixed
    {
        $audit = $auditOrId instanceof Audit ? $auditOrId : Audit::findOrFail($auditOrId);

        return app(RevertAudit::class)->handle($audit, $dryRun);
    }

    /**
     * @param  string  $id
     * @param  bool    $dryRun
     * @return array
     */
    public function rollbackBatch(string $id, bool $dryRun = false): array
    {
        return app(RevertBatch::class)->handle($id, $dryRun);
    }

    /**
     * @param  Closure  $resolver
     * @return static
     */
    public function resolveActorUsing(Closure $resolver): static
    {
        $this->actorResolver = $resolver;

        return $this;
    }

    /** @return mixed */
    public function resolveActor(): mixed
    {
        if ($this->actorResolver !== null) {
            return ($this->actorResolver)();
        }

        return auth()->user();
    }

    /** @return bool */
    public function isEnabled(): bool
    {
        return (bool) config('recordkeeper.enabled', true);
    }
}
