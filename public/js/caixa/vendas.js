"use strict";
document.getElementById("imprimir-recibo")?.addEventListener("click", () => window.print());
const completed=document.querySelector("[data-clear-cart]");if(completed){try{localStorage.removeItem("mpb-cart-"+completed.dataset.clearCart);}catch{}}