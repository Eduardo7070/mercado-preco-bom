"use strict";
(() => {
    const edicao = true;
    const $ = (id) => document.getElementById(id);
    let original = null;
    function preencher() {
        original = MPB.produto($("produto").value);
        ["codigo", "barras", "nome", "categoria", "unidade", "preco"].forEach(
            (k) => ($(k).value = original[k]),
        );
        $("validade").checked = original.validade;
        MPB.texto("situacao", original.ativo ? "Ativo" : "Inativo");
        MPB.texto("saldo", original.deposito + original.gondola + " UN");
    }
    if (edicao) {
        MPB.preencherProdutos("produto");
        $("produto").addEventListener("change", preencher);
        preencher();
    }
    $("limpar").addEventListener("click", () => {
        if (edicao) preencher();
        else $("formulario").reset();
        $("mensagem").hidden = true;
    });
    $("formulario").addEventListener("submit", (event) => {
        event.preventDefault();
        const codigo = $("codigo").value.trim(),
            barras = $("barras").value.trim(),
            nome = $("nome").value.trim(),
            preco = Number($("preco").value);
        if (!codigo && !barras)
            return MPB.aviso(
                "Informe o código interno ou o código de barras.",
                true,
            );
        if (
            MPB.produtos.some(
                (p) =>
                    p !== original &&
                    ((codigo && p.codigo === codigo) ||
                        (barras && p.barras === barras)),
            )
        )
            return MPB.aviso("Código ou código de barras já utilizado.", true);
        if (!nome || !Number.isFinite(preco) || preco <= 0)
            return MPB.aviso("Informe descrição e preço maior que zero.", true);
        MPB.confirmar(
            edicao ? "Conferir alterações" : "Conferir cadastro",
            nome +
                "\n" +
                $("categoria").value +
                " · " +
                $("unidade").value +
                "\nPreço: " +
                MPB.moeda(preco),
            () =>
                MPB.aviso(
                    "Simulação concluída. Nenhum produto foi cadastrado ou alterado.",
                ),
        );
    });
})();
