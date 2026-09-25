@extends('layouts.mercado')

@section('content')
<section class="painel consulta">
    <div class="painel-titulo">
        <h2>Saldos por local</h2>
        <span id="contador" class="etiqueta"></span>
    </div>
    <div class="filtros">
        <label class="campo">Código, código de barras ou nome<input
                id="busca"
                type="search"
                placeholder="Digite para pesquisar..." /></label><label class="campo">Situação<select id="filtro">
                <option value="todos">Todos</option>
                <option value="ativos">Ativos</option>
                <option value="inativos">Inativos</option>
            </select></label>
    </div>
    <div class="tabela-scroll">
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th class="numerico">Depósito</th>
                    <th class="numerico">Gôndola</th>
                    <th class="numerico">Total</th>
                    <th>Situação</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody id="resultados"></tbody>
        </table>
    </div>
    <p id="vazio" class="vazio" hidden>
        Nenhum produto encontrado. Tente outro código ou nome.
    </p>
    <section
        id="detalhes"
        class="detalhes"
        aria-label="Detalhes do produto"
        hidden>
        <h3 id="nome-detalhe"></h3>
        <dl id="dados-detalhe" class="resumo"></dl>
    </section>
</section>
@endsection

@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H02.003</span>
    <h2 id="historia-titulo">Consultar saldo de estoque</h2>
</div>
<p class="historia-ator"><strong>Ator:</strong> Responsável pelo estoque</p>
<p class="historia-descricao">Como responsável pelo estoque, quero consultar os saldos por produto e local, para acompanhar a disponibilidade no depósito e na gôndola.</p>
<details class="historia-criterios">
    <summary>Critérios de aceitação</summary>
    <ul>
        <li>Pesquisar o produto e apresentar separadamente os saldos de depósito, gôndola e total.</li>
        <li>Exibir produtos com saldo zero e a última movimentação.</li>
        <li>Considerar apenas movimentações válidas, excluindo as canceladas ou pendentes.</li>
        <li>A consulta não deve modificar quantidades.</li>
    </ul>
    <p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection