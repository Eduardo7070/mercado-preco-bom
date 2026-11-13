<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('produtos.consultar') || $this->user()?->can('produtos.editar') || $this->user()?->can('produtos.inativar') || $this->user()?->can('estoque.consultar');
    }

    public function rules(): array
    {
        return ['categoria' => ['nullable', Rule::exists('categories', 'name')], 'saldo' => ['nullable', Rule::in(['todos', 'zero', 'baixo'])], 'q' => ['nullable', 'string', 'max:191'], 'situacao' => ['nullable', Rule::in(['todos', 'ativos', 'inativos'])], 'page' => ['sometimes', 'integer', 'min:1']];
    }
}
