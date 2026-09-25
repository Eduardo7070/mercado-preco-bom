<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $title }} | Mercado Preço Bom</title>
        <link rel="stylesheet" href="{{ asset('css/comum.css') }}" />
        <link rel="stylesheet" href="{{ asset('css/'.$asset.'.css') }}" />
        <script defer src="{{ asset('js/comum.js') }}"></script>
        <script defer src="{{ asset('js/'.$asset.'.js') }}"></script>
    </head>
    <body class="pagina-{{ $module }}">
        <a class="skip" href="#conteudo">Pular para o conteúdo</a>
        <header class="cabecalho">
            <a class="marca" href="{{ route('inicio') }}"
                ><span class="marca-simbolo" aria-hidden="true">✓</span
                ><span
                    ><small>MERCADO</small><strong>Preço Bom</strong></span
                ></a
            >
            <div class="cabecalho-info">
                <strong>Sistema de gestão</strong
                ><span>Loja 01 · Unidade principal</span>
            </div>
            <div class="operador">
                <span class="avatar">MO</span>
                <div>
                    <strong>Mariana Oliveira</strong
                    ><span>Operadora · Demonstração</span>
                </div>
            </div>
        </header>
        <nav class="navegacao" aria-label="Menu principal">
            <a
                href="{{ route('inicio') }}"
                @if($module === 'inicio') aria-current="page" @endif
                >Início</a
            ><a
                href="{{ route('produtos.consultar') }}"
                @if($module === 'produtos') aria-current="page" @endif
                >Produtos</a
            ><a
                href="{{ route('estoque.consultar') }}"
                @if($module === 'estoque') aria-current="page" @endif
                >Estoque</a
            ><a
                href="{{ route('caixa.finalizar') }}"
                @if($module === 'caixa') aria-current="page" @endif
                >Frente de caixa</a
            ><span class="ambiente">PROTÓTIPO · SEM GRAVAÇÃO</span>
        </nav>
        <main id="conteudo">
            <div class="titulo-pagina">
                <div>
                    <span class="caminho"
                        >Mercado Preço Bom / {{ ucfirst($module) }}</span
                    >
                    <h1>{{ $title }}</h1>
                </div>
                <span class="historia">{{ $story }}</span>
            </div>
            @if($module !== 'inicio')
            <nav class="abas" aria-label="Telas do módulo">
                @if($module === 'produtos')
                <a
                    href="{{ route('produtos.cadastrar') }}"
                    @if($asset === 'produtos/cadastrar') aria-current="page" @endif
                    >Cadastrar</a
                ><a
                    href="{{ route('produtos.editar') }}"
                    @if($asset === 'produtos/editar') aria-current="page" @endif
                    >Editar</a
                ><a
                    href="{{ route('produtos.inativar') }}"
                    @if($asset === 'produtos/inativar') aria-current="page" @endif
                    >Inativar</a
                ><a
                    href="{{ route('produtos.consultar') }}"
                    @if($asset === 'produtos/consultar') aria-current="page" @endif
                    >Consultar</a
                >
                @elseif($module === 'estoque')
                <a
                    href="{{ route('estoque.entrada') }}"
                    @if($asset === 'estoque/entrada') aria-current="page" @endif
                    >Entrada</a
                ><a
                    href="{{ route('estoque.saida') }}"
                    @if($asset === 'estoque/saida') aria-current="page" @endif
                    >Saída</a
                ><a
                    href="{{ route('estoque.consultar') }}"
                    @if($asset === 'estoque/consultar') aria-current="page" @endif
                    >Consultar saldos</a
                ><a
                    href="{{ route('estoque.ajustar') }}"
                    @if($asset === 'estoque/ajustar') aria-current="page" @endif
                    >Ajustar estoque</a
                >
                @else
                <a
                    href="{{ route('caixa.finalizar') }}"
                    @if($asset === 'caixa/finalizar') aria-current="page" @endif
                    >Venda / PDV</a
                ><a
                    href="{{ route('caixa.cancelar') }}"
                    @if($asset === 'caixa/cancelar') aria-current="page" @endif
                    >Cancelar item</a
                ><a
                    href="{{ route('caixa.pagamento') }}"
                    @if($asset === 'caixa/pagamento') aria-current="page" @endif
                    >Pagamento</a
                ><a
                    href="{{ route('caixa.estornar') }}"
                    @if($asset === 'caixa/estornar') aria-current="page" @endif
                    >Estornar venda</a
                >
                @endif
            </nav>
            @endif
            <noscript
                ><p class="aviso erro">
                    Ative o JavaScript para utilizar as simulações.
                </p></noscript
            >
            @hasSection('historia')
                <section class="historia-usuario" aria-labelledby="historia-titulo">
                    <span class="historia-legenda">História de usuário</span>
                    @yield('historia')
                </section>
            @endif
            @yield('content')
            <p id="mensagem" class="aviso" role="status" hidden></p>
            <footer class="rodape">
                <span>Mercado Preço Bom · Protótipos de interface</span
                ><span>Dados ilustrativos. Nenhuma operação é gravada.</span>
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
                    Somente demonstração: nenhum dado será gravado.
                </p>
                <div class="acoes">
                    <button value="cancelar" class="botao secundario">
                        Voltar</button
                    ><button id="confirmar" value="confirmar" class="botao">
                        Confirmar simulação
                    </button>
                </div>
            </form>
        </dialog>
    </body>
</html>
