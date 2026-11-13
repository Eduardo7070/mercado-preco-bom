<?php

namespace App\Actions\Sales;

use App\Models\Sale;
use App\Models\User;
use Ramsey\Uuid\Uuid;

class RefundSale
{
    public function execute(Sale $sale, string $reason, User $user): void
    {
        $sale->refresh();
        if ($sale->status === 'refunded') {
            return;
        }
        $items = $sale->items()->whereColumn('returned_quantity', '<', 'quantity')->get()->map(fn ($item) => ['sale_item_id' => $item->id, 'quantity' => (float) $item->quantity - (float) $item->returned_quantity, 'disposition' => 'restock'])->all();
        app(ReturnItems::class)->execute($sale, ['operation_id' => (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'full-return:'.$sale->id), 'reason' => $reason, 'items' => $items], $user);
    }
}
