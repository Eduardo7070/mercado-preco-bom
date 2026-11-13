<?php

namespace App\Actions\Stock;

use App\Actions\Operations\Quantity as Q;
use App\Models\Product;
use App\Models\StockBatch;
use Illuminate\Validation\ValidationException;

class BatchInventory
{
    public function add(Product $p, string $local, mixed $quantity, ?string $lot, ?string $expires, bool $quarantine = false): StockBatch
    {
        $lot = $lot ?: 'SEM-LOTE';
        $batch = StockBatch::where('product_id', $p->id)->where('local', $local)->where('lot', $lot)->where('expires_on', $expires)->where('quarantine', $quarantine)->lockForUpdate()->first();
        if (! $batch) {
            $batch = StockBatch::create(['product_id' => $p->id, 'local' => $local, 'lot' => $lot, 'expires_on' => $expires, 'quantity' => 0, 'quarantine' => $quarantine]);
        }
        $batch->quantity = Q::value(Q::milli($batch->quantity) + Q::milli($quantity));
        $batch->save();

        return $batch;
    }

    public function take(Product $p, string $local, mixed $quantity, bool $sellable = false): array
    {
        $remaining = Q::milli($quantity);
        $parts = [];
        $query = StockBatch::where('product_id', $p->id)->where('local', $local)->where('quantity', '>', 0);
        if ($sellable) {
            if ($p->validade) {
                $query->whereNotNull('expires_on');
            }
            $query->where('quarantine', false)->where(fn ($q) => $q->whereNull('expires_on')->orWhere('expires_on', '>=', now('America/Sao_Paulo')->toDateString()));
        }
        $batches = $query->orderByRaw('expires_on IS NULL')->orderBy('expires_on')->orderBy('id')->lockForUpdate()->get();
        foreach ($batches as $batch) {
            $take = min($remaining, Q::milli($batch->quantity));
            if ($take <= 0) {
                continue;
            }
            $batch->quantity = Q::value(Q::milli($batch->quantity) - $take);
            $batch->save();
            $parts[] = ['batch_id' => $batch->id, 'quantity' => Q::value($take), 'lot' => $batch->lot, 'expires_on' => $batch->getRawOriginal('expires_on'), 'quarantine' => $batch->quarantine];
            $remaining -= $take;
        }
        if ($remaining > 0) {
            throw ValidationException::withMessages(['quantidade' => $sellable ? 'Saldo vendável insuficiente. Há lotes vencidos, bloqueados ou sem validade regularizada.' : 'Saldo por lote insuficiente. Confira o estoque.']);
        }

        return $parts;
    }
}
