<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LaraArabDev\Recordkeeper\Support\Rollback;
use OwenIt\Auditing\Models\Audit as BaseAudit;

class Audit extends BaseAudit
{
    use MassPrunable;

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'context' => 'array',
    ];

    protected $guarded = [];

    public function scopeForGuard(Builder $query, string $guard): Builder
    {
        return $query->where('guard', $guard);
    }

    public function scopeForModel(Builder $query, string $model): Builder
    {
        if (! str_contains($model, '\\')) {
            return $query->where('auditable_type', 'like', '%\\'.$model);
        }

        return $query->where('auditable_type', $model);
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('auditable_type', $subject::class)
            ->where('auditable_id', $subject->getKey());
    }

    public function scopeForActorType(Builder $query, string $userType): Builder
    {
        if (! str_contains($userType, '\\')) {
            return $query->where('user_type', 'like', '%\\'.$userType);
        }

        return $query->where('user_type', $userType);
    }

    public function scopeForActor(Builder $query, Model|int|string $actor, ?string $userType = null): Builder
    {
        if ($actor instanceof Model) {
            return $query
                ->where('user_type', $actor::class)
                ->where('user_id', $actor->getKey());
        }

        $query->where('user_id', $actor);

        if ($userType !== null) {
            $query->where('user_type', str_contains($userType, '\\') ? $userType : '%\\'.$userType);
        }

        return $query;
    }

    public function scopeForBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeRollbackable(Builder $query): Builder
    {
        return $query->whereIn('event', ['created', 'updated', 'deleted', 'restored']);
    }

    public function scopeRouteHits(Builder $query): Builder
    {
        return $query->where('event', 'like', 'route.%');
    }

    public function scopeJobAudits(Builder $query): Builder
    {
        return $query->where('event', 'like', 'job.%');
    }

    public function scopeCommandAudits(Builder $query): Builder
    {
        return $query->where('event', 'like', 'command.%');
    }

    public function scopeEventAudits(Builder $query): Builder
    {
        return $query->where('event', 'like', 'event.%');
    }

    public function prunable(): Builder
    {
        $days = (int) config('recordkeeper.retention.default_days', 0);

        if ($days <= 0) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('created_at', '<', Carbon::now()->subDays($days));
    }

    public function rollback(bool $dryRun = false): mixed
    {
        return app(Rollback::class)->revert($this, $dryRun);
    }

    public function httpRequests(): HasMany
    {
        return $this->hasMany(AuditHttpRequest::class);
    }

    public function isRollbackable(): bool
    {
        return in_array($this->event, ['created', 'updated', 'deleted', 'restored'], true);
    }
}
