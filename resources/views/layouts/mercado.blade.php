<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title }} | Mercado Preço Bom</title>

    <link rel="stylesheet" href="{{ asset('css/comum.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/'.$asset.'.css') }}" />

    @include('partials.alerts')
    <script defer src="{{ asset('js/comum.js') }}"></script>
    <script defer src="{{ asset('js/'.$asset.'.js') }}"></script>

    <link rel="icon" type="image/png" href="{{ asset('storage/logo_mercado_verde.png') }}">
</head>

<body class="pagina-{{ $module }}">
    @php
        $usuario = auth()->user();
        $partesNome = collect(explode(' ', $usuario?->name ?? 'Usuário'))->filter()->take(2);
        $iniciais = $partesNome->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('') ?: 'U';
    @endphp

    <a class="skip" href="#conteudo">Pular para o conteúdo</a>

    <header class="cabecalho">
        <a class="marca" href="{{ route('inicio') }}">
            <img
                src="{{ asset('storage/logo_mercado_branca.png') }}"
                alt="Mercado Preço Bom"
                class="marca-logo"
            >
            <span>
                <small>MERCADO</small>
                <strong>Preço Bom</strong>
            </span>
        </a>

        <div class="cabecalho-info">
            <strong>Sistema de gestão</strong>
            <span>Loja 01 · Unidade principal</span>
        </div>

        <div class="operador"><a class="logout-button" href="{{ route('account.password') }}">Minha senha</a>
            <span class="avatar">{{ $iniciais }}</span>

            <div>
                <strong>{{ $usuario?->name ?? 'Usuário' }}</strong>
                <span>{{ $usuario?->email ?? 'Conta autenticada' }}</span>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-button">Sair</button>
            </form>
        </div>
    </header>

    <nav class="navegacao" aria-label="Menu principal">
        <a
            href="{{ route('inicio') }}"
            @if($module === 'inicio') aria-current="page" @endif
        >
            Início
        </a>

        @canany(['produtos.consultar', 'produtos.editar', 'produtos.inativar'])<a
            href="{{ route('produtos.consultar') }}"
            @if($module === 'produtos') aria-current="page" @endif
        >
            Produtos
        </a>@endcanany

        @canany(['estoque.consultar', 'estoque.entrada', 'estoque.saida', 'estoque.ajustar'])<a
            href="{{ route(auth()->user()->can('estoque.consultar') ? 'estoque.consultar' : (auth()->user()->can('estoque.entrada') ? 'estoque.entrada' : 'estoque.saida')) }}"
            @if($module === 'estoque') aria-current="page" @endif
        >
            Estoque
        </a>@endcanany

        @can('caixa.finalizar')<a
            href="{{ route('caixa.finalizar') }}"
            @if($module === 'caixa' && !request()->routeIs('caixa.vendas*')) aria-current="page" @endif
        >
            Frente de caixa
        </a>@endcan

        @can('acessos.gerenciar')<a href="{{ route('acessos.index') }}" @if(request()->routeIs('acessos.*')) aria-current="page" @endif>Perfis e permissões</a>@endcan @can('caixa.finalizar')<a href="{{ route('caixa.vendas') }}" @if(request()->routeIs('caixa.vendas*')) aria-current="page" @endif>Vendas</a>@endcan @can('compras.gerenciar')<a href="{{ route('compras.index') }}" @if(request()->routeIs('compras.*')) aria-current="page" @endif>Compras</a>@endcan
