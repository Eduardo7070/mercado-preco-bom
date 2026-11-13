<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SearchProducts
{
    public function execute(array $filters): LengthAwarePaginator
    {
        $q = trim($filters['q'] ?? '');

        return Product::query()->when($q !== '', function (Builder $query) use ($q) {
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q).'%';
            $query->where(function (Builder $query) use ($term) {
                $query->whereRaw("nome LIKE ? ESCAPE '!'", [$term])->orWhereRaw("codigo LIKE ? ESCAPE '!'", [$term])->orWhereRaw("barras LIKE ? ESCAPE '!'", [$term]);
            });
        })->when(in_array($filters['situacao'] ?? 'todos', ['ativos', 'inativos']), fn (Builder $query) => $query->where('ativo', $filters['situacao'] === 'ativos'))->when($filters['categoria'] ?? null, fn (Builder $q, $category) => $q->where('categoria', $category))->when(($filters['saldo'] ?? '') === 'zero', fn (Builder $q) => $q->whereRaw('(deposito + gondola) = 0'))->when(($filters['saldo'] ?? '') === 'baixo', fn (Builder $q) => $q->where('gondola', '<=', 5))->orderBy('nome')->orderBy('id')->paginate(30);
    }
}
