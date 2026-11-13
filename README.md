# Mercado Preço Bom

Sistema de gestão de supermercado em **Laravel 13, Blade, CSS e JavaScript**, organizado em **Requests → Controllers → Actions**. As views ficam em `resources/views`; os estilos e scripts ficam separados em `public/css` e `public/js`.

Produtos, estoque, usuários, compras e vendas são gravados no banco. **Os pagamentos continuam simulados: não há cobrança externa nem emissão de documento fiscal.**

## Requisitos

- Windows com WampServer, Apache e MySQL ativos.
- PHP compatível com o `composer.lock`; o desenvolvimento utiliza PHP 8.5. Confira com `composer check-platform-reqs`.
- Composer 2, Git e Node.js com npm para instalar dependências e executar testes de JavaScript.
- Extensões PHP: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `dom` e `xml`; `pdo_sqlite` para testes. O Composer informa outras extensões necessárias.

Os comandos abaixo usam **PowerShell**. Confira `php -v`, `php --ini`, `where.exe php`, `composer --version` e `node --version`. O PHP do terminal e o PHP do Apache devem ser compatíveis. **Não precisa de WSL ou Docker.**

## Instalar em outra máquina

```powershell
Set-Location C:\laravel
git clone https://github.com/eduardo7070/mercado-preco-bom.git
Set-Location C:\laravel\mercado-preco-bom
composer install
composer check-platform-reqs
npm ci
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

Crie um banco vazio chamado `mercado_preco_bom` no MySQL do WampServer. Edite as linhas correspondentes do `.env`, sem duplicá-las:

```dotenv
APP_NAME="Mercado Preço Bom"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mercado_preco_bom
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

Ajuste usuário, senha e porta para sua instalação. Não use credenciais reais no `.env.example`. O `.env` não deve ir para o Git.

```powershell
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

Abra **http://127.0.0.1:8000**. Gere a `APP_KEY` somente na primeira instalação; preserve a chave de instalações existentes.

O seeder cria os perfis `admin`, `gerente`, `caixa` e `estoquista`. Para contas novas, mostra uma senha inicial aleatória no terminal, com e-mails como `admin@mercadoprecobom.local`. **Guarde a senha exibida e altere-a em Minha senha.** Rodar os seeders novamente preserva as senhas, as permissões personalizadas e os produtos já editados. O cadastro de usuários é restrito a quem possui `acessos.gerenciar`.

`DatabaseSeeder` inclui produtos e saldos de demonstração. Para preparar apenas perfis, permissões, categorias e terminais, sem inserir mercadorias de exemplo:

```powershell
php artisan db:seed --class=AccessControlSeeder
php artisan db:seed --class=OperationsSeeder
```

Os arquivos de interface são servidos diretamente de `public`; **não precisa manter `npm run dev` aberto**. SweetAlert2 está versionado em `public/vendor/sweetalert2`. Vite permanece disponível para os recursos do esqueleto Laravel.

## Usar o Apache do WampServer

Crie um VirtualHost `mercado-preco-bom.test` apontando para **`C:/laravel/mercado-preco-bom/public`**. Habilite `mod_rewrite` e `AllowOverride All` para que o `.htaccess` do Laravel funcione. Reinicie os serviços, ajuste `APP_URL=http://mercado-preco-bom.test` e execute `php artisan config:clear`.

A raiz pública deve ser `public`, para que `.env`, código PHP e backups permaneçam fora do acesso web. Se a criação do link `public/storage` falhar no Windows, habilite o Modo de Desenvolvedor ou execute `php artisan storage:link` em um terminal com a permissão necessária. As imagens de marca ficam em `storage/app/public`.

## Atualizar uma instalação existente

Faça backup do banco e preserve seu `.env` e sua `APP_KEY`. Atualize o código e execute:

```powershell
composer install
npm ci
php artisan migrate
php artisan db:seed --class=OperationsSeeder
php artisan optimize:clear
```

**Não use `migrate:fresh`, `db:wipe` ou `migrate:refresh` na base de trabalho: esses comandos apagam dados.** A migration operacional preserva os saldos e o histórico. Ela recusa identificadores ambíguos entre código interno e código de barras; corrija esses registros antes de migrar.

Saldos antigos de produtos com controle de validade entram como lotes `LEGADO-*` **pendentes de conferência**. Em **Estoque → Lotes e validade**, informe o lote e a validade reais e retire o bloqueio somente após conferir a mercadoria. Produtos vencidos ou bloqueados não podem ser vendidos; transferências desses lotes também são recusadas. Não se atribui uma validade fictícia a mercadorias existentes.

A migration operacional não faz rollback destrutivo de histórico; uma reversão dessa atualização exige restauração do backup correspondente.

## Fluxos disponíveis

- **Produtos:** cadastrar, pesquisar enquanto digita, consultar em modal, editar, ativar/inativar e excluir cadastros sem histórico. Código e código de barras não podem identificar produtos diferentes.
- **Estoque:** entrada, saída, contagem, ajuste, transferência depósito/gôndola, lotes, validade e histórico com responsável e justificativa. `KG` e `L` aceitam até três casas decimais; `UN` e `CX` exigem inteiros.
- **Caixa:** abra uma sessão escolhendo terminal e fundo inicial. Registre vendas; dinheiro calcula troco e as demais formas exigem confirmação simulada. Há suprimento, sangria, fechamento com conferência e justificativa de diferença. Um terminal não pode ser aberto por dois operadores.
- **Atendimento:** o carrinho é recuperado no mesmo navegador após recarregar. Vendas suspensas são guardadas no banco e podem ser retomadas pelo operador. Suspender não reserva estoque nem congela preços.
- **Vendas:** histórico, filtros, recibos, estorno integral e devolução parcial. A devolução distingue reposição de mercadoria e descarte por avaria, mantém rastreabilidade e evita devolver acima da quantidade vendida. Reembolsos em dinheiro exigem saldo na gaveta.
- **Cadastros:** categorias, clientes, fornecedores e terminais. Registros usados no histórico não podem ser apagados.
- **Compras:** recebimento com fornecedor, documento, quantidades, custo, lote e validade; entrada no depósito e atualização do custo médio. Documento repetido do mesmo fornecedor é recusado.
- **Relatórios:** valores brutos, devolvidos e líquidos, custo e margem estimada; filtro por data da venda e exportação CSV. Avarias continuam no custo. Vendas antigas sem custo registrado são identificadas como incompletas para cálculo de margem.
- **Usuários:** criação, edição, perfil, bloqueio e troca de senha. Bloqueios interrompem o acesso. Administradores não podem bloquear a si mesmos ou retirar o próprio perfil pela edição da conta.
- **Permissões:** verificadas no servidor e refletidas nos menus. Caixa consulta suas vendas; admin e gerente têm visão global. Alterações por perfil continuam configuráveis na tela de permissões.

As operações críticas usam transações e bloqueios de registros. Reenvios com a mesma chave de operação são aceitos apenas se o conteúdo for o mesmo. Alterações em produtos e saldos verificam a versão recebida, para evitar sobrescrever dados antigos.

## Recuperação de senha

O login oferece **Esqueci minha senha**. O Laravel gera um token com expiração e uso único. Com `MAIL_MAILER=log`, a mensagem fica em `storage/logs/laravel.log`; não é enviada para uma caixa de e-mail.

Para envio real, configure seu serviço SMTP no `.env` (`MAIL_MAILER=smtp`, host, porta, credenciais, remetente e esquema compatível com o provedor) e rode `php artisan config:clear`. Use uma `APP_URL` acessível ao destinatário. A entrega depende de um serviço de e-mail configurado.

## Testes

A suíte PHP utiliza SQLite em memória, separado do MySQL da aplicação:

```powershell
php -d extension=pdo_sqlite vendor/bin/phpunit
npm run test:alerts
```

Se `pdo_sqlite` já estiver habilitado no `php.ini`, use `php vendor/bin/phpunit` para evitar carregá-lo duas vezes.

Os testes de interface criam seu próprio banco SQLite temporário, credenciais aleatórias e servidor em `127.0.0.1:8097`; não gravam no banco da aplicação:

```powershell
# Com Google Chrome já instalado:
$env:MPB_UI_CHANNEL="chrome"
npm run test:ui

# Ou com o Chromium do Playwright:
npx playwright install chromium
Remove-Item Env:MPB_UI_CHANNEL -ErrorAction SilentlyContinue
npm run test:ui
```

Mude `MPB_UI_PORT` se a porta estiver ocupada. Prints de verificação ficam em `test-results/review` (ignorado pelo Git). `BrowserTestSeeder` recusa execução fora do banco temporário de testes.

## Limites atuais

Pagamentos e recibos são demonstrativos, sem integração com adquirentes, Pix bancário, NFC-e/SAT ou emissão fiscal. O sistema não substitui a homologação fiscal e financeira de um PDV comercial. A margem é estimada e não inclui tributos ou despesas operacionais.
