<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Concerns;

use LaraArabDev\Recordkeeper\Recordkeeper;
use LaraArabDev\Recordkeeper\Support\AttributeResolver;

trait AuditsChanges
{
    use \OwenIt\Auditing\Auditable;

    /** @var array */
    protected array $auditInclude = [];

    /** @var array */
    protected array $auditExclude = [];

    /** @var array */
    protected array $auditEvents = [];

    /** @var array */
    protected array $attributeModifiers = [];

    /** @var int */
    protected int $auditThreshold = 0;

    /** @return void */
    public function initializeAuditsChanges(): void
    {
        $resolved = AttributeResolver::resolve($this);

        $this->auditInclude       = $resolved->auditInclude;
        $this->auditExclude       = $resolved->auditExclude;
        $this->auditEvents        = $resolved->auditEvents;
        $this->attributeModifiers = $resolved->attributeModifiers;
        $this->auditThreshold     = $resolved->auditThreshold;
    }

    /** @return array */
    public function generateTags(): array
    {
        $resolved = AttributeResolver::resolve($this);

        return array_merge(
            $resolved->auditTags,
            app(Recordkeeper::class)->currentTags(),
        );
    }

    /**
     * @param  array  $data
     * @return array
     */
    public function transformAudit(array $data): array
    {
        $data['tags'] = implode(',', $this->generateTags());

        $privacyMode = config('recordkeeper.privacy.mode', 'redact');

        if ($privacyMode !== 'off') {
            $patterns = config('recordkeeper.privacy.sensitive_patterns', []);

            if (! empty($patterns)) {
                foreach (['new_values', 'old_values'] as $key) {
                    if (! is_array($data[$key] ?? null)) {
                        continue;
                    }
                    foreach ($data[$key] as $attr => $value) {
                        if (isset($this->attributeModifiers[$attr])) {
                            continue;
                        }
                        foreach ($patterns as $pattern) {
                            if (str_contains(strtolower((string) $attr), strtolower($pattern))) {
                                $data[$key][$attr] = '***';
                                break;
                            }
                        }
                    }
                }
            }
        }

        return app(Recordkeeper::class)->decorate($data);
    }

    /**
     * @param  array  $context
     * @return static
     */
    public function auditContext(array $context): static
    {
        app(Recordkeeper::class)->pushContext($context);

        return $this;
    }
}
