<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Audit
{
    /**
     * @param  ?string  $tag
     * @param  bool     $body
     * @param  bool     $response
     * @param  float    $sample
     */
    public function __construct(
        public readonly ?string $tag = null,
        public readonly bool $body = false,
        public readonly bool $response = false,
        public readonly float $sample = 1.0,
    ) {}
}
