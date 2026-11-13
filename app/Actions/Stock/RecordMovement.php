<?php

namespace App\Actions\Stock;

use App\Actions\Operations\Fingerprint as F;
use App\Actions\Operations\Quantity as Q;
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
            $hash = F::make(['operation' => $operation, 'data' => $data, 'user_id' => $user->id]);
            $existing = StockMovement::where('operation_id', $data['operation_id'])->first();
            if ($existing) {
                F::assert($existing->request_hash, $hash);

                return $existing;
            }
            if (! $product->ativo && $operation === 'entrada') {
                throw ValidationException::withMessages(['product_id' => 'Produto inativo.']);
            }
            Q::checkUnit($product->unidade, $data['quantidade']);
            $quantity = Q::milli($data['quantidade']);
            if ($quantity === 0 && ! ($operation === 'ajustar' && ($data['tipo'] ?? null) === 'contagem')) {
                throw ValidationException::withMessages(['quantidade' => 'Quantidade deve ser maior que zero.']);
            }
            $before = Q::milli($product->{$data['local']});
            $after = match (true) {
                $operation === 'entrada' => $before + $quantity,
                $operation === 'saida' || ($data['tipo'] ?? null) === 'avaria' => $before - $quantity,
                default => $quantity,
            };
            if ($after < 0 || $after > 999999999999999) {
                throw ValidationException::withMessages(['quantidade' => 'Saldo insuficiente ou acima do limite.']);
            }
            $inventory = app(BatchInventory::class);
            $parts = [];
            if ($after < $before) {
                $parts = $inventory->take($product, $data['local'], Q::value($before - $after));
            }
            if ($after > $before) {
                if (isset($data['batch_parts'])) {
                    foreach ($data['batch_parts'] as $part) {
                        $batch = $inventory->add($product, $data['local'], $part['quantity'], $part['lot'], $part['expires_on'], $part['quarantine']);
                        $parts[] = ['batch_id' => $batch->id, 'quantity' => $part['quantity']];
                    }
                } else {
                    $lot = $data['lote'] ?? ($product->validade ? 'PENDENTE-'.$data['operation_id'] : 'SEM-LOTE');
                    $expiry = $data['validade'] ?? null;
                    $batch = $inventory->add($product, $data['local'], Q::value($after - $before), $lot, $expiry, $product->validade && ! $expiry);
                    $parts[] = ['batch_id' => $batch->id, 'quantity' => Q::value($after - $before)];
                }
            }
            $product->{$data['local']} = Q::value($after);
            $product->movimentado_em = now();
            $product->save();

            return StockMovement::create([
                'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'product_id' => $product->id, 'user_id' => $user->id,
                'operacao' => $operation === 'ajustar' ? $data['tipo'] : $operation, 'local' => $data['local'], 'quantidade' => Q::value($quantity),
                'saldo_anterior' => Q::value($before), 'saldo_final' => Q::value($after), 'documento' => $data['documento'] ?? null, 'lote' => $data['lote'] ?? null,
                'validade' => $data['validade'] ?? null, 'motivo' => $data['motivo'] ?? null, 'allocations' => $parts,
            ]);
        });
    }
}
