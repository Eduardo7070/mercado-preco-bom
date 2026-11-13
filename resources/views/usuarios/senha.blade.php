@extends('layouts.mercado')
@section('content')
@include('partials.form-feedback')
<section class="painel"><form class="painel-corpo" method="POST" action="{{ route('account.password.update') }}" data-server-form>
@csrf @method('PUT')
<div class="campos">
<label class="campo">Senha atual<input name="current_password" type="password" required autocomplete="current-password"></label>
<label class="campo">Nova senha<input name="password" type="password" minlength="8" required autocomplete="new-password"></label>
<label class="campo">Confirmar nova senha<input name="password_confirmation" type="password" required autocomplete="new-password"></label>
</div><div class="acoes"><button class="botao">Alterar senha</button></div>
</form></section>
@endsection