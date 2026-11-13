<?php

namespace App\Actions\Access;

use App\Actions\Administration\SaveUser;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignUserProfile
{
    public function execute(User $user, array $data, User $actor): void
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages(['profile_id' => 'Você não pode alterar seu próprio perfil.']);
        }
        app(SaveUser::class)->execute($user, $data, $actor);
    }
}
