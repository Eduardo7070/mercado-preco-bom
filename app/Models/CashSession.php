<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashSession extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    protected function casts(): array
    {
        return ['opening_cents' => 'integer', 'expected_cents' => 'integer', 'counted_cents' => 'integer', 'difference_cents' => 'integer', 'closed_at' => 'datetime'];
    }
}
