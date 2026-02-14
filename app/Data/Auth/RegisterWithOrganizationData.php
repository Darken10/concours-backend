<?php

namespace App\Data\Auth;

use App\Enums\UserGenderEnum;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class RegisterWithOrganizationData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,

        #[Required, Min(8)]
        public string $password,

        #[Nullable]
        public ?string $avatar,

        #[Required]
        public string $firstname,

        #[Required]
        public string $lastname,

        #[Required]
        public UserGenderEnum $gender,

        #[Nullable]
        public ?string $date_of_birth,

        #[Nullable]
        public ?string $phone,

        #[Required]
        public bool $is_organization,

        #[Nullable]
        public ?string $organization_name,

        #[Nullable]
        public ?string $organization_description
    ) {}

    public static function fromArray(array $data): self
    {
        if (! isset($data['email'])) {
            throw new \InvalidArgumentException('email is required');
        }

        if (! isset($data['password'])) {
            throw new \InvalidArgumentException('password is required');
        }

        if (! isset($data['gender'])) {
            throw new \InvalidArgumentException('gender is required');
        }

        return new self(
            email: (string) $data['email'],
            password: (string) $data['password'],
            avatar: $data['avatar'] ?? null,
            firstname: $data['firstname'] ?? '',
            lastname: $data['lastname'] ?? '',
            gender: UserGenderEnum::from($data['gender']),
            date_of_birth: $data['date_of_birth'] ?? null,
            phone: $data['phone'] ?? null,
            is_organization: (bool) ($data['is_organization'] ?? false),
            organization_name: $data['organization_name'] ?? null,
            organization_description: $data['organization_description'] ?? null
        );
    }
}
