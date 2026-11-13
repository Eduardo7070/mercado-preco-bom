<?php

namespace App\Http\Controllers;

use App\Actions\Auth\LoginUser;
use App\Actions\Auth\LogoutUser;
use App\Actions\Auth\RegisterUser;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login', ['title' => 'Entrar']);
    }

    public function login(LoginRequest $request, LoginUser $action): RedirectResponse
    {
        $action->execute($request);

        return redirect()->intended(route('inicio'))->with('status', 'Login realizado com sucesso.');
    }

    public function showRegister(): View
    {
        return view('auth.register', ['title' => 'Criar conta']);
    }

    public function register(RegisterRequest $request, RegisterUser $action): RedirectResponse
    {
        Auth::login($action->execute($request->validated()));
        $request->session()->regenerate();

        return redirect()->route('inicio')->with('status', 'Conta criada com sucesso.');
    }

    public function logout(Request $request, LogoutUser $action): RedirectResponse
    {
        $action->execute($request);

        return redirect()->route('login')->with('status', 'Você saiu do sistema com segurança.');
    }
}
