<?php

namespace Tests\Feature;

use App\Models\Permission;
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
        $this->assertEquals(0, $product->deposito);
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
        $this->assertEquals(10, $product->fresh()->deposito);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'saldo_anterior' => 0, 'saldo_final' => 10, 'user_id' => auth()->id()]);
    }

    public function test_entry_requires_lot_and_expiration_for_controlled_product(): void
    {
        $product = $this->product(['validade' => true]);
        $this->post('/estoque/entrada', $this->movement($product))->assertSessionHasErrors(['lote', 'validade']);
        $this->assertEquals(0, $product->fresh()->deposito);
        $this->post('/estoque/entrada', $this->movement($product, ['lote' => 'L001', 'validade' => now()->addMonth()->toDateString()]))->assertSessionHasNoErrors();
        $this->assertEquals(10, $product->fresh()->deposito);
    }

    public function test_exit_cannot_exceed_stock_and_adjustment_records_real_counts(): void
    {
        $product = $this->product();
        $this->post('/estoque/saida', $this->movement($product, ['motivo' => 'Devolução']))->assertSessionHasErrors('quantidade');
        $this->assertDatabaseCount('stock_movements', 0);
        $this->post('/estoque/entrada', $this->movement($product))->assertSessionHasNoErrors();
        $this->post('/estoque/saida', $this->movement($product, ['quantidade' => 3, 'motivo' => 'Devolução']))->assertSessionHasNoErrors();
        $this->assertEquals(7, $product->fresh()->deposito);
        $this->post('/estoque/ajustar', $this->movement($product, ['quantidade' => 5, 'tipo' => 'contagem', 'motivo' => 'Inventário']))->assertSessionHasNoErrors();
        $this->assertEquals(5, $product->fresh()->deposito);
        $this->post('/estoque/ajustar', $this->movement($product, ['quantidade' => 1, 'tipo' => 'avaria', 'motivo' => 'Embalagem danificada']))->assertSessionHasNoErrors();
        $this->assertEquals(4, $product->fresh()->deposito);
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
        foreach (['/produtos/cadastrar', '/produtos/consultar', '/estoque/entrada', '/estoque/saida', '/estoque/consultar'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_stock_product_suggestions_search_name_code_barcode_and_limit_results(): void
    {
        $product = $this->product();
        foreach (['Arroz', '100', '7891'] as $term) {
            $this->getJson(route('estoque.products.search', ['operation' => 'entrada', 'q' => $term]))->assertOk()
                ->assertJsonPath('data.0.id', $product->id)->assertJsonPath('has_more', false)
                ->assertJsonStructure(['data' => [['id', 'nome', 'unidade', 'deposito', 'gondola', 'validade', 'ativo']]]);
        }
        Product::factory()->count(15)->create(['nome' => 'Produto para busca paginada']);
        $this->getJson(route('estoque.products.search', ['operation' => 'entrada', 'q' => 'busca paginada']))
            ->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('has_more', true);
        $this->getJson(route('estoque.products.search', ['operation' => 'entrada', 'q' => 'inexistente']))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(route('estoque.products.search', ['operation' => 'entrada']))->assertUnprocessable();
        foreach (['entrada', 'saida'] as $operation) {
            $this->get(route('estoque.'.$operation))->assertOk()->assertSee('role="combobox"', false)
                ->assertDontSee('id="produtos-estoque"', false)->assertDontSee('Produto para busca paginada');
        }
    }

    public function test_stock_suggestions_respect_operation_permissions_and_inactive_products(): void
    {
        $product = $this->product();
        $product->ativo = false;
        $product->save();
        $this->getJson(route('estoque.products.search', ['operation' => 'entrada', 'q' => 'Arroz']))->assertOk()->assertJsonCount(0, 'data');
        foreach (['saida', 'ajustar'] as $operation) {
            $this->getJson(route('estoque.products.search', ['operation' => $operation, 'q' => 'Arroz']))->assertOk()->assertJsonPath('data.0.id', $product->id);
        }
        $user = User::where('email', 'caixa@mercadoprecobom.local')->firstOrFail();
        $this->actingAs($user)->getJson(route('estoque.products.search', ['operation' => 'entrada', 'q' => 'Arroz']))->assertForbidden();
        $permission = Permission::where('slug', 'estoque.entrada')->sole();
        $user->profile->permissions()->sync([$permission->id]);
        $this->getJson(route('estoque.products.search', ['operation' => 'entrada', 'q' => 'Arroz']))->assertOk();
        $this->getJson(route('estoque.products.search', ['operation' => 'saida', 'q' => 'Arroz']))->assertForbidden();
    }

    public function test_unified_exit_supports_losses_and_physical_counts(): void
    {
        $product = $this->product();
        $this->post('/estoque/entrada', $this->movement($product))->assertSessionHasNoErrors();
        $this->get(route('estoque.ajustar'))->assertRedirect(route('estoque.saida', ['tipo' => 'contagem']));
        $this->get(route('estoque.saida'))->assertOk()->assertSee('Corrigir saldo por contagem')
            ->assertDontSee('>Ajustar estoque</a>', false);
        $this->post('/estoque/saida', $this->movement($product, ['tipo' => 'avaria', 'quantidade' => 2, 'motivo' => 'Embalagem danificada']))
            ->assertSessionHasNoErrors()->assertRedirect(route('estoque.saida'));
        $this->assertEquals(8, $product->fresh()->deposito);
        $this->assertDatabaseHas('stock_movements', ['operacao' => 'avaria', 'saldo_final' => 8]);
        foreach ([12, 0] as $count) {
            $this->post('/estoque/saida', $this->movement($product, ['tipo' => 'contagem', 'quantidade' => $count, 'motivo' => 'Conferência física']))
                ->assertSessionHasNoErrors()->assertRedirect(route('estoque.saida'));
            $this->assertEquals($count, $product->fresh()->deposito);
            $this->assertDatabaseHas('stock_movements', ['operacao' => 'contagem', 'saldo_final' => $count]);
        }
    }

    public function test_unified_exit_keeps_permissions_for_each_movement_type(): void
    {
        $product = $this->product();
        $this->post('/estoque/entrada', $this->movement($product))->assertSessionHasNoErrors();
        $user = User::where('email', 'caixa@mercadoprecobom.local')->firstOrFail();
        $user->profile->permissions()->sync([Permission::where('slug', 'estoque.saida')->sole()->id]);
        $this->actingAs($user);
        $this->get(route('estoque.saida'))->assertOk()->assertSee('Retirada / devolução')->assertDontSee('value="contagem"', false);
        $this->post('/estoque/saida', $this->movement($product, ['tipo' => 'avaria', 'motivo' => 'Avaria']))->assertForbidden();
        $this->post('/estoque/saida', $this->movement($product, ['tipo' => 'contagem', 'motivo' => 'Inventário']))->assertForbidden();
        $this->post('/estoque/saida', $this->movement($product, ['tipo' => 'saida', 'quantidade' => 1, 'motivo' => 'Devolução']))->assertSessionHasNoErrors();
        $user->profile->permissions()->sync([Permission::where('slug', 'estoque.ajustar')->sole()->id]);
        $user->unsetRelation('profile');
        $this->get(route('estoque.saida'))->assertOk()->assertSee('value="contagem"', false)->assertDontSee('value="saida"', false);
        $this->getJson(route('estoque.products.search', ['operation' => 'saida', 'q' => 'Arroz']))->assertOk();
        $this->post('/estoque/saida', $this->movement($product, ['tipo' => 'saida', 'motivo' => 'Devolução']))->assertForbidden();
        $this->post('/estoque/saida', $this->movement($product, ['tipo' => 'contagem', 'quantidade' => 7, 'motivo' => 'Inventário']))->assertSessionHasNoErrors();
        $this->assertEquals(7, $product->fresh()->deposito);
    }
}
