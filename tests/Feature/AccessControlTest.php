<?php

namespace Tests\Feature;

use App\Actions\Sales\Checkout;
use App\Models\Product;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\StockSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_all_profiles_enforce_the_access_matrix(): void
    {
        $matrix = ['admin' => [200, 200, 200], 'gerente' => [200, 200, 403], 'caixa' => [200, 403, 403], 'estoquista' => [403, 200, 403]];
        foreach ($matrix as $slug => $statuses) {
            $user = User::where('email', $slug.'@mercadoprecobom.local')->firstOrFail();
            $this->actingAs($user)->get('/')->assertOk();
            foreach (array_combine(['/caixa/finalizar', '/estoque/consultar', '/acessos'], $statuses) as $path => $status) {
                $this->actingAs($user)->get($path)->assertStatus($status);
            }
        }
    }

    public function test_public_registration_cannot_create_an_operator(): void
    {
        $this->get('/cadastro')->assertRedirect('/login');
        $this->post('/cadastro', ['name' => 'Novo', 'email' => 'novo@example.test', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertRedirect('/login');
        $this->assertDatabaseMissing('users', ['email' => 'novo@example.test']);
    }

    public function test_admin_can_update_permissions_and_assign_profiles(): void
    {
        $admin = User::where('email', 'admin@mercadoprecobom.local')->firstOrFail();
        $caixa = Profile::where('slug', 'caixa')->firstOrFail();
        $this->actingAs($admin)->put(route('acessos.profiles.update', $caixa), ['permissions' => []])->assertRedirect();
        $this->assertSame(0, $caixa->permissions()->count());
        $user = User::where('email', 'caixa@mercadoprecobom.local')->firstOrFail();
        $gerente = Profile::where('slug', 'gerente')->firstOrFail();
        $this->put(route('acessos.users.update', $user), ['profile_id' => $gerente->id])->assertRedirect();
        $this->assertSame($gerente->id, $user->fresh()->profile_id);
    }

    public function test_non_admin_cannot_change_permissions(): void
    {
        $user = User::where('email', 'gerente@mercadoprecobom.local')->firstOrFail();
        $this->actingAs($user)->put(route('acessos.profiles.update', $user->profile), ['permissions' => []])->assertForbidden();
    }

    public function test_seeding_again_preserves_credentials_and_custom_permissions(): void
    {
        $user = User::where('email', 'caixa@mercadoprecobom.local')->firstOrFail();
        $hash = $user->password;
        $user->profile->permissions()->sync([]);
        $this->seed(AccessControlSeeder::class);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame(0, $user->profile->permissions()->count());
    }

    public function test_permissions_page_has_profile_selector_modal_and_active_navigation(): void
    {
        $admin = User::where('email', 'admin@mercadoprecobom.local')->firstOrFail();
        $response = $this->actingAs($admin)->get('/acessos')->assertOk()
            ->assertSee('id="perfil-selecionado"', false)
            ->assertSee('id="modal-permissoes"', false)
            ->assertSee('id="buscar-permissao"', false)
            ->assertSee('data-permission-group', false);
        $this->assertActiveNav($response->getContent(), route('acessos.index'));
    }

    public function test_permissions_modal_saves_using_json_and_admin_remains_protected(): void
    {
        $admin = User::where('email', 'admin@mercadoprecobom.local')->firstOrFail();
        $profile = Profile::where('slug', 'caixa')->firstOrFail();
        $ids = $profile->permissions()->pluck('permissions.id')->take(2)->all();
        $this->actingAs($admin)->putJson(route('acessos.profiles.update', $profile), ['permissions' => $ids])
            ->assertOk()->assertJsonPath('message', 'Permissões do perfil atualizadas com sucesso.');
        $this->assertEqualsCanonicalizing($ids, $profile->permissions()->pluck('permissions.id')->all());
        $adminProfile = Profile::where('slug', 'admin')->firstOrFail();
        $count = $adminProfile->permissions()->count();
        $this->putJson(route('acessos.profiles.update', $adminProfile), ['permissions' => []])->assertUnprocessable();
        $this->assertSame($count, $adminProfile->permissions()->count());
    }

    public function test_sales_and_receipt_highlight_sales_instead_of_pdv(): void
    {
        $admin = User::where('email', 'admin@mercadoprecobom.local')->firstOrFail();
        $this->actingAs($admin);
        $this->assertActiveNav($this->get('/caixa/vendas')->assertOk()->getContent(), route('caixa.vendas'));
        $this->assertActiveNav($this->get('/caixa/finalizar')->assertOk()->getContent(), route('caixa.finalizar'));
        $this->seed(ProductSeeder::class);
        $this->seed(StockSeeder::class);
        $p = Product::where('codigo', 'DEMO-1001')->firstOrFail();
        $this->openCash($admin);
        $sale = app(Checkout::class)->execute([
            'operation_id' => (string) Str::uuid(),
            'payment_method' => 'Pix', 'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ], $admin);
        $this->assertActiveNav($this->get(route('caixa.vendas.show', $sale))->assertOk()->getContent(), route('caixa.vendas'));
    }

    private function assertActiveNav(string $html, string $url): void
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//nav[@aria-label="Menu principal"]//a[@aria-current="page"]');
        $this->assertSame(1, $links->length, 'A navbar deve ter exatamente um item ativo.');
        $this->assertSame($url, $links->item(0)->getAttribute('href'));
    }
}
