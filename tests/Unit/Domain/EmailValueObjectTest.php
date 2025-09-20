<?php

namespace Tests\Unit\Domain;

use App\Domain\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

class EmailValueObjectTest extends TestCase
{
    public function test_can_create_valid_email(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', $email->getValue());
        $this->assertEquals('example.com', $email->getDomain());
        $this->assertEquals('test', $email->getLocalPart());
    }

    public function test_email_is_normalized_to_lowercase(): void
    {
        $email = new Email('Test@EXAMPLE.COM');

        $this->assertEquals('test@example.com', $email->getValue());
    }

    public function test_cannot_create_empty_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email cannot be empty');

        new Email('');
    }

    public function test_cannot_create_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        new Email('invalid-email');
    }

    public function test_cannot_create_email_too_long(): void
    {
        // Create an email that's definitely too long (over 255 chars)
        $longEmail = str_repeat('a', 250) . '@example.com'; // This will be 261 chars

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email cannot exceed 255 characters');

        new Email($longEmail);
    }

    public function test_emails_can_be_compared(): void
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');
        $email3 = new Email('other@example.com');

        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function test_email_to_string(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', (string) $email);
    }
}
