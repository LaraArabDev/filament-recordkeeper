<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Actions;

use Illuminate\Database\Eloquent\Collection;
use LaraArabDev\Recordkeeper\Support\AuditQuery;

final class SearchAudits
{
    public function handle(array $filters = [], int $limit = 25, int $offset = 0): Collection
    {
        return $this->__invoke($filters, $limit, $offset);
    }

    public function __invoke(array $filters = [], int $limit = 25, int $offset = 0): Collection
    {
        $query = new AuditQuery;

        if (! empty($filters['model'])) {
            $query->model($filters['model']);
        }
        if (! empty($filters['subject_id'])) {
            $query->subjectId($filters['subject_id']);
        }
        if (! empty($filters['event'])) {
            $query->event($filters['event']);
        }
        if (! empty($filters['user'])) {
            $query->actor($filters['user'], $filters['user_type'] ?? null);
        } elseif (! empty($filters['user_type'])) {
            $query->actorType($filters['user_type']);
        }
        if (! empty($filters['guard'])) {
            $query->guard($filters['guard']);
        }
        if (! empty($filters['tag'])) {
            $query->tag($filters['tag']);
        }
        if (! empty($filters['batch'])) {
            $query->batch($filters['batch']);
        }
        if (! empty($filters['since'])) {
            $query->between($filters['since'], $filters['until'] ?? now());
        }
        if (! empty($filters['q'])) {
            $query->search($filters['q']);
        }

        return $query->latest()->limit($limit)->offset($offset)->builder()->get();
    }
}
