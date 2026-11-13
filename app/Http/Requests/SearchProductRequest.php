<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('produtos.consultar') || $this->user()?->can('estoque.consultar');
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:191'], 'situacao' => ['nullable', Rule::in(['todos', 'ativos', 'inativos'])], 'page' => ['sometimes', 'integer', 'min:1']];
    }
}
