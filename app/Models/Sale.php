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
        return ['total_cents' => 'integer', 'received_cents' => 'integer', 'change_cents' => 'integer', 'simulated' => 'boolean', 'refunded_at' => 'datetime'];
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
