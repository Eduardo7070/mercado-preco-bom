<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('acessos.gerenciar') ?? false;
    }

    public function rules(): array
    {
        return ['permissions' => ['sometimes', 'array'], 'permissions.*' => ['integer', 'distinct', 'exists:permissions,id']];
    }
}
