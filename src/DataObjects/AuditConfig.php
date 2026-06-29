<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\DataObjects;

final readonly class AuditConfig
{
    /**
     * @param  array  $auditInclude
     * @param  array  $auditExclude
     * @param  array  $auditEvents
     * @param  array  $attributeModifiers
     * @param  int    $auditThreshold
     * @param  array  $auditTags
     * @param  int    $retentionDays
     */
    public function __construct(
        public array $auditInclude,
        public array $auditExclude,
        public array $auditEvents,
        public array $attributeModifiers,
        public int $auditThreshold,
        public array $auditTags,
        public int $retentionDays,
    ) {}
}
