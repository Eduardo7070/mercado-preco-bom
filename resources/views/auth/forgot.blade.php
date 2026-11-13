@extends('layouts.auth')
@section('content')
<div class="auth-card"><h2>Recuperar acesso</h2><p class="subtitle">Informe seu e-mail para receber o link de redefinição.</p>
<form class="auth-form" method="POST" action="{{ route('password.email') }}">@csrf
<label class="field">E-mail<input name="email" type="email" required autocomplete="email" value="{{ old('email') }}"></label>
<button class="auth-button">Enviar instruções</button></form><p class="auth-link"><a href="{{ route('login') }}">Voltar ao login</a></p></div>
@endsection