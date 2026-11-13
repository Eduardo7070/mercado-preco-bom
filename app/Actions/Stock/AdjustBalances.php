<?php

namespace App\Actions\Stock;

use App\Actions\Operations\Fingerprint;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class AdjustBalances
{
    public function execute(Product $product, array $data, User $user): void
    {
        DB::transaction(function () use ($product, $data, $user) {
            $p = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $hash = Fingerprint::make(['balances' => $data, 'user' => $user->id]);
            $existing = StockMovement::where('operation_id', $data['operation_id'])->first();
            if ($existing) {
                Fingerprint::assert($existing->request_hash, $hash);

                return;
            }
            if (! hash_equals($p->version(), $data['version'])) {
                throw ValidationException::withMessages(['version' => 'O saldo mudou. Feche e abra o modal para conferir o saldo atual.']);
            }
            foreach (['deposito', 'gondola'] as $local) {
                app(RecordMovement::class)->execute([
                    'product_id' => $p->id, 'operation_id' => $local === 'deposito' ? $data['operation_id'] : (string) Uuid::uuid5(Uuid::NAMESPACE_URL, $data['operation_id'].':gondola'),
                    'documento' => 'Ajuste de saldos '.$data['operation_id'], 'local' => $local, 'tipo' => 'contagem', 'quantidade' => $data[$local], 'motivo' => $data['motivo'],
                ], 'ajustar', $user);
            }
            StockMovement::where('operation_id', $data['operation_id'])->update(['request_hash' => $hash]);
        });
    }
}
