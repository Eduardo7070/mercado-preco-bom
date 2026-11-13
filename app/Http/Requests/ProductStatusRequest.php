<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('produtos.inativar') ?? false;
    }

    public function rules(): array
    {
        return ['ativo' => ['required', 'boolean'], 'motivo' => ['required', 'string', 'min:5', 'max:2000'], 'version' => ['required', 'string', 'size:64']];
    }
}
