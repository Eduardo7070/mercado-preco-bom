<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RecoveryController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->middleware('can:inicio')->name('inicio');
    Route::redirect('/dashboard', '/')->name('dashboard');
    Route::view('/produtos/cadastrar', 'produtos.cadastrar', ['title' => 'Cadastrar produto', 'module' => 'produtos', 'asset' => 'produtos/cadastrar'])->middleware('can:produtos.cadastrar')->name('produtos.cadastrar');
    Route::get('/produtos/editar', [ProductController::class, 'listing'])->middleware('can:produtos.editar')->name('produtos.editar');
    Route::get('/produtos/inativar', [ProductController::class, 'listing'])->middleware('can:produtos.inativar')->name('produtos.inativar');
    Route::get('/produtos/consultar', [ProductController::class, 'listing'])->name('produtos.consultar');
    Route::get('/estoque/consultar', [ProductController::class, 'listing'])->middleware('can:estoque.consultar')->name('estoque.consultar');
});

Route::middleware(['auth', 'can:acessos.gerenciar'])->group(function () {
    Route::get('/acessos', [AccessController::class, 'index'])->name('acessos.index');
    Route::put('/acessos/perfis/{profile}', [AccessController::class, 'updateProfile'])->name('acessos.profiles.update');
    Route::put('/acessos/usuarios/{user}', [AccessController::class, 'updateUser'])->name('acessos.users.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/produtos', [ProductController::class, 'store'])->middleware('can:produtos.cadastrar')->name('produtos.store');
    Route::get('/consulta/produtos', [ProductController::class, 'search'])->name('produtos.search');
    Route::post('/estoque/{operation}', [StockController::class, 'store'])->whereIn('operation', ['entrada', 'saida', 'ajustar'])->name('estoque.store');
});
Route::get('/estoque/entrada', [StockController::class, 'form'])->defaults('operation', 'entrada')->middleware(['auth', 'can:estoque.entrada'])->name('estoque.entrada');
Route::get('/estoque/saida', [StockController::class, 'form'])->defaults('operation', 'saida')->middleware('auth')->name('estoque.saida');
Route::get('/estoque/ajustar', [StockController::class, 'form'])->defaults('operation', 'ajustar')->middleware(['auth', 'can:estoque.ajustar'])->name('estoque.ajustar');

Route::middleware(['auth', 'can:caixa.finalizar'])->group(function () {
    Route::get('/caixa/finalizar', [CashierController::class, 'index'])->name('caixa.finalizar');
    Route::post('/caixa/vendas', [CashierController::class, 'store'])->middleware('can:caixa.pagamento')->name('caixa.vendas.store');
    Route::get('/caixa/vendas', [CashierController::class, 'sales'])->name('caixa.vendas');
    Route::get('/caixa/vendas/{sale}', [CashierController::class, 'show'])->name('caixa.vendas.show');
    Route::post('/caixa/vendas/{sale}/estorno', [CashierController::class, 'refund'])->middleware('can:caixa.estornar')->name('caixa.vendas.refund');
    Route::get('/caixa/cancelar', [CashierController::class, 'index'])->middleware('can:caixa.cancelar')->name('caixa.cancelar');
    Route::get('/caixa/pagamento', [CashierController::class, 'index'])->middleware('can:caixa.pagamento')->name('caixa.pagamento');
    Route::get('/caixa/estornar', [CashierController::class, 'refundPage'])->middleware('can:caixa.estornar')->name('caixa.estornar');
});

