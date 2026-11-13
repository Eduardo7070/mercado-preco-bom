<?php

namespace App\Http\Controllers;

use App\Actions\Sales\Checkout;
use App\Actions\Sales\RefundSale;
use App\Actions\Sales\SearchSales;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\RefundSaleRequest;
use App\Http\Requests\SaleSearchRequest;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashierController extends Controller
{
    private function context(string $title, string $asset): array
    {
        return ['title' => $title, 'module' => 'caixa', 'asset' => 'caixa/'.$asset, 'story' => 'H03 / Frente de caixa'];
    }

    public function index(Request $request, SearchSales $search): View
    {
        return view('caixa.finalizar', array_merge($this->context('Frente de caixa', 'finalizar'), ['products' => Product::where('ativo', true)->where('gondola', '>', 0)->orderBy('nome')->get(), 'recent' => $search->query($request->user())->latest('id')->limit(5)->get()]));
    }

    public function store(CheckoutRequest $request, Checkout $action): RedirectResponse
    {
        $sale = $action->execute($request->validated(), $request->user());

        return redirect()->route('caixa.vendas.show', $sale)->with('status', 'Venda registrada. Pagamento simulado aprovado.');
    }

    public function sales(SaleSearchRequest $request, SearchSales $search): View
    {
        $query = $search->query($request->user(), $request->validated());
        $total = (clone $query)->where('status', 'paid')->sum('total_cents');

        return view('caixa.vendas', array_merge($this->context('Vendas do caixa', 'vendas'), ['sales' => $query->latest('id')->paginate(20)->withQueryString(), 'total' => $total]));
    }

    public function show(Request $request, Sale $sale): View
    {
        abort_unless(in_array($request->user()->profile?->slug, ['admin', 'gerente']) || $sale->user_id === $request->user()->id, 403);

        return view('caixa.venda', array_merge($this->context('Venda #'.$sale->id, 'vendas'), ['sale' => $sale->load('items', 'user', 'refundedBy')]));
    }

    public function refund(RefundSaleRequest $request, Sale $sale, RefundSale $action): RedirectResponse
    {
        abort_unless(in_array($request->user()->profile?->slug, ['admin', 'gerente']) || $sale->user_id === $request->user()->id, 403);
        $action->execute($sale, $request->validated('reason'), $request->user());

        return redirect()->route('caixa.vendas.show',$sale)->with('status','Venda estornada e itens devolvidos à gôndola. Reembolso simulado.');
    }
}
