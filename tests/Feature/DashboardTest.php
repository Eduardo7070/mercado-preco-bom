<?php

namespace Tests\Feature;

use App\Actions\Dashboard\BuildDashboard;
use App\Actions\Sales\Checkout;
use App\Actions\Sales\RefundSale;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-13 12:00:00 UTC'));
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $profile): User
    {
        return User::where('email', $profile.'@mercadoprecobom.local')->firstOrFail();
    }

    private function populateSales(): void
    {
        foreach (['caixa', 'gerente', 'admin'] as $slug) {
            $this->openCash($this->user($slug));
        }
        $product = Product::where('codigo', 'DEMO-1001')->firstOrFail();
        foreach ([
            ['caixa', 1, 'Cliente do caixa', 'paid'],
            ['gerente', 2, 'Cliente do gerente', 'paid'],
            ['caixa', 1, 'Cliente estornado', 'refunded'],
            ['caixa', 1, 'Cliente de ontem', 'old'],
        ] as [$profile,$quantity,$name,$status]) {
            $sale = app(Checkout::class)->execute([
                'operation_id' => (string) Str::uuid(), 'customer_name' => $name, 'payment_method' => 'Pix',
                'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
            ], $this->user($profile));
            if ($status === 'refunded') {
                app(RefundSale::class)->execute($sale, 'Devolução integral', $this->user('admin'));
            }
            if ($status === 'old') {
                $sale->forceFill(['created_at' => '2026-11-13 02:00:00'])->save();
            }
        }
    }

    public function test_admin_and_manager_see_global_totals_from_database(): void
    {
        $this->populateSales();
        foreach (['admin', 'gerente'] as $profile) {
            $data = app(BuildDashboard::class)->execute($this->user($profile));
            $this->assertSame('R$ 77,70', $data['metrics'][0]['value']);
            $this->assertSame('R$ 38,85', $data['metrics'][1]['value']);
            $this->assertSame('1', $data['metrics'][2]['value']);
            $this->assertCount(4, $data['sales']);
            $this->assertSame(7770, (int) $data['payments']->sum('amount_cents'));
            $this->actingAs($this->user($profile))->get('/')->assertOk()->assertSee('Cliente do gerente')->assertSee('R$ 77,70');
        }
    }

    public function test_cashier_only_sees_own_financial_data_and_authorized_modules(): void
    {
        $this->populateSales();
        $data = app(BuildDashboard::class)->execute($this->user('caixa'));
        $this->assertSame('R$ 25,90', $data['metrics'][0]['value']);
        $this->assertSame('R$ 25,90', $data['metrics'][1]['value']);
        $this->assertCount(3, $data['sales']);
        $this->assertNull($data['lowStock']);
        $this->assertSame(2590, (int) $data['payments']->sum('amount_cents'));
        $this->actingAs($this->user('caixa'))->get('/')->assertOk()
            ->assertSee('Cliente do caixa')->assertDontSee('Cliente do gerente')
            ->assertDontSee('Reposição de gôndola')->assertDontSee('Usuários cadastrados')
            ->assertDontSee('Gerenciar perfis e permissões')->assertSee('Minhas vendas pagas hoje');
    }

    public function test_stock_and_user_counts_are_real_and_permission_scoped(): void
    {
        $data = app(BuildDashboard::class)->execute($this->user('admin'));
        $metrics = collect($data['metrics'])->keyBy('label');
        $this->assertSame('12', $metrics['Produtos cadastrados']['value']);
        $this->assertSame('11', $metrics['Produtos com estoque']['value']);
        $this->assertSame('1', $metrics['Produtos sem estoque']['value']);
        $this->assertSame((string) User::count(), $metrics['Usuários cadastrados']['value']);
        $this->assertContains('Café tradicional 500 g', $data['lowStock']->pluck('nome'));
        $this->actingAs($this->user('gerente'))->get('/')->assertOk()->assertDontSee('Usuários cadastrados');
        $stock = app(BuildDashboard::class)->execute($this->user('estoquista'));
        $this->assertNull($stock['sales']);
        $this->assertNull($stock['payments']);
        $this->actingAs($this->user('estoquista'))->get('/')->assertOk()->assertSee('Reposição de gôndola')->assertDontSee('Últimas vendas');
    }

    public function test_empty_sales_show_zero_values_and_empty_states(): void
    {
        $data = app(BuildDashboard::class)->execute($this->user('caixa'));
        $this->assertSame('R$ 0,00', $data['metrics'][0]['value']);
        $this->assertSame('R$ 0,00', $data['metrics'][1]['value']);
        $this->actingAs($this->user('caixa'))->get('/')->assertOk()->assertSee('Nenhuma venda registrada')->assertSee('Nenhum pagamento registrado hoje.');
    }

    public function test_removing_permissions_hides_data_and_links(): void
    {
        $user = $this->user('caixa');
        $user->profile->permissions()->sync([]);
        $data = app(BuildDashboard::class)->execute($user);
        $this->assertSame([], $data['metrics']);
        $this->assertSame([], $data['modules']);
        $this->assertNull($data['sales']);
        $this->actingAs($user)->get('/')->assertForbidden();
    }
}
