<?php

namespace Tests\Feature;

use App\Actions\Cash\ManageSession;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        foreach (['caixa', 'gerente'] as $slug) {
            $this->openCash(User::where('email', $slug.'@mercadoprecobom.local')->firstOrFail());
        }
        $this->actingAs(User::where('email', 'caixa@mercadoprecobom.local')->firstOrFail());
    }

    private function payload(array $overrides = []): array
    {
        $p = Product::where('codigo', 'DEMO-1001')->firstOrFail();

        return array_replace(['cash_session_id' => app(ManageSession::class)->current(User::where('email', 'caixa@mercadoprecobom.local')->firstOrFail())->id, 'operation_id' => (string) Str::uuid(), 'customer_name' => 'João Silva', 'payment_method' => 'Dinheiro', 'received' => '100.00', 'items' => [['product_id' => $p->id, 'quantity' => 2]]], $overrides);
    }

    public function test_checkout_saves_payment_items_customer_and_stock_only_once(): void
    {
        $p = Product::where('codigo', 'DEMO-1001')->firstOrFail();
        $data = $this->payload();
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('caixa.vendas.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        }
        $sale = Sale::sole();
        $this->assertSame(5180, $sale->total_cents);
        $this->assertSame(4820, $sale->change_cents);
        $this->assertSame('João Silva', $sale->customer_name);
        $this->assertTrue($sale->simulated);
        $this->assertEquals(10, $p->fresh()->gondola);
        $this->assertSame(1, $sale->items()->count());
        $this->assertDatabaseHas('stock_movements', ['documento' => 'Venda #'.$sale->id, 'operacao' => 'venda', 'quantidade' => 2]);
        $this->get(route('caixa.vendas.show', $sale))->assertOk()->assertSee('João Silva')->assertSee('Arroz branco 5 kg');
    }

    public function test_server_uses_catalog_prices_and_preserves_receipt_after_product_change(): void
    {
        $data = $this->payload();
        $data['items'][0]['unit_price_cents'] = 1;
        $this->post(route('caixa.vendas.store'), $data)->assertSessionHasNoErrors();
        $sale = Sale::sole();
        $p = Product::findOrFail($data['items'][0]['product_id']);
        $p->update(['nome' => 'Novo nome', 'preco' => 1]);
        $this->assertSame(5180, $sale->total_cents);
        $this->assertSame('Arroz branco 5 kg', $sale->items()->sole()->name);
    }

    public function test_rejected_payment_and_insufficient_stock_do_not_create_sales(): void
    {
        $this->post(route('caixa.vendas.store'), $this->payload(['received' => '1']))->assertSessionHasErrors('received');
        $this->post(route('caixa.vendas.store'), $this->payload(['payment_method' => 'Pix']))->assertSessionHasErrors('approved');
        $data = $this->payload();
        $data['items'][0]['quantity'] = 100;
        $this->post(route('caixa.vendas.store'), $data)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(12, Product::where('codigo', 'DEMO-1001')->firstOrFail()->gondola);
    }

    public function test_electronic_payment_requires_approval_and_has_no_change(): void
    {
        foreach (['Pix', 'Débito', 'Crédito'] as $method) {
            $this->post(route('caixa.vendas.store'), $this->payload(['payment_method' => $method, 'approved' => '1', 'received' => null]))->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('sales', 3);
        $this->assertSame(0, (int) Sale::sum('change_cents'));
    }

    public function test_refund_restores_stock_once_and_requires_manager_permission(): void
    {
        $this->post(route('caixa.vendas.store'), $this->payload())->assertSessionHasNoErrors();
        $sale = Sale::sole();
        $this->post(route('caixa.vendas.refund', $sale), ['reason' => 'Cliente devolveu compra'])->assertForbidden();
        foreach (['caixa', 'gerente'] as $slug) {
            $this->openCash(User::where('email', $slug.'@mercadoprecobom.local')->firstOrFail());
        }
        $this->actingAs(User::where('email', 'gerente@mercadoprecobom.local')->firstOrFail());
        $this->post(route('caixa.vendas.refund', $sale), ['reason' => ''])->assertSessionHasErrors('reason');
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('caixa.vendas.refund', $sale), ['reason' => 'Cliente devolveu compra'])->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->assertSame('refunded', $sale->fresh()->status);
        $this->assertEquals(12, Product::where('codigo', 'DEMO-1001')->firstOrFail()->gondola);
        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(1, StockMovement::where('operacao', 'estorno')->count());
        $this->get(route('caixa.vendas.show', $sale))->assertOk()->assertSee('Cliente devolveu compra');
    }

    public function test_cashiers_only_see_own_sales_and_manager_can_filter_all_sales(): void
    {
        $this->post(route('caixa.vendas.store'), $this->payload())->assertSessionHasNoErrors();
        $sale = Sale::sole();
        $other = User::factory()->create(['profile_id' => Profile::where('slug', 'caixa')->value('id')]);
        $other->profile_id = Profile::where('slug', 'caixa')->value('id');
        $other->save();
        $this->actingAs($other)->get(route('caixa.vendas.show', $sale))->assertForbidden();
        $this->get(route('caixa.vendas'))->assertOk()->assertDontSee('João Silva');
        foreach (['caixa', 'gerente'] as $slug) {
            $this->openCash(User::where('email', $slug.'@mercadoprecobom.local')->firstOrFail());
        }
        $this->actingAs(User::where('email', 'gerente@mercadoprecobom.local')->firstOrFail());
        $this->get(route('caixa.vendas', ['q' => 'João']))->assertOk()->assertSee('João Silva');
        $this->get(route('caixa.vendas', ['q' => 'inexistente']))->assertOk()->assertDontSee('João Silva');
    }

    public function test_empty_cart_duplicate_items_and_partial_failure_do_not_change_stock(): void
    {
        $this->post(route('caixa.vendas.store'), $this->payload(['items' => []]))->assertSessionHasErrors('items');
        $data = $this->payload();
        $data['items'][] = $data['items'][0];
        $this->post(route('caixa.vendas.store'), $data)->assertSessionHasErrors('items.0.product_id');
        $data = $this->payload();
        $second = Product::where('codigo', 'DEMO-1002')->firstOrFail();
        $data['items'][] = ['product_id' => $second->id, 'quantity' => 10000];
        $this->post(route('caixa.vendas.store'), $data)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(12, Product::where('codigo', 'DEMO-1001')->firstOrFail()->gondola);
        $this->assertSame(0, StockMovement::where('operacao', 'venda')->count());
    }

    public function test_sale_date_filter_and_receipt_use_sao_paulo_time(): void
    {
        $this->post(route('caixa.vendas.store'), $this->payload())->assertSessionHasNoErrors();
        $sale = Sale::sole();
        $sale->forceFill(['created_at' => '2026-11-13 02:30:00'])->save();
        $this->get(route('caixa.vendas', ['from' => '2026-11-12', 'to' => '2026-11-12']))->assertOk()->assertSee('João Silva');
        $this->get(route('caixa.vendas', ['from' => '2026-11-13', 'to' => '2026-11-13']))->assertOk()->assertDontSee('João Silva');
        $this->get(route('caixa.vendas.show', $sale))->assertOk()->assertSee('12/11/2026 23:30:00');
    }

    public function test_stock_user_cannot_access_cashier_and_new_pages_render(): void
    {
        $this->get('/caixa/finalizar')->assertOk()->assertSee('Produtos da venda');
        $this->get('/caixa/vendas')->assertOk();
        foreach (['caixa', 'gerente'] as $slug) {
            $this->openCash(User::where('email', $slug.'@mercadoprecobom.local')->firstOrFail());
        }
        $this->actingAs(User::where('email', 'estoquista@mercadoprecobom.local')->firstOrFail());
        $this->get('/caixa/finalizar')->assertForbidden();
        $this->get('/caixa/vendas')->assertForbidden();
        $this->post(route('caixa.vendas.store'), $this->payload())->assertForbidden();
    }

    public function test_sale_cancellation_and_payment_are_separate_screens_with_one_active_tab(): void
    {
        foreach (['caixa.finalizar' => 'adicionar', 'caixa.cancelar' => 'cupom', 'caixa.pagamento' => 'recebimento'] as $route => $form) {
            $response = $this->get(route($route))->assertOk()->assertSee('id="'.$form.'"', false);
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $active = $xpath->query('//nav[@aria-label="Telas do módulo"]//a[@aria-current="page"]');
            $this->assertSame(1, $active->length);
            $this->assertSame(route($route), $active->item(0)->getAttribute('href'));
        }
        $this->get(route('caixa.finalizar'))->assertDontSee('id="recebimento"', false);
        $this->get(route('caixa.pagamento'))->assertDontSee('id="adicionar"', false);
    }

    public function test_refund_screen_finds_a_sale_and_is_separate_from_receipt_and_history(): void
    {
        $this->post(route('caixa.vendas.store'), $this->payload())->assertSessionHasNoErrors();
        $sale = Sale::sole();
        $this->get(route('caixa.estornar'))->assertForbidden();
        $this->actingAs(User::where('email', 'gerente@mercadoprecobom.local')->firstOrFail());
        $this->get(route('caixa.estornar', ['q' => 'João']))->assertOk()->assertSee('Selecionar venda')->assertSee('João Silva');
        $this->get(route('caixa.estornar', ['sale' => $sale->id]))->assertOk()->assertSee('id="form-estorno"', false)->assertSee('Toda a venda restante');
        $this->get(route('caixa.vendas.show', $sale))->assertOk()->assertSee('Estornar esta venda')->assertDontSee('id="form-estorno"', false);
        $this->post(route('caixa.returns', $sale), [
            'operation_id' => (string) Str::uuid(), 'reason' => 'Cliente devolveu os produtos',
            'items' => [['sale_item_id' => $sale->items()->sole()->id, 'quantity' => 2, 'disposition' => 'restock']],
        ])->assertSessionHasNoErrors()->assertRedirect(route('caixa.vendas.show', $sale));
        $this->get(route('caixa.estornar', ['sale' => $sale->id]))->assertOk()->assertSee('Esta venda já foi estornada.')->assertDontSee('id="form-estorno"', false);
    }
}
