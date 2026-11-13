<?php

namespace App\Http\Controllers;

use App\Actions\Cash\ManageSession;
use App\Actions\Sales\Checkout;
use App\Actions\Sales\RefundSale;
use App\Actions\Sales\ReturnItems;
use App\Actions\Sales\SaveDraft;
use App\Actions\Sales\SearchSales;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\DraftRequest;
use App\Http\Requests\RefundSaleRequest;
use App\Http\Requests\ReturnItemsRequest;
use App\Http\Requests\SaleSearchRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDraft;
use App\Models\SaleReturn;
use App\Models\StockBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashierController extends Controller
{
    private function context(string $title, string $asset): array
    {
        return ['title' => $title, 'module' => 'caixa', 'asset' => 'caixa/'.$asset];
    }

    public function index(Request $request): View
    {
        $screen = match ($request->route()->getName()) {
            'caixa.cancelar' => 'cancelar',
            'caixa.pagamento' => 'pagamento',
            default => 'finalizar',
        };
        $title = match ($screen) {
            'cancelar' => 'Cancelar item',
            'pagamento' => 'Pagamento',
            default => 'Nova venda',
        };

        return view('caixa.'.$screen, array_merge($this->context($title, $screen), ['products' => Product::where('ativo', true)->where('gondola', '>', 0)->orderBy('nome')->get()->map(function ($p) {
            $p->gondola = (float) StockBatch::where('product_id', $p->id)->where('local', 'gondola')->where('quarantine', false)->when($p->validade, fn ($q) => $q->whereNotNull('expires_on'))->where(fn ($q) => $q->whereNull('expires_on')->orWhere('expires_on', '>=', now('America/Sao_Paulo')->toDateString()))->sum('quantity');

            return $p;
        })->filter(fn ($p) => $p->gondola > 0)->values(),
            'session' => app(ManageSession::class)->current($request->user()),
            'drafts' => SaleDraft::where('user_id', $request->user()->id)->where('status', 'suspended')->latest()->get(),
            'customers' => Customer::where('active', true)->orderBy('name')->get(),
            'resume' => $request->filled('draft') ? SaleDraft::where('user_id', $request->user()->id)->where('status', 'suspended')->findOrFail($request->integer('draft')) : null]));
    }

    public function store(CheckoutRequest $request, Checkout $action): RedirectResponse
    {
        $sale = $action->execute($request->validated(), $request->user());

        return redirect()->route('caixa.vendas.show', $sale)->with('status', 'Venda registrada. Pagamento simulado aprovado.')->with('checkout_completed', $sale->id);
    }

    public function sales(SaleSearchRequest $request, SearchSales $search): View
    {
        $query = $search->query($request->user(), $request->validated());
        $total = (clone $query)->selectRaw('COALESCE(SUM(total_cents - returned_cents),0) as value')->value('value');

        return view('caixa.vendas', array_merge($this->context('Vendas do caixa', 'vendas'), ['sales' => $query->latest('id')->paginate(20)->withQueryString(), 'total' => $total]));
    }

    public function show(Request $request, Sale $sale): View
    {
        abort_unless(in_array($request->user()->profile?->slug, ['admin', 'gerente']) || $sale->user_id === $request->user()->id, 403);

        return view('caixa.venda', array_merge($this->context('Venda #'.$sale->id, 'vendas'), ['sale' => $sale->load('items', 'user', 'refundedBy'), 'returns' => SaleReturn::where('sale_id', $sale->id)->latest()->get()]));
    }

    public function refund(RefundSaleRequest $request, Sale $sale, RefundSale $action): RedirectResponse
    {
        abort_unless(in_array($request->user()->profile?->slug, ['admin', 'gerente']) || $sale->user_id === $request->user()->id, 403);
        $action->execute($sale, $request->validated('reason'), $request->user());

        return redirect()->route('caixa.vendas.show', $sale)->with('status', 'Venda estornada e itens devolvidos à gôndola. Reembolso simulado.');
    }

    public function returnItems(ReturnItemsRequest $request, Sale $sale, ReturnItems $action): RedirectResponse
    {
        abort_unless(in_array($request->user()->profile?->slug, ['admin', 'gerente']) || $sale->user_id === $request->user()->id, 403);
        $action->execute($sale, $request->validated(), $request->user());

        return redirect()->route('caixa.vendas.show', $sale)->with('status', 'Estorno registrado. Confira o valor devolvido e os produtos no recibo.');
    }

    public function draft(DraftRequest $request): JsonResponse
    {
        app(SaveDraft::class)->execute($request->validated(), $request->user());

        return response()->json(['message' => 'Venda suspensa. O estoque ainda não foi reservado.', 'url' => route('caixa.finalizar')]);
    }

    public function discardDraft(Request $request, SaleDraft $draft): RedirectResponse
    {
        abort_unless($draft->user_id === $request->user()->id, 403);
        $draft->update(['status' => 'discarded']);

        return redirect()->route('caixa.finalizar')->with('status', 'Atendimento descartado.')->with('discarded_draft', $draft->id);
    }

    public function refundPage(SaleSearchRequest $request, SearchSales $search, ManageSession $sessions): View
    {
        $request->validate(['sale' => ['nullable', 'integer', 'min:1']]);
        $sale = null;
        if ($request->filled('sale')) {
            $sale = Sale::with('items', 'user')->findOrFail($request->integer('sale'));
            abort_unless(in_array($request->user()->profile?->slug, ['admin', 'gerente']) || $sale->user_id === $request->user()->id, 403);
        }

        return view('caixa.estornar', array_merge($this->context('Estornar venda', 'estornar'), [
            'sale' => $sale,
            'sales' => $search->query($request->user(), $request->validated())->whereIn('status', ['paid', 'partially_refunded'])->latest('id')->paginate(15)->withQueryString(),
            'session' => $sessions->current($request->user()),
        ]));
    }
}
