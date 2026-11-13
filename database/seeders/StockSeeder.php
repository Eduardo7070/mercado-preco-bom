<?php

namespace Database\Seeders;

use App\Actions\Stock\RecordMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::whereHas('profile', fn ($query) => $query->where('slug', 'admin'))->orderBy('id')->firstOrFail();
        DB::transaction(function () use ($user) {
            foreach (ProductSeeder::ITEMS as $item) {
                $product = Product::where('codigo', $item['codigo'])->lockForUpdate()->firstOrFail();
                if (! $product->ativo || $product->movements()->exists() || $product->deposito > 0 || $product->gondola > 0) {
                    continue;
                }
                foreach (['deposito', 'gondola'] as $local) {
                    if ($item[$local] === 0) {
                        continue;
                    }
                    app(RecordMovement::class)->execute([
                        'operation_id' => (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'mercado-preco-bom:stock-seeder:'.$product->id.':'.$local),
                        'product_id' => $product->id,
                        'local' => $local,
                        'quantidade' => $item[$local],
                        'documento' => 'Carga inicial de demonstração',
                        'lote' => $product->validade ? 'DEMO-'.$product->codigo : null,
                        'validade' => $product->validade ? now()->addMonths(6)->toDateString() : null,
                        'motivo' => 'Saldo inicial criado pelo StockSeeder.',
                    ], 'entrada', $user);
                }
            }
        });
    }
}
