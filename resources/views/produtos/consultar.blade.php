@extends('layouts.mercado')

@section('content')
<section class="painel consulta">
    <div class="painel-titulo">
        <h2>Lista de produtos</h2>
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
                    <th>Unidade</th>
                    <th class="numerico">Preço</th>
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
            <h2 id="titulo-editar">Editar produto</h2>
            <button value="cancelar" class="fechar" aria-label="Fechar">×</button>
        </div>
        <div class="campos">
            <label class="campo">Código interno<input id="editar-codigo" readonly /></label>
            <label class="campo">Código de barras<input id="editar-barras" /></label>
            <label class="campo inteiro">Descrição<input id="editar-nome" /></label>
            <label class="campo">Categoria<input id="editar-categoria" /></label>
            <label class="campo">Unidade<input id="editar-unidade" /></label>
            <label class="campo">Preço<input id="editar-preco" type="number" min="0" step="0.01" /></label>
        </div>
        <p class="ajuda">Alteração apenas demonstrativa. Os dados da tabela não serão gravados.</p>
        <div class="acoes">
            <button value="cancelar" class="botao secundario">Cancelar</button>
            <button value="confirmar" class="botao">Salvar simulação</button>
        </div>
    </form>
</dialog>

<dialog id="modal-deletar" class="modal-consulta" aria-labelledby="titulo-deletar">
    <form method="dialog" id="form-deletar">
        <div class="dialogo-cabecalho">
            <h2 id="titulo-deletar">Deletar produto</h2>
            <button value="cancelar" class="fechar" aria-label="Fechar">×</button>
        </div>
        <p id="texto-deletar" class="texto-modal"></p>
        <p class="ajuda">Esta é uma ação simulada. Nenhum produto será removido.</p>
        <div class="acoes">
            <button value="cancelar" class="botao secundario">Cancelar</button>
            <button value="confirmar" class="botao perigo">Confirmar exclusão</button>
        </div>
    </form>
</dialog>
@endsection

@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H01.004</span><h2 id="historia-titulo">Consultar produto por código ou nome</h2></div>
<p class="historia-ator"><strong>Ator:</strong> Usuário do sistema</p>
<p class="historia-descricao">Como usuário do sistema, quero consultar produtos por código, código de barras ou nome, para encontrar suas informações comerciais e sua situação.</p>
<details class="historia-criterios"><summary>Critérios de aceitação</summary>
<ul>
    <li>Pesquisar por código ou código de barras exato e por parte do nome.</li>
    <li>Apresentar descrição, categoria, unidade, preço e situação do produto.</li>
    <li>Permitir filtrar produtos ativos e inativos.</li>
    <li>Mostrar mensagem quando não houver resultados; a consulta não altera dados.</li>
</ul>
<p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection
