<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class LoginUserData extends Data
{
    public function __construct(
        #[Required]
        public string $login, // email OU téléphone

        #[Required]
        public string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        if (! isset($data['email']) && ! isset($data['phone'])) {
            throw new \InvalidArgumentException('login is required');
        }

        if (! isset($data['password'])) {
            throw new \InvalidArgumentException('password is required');
        }

        return new self(
            login: (string) $data['email'] ?? (string) $data['phone'],
            password: (string) $data['password'],
        );
    }
}
