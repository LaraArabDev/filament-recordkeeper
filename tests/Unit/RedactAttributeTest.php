<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Unit;

use LaraArabDev\Recordkeeper\Modifiers\RedactAttribute;
use LaraArabDev\Recordkeeper\Tests\TestCase;

class RedactAttributeTest extends TestCase
{
    public function test_redact_returns_configured_mask(): void
    {
        config(['recordkeeper.privacy.mask' => '***']);

        $this->assertSame('***', RedactAttribute::redact('any-value'));
    }

    public function test_redact_uses_custom_mask(): void
    {
        config(['recordkeeper.privacy.mask' => '[REDACTED]']);

        $this->assertSame('[REDACTED]', RedactAttribute::redact('secret'));
    }

    public function test_redact_hides_any_value_type(): void
    {
        config(['recordkeeper.privacy.mask' => '***']);

        $this->assertSame('***', RedactAttribute::redact(12345));
        $this->assertSame('***', RedactAttribute::redact(null));
        $this->assertSame('***', RedactAttribute::redact(['nested' => 'array']));
    }
}
