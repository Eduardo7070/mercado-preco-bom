<?php

namespace App\Actions\Auth;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginUser
{
    public function execute(LoginRequest $request): void
    {
        $key = hash('sha256', mb_strtolower($request->string('email')->toString()).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Muitas tentativas. Aguarde um minuto.']);
        }
        if (! Auth::attempt(array_merge($request->safe()->only(['email', 'password']), ['active' => true]), $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos ou conta bloqueada.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
    }
}
