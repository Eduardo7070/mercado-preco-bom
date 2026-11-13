<?php

namespace Tests\Feature;

use App\Actions\Stock\RecordMovement;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductStockSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_products_and_audited_balances_without_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('products', 12);
        $this->assertDatabaseCount('stock_movements', 22);
        foreach (ProductSeeder::ITEMS as $item) {
            $this->assertDatabaseHas('products', [
                'codigo' => $item['codigo'],
                'deposito' => $item['deposito'],
                'gondola' => $item['gondola'],
            ]);
        }
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('products', 12);
        $this->assertDatabaseCount('stock_movements', 22);
        $this->assertSame(22, StockMovement::whereNotNull('user_id')->count());
    }

    public function test_reseeding_preserves_product_edits_and_stock_operations(): void
    {
        $this->seed(DatabaseSeeder::class);
        $product = Product::where('codigo', 'DEMO-1001')->firstOrFail();
        $product->update(['nome' => 'Arroz atualizado', 'preco' => 30]);
        app(RecordMovement::class)->execute([
            'product_id' => $product->id, 'operation_id' => (string) Str::uuid(),
            'local' => 'deposito', 'quantidade' => 5, 'motivo' => 'Saída após carga inicial',
        ], 'saida', User::where('email', 'admin@mercadoprecobom.local')->firstOrFail());
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(35, $product->fresh()->deposito);
        $this->assertSame('Arroz atualizado', $product->fresh()->nome);
        $this->assertSame(30.0, $product->fresh()->preco);
        $this->assertDatabaseCount('stock_movements', 23);
    }
}
