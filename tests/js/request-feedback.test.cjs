const { test } = require("node:test");
const assert = require("node:assert/strict");
const vm = require("node:vm");
const fs = require("node:fs");
const path = require("node:path");
const source = fs.readFileSync(path.join(__dirname, "../../public/js/alertas.js"), "utf8");

function fixture(fetcher, flash = {}) {
    const calls = [], events = {}, timers = new Map();
    let timerId = 0, loading = false, closed = 0;
    const body = {};
    const context = {
        window: { addEventListener() {} },
        document: {
            body,
            querySelector() { return null; },
            getElementById() { return { textContent: JSON.stringify(flash) }; },
            addEventListener(name, fn) { events[name] = fn; },
        },
        Swal: {
            fire(options) { calls.push(options); options.didOpen?.({ addEventListener() {} }); return Promise.resolve({ isConfirmed: true }); },
            showLoading() { loading = true; }, isLoading() { return loading; },
            close() { closed++; loading = false; },
            stopTimer() {}, resumeTimer() {},
        },
        sessionStorage: { getItem() { return null; }, removeItem() {} },
        fetch: fetcher,
        setTimeout(fn) { timers.set(++timerId, fn); return timerId; },
        clearTimeout(id) { timers.delete(id); },
        console,
    };
    vm.createContext(context); vm.runInContext(source, context);
    return { api: context.window.MPBAlert, calls, events, runTimers() { for (const fn of timers.values()) fn(); timers.clear(); }, get closed() { return closed; } };
}
const response = (status, data) => new Response(JSON.stringify(data), { status, headers: { "Content-Type": "application/json" } });

test("GET reports success after the server responds", async () => {
    const app = fixture(async () => response(200, { total: 2 }));
    assert.equal((await app.api.request("/consulta")).total, 2);
    assert.equal(app.calls.length, 1);
    assert.equal(app.calls[0].icon, "success");
});
test("loading stays pending until a write completes and closes exactly once", async () => {
    let finish;
    const app = fixture(() => new Promise(resolve => finish = resolve));
    const pending = app.api.request("/produto", { method: "PUT" }, { success: false });
    app.runTimers();
    assert.equal(app.calls[0].title, "Salvando alterações…");
    finish(response(200, { message: "Salvo" }));
    await pending;
    assert.equal(app.closed, 1);
    assert.equal(app.calls.length, 1);
});
test("422 displays field errors once as a warning", async () => {
    const app = fixture(async () => response(422, { errors: { nome: ["Informe o nome."] } }));
    await assert.rejects(app.api.request("/produto", { method: "PUT" }), error => error.status === 422 && error.notified);
    assert.equal(app.calls.length, 1);
    assert.equal(app.calls[0].icon, "warning");
    assert.equal(app.calls[0].text, "Informe o nome.");
});
test("403, 419 and 429 produce actionable warnings", async () => {
    for (const status of [403, 419, 429]) {
        const app = fixture(async () => response(status, {}));
        await assert.rejects(app.api.request("/produto"), error => error.status === status);
        assert.equal(app.calls[0].icon, "warning");
        assert.ok(app.calls[0].text.length > 20);
    }
});
test("aborted searches produce no failure or success notification", async () => {
    const app = fixture(async () => { const error = new Error("cancelled"); error.name = "AbortError"; throw error; });
    await assert.rejects(app.api.request("/consulta"), error => error.name === "AbortError");
    assert.equal(app.calls.length, 0);
});
test("server failures never expose debug details or report success", async () => {
    const app = fixture(async () => response(500, { message: "SQL secret debug details" }));
    await assert.rejects(app.api.request("/produto", { method: "POST" }));
    assert.equal(app.calls.length, 1);
    assert.equal(app.calls[0].icon, "error");
    assert.ok(!app.calls[0].text.includes("SQL"));
});
test("write connection failures tell the user to verify the outcome before retrying", async () => {
    const app = fixture(async () => { throw new TypeError("network failed"); });
    await assert.rejects(app.api.request("/venda", { method: "POST" }), error => error.message.includes("Atualize a consulta"));
    assert.equal(app.calls[0].icon, "error");
});
test("202 is pending information rather than completed success", async () => {
    const app = fixture(async () => response(202, { message: "Aguardando processamento." }));
    await app.api.request("/fila", { method: "POST" });
    assert.equal(app.calls[0].icon, "info");
});
test("HTML login redirects cannot be mistaken for a successful JSON response", async () => {
    const app = fixture(async () => ({
        ok: true, status: 200, redirected: true, headers: new Headers({ "Content-Type": "text/html" }),
    }));
    await assert.rejects(app.api.request("/produto"), error => error.status === 401);
    assert.equal(app.calls[0].icon, "warning");
});
test("flash validation feedback has precedence over success", () => {
    const app = fixture(null, { errors: ["Campo inválido"], success: "OK" });
    app.events.DOMContentLoaded();
    assert.equal(app.calls.length, 1);
    assert.equal(app.calls[0].icon, "warning");
});