<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(match ($this->route('resource')) {
            'terminais' => 'caixa.gerenciar',default => $this->route('resource').'.gerenciar'
        }) ?? false;
    }

    public function rules(): array
    {
        if ($this->isMethod('DELETE')) {
            return [];
        }
        $table = match ($this->route('resource')) {
            'categorias' => 'categories','clientes' => 'customers','fornecedores' => 'suppliers','terminais' => 'cash_registers'
        };
        $name = ['required', 'string', 'max:191'];
        if (in_array($table, ['categories', 'cash_registers'])) {
            $name[] = Rule::unique($table, 'name')->ignore($this->route('record'));
        }
        $rules = ['name' => $name];
        if ($table !== 'cash_registers') {
            $rules['active'] = ['required', 'boolean'];
        }
        if (in_array($table, ['customers', 'suppliers'])) {
            $rules += ['email' => ['nullable', 'email', 'max:191'], 'phone' => ['nullable', 'string', 'max:30'], 'document' => ['nullable', 'string', 'max:30', Rule::unique($table, 'document')->ignore($this->route('record'))]];
        }

        return $rules;
    }
}
