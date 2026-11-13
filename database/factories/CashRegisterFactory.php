<?php

namespace Database\Factories;

use App\Models\CashRegister;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashRegister> */
class CashRegisterFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Terminal '.fake()->unique()->numerify('######'), 'active_session_id' => null];
    }
}
