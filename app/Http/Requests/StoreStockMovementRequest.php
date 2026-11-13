<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('estoque.'.$this->operation()) ?? false;
    }

    public function operation(): string
    {
        if ($this->route('operation') === 'saida' && in_array($this->input('tipo'), ['contagem', 'avaria'], true)) {
            return 'ajustar';
        }

        return $this->route('operation');
    }

    public function rules(): array
    {
        $product = is_scalar($this->input('product_id')) ? Product::find($this->input('product_id')) : null;
        $entry = $this->route('operation') === 'entrada';
        $count = $this->operation() === 'ajustar' && $this->input('tipo') === 'contagem';

        return [
            'operation_id' => ['required', 'uuid'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'local' => ['required', Rule::in(['deposito', 'gondola'])],
            'quantidade' => ['required', 'numeric', 'decimal:0,3', 'min:'.($count ? 0 : 0.001), 'max:1000000000'],
            'tipo' => [Rule::requiredIf($this->route('operation') === 'ajustar'), 'nullable', Rule::in($this->route('operation') === 'saida' ? ['saida', 'contagem', 'avaria'] : ['contagem', 'avaria'])],
            'motivo' => [Rule::requiredIf(! $entry), 'nullable', 'string', 'max:2000'],
            'documento' => ['nullable', 'string', 'max:191'],
            'lote' => [Rule::requiredIf($entry && (bool) $product?->validade), 'nullable', 'string', 'max:191'],
            'validade' => [Rule::requiredIf($entry && (bool) $product?->validade), 'nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
