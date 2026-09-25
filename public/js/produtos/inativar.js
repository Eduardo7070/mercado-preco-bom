"use strict";
(() => {
    const $ = (id) => document.getElementById(id);
    MPB.preencherProdutos("produto");
    function atualizar() {
        const p = MPB.produto($("produto").value);
        MPB.texto("situacao", p.ativo ? "Ativo" : "Já inativo");
        MPB.texto("saldo", p.deposito + p.gondola + " UN");
        $("revisar").disabled = !p.ativo;
        $("motivo").value = "";
    }
    atualizar();
    $("produto").addEventListener("change", atualizar);
    $("formulario").addEventListener("submit", (e) => {
        e.preventDefault();
        const p = MPB.produto($("produto").value);
        if (!p.ativo) return;
        if (!$("motivo").value.trim())
            return MPB.aviso("Informe o motivo da inativação.", true);
        MPB.confirmar(
            "Inativar produto?",
            p.nome + "\nMotivo: " + $("motivo").value,
            () =>
                MPB.aviso("Inativação simulada. Nenhum produto foi alterado."),
        );
    });
})();
