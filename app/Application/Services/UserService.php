<?php

namespace App\Application\Services;

use App\Application\Contracts\UserServiceInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;

/**
 * User Application Service
 *
 * Implements user-related use cases and coordinates between
 * the domain layer and infrastructure layer.
 */
class UserService implements UserServiceInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getUserById(int $id): ?User
    {
        $userId = new UserId($id);

        return $this->userRepository->findById($userId);
    }

    public function getUserByEmail(string $email): ?User
    {
        $emailVO = new Email($email);

        return $this->userRepository->findByEmail($emailVO);
    }

    public function createUser(string $name, string $email): User
    {
        $emailVO = new Email($email);

        // Business rule: Email must be unique
        if ($this->userRepository->existsByEmail($emailVO)) {
            throw new \DomainException('Email is already registered');
        }

        // Create new user entity - use placeholder ID that will be replaced by repository
        $user = new User(
            new UserId(1), // Temporary ID - will be replaced by repository when saving
            $name,
            $emailVO
        );

        return $this->userRepository->save($user);
    }

    public function updateUser(int $id, array $data): User
    {
        $userId = new UserId($id);
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new \DomainException('User not found');
        }

        // Update name if provided
        if (isset($data['name'])) {
            $user->updateName($data['name']);
        }

        // Update email if provided
        if (isset($data['email'])) {
            $newEmail = new Email($data['email']);

            // Check if new email is already taken by another user
            if (!$user->getEmail()->equals($newEmail) && $this->userRepository->existsByEmail($newEmail)) {
                throw new \DomainException('Email is already registered');
            }

            $user->updateEmail($newEmail);
        }

        return $this->userRepository->save($user);
    }

    public function verifyUserEmail(int $id): User
    {
        $userId = new UserId($id);
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new \DomainException('User not found');
        }

        $user->verifyEmail();

        return $this->userRepository->save($user);
    }

    public function isEmailRegistered(string $email): bool
    {
        $emailVO = new Email($email);

        return $this->userRepository->existsByEmail($emailVO);
    }

    public function getUsers(int $page = 1, int $perPage = 15): array
    {
        return $this->userRepository->findAll($page, $perPage);
    }
}
