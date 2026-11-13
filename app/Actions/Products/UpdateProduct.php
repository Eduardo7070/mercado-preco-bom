<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateProduct
{
    public function execute(Product $product, array $data, User $user): Product
    {
        return DB::transaction(function () use ($product, $data, $user) {
            $p = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if (! hash_equals($p->version(), $data['version'])) {
                throw ValidationException::withMessages(['version' => 'O produto foi alterado. Feche e abra o formulário novamente.']);
            }
            if ($data['unidade'] !== $p->unidade && ($p->deposito + $p->gondola > 0 || $p->movements()->exists() || DB::table('sale_items')->where('product_id', $p->id)->exists())) {
                throw ValidationException::withMessages(['unidade' => 'Não é possível mudar a unidade de um produto com saldo ou histórico.']);
            }
            $before = $p->getAttributes();
            unset($data['version'],$data['codigo']);
            $data['validade'] = $data['validade'] ?? false;
            $p->fill($data);
            $p->save();
            $p->recordAudit('edicao', $user, $before);

            return $p;
        });
    }
}
