<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('estoque.'.$this->route('operation')) ?? false;
    }

    public function rules(): array
    {
        $product = Product::find($this->input('product_id'));
        $entry = $this->route('operation') === 'entrada';
        $count = $this->route('operation') === 'ajustar' && $this->input('tipo') === 'contagem';

        return [
            'operation_id' => ['required', 'uuid'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'local' => ['required', Rule::in(['deposito', 'gondola'])],
            'quantidade' => ['required', 'integer', 'min:'.($count ? 0 : 1), 'max:1000000000'],
            'tipo' => [Rule::requiredIf($this->route('operation') === 'ajustar'), 'nullable', Rule::in(['contagem', 'avaria'])],
            'motivo' => [Rule::requiredIf(! $entry), 'nullable', 'string', 'max:2000'],
            'documento' => ['nullable', 'string', 'max:191'],
            'lote' => [Rule::requiredIf($entry && (bool) $product?->validade), 'nullable', 'string', 'max:191'],
            'validade' => [Rule::requiredIf($entry && (bool) $product?->validade), 'nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
