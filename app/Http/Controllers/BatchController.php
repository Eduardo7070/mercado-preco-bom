<?php

namespace App\Http\Controllers;

use App\Actions\Stock\RegularizeBatch;
use App\Http\Requests\BatchRequest;
use App\Models\StockBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(Request $r): View
    {
        $r->validate(['q' => ['nullable', 'string', 'max:191'], 'page' => ['nullable', 'integer', 'min:1']]);
        $q = StockBatch::query()->join('products', 'products.id', '=', 'stock_batches.product_id')->select('stock_batches.*', 'products.nome', 'products.unidade', 'products.validade')->where('stock_batches.quantity', '>', 0);
        if ($r->filled('q')) {
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $r->string('q')).'%';
            $q->where(fn ($q) => $q->whereRaw("products.nome LIKE ? ESCAPE '!'", [$term])->orWhereRaw("stock_batches.lot LIKE ? ESCAPE '!'", [$term]));
        }

        return view('estoque.lotes', ['title' => 'Lotes e validade', 'module' => 'estoque', 'asset' => 'estoque/lotes', 'batches' => $q->orderBy('stock_batches.expires_on')->paginate(20)->withQueryString()]);
    }

    public function update(BatchRequest $r, StockBatch $batch): RedirectResponse
    {
        app(RegularizeBatch::class)->execute($batch, $r->validated(), $r->user());

        return back()->with('status', 'Lote regularizado. A validade e o bloqueio são verificados antes da venda.');
    }
}
