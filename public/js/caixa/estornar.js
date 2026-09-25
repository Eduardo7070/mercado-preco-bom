"use strict";
(() => {
    const $ = (id) => document.getElementById(id);
    const vendas = [
        {
            codigo: "02458",
            estado: "Concluída",
            cliente: "Ana Paula Silva",
            metodo: "Pix",
        },
        {
            codigo: "02457",
            estado: "Estornada",
            cliente: "Carlos Eduardo",
            metodo: "Débito",
        },
        {
            codigo: "02456",
            estado: "Aberta",
            cliente: "Mariana Costa",
            metodo: "Pendente",
        },
    ];
    let venda = vendas[0],
        concluido = false;
    function exibir() {
        venda = vendas.find((v) => v.codigo === $("venda").value);
        concluido = false;
        $("motivo").value = "";
        $("revisar").disabled = false;
        $("formulario").hidden = !venda;
        $("vazio").hidden = !!venda;
        MPB.texto("total", venda ? "R$ 80,00" : "—");
        if (venda) {
            MPB.texto("cliente", venda.cliente);
            MPB.texto("estado", venda.estado);
            MPB.texto("metodo", venda.metodo);
        }
    }
    $("venda").addEventListener("change", exibir);
    $("busca").addEventListener("input", () => {
        const itens = vendas.filter((v) =>
            v.codigo.includes($("busca").value.trim()),
        );
        $("venda").replaceChildren();
        itens.forEach((v) =>
            $("venda").add(
                new Option("#" + v.codigo + " — " + v.estado, v.codigo),
            ),
        );
        exibir();
    });
    $("formulario").addEventListener("submit", (e) => {
        e.preventDefault();
        if (!venda || concluido) return;
        if (venda.estado !== "Concluída")
            return MPB.aviso(
                venda.estado === "Estornada"
                    ? "Esta venda já foi estornada. Não é possível repetir a operação."
                    : "A venda está aberta. Utilize Cancelar item.",
                true,
            );
        if ($("perfil").value !== "Responsável autorizado")
            return MPB.aviso("Perfil sem autorização para estornar.", true);
        if (!$("motivo").value.trim())
            return MPB.aviso("Informe o motivo do estorno.", true);
        MPB.confirmar(
            "Conferir estorno integral",
            "Venda #" +
                venda.codigo +
                " — R$ 80,00\nMotivo: " +
                $("motivo").value,
            () => {
                concluido = true;
                $("revisar").disabled = true;
                MPB.aviso(
                    "Estorno simulado. Nenhum saldo, pagamento ou registro foi alterado.",
                );
            },
        );
    });
    exibir();
})();
