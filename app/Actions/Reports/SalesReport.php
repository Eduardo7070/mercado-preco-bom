<?php

namespace App\Actions\Reports;

use App\Actions\Operations\Quantity as Q;
use App\Actions\Sales\SearchSales;
use App\Models\SaleReturn;
use App\Models\User;

class SalesReport
{
    public function execute(array $filters, User $user): array
    {
        $query = app(SearchSales::class)->query($user, $filters)->with('items');
        $gross = (int) (clone $query)->sum('total_cents');
        $returned = (int) (clone $query)->sum('returned_cents');
        $cost = 0;
        $unknown = 0;
        foreach ((clone $query)->lazyById(200) as $sale) {
            $damaged = [];
            foreach (SaleReturn::where('sale_id', $sale->id)->get() as $return) {
                foreach ($return->items as $row) {
                    if ($row['disposition'] === 'discard') {
                        $damaged[$row['sale_item_id']] = ($damaged[$row['sale_item_id']] ?? 0) + Q::milli($row['quantity']);
                    }
                }
            }
            foreach ($sale->items as $item) {
                $expensed = Q::value(Q::milli($item->quantity) - Q::milli($item->returned_quantity) + ($damaged[$item->id] ?? 0));
                $cost += Q::line($item->unit_cost_cents, $expensed);
                if (! $item->unit_cost_cents && $expensed > 0) {
                    $unknown++;
                }
            }
        }

        return ['gross' => $gross, 'returned' => $returned, 'net' => $gross - $returned, 'cost' => $cost, 'margin' => $gross - $returned - $cost, 'unknown' => $unknown, 'sales' => $query->latest('id')->paginate(30)->withQueryString()];
    }
}
