@extends('layouts.mercado')
@section('content')
@include('partials.form-feedback')
<nav class="abas" aria-label="Tipos de cadastro">
@foreach(['clientes'=>'clientes.gerenciar','fornecedores'=>'fornecedores.gerenciar','categorias'=>'categorias.gerenciar','terminais'=>'caixa.gerenciar'] as $kind=>$permission)
@can($permission)<a href="{{ route('cadastros.index',$kind) }}" @if($resource===$kind) aria-current="page" @endif>{{ ucfirst($kind) }}</a>@endcan
@endforeach
</nav>
<section class="painel"><div class="painel-titulo"><h2>{{ $record->exists?'Editar cadastro':'Novo cadastro' }}</h2></div>
<form class="painel-corpo" method="POST" action="{{ $record->exists?route('cadastros.update',[$resource,$record->id]):route('cadastros.store',$resource) }}" data-server-form>
@csrf @if($record->exists) @method('PUT') @endif
<div class="campos">
<label class="campo">Nome *<input name="name" maxlength="191" required value="{{ old('name',$record->name) }}"></label>
@if($resource!=='terminais')<label class="campo">Situação<select name="active"><option value="1" @selected(old('active',$record->active??true))>Ativo</option><option value="0" @selected(!old('active',$record->active??true))>Inativo</option></select></label>@endif
@if(in_array($resource,['clientes','fornecedores']))
<label class="campo">E-mail<input name="email" type="email" maxlength="191" value="{{ old('email',$record->email) }}"></label>
<label class="campo">Telefone<input name="phone" maxlength="30" value="{{ old('phone',$record->phone) }}"></label>
<label class="campo">Documento<input name="document" maxlength="30" value="{{ old('document',$record->document) }}"></label>
@endif
</div><div class="acoes"><button class="botao">Salvar cadastro</button>@if($record->exists)<a href="{{ route('cadastros.index',$resource) }}" class="botao secundario">Cancelar edição</a>@endif</div>
</form></section>
<section class="painel"><form class="painel-corpo filtros" method="GET" data-server-form><label class="campo">Pesquisar<input type="search" name="q" maxlength="191" value="{{ request('q') }}"></label><button type="submit" class="botao secundario">Pesquisar</button></form>
<div class="tabela-scroll"><table><thead><tr><th>Nome</th><th>Situação</th><th>Ações</th></tr></thead><tbody>
@forelse($records as $row)<tr><td>{{ $row->name }}@if($row->email)<small>{{ $row->email }}</small>@endif</td><td>{{ $resource==='terminais'?($row->active_session_id?'Em uso':'Disponível'):($row->active?'Ativo':'Inativo') }}</td><td>
<a href="{{ route('cadastros.index',[$resource,'edit'=>$row->id]) }}">Editar</a>
<form method="POST" action="{{ route('cadastros.destroy',[$resource,$row->id]) }}" data-server-form data-confirm="Excluir este cadastro? Registros com histórico não poderão ser excluídos.">@csrf @method('DELETE')<button class="link perigo-link">Excluir</button></form>
</td></tr>@empty<tr><td colspan="3">Nenhum cadastro encontrado.</td></tr>@endforelse
</tbody></table></div><div class="painel-corpo">{{ $records->links() }}</div></section>
@endsection