@can('relatorios.consultar')<a href="{{ route('relatorios.index') }}" @if(request()->routeIs('relatorios.*')) aria-current="page" @endif>Relatórios</a>@endcan
@canany(['clientes.gerenciar','fornecedores.gerenciar','categorias.gerenciar','caixa.gerenciar'])<a href="{{ route('cadastros.home') }}" @if(request()->routeIs('cadastros.*')) aria-current="page" @endif>Cadastros</a>@endcanany
<span class="ambiente">{{ in_array($module, ['produtos', 'estoque']) ? 'Produtos e estoque' : 'Pagamento simulado' }}</span>
    </nav>

    <main id="conteudo">
        <div class="titulo-pagina">
            <div>
                <span class="caminho">Mercado Preço Bom / {{ ucfirst($module) }}</span>
                <h1>{{ $title }}</h1>
            </div>
        </div>

        @if($module === 'acessos')<nav class="abas" aria-label="Telas de acesso">@can('acessos.gerenciar')<a href="{{ route('acessos.index') }}">Permissões</a><a href="{{ route('usuarios.index') }}">Usuários</a>@endcan<a href="{{ route('account.password') }}">Minha senha</a></nav>@endif
        @if(in_array($module, ['produtos', 'estoque', 'caixa']) && !request()->routeIs('caixa.vendas*'))
            <nav class="abas" aria-label="Telas do módulo">
                @if($module === 'produtos')
                    @can('produtos.cadastrar')<a
                        href="{{ route('produtos.cadastrar') }}"
                        @if(request()->routeIs('produtos.cadastrar')) aria-current="page" @endif
                    >
                        Cadastrar
                    </a>@endcan

                    @canany(['produtos.consultar', 'produtos.editar', 'produtos.inativar'])
                    <a href="{{ route('produtos.consultar') }}"
                        @if(request()->routeIs('produtos.consultar', 'produtos.editar', 'produtos.inativar')) aria-current="page" @endif>
                        Gerenciar produtos
                    </a>
                    @endcanany
                @elseif($module === 'estoque')
                    @can('estoque.entrada')<a
                        href="{{ route('estoque.entrada') }}"
                        @if($asset === 'estoque/entrada') aria-current="page" @endif
                    >
                        Entrada
                    </a>@endcan

                    @canany(['estoque.saida', 'estoque.ajustar'])<a
                        href="{{ route('estoque.saida') }}"
                        @if($asset === 'estoque/saida') aria-current="page" @endif
                    >
                        Ajuste de estoque
                    </a>@endcanany

                    @can('estoque.consultar')<a
                        href="{{ route('estoque.consultar') }}"
                        @if(request()->routeIs('estoque.consultar')) aria-current="page" @endif
                    >
                        Consultar saldos
                    </a>@endcan

                    @can('estoque.consultar')<a href="{{ route('estoque.lotes') }}" @if(request()->routeIs('estoque.lotes')) aria-current="page" @endif>Lotes e validade</a>@endcan
                @else
                    <a href="{{ route('caixa.sessoes') }}" @if(request()->routeIs('caixa.sessoes')) aria-current="page" @endif>Abertura e fechamento</a>
                    @can('caixa.finalizar')<a
                        href="{{ route('caixa.finalizar') }}"
                        @if($asset === 'caixa/finalizar') aria-current="page" @endif
                    >
                        Nova venda
                    </a>@endcan

                    @can('caixa.cancelar')<a
                        href="{{ route('caixa.cancelar') }}"
                        @if($asset === 'caixa/cancelar') aria-current="page" @endif
                    >
                        Cancelar item
                    </a>@endcan

                    @can('caixa.pagamento')<a
                        href="{{ route('caixa.pagamento') }}"
                        @if($asset === 'caixa/pagamento') aria-current="page" @endif
                    >
                        Pagamento
                    </a>@endcan

                    @can('caixa.estornar')<a
                        href="{{ route('caixa.estornar') }}"
                        @if($asset === 'caixa/estornar') aria-current="page" @endif
                    >
                        Estornar venda
                    </a>@endcan
                @endif
            </nav>
        @endif

        <noscript>
            <p class="aviso erro">
                Ative o JavaScript para utilizar todos os recursos do sistema.
            </p>
        </noscript>

        @yield('content')

        <p id="mensagem" class="aviso" role="status" hidden></p>

        <footer class="rodape">
            <span>Mercado Preço Bom · Gestão de supermercado</span>
            <span>Vendas e estoque registrados. Pagamentos simulados, sem cobrança externa.</span>
        </footer>
    </main>

    <dialog id="confirmacao" aria-labelledby="confirmacao-titulo">
        <form method="dialog">
            <div class="dialogo-cabecalho">
                <h2 id="confirmacao-titulo">Conferir operação</h2>

                <button value="cancelar" class="fechar" aria-label="Fechar">
                    ×
                </button>
            </div>

            <p id="confirmacao-texto"></p>

            <p class="ajuda">
                Confira os dados antes de confirmar a operação.
            </p>

            <div class="acoes">
                <button value="cancelar" class="botao secundario">
                    Voltar
                </button>

                <button id="confirmar" value="confirmar" class="botao">
                    Confirmar operação
                </button>
            </div>
        </form>
    </dialog>
</body>

</html>