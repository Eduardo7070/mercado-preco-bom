<?php

namespace App\Actions\Administration;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChangePassword
{
    public function execute(User $user, string $password, ?string $keepSession = null): void
    {
        DB::transaction(function () use ($user, $password, $keepSession) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $user->password = $password;
            $user->remember_token = Str::random(60);
            $user->save();
            if (config('session.driver') === 'database') {
                DB::table('sessions')->where('user_id', $user->id)->when($keepSession, fn ($q) => $q->where('id', '!=', $keepSession))->delete();
            }
        });
    }
}
