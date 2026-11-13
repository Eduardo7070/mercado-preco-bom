<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('caixa.finalizar') ?? false;
    }

    public function rules(): array
    {
        return ['draft_id' => ['nullable', 'integer', 'exists:sale_drafts,id'], 'operation_id' => ['required', 'uuid'], 'label' => ['required', 'string', 'max:191'], 'customer_name' => ['nullable', 'string', 'max:191'], 'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'], 'items.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0.001', 'max:100000']];
    }
}
