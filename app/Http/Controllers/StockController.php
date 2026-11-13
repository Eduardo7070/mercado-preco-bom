<?php

namespace App\Http\Controllers;

use App\Actions\Products\SearchProducts;
use App\Actions\Stock\AdjustBalances;
use App\Actions\Stock\RecordMovement;
use App\Actions\Stock\TransferStock;
use App\Http\Requests\AdjustBalancesRequest;
use App\Http\Requests\StockProductSearchRequest;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\TransferStockRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockController extends Controller
{
    public function form(Request $request, string $operation): View|RedirectResponse
    {
        if ($operation === 'ajustar') {
            Gate::authorize('estoque.ajustar');
            $request->session()->reflash();

            return redirect()->route('estoque.saida', ['tipo' => 'contagem']);
        }
        abort_unless($operation === 'saida'
            ? $request->user()->canAny(['estoque.saida', 'estoque.ajustar'])
            : $request->user()->can('estoque.entrada'), 403);
        $titles = ['entrada' => 'Entrada de mercadoria', 'saida' => 'Ajuste de estoque'];

        $previousId = filter_var($request->old('product_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $selectedProduct = $previousId ? Product::query()
            ->select('id', 'codigo', 'barras', 'nome', 'unidade', 'deposito', 'gondola', 'validade', 'ativo')
            ->when($operation === 'entrada', fn ($query) => $query->where('ativo', true))->find($previousId) : null;

        return view('estoque.'.$operation, [
            'title' => $titles[$operation], 'module' => 'estoque', 'asset' => 'estoque/'.$operation,
            'selectedProduct' => $selectedProduct,
            'productSearch' => ['url' => route('estoque.products.search', ['operation' => $operation]), 'selected' => $selectedProduct],
        ]);
    }

    public function store(StoreStockMovementRequest $request, RecordMovement $action, string $operation): RedirectResponse
    {
        $action->execute($request->validated(), $request->operation(), $request->user());

        return redirect()->route('estoque.'.($operation === 'entrada' ? 'entrada' : 'saida'))->with('status', 'Movimentação registrada com sucesso.');
    }

    public function balances(AdjustBalancesRequest $request, Product $product, AdjustBalances $action): JsonResponse
    {
        $action->execute($product, $request->validated(), $request->user());

        return response()->json(['message' => 'Saldos ajustados e movimentações registradas.']);
    }

    public function transfer(TransferStockRequest $request, Product $product, TransferStock $action): JsonResponse
    {
        $action->execute($product, $request->validated(), $request->user());

        return response()->json(['message' => 'Transferência registrada entre os locais.']);
    }

    public function history(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('estoque.consultar');
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);

        return response()->json($product->movements()->with('user:id,name')->latest('id')->paginate(20));
    }

    public function searchProducts(StockProductSearchRequest $request, SearchProducts $search, string $operation): JsonResponse
    {
        $results = $search->execute([
            'q' => $request->validated('q'),
            'situacao' => $operation === 'entrada' ? 'ativos' : 'todos',
        ]);

        return response()->json([
            'data' => $results->getCollection()->take(10)->map(fn (Product $product) => $product->only(
                'id', 'codigo', 'barras', 'nome', 'unidade', 'deposito', 'gondola', 'validade', 'ativo'
            ))->values(),
            'has_more' => $results->total() > 10,
        ]);
    }
}
