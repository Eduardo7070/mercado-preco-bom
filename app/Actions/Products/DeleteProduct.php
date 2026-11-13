<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteProduct
{
    public function execute(Product $product, array $data, User $user): void
    {
        DB::transaction(function () use ($product, $data, $user) {
            $p = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if (! hash_equals($p->version(), $data['version'])) {
                throw ValidationException::withMessages(['version' => 'Produto alterado. Atualize os dados antes de continuar.']);
            }
            if ($p->deposito + $p->gondola > 0 || $p->movements()->exists() || DB::table('sale_items')->where('product_id', $p->id)->exists()) {
                throw ValidationException::withMessages(['produto' => 'Produto com saldo ou histórico não pode ser excluído. Utilize a inativação.']);
            }
            $p->recordAudit('exclusao', $user, $p->getAttributes(), $data['motivo']);
            $p->delete();
        });
    }
}
