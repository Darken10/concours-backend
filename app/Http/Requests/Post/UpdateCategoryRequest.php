<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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

        if ($user->isAgent() && $user->can('edit categories')) {
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
        $category = $this->route('category');
        $categoryId = is_object($category) ? $category->getKey() : $category;

        return [
            'name' => ['required', 'string', 'min:2', 'max:100', 'unique:categories,name,'.$categoryId],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est requis',
            'name.min' => 'Le nom doit contenir au moins 2 caractères',
            'name.unique' => 'Cette catégorie existe déjà',
            'slug.required' => 'Le slug est requis',
            'slug.unique' => 'Ce slug est déjà utilisé',
            'slug.regex' => 'Le slug ne peut contenir que des lettres minuscules, chiffres et tirets',
        ];
    }
}
