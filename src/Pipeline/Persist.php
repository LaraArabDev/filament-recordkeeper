<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Pipeline;

use Closure;
use LaraArabDev\Recordkeeper\Actions\RecordAudit;
use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;
use LaraArabDev\Recordkeeper\Events\ChangeRecorded;
use LaraArabDev\Recordkeeper\Models\Audit;

final class Persist
{
    public function __construct(
        private readonly RecordAudit $recordAudit,
    ) {}

    /**
     * @param  AuditPayload  $payload
     * @param  Closure       $next
     * @return mixed
     */
    public function handle(AuditPayload $payload, Closure $next): mixed
    {
        $audit = ($this->recordAudit)($payload);

        event(new ChangeRecorded($audit));

        return $next($audit);
    }
}
