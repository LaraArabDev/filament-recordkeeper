<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface AuditQueryContract
{
    public function model(string $type): static;

    public function subjectId(int|string $id): static;

    public function event(string|array $event): static;

    public function actor(int|string $userId, ?string $userType = null): static;

    public function actorType(string $userType): static;

    public function guard(string $guard): static;

    public function tag(string|array $tags): static;

    public function batch(string $batchId): static;

    public function between(\DateTimeInterface|string $from, \DateTimeInterface|string $until): static;

    public function search(string $term): static;

    public function builder(): Builder;
}
