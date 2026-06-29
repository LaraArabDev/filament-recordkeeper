<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Contracts;

use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;
use LaraArabDev\Recordkeeper\Models\Audit;

interface AuditDriver
{
    /**
     * @param  AuditPayload  $payload
     * @return Audit
     */
    public function persist(AuditPayload $payload): Audit;
}
