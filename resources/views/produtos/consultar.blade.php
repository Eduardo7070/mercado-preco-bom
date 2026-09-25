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
        hidden
    >
        <h3 id="nome-detalhe"></h3>
        <dl id="dados-detalhe" class="resumo"></dl>
    </section>
</section>
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
