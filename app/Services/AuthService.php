<?php

namespace App\Services;

use App\Data\Auth\CreateUserData;
use App\Data\Auth\LoginUserData;
use App\Data\Auth\RegisterWithOrganizationData;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthService
{
    /**
     * Ensure roles exist before assigning them
     */
    private function ensureRolesExist(): void
    {
        foreach (UserRoleEnum::cases() as $role) {
            Role::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }
    }

    /**
     * Assign role safely
     */
    private function assignRoleSafely(User $user, string $roleName): void
    {
        $this->ensureRolesExist();
        $user->assignRole($roleName);
    }

    public function registerUser(CreateUserData $data): array
    {
        try {
            DB::beginTransaction();

            // Generate a 6-digit verification code
            $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

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
                'email_verification_code' => $verificationCode,
                'email_verification_code_expires_at' => now()->addMinutes(15),
            ]);

            $this->assignRoleSafely($user, 'user');

            // Send verification code via email
            $user->notify(new EmailVerificationCode($verificationCode));

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

    public function registerUserWithOrganization(RegisterWithOrganizationData $data): array
    {
        try {
            DB::beginTransaction();

            $organization = null;

            // Generate a 6-digit verification code
            $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Create organization if is_organization is true
            if ($data->is_organization) {
                $organization = Organization::create([
                    'name' => $data->organization_name,
                    'description' => $data->organization_description,
                ]);
            }

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
                'organization_id' => $organization?->id,
                'email_verification_code' => $verificationCode,
                'email_verification_code_expires_at' => now()->addMinutes(15),
            ]);

            // Assign role based on organization status
            if ($data->is_organization) {
                $this->assignRoleSafely($user, 'admin');
            } else {
                $this->assignRoleSafely($user, 'user');
            }

            // Send verification code via email
            $user->notify(new EmailVerificationCode($verificationCode));

            $token = $user->createToken('auth_token')->plainTextToken;
            DB::commit();

            return [
                'user' => $user->load('organization'),
                'token' => $token,
                'organization' => $organization,
            ];
        } catch (\Exception $e) {
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

        if (! Auth::attempt([
            $field => $credentials->login,
            'password' => $credentials->password,
        ])) {
            throw ValidationException::withMessages([
                'login' => ['Identifiants invalides'],
            ]);
        }

        $user = Auth::user();

        // Check if email is verified
        if ($user->email_verified_at === null) {
            throw ValidationException::withMessages([
                'email' => ['Veuillez vérifier votre adresse email avant de vous connecter'],
            ]);
        }

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
                $nameParts = explode(' ', $socialUser->getName() ?? 'Unknown User', 2);

                $user = User::create([
                    'firstname' => $nameParts[0] ?? 'Unknown',
                    'lastname' => $nameParts[1] ?? 'User',
                    'email' => $socialUser->getEmail(),
                    'password' => null, // Social users don't have a password initially
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);

                $this->assignRoleSafely($user, 'user');
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
