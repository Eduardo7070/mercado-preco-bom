<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleDraft extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'suspended'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }
}
