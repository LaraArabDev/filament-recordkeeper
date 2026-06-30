<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Redact
{
    public readonly array $attributes;

    public function __construct(string ...$attributes)
    {
        $this->attributes = $attributes;
    }
}
