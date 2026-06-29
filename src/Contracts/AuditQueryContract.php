<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface AuditQueryContract
{
    /**
     * @param  string  $type
     * @return static
     */
    public function model(string $type): static;

    /**
     * @param  int|string  $id
     * @return static
     */
    public function subjectId(int|string $id): static;

    /**
     * @param  string|array  $event
     * @return static
     */
    public function event(string|array $event): static;

    /**
     * @param  int|string  $userId
     * @param  ?string     $userType
     * @return static
     */
    public function actor(int|string $userId, ?string $userType = null): static;

    /**
     * @param  string  $userType
     * @return static
     */
    public function actorType(string $userType): static;

    /**
     * @param  string  $guard
     * @return static
     */
    public function guard(string $guard): static;

    /**
     * @param  string|array  $tags
     * @return static
     */
    public function tag(string|array $tags): static;

    /**
     * @param  string  $batchId
     * @return static
     */
    public function batch(string $batchId): static;

    /**
     * @param  \DateTimeInterface|string  $from
     * @param  \DateTimeInterface|string  $until
     * @return static
     */
    public function between(\DateTimeInterface|string $from, \DateTimeInterface|string $until): static;

    /**
     * @param  string  $term
     * @return static
     */
    public function search(string $term): static;

    /** @return Builder */
    public function builder(): Builder;
}
