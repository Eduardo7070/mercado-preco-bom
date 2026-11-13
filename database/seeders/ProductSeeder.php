<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public const ITEMS = [
        ['codigo' => 'DEMO-1001', 'nome' => 'Arroz branco 5 kg', 'categoria' => 'Mercearia', 'unidade' => 'UN', 'preco' => 25.90, 'validade' => true, 'deposito' => 40, 'gondola' => 12],
        ['codigo' => 'DEMO-1002', 'nome' => 'Feijão carioca 1 kg', 'categoria' => 'Mercearia', 'unidade' => 'UN', 'preco' => 8.49, 'validade' => true, 'deposito' => 30, 'gondola' => 15],
        ['codigo' => 'DEMO-1003', 'nome' => 'Café tradicional 500 g', 'categoria' => 'Mercearia', 'unidade' => 'UN', 'preco' => 18.90, 'validade' => true, 'deposito' => 0, 'gondola' => 0],
        ['codigo' => 'DEMO-1004', 'nome' => 'Açúcar refinado 1 kg', 'categoria' => 'Mercearia', 'unidade' => 'UN', 'preco' => 4.79, 'validade' => true, 'deposito' => 25, 'gondola' => 10],
        ['codigo' => 'DEMO-1005', 'nome' => 'Óleo de soja 900 ml', 'categoria' => 'Mercearia', 'unidade' => 'UN', 'preco' => 7.29, 'validade' => true, 'deposito' => 24, 'gondola' => 8],
        ['codigo' => 'DEMO-1006', 'nome' => 'Leite integral 1 L', 'categoria' => 'Laticínios', 'unidade' => 'UN', 'preco' => 5.49, 'validade' => true, 'deposito' => 48, 'gondola' => 24],
        ['codigo' => 'DEMO-1007', 'nome' => 'Queijo muçarela 500 g', 'categoria' => 'Laticínios', 'unidade' => 'UN', 'preco' => 22.90, 'validade' => true, 'deposito' => 10, 'gondola' => 6],
        ['codigo' => 'DEMO-1008', 'nome' => 'Detergente neutro 500 ml', 'categoria' => 'Limpeza', 'unidade' => 'UN', 'preco' => 2.69, 'validade' => false, 'deposito' => 30, 'gondola' => 12],
        ['codigo' => 'DEMO-1009', 'nome' => 'Sabão em pó 1 kg', 'categoria' => 'Limpeza', 'unidade' => 'UN', 'preco' => 12.50, 'validade' => false, 'deposito' => 2, 'gondola' => 2],
        ['codigo' => 'DEMO-1010', 'nome' => 'Banana prata', 'categoria' => 'Hortifruti', 'unidade' => 'KG', 'preco' => 6.99, 'validade' => false, 'deposito' => 20, 'gondola' => 10],
        ['codigo' => 'DEMO-1011', 'nome' => 'Água mineral 1,5 L', 'categoria' => 'Bebidas', 'unidade' => 'UN', 'preco' => 3.49, 'validade' => true, 'deposito' => 36, 'gondola' => 18],
        ['codigo' => 'DEMO-1012', 'nome' => 'Suco de uva integral 1 L', 'categoria' => 'Bebidas', 'unidade' => 'UN', 'preco' => 13.90, 'validade' => true, 'deposito' => 15, 'gondola' => 6],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::ITEMS as $item) {
                $data = $item;
                unset($data['deposito'], $data['gondola']);
                Product::firstOrCreate(['codigo' => $data['codigo']], $data);
            }
        });
    }
}
