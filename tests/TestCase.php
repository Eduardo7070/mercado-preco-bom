<?php

namespace Tests;

use App\Actions\Cash\ManageSession;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function openCash(User $user): CashSession
    {
        $a = app(ManageSession::class);

        return $a->current($user) ?? $a->execute(['action' => 'open', 'register_id' => CashRegister::whereNull('active_session_id')->firstOrFail()->id, 'amount' => '100.00'], $user);
    }
}
