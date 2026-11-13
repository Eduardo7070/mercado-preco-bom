<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('caixa.finalizar') ?? false;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:191'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'status' => ['nullable', Rule::in(['paid', 'refunded', 'partially_refunded'])], 'page' => ['sometimes', 'integer', 'min:1']];
    }
}
