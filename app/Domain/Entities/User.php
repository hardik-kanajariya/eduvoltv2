<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;

/**
 * User Domain Entity
 *
 * Represents a user in the domain layer, containing business logic
 * and maintaining business invariants.
 */
class User
{
    private UserId $id;
    private string $name;
    private Email $email;
    private ?\DateTime $emailVerifiedAt;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        UserId $id,
        string $name,
        Email $email,
        ?\DateTime $emailVerifiedAt = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $this->validateName($name);
        $this->email = $email;
        $this->emailVerifiedAt = $emailVerifiedAt;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getEmailVerifiedAt(): ?\DateTime
    {
        return $this->emailVerifiedAt;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function verifyEmail(): void
    {
        if ($this->isEmailVerified()) {
            throw new \DomainException('Email is already verified');
        }

        $this->emailVerifiedAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function updateName(string $name): void
    {
        $validatedName = $this->validateName($name);
        $this->name = $validatedName;
        $this->updatedAt = new \DateTime();
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->emailVerifiedAt = null; // Reset verification when email changes
        $this->updatedAt = new \DateTime();
    }

    private function validateName(string $name): string
    {
        $trimmedName = trim($name);

        if (empty($trimmedName)) {
            throw new \DomainException('User name cannot be empty');
        }

        if (strlen($trimmedName) > 255) {
            throw new \DomainException('User name cannot exceed 255 characters');
        }

        return $trimmedName;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->getValue(),
            'name' => $this->name,
            'email' => $this->email->getValue(),
            'email_verified_at' => $this->emailVerifiedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
