"use strict";
(() => {
    const $ = id => document.getElementById(id);
    if (!$("perfis-dados")) return;
    const profiles = JSON.parse($("perfis-dados").textContent);
    const modal = $("modal-permissoes");
    const form = $("form-permissoes");
    const checks = [...form.querySelectorAll('input[name="permissions[]"]')];
    const normalize = value => value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLocaleLowerCase("pt-BR");
    let profile, baseline = [], saving = false, trigger;
    const selectedIds = () => checks.filter(input => input.checked).map(input => Number(input.value));
    function updateSummary() {
        const item = profiles.find(p => String(p.id) === $("perfil-selecionado").value);
        text("perfil-resumo-nome", item.name);
        text("perfil-resumo-contagem", item.permissionIds.length + " permissões habilitadas" + (item.slug === "admin" ? " · Perfil protegido" : ""));
    }
    function text(id, value) { $(id).textContent = value; }
    function updateCount() {
        const selected = selectedIds();
        const changed = selected.length !== baseline.length || selected.some(id => !baseline.includes(id));
        text("permissoes-contador", selected.length + " de " + checks.length + " permissões selecionadas");
        text("permissoes-alteracoes", profile.slug === "admin" ? "Perfil protegido" : changed ? "Alterações ainda não salvas" : "Nenhuma alteração pendente");
        $("salvar-permissoes").disabled = saving || profile.slug === "admin" || !changed;
    }
    function filter() {
        const term = normalize($("buscar-permissao").value.trim());
        let count = 0;
        form.querySelectorAll("[data-permission-group]").forEach(group => {
            let visible = 0;
            const name = group.querySelector("legend").textContent;
            group.querySelectorAll("[data-permission-label]").forEach(label => {
                label.hidden = !normalize(name + " " + label.textContent).includes(term);
                if (!label.hidden) visible++;
            });
            group.hidden = visible === 0; count += visible;
        });
        $("permissoes-vazio").hidden = count > 0;
    }
    $("abrir-permissoes").addEventListener("click", event => {
        trigger = event.currentTarget;
        profile = profiles.find(p => String(p.id) === $("perfil-selecionado").value);
        baseline = [...profile.permissionIds];
        form.action = profile.url;
        form.reset();
        checks.forEach(input => { input.checked = baseline.includes(Number(input.value)); input.disabled = profile.slug === "admin"; });
        text("modal-permissoes-titulo", "Permissões · " + profile.name);
        $("perfil-protegido").hidden = profile.slug !== "admin";
        $("salvar-permissoes").hidden = profile.slug === "admin";
        for (const id of ["marcar-permissoes", "desmarcar-permissoes"]) $(id).hidden = profile.slug === "admin";
        $("permissoes-erro").hidden = true;
        updateCount(); filter(); modal.showModal(); $("buscar-permissao").focus();
    });
    for (const id of ["fechar-permissoes", "cancelar-permissoes"]) $(id).addEventListener("click", () => { if (!saving) modal.close(); });
    modal.addEventListener("cancel", event => { if (saving) event.preventDefault(); });
    modal.addEventListener("close", () => trigger?.focus());
    checks.forEach(input => input.addEventListener("change", updateCount));
    $("buscar-permissao").addEventListener("input", filter);
    for (const [id, value] of [["marcar-permissoes", true], ["desmarcar-permissoes", false]]) {
        $(id).addEventListener("click", () => {
            if (profile.slug === "admin" || saving) return;
            checks.filter(input => !input.closest("[data-permission-label]").hidden).forEach(input => input.checked = value);
            updateCount();
        });
    }
    $("perfil-selecionado").addEventListener("change", updateSummary);
    form.addEventListener("submit", async event => {
        event.preventDefault();
        if (saving || profile.slug === "admin") return;
        const ids = selectedIds();
        saving = true; $("permissoes-erro").hidden = true;
        checks.forEach(input => input.disabled = true);
        for (const id of ["fechar-permissoes","cancelar-permissoes","marcar-permissoes","desmarcar-permissoes"]) $(id).disabled = true;
        updateCount(); text("salvar-permissoes", "Salvando…");
        try {
            const result = await MPBAlert.request(profile.url, {
                method: "PUT",
                headers: { Accept: "application/json", "Content-Type": "application/json", "X-CSRF-TOKEN": form.querySelector('[name="_token"]').value },
                body: JSON.stringify({ permissions: ids })
            }, { success: false, pending: "Salvando permissões…" });
            profile.permissionIds = ids; baseline = [...ids];
            updateSummary(); text("permissoes-feedback", result.message);
            modal.close();
            MPBAlert.success(result.message);
        } catch (error) { text("permissoes-erro", error.message); $("permissoes-erro").hidden = false; }
        finally {
            saving = false; checks.forEach(input => input.disabled = profile.slug === "admin");
            for (const id of ["fechar-permissoes","cancelar-permissoes","marcar-permissoes","desmarcar-permissoes"]) $(id).disabled = false;
            text("salvar-permissoes", "Salvar permissões"); updateCount();
        }
    });
    updateSummary();
})();