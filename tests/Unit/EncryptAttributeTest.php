<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Unit;

use LaraArabDev\Recordkeeper\Modifiers\EncryptAttribute;
use LaraArabDev\Recordkeeper\Tests\TestCase;

class EncryptAttributeTest extends TestCase
{
    public function test_redact_returns_encrypted_string_with_prefix(): void
    {
        $result = EncryptAttribute::redact('sensitive-value');

        $this->assertStringStartsWith('__encrypted:', $result);
    }

    public function test_is_encrypted_returns_true_for_encrypted_value(): void
    {
        $encrypted = EncryptAttribute::redact('secret');

        $this->assertTrue(EncryptAttribute::isEncrypted($encrypted));
    }

    public function test_is_encrypted_returns_false_for_plain_value(): void
    {
        $this->assertFalse(EncryptAttribute::isEncrypted('plain-text'));
        $this->assertFalse(EncryptAttribute::isEncrypted('***'));
        $this->assertFalse(EncryptAttribute::isEncrypted(''));
    }

    public function test_decrypt_recovers_original_value(): void
    {
        $original  = 'my-secret-123';
        $encrypted = EncryptAttribute::redact($original);

        $this->assertSame($original, EncryptAttribute::decrypt($encrypted));
    }

    public function test_decrypt_returns_value_unchanged_if_not_encrypted(): void
    {
        $plain = 'not-encrypted';

        $this->assertSame($plain, EncryptAttribute::decrypt($plain));
    }

    public function test_redact_empty_string_returns_empty(): void
    {
        $this->assertSame('', EncryptAttribute::redact(''));
    }

    public function test_redact_null_returns_empty(): void
    {
        $this->assertSame('', EncryptAttribute::redact(null));
    }

    public function test_different_values_produce_different_ciphertext(): void
    {
        $a = EncryptAttribute::redact('value-a');
        $b = EncryptAttribute::redact('value-b');

        $this->assertNotSame($a, $b);
    }

    public function test_does_not_store_plaintext_in_encrypted_output(): void
    {
        $secret    = 'super-secret-ssn-123';
        $encrypted = EncryptAttribute::redact($secret);

        $this->assertStringNotContainsString($secret, $encrypted);
    }
}
