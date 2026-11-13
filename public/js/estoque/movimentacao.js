export function iniciarMovimentacao(operation) {
    const element = id => document.getElementById(id);
    const config = JSON.parse(element('produto-estoque-config').textContent);
    const form = element('formulario');
    const input = element('produto-busca');
    const productId = element('produto');
    const options = element('produto-opcoes');
    const status = element('produto-busca-status');
    const quantity = element('quantidade');
    let selected = config.selected;
    let matches = [];
    let activeIndex = -1;
    let timer;
    let request;

    function hideOptions() {
        clearTimeout(timer);
        request?.abort();
        options.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        activeIndex = -1;
    }
    function updateBalance() {
        const amount = Number(quantity.value);
        const before = selected ? Number(selected[element('local').value]) : 0;
        const type = operation === 'saida' ? element('tipo').value : operation;
        const count = type === 'contagem';
        if (operation === 'saida') {
            element('quantidade-label').textContent = count ? 'Quantidade encontrada no estoque *' : 'Quantidade a retirar *';
            element('tipo-ajuda').textContent = count
                ? 'Informe o total encontrado na conferência. Esse valor será o novo saldo do local selecionado.'
                : type === 'avaria'
                    ? 'Informe a quantidade perdida ou danificada que será descontada do estoque.'
                    : 'Informe a quantidade que será retirada do estoque.';
        }
        const after = operation === 'entrada' ? before + amount : count ? amount : before - amount;
        const rounded = value => Math.round(value * 1000) / 1000;
        const variation = rounded(after - before);
        element('saldo-atual').textContent = selected ? before + ' ' + selected.unidade : '—';
        element('saldo-novo').textContent = selected && Number.isFinite(after) ? rounded(after) + ' ' + selected.unidade : '—';
        element('variacao').textContent = selected && Number.isFinite(variation) ? (variation > 0 ? '+' : '') + variation : '—';
        element('saldo-novo').classList.toggle('negativo', !!selected && after < 0);
        const wholeUnits = selected && ['UN', 'CX'].includes(selected.unidade);
        quantity.step = wholeUnits ? '1' : '0.001';
        quantity.min = count ? '0' : wholeUnits ? '1' : '0.001';
        if (operation === 'entrada') {
            element('controle-lote').hidden = !selected?.validade;
            for (const id of ['lote', 'validade']) {
                element(id).required = !!selected?.validade;
                element(id).disabled = !selected?.validade;
            }
        }
    }
    function choose(product) {
        selected = product;
        productId.value = product.id;
        input.value = product.nome;
        input.setCustomValidity('');
        status.textContent = (product.codigo || product.barras || 'Sem código') + (product.ativo ? ' · Produto selecionado.' : ' · Produto inativo selecionado para conferência.');
        hideOptions();
        updateBalance();
    }
    function highlight(index) {
        const buttons = [...options.querySelectorAll('[role="option"]')];
        if (!buttons.length) return;
        activeIndex = (index + buttons.length) % buttons.length;
        buttons.forEach((button, position) => {
            const active = position === activeIndex;
            button.setAttribute('aria-selected', String(active));
            button.classList.toggle('produto-opcao-ativa', active);
        });
        input.setAttribute('aria-activedescendant', buttons[activeIndex].id);
        buttons[activeIndex].scrollIntoView({ block: 'nearest' });
    }
    async function search() {
        const term = input.value.trim();
        if (!term || selected) return;
        request?.abort();
        request = new AbortController();
        const signal = request.signal;
        status.textContent = 'Pesquisando produtos…';
        try {
            const params = new URLSearchParams({ q: term });
            const result = await MPBAlert.request(config.url + '?' + params, { signal }, { success: false });
            if (signal.aborted || input.value.trim() !== term || document.activeElement !== input) return;
            matches = result.data;
            activeIndex = -1;
            options.replaceChildren();
            matches.forEach((product, index) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.id = 'produto-opcao-' + index;
                option.className = 'produto-opcao';
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.tabIndex = -1;
                const name = document.createElement('span');
                name.textContent = (product.codigo || product.barras || 'Sem código') + ' — ' + product.nome;
                const detail = document.createElement('small');
                detail.textContent = product.unidade + (product.ativo ? '' : ' · Inativo');
                option.append(name, detail);
                option.addEventListener('pointerdown', event => event.preventDefault());
                option.addEventListener('click', () => choose(product));
                options.append(option);
            });
            options.hidden = matches.length === 0;
            input.setAttribute('aria-expanded', String(matches.length > 0));
            status.textContent = !matches.length ? 'Nenhum produto encontrado.' : result.has_more
                ? 'Mostrando 10 sugestões. Digite mais para refinar a pesquisa.'
                : 'Selecione um produto ou use as setas e Enter.';
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = 'Não foi possível pesquisar. Digite novamente para tentar.';
        }
    }

    input.addEventListener('input', () => {
        hideOptions();
        selected = null;
        productId.value = '';
        matches = [];
        input.setCustomValidity('Selecione um produto entre as sugestões.');
        updateBalance();
        status.textContent = input.value.trim() ? 'Pesquisando produtos…' : 'Digite para pesquisar e selecione um produto.';
        if (input.value.trim()) timer = setTimeout(search, 250);
    });
    input.addEventListener('focus', () => {
        if (!selected && input.value.trim()) {
            clearTimeout(timer);
            timer = setTimeout(search, 250);
        }
    });
    input.addEventListener('blur', hideOptions);
    input.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            hideOptions();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (!options.hidden) highlight(activeIndex + (event.key === 'ArrowDown' ? 1 : -1));
        } else if (event.key === 'Enter' && !selected) {
            event.preventDefault();
            if (!options.hidden && matches.length) choose(matches[activeIndex >= 0 ? activeIndex : 0]);
            else { clearTimeout(timer); search(); }
        }
    });
    form.addEventListener('input', updateBalance);
    form.addEventListener('change', updateBalance);
    form.addEventListener('submit', event => {
        if (!selected || String(selected.id) !== productId.value) {
            event.preventDefault();
            input.setCustomValidity('Selecione um produto entre as sugestões.');
            input.reportValidity();
        }
    });
    if (selected) choose(selected);
    else input.setCustomValidity('Selecione um produto entre as sugestões.');
    updateBalance();
}
