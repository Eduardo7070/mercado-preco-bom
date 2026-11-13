<?php

namespace App\Http\Controllers;

use App\Actions\Administration\ChangePassword;
use App\Http\Requests\RecoveryRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class RecoveryController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot', ['title' => 'Recuperar acesso']);
    }

    public function email(RecoveryRequest $r): RedirectResponse
    {
        Password::sendResetLink($r->only('email'));

        return back()->with('status', 'Se o e-mail estiver cadastrado, as instruções de recuperação serão enviadas.');
    }

    public function reset(Request $r, string $token): View
    {
        return view('auth.reset', ['title' => 'Redefinir senha', 'token' => $token, 'email' => $r->query('email')]);
    }

    public function update(RecoveryRequest $r): RedirectResponse
    {
        $status = Password::reset($r->validated(), function (User $user, string $password): void {
            app(ChangePassword::class)->execute($user, $password);
        });

        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('status', 'Senha redefinida. Entre com a nova senha.') : back()->withErrors(['email' => 'Link inválido ou expirado. Solicite uma nova recuperação.'])->withInput($r->only('email'));
    }
}
