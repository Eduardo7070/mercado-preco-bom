@extends('layouts.mercado')

@section('content')
<section class="pdv" aria-label="Terminal de caixa">
    <header class="pdv-barra">
        <strong>PREÇO BOM · PDV</strong><span>Caixa 01 &nbsp; | &nbsp; Operadora: Mariana Oliveira &nbsp; |
            &nbsp; 25/09/2026</span>
    </header>
    <div id="situacao" class="pdv-situacao">CAIXA ABERTO</div>
    <div class="pdv-corpo">
        <section class="pdv-leitura">
            <div class="pdv-logo">
                <svg
                    viewBox="0 0 120 80"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="4"
                    aria-hidden="true">
                    <path
                        d="M10 8h14l13 43h56l13-32H29M39 62h53M44 20l6 31M66 20v31M89 20l-6 31M33 35h66" />
                    <circle cx="43" cy="72" r="5" />
                    <circle cx="88" cy="72" r="5" />
                </svg><strong>Mercado Preço Bom</strong><small>ECONOMIA TODOS OS DIAS</small>
            </div>
            <form id="adicionar">
                <label class="pdv-campo"><span>Código de barras / código interno</span><input
                        id="codigo"
                        autocomplete="off"
                        placeholder="Leia ou digite"
                        value="1001"
                        required /></label>
                <div class="pdv-dupla">
                    <label class="pdv-campo"><span>Quantidade</span><input
                            id="quantidade"
                            type="number"
                            min="1"
                            step="1"
                            value="1"
                            required /></label>
                    <div class="pdv-campo">
                        <span>Valor unitário</span><strong id="unitario">R$ 25,00</strong>
                    </div>
                </div>
                <div class="pdv-campo">
                    <span>Total do item</span><strong id="total-item">R$ 25,00</strong>
                </div>
                <button class="botao" id="incluir">
                    Incluir produto <kbd>Enter</kbd>
                </button>
            </form>
            <p class="ajuda">Exemplos: 1001 — arroz · 1002 — leite.</p>
            <label class="pdv-contexto">Situação do caixa<select id="caixa">
                    <option>Aberto</option>
                    <option>Fechado</option>
                </select></label>
        </section>
        <section class="pdv-cupom">
            <h2>LISTA DE PRODUTOS</h2>
            <div class="cupom-info">
                <span>CUPOM #02459</span><span id="itens">2 itens · 8 unidades</span>
            </div>
            <div class="tabela-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Nº</th>
                            <th>Código / descrição</th>
                            <th>Qtd.</th>
                            <th>Vl. unit.</th>
                            <th>Total</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody id="cupom"></tbody>
                </table>
            </div>
            <div class="cupom-fim">FIM DOS ITENS · CUPOM DEMONSTRATIVO</div>
            <div id="produto-atual" class="pdv-item-atual">
                AGUARDANDO PRÓXIMO PRODUTO
            </div>
        </section>
        <form id="recebimento" class="pdv-pagamento">
            <div class="pdv-numero">
                <span>Subtotal / total a pagar</span><strong id="total">R$ 80,00</strong>
            </div>
            <fieldset>
                <legend>Forma de pagamento</legend>
                <label><input
                        type="radio"
                        name="metodo"
                        value="Dinheiro"
                        checked />Dinheiro</label><label><input
                        type="radio"
                        name="metodo"
                        value="Débito" />Débito</label><label><input
                        type="radio"
                        name="metodo"
                        value="Crédito" />Crédito</label><label><input type="radio" name="metodo" value="Pix" />Pix</label>
            </fieldset>
            <label class="pdv-campo" id="campo-recebido"><span>Total recebido (R$)</span><input
                    id="recebido"
                    type="number"
                    min="0"
                    step="0.01"
                    value="100"
                    required /></label><label id="campo-aprovado" class="check" hidden><input id="aprovado" type="checkbox" /> Recebimento aprovado
                (simulação)</label>
            <div class="pdv-numero troco">
                <span id="troco-label">Troco</span><strong id="troco">R$ 20,00</strong>
            </div>
            <button id="finalizar" class="botao">
                Finalizar venda <kbd>F4</kbd>
            </button>
        </form>
    </div>
    <footer class="pdv-rodape">
        <span><kbd>F2</kbd> Código do produto &nbsp; <kbd>F4</kbd> Conferir
            pagamento</span><span>Ambiente de demonstração · Nenhuma cobrança ou baixa de
            estoque</span>
    </footer>
</section>
@endsection

@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H03.001</span>
    <h2 id="historia-titulo">Finalizar venda com baixa automática de estoque</h2>
</div>
<p class="historia-ator"><strong>Ator:</strong> Operador de caixa</p>
<p class="historia-descricao">Como operador de caixa, quero finalizar uma venda com pagamento confirmado e baixa dos itens vendidos, para concluir o atendimento e manter o estoque atualizado.</p>
<details class="historia-criterios">
    <summary>Critérios de aceitação</summary>
    <ul>
        <li>Exigir operador identificado, caixa aberto e ao menos um item válido na venda.</li>
        <li>Validar o produto e a quantidade disponível na gôndola antes da inclusão.</li>
        <li>Permitir dinheiro, débito, crédito ou Pix e exigir a confirmação do recebimento.</li>
        <li>Calcular o troco em dinheiro e excluir itens cancelados do total.</li>
        <li>Na operação real, registrar a venda e baixar somente os itens válidos uma única vez.</li>
    </ul>
    <p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection