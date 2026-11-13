<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('produtos.editar') ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $product = $this->route('product');
        $rules['codigo'] = ['nullable', Rule::in([$product->codigo])];
        $rules['barras'] = ['nullable', Rule::requiredIf(! $product->codigo), 'string', 'regex:/^[0-9]+$/', 'max:80', Rule::unique('products', 'barras')->ignore($product->id)];
        $rules['version'] = ['required', 'string', 'size:64'];

        return $rules;
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
