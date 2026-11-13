<div class="campo inteiro produto-seletor">
    <label for="produto-busca">Produto *</label>
    <div class="produto-combobox">
        <input id="produto-busca" name="product_search" type="search" autocomplete="off" maxlength="191" required
            role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="produto-opcoes" aria-describedby="produto-busca-status"
            placeholder="Digite o nome, código ou código de barras"
            value="{{ $selectedProduct?->nome }}">
        <div id="produto-opcoes" class="produto-opcoes" role="listbox" aria-label="Produtos encontrados" hidden></div>
    </div>
    <input type="hidden" id="produto" name="product_id" value="{{ $selectedProduct?->id }}">
    <span id="produto-busca-status" class="produto-busca-status" role="status" aria-live="polite">Digite para pesquisar e selecione um produto.</span>
</div>
