<?php

namespace App\Services;

use App\Models\User;
use App\Enums\UserGenderEnum;
use App\Enums\UserStatusEnum;
use App\Data\Auth\LoginUserData;
use App\Data\Auth\CreateUserData;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{

    public function registerUser(CreateUserData $data): array
    {
        try {
            DB::beginTransaction();
            $user = User::create([
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'avatar' => $data->avatar,
                'firstname' => $data->firstname,
                'lastname' => $data->lastname,
                'gender' => $data->gender,
                'date_of_birth' => $data->date_of_birth,
                'phone' => $data->phone,
                'status' => UserStatusEnum::ACTIVE->value,
            ]);

            $user->assignRole('user');

            $token = $user->createToken('auth_token')->plainTextToken;
            DB::commit();
            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function loginUser(LoginUserData $credentials): array
    {
        $user = User::where('email', $credentials->login)->first();
        

        $field = filter_var($credentials->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'phone';

        if (!Auth::attempt([
            $field => $credentials->login,
            'password' => $credentials->password,
        ])) {
            throw ValidationException::withMessages([
                'login' => ['Identifiants invalides'],
            ]);
        }

        $user = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }


    public function findOrCreateSocialUser(string $provider, object $socialUser): array
    {
        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user) {
            // Check if user exists by email
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Link social account
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => null, // Social users don't have a password initially
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);

                $user->assignRole('user');
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
