<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Unit;

use LaraArabDev\Recordkeeper\Modifiers\RedactAttribute;
use LaraArabDev\Recordkeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RedactAttributeTest extends TestCase
{
    #[Test]
    public function redact_returns_configured_mask(): void
    {
        config(['recordkeeper.privacy.mask' => '***']);

        $this->assertSame('***', RedactAttribute::redact('any-value'));
    }

    #[Test]
    public function redact_uses_custom_mask(): void
    {
        config(['recordkeeper.privacy.mask' => '[REDACTED]']);

        $this->assertSame('[REDACTED]', RedactAttribute::redact('secret'));
    }

    #[Test]
    public function redact_hides_any_value_type(): void
    {
        config(['recordkeeper.privacy.mask' => '***']);

        $this->assertSame('***', RedactAttribute::redact(12345));
        $this->assertSame('***', RedactAttribute::redact(null));
        $this->assertSame('***', RedactAttribute::redact(['nested' => 'array']));
    }
}
