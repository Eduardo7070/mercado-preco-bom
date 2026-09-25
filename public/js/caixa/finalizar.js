"use strict";
(() => {
    const modo = "finalizar";
    const $ = (id) => document.getElementById(id);
    let concluida = false;
    let linhas = [
        { produto: MPB.produto("1001"), quantidade: 2, cancelado: false },
        { produto: MPB.produto("1002"), quantidade: 6, cancelado: false },
    ];
    const total = () =>
        linhas.reduce(
            (s, l) => s + (l.cancelado ? 0 : l.produto.preco * l.quantidade),
            0,
        );
    const metodo = () => document.querySelector("[name=metodo]:checked")?.value;
    function pagamento() {
        if (modo === "cancelar") return;
        const dinheiro = metodo() === "Dinheiro";
        $("campo-recebido").hidden = !dinheiro;
        $("recebido").required = dinheiro;
        $("campo-aprovado").hidden = dinheiro;
        const diferenca = Number($("recebido").value) - total();
        MPB.texto(
            "troco-label",
            dinheiro
                ? diferenca < 0
                    ? "Falta receber"
                    : "Troco"
                : "Situação do recebimento",
        );
        MPB.texto(
            "troco",
            dinheiro
                ? MPB.moeda(Math.abs(diferenca))
                : $("aprovado").checked
                    ? "Aprovado"
                    : "Pendente",
        );
    }
    function renderizar() {
        const corpo = $("cupom");
        corpo.replaceChildren();
        linhas.forEach((linha, i) => {
            const tr = document.createElement("tr");
            tr.className = linha.cancelado
                ? "cancelado"
                : i === linhas.length - 1
                    ? "selecionado"
                    : "";
            const valores = [
                String(i + 1).padStart(3, "0"),
                linha.produto.nome + (linha.cancelado ? " · CANCELADO" : ""),
                linha.quantidade,
                MPB.moeda(linha.produto.preco),
                MPB.moeda(
                    linha.cancelado
                        ? 0
                        : linha.produto.preco * linha.quantidade,
                ),
            ];
            valores.forEach((valor, k) => {
                const td = document.createElement("td");
                td.textContent = valor;
                if (k === 1) {
                    const small = document.createElement("small");
                    small.textContent = linha.produto.codigo;
                    td.append(small);
                }
                if (k > 1) td.className = "numerico";
                tr.append(td);
            });
            if (modo !== "pagamento") {
                const td = document.createElement("td"),
                    b = document.createElement("button");
                b.type = "button";
                b.className = "link";
                b.textContent = "Cancelar";
                b.disabled = linha.cancelado;
                b.setAttribute("aria-label", "Cancelar item " + (i + 1));
                b.addEventListener("click", () => cancelar(i));
                td.append(b);
                tr.append(td);
            }
            corpo.append(tr);
        });
        MPB.texto("total", MPB.moeda(total()));
        MPB.texto(
            "itens",
            linhas.filter((l) => !l.cancelado).length +
            " itens · " +
            linhas.reduce(
                (s, l) => s + (l.cancelado ? 0 : l.quantidade),
                0,
            ) +
            " unidades",
        );
        pagamento();
    }
    function cancelar(i) {
        if (concluida)
            return MPB.aviso("Venda concluída. Utilize Estornar venda.", true);
        if (linhas[i].cancelado) return;
        MPB.confirmar(
            "Cancelar item?",
            linhas[i].produto.nome + "\nQuantidade: " + linhas[i].quantidade,
            () => {
                linhas[i].cancelado = true;
                if ($("aprovado")) $("aprovado").checked = false;
                renderizar();
                MPB.aviso(
                    "Item cancelado somente na prévia. Nenhum estoque foi alterado.",
                );
            },
        );
    }
    function conferir() {
        if (concluida) return;
        if ($("caixa").value !== "Aberto")
            return MPB.aviso("Abra o caixa antes de continuar.", true);
        if (total() <= 0)
            return MPB.aviso("Inclua ao menos um item válido.", true);
        if (
            metodo() === "Dinheiro" &&
            ($("recebido").value === "" ||
                !Number.isFinite(Number($("recebido").value)) ||
                Number($("recebido").value) < total())
        )
            return MPB.aviso(
                "O valor recebido deve cobrir o total da venda.",
                true,
            );
        if (metodo() !== "Dinheiro" && !$("aprovado").checked)
            return MPB.aviso(
                "Pagamento pendente. Confirme o recebimento.",
                true,
            );
        MPB.confirmar(
            modo === "finalizar"
                ? "Conferir finalização da venda"
                : "Conferir pagamento",
            "Total: " +
            MPB.moeda(total()) +
            "\nForma: " +
            metodo() +
            (metodo() === "Dinheiro"
                ? "\nTroco: " +
                MPB.moeda(Number($("recebido").value) - total())
                : ""),
            () => {
                concluida = true;
                MPB.texto(
                    "situacao",
                    modo === "finalizar"
                        ? "VENDA CONCLUÍDA · PRÉVIA"
                        : "PAGAMENTO CONFERIDO · PRÉVIA",
                );
                $("finalizar").disabled = true;
                $("caixa").disabled = true;
                document
                    .querySelectorAll(
                        "#recebimento input, #adicionar input, #incluir",
                    )
                    .forEach((el) => (el.disabled = true));
                MPB.aviso(
                    "Simulação concluída. Nenhum pagamento, venda ou saldo foi registrado.",
                );
            },
        );
    }
    if (modo === "finalizar") {
        function previa() {
            const p = MPB.produto($("codigo").value.trim());
            const q = Number($("quantidade").value);
            MPB.texto("unitario", p ? MPB.moeda(p.preco) : "—");
            MPB.texto(
                "total-item",
                p && Number.isFinite(q) ? MPB.moeda(p.preco * q) : "—",
            );
        }
        $("codigo").addEventListener("input", previa);
        $("quantidade").addEventListener("input", previa);
        $("adicionar").addEventListener("submit", (e) => {
            e.preventDefault();
            if (concluida) return;
            if ($("caixa").value !== "Aberto")
                return MPB.aviso("Abra o caixa para incluir produtos.", true);
            const p = MPB.produto($("codigo").value.trim()),
                q = Number($("quantidade").value);
            if (!p) return MPB.aviso("Produto não encontrado.", true);
            if (!p.ativo)
                return MPB.aviso(
                    "Produto inativo. Inclusão não permitida.",
                    true,
                );
            if (!Number.isInteger(q) || q <= 0)
                return MPB.aviso(
                    "Informe uma quantidade inteira maior que zero.",
                    true,
                );
            const usado = linhas
                .filter((l) => !l.cancelado && l.produto.codigo === p.codigo)
                .reduce((s, l) => s + l.quantidade, 0);
            if (usado + q > p.gondola)
                return MPB.aviso("Saldo insuficiente na gôndola.", true);
            linhas.push({ produto: p, quantidade: q, cancelado: false });
            $("aprovado").checked = false;
            renderizar();
            MPB.texto("produto-atual", p.nome.toUpperCase());
            $("codigo").focus();
            $("codigo").select();
        });
    }
    if (modo === "cancelar")
        $("concluida").addEventListener("click", () => {
            concluida = !concluida;
            MPB.texto(
                "situacao",
                concluida ? "VENDA CONCLUÍDA" : "CAIXA ABERTO",
            );
            MPB.texto(
                "concluida",
                concluida
                    ? "Voltar à venda aberta"
                    : "Exibir venda já concluída",
            );
        });
    else {
        $("recebimento").addEventListener("submit", (e) => {
            e.preventDefault();
            conferir();
        });
        $("recebimento").addEventListener("input", pagamento);
        document.querySelectorAll("[name=metodo]").forEach((el) =>
            el.addEventListener("change", () => {
                $("aprovado").checked = false;
                pagamento();
            }),
        );
    }
    $("caixa").addEventListener("change", () =>
        MPB.texto(
            "situacao",
            $("caixa").value === "Aberto" ? "CAIXA ABERTO" : "CAIXA FECHADO",
        ),
    );
    document.addEventListener("keydown", (e) => {
        if (document.querySelector("dialog[open]")) return;
        if (e.key === "F2" && $("codigo")) {
            e.preventDefault();
            $("codigo").focus();
            $("codigo").select();
        }
        if (e.key === "F4" && modo !== "cancelar") {
            e.preventDefault();
            conferir();
        }
    });
    renderizar();
})();
