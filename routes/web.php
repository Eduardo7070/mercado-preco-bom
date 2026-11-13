<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/cadastro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register'])->name('register.store');
});

Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/', 'inicio', ['title' => 'Início', 'module' => 'inicio', 'asset' => 'inicio', 'story' => 'Visão geral'])->name('inicio');
    Route::redirect('/dashboard', '/')->name('dashboard');
    Route::view('/produtos/cadastrar', 'produtos.cadastrar', ['title' => 'Cadastrar produto', 'module' => 'produtos', 'asset' => 'produtos/cadastrar', 'story' => 'H01.001'])->middleware('can:produtos.cadastrar')->name('produtos.cadastrar');
    Route::view('/produtos/editar', 'produtos.editar', ['title' => 'Editar produto', 'module' => 'produtos', 'asset' => 'produtos/editar', 'story' => 'H01.002'])->middleware('can:produtos.editar')->name('produtos.editar');
    Route::view('/produtos/inativar', 'produtos.inativar', ['title' => 'Inativar produto', 'module' => 'produtos', 'asset' => 'produtos/inativar', 'story' => 'H01.003'])->middleware('can:produtos.inativar')->name('produtos.inativar');
    Route::view('/produtos/consultar', 'produtos.consultar', ['title' => 'Consultar produtos', 'module' => 'produtos', 'asset' => 'produtos/consultar', 'story' => 'H01.004'])->middleware('can:produtos.consultar')->name('produtos.consultar');
    Route::view('/estoque/consultar', 'estoque.consultar', ['title' => 'Consultar saldos', 'module' => 'estoque', 'asset' => 'estoque/consultar', 'story' => 'H02.003'])->middleware('can:estoque.consultar')->name('estoque.consultar');
    Route::view('/caixa/finalizar', 'caixa.finalizar', ['title' => 'Frente de caixa', 'module' => 'caixa', 'asset' => 'caixa/finalizar', 'story' => 'H03.001'])->middleware('can:caixa.finalizar')->name('caixa.finalizar');
    Route::view('/caixa/cancelar', 'caixa.cancelar', ['title' => 'Cancelar item', 'module' => 'caixa', 'asset' => 'caixa/cancelar', 'story' => 'H03.002'])->middleware('can:caixa.cancelar')->name('caixa.cancelar');
    Route::view('/caixa/pagamento', 'caixa.pagamento', ['title' => 'Pagamento', 'module' => 'caixa', 'asset' => 'caixa/pagamento', 'story' => 'H03.003'])->middleware('can:caixa.pagamento')->name('caixa.pagamento');
    Route::view('/caixa/estornar', 'caixa.estornar', ['title' => 'Estornar venda', 'module' => 'caixa', 'asset' => 'caixa/estornar', 'story' => 'H03.004'])->middleware('can:caixa.estornar')->name('caixa.estornar');
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
Route::get('/estoque/saida', [StockController::class, 'form'])->defaults('operation', 'saida')->middleware(['auth', 'can:estoque.saida'])->name('estoque.saida');
Route::get('/estoque/ajustar', [StockController::class, 'form'])->defaults('operation', 'ajustar')->middleware(['auth', 'can:estoque.ajustar'])->name('estoque.ajustar');
