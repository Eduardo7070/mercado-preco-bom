"use strict";
document.getElementById("catalogo-config") && import("../catalogo.js").then(module => module.iniciarCatalogo()).catch(() => {
    document.getElementById("busca-status").textContent = "Não foi possível carregar a consulta. Atualize a página.";
});