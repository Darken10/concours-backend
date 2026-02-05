<?php

namespace App\Enums;

enum UserStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';

    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }

    public static function labels(): array
    {
        return [
            self::ACTIVE->value => 'Actif',
            self::INACTIVE->value => 'Inactif',
            self::SUSPENDED->value => 'Suspendu',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value];
    }
}
