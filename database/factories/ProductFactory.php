<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        Category::firstOrCreate(['name' => 'Mercearia']);

        return ['codigo' => 'TEST-'.fake()->unique()->numerify('########'), 'nome' => fake()->words(3, true), 'categoria' => 'Mercearia', 'unidade' => 'UN', 'preco' => 10, 'custo' => 5, 'validade' => false];
    }
}
