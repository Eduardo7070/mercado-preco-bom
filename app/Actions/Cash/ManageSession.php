<?php

namespace App\Actions\Cash;

use App\Actions\Operations\Fingerprint as F;
use App\Actions\Operations\Quantity as Q;
use App\Models\CashEntry;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageSession
{
    public function current(User $user, bool $lock = false): ?CashSession
    {
        $q = CashSession::where('user_id', $user->id)->where('status', 'open');

        return ($lock ? $q->lockForUpdate() : $q)->first();
    }

    public function requireOpen(User $user, ?int $id = null): CashSession
    {
        $session = $this->current($user, true);
        if (! $session || ($id !== null && $session->id !== $id)) {
            throw ValidationException::withMessages(['cash_session_id' => 'Abra seu caixa antes de concluir a operação.']);
        }

        return $session;
    }

    public function expected(CashSession $session): int
    {
        return $session->opening_cents + (int) CashEntry::where('cash_session_id', $session->id)->sum('amount_cents');
    }

    public function execute(array $data, User $user): CashSession
    {
        return DB::transaction(function () use ($data, $user) {
            if ($data['action'] === 'manager_close') {
                abort_unless($user->can('caixa.gerenciar'), 403);
                $target = CashSession::findOrFail($data['session_id']);
                User::whereIn('id', [$user->id, $target->user_id])->orderBy('id')->lockForUpdate()->get();
                $session = CashSession::whereKey($target->id)->lockForUpdate()->firstOrFail();
                if ($session->status !== 'open') {
                    throw ValidationException::withMessages(['session_id' => 'Esta sessão já foi fechada.']);
                }
                $data['reason'] = 'Fechamento gerencial por '.$user->name.': '.$data['reason'];

                return $this->close($session, $data, $user);
            }
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($data['action'] === 'open') {
                if ($this->current($user, true)) {
                    throw ValidationException::withMessages(['action' => 'Você já possui um caixa aberto.']);
                }
                $register = CashRegister::whereKey($data['register_id'])->lockForUpdate()->firstOrFail();
                if ($register->active_session_id) {
                    throw ValidationException::withMessages(['register_id' => 'Este terminal está em uso.']);
                }
                $session = CashSession::create(['cash_register_id' => $register->id, 'user_id' => $user->id, 'opening_cents' => Q::cents($data['amount']), 'status' => 'open']);
                $register->active_session_id = $session->id;
                $register->save();

                return $session;
            }
            $session = $this->requireOpen($user, isset($data['session_id']) ? (int) $data['session_id'] : null);
            if ($data['action'] === 'close') {
                return $this->close($session, $data, $user);
            }
            $hash = F::make(['session_id' => $session->id, 'data' => $data]);
            $existing = CashEntry::where('operation_id', $data['operation_id'])->first();
            if ($existing) {
                abort_unless($existing->user_id === $user->id, 403);
                F::assert($existing->request_hash, $hash);

                return $session;
            }
            $amount = Q::cents($data['amount']) * ($data['action'] === 'withdraw' ? -1 : 1);
            if ($amount === 0 || $this->expected($session) + $amount < 0) {
                throw ValidationException::withMessages(['amount' => 'Valor inválido ou superior ao dinheiro disponível.']);
            }
            CashEntry::create(['operation_id' => $data['operation_id'], 'request_hash' => $hash, 'cash_session_id' => $session->id, 'user_id' => $user->id, 'kind' => $data['action'], 'amount_cents' => $amount, 'reason' => $data['reason']]);

            return $session;
        });
    }

    private function close(CashSession $session, array $data, User $user): CashSession
    {
        $expected = $this->expected($session);
        $counted = Q::cents($data['amount']);
        if ($expected !== $counted && mb_strlen(trim($data['reason'] ?? '')) < 5) {
            throw ValidationException::withMessages(['reason' => 'Justifique a diferença entre valor contado e esperado.']);
        }
        $session->update(['status' => 'closed', 'closed_by' => $user->id, 'closed_at' => now(), 'expected_cents' => $expected, 'counted_cents' => $counted, 'difference_cents' => $counted - $expected, 'closing_note' => $data['reason'] ?? null]);
        CashRegister::whereKey($session->cash_register_id)->update(['active_session_id' => null]);

        return $session;
    }
}
