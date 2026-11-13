<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compras.gerenciar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('items', []))) {
            return;
        }$this->merge(['items' => array_values(array_filter($this->input('items', []), fn ($row) => ! is_array($row) || ! empty($row['product_id']) || ! empty($row['quantity']) || ! empty($row['unit_cost'])))]);
    }

    public function rules(): array
    {
        return [
            'operation_id' => ['required', 'uuid'], 'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('active', true)], 'document' => ['nullable', 'string', 'max:191'], 'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'], 'items.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0.001', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999.99'], 'items.*.lot' => ['nullable', 'string', 'max:191'], 'items.*.expires_on' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function after(): array
    {
        return [function ($v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            if (! is_array($this->input('items', []))) {
                return;
            }foreach ($this->input('items', []) as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }$p = Product::find($row['product_id'] ?? null);
                if ($p?->validade && (empty($row['lot']) || empty($row['expires_on']))) {
                    $v->errors()->add("items.$i.lot", 'Produto controlado exige lote e validade.');
                }
            }
        }];
    }
}
