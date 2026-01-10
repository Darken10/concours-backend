<?php

namespace App\Enums;

enum UserGenderEnum: string
{
    case MALE = 'male';
    case FEMALE = 'female';

    public static function values(): array
    {
        return array_map(fn (self $gender) => $gender->value, self::cases());
    }

}
