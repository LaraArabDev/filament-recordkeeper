<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Actions;

use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;
use LaraArabDev\Recordkeeper\Models\Audit;

final class RecordAudit
{
    public function handle(AuditPayload $payload): Audit
    {
        return $this->__invoke($payload);
    }

    public function __invoke(AuditPayload $payload): Audit
    {
        $audit = new Audit;
        $audit->fill($payload->toArray());
        $audit->save();

        return $audit;
    }
}
