"use strict";
(() => {
    const estoque = true;
    const $ = (id) => document.getElementById(id);
    function pesquisar() {
        const termo = MPB.normalizar($("busca").value.trim());
        const itens = MPB.produtos.filter(
            (p) =>
                (!termo ||
                    p.codigo === termo ||
                    p.barras === termo ||
                    MPB.normalizar(p.nome).includes(termo)) &&
                ($("filtro").value === "todos" ||
                    p.ativo === ($("filtro").value === "ativos")),
        );
        $("resultados").replaceChildren();
        $("detalhes").hidden = true;
        MPB.texto("contador", itens.length + " produtos");
        $("vazio").hidden = !!itens.length;
        itens.forEach((p) => {
            const tr = document.createElement("tr");
            const valores = [
                p.nome,
                p.categoria,
                ...(estoque
                    ? [p.deposito, p.gondola, p.deposito + p.gondola]
                    : [p.unidade, MPB.moeda(p.preco)]),
                p.ativo ? "Ativo" : "Inativo",
            ];
            valores.forEach((valor, i) => {
                const td = document.createElement("td");
                td.textContent = valor;
                if (i === 0) {
                    const small = document.createElement("small");
                    small.textContent = p.codigo + " · " + p.barras;
                    td.append(small);
                }
                if (i >= 2 && i < valores.length - 1) td.className = "numerico";
                tr.append(td);
            });
            const td = document.createElement("td"),
                b = document.createElement("button");
            b.className = "link";
            b.textContent = "Detalhes";
            b.setAttribute("aria-label", "Ver detalhes de " + p.nome);
            b.addEventListener("click", () => {
                MPB.texto("nome-detalhe", p.nome);
                $("dados-detalhe").replaceChildren();
                Object.entries({
                    Código: p.codigo,
                    "Código de barras": p.barras,
                    "Saldo depósito": p.deposito + " UN",
                    "Saldo gôndola": p.gondola + " UN",
                    "Saldo total": p.deposito + p.gondola + " UN",
                    "Última movimentação": "25/09/2026 · 08:30",
                }).forEach(([k, v]) => {
                    const div = document.createElement("div"),
                        dt = document.createElement("dt"),
                        dd = document.createElement("dd");
                    dt.textContent = k;
                    dd.textContent = v;
                    div.append(dt, dd);
                    $("dados-detalhe").append(div);
                });
                $("detalhes").hidden = false;
            });
            td.append(b);
            tr.append(td);
            $("resultados").append(tr);
        });
    }
    ["busca", "filtro"].forEach((id) =>
        $(id).addEventListener("input", pesquisar),
    );
    pesquisar();
})();
