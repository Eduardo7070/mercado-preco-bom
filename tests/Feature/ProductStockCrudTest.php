<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductStockCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'admin@mercadoprecobom.local')->firstOrFail());
    }

    private function product(): Product
    {
        return Product::where('codigo', 'DEMO-1001')->firstOrFail();
    }

    private function body(Product $p, array $data = []): array
    {
        return array_merge($p->only('codigo', 'barras', 'nome', 'categoria', 'unidade', 'preco', 'validade'), ['version' => $p->version()], $data);
    }

    public function test_edit_is_persisted_audited_and_rejects_duplicate_barcode_or_stale_version(): void
    {
        $p = $this->product();
        $p->update(['barras' => '12345']);
        $p->refresh();
        $old = $this->body($p, ['nome' => 'Arroz atualizado', 'preco' => 29.99]);
        $this->putJson(route('produtos.update', $p), $old)->assertOk();
        $this->assertSame('Arroz atualizado', $p->fresh()->nome);
        $this->assertDatabaseHas('product_audits', ['product_id' => $p->id, 'action' => 'edicao', 'user_id' => auth()->id()]);
        $this->putJson(route('produtos.update', $p), $old)->assertUnprocessable()->assertJsonValidationErrors('version');
        $second = Product::where('codigo', 'DEMO-1002')->firstOrFail();
        $second->update(['barras' => '98765']);
        $this->putJson(route('produtos.update', $p), $this->body($p->fresh(), ['barras' => '98765']))->assertUnprocessable()->assertJsonValidationErrors('barras');
        $this->putJson(route('produtos.update', $p), $this->body($p->fresh(), ['unidade' => 'CX']))->assertUnprocessable()->assertJsonValidationErrors('unidade');
    }

    public function test_product_can_be_inactivated_and_reactivated_but_not_sold_when_inactive(): void
    {
        $p = $this->product();
        $this->patchJson(route('produtos.status', $p), ['ativo' => false, 'motivo' => 'Fora de linha', 'version' => $p->version()])->assertOk();
        $this->assertFalse($p->fresh()->ativo);
        $this->postJson(route('caixa.vendas.store'), ['operation_id' => (string) Str::uuid(), 'payment_method' => 'Pix', 'approved' => true, 'items' => [['product_id' => $p->id, 'quantity' => 1]]])->assertUnprocessable();
        $this->patchJson(route('produtos.status', $p), ['ativo' => true, 'motivo' => 'Voltou ao catálogo', 'version' => $p->fresh()->version()])->assertOk();
        $this->assertTrue($p->fresh()->ativo);
    }

    public function test_deletion_restricts_history_but_deletes_unused_product_and_retains_audit(): void
    {
        $p = $this->product();
        $this->deleteJson(route('produtos.destroy', $p), ['motivo' => 'Remover do catálogo', 'version' => $p->version()])->assertUnprocessable();
        $this->post(route('produtos.store'), ['codigo' => 'NOVO', 'nome' => 'Produto sem histórico', 'categoria' => 'Limpeza', 'unidade' => 'UN', 'preco' => 10])->assertSessionHasNoErrors();
        $new = Product::where('codigo', 'NOVO')->firstOrFail();
        $this->deleteJson(route('produtos.destroy', $new), ['motivo' => 'Cadastro incorreto', 'version' => $new->version()])->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $new->id]);
        $this->assertDatabaseHas('product_audits', ['product_name' => 'Produto sem histórico', 'product_id' => null, 'action' => 'exclusao']);
    }

    public function test_adjustment_and_zero_balance_are_real_and_idempotent(): void
    {
        $p = $this->product();
        $data = ['operation_id' => (string) Str::uuid(), 'version' => $p->version(), 'deposito' => 8, 'gondola' => 3, 'motivo' => 'Contagem física'];
        for ($i = 0; $i < 2; $i++) {
            $this->putJson(route('estoque.balances', $p), $data)->assertOk();
        }
        $this->assertEquals(8, $p->fresh()->deposito);
        $this->assertEquals(3, $p->fresh()->gondola);
        $this->assertSame(2, $p->movements()->where('operacao', 'contagem')->count());
        $this->putJson(route('estoque.balances', $p), array_merge($data, ['operation_id' => (string) Str::uuid()]))->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->putJson(route('estoque.balances', $p), ['operation_id' => (string) Str::uuid(), 'version' => $p->fresh()->version(), 'deposito' => 0, 'gondola' => 0, 'motivo' => 'Esvaziamento por conferência'])->assertOk();
        $this->assertEquals(0, $p->fresh()->deposito + $p->fresh()->gondola);
        $this->assertGreaterThan(0, $p->movements()->count());
    }

    public function test_transfer_preserves_total_and_prevents_insufficient_stock(): void
    {
        $p = $this->product();
        $total = $p->deposito + $p->gondola;
        $data = ['operation_id' => (string) Str::uuid(), 'version' => $p->version(), 'origem' => 'deposito', 'quantidade' => 5, 'motivo' => 'Reposição de gôndola'];
        for ($i = 0; $i < 2; $i++) {
            $this->postJson(route('estoque.transfer', $p), $data)->assertOk();
        }
        $this->assertEquals(35, $p->fresh()->deposito);
        $this->assertEquals(17, $p->fresh()->gondola);
        $this->assertSame($total, $p->fresh()->deposito + $p->fresh()->gondola);
        $this->postJson(route('estoque.transfer', $p), array_merge($data, ['operation_id' => (string) Str::uuid(), 'version' => $p->fresh()->version(), 'quantidade' => 1000]))->assertUnprocessable();
        $this->assertSame($total, $p->fresh()->deposito + $p->fresh()->gondola);
    }

    public function test_permissions_filtering_and_history(): void
    {
        $p = $this->product();
        $this->getJson(route('produtos.search', ['categoria' => 'Limpeza']))->assertOk()->assertJsonPath('total', 2);
        $this->getJson(route('produtos.search', ['saldo' => 'zero']))->assertOk()->assertJsonPath('total', 1);
        $this->getJson(route('estoque.history', $p))->assertOk()->assertJsonPath('total', 2)->assertJsonStructure(['data' => [['user' => ['name'], 'saldo_anterior', 'saldo_final']]]);
        $this->actingAs(User::where('email', 'caixa@mercadoprecobom.local')->firstOrFail());
        $this->putJson(route('produtos.update', $p), $this->body($p))->assertForbidden();
        $this->deleteJson(route('produtos.destroy', $p), [])->assertForbidden();
        $this->putJson(route('estoque.balances', $p), [])->assertForbidden();
        $this->getJson(route('estoque.history', $p))->assertForbidden();
        $this->get(route('produtos.consultar'))->assertOk()->assertSee('"canEdit":false', false);
    }

    public function test_all_existing_pages_render_without_prototype_forms(): void
    {
        foreach (['/produtos/editar', '/produtos/inativar', '/produtos/consultar', '/estoque/consultar'] as $path) {
            $this->get($path)->assertOk()->assertSee('catalogo-config')->assertDontSee('Salvar simulação');
        }
    }

    public function test_products_have_one_management_tab_and_keep_permissions_per_action(): void
    {
        $response = $this->get(route('produtos.consultar'))->assertOk()->assertSee('Gerenciar produtos');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $tabs = $xpath->query('//nav[@aria-label="Telas do módulo"]//a');
        $this->assertSame(2, $tabs->length);
        $this->assertSame('Cadastrar', trim($tabs->item(0)->textContent));
        $this->assertSame('Gerenciar produtos', trim($tabs->item(1)->textContent));
        $this->assertSame(route('produtos.consultar'), $tabs->item(1)->getAttribute('href'));
        foreach (['produtos.editar', 'produtos.inativar'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Gerenciar produtos');
        }
        $user = User::where('email', 'caixa@mercadoprecobom.local')->firstOrFail();
        $permission = Permission::where('slug', 'produtos.editar')->sole();
        $user->profile->permissions()->sync([$permission->id]);
        $this->actingAs($user)->get(route('produtos.consultar'))->assertOk()->assertSee('"canEdit":true', false)->assertSee('"canStatus":false', false);
        $user->profile->permissions()->sync([]);
        $this->get(route('produtos.consultar'))->assertForbidden();
    }
}
