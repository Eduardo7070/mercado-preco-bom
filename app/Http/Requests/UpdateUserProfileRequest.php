<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('acessos.gerenciar') ?? false;
    }

    public function rules(): array
    {
        return ['profile_id' => ['required', 'integer', 'exists:profiles,id']];
    }
}
