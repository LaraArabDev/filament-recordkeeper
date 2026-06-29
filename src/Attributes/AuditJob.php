<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class AuditJob
{
    /**
     * @param  bool   $queued
     * @param  bool   $processing
     * @param  bool   $processed
     * @param  bool   $failed
     * @param  array  $tags
     */
    public function __construct(
        public readonly bool $queued = true,
        public readonly bool $processing = true,
        public readonly bool $processed = true,
        public readonly bool $failed = true,
        public readonly array $tags = [],
    ) {}
}
