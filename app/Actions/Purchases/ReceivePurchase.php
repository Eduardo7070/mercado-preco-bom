<?php

namespace App\Actions\Purchases;

use App\Actions\Operations\Fingerprint as F;
use App\Actions\Operations\Quantity as Q;
use App\Actions\Stock\RecordMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class ReceivePurchase
{
    public function execute(array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $user) {
            Supplier::whereKey($data['supplier_id'])->where('active', true)->lockForUpdate()->firstOrFail();
            $hash = F::make($data);
            $old = Purchase::where('operation_id', $data['operation_id'])->first();
            if ($old) {
                F::assert($old->request_hash, $hash);

                return $old;
            }
            if (! empty($data['document']) && Purchase::where('supplier_id', $data['supplier_id'])->where('document', $data['document'])->exists()) {
                throw ValidationException::withMessages(['document' => 'Documento já recebido para este fornecedor.']);
            }
            $rows = $data['items'];
            usort($rows, fn ($a, $b) => $a['product_id'] <=> $b['product_id']);
            $total = 0;
            $saved = [];
            foreach ($rows as $i => $row) {
                $p = Product::whereKey($row['product_id'])->lockForUpdate()->firstOrFail();
                Q::checkUnit($p->unidade, $row['quantity']);
                $cost = Q::cents($row['unit_cost']);
                $line = Q::line($cost, $row['quantity']);
                $total += $line;
                if ($total > 999999999999) {
                    throw ValidationException::withMessages(['items' => 'Total da compra acima do limite.']);
                }
                $before = Q::milli($p->deposito) + Q::milli($p->gondola);
                $qty = Q::milli($row['quantity']);
                $p->custo = round((Q::cents($p->custo) * $before + $cost * $qty) / max(1, $before + $qty)) / 100;
                $p->save();
                app(RecordMovement::class)->execute(['operation_id' => (string) Uuid::uuid5(Uuid::NAMESPACE_URL, $data['operation_id'].':'.$p->id), 'product_id' => $p->id, 'local' => 'deposito', 'quantidade' => $row['quantity'], 'documento' => $data['document'] ?? 'Compra', 'lote' => $row['lot'] ?? null, 'validade' => $row['expires_on'] ?? null, 'motivo' => 'Compra recebida de fornecedor #'.$data['supplier_id']], 'entrada', $user);
                $saved[] = $row + ['product_name' => $p->nome, 'line_cents' => $line];
            }

            return Purchase::create(['operation_id' => $data['operation_id'], 'request_hash' => $hash, 'supplier_id' => $data['supplier_id'], 'user_id' => $user->id, 'document' => $data['document'] ?? null, 'total_cents' => $total, 'items' => $saved]);
        });
    }
}
