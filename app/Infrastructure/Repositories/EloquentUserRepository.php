<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;
use App\Models\User as EloquentUser;

/**
 * Eloquent User Repository
 *
 * Infrastructure implementation of UserRepositoryInterface using Laravel's Eloquent ORM.
 * This adapter translates between domain entities and Laravel models.
 */
class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?User
    {
        $eloquentUser = EloquentUser::find($id->getValue());

        return $eloquentUser ? $this->toDomainEntity($eloquentUser) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $eloquentUser = EloquentUser::where('email', $email->getValue())->first();

        return $eloquentUser ? $this->toDomainEntity($eloquentUser) : null;
    }

    public function save(User $user): User
    {
        $data = $user->toArray();

        if ($user->getId()->getValue() === 1 && !EloquentUser::find(1)) {
            // This is a new user with placeholder ID - create new record
            unset($data['id']); // Let database auto-increment
            $eloquentUser = EloquentUser::create($data);
        } else {
            // Update existing user
            $eloquentUser = EloquentUser::findOrFail($user->getId()->getValue());
            $eloquentUser->update($data);
        }

        return $this->toDomainEntity($eloquentUser);
    }

    public function deleteById(UserId $id): bool
    {
        $eloquentUser = EloquentUser::find($id->getValue());

        if (!$eloquentUser) {
            return false;
        }

        return $eloquentUser->delete();
    }

    public function existsByEmail(Email $email): bool
    {
        return EloquentUser::where('email', $email->getValue())->exists();
    }

    public function findAll(int $page = 1, int $perPage = 15): array
    {
        $eloquentUsers = EloquentUser::paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map([$this, 'toDomainEntity'], $eloquentUsers->items()),
            'total' => $eloquentUsers->total(),
            'current_page' => $eloquentUsers->currentPage(),
            'per_page' => $eloquentUsers->perPage(),
            'last_page' => $eloquentUsers->lastPage(),
        ];
    }

    public function count(): int
    {
        return EloquentUser::count();
    }

    /**
     * Convert Eloquent model to Domain entity
     */
    private function toDomainEntity(EloquentUser $eloquentUser): User
    {
        return new User(
            new UserId($eloquentUser->id),
            $eloquentUser->name,
            new Email($eloquentUser->email),
            $eloquentUser->email_verified_at ? new \DateTime($eloquentUser->email_verified_at) : null,
            new \DateTime($eloquentUser->created_at),
            new \DateTime($eloquentUser->updated_at)
        );
    }
}
