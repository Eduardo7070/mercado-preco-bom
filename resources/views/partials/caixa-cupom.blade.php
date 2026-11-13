<div class="tabela-scroll">
    <table class="cupom-tabela"><thead><tr>
        <th>Produto</th><th>Quantidade</th><th>Preço</th><th>Total</th>
        @if($cancel ?? false)<th>Ação</th>@endif
    </tr></thead><tbody id="cupom"></tbody></table>
</div>
<p class="vazio" id="carrinho-vazio">Nenhum produto nesta venda.</p>
<p id="pdv-aviso" class="aviso" role="status" hidden></p>
<div class="atendimento-total"><span id="itens">0 itens</span><span>Total <strong id="total">R$ 0,00</strong></span></div>
