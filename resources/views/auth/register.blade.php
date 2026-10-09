@extends('layouts.auth')

@section('content')
    <div class="auth-card">
        <span class="eyebrow">Novo usuário</span>
        <h2>Criar conta</h2>
        <p class="subtitle">Cadastre um operador para acessar as telas do sistema.</p>

        @if ($errors->any())
            <div class="feedback error" role="alert">
                <strong>Revise os dados informados.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="auth-form">
            @csrf
            <label class="field">
                <span>Nome</span>
                <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="Nome do operador" required autofocus>
            </label>

            <label class="field">
                <span>E-mail</span>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="usuario@mercado.com" required>
            </label>

            <label class="field">
                <span>Senha</span>
                <input type="password" name="password" autocomplete="new-password" placeholder="Mínimo de 8 caracteres" required>
            </label>

            <label class="field">
                <span>Confirmar senha</span>
                <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repita a senha" required>
            </label>

            <button class="auth-button" type="submit">Criar conta e entrar</button>
        </form>

        <p class="auth-link">Já possui conta? <a href="{{ route('login') }}">Entrar no sistema</a></p>
    </div>
@endsection
