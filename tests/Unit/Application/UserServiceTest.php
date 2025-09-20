<?php

namespace Tests\Unit\Application;

use App\Application\Services\UserService;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class UserServiceTest extends TestCase
{
    private UserRepositoryInterface|MockObject $userRepository;
    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->userService = new UserService($this->userRepository);
    }

    public function test_get_user_by_id(): void
    {
        $userId = new UserId(1);
        $user = new User($userId, 'John Doe', new Email('john@example.com'));

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $result = $this->userService->getUserById(1);

        $this->assertEquals($user, $result);
    }

    public function test_get_user_by_email(): void
    {
        $email = new Email('john@example.com');
        $user = new User(new UserId(1), 'John Doe', $email);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $result = $this->userService->getUserByEmail('john@example.com');

        $this->assertEquals($user, $result);
    }

    public function test_create_user_success(): void
    {
        $email = new Email('john@example.com');
        $user = new User(new UserId(1), 'John Doe', $email);

        $this->userRepository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with($email)
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn($user);

        $result = $this->userService->createUser('John Doe', 'john@example.com');

        $this->assertEquals($user, $result);
    }

    public function test_create_user_fails_when_email_exists(): void
    {
        $email = new Email('john@example.com');

        $this->userRepository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with($email)
            ->willReturn(true);

        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email is already registered');

        $this->userService->createUser('John Doe', 'john@example.com');
    }

    public function test_update_user_name(): void
    {
        $userId = new UserId(1);
        $user = new User($userId, 'John Doe', new Email('john@example.com'));

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user)
            ->willReturn($user);

        $result = $this->userService->updateUser(1, ['name' => 'Jane Smith']);

        $this->assertEquals('Jane Smith', $result->getName());
    }

    public function test_verify_user_email(): void
    {
        $userId = new UserId(1);
        $user = new User($userId, 'John Doe', new Email('john@example.com'));

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user)
            ->willReturn($user);

        $result = $this->userService->verifyUserEmail(1);

        $this->assertTrue($result->isEmailVerified());
    }

    public function test_is_email_registered(): void
    {
        $email = new Email('john@example.com');

        $this->userRepository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with($email)
            ->willReturn(true);

        $result = $this->userService->isEmailRegistered('john@example.com');

        $this->assertTrue($result);
    }
}
