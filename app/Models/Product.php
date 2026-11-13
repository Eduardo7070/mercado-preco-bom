<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['codigo', 'barras', 'nome', 'categoria', 'unidade', 'preco', 'validade', 'custo', 'category_id'];

    protected function casts(): array
    {
        return ['preco' => 'float', 'ativo' => 'boolean', 'validade' => 'boolean', 'deposito' => 'float', 'gondola' => 'float', 'movimentado_em' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $p): void {
            if ($p->isDirty('categoria') || ! $p->category_id) {
                $p->category_id = Category::where('name', $p->categoria)->value('id');
            }
        });
        static::saved(function (Product $p): void {
            if (! $p->wasRecentlyCreated && ! $p->wasChanged(['codigo', 'barras'])) {
                return;
            }
            DB::table('product_identifiers')->where('product_id', $p->id)->delete();
            try {
                foreach (array_unique(array_filter([$p->codigo, $p->barras], fn ($v) => $v !== null && $v !== '')) as $value) {
                    DB::table('product_identifiers')->insert(['identifier' => $value, 'product_id' => $p->id]);
                }
            } catch (UniqueConstraintViolationException $e) {
                throw ValidationException::withMessages(['codigo' => 'Código interno ou código de barras já pertence a outro produto.']);
            }
        });
    }

    public function version(): string
    {
        return hash('sha256', json_encode($this->getAttributes()));
    }

    public function recordAudit(string $action, User $user, array $before, ?string $reason = null): void
    {
        DB::table('product_audits')->insert([
            'product_id' => $this->id, 'user_id' => $user->id, 'product_name' => $this->nome,
            'action' => $action, 'reason' => $reason, 'before' => json_encode($before),
            'after' => json_encode($this->getAttributes()), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
