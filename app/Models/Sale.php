<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['returned_cents' => 'integer', 'total_cents' => 'integer', 'received_cents' => 'integer', 'change_cents' => 'integer', 'simulated' => 'boolean', 'refunded_at' => 'datetime'];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'Paga (simulada)','refunded' => 'Estornada',default => 'Devolução parcial'
        };
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}
