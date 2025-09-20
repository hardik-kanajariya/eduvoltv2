<?php

namespace App\Domain\ValueObjects;

/**
 * User ID Value Object
 *
 * Represents a unique identifier for a user in the domain.
 */
class UserId
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }

        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
