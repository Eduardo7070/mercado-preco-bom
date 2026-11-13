<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['items' => 'array', 'amount_cents' => 'integer', 'cost_cents' => 'integer'];
    }
}
