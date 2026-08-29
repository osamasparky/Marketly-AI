<?php

namespace App\Domains\Identity\Application\DTOs;

use App\Domains\Shared\ValueObjects\Email;

class RegisterUserData
{
    public readonly Email $email;

    public function __construct(
        public readonly string $name,
        string $email,
        public readonly string $password
    ) {
        $this->email = new Email($email);
    }
}
