<?php

namespace App\Actions\Stock;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordMovement
{
    public function execute(array $data, string $operation, User $user): StockMovement
    {
        return DB::transaction(function () use ($data, $operation, $user) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            if (! $product->ativo) {
                throw ValidationException::withMessages(['product_id' => 'Produto inativo.']);
            }
            $existing = StockMovement::where('operation_id', $data['operation_id'])->first();
            if ($existing) {
                if ($existing->user_id !== $user->id || $existing->product_id !== $product->id) {
                    throw ValidationException::withMessages(['operation_id' => 'Operação já utilizada.']);
                }

                return $existing;
            }
            $before = $product->{$data['local']};
            $after = match (true) {
                $operation === 'entrada' => $before + $data['quantidade'],
                $operation === 'saida' || ($data['tipo'] ?? null) === 'avaria' => $before - $data['quantidade'],
                default => $data['quantidade'],
            };
            if ($after < 0 || $after > 4294967295) {
                throw ValidationException::withMessages(['quantidade' => 'Saldo insuficiente ou quantidade acima do limite.']);
            }
            $product->{$data['local']} = $after;
            $product->movimentado_em = now();
            $product->save();

            return StockMovement::create([
                'operation_id' => $data['operation_id'], 'product_id' => $product->id, 'user_id' => $user->id,
                'operacao' => $operation === 'ajustar' ? $data['tipo'] : $operation, 'local' => $data['local'], 'quantidade' => $data['quantidade'],
                'saldo_anterior' => $before, 'saldo_final' => $after, 'documento' => $data['documento'] ?? null, 'lote' => $data['lote'] ?? null, 'validade' => $data['validade'] ?? null, 'motivo' => $data['motivo'] ?? null,
            ]);
        });
    }
}
