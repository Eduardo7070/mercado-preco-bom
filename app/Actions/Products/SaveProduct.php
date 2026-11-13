<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveProduct
{
    public function execute(array $data, User $user): Product
    {
        return DB::transaction(function () use ($data, $user) {
            $p = Product::create($data)->fresh();
            $p->recordAudit('cadastro', $user, []);

            return $p;
        });
    }
}
