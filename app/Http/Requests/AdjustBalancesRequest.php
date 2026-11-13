<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustBalancesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('estoque.ajustar') ?? false;
    }

    public function rules(): array
    {
        return ['deposito' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:1000000000'], 'gondola' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:1000000000'], 'motivo' => ['required', 'string', 'min:5', 'max:2000'], 'version' => ['required', 'string', 'size:64'], 'operation_id' => ['required', 'uuid']];
    }
}
