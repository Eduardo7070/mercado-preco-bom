<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeProductStatus
{
    public function execute(Product $product, array $data, User $user): void
    {
        DB::transaction(function () use ($product, $data, $user) {
            $p = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if (! hash_equals($p->version(), $data['version'])) {
                throw ValidationException::withMessages(['version' => 'Produto alterado. Atualize os dados antes de continuar.']);
            }
            $before = $p->getAttributes();
            $p->ativo = (bool) $data['ativo'];
            $p->save();
            $p->recordAudit($p->ativo ? 'reativacao' : 'inativacao', $user, $before, $data['motivo']);
        });
    }
}
