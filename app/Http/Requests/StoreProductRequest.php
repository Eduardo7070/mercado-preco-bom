<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'custo' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'], 'nome' => ['required', 'string', 'max:191'], 'categoria' => ['required', 'string', Rule::exists('categories', 'name')->where('active', true)],
            'unidade' => ['required', Rule::in(['UN', 'KG', 'L', 'CX'])], 'preco' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'], 'validade' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['codigo.required_without' => 'Informe código interno ou código de barras.', 'barras.required_without' => 'Informe código interno ou código de barras.', 'codigo.unique' => 'Código interno já cadastrado.', 'barras.unique' => 'Código de barras já cadastrado.'];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $id = $this->route('product')?->id;
            foreach (['codigo', 'barras'] as $field) {
                $value = $this->input($field);
                if ($value && DB::table('product_identifiers')->where('identifier', $value)->when($id, fn ($q) => $q->where('product_id', '!=', $id))->exists()) {
                    $validator->errors()->add($field, 'Este identificador já pertence a outro produto.');
                }
            }
        }];
    }
}
