<?php

namespace App\Actions\Sales;

use App\Models\Product;
use App\Models\Sale;
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
            $existing = Sale::where('operation_id', $data['operation_id'])->first();
            if ($existing) {
                abort_unless($existing->user_id === $user->id, 403);

                return $existing;
            }
            $products = Product::whereIn('id', array_column($data['items'], 'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $total = 0;
            $items = [];
            foreach ($data['items'] as $row) {
                $p = $products->get($row['product_id']);
                if (! $p || ! $p->ativo || $p->gondola < $row['quantity']) {
                    throw ValidationException::withMessages(['items' => 'Produto inativo ou estoque insuficiente na gôndola.']);
                }
                $price = (int) round((float) $p->getRawOriginal('preco') * 100);
                if ($price <= 0) {
                    throw ValidationException::withMessages(['items' => 'Produto sem preço válido.']);
                }
                $line = $price * $row['quantity'];
                $total += $line;
                if ($total > 9999999999) {
                    throw ValidationException::withMessages(['items' => 'Total da venda acima do limite permitido.']);
                }
                $items[] = ['product_id' => $p->id, 'name' => $p->nome, 'code' => $p->codigo ?? $p->barras, 'unit' => $p->unidade, 'quantity' => $row['quantity'], 'unit_price_cents' => $price, 'total_cents' => $line];
            }
            $received = $data['payment_method'] === 'Dinheiro' ? (int) round((float) $data['received'] * 100) : $total;
            if ($received < $total) {
                throw ValidationException::withMessages(['received' => 'Valor recebido insuficiente.']);
            }
            $sale = Sale::create(['operation_id' => $data['operation_id'], 'user_id' => $user->id, 'customer_name' => $data['customer_name'] ?? null, 'terminal' => 'Caixa 01', 'payment_method' => $data['payment_method'], 'total_cents' => $total, 'received_cents' => $received, 'change_cents' => $received - $total, 'status' => 'paid', 'simulated' => true]);
            foreach ($items as $item) {
                $sale->items()->create($item);
                $p = $products[$item['product_id']];
                $before = $p->gondola;
                $p->gondola -= $item['quantity'];
                $p->movimentado_em = now();
                $p->save();
                StockMovement::create(['operation_id' => (string) Str::uuid(), 'product_id' => $p->id, 'user_id' => $user->id, 'operacao' => 'venda', 'local' => 'gondola', 'quantidade' => $item['quantity'], 'saldo_anterior' => $before, 'saldo_final' => $p->gondola, 'documento' => 'Venda #'.$sale->id, 'motivo' => 'Venda com pagamento simulado.']);
            }

            return $sale;
        });
    }
}
