<?php

namespace App\Domains\Shared\ValueObjects;

use InvalidArgumentException;

class Email
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $sanitized = filter_var(trim($value), FILTER_VALIDATE_EMAIL);

        if ($sanitized === false) {
            throw new InvalidArgumentException("Invalid email format: '{$value}'");
        }

        $this->value = strtolower($sanitized);
    }

    public function value(): string
    {
        return $this->value;
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
