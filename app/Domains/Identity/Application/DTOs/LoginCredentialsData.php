<?php

namespace App\Domains\Identity\Application\DTOs;

use App\Domains\Shared\ValueObjects\Email;

class LoginCredentialsData
{
    public readonly Email $email;

    public function __construct(
        string $email,
        public readonly string $password
    ) {
        $this->email = new Email($email);
    }
}
