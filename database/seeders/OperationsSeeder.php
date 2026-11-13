<?php

namespace Database\Seeders;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Profile;
use Illuminate\Database\Seeder;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Mercearia', 'Laticínios', 'Limpeza', 'Hortifruti', 'Bebidas'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
        foreach (['Caixa 01', 'Caixa 02', 'Caixa 03'] as $name) {
            CashRegister::firstOrCreate(['name' => $name]);
        }
        foreach (['caixa.gerenciar', 'clientes.gerenciar', 'fornecedores.gerenciar', 'categorias.gerenciar', 'compras.gerenciar', 'relatorios.consultar'] as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['name' => ucfirst(str_replace('.', ' / ', $slug))]);
            if (! $p->wasRecentlyCreated) {
                continue;
            }
            foreach (Profile::whereIn('slug', ['admin', 'gerente'])->get() as $profile) {
                $profile->permissions()->syncWithoutDetaching([$p->id]);
            }
            if ($slug === 'clientes.gerenciar') {
                Profile::where('slug', 'caixa')->first()?->permissions()->syncWithoutDetaching([$p->id]);
            }
        }
    }
}
