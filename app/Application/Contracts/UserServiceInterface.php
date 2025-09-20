<?php

namespace App\Application\Contracts;

use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;

/**
 * User Service Interface
 *
 * Defines the contract for user-related use cases and application services.
 */
interface UserServiceInterface
{
    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?User;

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): ?User;

    /**
     * Create a new user
     */
    public function createUser(string $name, string $email): User;

    /**
     * Update user information
     */
    public function updateUser(int $id, array $data): User;

    /**
     * Verify user email
     */
    public function verifyUserEmail(int $id): User;

    /**
     * Check if email is already registered
     */
    public function isEmailRegistered(string $email): bool;

    /**
     * Get paginated users
     */
    public function getUsers(int $page = 1, int $perPage = 15): array;
}
