<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title }} | Mercado Preço Bom</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}" />
    <link rel="icon" type="image/png" href="{{ asset('storage/assets/logo_mercado_verde.png') }}">
</head>

<body>
    <main class="auth-shell">
        <section class="auth-brand" aria-label="Apresentação do Mercado Preço Bom">
            <a class="auth-logo" href="{{ route('login') }}">
                <img src="{{ asset('storage/assets/logo_mercado_branca.png') }}" alt="Mercado Preço Bom">
                <span><small>MERCADO</small><strong>Preço Bom</strong></span>
            </a>
            <div class="auth-brand-content">
                <span class="auth-tag">Sistema de supermercado</span>
                <h1>Gestão simples, segura e rápida para o seu mercado.</h1>
                <p>Controle produtos, estoque e frente de caixa em uma interface organizada para a rotina da loja.</p>
                <div class="auth-metrics" aria-label="Recursos do sistema">
                    <span>Produtos</span>
                    <span>Estoque</span>
                    <span>Caixa</span>
                </div>
            </div>
        </section>

        <section class="auth-panel" aria-label="Acesso ao sistema">
            @yield('content')
        </section>
    </main>
</body>

</html>
