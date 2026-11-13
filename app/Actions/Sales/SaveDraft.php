<?php

namespace App\Actions\Sales;

use App\Actions\Operations\Fingerprint;
use App\Models\SaleDraft;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveDraft
{
    public function execute(array $data, User $user): SaleDraft
    {
        return DB::transaction(function () use ($data, $user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $values = ['label' => $data['label'], 'customer_name' => $data['customer_name'] ?? null, 'items' => $data['items']];
            $existing = SaleDraft::where('operation_id', $data['operation_id'])->first();
            if ($existing) {
                abort_unless($existing->user_id === $user->id, 403);
                if ($existing->status !== 'suspended' || (! empty($data['draft_id']) && (int) $data['draft_id'] !== $existing->id) || Fingerprint::make($existing->only('items', 'label', 'customer_name')) !== Fingerprint::make($values)) {
                    throw ValidationException::withMessages(['operation_id' => 'Atendimento já utilizado ou salvo com outros dados. Atualize a página.']);
                }

                return $existing;
            }
            if (! empty($data['draft_id'])) {
                $draft = SaleDraft::whereKey($data['draft_id'])->where('user_id', $user->id)->where('status', 'suspended')->lockForUpdate()->firstOrFail();
                $draft->update($values + ['operation_id' => $data['operation_id']]);

                return $draft;
            }

            return SaleDraft::create($values + ['operation_id' => $data['operation_id'], 'user_id' => $user->id]);
        });
    }
}
