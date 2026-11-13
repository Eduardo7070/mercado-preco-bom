export function iniciarCatalogo() {
    const $ = id => document.getElementById(id);
    const config = JSON.parse($("catalogo-config").textContent);
    let page = 1, lastPage = 1, selected = null, historyPage = 1, timer, searchController;
    const forms = new Map();
    const url = (key, id) => config[key].replace("__ID__", id);
    const date = value => value ? new Date(value.includes("T") ? value : value.replace(" ", "T") + "Z").toLocaleString("pt-BR", { timeZone: "America/Sao_Paulo" }) : "—";
    const text = (id, value) => { $(id).textContent = value; };
    async function api(endpoint, options = {}) {
        const writing = !["GET", "HEAD"].includes((options.method || "GET").toUpperCase());
        return MPBAlert.request(endpoint, {
            ...options,
            headers: { "X-CSRF-TOKEN": config.csrf, ...options.headers },
        }, { success: false });
    }
    function action(label, callback, danger = false) {
        const button = document.createElement("button");
        button.type = "button"; button.className = danger ? "link perigo-link" : "link"; button.textContent = label;
        button.addEventListener("click", async () => {
            button.disabled = true;
            try { await callback(); } catch (error) { text("busca-status", error.message); } finally { button.disabled = false; }
        });
        return button;
    }
    function cell(tr, value) { const td = document.createElement("td"); td.textContent = value ?? "—"; tr.append(td); return td; }
    async function search() {
        clearTimeout(timer);
        searchController?.abort();
        searchController = new AbortController();
        const signal = searchController.signal;
        text("busca-status", "Pesquisando…");
        const params = new URLSearchParams({ q: $("busca").value.trim(), situacao: $("filtro").value, categoria: $("categoria-filtro").value, saldo: $("saldo-filtro")?.value || "todos", page });
        try {
            const result = await api(config.search + "?" + params, { signal });
            if (signal.aborted) return;
            if (result.current_page > result.last_page) { page = result.last_page; return search(); }
            lastPage = result.last_page; page = result.current_page;
            $("resultados").replaceChildren();
            for (const p of result.data) {
                const tr = document.createElement("tr");
                const name = cell(tr, p.nome);
                const code = document.createElement("small"); code.textContent = (p.codigo || "Sem código") + " · " + (p.barras || "Sem código de barras"); name.append(code);
                cell(tr, p.categoria);
                if (config.stock) {
                    for (const value of [p.deposito, p.gondola, p.deposito + p.gondola]) cell(tr, value + " " + p.unidade);
                } else { cell(tr, p.unidade); cell(tr, MPB.moeda(p.preco)); }
                cell(tr, p.ativo ? "Ativo" : "Inativo");
                const td = cell(tr, "");
                const group = document.createElement("div"); group.className = "acoes-linha";
                group.append(action("Detalhes", () => open(p.id, "detalhes")));
                if (config.canEdit) group.append(action(config.stock ? "Ajustar" : "Editar", () => open(p.id, "editar")));
                if (config.canStatus) group.append(action(p.ativo ? "Inativar" : "Reativar", () => open(p.id, "status")));
                if (config.stock && config.canEdit && p.ativo) group.append(action("Transferir", () => open(p.id, "transferir")));
                if (config.canDelete) group.append(action(config.stock ? "Zerar saldos" : "Excluir", () => open(p.id, "deletar"), true));
                td.append(group);
                $("resultados").append(tr);
            }
            text("contador", result.total + " produtos");
            text("busca-status", result.total + " produtos encontrados.");
            $("vazio").hidden = result.total > 0;
            text("pagina-atual", "Página " + page + " de " + lastPage);
            $("pagina-anterior").disabled = page <= 1;
            $("pagina-proxima").disabled = page >= lastPage;
        } catch (error) {
            if (error.name !== "AbortError") { $("resultados").replaceChildren(); text("busca-status", error.message); text("contador", "—"); }
        }
    }
    function summary(p) {
        const pairs = {
            Código: p.codigo || "—", "Código de barras": p.barras || "—", Categoria: p.categoria, Unidade: p.unidade,
            Preço: MPB.moeda(p.preco), Situação: p.ativo ? "Ativo" : "Inativo",
            "Saldo depósito": p.deposito + " " + p.unidade, "Saldo gôndola": p.gondola + " " + p.unidade,
            "Última movimentação": date(p.movimentado_em), "Última alteração": date(p.updated_at),
        };
        $("dados-detalhes").replaceChildren();
        for (const [key, value] of Object.entries(pairs)) {
            const div = document.createElement("div"), dt = document.createElement("dt"), dd = document.createElement("dd");
            dt.textContent = key; dd.textContent = value; div.append(dt, dd); $("dados-detalhes").append(div);
        }
        $("auditoria").replaceChildren();
        for (const audit of p.audits || []) {
            const line = document.createElement("p");
            line.className = "ajuda";
            line.textContent = date(audit.created_at) + " · " + audit.action + " · " + audit.user + (audit.reason ? " · " + audit.reason : "");
            $("auditoria").append(line);
        }
    }
    async function history() {
        const id = selected.id;
        text("historico-status", "Carregando histórico…");
        try {
            const data = await api(url("history", id) + "?page=" + historyPage);
            if (id !== selected?.id) return;
            $("historico-itens").replaceChildren();
            for (const item of data.data) {
                const tr = document.createElement("tr");
                cell(tr, date(item.created_at)); cell(tr, item.operacao + " / " + item.local);
                cell(tr, item.quantidade + " " + selected.unidade);
                cell(tr, item.saldo_anterior + " → " + item.saldo_final);
                cell(tr, (item.user?.name || "—") + " · " + (item.motivo || "Sem justificativa") + (item.documento ? " · " + item.documento : "") + (item.lote ? " · Lote: " + item.lote : "") + (item.validade ? " · Validade: " + item.validade : ""));
                $("historico-itens").append(tr);
            }
            text("historico-status", data.total ? "Página " + data.current_page + " de " + data.last_page + " · " + data.total + " movimentações" : "Nenhuma movimentação registrada.");
            $("historico-anterior").disabled = data.current_page <= 1;
            $("historico-proxima").disabled = data.current_page >= data.last_page;
        } catch (error) { text("historico-status", error.message); }
    }
    async function open(id, mode) {
        const p = await api(url("show", id)); selected = p;
        if (mode === "detalhes") {
            text("titulo-detalhes", p.nome); summary(p);
            $("historico-area").hidden = !config.canHistory;
            $("modal-detalhes").showModal();
            if (config.canHistory) { historyPage = 1; history(); }
            return;
        }
        const form = $("form-" + mode); form.reset(); form.querySelector(".erro-modal").hidden = true;
        forms.set(form, { product: p, operation: crypto.randomUUID() });
        if (mode === "editar") {
            text("titulo-editar", (config.stock ? "Ajustar saldos: " : "Editar: ") + p.nome);
            for (const [name, value] of Object.entries(p)) {
                const field = form.elements.namedItem(name);
                if (field && field instanceof HTMLInputElement && field.type === "checkbox") field.checked = !!value;
                else if (field) field.value = value ?? "";
            }
        }
        if (mode === "deletar") text("texto-deletar", (config.stock ? "Zerar depósito e gôndola de " : "Excluir definitivamente ") + p.nome + "?");
        if (mode === "status") text("texto-status", (p.ativo ? "Inativar " : "Reativar ") + p.nome + "? O saldo e o histórico serão preservados.");
        if (mode === "transferir") text("texto-transferir", p.nome + " · Depósito: " + p.deposito + " · Gôndola: " + p.gondola + " " + p.unidade);
        $("modal-" + mode).showModal();
    }
    document.querySelectorAll("[data-close]").forEach(button => button.addEventListener("click", () => button.closest("dialog").close()));
    for (const mode of ["editar", "deletar", "status", "transferir"]) {
        const form = $("form-" + mode);
        form.addEventListener("submit", async event => {
            event.preventDefault();
            const state = forms.get(form);
            if (!state || state.pending) return;
            const p = state.product;
            const body = Object.fromEntries(new FormData(form)); body.version = p.version; body.operation_id = state.operation;
            let endpoint, method;
            if (mode === "editar") {
                endpoint = config.stock ? "balances" : "update"; method = "PUT";
                if (!config.stock) body.validade = form.elements.validade.checked;
            }
            if (mode === "deletar") { endpoint = config.stock ? "balances" : "destroy"; method = config.stock ? "PUT" : "DELETE"; if (config.stock) { body.deposito = 0; body.gondola = 0; } }
            if (mode === "status") { endpoint = "status"; method = "PATCH"; body.ativo = !p.ativo; }
            if (mode === "transferir") { endpoint = "transfer"; method = "POST"; }
            const errorBox = form.querySelector(".erro-modal");
            const buttons = [...form.querySelectorAll("button")];
            state.pending = true; buttons.forEach(button => button.disabled = true); errorBox.hidden = true;
            try {
                const result = await api(url(endpoint, p.id), { method, headers: { "Content-Type": "application/json" }, body: JSON.stringify(body) });
                form.closest("dialog").close();
                await search();
                text("busca-status", result.message);
                MPBAlert.success(result.message);
            } catch (error) { errorBox.textContent = error.message; errorBox.hidden = false; }
            finally { state.pending = false; buttons.forEach(button => button.disabled = false); }
        });
    }
    ["busca", "filtro", "categoria-filtro", "saldo-filtro"].forEach(id => $(id)?.addEventListener("input", () => {
        page = 1; searchController?.abort(); clearTimeout(timer); timer = setTimeout(search, 250);
    }));
    $("limpar-filtros").addEventListener("click", () => { $("busca").value = ""; $("filtro").value = "todos"; $("categoria-filtro").value = ""; if ($("saldo-filtro")) $("saldo-filtro").value = "todos"; page = 1; search(); });
    $("pagina-anterior").addEventListener("click", () => { if (page > 1) { page--; search(); } });
    $("pagina-proxima").addEventListener("click", () => { if (page < lastPage) { page++; search(); } });
    $("historico-anterior").addEventListener("click", () => { historyPage--; history(); });
    $("historico-proxima").addEventListener("click", () => { historyPage++; history(); });
    search();
}