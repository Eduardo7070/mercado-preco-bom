<?php

namespace App\Http\Controllers;

use App\Actions\Products\ChangeProductStatus;
use App\Actions\Products\DeleteProduct;
use App\Actions\Products\SaveProduct;
use App\Actions\Products\SearchProducts;
use App\Actions\Products\UpdateProduct;
use App\Http\Requests\DeleteProductRequest;
use App\Http\Requests\ProductStatusRequest;
use App\Http\Requests\SearchProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request, SaveProduct $action): RedirectResponse
    {
        $product = $action->execute($request->validated(), $request->user());

        return redirect()->route('produtos.cadastrar')->with('status', 'Produto '.$product->nome.' cadastrado com sucesso.');
    }

    public function search(SearchProductRequest $request, SearchProducts $action): JsonResponse
    {
        $results = $action->execute($request->validated(), $request->user());
        $results->getCollection()->transform(fn (Product $p) => array_merge($p->toArray(), ['version' => $p->version()]));

        return response()->json($results);
    }

    public function listing(Request $request): View
    {
        $route = $request->route()->getName();

        $stock = $route === 'estoque.consultar';
        abort_unless($stock ? $request->user()->can('estoque.consultar') : $request->user()->canAny(['produtos.consultar', 'produtos.editar', 'produtos.inativar']), 403);

        return view('produtos.catalogo', [
            'title' => $stock ? 'Consultar estoque' : 'Gerenciar produtos', 'module' => $stock ? 'estoque' : 'produtos',
            'asset' => $stock ? 'estoque/consultar' : 'produtos/consultar', 'stock' => $stock,
        ]);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()->can('produtos.consultar') || $request->user()->can('produtos.editar') || $request->user()->can('produtos.inativar') || $request->user()->can('estoque.consultar'), 403);
        $audits = [];
        if ($request->user()->can('produtos.editar')) {
            $audits = DB::table('product_audits')->join('users', 'users.id', '=', 'product_audits.user_id')->where('product_id', $product->id)->select('product_audits.action', 'product_audits.reason', 'product_audits.created_at', 'users.name as user')->latest('product_audits.id')->limit(20)->get();
        }

        return response()->json(array_merge($product->toArray(), ['version' => $product->version(), 'audits' => $audits]));
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProduct $action): JsonResponse
    {
        $action->execute($product, $request->validated(), $request->user());

        return response()->json(['message' => 'Produto atualizado com sucesso.']);
    }

    public function status(ProductStatusRequest $request, Product $product, ChangeProductStatus $action): JsonResponse
    {
        $action->execute($product, $request->validated(), $request->user());

        return response()->json(['message' => 'Situação do produto atualizada.']);
    }

    public function destroy(DeleteProductRequest $request, Product $product, DeleteProduct $action): JsonResponse
    {
        $action->execute($product, $request->validated(), $request->user());

        return response()->json(['message' => 'Produto excluído.']);
    }
}
