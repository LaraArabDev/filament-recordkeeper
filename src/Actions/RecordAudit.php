<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Actions;

use LaraArabDev\Recordkeeper\DataObjects\AuditPayload;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Support\AuditDriverManager;

final class RecordAudit
{
    public function __construct(private readonly AuditDriverManager $manager) {}

    public function handle(AuditPayload $payload): Audit
    {
        return $this->__invoke($payload);
    }

    public function __invoke(AuditPayload $payload): Audit
    {
        return $this->manager->driver()->persist($payload);
    }
}
