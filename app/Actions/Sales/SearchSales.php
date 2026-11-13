<?php

namespace App\Actions\Sales;

use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class SearchSales
{
    public function query(User $user, array $filters = []): Builder
    {
        return Sale::query()->with('user')->when(! in_array($user->profile?->slug, ['admin', 'gerente']), fn (Builder $q) => $q->where('user_id', $user->id))
            ->when($filters['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
                $q->where(fn (Builder $q) => $q->where('id', ctype_digit($term) ? $term : 0)->orWhereRaw("customer_name LIKE ? ESCAPE '!'", [$like]));
            })
            ->when($filters['from'] ?? null, fn (Builder $q, $date) => $q->where('created_at', '>=', CarbonImmutable::parse($date, 'America/Sao_Paulo')->startOfDay()->utc()))
            ->when($filters['to'] ?? null, fn (Builder $q, $date) => $q->where('created_at', '<=', CarbonImmutable::parse($date, 'America/Sao_Paulo')->endOfDay()->utc()))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status));
    }
}
