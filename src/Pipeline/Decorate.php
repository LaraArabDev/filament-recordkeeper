<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Pipeline;

use Closure;
use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;
use LaraArabDev\Recordkeeper\Recordkeeper;

final class Decorate
{
    public function __construct(
        private readonly Recordkeeper $recordkeeper,
    ) {}

    public function handle(AuditPayload $payload, Closure $next): mixed
    {
        if ($payload->batchId === null) {
            $payload->batchId = $this->recordkeeper->currentBatchId();
        }

        return $next($payload);
    }
}