Route::middleware('auth')->group(function () {
    Route::get('/produtos/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('produtos.show');
    Route::put('/produtos/{product}', [ProductController::class, 'update'])->middleware('can:produtos.editar')->whereNumber('product')->name('produtos.update');
    Route::patch('/produtos/{product}/situacao', [ProductController::class, 'status'])->middleware('can:produtos.inativar')->whereNumber('product')->name('produtos.status');
    Route::delete('/produtos/{product}', [ProductController::class, 'destroy'])->middleware('can:produtos.excluir')->whereNumber('product')->name('produtos.destroy');
    Route::put('/estoque/{product}/saldos', [StockController::class, 'balances'])->middleware('can:estoque.ajustar')->whereNumber('product')->name('estoque.balances');
    Route::post('/estoque/{product}/transferencia', [StockController::class, 'transfer'])->middleware('can:estoque.ajustar')->whereNumber('product')->name('estoque.transfer');
    Route::get('/estoque/{product}/historico', [StockController::class, 'history'])->middleware('can:estoque.consultar')->whereNumber('product')->name('estoque.history');
});

Route::get('/cadastro', [UserManagementController::class, 'create'])->middleware(['auth', 'can:acessos.gerenciar'])->name('register');
Route::post('/cadastro', [UserManagementController::class, 'store'])->middleware(['auth', 'can:acessos.gerenciar'])->name('register.store');
Route::middleware('guest')->group(function () {
    Route::get('/esqueci-minha-senha', [RecoveryController::class, 'request'])->name('password.request');
    Route::post('/esqueci-minha-senha', [RecoveryController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/redefinir-senha/{token}', [RecoveryController::class, 'reset'])->name('password.reset');
    Route::post('/redefinir-senha', [RecoveryController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::get('/minha-conta/senha', [UserManagementController::class, 'password'])->name('account.password');
    Route::put('/minha-conta/senha', [UserManagementController::class, 'changePassword'])->name('account.password.update');
});
Route::middleware(['auth', 'can:acessos.gerenciar'])->group(function () {
    Route::get('/usuarios', [UserManagementController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/criar', [UserManagementController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UserManagementController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{user}/editar', [UserManagementController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{user}', [UserManagementController::class, 'update'])->name('usuarios.update');
});
Route::middleware(['auth', 'can:caixa.finalizar'])->group(function () {
    Route::get('/caixa/sessoes', [CashSessionController::class, 'index'])->name('caixa.sessoes');
    Route::post('/caixa/sessoes', [CashSessionController::class, 'store'])->name('caixa.sessoes.store');
    Route::post('/caixa/suspender', [CashierController::class, 'draft'])->name('caixa.drafts.store');
    Route::delete('/caixa/suspensas/{draft}', [CashierController::class, 'discardDraft'])->middleware('can:caixa.cancelar')->name('caixa.drafts.destroy');
    Route::post('/caixa/vendas/{sale}/devolucoes', [CashierController::class, 'returnItems'])->middleware('can:caixa.estornar')->name('caixa.returns');
});
Route::get('/estoque/lotes', [BatchController::class, 'index'])->middleware(['auth', 'can:estoque.consultar'])->name('estoque.lotes');
Route::put('/estoque/lotes/{batch}', [BatchController::class, 'update'])->middleware(['auth', 'can:estoque.ajustar'])->name('estoque.lotes.update');
Route::middleware('auth')->prefix('cadastros/{resource}')->whereIn('resource', ['clientes', 'fornecedores', 'categorias', 'terminais'])->group(function () {
    Route::get('/', [ReferenceController::class, 'index'])->name('cadastros.index');
    Route::post('/', [ReferenceController::class, 'store'])->name('cadastros.store');
    Route::put('/{record}', [ReferenceController::class, 'update'])->whereNumber('record')->name('cadastros.update');
    Route::delete('/{record}', [ReferenceController::class, 'destroy'])->whereNumber('record')->name('cadastros.destroy');
});
Route::middleware(['auth', 'can:compras.gerenciar'])->group(function () {
    Route::get('/compras', [PurchaseController::class, 'index'])->name('compras.index');
    Route::post('/compras', [PurchaseController::class, 'store'])->name('compras.store');
});
Route::middleware(['auth', 'can:relatorios.consultar'])->group(function () {
    Route::get('/relatorios', [ReportController::class, 'index'])->name('relatorios.index');
    Route::get('/relatorios/vendas.xlsx', [ReportController::class, 'excel'])->name('relatorios.excel');
    Route::get('/relatorios/vendas.csv', [ReportController::class, 'export'])->name('relatorios.export');
});
Route::get('/cadastros', function () {
    foreach (['clientes' => 'clientes.gerenciar', 'fornecedores' => 'fornecedores.gerenciar', 'categorias' => 'categorias.gerenciar', 'terminais' => 'caixa.gerenciar'] as $resource => $permission) {
        if (auth()->user()->can($permission)) {
            return redirect()->route('cadastros.index', $resource);
        }
    }
    abort(403);
})->middleware('auth')->name('cadastros.home');

Route::get('/estoque/{operation}/produtos', [StockController::class, 'searchProducts'])->whereIn('operation', ['entrada', 'saida', 'ajustar'])->middleware('auth')->name('estoque.products.search');
