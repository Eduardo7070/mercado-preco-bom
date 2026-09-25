@extends('layouts.mercado')

@section('content')
<div class="colunas movimentacao">
    <section class="painel">
        <div class="painel-titulo"><h2>Conferência e ajuste de saldo</h2></div>
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
                    >Tipo de ajuste<select id="tipo">
                        <option value="contagem">Contagem física</option>
                        <option value="avaria">Avaria</option>
                    </select></label
                ><label class="campo"
                    >Quantidade contada / avariada *<input
                        id="quantidade"
                        type="number"
                        required
                        min="0"
                        step="1"
                        value="10" /></label
                ><label class="campo inteiro"
                    >Justificativa do ajuste *<textarea
                        id="motivo"
                        required
                        placeholder="Descreva o motivo da movimentação"
                    ></textarea>
                </label>
            </div>
            <div class="acoes">
                <button class="botao">Revisar ajuste</button>
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
<div class="historia-cabecalho"><span class="historia-codigo">H02.004</span><h2 id="historia-titulo">Ajustar estoque por contagem ou avaria</h2></div>
<p class="historia-ator"><strong>Ator:</strong> Responsável pelo estoque</p>
<p class="historia-descricao">Como responsável pelo estoque, quero ajustar o saldo após contagem física ou identificação de avarias, para refletir a quantidade real disponível.</p>
<details class="historia-criterios"><summary>Critérios de aceitação</summary>
<ul>
    <li>Selecionar produto, local e tipo de ajuste: contagem física ou avaria.</li>
    <li>Exigir quantidade válida e justificativa do ajuste.</li>
    <li>Exibir saldo atual, diferença e saldo previsto; impedir saldo negativo.</li>
    <li>A avaria deve reduzir o saldo; identificar responsável e data e hora do ajuste.</li>
</ul>
<p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection
