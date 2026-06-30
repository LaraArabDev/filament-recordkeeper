<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Auditable
{
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
