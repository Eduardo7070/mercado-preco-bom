<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockBatch> */
class StockBatchFactory extends Factory
{
    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'local' => 'deposito', 'lot' => 'LOTE-'.fake()->unique()->numerify('######'), 'expires_on' => now()->addMonth(), 'quantity' => 0, 'quarantine' => false];
    }
}
