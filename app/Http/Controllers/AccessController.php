<?php

namespace App\Http\Controllers;

use App\Actions\Access\AssignUserProfile;
use App\Actions\Access\UpdateProfile;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function index(): View
    {
        return view('acessos.index', ['title' => 'Perfis e permissões', 'module' => 'acessos', 'asset' => 'acessos', 'profiles' => Profile::with('permissions')->get(), 'permissions' => Permission::orderBy('slug')->get(), 'users' => User::with('profile')->orderBy('name')->paginate(20)]);
    }

    public function updateProfile(UpdateProfileRequest $request, Profile $profile, UpdateProfile $action): RedirectResponse|JsonResponse
    {
        $action->execute($profile, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Permissões do perfil atualizadas com sucesso.']);
        }

        return back()->with('status', 'Permissões atualizadas.');
    }

    public function updateUser(UpdateUserProfileRequest $request, User $user, AssignUserProfile $action): RedirectResponse
    {
        $action->execute($user, $request->validated(), $request->user());

        return back()->with('status', 'Perfil atualizado.');
    }
}
