<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use App\Enums\UserGenderEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $roles = Role::all();

        foreach ($roles as $role) {
            $email = Str::slug($role->name) . '@example.com';

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
