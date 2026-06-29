<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Support;

use Illuminate\Database\Eloquent\Builder;
use LaraArabDev\Recordkeeper\Contracts\AuditQueryContract;
use LaraArabDev\Recordkeeper\Models\Audit;

final class AuditQuery implements AuditQueryContract
{
    /** @var Builder */
    private Builder $query;

    /** @return void */
    public function __construct()
    {
        $this->query = Audit::query()->with(['auditable']);
    }

    /**
     * @param  string  $type
     * @return static
     */
    public function model(string $type): static
    {
        if (! str_contains($type, '\\')) {
            $this->query->where('auditable_type', 'like', '%\\' . $type);
        } else {
            $this->query->where('auditable_type', $type);
        }

        return $this;
    }

    /**
     * @param  int|string  $id
     * @return static
     */
    public function subjectId(int|string $id): static
    {
        $this->query->where('auditable_id', $id);

        return $this;
    }

    /**
     * @param  string|array  $event
     * @return static
     */
    public function event(string|array $event): static
    {
        $this->query->whereIn('event', (array) $event);

        return $this;
    }

    /** @return static */
    public function rollbackable(): static
    {
        $this->query->whereIn('event', ['created', 'updated', 'deleted', 'restored']);

        return $this;
    }

    /**
     * @param  int|string  $userId
     * @param  ?string     $userType
     * @return static
     */
    public function actor(int|string $userId, ?string $userType = null): static
    {
        $this->query->where('user_id', $userId);

        if ($userType !== null) {
            if (! str_contains($userType, '\\')) {
                $this->query->where('user_type', 'like', '%\\' . $userType);
            } else {
                $this->query->where('user_type', $userType);
            }
        }

        return $this;
    }

    /**
     * @param  string  $userType
     * @return static
     */
    public function actorType(string $userType): static
    {
        if (! str_contains($userType, '\\')) {
            $this->query->where('user_type', 'like', '%\\' . $userType);
        } else {
            $this->query->where('user_type', $userType);
        }

        return $this;
    }

    /** @return static */
    public function onlyAuthenticated(): static
    {
        $this->query->whereNotNull('user_id');

        return $this;
    }

    /**
     * @param  string  $guard
     * @return static
     */
    public function guard(string $guard): static
    {
        $this->query->where('guard', $guard);

        return $this;
    }

    /**
     * @param  string|array  $tags
     * @return static
     */
    public function tag(string|array $tags): static
    {
        foreach ((array) $tags as $tag) {
            $this->query->where('tags', 'like', '%' . $tag . '%');
        }

        return $this;
    }

    /**
     * @param  string  $batchId
     * @return static
     */
    public function batch(string $batchId): static
    {
        $this->query->where('batch_id', $batchId);

        return $this;
    }

    /**
     * @param  \DateTimeInterface|string  $from
     * @param  \DateTimeInterface|string  $until
     * @return static
     */
    public function between(\DateTimeInterface|string $from, \DateTimeInterface|string $until): static
    {
        $this->query->whereBetween('created_at', [$from, $until]);

        return $this;
    }

    /**
     * @param  \DateTimeInterface|string  $from
     * @return static
     */
    public function since(\DateTimeInterface|string $from): static
    {
        $this->query->where('created_at', '>=', $from);

        return $this;
    }

    /**
     * @param  string  $term
     * @return static
     */
    public function search(string $term): static
    {
        $this->query->where(function (Builder $q) use ($term): void {
            $q->where('event', 'like', '%' . $term . '%')
              ->orWhere('auditable_type', 'like', '%' . $term . '%')
              ->orWhere('batch_id', 'like', '%' . $term . '%')
              ->orWhere('user_id', 'like', '%' . $term . '%');
        });

        return $this;
    }

    /** @return static */
    public function latest(): static
    {
        $this->query->latest('created_at');

        return $this;
    }

    /**
     * @param  int  $limit
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->query->limit($limit);

        return $this;
    }

    /**
     * @param  int  $offset
     * @return static
     */
    public function offset(int $offset): static
    {
        $this->query->offset($offset);

        return $this;
    }

    /** @return static */
    public function jobs(): static
    {
        $this->query->where('event', 'like', 'job.%');

        return $this;
    }

    /** @return static */
    public function commands(): static
    {
        $this->query->where('event', 'like', 'command.%');

        return $this;
    }

    /** @return static */
    public function events(): static
    {
        $this->query->where('event', 'like', 'event.%');

        return $this;
    }

    /** @return Builder */
    public function builder(): Builder
    {
        return $this->query;
    }
}
