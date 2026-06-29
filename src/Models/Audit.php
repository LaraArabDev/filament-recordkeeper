<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Models\Audit as BaseAudit;

class Audit extends BaseAudit
{
    use MassPrunable;

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'context'    => 'array',
    ];

    protected $guarded = [];

    /**
     * @param  Builder  $query
     * @param  string   $guard
     * @return Builder
     */
    public function scopeForGuard(Builder $query, string $guard): Builder
    {
        return $query->where('guard', $guard);
    }

    /**
     * @param  Builder  $query
     * @param  string   $model
     * @return Builder
     */
    public function scopeForModel(Builder $query, string $model): Builder
    {
        if (! str_contains($model, '\\')) {
            return $query->where('auditable_type', 'like', '%\\' . $model);
        }

        return $query->where('auditable_type', $model);
    }

    /**
     * @param  Builder                              $query
     * @param  \Illuminate\Database\Eloquent\Model  $subject
     * @return Builder
     */
    public function scopeForSubject(Builder $query, \Illuminate\Database\Eloquent\Model $subject): Builder
    {
        return $query
            ->where('auditable_type', $subject::class)
            ->where('auditable_id', $subject->getKey());
    }

    /**
     * @param  Builder  $query
     * @param  string   $userType
     * @return Builder
     */
    public function scopeForActorType(Builder $query, string $userType): Builder
    {
        if (! str_contains($userType, '\\')) {
            return $query->where('user_type', 'like', '%\\' . $userType);
        }

        return $query->where('user_type', $userType);
    }

    /**
     * @param  Builder                                                   $query
     * @param  \Illuminate\Database\Eloquent\Model|int|string            $actor
     * @param  ?string                                                    $userType
     * @return Builder
     */
    public function scopeForActor(Builder $query, \Illuminate\Database\Eloquent\Model|int|string $actor, ?string $userType = null): Builder
    {
        if ($actor instanceof \Illuminate\Database\Eloquent\Model) {
            return $query
                ->where('user_type', $actor::class)
                ->where('user_id', $actor->getKey());
        }

        $query->where('user_id', $actor);

        if ($userType !== null) {
            $query->where('user_type', str_contains($userType, '\\') ? $userType : '%\\' . $userType);
        }

        return $query;
    }

    /**
     * @param  Builder  $query
     * @param  string   $batchId
     * @return Builder
     */
    public function scopeForBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeRollbackable(Builder $query): Builder
    {
        return $query->whereIn('event', ['created', 'updated', 'deleted', 'restored']);
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeRouteHits(Builder $query): Builder
    {
        return $query->where('event', 'like', 'route.%');
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeJobAudits(Builder $query): Builder
    {
        return $query->where('event', 'like', 'job.%');
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeCommandAudits(Builder $query): Builder
    {
        return $query->where('event', 'like', 'command.%');
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeEventAudits(Builder $query): Builder
    {
        return $query->where('event', 'like', 'event.%');
    }

    /** @return Builder */
    public function prunable(): Builder
    {
        $days = (int) config('recordkeeper.retention.default_days', 0);

        if ($days <= 0) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('created_at', '<', Carbon::now()->subDays($days));
    }

    /**
     * @param  bool  $dryRun
     * @return mixed
     */
    public function rollback(bool $dryRun = false): mixed
    {
        return app(\LaraArabDev\Recordkeeper\Support\Rollback::class)->revert($this, $dryRun);
    }

    /** @return HasMany */
    public function httpRequests(): HasMany
    {
        return $this->hasMany(AuditHttpRequest::class);
    }

    /** @return bool */
    public function isRollbackable(): bool
    {
        return in_array($this->event, ['created', 'updated', 'deleted', 'restored'], true);
    }
}
