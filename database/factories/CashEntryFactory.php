<?php

namespace Database\Factories;

use App\Models\CashEntry;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CashEntry> */
class CashEntryFactory extends Factory
{
    public function definition(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'factory'), 'cash_session_id' => CashSession::factory(), 'user_id' => User::factory(), 'kind' => 'supply', 'amount_cents' => 1000, 'reason' => 'Suprimento de teste'];
    }
}
