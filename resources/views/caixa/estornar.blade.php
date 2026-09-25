@extends('layouts.mercado')

@section('content')
<section class="estorno">
    <header class="estorno-barra">
        <strong>PREÇO BOM · PDV</strong><span>Caixa 01 · Supervisão</span>
    </header>
    <div class="estorno-titulo">ESTORNO DE VENDA</div>
    <div class="colunas">
        <section class="painel">
            <div class="painel-titulo"><h2>Localizar cupom original</h2></div>
            <div class="filtros">
                <label class="campo"
                    >Número da venda<input
                        id="busca"
                        type="search"
                        placeholder="Ex.: 02458" /></label
                ><label class="campo"
                    >Venda localizada<select id="venda">
                        <option value="02458">#02458 — Concluída</option>
                        <option value="02457">#02457 — Estornada</option>
                        <option value="02456">#02456 — Aberta</option>
                    </select></label
                >
            </div>
            <p id="vazio" class="vazio" hidden>Venda não encontrada.</p>
            <form id="formulario" class="painel-corpo">
                <dl class="resumo">
                    <div>
                        <dt>Cliente</dt>
                        <dd id="cliente"></dd>
                    </div>
                    <div>
                        <dt>Situação original</dt>
                        <dd id="estado"></dd>
                    </div>
                    <div>
                        <dt>Pagamento</dt>
                        <dd id="metodo"></dd>
                    </div>
                    <div>
                        <dt>Operador / caixa</dt>
                        <dd>Mariana Oliveira / 01</dd>
                    </div>
                    <div>
                        <dt>Data original</dt>
                        <dd>25/09/2026 · 08:30</dd>
                    </div>
                </dl>
                <div class="tabela-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Itens originais</th>
                                <th>Qtd.</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Arroz branco 5 kg</td>
                                <td>2</td>
                                <td>R$ 50,00</td>
                            </tr>
                            <tr>
                                <td>Leite integral 1 L</td>
                                <td>6</td>
                                <td>R$ 30,00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="campos">
                    <label class="campo inteiro"
                        >Perfil utilizado na prévia<select id="perfil">
                            <option>Responsável autorizado</option>
                            <option>Operador sem autorização</option>
                        </select></label
                    ><label class="campo inteiro"
                        >Motivo do estorno *<textarea
                            id="motivo"
                            required
                            placeholder="Informe o motivo da reversão integral"
                        ></textarea>
                    </label>
                </div>
                <div class="acoes">
                    <button id="revisar" class="botao perigo">
                        Revisar estorno
                    </button>
                </div>
            </form>
        </section>
        <aside class="painel">
            <div class="painel-titulo"><h2>Total a estornar</h2></div>
            <div class="painel-corpo">
                <strong id="total" class="total-estorno">R$ 80,00</strong>
                <p class="ajuda">
                    Reversão integral da venda. Os itens, o pagamento e o
                    operador originais permanecem identificados.
                </p>
                <dl class="resumo">
                    <div>
                        <dt>Arroz</dt>
                        <dd>2 unidades</dd>
                    </div>
                    <div>
                        <dt>Leite</dt>
                        <dd>6 unidades</dd>
                    </div>
                </dl>
                <p class="aviso">
                    Simulação sem devolução de valores e sem alteração do
                    estoque.
                </p>
            </div>
        </aside>
    </div>
    <footer class="estorno-rodape">
        Operação sujeita à autorização · Histórico preservado
    </footer>
</section>
@endsection

@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H03.004</span><h2 id="historia-titulo">Estornar venda</h2></div>
<p class="historia-ator"><strong>Ator:</strong> Responsável autorizado</p>
<p class="historia-descricao">Como responsável autorizado, quero estornar integralmente uma venda concluída com justificativa, para corrigir uma operação indevida e preservar sua rastreabilidade.</p>
<details class="historia-criterios"><summary>Critérios de aceitação</summary>
<ul>
    <li>Localizar uma venda existente e concluída que ainda não tenha sido estornada.</li>
    <li>Exigir perfil autorizado, motivo obrigatório e confirmação do estorno integral.</li>
    <li>Preservar os itens, o pagamento, o operador e a data da venda original.</li>
    <li>Na operação real, reverter os valores e o estoque uma única vez, sem apagar a venda.</li>
</ul>
<p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection
