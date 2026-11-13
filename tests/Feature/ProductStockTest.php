<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
        $this->actingAs(User::where('email', 'admin@mercadoprecobom.local')->firstOrFail());
    }

    private function product(array $overrides = []): Product
    {
        $this->post(route('produtos.store'), array_merge(['codigo' => '1001', 'barras' => '7891000000011', 'nome' => 'Arroz branco 5 kg', 'categoria' => 'Mercearia', 'unidade' => 'UN', 'preco' => '25.00'], $overrides))->assertSessionHasNoErrors()->assertRedirect(route('produtos.cadastrar'));

        return Product::latest('id')->firstOrFail();
    }

    private function movement(Product $product, array $data = []): array
    {
        return array_merge(['product_id' => $product->id, 'operation_id' => (string) Str::uuid(), 'local' => 'deposito', 'quantidade' => 10], $data);
    }

    public function test_product_is_persisted_with_zero_stock_and_unique_identifiers(): void
    {
        $product = $this->product();
        $this->assertSame(0, $product->deposito);
        $this->assertTrue($product->ativo);
        $this->post(route('produtos.store'), ['codigo' => '1001', 'nome' => 'Duplicado', 'categoria' => 'Mercearia', 'unidade' => 'UN', 'preco' => 10])->assertSessionHasErrors('codigo');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_identifiers_and_positive_price_are_required(): void
    {
        $this->post(route('produtos.store'), ['nome' => 'Sem código', 'categoria' => 'Mercearia', 'unidade' => 'UN', 'preco' => 0])->assertSessionHasErrors(['codigo', 'barras', 'preco']);
    }

    public function test_stock_entry_is_audited_and_duplicate_submission_does_not_add_twice(): void
    {
        $product = $this->product();
        $data = $this->movement($product);
        for ($i = 0; $i < 2; $i++) {
            $this->post('/estoque/entrada', $data)->assertSessionHasNoErrors()->assertRedirect(route('estoque.entrada'));
        }
        $this->assertSame(10, $product->fresh()->deposito);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'saldo_anterior' => 0, 'saldo_final' => 10, 'user_id' => auth()->id()]);
    }

    public function test_entry_requires_lot_and_expiration_for_controlled_product(): void
    {
        $product = $this->product(['validade' => true]);
        $this->post('/estoque/entrada', $this->movement($product))->assertSessionHasErrors(['lote', 'validade']);
        $this->assertSame(0, $product->fresh()->deposito);
        $this->post('/estoque/entrada', $this->movement($product, ['lote' => 'L001', 'validade' => now()->addMonth()->toDateString()]))->assertSessionHasNoErrors();
        $this->assertSame(10, $product->fresh()->deposito);
    }

    public function test_exit_cannot_exceed_stock_and_adjustment_records_real_counts(): void
    {
        $product = $this->product();
        $this->post('/estoque/saida', $this->movement($product, ['motivo' => 'Devolução']))->assertSessionHasErrors('quantidade');
        $this->assertDatabaseCount('stock_movements', 0);
        $this->post('/estoque/entrada', $this->movement($product))->assertSessionHasNoErrors();
        $this->post('/estoque/saida', $this->movement($product, ['quantidade' => 3, 'motivo' => 'Devolução']))->assertSessionHasNoErrors();
        $this->assertSame(7, $product->fresh()->deposito);
        $this->post('/estoque/ajustar', $this->movement($product, ['quantidade' => 5, 'tipo' => 'contagem', 'motivo' => 'Inventário']))->assertSessionHasNoErrors();
        $this->assertSame(5, $product->fresh()->deposito);
        $this->post('/estoque/ajustar', $this->movement($product, ['quantidade' => 1, 'tipo' => 'avaria', 'motivo' => 'Embalagem danificada']))->assertSessionHasNoErrors();
        $this->assertSame(4, $product->fresh()->deposito);
    }

    public function test_search_finds_partial_name_code_and_barcode_and_empty_results(): void
    {
        $this->product();
        foreach (['Arroz', '100', '7891'] as $term) {
            $this->getJson(route('produtos.search', ['q' => $term]))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.nome', 'Arroz branco 5 kg');
        }
        $this->getJson(route('produtos.search', ['q' => 'inexistente']))->assertOk()->assertJsonPath('total', 0);
        $this->getJson(route('produtos.search', ['situacao' => 'inativos']))->assertOk()->assertJsonPath('total', 0);
    }

    public function test_cashier_cannot_create_products_or_move_stock(): void
    {
        $product = $this->product();
        $this->actingAs(User::where('email', 'caixa@mercadoprecobom.local')->firstOrFail());
        $this->post(route('produtos.store'), [])->assertForbidden();
        $this->post('/estoque/entrada', $this->movement($product))->assertForbidden();
        $this->getJson(route('produtos.search'))->assertOk();
    }

    public function test_database_forms_and_consultations_render(): void
    {
        foreach (['/produtos/cadastrar', '/produtos/consultar', '/estoque/entrada', '/estoque/saida', '/estoque/ajustar', '/estoque/consultar'] as $path) {
            $this->get($path)->assertOk();
        }
    }
}
