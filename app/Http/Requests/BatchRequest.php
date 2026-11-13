<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('estoque.ajustar') ?? false;
    }

    public function rules(): array
    {
        return ['lot' => ['required', 'string', 'max:191'], 'expires_on' => ['nullable', 'required_if:requires_expiration,1', 'date'], 'quarantine' => ['required', 'boolean'], 'reason' => ['required', 'string', 'min:5', 'max:2000']];
    }
}
