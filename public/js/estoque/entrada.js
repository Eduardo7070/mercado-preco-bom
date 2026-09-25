"use strict";
(() => {
    const tipoTela = "entrada";
    const $ = (id) => document.getElementById(id);
    MPB.preencherProdutos("produto");
    let atual = 0,
        proximo = 0;
    function atualizar() {
        const p = MPB.produto($("produto").value),
            q = Number($("quantidade").value);
        atual = p[$("local").value];
        proximo =
            tipoTela === "entrada"
                ? atual + q
                : tipoTela === "saida" || $("tipo").value === "avaria"
                  ? atual - q
                  : q;
        MPB.texto("saldo-atual", atual + " UN");
        MPB.texto(
            "saldo-novo",
            Number.isFinite(proximo) ? proximo + " UN" : "—",
        );
        MPB.texto(
            "variacao",
            (proximo - atual > 0 ? "+" : "") + (proximo - atual) + " UN",
        );
        $("saldo-novo").classList.toggle("negativo", proximo < 0);
        if (tipoTela === "entrada") {
            ["lote", "validade"].forEach((id) => ($(id).required = p.validade));
            $("controle-lote").hidden = !p.validade;
        }
    }
    $("formulario").addEventListener("input", atualizar);
    atualizar();
    $("formulario").addEventListener("submit", (e) => {
        e.preventDefault();
        atualizar();
        const p = MPB.produto($("produto").value),
            q = Number($("quantidade").value);
        if (tipoTela !== "ajustar" && !p.ativo)
            return MPB.aviso(
                "Produto inativo. Selecione um produto ativo.",
                true,
            );
        const contagem =
            tipoTela === "ajustar" && $("tipo").value === "contagem";
        if (
            $("quantidade").value === "" ||
            !Number.isInteger(q) ||
            q < 0 ||
            (!contagem && q === 0)
        )
            return MPB.aviso("Informe uma quantidade inteira válida.", true);
        if (proximo < 0)
            return MPB.aviso(
                "Saldo insuficiente. A operação não pode gerar estoque negativo.",
                true,
            );
        if (tipoTela !== "entrada" && !$("motivo").value.trim())
            return MPB.aviso("Informe a justificativa.", true);
        if (tipoTela === "entrada" && p.validade && !$("lote").value.trim())
            return MPB.aviso("Informe o lote.", true);
        MPB.confirmar(
            "Conferir movimentação",
            p.nome +
                "\nSaldo atual: " +
                atual +
                " UN\nSaldo previsto: " +
                proximo +
                " UN",
            () =>
                MPB.aviso("Movimentação simulada. Nenhum saldo foi alterado."),
        );
    });
})();
