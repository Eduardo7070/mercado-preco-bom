<?php

namespace App\Http\Controllers;

use App\Actions\Purchases\ReceivePurchase;
use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(): View
    {
        return view('compras.index', ['title' => 'Compras e recebimentos', 'module' => 'compras', 'asset' => 'acessos', 'products' => Product::where('ativo', true)->orderBy('nome')->get(), 'suppliers' => Supplier::where('active', true)->orderBy('name')->get(), 'purchases' => Purchase::with('supplier')->latest()->paginate(20)]);
    }

    public function store(PurchaseRequest $r, ReceivePurchase $a): RedirectResponse
    {
        $a->execute($r->validated(), $r->user());

        return back()->with('status', 'Compra recebida. Estoque e custo médio atualizados.');
    }
}
