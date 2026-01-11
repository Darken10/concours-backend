<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:5000'],
            'parent_id' => ['nullable', 'string', 'exists:comments,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpeg,png,gif,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Le contenu est requis',
            'content.min' => 'Le contenu ne peut pas être vide',
            'parent_id.exists' => 'Le commentaire parent n\'existe pas',
            'attachments.*.mimes' => 'Les pièces jointes doivent être en format image ou PDF',
            'attachments.*.max' => 'Les pièces jointes ne doivent pas dépasser 5 Mo',
        ];
    }
}
