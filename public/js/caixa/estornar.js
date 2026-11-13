"use strict";
(() => {
    const form = document.getElementById('form-estorno');
    if (!form) return;
    const type = document.getElementById('tipo-estorno');
    const destination = document.getElementById('destino-estorno');
    const rows = [...form.querySelectorAll('[data-return-item]')];
    const previousDisposition = form.querySelector('[data-disposition]')?.value;
    if (previousDisposition) destination.value = previousDisposition;
    function updateTotal() {
        let cents = 0;
        let quantity = 0;
        for (const row of rows) {
            const input = row.querySelector('[data-return-quantity]');
            const value = Number(input.value);
            quantity += value;
            const amount = value === Number(input.max) ? Number(row.dataset.remainingCents)
                : Math.floor((Number(row.dataset.unitPrice) * (Math.round(value * 1000) + Math.round(Number(row.dataset.returnedQuantity) * 1000)) + 500) / 1000) - Number(row.dataset.returnedCents);
            cents += amount;
            row.querySelector('[data-disposition]').value = destination.value;
        }
        document.getElementById('valor-estorno').textContent = MPB.moeda(cents / 100);
        document.getElementById('confirmar-estorno').disabled = quantity <= 0;
    }
    function updateMode(reset) {
        const full = type.value === 'total';
        for (const row of rows) {
            const input = row.querySelector('[data-return-quantity]');
            input.readOnly = full;
            if (full) input.value = input.max;
            else if (reset) input.value = 0;
        }
        updateTotal();
    }
    type.addEventListener('change', () => updateMode(true));
    destination.addEventListener('change', updateTotal);
    form.addEventListener('input', updateTotal);
    updateMode(false);
})();
