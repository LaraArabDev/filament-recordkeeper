<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Modifiers;

use Illuminate\Support\Facades\Crypt;
use OwenIt\Auditing\Contracts\AttributeRedactor;

final class EncryptAttribute implements AttributeRedactor
{
    private const PREFIX = '__encrypted:';

    public static function redact(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::PREFIX.Crypt::encryptString((string) $value);
    }

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    public static function decrypt(string $value): string
    {
        if (! self::isEncrypted($value)) {
            return $value;
        }

        return Crypt::decryptString(substr($value, strlen(self::PREFIX)));
    }
}
