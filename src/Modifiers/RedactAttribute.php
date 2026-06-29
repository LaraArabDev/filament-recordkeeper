<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Modifiers;

use OwenIt\Auditing\Contracts\AttributeRedactor;

final class RedactAttribute implements AttributeRedactor
{
    /**
     * @param  mixed  $value
     * @return string
     */
    public static function redact(mixed $value): string
    {
        return (string) config('recordkeeper.privacy.mask', '***');
    }
}
