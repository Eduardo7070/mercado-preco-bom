@extends('layouts.mercado')

@section('content')
<div class="colunas movimentacao">
    <section class="painel">
        <div class="painel-titulo"><h2>Saída não vinculada a venda</h2></div>
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
                ><label class="campo inteiro"
                    >Motivo da saída *<textarea
                        id="motivo"
                        required
                        placeholder="Descreva o motivo da movimentação"
                    ></textarea>
                </label>
            </div>
            <div class="acoes">
                <button class="botao">Revisar saída</button>
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
<div class="historia-cabecalho"><span class="historia-codigo">H02.002</span><h2 id="historia-titulo">Registrar saída de mercadoria</h2></div>
<p class="historia-ator"><strong>Ator:</strong> Responsável autorizado pelo estoque</p>
<p class="historia-descricao">Como responsável autorizado pelo estoque, quero registrar saídas não relacionadas a vendas, para controlar devoluções e outras retiradas justificadas.</p>
<details class="historia-criterios"><summary>Critérios de aceitação</summary>
<ul>
    <li>Informar o produto, o local do estoque, a quantidade e o motivo obrigatório da saída.</li>
    <li>A quantidade deve ser maior que zero e não pode superar o saldo disponível.</li>
    <li>Apresentar o saldo anterior e o saldo previsto após a saída.</li>
    <li>Identificar o responsável autorizado e a data e hora da movimentação.</li>
</ul>
<p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection
