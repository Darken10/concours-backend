<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;
use App\Enums\UserGenderEnum;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;

class CreateUserData extends Data
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
        public ?string $phone
    ){}


    public static function fromArray(array $data): self
    {
        if (!isset($data['email'])) {
            throw new \InvalidArgumentException('email is required');
        }

        if (!isset($data['password'])) {
            throw new \InvalidArgumentException('password is required');
        }

        return new self(
            email: (string) $data['email'],
            password: (string) $data['password'],
            avatar: $data['avatar'] ?? null,
            firstname: $data['firstname'] ?? null,
            lastname: $data['lastname'] ?? null,
            gender: array_key_exists('gender', $data) && $data['gender'] !== null ? UserGenderEnum::tryFrom($data['gender']) : null,
            date_of_birth: $data['date_of_birth'] ?? null,
            phone: $data['phone'] ?? null
        );
    }
}
