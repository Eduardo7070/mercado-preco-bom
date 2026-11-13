<?php

namespace App\Actions\Products;

use App\Models\Product;

class SaveProduct
{
    public function execute(array $data): Product
    {
        return Product::create($data);
    }
}
