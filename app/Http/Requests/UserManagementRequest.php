<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('acessos.gerenciar') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:191'], 'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($this->route('user')?->id)], 'profile_id' => ['required', 'integer', 'exists:profiles,id'], 'active' => ['required', 'boolean'], 'password' => [$this->route('user') ? 'nullable' : 'required', 'confirmed', Password::min(8)]];
    }
}
