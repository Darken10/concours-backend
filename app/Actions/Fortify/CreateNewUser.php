<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        // Extract firstname and lastname from name
        $nameParts = explode(' ', trim($input['name']), 2);
        $firstname = $nameParts[0];
        $lastname = $nameParts[1] ?? '';

        // Generate a 6-digit verification code
        $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $input['email'],
            'password' => $input['password'],
            'email_verification_code' => $verificationCode,
            'email_verification_code_expires_at' => now()->addMinutes(15),
        ]);

        // Send verification code via email
        $user->notify(new EmailVerificationCode($verificationCode));

        return $user;
    }
}
