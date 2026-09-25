@extends('layouts.mercado')

@section('content')
<section class="pdv" aria-label="Terminal de caixa">
    <header class="pdv-barra">
        <strong>PREÇO BOM · PDV</strong
        ><span
            >Caixa 01 &nbsp; | &nbsp; Operadora: Mariana Oliveira &nbsp; |
            &nbsp; 25/09/2026</span
        >
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
                    aria-hidden="true"
                >
                    <path
                        d="M10 8h14l13 43h56l13-32H29M39 62h53M44 20l6 31M66 20v31M89 20l-6 31M33 35h66"
                    />
                    <circle cx="43" cy="72" r="5" />
                    <circle cx="88" cy="72" r="5" /></svg
                ><strong>Mercado Preço Bom</strong
                ><small>ECONOMIA TODOS OS DIAS</small>
            </div>
            <p class="pdv-observacao">
                Selecione Cancelar na linha do cupom. O item permanece
                identificado e seu valor é retirado do total.
            </p>
            <label class="pdv-contexto"
                >Situação do caixa<select id="caixa">
                    <option>Aberto</option>
                    <option>Fechado</option>
                </select></label
            >
        </section>
        <section class="pdv-cupom">
            <h2>LISTA DE PRODUTOS</h2>
            <div class="cupom-info">
                <span>CUPOM #02459</span
                ><span id="itens">2 itens · 8 unidades</span>
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
                <span>Subtotal / total a pagar</span
                ><strong id="total">R$ 80,00</strong>
            </div>
            <p class="pdv-observacao">
                Cancele apenas itens de uma venda aberta. Para uma venda
                concluída, utilize a tela Estornar venda.
            </p>
            <button id="concluida" type="button" class="botao secundario">
                Exibir venda já concluída
            </button>
        </form>
    </div>
    <footer class="pdv-rodape">
        <span
            ><kbd>F2</kbd> Código do produto &nbsp; <kbd>F4</kbd> Conferir
            pagamento</span
        ><span
            >Ambiente de demonstração · Nenhuma cobrança ou baixa de
            estoque</span
        >
    </footer>
</section>
@endsection

@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H03.002</span><h2 id="historia-titulo">Cancelar item da venda</h2></div>
<p class="historia-ator"><strong>Ator:</strong> Operador de caixa</p>
<p class="historia-descricao">Como operador de caixa, quero cancelar um item de uma venda em andamento, para corrigir o cupom antes de finalizar o atendimento.</p>
<details class="historia-criterios"><summary>Critérios de aceitação</summary>
<ul>
    <li>Permitir escolher e confirmar o cancelamento de um item de uma venda aberta.</li>
    <li>Manter o item visível e identificado como cancelado no cupom.</li>
    <li>Recalcular o total sem o valor do item e impedir cancelamento repetido.</li>
    <li>Para uma venda já concluída, orientar o uso do estorno.</li>
</ul>
<p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection
