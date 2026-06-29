<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Actions;

use LaraArabDev\Recordkeeper\Modifiers\RedactAttribute;

final class RedactValues
{
    /**
     * @param  array  $values
     * @param  array  $patterns
     * @return array
     */
    public function handle(array $values, array $patterns = []): array
    {
        return $this->__invoke($values, $patterns);
    }

    /**
     * @param  array  $values
     * @param  array  $patterns
     * @return array
     */
    public function __invoke(array $values, array $patterns = []): array
    {
        if (empty($patterns)) {
            $patterns = config('recordkeeper.privacy.sensitive_patterns', []);
        }

        $result = [];
        foreach ($values as $key => $value) {
            $redact = false;
            foreach ($patterns as $pattern) {
                if (str_contains(strtolower((string) $key), strtolower($pattern))) {
                    $redact = true;
                    break;
                }
            }
            $result[$key] = $redact ? RedactAttribute::redact($value) : $value;
        }

        return $result;
    }
}
