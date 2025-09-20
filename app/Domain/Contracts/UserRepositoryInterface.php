<?php

namespace App\Domain\Contracts;

use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;

/**
 * User Repository Interface
 *
 * Defines the contract for user persistence operations.
 * This interface belongs to the domain layer and is implemented
 * by the infrastructure layer.
 */
interface UserRepositoryInterface
{
    /**
     * Find a user by their ID
     */
    public function findById(UserId $id): ?User;

    /**
     * Find a user by their email address
     */
    public function findByEmail(Email $email): ?User;

    /**
     * Save a user entity
     */
    public function save(User $user): User;

    /**
     * Delete a user by their ID
     */
    public function deleteById(UserId $id): bool;

    /**
     * Check if a user exists by email
     */
    public function existsByEmail(Email $email): bool;

    /**
     * Get all users with pagination
     */
    public function findAll(int $page = 1, int $perPage = 15): array;

    /**
     * Count total users
     */
    public function count(): int;
}
