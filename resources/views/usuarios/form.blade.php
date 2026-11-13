@extends('layouts.mercado')
@section('content')
@include('partials.form-feedback')
<section class="painel"><form class="painel-corpo" method="POST" action="{{ $record->exists ? route('usuarios.update',$record) : route('usuarios.store') }}" data-server-form>
@csrf @if($record->exists) @method('PUT') @endif
<div class="campos">
<label class="campo">Nome *<input name="name" required maxlength="191" value="{{ old('name',$record->name) }}"></label>
<label class="campo">E-mail *<input name="email" type="email" required maxlength="191" value="{{ old('email',$record->email) }}"></label>
<label class="campo">Perfil *
<select name="profile_id" required @disabled($record->is(auth()->user()))>
@foreach($profiles as $profile)<option value="{{ $profile->id }}" @selected(old('profile_id',$record->profile_id)==$profile->id)>{{ $profile->name }}</option>@endforeach
</select></label>
@if($record->is(auth()->user()))<input type="hidden" name="profile_id" value="{{ $record->profile_id }}">@endif
<label class="campo">Acesso<select name="active" @disabled($record->is(auth()->user()))><option value="1" @selected(old('active',$record->active??true))>Ativo</option><option value="0" @selected(!old('active',$record->active??true))>Bloqueado</option></select></label>
@if($record->is(auth()->user()))<input type="hidden" name="active" value="1">@endif
<label class="campo">{{ $record->exists ? 'Nova senha (opcional)' : 'Senha *' }}<input name="password" type="password" minlength="8" autocomplete="new-password" @required(!$record->exists)></label>
<label class="campo">Confirmar senha<input name="password_confirmation" type="password" minlength="8" autocomplete="new-password"></label>
</div><p class="ajuda">Bloquear uma conta interrompe seu acesso. Senhas novas exigem pelo menos 8 caracteres.</p>
<div class="acoes"><a class="botao secundario" href="{{ route('usuarios.index') }}">Voltar</a><button class="botao">Salvar usuário</button></div>
</form></section>
@endsection