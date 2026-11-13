"use strict";
(() => {
    const target = () => document.querySelector("dialog[open]") || document.body;
    let loadingOwner = 0;
    function show(icon, title, text, options = {}) {
        return Swal.fire({
            target: target(), icon, title, text: String(text || ""),
            confirmButtonText: "Entendi", confirmButtonColor: "#145b3d",
            cancelButtonColor: "#66766e", customClass: { popup: "mpb-swal" },
            heightAuto: false, returnFocus: true, ...options,
        });
    }
    function toast(icon, text) {
        return show(icon, "", text, {
            toast: true, position: "top-end", showConfirmButton: false,
            timer: icon === "error" ? 6500 : 3200, timerProgressBar: true,
            didOpen: popup => {
                popup.addEventListener("mouseenter", Swal.stopTimer);
                popup.addEventListener("mouseleave", Swal.resumeTimer);
            },
        });
    }
    function pending(title = "Processando solicitação…", blocking = true) {
        const owner = ++loadingOwner;
        const timer = setTimeout(() => {
            if (owner !== loadingOwner) return;
            show("info", title, blocking ? "Aguarde a confirmação do servidor." : "", {
                toast: !blocking, position: blocking ? "center" : "top-end",
                showConfirmButton: false, allowOutsideClick: !blocking, allowEscapeKey: !blocking,
                didOpen: () => Swal.showLoading(),
            });
        }, blocking ? 0 : 400);
        return () => {
            clearTimeout(timer);
            if (owner === loadingOwner) { loadingOwner++; if (Swal.isLoading()) Swal.close(); }
        };
    }
    async function request(endpoint, options = {}, feedback = {}) {
        const method = (options.method || "GET").toUpperCase();
        const writing = !["GET", "HEAD"].includes(method);
        const stop = pending(feedback.pending || (writing ? "Salvando alterações…" : "Carregando dados…"), writing);
        try {
            const response = await fetch(endpoint, { cache: "no-store", ...options, headers: { Accept: "application/json", ...options.headers } });
            let data = {};
            const json = (response.headers.get("content-type") || "").includes("json");
            if (response.ok && !json && response.status !== 204) {
                const error = new Error("Resposta inesperada do servidor.");
                error.status = response.redirected ? 401 : 502;
                throw error;
            }
            if (json) {
                try { data = await response.json(); }
                catch (error) { if (error.name === "AbortError") throw error; const invalid = new Error("Resposta inválida do servidor."); invalid.status = 502; throw invalid; }
            }
            if (!response.ok) {
                const validation = Object.values(data.errors || {}).flat().join("\n");
                const messages = {
                    401: "Sua sessão expirou. Atualize a página e entre novamente.",
                    403: "Seu perfil não tem permissão para realizar esta ação.",
                    404: "O registro não foi encontrado. Atualize a consulta.",
                    409: "Os dados foram alterados por outra operação. Atualize e confira novamente.",
                    419: "Sua sessão expirou. Atualize a página antes de reenviar.",
                    429: "Muitas tentativas em pouco tempo. Aguarde e tente novamente.",
                };
                const error = new Error(messages[response.status] || (response.status === 422 ? validation || "Revise os campos informados." : "O servidor não conseguiu concluir a solicitação. Tente novamente."));
                error.status = response.status;
                throw error;
            }
            stop();
            if (response.status === 202) toast("info", data.message || "Solicitação recebida e aguardando processamento.");
            else if (feedback.success !== false) toast("success", data.message || feedback.success || "Dados carregados.");
            return data;
        } catch (error) {
            stop();
            if (error.name === "AbortError") throw error;
            if (error.status === 401) error.message = "Sua sessão expirou. Atualize a página e entre novamente.";
            if (!error.status) error.message = writing
                ? "Não foi possível confirmar o resultado. Atualize a consulta antes de tentar novamente."
                : "Não foi possível carregar os dados. Confira sua conexão e tente novamente.";
            if (!error.notified) {
                error.notified = true;
                await show(error.status && error.status < 500 ? "warning" : "error",
                    error.status === 422 ? "Confira os dados" : "Não foi possível concluir", error.message);
            }
            throw error;
        }
    }
    window.MPBAlert = {
        request, pending,
        success: text => toast("success", text),
        info: text => toast("info", text),
        warning: text => show("warning", "Atenção", text),
        error: text => show("error", "Não foi possível concluir", text),
        confirm: (title, text) => show("question", title, text, {
            showCancelButton: true, confirmButtonText: "Confirmar", cancelButtonText: "Cancelar",
            focusCancel: true, allowOutsideClick: false,
        }).then(result => result.isConfirmed),
    };
    function readNavigation() {
        try { const stored = sessionStorage.getItem("mpb-pending-request"); sessionStorage.removeItem("mpb-pending-request"); return stored ? JSON.parse(stored) : null; } catch { return null; }
    }
    document.addEventListener("DOMContentLoaded", () => {
        const payload = document.getElementById("mpb-alert-data");
        const data = payload ? JSON.parse(payload.textContent) : {};
        const previous = readNavigation();
        if (data.errors?.length) show("warning", "Revise os dados informados", data.errors.join("\n"));
        else if (data.error) show("error", "Não foi possível concluir", data.error);
        else if (data.warning) show("warning", "Atenção", data.warning);
        else if (data.success) toast("success", data.success);
        else if (data.info) toast("info", data.info);
        else if (previous && previous.method !== "GET" && Date.now() - previous.at < 120000) {
            show("info", "Confira o resultado", "A página foi atualizada. Confira o resultado antes de reenviar a operação.");
        }
    });
    // Runs after the screen-specific handlers, so cancelled/AJAX submits are ignored.
    window.addEventListener("submit", async event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.method === "dialog") return;
        if (!form.matches('[data-server-form], .auth-form, .logout-form')) return;
        if(form.dataset.confirm && form.dataset.confirmed !== 'true') {
            event.preventDefault();
            if(form.dataset.confirming === 'true') return;
            form.dataset.confirming='true';
            const accepted=await window.MPBAlert.confirm('Confirmar operação',form.dataset.confirm);
            delete form.dataset.confirming;
            if(accepted){form.dataset.confirmed='true';form.requestSubmit(event.submitter || undefined);delete form.dataset.confirmed;}
            return;
        }
        if (form.dataset.requestPending === "true") { event.preventDefault(); return; }
        form.dataset.requestPending = "true";
        const method = form.method.toUpperCase();
        if (method === "GET") return;
        try { sessionStorage.setItem("mpb-pending-request", JSON.stringify({ at: Date.now(), method })); } catch {}
        pending("Processando solicitação…", true);
    });
    window.addEventListener("click", event => {
        const link = event.target.closest?.("a[href]");
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        if (link.hasAttribute("download") || (link.target && link.target !== "_self")) return;
        const destination = new URL(link.href, location.href);
        if (!["http:", "https:"].includes(destination.protocol) || destination.origin !== location.origin || destination.href === location.href) return;
        if (destination.pathname === location.pathname && destination.search === location.search) return;
        try { sessionStorage.setItem("mpb-pending-request", JSON.stringify({ at: Date.now(), method: "GET" })); } catch {}
        pending("Carregando página…", false);
    });
    let invalidTimer;
    document.addEventListener("invalid", event => {
        const form = event.target.form;
        if (!form) return;
        clearTimeout(invalidTimer);
        invalidTimer = setTimeout(() => {
            const invalid = [...form.querySelectorAll(":invalid")];
            if (!invalid.length) return;
            const messages = invalid.slice(0, 6).map(field => {
                const label = (field.labels?.[0] || field.closest("label"))?.textContent.trim().split("\n")[0] || field.name || "Campo";
                return label + ": " + field.validationMessage;
            });
            show("warning", "Existem campos pendentes", messages.join("\n"));
        }, 0);
    }, true);
    window.addEventListener("pageshow", event => {
        if (!event.persisted) return;
        document.querySelectorAll('[data-request-pending="true"]').forEach(form => delete form.dataset.requestPending);
        if (Swal.isLoading()) Swal.close();
    });
})();