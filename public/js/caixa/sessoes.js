"use strict";
(() => {
    const form = document.getElementById('movimentar-form');
    if (!form) return;
    const button = document.getElementById('registrar-dinheiro');
    function updateLabel() {
        button.textContent = form.elements.action.value === 'withdraw' ? 'Registrar sangria' : 'Registrar suprimento';
    }
    form.elements.action.addEventListener('change', updateLabel);
    updateLabel();
})();
