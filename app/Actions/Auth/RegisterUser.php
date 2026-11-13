<?php

namespace App\Actions\Auth;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->profile_id = Profile::where('slug', 'caixa')->sole()->id;
            $user->save();

            return $user;
        });
    }
}
