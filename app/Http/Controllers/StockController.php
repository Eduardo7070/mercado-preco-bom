<?php

namespace App\Http\Controllers;

use App\Actions\Stock\RecordMovement;
use App\Http\Requests\StoreStockMovementRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockController extends Controller
{
    public function form(string $operation): View
    {
        Gate::authorize('estoque.'.$operation);
        $titles = ['entrada' => 'Entrada de mercadoria', 'saida' => 'Saída de mercadoria', 'ajustar' => 'Ajustar estoque'];
        $stories = ['entrada' => 'H02.001', 'saida' => 'H02.002', 'ajustar' => 'H02.004'];

        return view('estoque.'.$operation, ['title' => $titles[$operation], 'module' => 'estoque', 'asset' => 'estoque/'.$operation, 'story' => $stories[$operation], 'products' => Product::where('ativo', true)->orderBy('nome')->get()]);
    }

    public function store(StoreStockMovementRequest $request, RecordMovement $action, string $operation): RedirectResponse
    {
        $action->execute($request->validated(), $operation, $request->user());

        return redirect()->route('estoque.'.$operation)->with('status', 'Movimentação registrada com sucesso.');
    }
}
