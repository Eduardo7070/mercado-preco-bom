@extends('layouts.mercado')

@section('content')
<section class="painel consulta">
    <div class="painel-titulo">
        <h2>Saldos por local</h2>
        <span id="contador" class="etiqueta"></span>
    </div>
    <div class="filtros">
        <label class="campo"
            >Código, código de barras ou nome<input
                id="busca"
                type="search"
                placeholder="Digite para pesquisar..." /></label
        ><label class="campo"
            >Situação<select id="filtro">
                <option value="todos">Todos</option>
                <option value="ativos">Ativos</option>
                <option value="inativos">Inativos</option>
            </select></label
        >
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
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="resultados"></tbody>
        </table>
    </div>
    <p id="vazio" class="vazio" hidden>
        Nenhum produto encontrado. Tente outro código ou nome.
    </p>
</section>

<dialog id="modal-detalhes" class="modal-consulta" aria-labelledby="titulo-detalhes">
    <form method="dialog">
        <div class="dialogo-cabecalho">
            <h2 id="titulo-detalhes">Detalhes do produto</h2>
            <button value="cancelar" class="fechar" aria-label="Fechar">×</button>
        </div>
        <dl id="dados-detalhes" class="resumo modal-resumo"></dl>
        <div class="acoes">
            <button value="cancelar" class="botao secundario">Fechar</button>
        </div>
    </form>
</dialog>

<dialog id="modal-editar" class="modal-consulta" aria-labelledby="titulo-editar">
    <form method="dialog" id="form-editar">
        <div class="dialogo-cabecalho">
            <h2 id="titulo-editar">Editar saldo do produto</h2>
            <button value="cancelar" class="fechar" aria-label="Fechar">×</button>
        </div>
        <div class="campos">
            <label class="campo">Código interno<input id="editar-codigo" readonly /></label>
            <label class="campo">Produto<input id="editar-nome" readonly /></label>
            <label class="campo">Saldo depósito<input id="editar-deposito" type="number" min="0" step="1" /></label>
            <label class="campo">Saldo gôndola<input id="editar-gondola" type="number" min="0" step="1" /></label>
            <label class="campo inteiro">Última movimentação<input id="editar-movimentacao" readonly /></label>
        </div>
        <p class="ajuda">Alteração apenas demonstrativa. Os saldos da tabela não serão gravados.</p>
        <div class="acoes">
            <button value="cancelar" class="botao secundario">Cancelar</button>
            <button value="confirmar" class="botao">Salvar simulação</button>
        </div>
    </form>
</dialog>

<dialog id="modal-deletar" class="modal-consulta" aria-labelledby="titulo-deletar">
    <form method="dialog" id="form-deletar">
        <div class="dialogo-cabecalho">
            <h2 id="titulo-deletar">Deletar registro de estoque</h2>
            <button value="cancelar" class="fechar" aria-label="Fechar">×</button>
        </div>
        <p id="texto-deletar" class="texto-modal"></p>
        <p class="ajuda">Esta é uma ação simulada. Nenhum registro de estoque será removido.</p>
        <div class="acoes">
            <button value="cancelar" class="botao secundario">Cancelar</button>
            <button value="confirmar" class="botao perigo">Confirmar exclusão</button>
        </div>
    </form>
</dialog>
@endsection

@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H02.003</span><h2 id="historia-titulo">Consultar saldo de estoque</h2></div>
<p class="historia-ator"><strong>Ator:</strong> Responsável pelo estoque</p>
<p class="historia-descricao">Como responsável pelo estoque, quero consultar os saldos por produto e local, para acompanhar a disponibilidade no depósito e na gôndola.</p>
<details class="historia-criterios"><summary>Critérios de aceitação</summary>
<ul>
    <li>Pesquisar o produto e apresentar separadamente os saldos de depósito, gôndola e total.</li>
    <li>Exibir produtos com saldo zero e a última movimentação.</li>
    <li>Considerar apenas movimentações válidas, excluindo as canceladas ou pendentes.</li>
    <li>A consulta não deve modificar quantidades.</li>
</ul>
<p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection

