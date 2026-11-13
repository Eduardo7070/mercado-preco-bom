<?php

namespace App\Actions\Access;

use App\Models\Profile;
use Illuminate\Validation\ValidationException;

class UpdateProfile
{
    public function execute(Profile $profile, array $data): void
    {
        if ($profile->slug === 'admin') {
            throw ValidationException::withMessages(['permissions' => 'As permissões do administrador são protegidas.']);
        }
        $profile->permissions()->sync($data['permissions'] ?? []);
    }
}
