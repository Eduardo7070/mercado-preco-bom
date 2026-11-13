<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('produtos.excluir') ?? false;
    }

    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'min:5', 'max:2000'], 'version' => ['required', 'string', 'size:64']];
    }
}
