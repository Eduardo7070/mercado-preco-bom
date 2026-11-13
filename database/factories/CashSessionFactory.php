<?php

namespace Database\Factories;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashSession> */
class CashSessionFactory extends Factory
{
    public function definition(): array
    {
        return ['cash_register_id' => CashRegister::factory(), 'user_id' => User::factory(), 'status' => 'closed', 'opening_cents' => 0, 'expected_cents' => 0, 'counted_cents' => 0, 'difference_cents' => 0, 'closed_at' => now()];
    }
}
