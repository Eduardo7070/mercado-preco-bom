"use strict";
(() => {
    const estoque = true;
    const $ = (id) => document.getElementById(id);

    function paresDoProduto(p) {
        return {
            Código: p.codigo,
            "Código de barras": p.barras,
            Categoria: p.categoria,
            Unidade: p.unidade,
            Preço: MPB.moeda(p.preco),
            Situação: p.ativo ? "Ativo" : "Inativo",
            "Saldo depósito": p.deposito + " UN",
            "Saldo gôndola": p.gondola + " UN",
            "Saldo total": p.deposito + p.gondola + " UN",
            "Última movimentação": "25/09/2026 · 08:30",
        };
    }

    function preencherResumo(id, dados) {
        const lista = $(id);
        lista.replaceChildren();
        Object.entries(dados).forEach(([rotulo, valor]) => {
            const div = document.createElement("div");
            const dt = document.createElement("dt");
            const dd = document.createElement("dd");
            dt.textContent = rotulo;
            dd.textContent = valor;
            div.append(dt, dd);
            lista.append(div);
        });
    }

    function abrirDetalhes(p) {
        MPB.texto("titulo-detalhes", "Detalhes de " + p.nome);
        preencherResumo("dados-detalhes", paresDoProduto(p));
        $("modal-detalhes").showModal();
    }

    function abrirEditar(p) {
        MPB.texto("titulo-editar", "Editar estoque de " + p.nome);
        $("editar-codigo").value = p.codigo;
        $("editar-nome").value = p.nome;
        $("editar-deposito").value = p.deposito;
        $("editar-gondola").value = p.gondola;
        $("editar-movimentacao").value = "25/09/2026 · 08:30";
        $("modal-editar").showModal();
    }

    function abrirDeletar(p) {
        MPB.texto(
            "texto-deletar",
            "Deseja deletar o registro de estoque de " +
                p.nome +
                "? Código " +
                p.codigo +
                ".",
        );
        $("modal-deletar").showModal();
    }

    function botaoAcao(texto, classe, aoClicar) {
        const botao = document.createElement("button");
        botao.type = "button";
        botao.className = classe;
        botao.textContent = texto;
        botao.addEventListener("click", aoClicar);
        return botao;
    }

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

            const td = document.createElement("td");
            const grupo = document.createElement("div");
            grupo.className = "acoes-linha";
            grupo.append(
                botaoAcao("Detalhes", "link", () => abrirDetalhes(p)),
                botaoAcao("Editar", "link", () => abrirEditar(p)),
                botaoAcao("Deletar", "link perigo-link", () => abrirDeletar(p)),
            );
            td.append(grupo);
            tr.append(td);
            $("resultados").append(tr);
        });
    }

    $("modal-editar").addEventListener("close", () => {
        if ($("modal-editar").returnValue === "confirmar") {
            MPB.aviso("Edição simulada. Nenhum saldo foi alterado.");
        }
    });
    $("modal-deletar").addEventListener("close", () => {
        if ($("modal-deletar").returnValue === "confirmar") {
            MPB.aviso(
                "Exclusão simulada. Nenhum registro de estoque foi removido.",
            );
        }
    });

    ["busca", "filtro"].forEach((id) =>
        $(id).addEventListener("input", pesquisar),
    );
    pesquisar();
})();

