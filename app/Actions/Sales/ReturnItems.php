<?php

namespace App\Actions\Sales;

use App\Actions\Cash\ManageSession;
use App\Actions\Operations\Fingerprint as F;
use App\Actions\Operations\Quantity as Q;
use App\Actions\Stock\BatchInventory;
use App\Models\CashEntry;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReturnItems
{
    public function execute(Sale $sale, array $data, User $user): SaleReturn
    {
        return DB::transaction(function () use ($sale, $data, $user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $rows = $data['items'];
            usort($rows, fn ($a, $b) => $a['sale_item_id'] <=> $b['sale_item_id']);
            $hash = F::make(['sale_id' => $sale->id, 'items' => $rows, 'reason' => $data['reason'], 'user' => $user->id]);
            $old = SaleReturn::where('operation_id', $data['operation_id'])->first();
            if ($old) {
                F::assert($old->request_hash, $hash);

                return $old;
            }
            $session = app(ManageSession::class)->requireOpen($user);
            if (! $rows || $sale->status === 'refunded') {
                throw ValidationException::withMessages(['items' => 'Esta venda não possui saldo para devolução.']);
            }
            $items = $sale->items()->orderBy('product_id')->get()->keyBy('id');
            $amount = 0;
            $cost = 0;
            $saved = [];
            $ids = $items->pluck('product_id')->sort()->values()->all();
            $products = Product::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($rows as $row) {
                $item = $items->get($row['sale_item_id']);
                $qty = Q::milli($row['quantity']);
                if (! $item || $qty <= 0 || $qty > Q::milli($item->quantity) - Q::milli($item->returned_quantity)) {
                    throw ValidationException::withMessages(['items' => 'Quantidade devolvida superior ao saldo vendido.']);
                }
                Q::checkUnit($item->unit, $row['quantity']);
                $p = $products[$item->product_id];
                $cumulative = Q::milli($item->returned_quantity) + $qty;
                $cents = $cumulative === Q::milli($item->quantity) ? $item->total_cents - $item->returned_cents : Q::line($item->unit_price_cents, Q::value($cumulative)) - $item->returned_cents;
                $amount += $cents;
                $cost += Q::line($item->unit_cost_cents, $row['quantity']);
                $before = $p->gondola;
                $remaining = $qty;
                $parts = [];
                $allocations = DB::table('sale_item_batches')->where('sale_item_id', $item->id)->orderBy('id')->lockForUpdate()->get();
                foreach ($allocations as $allocation) {
                    $part = min($remaining, Q::milli($allocation->quantity) - Q::milli($allocation->returned_quantity));
                    if ($part <= 0) {
                        continue;
                    }
                    $batch = StockBatch::findOrFail($allocation->stock_batch_id);
                    if ($row['disposition'] === 'restock') {
                        $returned = app(BatchInventory::class)->add($p, 'gondola', Q::value($part), $batch->lot, $batch->getRawOriginal('expires_on'), $batch->quarantine || ($batch->expires_on && $batch->expires_on->toDateString() < now('America/Sao_Paulo')->toDateString()));
                        $parts[] = ['batch_id' => $returned->id, 'quantity' => Q::value($part)];
                    }
                    DB::table('sale_item_batches')->where('id', $allocation->id)->update(['returned_quantity' => Q::value(Q::milli($allocation->returned_quantity) + $part)]);
                    $remaining -= $part;
                }
                if ($remaining > 0 && $row['disposition'] === 'restock') {
                    $batch = app(BatchInventory::class)->add($p, 'gondola', Q::value($remaining), 'DEVOLUCAO-LEGADO-'.$sale->id, null, (bool) $p->validade);
                    $parts[] = ['batch_id' => $batch->id, 'quantity' => Q::value($remaining)];
                }
                if ($row['disposition'] === 'restock') {
                    $p->gondola = Q::value(Q::milli($p->gondola) + $qty);
                    $p->movimentado_em = now();
                    $p->save();
                }
                StockMovement::create(['operation_id' => (string) Str::uuid(), 'request_hash' => $hash, 'product_id' => $p->id, 'user_id' => $user->id, 'operacao' => $row['disposition'] === 'restock' ? 'estorno' : 'devolucao_avaria', 'local' => 'gondola', 'quantidade' => $row['quantity'], 'saldo_anterior' => $before, 'saldo_final' => $p->gondola, 'documento' => 'Devolução venda #'.$sale->id, 'motivo' => $data['reason'], 'allocations' => $parts]);
                $item->returned_quantity = Q::value($cumulative);
                $item->returned_cents += $cents;
                $item->save();
                $saved[] = $row + ['amount_cents' => $cents, 'product_name' => $item->name];
            }
            if ($sale->payment_method === 'Dinheiro' && app(ManageSession::class)->expected($session) < $amount) {
                throw ValidationException::withMessages(['items' => 'Dinheiro insuficiente na gaveta para o reembolso. Registre um suprimento antes de devolver.']);
            }
            $return = SaleReturn::create(['operation_id' => $data['operation_id'], 'request_hash' => $hash, 'sale_id' => $sale->id, 'user_id' => $user->id, 'cash_session_id' => $session->id, 'reason' => $data['reason'], 'amount_cents' => $amount, 'cost_cents' => $cost, 'items' => $saved]);
            $sale->returned_cents += $amount;
            $full = $sale->items()->whereColumn('returned_quantity', '<', 'quantity')->doesntExist();
            $sale->status = $full ? 'refunded' : 'partially_refunded';
            $sale->refunded_by = $user->id;
            $sale->refunded_at = now();
            $sale->refund_reason = $data['reason'];
            $sale->save();
            if ($sale->payment_method === 'Dinheiro') {
                CashEntry::create(['operation_id' => $data['operation_id'], 'request_hash' => $hash, 'cash_session_id' => $session->id, 'user_id' => $user->id, 'kind' => 'refund', 'amount_cents' => -$amount, 'reason' => 'Devolução venda #'.$sale->id]);
            }

            return $return;
        });
    }
}
