<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mercado Preço Bom</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('storage/assets/logo_mercado_verde.png') }}">
</head>

<body>
    <main class="auth-shell">
        <section class="auth-brand" aria-label="Mercado Preço Bom">
            <a class="auth-logo" href="{{ route('login') }}">
                <img src="{{ asset('storage/assets/logo_mercado_branca.png') }}" alt="Mercado Preço Bom">
                <span><small>MERCADO</small><strong>Preço Bom</strong></span>
            </a>
            <div class="auth-brand-content">
                <span class="auth-tag">Sistema de gestão</span>
                <h1>Mercado Preço Bom</h1>
                <p>Protótipo de gestão para supermercado com módulos de produtos, estoque e frente de caixa.</p>
                <div class="auth-metrics" aria-label="Módulos do sistema">
                    <span>Produtos</span>
                    <span>Estoque</span>
                    <span>Caixa</span>
                </div>
            </div>
        </section>

        <section class="auth-panel" aria-label="Acesso ao sistema">
            <div class="auth-card">
                <span class="eyebrow">Acesso ao sistema</span>
                <h2>Bem-vindo!</h2>
                <p class="subtitle">Entre com sua conta para acessar as telas do Mercado Preço Bom.</p>
                <a class="auth-button auth-button-link" href="{{ route('login') }}">Entrar no sistema</a>
                <p class="auth-link">Ainda não tem conta? <a href="{{ route('register') }}">Criar usuário</a></p>
            </div>
        </section>
    </main>
</body>

</html>
