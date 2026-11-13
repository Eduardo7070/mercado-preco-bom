<?php

namespace App\Actions\Administration;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveUser
{
    public function execute(?User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            $profile = Profile::findOrFail($data['profile_id']);
            if ($profile->slug === 'admin' && $actor->profile?->slug !== 'admin') {
                throw ValidationException::withMessages(['profile_id' => 'Somente administradores podem atribuir o perfil admin.']);
            }
            if ($user) {
                $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            }
            if ($user?->profile?->slug === 'admin' && $actor->profile?->slug !== 'admin') {
                throw ValidationException::withMessages(['profile_id' => 'Somente um administrador pode alterar outro administrador.']);
            }
            if ($user?->is($actor) && ((! ($data['active'] ?? $user->active)) || (int) $data['profile_id'] !== $user->profile_id)) {
                throw ValidationException::withMessages(['active' => 'Você não pode bloquear a si mesmo nem mudar seu próprio perfil.']);
            }
            $target = $user ?? new User;
            $target->name = $data['name'] ?? $target->name;
            $target->email = $data['email'] ?? $target->email;
            $target->profile_id = $profile->id;
            $target->active = $data['active'] ?? $target->active ?? true;
            $reset = ! empty($data['password']);
            if ($reset) {
                $target->password = $data['password'];
                $target->remember_token = Str::random(60);
            }
            if (! $target->active) {
                $target->remember_token = Str::random(60);
            }
            $target->save();
            if (($reset || ! $target->active) && config('session.driver') === 'database') {
                DB::table('sessions')->where('user_id', $target->id)->delete();
            }

            return $target;
        });
    }
}
