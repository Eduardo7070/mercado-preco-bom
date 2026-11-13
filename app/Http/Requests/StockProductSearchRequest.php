<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockProductSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('operation') === 'saida'
            ? ($this->user()?->canAny(['estoque.saida', 'estoque.ajustar']) ?? false)
            : ($this->user()?->can('estoque.'.$this->route('operation')) ?? false);
    }

    public function rules(): array
    {
        return ['q' => ['required', 'string', 'max:191']];
    }
}
