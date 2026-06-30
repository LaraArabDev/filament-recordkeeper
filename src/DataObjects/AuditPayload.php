<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\DataObjects;

final class AuditPayload
{
    public function __construct(
        public string $event,
        public string $auditableType,
        public int|string|null $auditableId,
        public array $oldValues,
        public array $newValues,
        public ?string $userType = null,
        public int|string|null $userId = null,
        public ?string $url = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $tags = null,
        public ?string $batchId = null,
        public array $context = [],
    ) {}

    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'auditable_type' => $this->auditableType,
            'auditable_id' => $this->auditableId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'user_type' => $this->userType,
            'user_id' => $this->userId,
            'url' => $this->url,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'tags' => $this->tags,
            'batch_id' => $this->batchId,
            'context' => $this->context,
        ];
    }
}
