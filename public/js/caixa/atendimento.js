export function iniciarAtendimento(screen) {
    const element = id => document.getElementById(id);
    if (!element('cupom')) return;

    const config = JSON.parse(element('pdv-config').textContent);
    const products = JSON.parse(element('pdv-products').textContent);
    const storageKey = 'mpb-cart-' + config.userId;
    const cart = new Map();
    const quantityInMilli = quantity => Math.round(Number(quantity) * 1000);
    const lineTotal = row => Math.floor((Math.round(row.product.preco * 100) * quantityInMilli(row.quantity) + 500) / 1000);
    const money = cents => MPB.moeda(cents / 100);
    let cached = null;
    let submitted = false;
    try { cached = JSON.parse(localStorage.getItem(storageKey)); } catch {}

    if (config.discardedDraftId && Number(cached?.draftId) === Number(config.discardedDraftId)) {
        try { localStorage.removeItem(storageKey); } catch {}
        cached = null;
    }

    const restoreCache = !config.hasOld && cached && (!config.hasResume || Number(cached.draftId) === Number(config.draftId));
    const state = {
        operationId: restoreCache ? cached.operationId : config.operationId,
        draftId: restoreCache ? cached.draftId : config.draftId,
        customerName: restoreCache ? cached.customerName || '' : config.customerName,
        customerId: restoreCache ? cached.customerId || '' : config.customerId,
        method: restoreCache ? cached.method || 'Dinheiro' : config.method,
        received: restoreCache ? cached.received || '' : config.received,
    };
    state.operationId ||= config.operationId;
    const initial = restoreCache ? cached.items || [] : JSON.parse(element('pdv-old-items').textContent);
    for (const row of initial) {
        const product = products.find(item => item.id === Number(row.product_id)) || {
            id: Number(row.product_id), nome: 'Produto #' + row.product_id + ' indisponível',
            unidade: 'UN', preco: 0, gondola: 0, unavailable: true,
        };
        cart.set(product.id, { product, quantity: Number(row.quantity) });
    }

    function notify(message) {
        element('pdv-aviso').textContent = message;
        element('pdv-aviso').hidden = false;
        MPBAlert.warning(message);
    }
    function invalidItems() {
        return [...cart.values()].some(row => row.product.unavailable || row.quantity > row.product.gondola);
    }
    function total() {
        return [...cart.values()].reduce((amount, row) => amount + lineTotal(row), 0);
    }
    function save() {
        state.items = [...cart.values()].map(row => ({ product_id: row.product.id, quantity: row.quantity }));
        try { localStorage.setItem(storageKey, JSON.stringify(state)); return true; }
        catch { return false; }
    }
    function payment() {
        if (!element('metodo')) return;
        const cash = element('metodo').value === 'Dinheiro';
        element('campo-recebido').hidden = !cash;
        element('recebido').disabled = !cash;
        element('recebido').required = cash;
        element('campo-aprovado').hidden = cash;
        element('aprovado').disabled = cash;
        element('aprovado').required = !cash;
        element('resumo-troco').hidden = !cash;
        element('troco').textContent = money(Math.max(0, Math.round(Number(element('recebido').value) * 100) - total()));
    }
    function render() {
        element('cupom').replaceChildren();
        element('campos-itens')?.replaceChildren();
        let index = 0;
        for (const row of cart.values()) {
            const tableRow = document.createElement('tr');
            for (const text of [row.product.nome, row.quantity + ' ' + row.product.unidade,
                row.product.unavailable ? 'Indisponível' : money(Math.round(row.product.preco * 100)),
                row.product.unavailable ? '—' : money(lineTotal(row))]) {
                const cell = document.createElement('td');
                cell.textContent = text;
                tableRow.append(cell);
            }
            if (screen === 'cancelar' && config.canCancel) {
                const cell = document.createElement('td');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'botao secundario';
                button.textContent = 'Cancelar item';
                button.addEventListener('click', async () => {
                    if (submitted || !await MPBAlert.confirm('Cancelar este item?', row.product.nome + ' será retirado da compra.')) return;
                    cart.delete(row.product.id);
                    render();
                    MPBAlert.success('Item retirado da venda.');
                });
                cell.append(button);
                tableRow.append(cell);
            }
            element('cupom').append(tableRow);
            if (element('campos-itens')) {
                for (const [key, value] of Object.entries({ product_id: row.product.id, quantity: row.quantity })) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `items[${index}][${key}]`;
                    input.value = value;
                    element('campos-itens').append(input);
                }
            }
            index++;
        }
        element('total').textContent = money(total());
        element('itens').textContent = cart.size + (cart.size === 1 ? ' produto' : ' produtos');
        element('carrinho-vazio').hidden = cart.size > 0;
        if (element('finalizar')) element('finalizar').disabled = !cart.size || invalidItems();
        payment();
        save();
    }

    const paymentForm = element('recebimento');
    if (paymentForm) {
        paymentForm.elements.operation_id.value = state.operationId;
        paymentForm.elements.draft_id.value = state.draftId || '';
        paymentForm.elements.customer_name.value = state.customerName;
        paymentForm.elements.customer_id.value = state.customerId;
        element('metodo').value = state.method;
        element('recebido').value = state.received;
        if (state.customerName || state.customerId) element('identificar-cliente').open = true;
        paymentForm.addEventListener('input', () => {
            state.customerName = paymentForm.elements.customer_name.value;
            state.customerId = paymentForm.elements.customer_id.value;
            state.received = element('recebido').value;
            state.method = element('metodo').value;
            payment();
            save();
        });
        element('metodo').addEventListener('change', () => { element('aprovado').checked = false; payment(); });
        paymentForm.addEventListener('submit', event => {
            if (submitted || !cart.size || invalidItems() || (element('metodo').value === 'Dinheiro' && Math.round(Number(element('recebido').value) * 100) < total())) {
                event.preventDefault();
                notify('Confira os produtos e o valor recebido antes de confirmar.');
                return;
            }
            submitted = true;
            element('finalizar').disabled = true;
            element('finalizar').textContent = 'Registrando pagamento…';
        });
    }

    element('adicionar')?.addEventListener('submit', event => {
        event.preventDefault();
        const term = element('codigo').value.trim().toLocaleLowerCase('pt-BR');
        const product = products.find(item => [item.codigo, item.barras, item.nome].some(value => value && value.toLocaleLowerCase('pt-BR') === term));
        if (!product) return notify('Produto não encontrado ou indisponível para venda.');
        const quantity = (quantityInMilli(cart.get(product.id)?.quantity || 0) + quantityInMilli(element('quantidade').value)) / 1000;
        if (['UN', 'CX'].includes(product.unidade) && !Number.isInteger(quantity)) return notify('Informe uma quantidade inteira para este produto.');
        if (!Number.isFinite(quantity) || quantity <= 0 || quantity > product.gondola) return notify('Quantidade acima do estoque disponível.');
        cart.set(product.id, { product, quantity });
        render();
        element('codigo').value = '';
        element('quantidade').value = 1;
        element('codigo').focus();
    });

    document.querySelectorAll('[data-payment-link]').forEach(link => link.addEventListener('click', event => {
        if (!cart.size || invalidItems()) {
            event.preventDefault();
            notify('Inclua produtos disponíveis antes de seguir para o pagamento.');
        } else if (!save()) {
            event.preventDefault();
            notify('Permita o armazenamento neste navegador para continuar a venda.');
        }
    }));
    element('suspender')?.addEventListener('click', async () => {
        if (!cart.size) return notify('Inclua um produto antes de suspender o atendimento.');
        const button = element('suspender');
        button.disabled = true;
        const label = element('rotulo-suspensao').value.trim() || 'Atendimento ' + new Date().toLocaleTimeString('pt-BR');
        element('rotulo-suspensao').value = label;
        try {
            await MPBAlert.request(config.draftUrl, {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: JSON.stringify({ operation_id: state.operationId, draft_id: state.draftId, label,
                    customer_name: state.customerName, items: [...cart.values()].map(row => ({ product_id: row.product.id, quantity: row.quantity })) }),
            }, { success: false });
            try { localStorage.removeItem(storageKey); } catch {}
            location.href = config.saleUrl;
        } catch {} finally { button.disabled = false; }
    });
    document.addEventListener('keydown', event => {
        if (window.Swal?.isVisible()) return;
        if (event.key === 'F2' && element('codigo')) { event.preventDefault(); element('codigo').focus(); }
        if (event.key === 'F4') {
            event.preventDefault();
            if (paymentForm) paymentForm.requestSubmit();
            else element('ir-pagamento')?.click();
        }
    });
    window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
    render();
    if (invalidItems()) notify('Há produtos indisponíveis no carrinho. Cancele esses itens para continuar.');
}
