<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class AssignAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required_without_all:email,firstname,lastname', 'uuid', 'exists:users,id'],
            'email' => ['required_without:user_id', 'email', 'max:255', 'unique:users,email'],
            'firstname' => ['required_without:user_id', 'string', 'max:100'],
            'lastname' => ['required_without:user_id', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'url'],
        ];
    }
}
