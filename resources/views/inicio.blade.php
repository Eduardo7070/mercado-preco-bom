@extends('layouts.mercado')

@section('content')
<section class="boas-vindas">
    <div>
        <span class="etiqueta">LOJA PRINCIPAL</span>
        <h2>Bom dia, Mariana.</h2>
        <p>Produtos organizados, estoque em dia e caixa pronto para atender.</p>
    </div>
    <a class="botao" href="{{ route('caixa.finalizar') }}"
        >Abrir frente de caixa <span aria-hidden="true">→</span></a
    >
</section>
<div class="indicadores">
    <div>
        <span>Vendas de hoje</span><strong>R$ 2.480,00</strong
        ><small>32 vendas · Valores de exemplo</small>
    </div>
    <div>
        <span>Produtos cadastrados</span><strong>5</strong
        ><small>4 ativos · 1 inativo</small>
    </div>
    <div>
        <span>Unidades em estoque</span><strong>94</strong
        ><small>Depósito e gôndola</small>
    </div>
    <div>
        <span>Produtos sem saldo</span><strong>1</strong
        ><small>Café tradicional 500 g</small>
    </div>
</div>
<div class="modulos">
    <section class="painel">
        <div class="painel-titulo">
            <h2>01 / Produtos</h2>
            <span class="etiqueta">4 telas</span>
        </div>
        <div class="painel-corpo">
            <p>Cadastro, preços e situação dos produtos.</p>
            <a href="{{ route('produtos.cadastrar') }}"
                >Cadastrar produto <span>H01.001 →</span></a
            ><a href="{{ route('produtos.editar') }}"
                >Editar produto <span>H01.002 →</span></a
            ><a href="{{ route('produtos.inativar') }}"
                >Inativar produto <span>H01.003 →</span></a
            ><a href="{{ route('produtos.consultar') }}"
                >Consultar produtos <span>H01.004 →</span></a
            >
        </div>
    </section>
    <section class="painel">
        <div class="painel-titulo">
            <h2>02 / Estoque</h2>
            <span class="etiqueta">4 telas</span>
        </div>
        <div class="painel-corpo">
            <p>Controle de entradas, saídas e saldos.</p>
            <a href="{{ route('estoque.entrada') }}"
                >Entrada de mercadoria <span>H02.001 →</span></a
            ><a href="{{ route('estoque.saida') }}"
                >Saída de mercadoria <span>H02.002 →</span></a
            ><a href="{{ route('estoque.consultar') }}"
                >Consultar saldos <span>H02.003 →</span></a
            ><a href="{{ route('estoque.ajustar') }}"
                >Ajustar estoque <span>H02.004 →</span></a
            >
        </div>
    </section>
    <section class="painel">
        <div class="painel-titulo">
            <h2>03 / Frente de caixa</h2>
            <span class="etiqueta">4 telas</span>
        </div>
        <div class="painel-corpo">
            <p>Venda, recebimento e conferência do cupom.</p>
            <a href="{{ route('caixa.finalizar') }}"
                >Finalizar venda / PDV <span>H03.001 →</span></a
            ><a href="{{ route('caixa.cancelar') }}"
                >Cancelar item <span>H03.002 →</span></a
            ><a href="{{ route('caixa.pagamento') }}"
                >Selecionar pagamento <span>H03.003 →</span></a
            ><a href="{{ route('caixa.estornar') }}"
                >Estornar venda <span>H03.004 →</span></a
            >
        </div>
    </section>
</div>
<section class="painel apresentacao">
    <div>
        <h2>Uma tela para cada história de usuário</h2>
        <p class="ajuda">
            Use os menus para apresentar os campos, a navegação e as
            confirmações. Os dados são fictícios e as operações não são
            gravadas.
        </p>
    </div>
    <button
        id="orientacoes"
        type="button"
        class="botao secundario"
        aria-expanded="false"
        aria-controls="guia"
    >
        Como apresentar
    </button>
    <ol id="guia" hidden>
        <li>Escolha a história no módulo.</li>
        <li>Preencha os campos e demonstre a validação.</li>
        <li>Confira a simulação e capture a tela.</li>
        <li>Recarregue para restaurar os exemplos.</li>
    </ol>
</section>
@endsection
