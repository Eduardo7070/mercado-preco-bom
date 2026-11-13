<?php

namespace App\Actions\Stock;

use App\Models\Product;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegularizeBatch
{
    public function execute(StockBatch $batch, array $data, User $user): void
    {
        DB::transaction(function () use ($batch, $data, $user) {
            $product = Product::whereKey($batch->product_id)->lockForUpdate()->firstOrFail();
            $batch->refresh();
            if ($product->validade && empty($data['expires_on'])) {
                throw ValidationException::withMessages(['expires_on' => 'Informe a validade real deste lote.']);
            }
            $before = $batch->getAttributes();
            $batch->update(['lot' => $data['lot'], 'expires_on' => $data['expires_on'] ?? null, 'quarantine' => $data['quarantine']]);
            $product->recordAudit('regularizacao_lote', $user, ['batch' => $before], $data['reason'].' | Lote atualizado: '.json_encode($batch->getAttributes(), JSON_UNESCAPED_UNICODE));
        });
    }
}
