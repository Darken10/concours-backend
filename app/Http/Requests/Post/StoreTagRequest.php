<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class StoreTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->isAgent() && $user->can('edit tags')) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:50', 'unique:tags,name'],

        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du tag est requis',
            'name.min' => 'Le nom doit contenir au moins 2 caractères',
            'name.unique' => 'Ce tag existe déjà',
            'slug.required' => 'Le slug est requis',
            'slug.unique' => 'Ce slug est déjà utilisé',
            'slug.regex' => 'Le slug ne peut contenir que des lettres minuscules, chiffres et tirets',
        ];
    }
}
