<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'inicio', ['title' => 'Início', 'module' => 'inicio', 'asset' => 'inicio', 'story' => 'Visão geral'])->name('inicio');
Route::view('/produtos/cadastrar', 'produtos.cadastrar', ['title' => 'Cadastrar produto', 'module' => 'produtos', 'asset' => 'produtos/cadastrar', 'story' => 'H01.001'])->name('produtos.cadastrar');
Route::view('/produtos/editar', 'produtos.editar', ['title' => 'Editar produto', 'module' => 'produtos', 'asset' => 'produtos/editar', 'story' => 'H01.002'])->name('produtos.editar');
Route::view('/produtos/inativar', 'produtos.inativar', ['title' => 'Inativar produto', 'module' => 'produtos', 'asset' => 'produtos/inativar', 'story' => 'H01.003'])->name('produtos.inativar');
Route::view('/produtos/consultar', 'produtos.consultar', ['title' => 'Consultar produtos', 'module' => 'produtos', 'asset' => 'produtos/consultar', 'story' => 'H01.004'])->name('produtos.consultar');
Route::view('/estoque/entrada', 'estoque.entrada', ['title' => 'Entrada de mercadoria', 'module' => 'estoque', 'asset' => 'estoque/entrada', 'story' => 'H02.001'])->name('estoque.entrada');
Route::view('/estoque/saida', 'estoque.saida', ['title' => 'Saída de mercadoria', 'module' => 'estoque', 'asset' => 'estoque/saida', 'story' => 'H02.002'])->name('estoque.saida');
Route::view('/estoque/consultar', 'estoque.consultar', ['title' => 'Consultar saldos', 'module' => 'estoque', 'asset' => 'estoque/consultar', 'story' => 'H02.003'])->name('estoque.consultar');
Route::view('/estoque/ajustar', 'estoque.ajustar', ['title' => 'Ajustar estoque', 'module' => 'estoque', 'asset' => 'estoque/ajustar', 'story' => 'H02.004'])->name('estoque.ajustar');
Route::view('/caixa/finalizar', 'caixa.finalizar', ['title' => 'Frente de caixa', 'module' => 'caixa', 'asset' => 'caixa/finalizar', 'story' => 'H03.001'])->name('caixa.finalizar');
Route::view('/caixa/cancelar', 'caixa.cancelar', ['title' => 'Cancelar item', 'module' => 'caixa', 'asset' => 'caixa/cancelar', 'story' => 'H03.002'])->name('caixa.cancelar');
Route::view('/caixa/pagamento', 'caixa.pagamento', ['title' => 'Pagamento', 'module' => 'caixa', 'asset' => 'caixa/pagamento', 'story' => 'H03.003'])->name('caixa.pagamento');
Route::view('/caixa/estornar', 'caixa.estornar', ['title' => 'Estornar venda', 'module' => 'caixa', 'asset' => 'caixa/estornar', 'story' => 'H03.004'])->name('caixa.estornar');
