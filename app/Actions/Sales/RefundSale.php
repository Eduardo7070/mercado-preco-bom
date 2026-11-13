<?php

namespace App\Actions\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RefundSale
{
    public function execute(Sale $sale, string $reason, User $user): void
    {
        DB::transaction(function () use ($sale, $reason, $user) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($sale->status === 'refunded') {
                return;
            }
            foreach ($sale->items()->orderBy('product_id')->get() as $item) {
                $p = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $before = $p->gondola;
                if ($before + $item->quantity > 4294967295) {
                    throw ValidationException::withMessages(['reason' => 'Saldo acima do limite.']);
                }
                $p->gondola += $item->quantity;
                $p->movimentado_em = now();
                $p->save();
                StockMovement::create(['operation_id' => (string) Str::uuid(), 'product_id' => $p->id, 'user_id' => $user->id, 'operacao' => 'estorno', 'local' => 'gondola', 'quantidade' => $item->quantity, 'saldo_anterior' => $before, 'saldo_final' => $p->gondola, 'documento' => 'Estorno venda #'.$sale->id, 'motivo' => $reason]);
            }
            $sale->update(['status' => 'refunded', 'refund_reason' => $reason, 'refunded_by' => $user->id, 'refunded_at' => now()]);
        });
    }
}
