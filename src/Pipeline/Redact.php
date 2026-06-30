<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Pipeline;

use Closure;
use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;

final class Redact
{
    public function handle(AuditPayload $payload, Closure $next): mixed
    {
        $patterns = config('recordkeeper.privacy.sensitive_patterns', []);
        $mask = config('recordkeeper.privacy.mask', '***');

        $payload->newValues = $this->redactValues($payload->newValues, $patterns, $mask);

        return $next($payload);
    }

    private function redactValues(array $values, array $patterns, string $mask): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $shouldRedact = false;
            foreach ($patterns as $pattern) {
                if (str_contains(strtolower((string) $key), strtolower($pattern))) {
                    $shouldRedact = true;
                    break;
                }
            }
            $result[$key] = $shouldRedact ? $mask : $value;
        }

        return $result;
    }
}
