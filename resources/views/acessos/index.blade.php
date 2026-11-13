@extends('layouts.mercado')
@section('content')
@if(session('status')) <p class="aviso" role="status">{{ session('status') }}</p> @endif
@if($errors->any()) <p class="aviso erro" role="alert">{{ $errors->first() }}</p> @endif
<section class="painel">
<div class="painel-titulo"><h2>Permissões por perfil</h2></div>
<div class="painel-corpo">
@foreach($profiles as $profile)
<form method="POST" action="{{ route('acessos.profiles.update',$profile) }}" data-server-form>
@csrf @method('PUT')
<fieldset @disabled($profile->slug === 'admin')><legend>{{ $profile->name }}</legend>
@foreach($permissions as $permission)
<label style="display:inline-flex;gap:8px;margin:8px 16px 8px 0"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($profile->permissions->contains($permission))>{{ $permission->name }}</label>
@endforeach
<div class="acoes"><button class="botao">Salvar permissões</button></div></fieldset>
</form>
@endforeach
</div></section>
<section class="painel usuarios-perfis" aria-labelledby="usuarios-perfis-titulo">
    <div class="painel-titulo">
        <div>
            <h2 id="usuarios-perfis-titulo">Perfil dos usuários</h2>
            <p class="usuarios-descricao">Defina o acesso de cada pessoa ao sistema.</p>
        </div>
        <span class="usuarios-contagem">{{ $users->total() }} {{ $users->total() === 1 ? 'usuário' : 'usuários' }}</span>
    </div>
    <div class="usuarios-colunas" aria-hidden="true">
        <span>Usuário</span>
        <span>Perfil de acesso</span>
        <span>Ação</span>
    </div>
    <div class="usuarios-lista">
        @forelse($users as $user)
            <form method="POST" action="{{ route('acessos.users.update', $user) }}" data-server-form class="usuario-linha">
                @csrf
                @method('PUT')
                <div class="usuario-identidade">
                    <div class="usuario-nome">
                        <strong>{{ $user->name }}</strong>
                        @if($user->is(auth()->user()))
                            <span class="usuario-atual">Você</span>
                        @endif
                    </div>
                    <span class="usuario-email">{{ $user->email }}</span>
                </div>
                <div class="usuario-perfil">
                    <label class="usuario-perfil-label" for="perfil-{{ $user->id }}">Perfil de {{ $user->name }}</label>
                    <select id="perfil-{{ $user->id }}" name="profile_id" @disabled($user->is(auth()->user())) aria-describedby="{{ $user->is(auth()->user()) ? 'perfil-protegido-'.$user->id : 'perfil-ajuda-'.$user->id }}">
                        @foreach($profiles as $profile)
                            <option value="{{ $profile->id }}" @selected($user->profile_id === $profile->id)>{{ $profile->name }}</option>
                        @endforeach
                    </select>
                    @if($user->is(auth()->user()))
                        <small id="perfil-protegido-{{ $user->id }}">Seu próprio perfil é protegido.</small>
                    @else
                        <small id="perfil-ajuda-{{ $user->id }}">A alteração vale após salvar.</small>
                    @endif
                </div>
                <button type="submit" class="botao usuario-salvar" @disabled($user->is(auth()->user())) aria-label="Salvar perfil de {{ $user->name }}">Salvar perfil</button>
            </form>
        @empty
            <p class="usuarios-vazio">Nenhum usuário cadastrado.</p>
        @endforelse
    </div>
    @if($users->hasPages())
        <div class="usuarios-paginacao">{{ $users->links() }}</div>
    @endif
</section>
@endsection