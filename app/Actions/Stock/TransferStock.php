<?php

namespace App\Actions\Stock;

use App\Actions\Operations\Fingerprint as F;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class TransferStock
{
    public function execute(Product $product, array $data, User $user): void
    {
        DB::transaction(function () use ($product, $data, $user) {
            $p = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $hash = F::make(['transfer' => $data, 'user' => $user->id]);
            $existing = StockMovement::where('operation_id', $data['operation_id'])->first();
            if ($existing) {
                F::assert($existing->request_hash, $hash);

                return;
            }
            if (! hash_equals($p->version(), $data['version'])) {
                throw ValidationException::withMessages(['version' => 'Saldo alterado. Confira novamente.']);
            }
            if (! $p->ativo) {
                throw ValidationException::withMessages(['produto' => 'Reative o produto antes de transferir.']);
            }
            $origin = $data['origem'];
            $destination = $origin === 'deposito' ? 'gondola' : 'deposito';
            $base = ['product_id' => $p->id, 'quantidade' => $data['quantidade'], 'motivo' => 'Transferência: '.$data['motivo'], 'documento' => 'Transferência '.$data['operation_id']];
            $source = app(RecordMovement::class)->execute(array_merge($base, ['operation_id' => $data['operation_id'], 'local' => $origin]), 'saida', $user);
            foreach ($source->allocations as $part) {
                if (($part['quarantine'] ?? false) || (! empty($part['expires_on']) && $part['expires_on'] < now('America/Sao_Paulo')->toDateString())) {
                    throw ValidationException::withMessages(['quantidade' => 'Regularize o lote pendente ou descarte o saldo vencido antes de transferir.']);
                }
            }
            $source->request_hash = $hash;
            $source->save();
            app(RecordMovement::class)->execute(array_merge($base, ['operation_id' => (string) Uuid::uuid5(Uuid::NAMESPACE_URL, $data['operation_id'].':destino'), 'local' => $destination, 'batch_parts' => $source->allocations]), 'entrada', $user);
        });
    }
}
