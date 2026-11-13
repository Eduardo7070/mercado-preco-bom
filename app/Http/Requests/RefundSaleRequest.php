<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('caixa.estornar') ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:2000']];
    }
}
