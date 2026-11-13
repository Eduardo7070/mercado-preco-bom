<?php

namespace App\Http\Controllers;

use App\Actions\Products\SaveProduct;
use App\Actions\Products\SearchProducts;
use App\Http\Requests\SearchProductRequest;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request, SaveProduct $action): RedirectResponse
    {
        $product = $action->execute($request->validated());

        return redirect()->route('produtos.cadastrar')->with('status', 'Produto '.$product->nome.' cadastrado com sucesso.');
    }

    public function search(SearchProductRequest $request, SearchProducts $action): JsonResponse
    {
        return response()->json($action->execute($request->validated()));
    }
}
