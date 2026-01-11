<?php

namespace App\Enums;

enum UserRoleEnum: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case SUPER_ADMIN = 'super-admin';
    case AGENT = 'agent';

    public static function values(): array
    {
        return array_map(fn (UserRoleEnum $role) => $role->value, self::cases());
    }
}
