<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_public_registration_cannot_choose_admin(): void
    {
        $admin = Profile::where('slug', 'admin')->firstOrFail();
        $this->post('/cadastro', ['name' => 'Novo', 'email' => 'novo@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'profile_id' => $admin->id])->assertRedirect('/');
        $this->assertSame('caixa', User::where('email', 'novo@example.test')->firstOrFail()->profile->slug);
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
}
