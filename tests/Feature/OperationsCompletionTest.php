<?php

namespace Tests\Feature;

use App\Actions\Cash\ManageSession;
use App\Actions\Reports\SalesReport;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleDraft;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OperationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationsCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->operator('admin'));
    }

    private function operator(string $slug): User
    {
        return User::where('email', $slug.'@mercadoprecobom.local')->firstOrFail();
    }

    private function product(): Product
    {
        return Product::where('codigo', 'DEMO-1001')->firstOrFail();
    }

    private function checkout(array $overrides = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'cash_session_id' => $this->openCash(auth()->user())->id, 'payment_method' => 'Dinheiro', 'received' => '100.00', 'items' => [['product_id' => $this->product()->id, 'quantity' => 2]]], $overrides);
    }

    public function test_new_pages_render_and_respect_roles(): void
    {
        foreach (['/usuarios', '/usuarios/criar', '/minha-conta/senha', '/caixa/sessoes', '/estoque/lotes', '/compras', '/relatorios', '/cadastros/clientes', '/cadastros/fornecedores', '/cadastros/categorias', '/cadastros/terminais'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->actingAs($this->operator('caixa'));
        $this->get('/cadastros/clientes')->assertOk();
        foreach (['/usuarios', '/compras', '/relatorios', '/cadastros/fornecedores', '/estoque/lotes'] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_cash_requires_open_session_and_register_is_exclusive(): void
    {
        $data = $this->checkout();
        $session = app(ManageSession::class)->current(auth()->user());
        $this->actingAs($this->operator('gerente'))->postJson(route('caixa.sessoes.store'), ['action' => 'open', 'register_id' => $session->cash_register_id, 'amount' => 0])->assertUnprocessable();
        $this->postJson(route('caixa.vendas.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('cash_session_id');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_cash_movements_replay_and_close_difference(): void
    {
        $session = $this->openCash(auth()->user());
        $body = ['session_id' => $session->id, 'action' => 'supply', 'amount' => '25.00', 'reason' => 'Reforço para troco', 'operation_id' => (string) Str::uuid()];
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('caixa.sessoes.store'), $body)->assertSessionHasNoErrors();
        }
        $this->assertSame(12500, app(ManageSession::class)->expected($session));
        $this->postJson(route('caixa.sessoes.store'), array_replace($body, ['amount' => 30]))->assertUnprocessable();
        $this->postJson(route('caixa.sessoes.store'), ['session_id' => $session->id, 'action' => 'close', 'amount' => '120.00'])->assertUnprocessable();
        $this->post(route('caixa.sessoes.store'), ['session_id' => $session->id, 'action' => 'close', 'amount' => '120.00', 'reason' => 'Diferença identificada na conferência'])->assertSessionHasNoErrors();
        $this->assertSame(-500, $session->fresh()->difference_cents);
        $this->assertNull(CashRegister::find($session->cash_register_id)->active_session_id);
        $this->assertDatabaseCount('cash_entries', 1);
    }

    public function test_checkout_replay_rejects_a_different_body(): void
    {
        $data = $this->checkout();
        $this->postJson(route('caixa.vendas.store'), $data)->assertRedirect();
        $data['items'][0]['quantity'] = 3;
        $this->postJson(route('caixa.vendas.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('operation_id');
        $this->assertDatabaseCount('sales', 1);
        $this->assertEquals(10, $this->product()->gondola);
    }

    public function test_fractional_sale_rounds_cents_and_refunds_exactly_without_restocking_damaged_goods(): void
    {
        $p = $this->product();
        $p->update(['unidade' => 'KG', 'preco' => 10, 'custo' => 4]);
        $data = $this->checkout(['items' => [['product_id' => $p->id, 'quantity' => '0.375']]]);
        $this->post(route('caixa.vendas.store'), $data)->assertSessionHasNoErrors();
        $sale = Sale::sole();
        $item = $sale->items()->sole();
        $this->assertSame(375, $sale->total_cents);
        $this->assertEquals(11.625, $p->fresh()->gondola);
        $return = ['operation_id' => (string) Str::uuid(), 'reason' => 'Parte do produto com avaria', 'items' => [['sale_item_id' => $item->id, 'quantity' => '0.125', 'disposition' => 'discard']]];
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('caixa.returns', $sale), $return)->assertSessionHasNoErrors();
        }
        $this->assertSame('partially_refunded', $sale->fresh()->status);
        $this->assertSame(125, $sale->fresh()->returned_cents);
        $this->assertEquals(11.625, $p->fresh()->gondola);
        $return['operation_id'] = (string) Str::uuid();
        $return['items'][0]['quantity'] = '0.250';
        $return['items'][0]['disposition'] = 'restock';
        $this->post(route('caixa.returns', $sale), $return)->assertSessionHasNoErrors();
        $this->assertSame(375, $sale->fresh()->returned_cents);
        $this->assertSame('refunded', $sale->fresh()->status);
        $this->assertEquals(11.875, $p->fresh()->gondola);
        $this->assertDatabaseCount('sale_returns', 2);
        $this->assertSame(10000, app(ManageSession::class)->expected(app(ManageSession::class)->current(auth()->user())));
    }

    public function test_whole_units_reject_fractional_sale_and_stock_movement(): void
    {
        $data = $this->checkout(['items' => [['product_id' => $this->product()->id, 'quantity' => '0.5']]]);
        $this->postJson(route('caixa.vendas.store'), $data)->assertUnprocessable();
        $this->postJson(route('estoque.store', 'entrada'), ['operation_id' => (string) Str::uuid(), 'product_id' => $this->product()->id, 'local' => 'deposito', 'quantidade' => '0.5', 'motivo' => 'Entrada fracionada'])->assertUnprocessable();
        $this->assertEquals(40, $this->product()->deposito);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_fefo_excludes_expired_and_quarantined_lots(): void
    {
        $p = $this->product();
        $p->update(['gondola' => 6, 'validade' => true]);
        StockBatch::where('product_id', $p->id)->where('local', 'gondola')->delete();
        $rows = [['VENCIDO', now()->subDay()->toDateString(), false], ['BLOQUEADO', now()->addDay()->toDateString(), true], ['PRIMEIRO', now()->addDays(2)->toDateString(), false], ['ULTIMO', now()->addDays(10)->toDateString(), false]];
        foreach ($rows as [$lot,$date,$blocked]) {
            StockBatch::factory()->create(['product_id' => $p->id, 'local' => 'gondola', 'quantity' => 1.5, 'lot' => $lot, 'expires_on' => $date, 'quarantine' => $blocked]);
        }
        $data = $this->checkout(['items' => [['product_id' => $p->id, 'quantity' => 1]]]);
        $this->post(route('caixa.vendas.store'), $data)->assertSessionHasNoErrors();
        $this->assertEquals(.5, StockBatch::where('lot', 'PRIMEIRO')->value('quantity'));
        $this->assertEquals(1.5, StockBatch::where('lot', 'VENCIDO')->value('quantity'));
        $data['operation_id'] = (string) Str::uuid();
        $data['items'][0]['quantity'] = 3;
        $this->postJson(route('caixa.vendas.store'), $data)->assertUnprocessable();
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_transfer_rejects_pending_batch_and_rolls_back(): void
    {
        $p = $this->product();
        StockBatch::where('product_id', $p->id)->where('local', 'deposito')->update(['quarantine' => true]);
        $this->postJson(route('estoque.transfer', $p), ['operation_id' => (string) Str::uuid(), 'version' => $p->version(), 'origem' => 'deposito', 'quantidade' => 2, 'motivo' => 'Reposição de gôndola'])->assertUnprocessable();
        $this->assertEquals(40, $p->fresh()->deposito);
        $this->assertEquals(40, StockBatch::where('product_id', $p->id)->where('local', 'deposito')->sum('quantity'));
    }

    public function test_cross_column_identifiers_cannot_be_ambiguous(): void
    {
        $p = $this->product();
        $p->update(['barras' => '123456789']);
        $body = ['codigo' => '123456789', 'nome' => 'Outro produto', 'categoria' => 'Mercearia', 'unidade' => 'UN', 'preco' => 12];
        $this->postJson(route('produtos.store'), $body)->assertUnprocessable()->assertJsonValidationErrors('codigo');
        $body['codigo'] = '22222';
        $body['barras'] = '123456789';
        $this->postJson(route('produtos.store'), $body)->assertUnprocessable()->assertJsonValidationErrors('barras');
        $this->assertDatabaseCount('products', 12);
    }

    public function test_admin_manages_users_and_blocked_account_cannot_authenticate(): void
    {
        $body = ['name' => 'Operador novo', 'email' => 'operador@example.test', 'profile_id' => Profile::where('slug', 'caixa')->value('id'), 'active' => 1, 'password' => 'NovaSenha123!', 'password_confirmation' => 'NovaSenha123!'];
        $this->post(route('usuarios.store'), $body)->assertSessionHasNoErrors();
        $u = User::where('email', $body['email'])->sole();
        $this->assertTrue(Hash::check($body['password'], $u->password));
        $body['active'] = 0;
        $body['password'] = $body['password_confirmation'] = null;
        $this->put(route('usuarios.update', $u), $body)->assertSessionHasNoErrors();
        $this->assertFalse($u->fresh()->active);
        $this->post('/logout');
        $this->post('/login', ['email' => $u->email, 'password' => 'NovaSenha123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_self_lockout_is_rejected_and_password_change_requires_current_password(): void
    {
        $u = auth()->user();
        $body = ['name' => $u->name, 'email' => $u->email, 'profile_id' => $u->profile_id, 'active' => 0];
        $this->putJson(route('usuarios.update', $u), $body)->assertUnprocessable();
        $u->password = 'OldPassword123!';
        $u->save();
        $this->put(route('account.password.update'), ['current_password' => 'wrong', 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'])->assertSessionHasErrors('current_password');
        $this->put(route('account.password.update'), ['current_password' => 'OldPassword123!', 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword123!', $u->fresh()->password));
    }

    public function test_password_reset_uses_single_use_broker_token(): void
    {
        $u = $this->operator('caixa');
        $token = Password::createToken($u);
        $this->post('/logout');
        $body = ['token' => $token, 'email' => $u->email, 'password' => 'Recovered123!', 'password_confirmation' => 'Recovered123!'];
        $this->post(route('password.update'), $body)->assertSessionHasNoErrors()->assertRedirect('/login');
        $this->assertTrue(Hash::check('Recovered123!', $u->fresh()->password));
        $this->post(route('password.update'), $body)->assertSessionHasErrors('email');
    }

    public function test_draft_resume_requires_owner_and_checkout_completes_it(): void
    {
        $body = ['operation_id' => (string) Str::uuid(), 'label' => 'Cliente aguardando', 'items' => [['product_id' => $this->product()->id, 'quantity' => 2]]];
        for ($i = 0; $i < 2; $i++) {
            $this->postJson(route('caixa.drafts.store'), $body)->assertOk();
        }
        $draft = SaleDraft::sole();
        $this->assertEquals(12, $this->product()->gondola);
        $this->get(route('caixa.finalizar', ['draft' => $draft->id]))->assertOk();
        $this->post(route('caixa.vendas.store'), $this->checkout(['draft_id' => $draft->id]))->assertSessionHasNoErrors();
        $this->assertSame('completed', $draft->fresh()->status);
        $this->actingAs($this->operator('caixa'))->get(route('caixa.finalizar', ['draft' => $draft->id]))->assertNotFound();
    }

    public function test_purchase_is_received_once_with_cost_and_supplier_history(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Fornecedor teste', 'active' => true]);
        $p = $this->product();
        $p->update(['custo' => 10]);
        $body = ['operation_id' => (string) Str::uuid(), 'supplier_id' => $supplier->id, 'document' => 'NF-123', 'items' => [['product_id' => $p->id, 'quantity' => 4, 'unit_cost' => 20, 'lot' => 'NOVO', 'expires_on' => now()->addMonth()->toDateString()]]];
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('compras.store'), $body)->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('purchases', 1);
        $this->assertEquals(44, $p->fresh()->deposito);
        $this->assertSame(8000, Purchase::sole()->total_cents);
        $this->assertEquals(10.71, $p->fresh()->custo);
        $body['operation_id'] = (string) Str::uuid();
        $this->postJson(route('compras.store'), $body)->assertUnprocessable()->assertJsonValidationErrors('document');
        $this->delete(route('cadastros.destroy', ['fornecedores', $supplier->id]))->assertSessionHasErrors('record');
    }

    public function test_reports_export_net_values_and_preserve_sale_cost_snapshots(): void
    {
        $p = $this->product();
        $p->update(['custo' => 10]);
        $this->post(route('caixa.vendas.store'), $this->checkout(['customer_name' => '=malicious']))->assertSessionHasNoErrors();
        Sale::sole()->forceFill(['created_at' => '2026-11-13 11:17:00'])->save();
        $p->update(['custo' => 20]);
        $report = app(SalesReport::class)->execute([], auth()->user());
        $this->assertSame(5180, $report['gross']);
        $this->assertSame(2000, $report['cost']);
        $this->assertSame(3180, $report['margin']);
        $csv = $this->get(route('relatorios.export'))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->streamedContent();
        $this->assertStringContainsString("'=malicious", $csv);
        $this->assertStringContainsString('51,80', $csv);
        $this->assertStringContainsString('13/11/2026 às 08:17:00', $csv);
        $this->get(route('relatorios.index'))->assertOk()->assertSee('download>Exportar CSV', false);
    }

    public function test_seeders_preserve_custom_operational_permissions(): void
    {
        $p = $this->operator('gerente')->profile;
        $p->permissions()->sync([]);
        $this->seed(OperationsSeeder::class);
        $this->assertSame(0, $p->permissions()->count());
    }

    public function test_malformed_array_requests_return_validation_errors(): void
    {
        $this->postJson(route('compras.store'), ['items' => 'invalid'])->assertUnprocessable();
        $this->post(route('caixa.vendas.store'), $this->checkout())->assertSessionHasNoErrors();
        $this->postJson(route('caixa.returns', Sale::sole()), ['items' => 'invalid'])->assertUnprocessable();
    }

    public function test_manager_closes_blocked_operators_session_with_audit(): void
    {
        $operator = $this->operator('caixa');
        $session = $this->openCash($operator);
        $operator->active = false;
        $operator->save();
        $this->post(route('caixa.sessoes.store'), ['action' => 'manager_close', 'session_id' => $session->id, 'amount' => 100, 'reason' => 'Operador indisponível na conferência'])->assertSessionHasNoErrors();
        $this->assertSame('closed', $session->fresh()->status);
        $this->assertSame(auth()->id(), $session->fresh()->closed_by);
        $this->assertNull(CashRegister::find($session->cash_register_id)->active_session_id);
        $this->actingAs($this->operator('caixa'))->postJson(route('caixa.sessoes.store'), ['action' => 'manager_close', 'session_id' => $session->id, 'amount' => 100, 'reason' => 'Fechamento indevido'])->assertForbidden();
    }

    public function test_resuspending_updates_the_same_draft_and_rejects_changes_on_replay(): void
    {
        $data = ['operation_id' => (string) Str::uuid(), 'label' => 'Atendimento 001', 'items' => [['product_id' => $this->product()->id, 'quantity' => 1]]];
        $this->postJson(route('caixa.drafts.store'), $data)->assertOk();
        $draft = SaleDraft::sole();
        $data['draft_id'] = $draft->id;
        $data['operation_id'] = (string) Str::uuid();
        $data['items'][0]['quantity'] = 2;
        $this->postJson(route('caixa.drafts.store'), $data)->assertOk();
        $this->assertDatabaseCount('sale_drafts', 1);
        $this->assertSame(2, $draft->fresh()->items[0]['quantity']);
        $data['items'][0]['quantity'] = 3;
        $this->postJson(route('caixa.drafts.store'), $data)->assertUnprocessable();
    }

    public function test_numeric_looking_names_remain_distinct_in_replay_checks(): void
    {
        $data = $this->checkout(['customer_name' => '001']);
        $this->post(route('caixa.vendas.store'), $data)->assertSessionHasNoErrors();
        $data['customer_name'] = '1';
        $this->postJson(route('caixa.vendas.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('operation_id');
    }

    public function test_damaged_return_keeps_cost_and_insufficient_cash_cannot_be_refunded(): void
    {
        $product = $this->product();
        $product->update(['custo' => 10]);
        $this->post(route('caixa.vendas.store'), $this->checkout())->assertSessionHasNoErrors();
        $sale = Sale::sole();
        $body = ['operation_id' => (string) Str::uuid(), 'reason' => 'Produto devolvido com avaria', 'items' => [['sale_item_id' => $sale->items()->sole()->id, 'quantity' => 1, 'disposition' => 'discard']]];
        $this->post(route('caixa.returns', $sale), $body)->assertSessionHasNoErrors();
        $report = app(SalesReport::class)->execute([], auth()->user());
        $this->assertSame(2000, $report['cost']);
        $this->assertSame(2590, $report['net']);
        $session = app(ManageSession::class)->current(auth()->user());
        $available = app(ManageSession::class)->expected($session) / 100;
        $this->post(route('caixa.sessoes.store'), ['session_id' => $session->id, 'action' => 'withdraw', 'amount' => $available, 'operation_id' => (string) Str::uuid(), 'reason' => 'Retirada para cofre'])->assertSessionHasNoErrors();
        $body['operation_id'] = (string) Str::uuid();
        $this->postJson(route('caixa.returns', $sale), $body)->assertUnprocessable();
        $this->assertSame(2590, $sale->fresh()->returned_cents);
        $this->assertDatabaseCount('sale_returns', 1);
    }

    public function test_stale_cash_forms_cannot_move_or_close_a_new_session(): void
    {
        $user = auth()->user();
        $first = $this->openCash($user);
        $this->post(route('caixa.sessoes.store'), ['action' => 'close', 'session_id' => $first->id, 'amount' => 100])->assertSessionHasNoErrors();
        $second = $this->openCash($user);
        $this->postJson(route('caixa.sessoes.store'), ['action' => 'close', 'session_id' => $first->id, 'amount' => 100])->assertUnprocessable();
        $this->postJson(route('caixa.sessoes.store'), ['action' => 'supply', 'session_id' => $first->id, 'amount' => 10, 'reason' => 'Formulário antigo', 'operation_id' => (string) Str::uuid()])->assertUnprocessable();
        $this->assertSame('open', $second->fresh()->status);
        $this->assertSame(10000, app(ManageSession::class)->expected($second));
    }

    public function test_controlled_products_without_real_expiration_cannot_be_sold(): void
    {
        $product = $this->product();
        StockBatch::where('product_id', $product->id)->where('local', 'gondola')->update(['expires_on' => null, 'quarantine' => false]);
        $this->postJson(route('caixa.vendas.store'), $this->checkout())->assertUnprocessable();
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_excel_export_preserves_date_width_values_filters_and_literal_customer_names(): void
    {
        $customerName = '=SUM(A1) & <Cliente>';
        $this->post(route('caixa.vendas.store'), $this->checkout(['customer_name' => $customerName]))->assertSessionHasNoErrors();
        Sale::sole()->forceFill(['created_at' => '2026-11-13 11:17:00'])->save();
        $response = $this->get(route('relatorios.excel', ['from' => '2026-11-13', 'to' => '2026-11-13']))
            ->assertOk()->assertDownload('vendas-'.now('America/Sao_Paulo')->format('Y-m-d').'.xlsx')
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $file = $response->baseResponse->getFile()->getPathname();
        $archive = new \ZipArchive;
        try {
            $this->assertTrue($archive->open($file));
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $this->assertNotFalse(simplexml_load_string($archive->getFromIndex($index)));
            }
            $sheet = simplexml_load_string($archive->getFromName('xl/worksheets/sheet1.xml'));
            $sheet->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $this->assertGreaterThanOrEqual(26, (float) $sheet->xpath('//s:col[@min="2"]')[0]['width']);
            $this->assertSame('3', (string) $sheet->xpath('//s:c[@r="B2"]')[0]['s']);
            $expectedDate = (new \DateTimeImmutable('2026-11-13 08:17:00', new \DateTimeZone('UTC')))->getTimestamp() / 86400 + 25569;
            $this->assertEqualsWithDelta($expectedDate, (float) $sheet->xpath('//s:c[@r="B2"]/s:v')[0], 0.000001);
            $this->assertSame($customerName, (string) $sheet->xpath('//s:c[@r="C2"]/s:is/s:t')[0]);
            $this->assertSame([], $sheet->xpath('//s:f'));
            $this->assertEqualsWithDelta(51.80, (float) $sheet->xpath('//s:c[@r="G2"]/s:v')[0], 0.001);
            $this->assertSame('A1:J2', (string) $sheet->xpath('//s:autoFilter')[0]['ref']);
        } finally {
            $archive->close();
            unlink($file);
        }
        $emptyResponse = $this->get(route('relatorios.excel', ['from' => '2026-11-14']))->assertOk();
        $emptyFile = $emptyResponse->baseResponse->getFile()->getPathname();
        try {
            $archive->open($emptyFile);
            $sheet = simplexml_load_string($archive->getFromName('xl/worksheets/sheet1.xml'));
            $sheet->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $this->assertCount(1, $sheet->xpath('//s:row'));
        } finally {
            $archive->close();
            unlink($emptyFile);
        }
        $this->actingAs($this->operator('caixa'))->get(route('relatorios.excel'))->assertForbidden();
    }

    public function test_batches_use_compact_pagination_and_keep_search_on_next_page(): void
    {
        StockBatch::query()->update(['lot' => 'LOTE-TESTE']);
        $response = $this->get(route('estoque.lotes', ['q' => 'LOTE-TESTE']))->assertOk()
            ->assertSee('Mostrando 1 a 20 de 22 registros')
            ->assertDontSee('<svg', false);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $activeTabs = $xpath->query('//nav[@aria-label="Telas do módulo"]//a[@aria-current="page"]');
        $this->assertSame(1, $activeTabs->length);
        $this->assertSame('Lotes e validade', trim($activeTabs->item(0)->textContent));
        $next = $xpath->query('//nav[@aria-label="Paginação"]//a[@rel="next"]');
        $this->assertSame(1, $next->length);
        $this->assertStringContainsString('q=LOTE-TESTE', $next->item(0)->getAttribute('href'));
        $this->get($next->item(0)->getAttribute('href'))->assertOk()->assertSee('Mostrando 21 a 22 de 22 registros');
        $this->get(route('estoque.lotes', ['q' => 'INEXISTENTE']))->assertOk()
            ->assertSee('Nenhum lote com saldo encontrado.')->assertDontSee('class="paginacao"', false);
    }
}
