<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserGenderEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
             'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'provider' => ['nullable', 'string', 'max:50'],
            'provider_id' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'url'],
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(UserGenderEnum::class)],
            'date_of_birth' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

     public function messages(): array
    {
        return [
            'email.required' => 'L’adresse email est obligatoire.',
            'email.email' => 'L’adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'email.max' => 'L’adresse email ne doit pas dépasser 255 caractères.',

            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',

            'provider.string' => 'Le provider doit être une chaîne de caractères.',
            'provider.max' => 'Le provider ne doit pas dépasser 50 caractères.',

            'provider_id.string' => 'L’identifiant du provider doit être une chaîne.',
            'provider_id.max' => 'L’identifiant du provider est trop long.',

            'avatar.url' => 'L’avatar doit être une URL valide.',

            'first_name.string' => 'Le prénom doit être une chaîne de caractères.',
            'first_name.max' => 'Le prénom ne doit pas dépasser 100 caractères.',

            'last_name.string' => 'Le nom doit être une chaîne de caractères.',
            'last_name.max' => 'Le nom ne doit pas dépasser 100 caractères.',

            'gender.in' => 'Le genre doit être : male, female ou other.',

            'date_of_birth.date' => 'La date de naissance doit être une date valide.',

            'phone.string' => 'Le numéro de téléphone doit être une chaîne.',
            'phone.max' => 'Le numéro de téléphone est trop long.',
        ];
    }
}
