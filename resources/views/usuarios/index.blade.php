@extends('layouts.mercado')
@section('content')
@include('partials.form-feedback')
<section class="painel"><div class="painel-titulo"><h2>Contas e funcionários</h2><a class="botao" href="{{ route('usuarios.create') }}">Cadastrar usuário</a></div>
<div class="tabela-scroll"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Acesso</th><th>Ação</th></tr></thead><tbody>
@foreach($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->profile?->name }}</td><td>{{ $user->active ? 'Ativo' : 'Bloqueado' }}</td><td><a href="{{ route('usuarios.edit',$user) }}">Editar / bloquear</a></td></tr>@endforeach
</tbody></table></div><div class="painel-corpo">{{ $users->links() }}</div></section>
@endsection