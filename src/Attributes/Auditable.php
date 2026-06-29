<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Auditable
{
    /**
     * @param  ?array  $events
     * @param  array   $only
     * @param  array   $exclude
     * @param  array   $redact
     * @param  array   $encrypt
     * @param  ?int    $retentionDays
     * @param  ?int    $threshold
     * @param  array   $tags
     */
    public function __construct(
        public readonly ?array $events = null,
        public readonly array $only = [],
        public readonly array $exclude = [],
        public readonly array $redact = [],
        public readonly array $encrypt = [],
        public readonly ?int $retentionDays = null,
        public readonly ?int $threshold = null,
        public readonly array $tags = [],
    ) {}
}
