<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('produtos.cadastrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'required_without:barras', 'string', 'max:80', Rule::unique('products', 'codigo')],
            'barras' => ['nullable', 'required_without:codigo', 'string', 'regex:/^[0-9]+$/', 'max:80', Rule::unique('products', 'barras')],
            'nome' => ['required', 'string', 'max:191'], 'categoria' => ['required', 'string', Rule::in(['Mercearia', 'Laticínios', 'Limpeza', 'Hortifruti', 'Bebidas'])],
            'unidade' => ['required', Rule::in(['UN', 'KG', 'L', 'CX'])], 'preco' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'], 'validade' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['codigo.required_without' => 'Informe código interno ou código de barras.', 'barras.required_without' => 'Informe código interno ou código de barras.', 'codigo.unique' => 'Código interno já cadastrado.', 'barras.unique' => 'Código de barras já cadastrado.'];
    }
}
