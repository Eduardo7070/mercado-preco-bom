"use strict";
(() => {
    const operation = "ajustar";
    const $ = id => document.getElementById(id);
    const products = JSON.parse($("produtos-estoque").textContent);
    const form = $("formulario");
    function update() {
        const p = products.find(p => String(p.id) === $("produto").value);
        const q = Number($("quantidade").value);
        const before = p ? p[$("local").value] : 0;
        const after = operation === "entrada" ? before + q :
            operation === "saida" || $("tipo")?.value === "avaria" ? before - q : q;
        $("saldo-atual").textContent = p ? before + " " + p.unidade : "—";
        $("saldo-novo").textContent = p && Number.isFinite(after) ? after + " " + p.unidade : "—";
        $("variacao").textContent = p ? (after - before > 0 ? "+" : "") + (after - before) : "—";
        $("saldo-novo").classList.toggle("negativo", after < 0);
        if (operation === "entrada") {
            $("controle-lote").hidden = !p?.validade;
            for (const key of ["lote", "validade"]) {
                $(key).required = !!p?.validade;
                $(key).disabled = !p?.validade;
            }
        }
    }
    form.addEventListener("input", update);
    form.addEventListener("change", update);
    form.addEventListener("submit", event => {
        const p = products.find(p => String(p.id) === $("produto").value);
        if (!p) { event.preventDefault(); MPB.aviso("Selecione um produto cadastrado.", true); }
    });
    update();
})();