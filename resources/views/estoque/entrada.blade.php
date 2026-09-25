@extends('layouts.mercado')

@section('content')
<div class="colunas movimentacao">
    <section class="painel">
        <div class="painel-titulo"><h2>Recebimento de mercadoria</h2></div>
        <form id="formulario" class="painel-corpo">
            <div class="campos">
                <label class="campo inteiro"
                    >Produto *<select id="produto"></select></label
                ><label class="campo"
                    >Local do estoque<select id="local">
                        <option value="deposito">Depósito</option>
                        <option value="gondola">Gôndola</option>
                    </select></label
                ><label class="campo"
                    >Quantidade *<input
                        id="quantidade"
                        type="number"
                        required
                        min="1"
                        step="1"
                        value="10" /></label
                ><label class="campo"
                    >Documento de origem<input
                        id="documento"
                        type="text"
                        placeholder="Ex.: NF 012345"
                /></label>
                <div id="controle-lote" class="campos inteiro">
                    <label class="campo"
                        >Lote *<input
                            id="lote"
                            type="text"
                            required
                            placeholder="Ex.: LT-0926" /></label
                    ><label class="campo"
                        >Validade *<input id="validade" type="date" required
                    /></label>
                </div>
            </div>
            <div class="acoes">
                <button class="botao">Revisar entrada</button>
            </div>
        </form>
    </section>
    <aside class="painel">
        <div class="painel-titulo">
            <h2>Prévia do saldo</h2>
            <span class="etiqueta">Simulação</span>
        </div>
        <div class="painel-corpo">
            <span class="legenda">Saldo após a operação</span
            ><strong id="saldo-novo" class="saldo-grande">—</strong>
            <dl class="resumo">
                <div>
                    <dt>Saldo atual</dt>
                    <dd id="saldo-atual"></dd>
                </div>
                <div>
                    <dt>Variação</dt>
                    <dd id="variacao"></dd>
                </div>
                <div>
                    <dt>Responsável</dt>
                    <dd>Mariana Oliveira</dd>
                </div>
            </dl>
            <p class="ajuda">
                O saldo é calculado para conferência. Nenhuma movimentação será
                registrada.
            </p>
        </div>
    </aside>
</div>
@endsection

@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H02.001</span><h2 id="historia-titulo">Registrar entrada de mercadoria</h2></div>
<p class="historia-ator"><strong>Ator:</strong> Responsável pelo estoque</p>
<p class="historia-descricao">Como responsável pelo estoque, quero registrar a entrada de mercadorias, para atualizar a quantidade disponível e identificar sua origem.</p>
<details class="historia-criterios"><summary>Critérios de aceitação</summary>
<ul>
    <li>Selecionar um produto cadastrado e ativo e informar quantidade maior que zero.</li>
    <li>Apresentar documento de origem e exigir lote e validade quando o produto tiver esse controle.</li>
    <li>Apresentar o saldo anterior e o saldo previsto após a entrada.</li>
    <li>Identificar responsável, data e hora e evitar o registro duplicado da operação.</li>
</ul>
<p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection
