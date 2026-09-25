const { chromium } = require(process.argv[2]);
const assert = require("node:assert/strict");
const fs = require("node:fs/promises");
(async () => {
    const browser = await chromium.launch({
        executablePath: "C:/Program Files/Google/Chrome/Application/chrome.exe",
        headless: true,
    });
    try {
        const page = await browser.newPage({
            viewport: { width: 1440, height: 1000 },
        });
        const errors = [],
            writes = [];
        page.on("pageerror", (e) => errors.push(e.message));
        await page.route("**/*", (r) => {
            if (!["GET", "HEAD"].includes(r.request().method())) {
                writes.push(r.request().url());
                return r.abort();
            }
            return r.continue();
        });
        const pages = [
            "",
            "produtos/cadastrar",
            "produtos/editar",
            "produtos/inativar",
            "produtos/consultar",
            "estoque/entrada",
            "estoque/saida",
            "estoque/consultar",
            "estoque/ajustar",
            "caixa/finalizar",
            "caixa/cancelar",
            "caixa/pagamento",
            "caixa/estornar",
        ];
        const open = async (p) => {
            const r = await page.goto("http://127.0.0.1:8091/" + p);
            assert.equal(r.status(), 200, p);
            await page.locator("h1").waitFor();
        };
        const confirm = async () => {
 await page.locator("#confirmar").click();
 await page.waitForFunction(() => !document.getElementById('confirmacao').open && !document.getElementById('mensagem').hidden && document.getElementById('mensagem').getAttribute('role') === 'status');
};
        await fs.mkdir("storage/app/prototipos", { recursive: true });
        for (const p of pages) {
            await open(p);
            assert(
                await page.evaluate(
                    () =>
                        document.documentElement.scrollWidth <= innerWidth + 1,
                ),
                p,
            );
            if (
                [
                    "",
                    "caixa/finalizar",
                    "produtos/cadastrar",
                    "estoque/entrada",
                    "caixa/estornar",
                ].includes(p)
            )
                await page.screenshot({
                    path:
                        "storage/app/prototipos/" +
                        (p.replaceAll("/", "-") || "inicio") +
                        ".png",
                    fullPage: true,
                });
        }
        await open("produtos/cadastrar");
        await page.locator("#codigo").fill("1001");
        await page.locator("#nome").fill("Produto exemplo");
        await page.locator("#categoria").selectOption("Mercearia");
        await page.locator("#preco").fill("12");
        await page.getByRole("button", { name: "Revisar cadastro" }).click();
        await page
            .getByRole("alert")
            .filter({ hasText: "já utilizado" })
            .waitFor();
        await page.locator("#codigo").fill("1010");
        await page.getByRole("button", { name: "Revisar cadastro" }).click();
        await confirm();
        await page
            .getByRole("status")
            .filter({ hasText: "Nenhum produto" })
            .waitFor();
        await open("produtos/editar");
        await page.locator("#preco").fill("26");
        await page.getByRole("button", { name: "Revisar alterações" }).click();
        await confirm();
        await open("produtos/inativar");
        await page.locator("#produto").selectOption("1004");
        assert(await page.locator("#revisar").isDisabled());
        await page.locator("#produto").selectOption("1001");
        await page.locator("#motivo").fill("Fora de linha");
        await page.locator("#revisar").click();
        await confirm();
        await open("produtos/consultar");
        await page.locator("#busca").fill("1010");
        await page.locator("#vazio").waitFor();
        await page.locator("#busca").fill("arroz");
        assert.equal(await page.locator("#resultados tr").count(), 2);
        await open("estoque/entrada");
        await page.locator("#lote").fill("LT0926");
        await page.locator("#validade").fill("2027-09-25");
        await page.getByRole("button", { name: "Revisar entrada" }).click();
        await confirm();
        await open("estoque/saida");
        await page.locator("#quantidade").fill("50");
        await page.locator("#motivo").fill("Devolução");
        await page.getByRole("button", { name: "Revisar saída" }).click();
        await page
            .getByRole("alert")
            .filter({ hasText: "Saldo insuficiente" })
            .waitFor();
        await page.locator("#quantidade").fill("5");
        await page.getByRole("button", { name: "Revisar saída" }).click();
        await confirm();
        await open("estoque/consultar");
        await page.locator("#busca").fill("1003");
        await page
            .getByRole("button", {
                name: "Ver detalhes de Café tradicional 500 g",
            })
            .click();
        await page.locator("#detalhes").waitFor();
        await open("estoque/ajustar");
        await page.locator("#tipo").selectOption("avaria");
        await page.locator("#quantidade").fill("2");
        await page.locator("#motivo").fill("Embalagem avariada");
        assert.equal(await page.locator("#saldo-novo").textContent(), "18 UN");
        await page.getByRole("button", { name: "Revisar ajuste" }).click();
        await confirm();
        await open("caixa/finalizar");
        await page.keyboard.press("F2");
        assert(
            await page
                .locator("#codigo")
                .evaluate((el) => el === document.activeElement),
        );
        await page.locator("#codigo").fill("1004");
        await page.locator("#incluir").click();
        await page.getByRole("alert").filter({ hasText: "inativo" }).waitFor();
        await page.locator("#codigo").fill("1002");
        await page.locator("#incluir").click();
        assert.match(await page.locator("#total").textContent(), /85,00/);
        await page.keyboard.press("F4");
        await confirm();
        assert(await page.locator("#finalizar").isDisabled());
        await open("caixa/cancelar");
        await page
            .getByRole("button", { name: "Cancelar item 1", exact: true })
            .click();
        await confirm();
        assert.match(await page.locator("#total").textContent(), /30,00/);
        assert(
            await page
                .getByRole("button", { name: "Cancelar item 1", exact: true })
                .isDisabled(),
        );
        await page.locator("#concluida").click();
        await page
            .getByRole("button", { name: "Cancelar item 2", exact: true })
            .click();
        await page
            .getByRole("alert")
            .filter({ hasText: "Venda concluída" })
            .waitFor();
        await open("caixa/pagamento");
        await page.getByRole("radio", { name: "Pix", exact: true }).check();
        await page.locator("#finalizar").click();
        await page
            .getByRole("alert")
            .filter({ hasText: "Pagamento pendente" })
            .waitFor();
        await page.locator("#aprovado").check();
        await page.locator("#finalizar").click();
        await confirm();
        await open("caixa/estornar");
        await page.locator("#perfil").selectOption("Operador sem autorização");
        await page.locator("#motivo").fill("Venda duplicada");
        await page.locator("#revisar").click();
        await page
            .getByRole("alert")
            .filter({ hasText: "sem autorização" })
            .waitFor();
        await page.locator("#perfil").selectOption("Responsável autorizado");
        await page.locator("#revisar").click();
        await confirm();
        assert(await page.locator("#revisar").isDisabled());
        await page.locator("#busca").fill("99999");
        await page.locator("#vazio").waitFor();
        assert(await page.locator("#formulario").isHidden());
        await page.setViewportSize({ width: 390, height: 844 });
        for (const p of pages) {
            await open(p);
            assert(
                await page.evaluate(
                    () =>
                        document.documentElement.scrollWidth <= innerWidth + 1,
                ),
                "Overflow " + p,
            );
            if (p === "caixa/finalizar")
                await page.screenshot({
                    path: "storage/app/prototipos/caixa-mobile.png",
                    fullPage: true,
                });
        }
        assert.deepEqual(errors, []);
        assert.deepEqual(writes, []);
        console.log(
            "PASS: 13 telas desktop/mobile, validações das 12 histórias, F2/F4, zero erros de JavaScript e zero gravações.",
        );
    } finally {
        await browser.close();
    }
})().catch((e) => {
    console.error(e);
    process.exit(1);
});
