<?php

namespace App\Actions\Access;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignUserProfile
{
    public function execute(User $user, array $data, User $actor): void
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages(['profile_id' => 'Você não pode alterar seu próprio perfil.']);
        }
        $user->profile_id = $data['profile_id'];
        $user->save();
    }
}
