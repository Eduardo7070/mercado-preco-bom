<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = ['email' => ['required', 'email']];
        if ($this->routeIs('password.update')) {
            $rules['token'] = ['required', 'string'];
            $rules['password'] = ['required', 'confirmed', Password::min(8)];
        }

        return $rules;
    }
}
