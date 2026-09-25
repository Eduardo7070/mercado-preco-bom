@extends('layouts.mercado')

@section('content')
<div class="colunas inativacao">
    <section class="painel">
        <div class="painel-titulo">
            <h2>Inativação de produto</h2>
            <span class="etiqueta">Sem exclusão de histórico</span>
        </div>
        <form id="formulario" class="painel-corpo">
            <label class="campo">Produto<select id="produto"></select></label>
            <dl class="resumo">
                <div>
                    <dt>Situação atual</dt>
                    <dd id="situacao"></dd>
                </div>
                <div>
                    <dt>Saldo preservado</dt>
                    <dd id="saldo"></dd>
                </div>
            </dl>
            <label class="campo"
                >Motivo da inativação *<textarea
                    id="motivo"
                    required
                    placeholder="Ex.: produto fora de linha"
                ></textarea>
            </label>
            <div class="acoes">
                <button id="revisar" class="botao perigo">
                    Revisar inativação
                </button>
            </div>
        </form>
    </section>
    <aside class="painel">
        <div class="painel-titulo"><h2>O que muda?</h2></div>
        <div class="painel-corpo">
            <ul class="regras">
                <li>
                    O produto deixa de estar disponível para novas entradas e
                    vendas.
                </li>
                <li>
                    O saldo e o histórico permanecem disponíveis para consulta.
                </li>
                <li>A inativação exige confirmação e justificativa.</li>
            </ul>
            <p class="ajuda">
                A situação do produto não será alterada nesta demonstração.
            </p>
        </div>
    </aside>
</div>
@endsection

@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H01.003</span><h2 id="historia-titulo">Inativar produto</h2></div>
<p class="historia-ator"><strong>Ator:</strong> Responsável pelo cadastro</p>
<p class="historia-descricao">Como responsável pelo cadastro, quero inativar produtos que não devem mais ser comercializados, para impedir novas operações sem apagar seus registros.</p>
<details class="historia-criterios"><summary>Critérios de aceitação</summary>
<ul>
    <li>Permitir a inativação de um produto ativo, com motivo e confirmação explícita.</li>
    <li>Preservar o saldo e o histórico, sem excluir o produto.</li>
    <li>Impedir novas entradas, reposições e vendas do produto inativo.</li>
    <li>Informar quando o produto selecionado já estiver inativo.</li>
</ul>
<p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection
