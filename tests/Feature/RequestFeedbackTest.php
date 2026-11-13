<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    private function payload(string $html): array
    {
        $this->assertSame(1, preg_match('~<script id="mpb-alert-data" type="application/json">(.*?)</script>~s', $html, $matches));

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_layouts_load_local_alert_assets_before_page_scripts(): void
    {
        $login = $this->get('/login')->assertOk()->assertSee('vendor/sweetalert2/sweetalert2.min.js')->assertSee('js/alertas.js');
        $this->assertSame([], $this->payload($login->getContent())['errors']);
        $user = User::factory()->create(['profile_id' => Profile::where('slug', 'caixa')->value('id')]);
        $home = $this->actingAs($user)->get('/')->assertOk();
        $html = $home->getContent();
        $this->assertLessThan(strpos($html, 'js/comum.js'), strpos($html, 'js/alertas.js'));
        $this->assertLessThan(strpos($html, 'js/alertas.js'), strpos($html, 'vendor/sweetalert2/sweetalert2.min.js'));
    }

    public function test_validation_errors_are_passed_to_alerts_after_redirect(): void
    {
        $this->from('/login')->post('/login', ['email' => 'invalido', 'password' => ''])->assertRedirect('/login')->assertSessionHasErrors(['email', 'password']);
        $html = $this->withCookie(config('session.cookie'), session()->getId())->get('/login')->assertOk()->getContent();
        $this->assertNotEmpty($this->payload($html)['errors']);
    }

    public function test_flash_content_is_serialized_without_injecting_scripts(): void
    {
        $message = 'Revise o campo <script>alert("x")</script>';
        $html = $this->withSession(['status' => $message])->get('/login')->assertOk()->getContent();
        $this->assertSame($message, $this->payload($html)['success']);
        $this->assertStringNotContainsString('<script>alert("x")</script>', $html);
    }

    public function test_login_success_and_logout_flash_messages_reach_alerts(): void
    {
        $user = User::factory()->create(['password' => 'password123', 'profile_id' => Profile::where('slug', 'caixa')->value('id')]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'])->assertRedirect('/');
        $this->assertSame('Login realizado com sucesso.', $this->payload($this->get('/')->assertOk()->getContent())['success']);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertSame('Você saiu do sistema com segurança.', $this->payload($this->get('/login')->assertOk()->getContent())['success']);
    }

    public function test_error_pages_include_alert_feedback(): void
    {
        $html = $this->get('/pagina-inexistente')->assertNotFound()->getContent();
        $this->assertArrayHasKey('warning', $this->payload($html));
        $user = User::factory()->create(['profile_id' => Profile::where('slug', 'caixa')->value('id')]);
        $html = $this->actingAs($user)->get('/acessos')->assertForbidden()->getContent();
        $this->assertStringContainsString('Seu perfil não tem permissão', $this->payload($html)['warning']);
    }
}
