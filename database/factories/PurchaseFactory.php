<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Purchase> */
class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'factory'), 'user_id' => User::factory(), 'supplier_id' => Supplier::factory(), 'document' => fake()->unique()->numerify('NF-######'), 'total_cents' => 0, 'items' => []];
    }
}
