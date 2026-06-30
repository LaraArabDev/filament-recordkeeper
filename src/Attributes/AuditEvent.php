<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class AuditEvent
{
    public function __construct(
        public readonly array $tags = [],
        public readonly bool $capturePayload = false,
    ) {}
}
