<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->input('action') === 'manager_close' ? 'caixa.gerenciar' : 'caixa.finalizar') ?? false;
    }

    public function rules(): array
    {
        return [
            'session_id' => [Rule::requiredIf(in_array($this->input('action'), ['manager_close', 'close', 'supply', 'withdraw'])), 'nullable', 'integer', 'exists:cash_sessions,id'],
            'action' => ['required', Rule::in(['open', 'close', 'supply', 'withdraw', 'manager_close'])],
            'register_id' => [Rule::requiredIf($this->input('action') === 'open'), 'nullable', 'integer', 'exists:cash_registers,id'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999.99'],
            'reason' => [Rule::requiredIf(in_array($this->input('action'), ['supply', 'withdraw', 'manager_close'])), 'nullable', 'string', 'min:5', 'max:2000'],
            'operation_id' => [Rule::requiredIf(in_array($this->input('action'), ['supply', 'withdraw'])), 'nullable', 'uuid'],
        ];
    }
}
