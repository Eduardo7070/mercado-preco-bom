@extends('layouts.auth')
@section('content')
<div class="auth-card"><h2>Redefinir senha</h2>
<form class="auth-form" method="POST" action="{{ route('password.update') }}">@csrf
<input name="token" type="hidden" value="{{ $token }}">
<label class="field">E-mail<input name="email" type="email" required value="{{ old('email',$email) }}"></label>
<label class="field">Nova senha<input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
<label class="field">Confirmar senha<input name="password_confirmation" type="password" required autocomplete="new-password"></label>
<button class="auth-button">Redefinir senha</button></form></div>
@endsection