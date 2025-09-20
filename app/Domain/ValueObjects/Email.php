<?php

namespace App\Domain\ValueObjects;

/**
 * Email Value Object
 *
 * Represents a valid email address in the domain.
 */
class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmedValue = trim($value);

        if (empty($trimmedValue)) {
            throw new \InvalidArgumentException('Email cannot be empty');
        }

        if (strlen($trimmedValue) > 255) {
            throw new \InvalidArgumentException('Email cannot exceed 255 characters');
        }

        if (!filter_var($trimmedValue, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        $this->value = strtolower($trimmedValue);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
