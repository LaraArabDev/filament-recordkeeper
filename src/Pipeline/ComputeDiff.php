<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Pipeline;

use Closure;
use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;

final class ComputeDiff
{
    public function handle(AuditPayload $payload, Closure $next): mixed
    {
        return $next($payload);
    }
}
