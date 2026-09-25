"use strict";
const orientacoes = document.getElementById("orientacoes");
orientacoes.addEventListener("click", () => {
    const guia = document.getElementById("guia");
    guia.hidden = !guia.hidden;
    orientacoes.setAttribute("aria-expanded", String(!guia.hidden));
    orientacoes.textContent = guia.hidden
        ? "Como apresentar"
        : "Ocultar orientações";
});
