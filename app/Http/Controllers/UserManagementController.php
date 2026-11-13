<?php

namespace App\Http\Controllers;

use App\Actions\Administration\ChangePassword;
use App\Actions\Administration\SaveUser;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\UserManagementRequest;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private function context(string $title): array
    {
        return ['title' => $title, 'module' => 'acessos', 'asset' => 'acessos'];
    }

    public function index(): View
    {
        return view('usuarios.index', array_merge($this->context('Usuários'), ['users' => User::with('profile')->orderBy('name')->paginate(20)]));
    }

    public function create(): View
    {
        return view('usuarios.form', array_merge($this->context('Cadastrar usuário'), ['record' => new User, 'profiles' => Profile::all()]));
    }

    public function edit(User $user): View
    {
        return view('usuarios.form', array_merge($this->context('Editar usuário'), ['record' => $user, 'profiles' => Profile::all()]));
    }

    public function store(UserManagementRequest $r, SaveUser $a): RedirectResponse
    {
        $a->execute(null, $r->validated(), $r->user());

        return redirect()->route('usuarios.index')->with('status', 'Usuário cadastrado.');
    }

    public function update(UserManagementRequest $r, User $user, SaveUser $a): RedirectResponse
    {
        $a->execute($user, $r->validated(), $r->user());

        return redirect()->route('usuarios.index')->with('status', 'Usuário atualizado.');
    }

    public function password(): View
    {
        return view('usuarios.senha', $this->context('Alterar minha senha'));
    }

    public function changePassword(PasswordChangeRequest $r): RedirectResponse
    {
        app(ChangePassword::class)->execute($r->user(), $r->validated('password'), $r->session()->getId());
        $r->session()->regenerate();

        return back()->with('status', 'Senha atualizada com sucesso.');
    }
}
