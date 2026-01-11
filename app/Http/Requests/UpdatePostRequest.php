<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() || auth()->user()->isAgent());
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
            'images.*' => ['file', 'mimes:jpeg,png,gif,webp', 'max:5120'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx', 'max:10240'],
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
            'attachments.*.mimes' => 'Les pièces jointes doivent être en format PDF, DOC ou DOCX',
            'attachments.*.max' => 'Les pièces jointes ne doivent pas dépasser 10 Mo',
        ];
    }
}
