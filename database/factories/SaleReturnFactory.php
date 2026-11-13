<?php

namespace Database\Factories;

use App\Models\CashSession;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SaleReturn> */
class SaleReturnFactory extends Factory
{
    public function definition(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'factory'), 'user_id' => User::factory(), 'cash_session_id' => CashSession::factory(), 'sale_id' => fn () => Sale::create(['operation_id' => (string) Str::uuid(), 'user_id' => User::factory()->create()->id, 'payment_method' => 'Pix', 'terminal' => 'Terminal de teste', 'status' => 'paid', 'total_cents' => 0, 'received_cents' => 0, 'change_cents' => 0, 'simulated' => true])->id, 'reason' => 'Devolução de teste', 'amount_cents' => 0, 'cost_cents' => 0, 'items' => []];
    }
}
