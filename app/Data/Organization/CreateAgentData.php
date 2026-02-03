<?php

namespace App\Data\Organization;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class CreateAgentData extends Data
{
    public function __construct(
        #[Required]
        public string $email,

        #[Required]
        public string $firstname,

        #[Required]
        public string $lastname,

        public ?string $phone = null,
        public ?string $avatar = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) ($data['email'] ?? ''),
            firstname: (string) ($data['firstname'] ?? $data['first_name'] ?? ''),
            lastname: (string) ($data['lastname'] ?? $data['last_name'] ?? ''),
            phone: $data['phone'] ?? null,
            avatar: $data['avatar'] ?? null,
        );
    }
}
