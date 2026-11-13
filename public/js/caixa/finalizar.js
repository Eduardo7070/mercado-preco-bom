"use strict";
(() => {
    const $ = id => document.getElementById(id);
    const products = JSON.parse($("pdv-products").textContent);
    const cart = new Map();
    const money = cents => MPB.moeda(cents / 100);
    let submitted = false;
    const notify = message => { $("pdv-aviso").textContent = message; $("pdv-aviso").hidden = false; };
    const total = () => [...cart.values()].reduce((sum, row) => sum + Math.round(row.product.preco * 100) * row.quantity, 0);
    function payment() {
        const cash = $("metodo").value === "Dinheiro";
        $("campo-recebido").hidden = !cash;
        $("recebido").disabled = !cash;
        $("recebido").required = cash;
        $("campo-aprovado").hidden = cash;
        $("aprovado").disabled = cash;
        $("aprovado").required = !cash;
        $("troco").textContent = money(cash ? Math.max(0, Math.round(Number($("recebido").value) * 100) - total()) : 0);
    }
    function render() {
        $("cupom").replaceChildren();
        $("campos-itens").replaceChildren();
        let index = 0;
        for (const row of cart.values()) {
            const tr = document.createElement("tr");
            for (const text of [row.product.nome, row.quantity + " " + row.product.unidade, money(Math.round(row.product.preco*100)), money(Math.round(row.product.preco*100)*row.quantity)]) {
                const td = document.createElement("td"); td.textContent = text; tr.append(td);
            }
            const td = document.createElement("td");
            const button = document.createElement("button");
            button.type = "button"; button.className = "link"; button.textContent = "Remover";
            button.addEventListener("click", () => { if (!submitted) { cart.delete(row.product.id); render(); } });
            td.append(button); tr.append(td); $("cupom").append(tr);
            for (const [key, value] of Object.entries({ product_id: row.product.id, quantity: row.quantity })) {
                const input = document.createElement("input"); input.type = "hidden"; input.name = "items[" + index + "][" + key + "]"; input.value = value; $("campos-itens").append(input);
            }
            index++;
        }
        $("total").textContent = money(total());
        $("itens").textContent = cart.size + " itens";
        $("carrinho-vazio").hidden = cart.size > 0;
        payment();
    }
    for (const row of JSON.parse($("pdv-old-items").textContent)) {
        const product = products.find(p => p.id === Number(row.product_id));
        if (product) cart.set(product.id, { product, quantity: Number(row.quantity) });
    }
    $("adicionar").addEventListener("submit", event => {
        event.preventDefault();
        if (submitted) return;
        const term = $("codigo").value.trim().toLocaleLowerCase("pt-BR");
        const product = products.find(p => [p.codigo, p.barras, p.nome].some(value => value && value.toLocaleLowerCase("pt-BR") === term));
        if (!product) return notify("Produto não encontrado ou sem saldo na gôndola.");
        const quantity = (cart.get(product.id)?.quantity || 0) + Number($("quantidade").value);
        if (!Number.isInteger(quantity) || quantity < 1 || quantity > product.gondola) return notify("Quantidade acima do saldo disponível na gôndola.");
        cart.set(product.id, { product, quantity });
        $("aprovado").checked = false;
        render();
        $("codigo").value = ""; $("quantidade").value = 1; $("codigo").focus();
    });
    $("metodo").addEventListener("change", () => { $("aprovado").checked = false; payment(); });
    $("recebido").addEventListener("input", payment);
    $("recebimento").addEventListener("submit", event => {
        if (submitted || !cart.size || ($("metodo").value === "Dinheiro" && Math.round(Number($("recebido").value)*100) < total())) {
            event.preventDefault(); notify("Inclua produtos e confira o pagamento antes de finalizar."); return;
        }
        submitted = true;
        if ($("finalizar")) { $("finalizar").disabled = true; $("finalizar").textContent = "Registrando venda…"; }
    });
    document.addEventListener("keydown", event => {
        if (event.key === "F2") { event.preventDefault(); $("codigo").focus(); }
        if (event.key === "F4" && $("finalizar")) { event.preventDefault(); $("recebimento").requestSubmit(); }
    });
    render();
})();