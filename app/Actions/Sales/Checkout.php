<?php

namespace App\Actions\Sales;

use App\Actions\Cash\ManageSession;
use App\Actions\Operations\Fingerprint as F;
use App\Actions\Operations\Quantity as Q;
use App\Actions\Stock\BatchInventory;
use App\Models\CashEntry;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDraft;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Checkout
{
    public function execute(array $data, User $user): Sale
    {
        return DB::transaction(function () use ($data, $user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $itemsData = $data['items'];
            usort($itemsData, fn ($a, $b) => $a['product_id'] <=> $b['product_id']);
            $canonical = ['items' => $itemsData, 'customer_id' => $data['customer_id'] ?? null, 'customer_name' => $data['customer_name'] ?? null, 'payment_method' => $data['payment_method'], 'received' => $data['payment_method'] === 'Dinheiro' ? ($data['received'] ?? null) : null, 'cash_session_id' => $data['cash_session_id'] ?? null, 'draft_id' => $data['draft_id'] ?? null];
            $hash = F::make($canonical);
            $existing = Sale::where('operation_id', $data['operation_id'])->first();
            if ($existing) {
                abort_unless($existing->user_id === $user->id, 403);
                F::assert($existing->request_hash, $hash);

                return $existing;
            }
            $session = app(ManageSession::class)->requireOpen($user, isset($data['cash_session_id']) ? (int) $data['cash_session_id'] : null);
            $draft = empty($data['draft_id']) ? null : SaleDraft::whereKey($data['draft_id'])->where('user_id', $user->id)->where('status', 'suspended')->lockForUpdate()->first();
            if (! empty($data['draft_id']) && ! $draft) {
                throw ValidationException::withMessages(['draft_id' => 'Atendimento suspenso indisponível. Atualize o caixa.']);
            }
            $products = Product::whereIn('id', array_column($itemsData, 'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $total = 0;
            $items = [];
            foreach ($itemsData as $row) {
                $p = $products->get($row['product_id']);
                Q::checkUnit($p?->unidade ?? 'UN', $row['quantity']);
                if (! $p || ! $p->ativo || Q::milli($row['quantity']) === 0 || Q::milli($p->gondola) < Q::milli($row['quantity'])) {
                    throw ValidationException::withMessages(['items' => 'Produto inativo ou saldo insuficiente na gôndola.']);
                }
                $price = Q::cents($p->getRawOriginal('preco'));
                $line = Q::line($price, $row['quantity']);
                $total += $line;
                if ($price <= 0 || $line <= 0 || $total > 9999999999) {
                    throw ValidationException::withMessages(['items' => 'Valor da venda inválido ou acima do limite.']);
                }
                $items[] = ['product_id' => $p->id, 'name' => $p->nome, 'code' => $p->codigo ?? $p->barras, 'unit' => $p->unidade, 'quantity' => Q::value(Q::milli($row['quantity'])), 'unit_price_cents' => $price, 'total_cents' => $line, 'unit_cost_cents' => Q::cents($p->custo), 'category_name' => $p->categoria];
            }
            $received = $data['payment_method'] === 'Dinheiro' ? Q::cents($data['received']) : $total;
            if ($received < $total) {
                throw ValidationException::withMessages(['received' => 'Valor recebido insuficiente.']);
            }
            $customer = isset($data['customer_id']) ? Customer::where('active', true)->findOrFail($data['customer_id']) : null;
            $sale = Sale::create(['operation_id' => $data['operation_id'], 'request_hash' => $hash, 'user_id' => $user->id, 'cash_session_id' => $session->id, 'customer_id' => $customer?->id, 'customer_name' => $customer?->name ?? ($data['customer_name'] ?? null), 'terminal' => CashRegister::findOrFail($session->cash_register_id)->name, 'payment_method' => $data['payment_method'], 'total_cents' => $total, 'received_cents' => $received, 'change_cents' => $received - $total, 'status' => 'paid', 'simulated' => true]);
            foreach ($items as $item) {
                $p = $products[$item['product_id']];
                $sold = $sale->items()->create($item);
                $before = $p->gondola;
                $parts = app(BatchInventory::class)->take($p, 'gondola', $item['quantity'], true);
                foreach ($parts as $part) {
                    DB::table('sale_item_batches')->insert(['sale_item_id' => $sold->id, 'stock_batch_id' => $part['batch_id'], 'quantity' => $part['quantity'], 'returned_quantity' => 0]);
                }
                $p->gondola = Q::value(Q::milli($before) - Q::milli($item['quantity']));
                $p->movimentado_em = now();
                $p->save();
                StockMovement::create(['operation_id' => (string) Str::uuid(), 'request_hash' => $hash, 'product_id' => $p->id, 'user_id' => $user->id, 'operacao' => 'venda', 'local' => 'gondola', 'quantidade' => $item['quantity'], 'saldo_anterior' => $before, 'saldo_final' => $p->gondola, 'documento' => 'Venda #'.$sale->id, 'motivo' => 'Venda com pagamento simulado.', 'allocations' => $parts]);
            }
            if ($sale->payment_method === 'Dinheiro') {
                CashEntry::create(['operation_id' => $data['operation_id'], 'request_hash' => $hash, 'cash_session_id' => $session->id, 'user_id' => $user->id, 'kind' => 'sale', 'amount_cents' => $total, 'reason' => 'Venda #'.$sale->id]);
            }
            if ($draft) {
                $draft->update(['status' => 'completed']);
            }

            return $sale;
        });
    }
}
