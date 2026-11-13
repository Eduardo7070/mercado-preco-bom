<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('caixa.finalizar') && $this->user()?->can('caixa.pagamento');
    }

    public function rules(): array
    {
        return [
            'operation_id' => ['required', 'uuid'], 'customer_name' => ['nullable', 'string', 'max:191'],
            'payment_method' => ['required', Rule::in(['Dinheiro', 'Pix', 'Débito', 'Crédito'])],
            'approved' => ['accepted_if:payment_method,Pix,Débito,Crédito'],
            'received' => ['nullable', Rule::requiredIf($this->input('payment_method') === 'Dinheiro'), 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'approved.accepted_if' => 'Confirme a aprovação simulada do pagamento.',
            'received.required' => 'Informe o valor recebido em dinheiro.',
            'items.required' => 'Inclua pelo menos um produto na venda.',
            'items.min' => 'Inclua pelo menos um produto na venda.',
            'items.*.product_id.distinct' => 'Agrupe as quantidades do mesmo produto em uma única linha.',
            'items.*.quantity.min' => 'A quantidade deve ser maior que zero.',
        ];
    }
}
