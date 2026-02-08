<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'content' => ['required', 'string', 'min:10', 'max:10000'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'file', 'mimes:jpeg,png,gif,webp', 'max:5120'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est requis',
            'title.min' => 'Le titre doit contenir au moins 3 caractères',
            'content.required' => 'Le contenu est requis',
            'content.min' => 'Le contenu doit contenir au moins 10 caractères',
            'images.*.mimes' => 'Les images doivent être en format JPEG, PNG, GIF ou WEBP',
            'images.*.max' => 'Les images ne doivent pas dépasser 5 Mo',
            'category_id.uuid' => 'L\'ID de catégorie doit être un UUID valide',
            'category_id.exists' => 'La catégorie spécifiée n\'existe pas',
            'tag_ids.*.uuid' => 'Chaque ID de tag doit être un UUID valide',
            'tag_ids.*.exists' => 'Chaque tag spécifié doit exister',
        ];
    }
}
