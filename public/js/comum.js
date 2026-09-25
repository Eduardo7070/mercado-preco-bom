"use strict";
window.MPB = {
    produtos: [
        {
            codigo: "1001",
            barras: "7891000000011",
            nome: "Arroz branco 5 kg",
            categoria: "Mercearia",
            unidade: "UN",
            preco: 25,
            ativo: true,
            validade: true,
            deposito: 20,
            gondola: 10,
        },
        {
            codigo: "1002",
            barras: "7891000000028",
            nome: "Leite integral 1 L",
            categoria: "Laticínios",
            unidade: "UN",
            preco: 5,
            ativo: true,
            validade: true,
            deposito: 12,
            gondola: 8,
        },
        {
            codigo: "1003",
            barras: "7891000000035",
            nome: "Café tradicional 500 g",
            categoria: "Mercearia",
            unidade: "UN",
            preco: 18.9,
            ativo: true,
            validade: true,
            deposito: 0,
            gondola: 0,
        },
        {
            codigo: "1004",
            barras: "7891000000042",
            nome: "Sabão em pó 1 kg",
            categoria: "Limpeza",
            unidade: "UN",
            preco: 12.5,
            ativo: false,
            validade: false,
            deposito: 4,
            gondola: 0,
        },
        {
            codigo: "1005",
            barras: "7891000000059",
            nome: "Arroz integral 1 kg",
            categoria: "Mercearia",
            unidade: "UN",
            preco: 8.9,
            ativo: true,
            validade: true,
            deposito: 15,
            gondola: 5,
        },
    ],
    moeda: (valor) =>
        new Intl.NumberFormat("pt-BR", {
            style: "currency",
            currency: "BRL",
        }).format(valor),
    normalizar: (texto) =>
        texto
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase(),
    texto: (id, valor) => {
        document.getElementById(id).textContent = valor;
    },
    aviso(texto, erro = false) {
        const el = document.getElementById("mensagem");
        el.textContent = texto;
        el.hidden = false;
        el.classList.toggle("erro", erro);
        el.setAttribute("role", erro ? "alert" : "status");
        el.scrollIntoView({ block: "nearest", behavior: "smooth" });
    },
    confirmar(titulo, texto, acao) {
        const dialogo = document.getElementById("confirmacao");
        this.texto("confirmacao-titulo", titulo);
        this.texto("confirmacao-texto", texto);
        dialogo.returnValue = "";
        dialogo.onclose = () => {
            if (dialogo.returnValue === "confirmar") acao();
        };
        dialogo.showModal();
    },
    preencherProdutos(id) {
        const select = document.getElementById(id);
        this.produtos.forEach((p) =>
            select.add(
                new Option(
                    p.codigo + " — " + p.nome + (p.ativo ? "" : " (inativo)"),
                    p.codigo,
                ),
            ),
        );
    },
    produto(codigo) {
        return this.produtos.find(
            (p) => p.codigo === codigo || p.barras === codigo,
        );
    },
};
// Os formulários são protótipos: não submetem dados ao servidor.
document.addEventListener("submit", (event) => {
    if (!event.target.closest("dialog")) event.preventDefault();
});
