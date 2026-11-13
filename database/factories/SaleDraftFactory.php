<?php

namespace Database\Factories;

use App\Models\SaleDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SaleDraft> */
class SaleDraftFactory extends Factory
{
    public function definition(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'user_id' => User::factory(), 'label' => 'Atendimento de teste', 'items' => [], 'status' => 'suspended'];
    }
}
