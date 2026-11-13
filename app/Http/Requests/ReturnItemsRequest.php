<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('caixa.estornar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('items', []))) {
            return;
        }
        $this->merge(['items' => array_values(array_filter($this->input('items', []), fn ($row) => ! is_array($row) || (float) ($row['quantity'] ?? 0) > 0))]);
    }

    public function rules(): array
    {
        return [
            'operation_id' => ['required', 'uuid'], 'reason' => ['required', 'string', 'min:5', 'max:2000'], 'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.sale_item_id' => ['required', 'integer', 'distinct', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0.001', 'max:100000'],
            'items.*.disposition' => ['required', Rule::in(['restock', 'discard'])],
        ];
    }
}
