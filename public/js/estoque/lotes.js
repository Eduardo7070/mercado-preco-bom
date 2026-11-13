"use strict";

(() => {
    const modal = document.getElementById('modal-lote');
    if (!modal) return;

    const form = document.getElementById('form-lote');
    const buttons = [...document.querySelectorAll('.regularizar-lote')];
    const field = name => form.elements.namedItem(name);

    function openModal(button, restore = false) {
        const batch = button.dataset;
        form.reset();
        form.action = batch.url;
        field('regularized_batch').value = batch.id;
        document.getElementById('modal-lote-contexto').textContent =
            batch.produto + ' · ' + (batch.local === 'deposito' ? 'Depósito' : 'Gôndola') + ' · ' + batch.saldo;
        const requiresExpiration = batch.exigeValidade === '1';
        field('expires_on').required = requiresExpiration;
        document.getElementById('validade-obrigatoria').hidden = !requiresExpiration;
        if (!restore) {
            field('lot').value = batch.lote;
            field('expires_on').value = batch.validade;
            field('quarantine').value = batch.bloqueado;
            field('reason').value = '';
        }
        modal.showModal();
        field('lot').focus();
    }

    buttons.forEach(button => button.addEventListener('click', () => openModal(button)));
    modal.querySelectorAll('[data-fechar-lote]').forEach(button => {
        button.addEventListener('click', () => modal.close());
    });
    modal.addEventListener('click', event => {
        if (event.target !== modal) return;
        const bounds = modal.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right ||
            event.clientY < bounds.top || event.clientY > bounds.bottom) modal.close();
    });

    const previous = buttons.find(button => button.dataset.id === field('regularized_batch').value);
    if (previous) openModal(previous, true);
})();