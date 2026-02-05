<?php

namespace Database\Seeders;

use App\Enums\UserGenderEnum;
use App\Enums\UserStatusEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $roles = Role::all();

        foreach ($roles as $role) {
            $email = Str::slug($role->name).'@example.com';

            $user = User::create([
                'firstname' => ucfirst($role->name),
                'lastname' => 'User',
                'email' => $email,
                'password' => 'password',
                'status' => UserStatusEnum::ACTIVE->value,
                'gender' => UserGenderEnum::MALE->value,
            ]);

            $user->assignRole($role->name);
        }

    }
}
