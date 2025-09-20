<?php

namespace Tests\Unit\Domain;

use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;

/**
 * Test Domain Layer User Entity
 *
 * Tests business logic and domain invariants.
 */
class UserEntityTest extends TestCase
{
    public function test_can_create_user_entity(): void
    {
        $userId = new UserId(1);
        $email = new Email('test@example.com');
        $name = 'John Doe';

        $user = new User($userId, $name, $email);

        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($email, $user->getEmail());
        $this->assertFalse($user->isEmailVerified());
        $this->assertNull($user->getEmailVerifiedAt());
    }

    public function test_can_verify_email(): void
    {
        $user = new User(
            new UserId(1),
            'John Doe',
            new Email('test@example.com')
        );

        $this->assertFalse($user->isEmailVerified());

        $user->verifyEmail();

        $this->assertTrue($user->isEmailVerified());
        $this->assertNotNull($user->getEmailVerifiedAt());
    }

    public function test_cannot_verify_already_verified_email(): void
    {
        $user = new User(
            new UserId(1),
            'John Doe',
            new Email('test@example.com'),
            new \DateTime() // Already verified
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email is already verified');

        $user->verifyEmail();
    }

    public function test_can_update_name(): void
    {
        $user = new User(
            new UserId(1),
            'John Doe',
            new Email('test@example.com')
        );

        $newName = 'Jane Smith';
        $user->updateName($newName);

        $this->assertEquals($newName, $user->getName());
    }

    public function test_cannot_set_empty_name(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User name cannot be empty');

        new User(
            new UserId(1),
            '',
            new Email('test@example.com')
        );
    }

    public function test_can_update_email_resets_verification(): void
    {
        $user = new User(
            new UserId(1),
            'John Doe',
            new Email('test@example.com'),
            new \DateTime() // Already verified
        );

        $this->assertTrue($user->isEmailVerified());

        $newEmail = new Email('new@example.com');
        $user->updateEmail($newEmail);

        $this->assertEquals($newEmail, $user->getEmail());
        $this->assertFalse($user->isEmailVerified()); // Verification reset
    }

    public function test_to_array_returns_correct_format(): void
    {
        $user = new User(
            new UserId(1),
            'John Doe',
            new Email('test@example.com')
        );

        $array = $user->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals('John Doe', $array['name']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertNull($array['email_verified_at']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }
}
