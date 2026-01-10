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
        public string $first_name,

        #[Required]
        public string $last_name,

        public ?string $phone = null,
        public ?string $avatar = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) ($data['email'] ?? ''),
            first_name: (string) ($data['first_name'] ?? $data['firstname'] ?? ''),
            last_name: (string) ($data['last_name'] ?? $data['lastname'] ?? ''),
            phone: $data['phone'] ?? null,
            avatar: $data['avatar'] ?? null,
        );
    }
}
