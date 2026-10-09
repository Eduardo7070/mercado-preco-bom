@extends('layouts.auth')

@section('content')
    <div class="auth-card">
        <span class="eyebrow">Acesso autorizado</span>
        <h2>Bem-vindo de volta!</h2>
        <p class="subtitle">Acesse sua conta para continuar no Mercado Preço Bom.</p>

        @if (session('status'))
            <p class="feedback success">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="feedback error" role="alert">
                <strong>Não foi possível entrar.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf
            <label class="field">
                <span>E-mail</span>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="usuario@mercado.com" required autofocus>
            </label>

            <label class="field">
                <span>Senha</span>
                <input type="password" name="password" autocomplete="current-password" placeholder="Digite sua senha" required>
            </label>

            <div class="form-row">
                <label class="check">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Lembrar de mim</span>
                </label>
            </div>

            <button class="auth-button" type="submit">Entrar</button>
        </form>

        <p class="auth-link">Ainda não tem conta? <a href="{{ route('register') }}">Criar usuário</a></p>
    </div>
@endsection
