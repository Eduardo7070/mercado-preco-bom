<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['codigo', 'barras', 'nome', 'categoria', 'unidade', 'preco', 'validade'];

    protected function casts(): array
    {
        return ['preco' => 'float', 'ativo' => 'boolean', 'validade' => 'boolean', 'deposito' => 'integer', 'gondola' => 'integer', 'movimentado_em' => 'datetime'];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
