# Mercado Preço Bom

Protótipo acadêmico de um sistema de gestão de supermercado, desenvolvido com **Laravel 13, Blade, CSS e JavaScript puro**.

O projeto contém uma página inicial e 12 telas dos módulos **Produtos, Estoque e Frente de caixa**. Cada tela apresenta sua história de usuário e seus critérios de aceitação.

As operações usam dados fictícios e simulam validações e confirmações no navegador. **Não há login, gravação de produtos, movimentação real de estoque ou processamento de pagamentos.** Os exemplos são restaurados ao recarregar a página.

## Tecnologias e requisitos

- Windows com [WampServer 64 bits](https://wampserver.aviatechno.net/) instalado e Apache disponível.
- **PHP 8.5 recomendado**, versão utilizada no desenvolvimento. O `composer.lock` atual contém dependências que exigem **PHP 8.4.1 ou superior**; PHP 8.3 não é suficiente para instalar esse conjunto de dependências.
- [Composer 2](https://getcomposer.org/download/).
- [Git para Windows](https://git-scm.com/downloads/win).
- Navegador atualizado.

O WampServer permite instalar outras versões de PHP por meio dos seus addons oficiais. Selecione uma versão compatível no menu de PHP e, quando houver configuração por VirtualHost, confira também a versão usada pelo site.

**Não é necessário usar Docker, WSL, Node.js ou `npm run dev` para abrir as telas atuais.** O CSS e o JavaScript são servidos diretamente da pasta `public`. Os arquivos do Vite presentes no esqueleto Laravel não fazem parte desse fluxo.

> Os comandos deste guia usam **PowerShell**, inclusive quando executados no terminal integrado do VS Code. Execute os comandos do projeto na pasta que contém o arquivo `artisan`.

## 1. Preparar o PHP e o Composer

Inicie o WampServer e confira se o Apache está funcionando. Se você também utilizar o banco opcional, mantenha o MySQL ativo.

No PowerShell, confira:

```powershell
php -v
php --ini
composer --version
git --version
where.exe php
```

O PHP do terminal e o PHP utilizado pelo Apache são configurações distintas. Ambos devem usar uma versão compatível.

Se `php` não for reconhecido ou apontar para uma instalação antiga:

1. Localize a pasta da versão escolhida em `C:\wamp64\bin\php`.
2. Adicione essa pasta ao `Path` do Windows, antes de outras instalações de PHP. Exemplo de formato: `C:\wamp64\bin\php\php8.5.X` — substitua `X` pela versão realmente instalada.
3. Feche e reabra o PowerShell e o VS Code.
4. Execute `php -v` e `where.exe php` novamente.

Durante a instalação do Composer, selecione o `php.exe` dessa versão.

Confira as extensões com `php -m`. O Laravel e suas dependências utilizam, entre outras, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `PDO`, `session`, `tokenizer` e `xml`. A extensão `zip` facilita a instalação pelo Composer. Para MySQL, habilite também `pdo_mysql`; para executar os testes com SQLite, habilite `pdo_sqlite`.

Extensões já embutidas no PHP podem não aparecer como opções no menu do WampServer. Para as demais, confira as extensões habilitadas e o `php.ini` correto. Reinicie os serviços após alterar a configuração do Apache/PHP.

## 2. Baixar o projeto

Para uma instalação nova:

```powershell
New-Item -ItemType Directory -Force C:\laravel | Out-Null
Set-Location C:\laravel
git clone https://github.com/eduardo7070/mercado-preco-bom.git
Set-Location C:\laravel\mercado-preco-bom
```

Se o repositório estiver privado, sua conta do GitHub precisa ter permissão de acesso.

Se você já baixou o projeto, apenas entre na pasta existente. Não clone por cima dela. Outra pasta pode ser utilizada; ajuste os caminhos do restante do guia.

## 3. Instalar as dependências PHP

```powershell
composer install
composer check-platform-reqs
```

Use `composer install` para respeitar as versões do `composer.lock`. Não use `composer update` nem `--ignore-platform-reqs` para contornar incompatibilidades: ajuste o PHP ou as extensões indicadas pelo Composer.

## 4. Criar a configuração local

Crie o `.env` somente se ele ainda não existir:

```powershell
if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
}
```

Abra o `.env` e **substitua as linhas correspondentes**, sem criar variáveis duplicadas:

```dotenv
APP_NAME="Mercado Preço Bom"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://mercado-preco-bom.test

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Mantenha as demais linhas do modelo. `APP_DEBUG=true` é uma configuração de desenvolvimento local.

Gere a chave na primeira instalação:

```powershell
php artisan key:generate
php artisan config:clear
```

Não gere uma chave nova a cada execução. O comando grava `APP_KEY` no seu `.env`.

**Para apresentar os protótipos, não é necessário criar banco nem executar migrations.** Com sessões e cache em arquivos, as telas atuais não consultam o banco, mesmo que `DB_CONNECTION=sqlite` continue no `.env`.

O `.env` contém a configuração de cada computador e não deve ser enviado ao GitHub. O `.env.example` deve permanecer no repositório como modelo, sem credenciais reais.

## 5. Configurar o site no Apache do WampServer

Crie um VirtualHost chamado `mercado-preco-bom.test`, apontando para:

```text
C:/laravel/mercado-preco-bom/public
```

Você pode usar a opção de adicionar VirtualHost na página inicial do WampServer, acessível em `http://localhost`. Informe o nome do host e o caminho absoluto acima. Se o assistente pedir a versão do PHP, selecione a versão compatível instalada.

A raiz do site deve ser **`public`**, e não a pasta principal do projeto. Essa é a pasta de entrada HTTP do Laravel; não mova o `index.php` para fora dela. Consulte a [orientação oficial do Laravel](https://laravel.com/docs/13.x/deployment).

### Configuração manual, se necessária

Se o assistente não estiver disponível, abra o arquivo `httpd-vhosts.conf` da instalação ativa do Apache. Ele fica na subpasta `conf\extra` da versão instalada em `C:\wamp64\bin\apache`. Preserve os VirtualHosts que já existem e acrescente:

```apache
<VirtualHost *:80>
    ServerName mercado-preco-bom.test
    DocumentRoot "C:/laravel/mercado-preco-bom/public"

    <Directory "C:/laravel/mercado-preco-bom/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require local
    </Directory>
</VirtualHost>
```

Confira se o Apache carrega `conf/extra/httpd-vhosts.conf` e se o módulo `rewrite_module` está habilitado. O arquivo `public/.htaccess` já faz parte do projeto e encaminha as rotas para o Laravel.

Abra um editor **como administrador** e edite o arquivo:

```text
C:\Windows\System32\drivers\etc\hosts
```

Adicione a linha abaixo caso ela ainda não exista:

```text
127.0.0.1 mercado-preco-bom.test
```

Não adicione extensão ao arquivo `hosts`. Se utilizou o assistente do WampServer, confira se ele já criou essa entrada para evitar duplicação.

Reinicie os serviços do WampServer. Se precisar atualizar a resolução do nome, execute:

```powershell
ipconfig /flushdns
```

## 6. Abrir o sistema

Com o Apache ativo e o VirtualHost configurado:

- **Início:** [http://mercado-preco-bom.test](http://mercado-preco-bom.test)
- **Frente de caixa:** [http://mercado-preco-bom.test/caixa/finalizar](http://mercado-preco-bom.test/caixa/finalizar)

Nesse modo, o WampServer atende o site. **Não é necessário executar `php artisan serve`, `composer run dev` ou `npm run dev`.**

### Alternativa: servidor do Laravel

Para verificar o projeto antes de configurar o VirtualHost, altere `APP_URL` para `http://127.0.0.1:8000` e execute:

```powershell
php artisan config:clear
php artisan serve --host=127.0.0.1 --port=8000
```

Acesse [http://127.0.0.1:8000](http://127.0.0.1:8000). Mantenha esse terminal aberto e use `Ctrl+C` para parar. Nessa alternativa, quem atende as páginas é o servidor do PHP, não o Apache.

## Banco MySQL do WampServer — opcional

Esta etapa só é necessária se você quiser usar o banco para as tabelas padrão do Laravel. **Ela não adiciona persistência às telas de produtos, estoque ou caixa.**

1. Inicie o MySQL pelo WampServer.
2. Acesse o phpMyAdmin ou Adminer pelo menu do WampServer e entre com as credenciais da sua instalação.
3. Crie um banco vazio chamado `mercado_preco_bom`, com codificação `utf8mb4`.
4. No `.env`, substitua a configuração de banco existente:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mercado_preco_bom
DB_USERNAME=SEU_USUARIO_MYSQL
DB_PASSWORD="SUA_SENHA_MYSQL"
```

Substitua os valores de exemplo pelas credenciais locais. Confira a porta usada pelo MySQL: ela pode ser diferente de `3306`. Se o seu usuário local realmente não tiver senha, use `DB_PASSWORD=`.

Na pasta do projeto, execute:

```powershell
php artisan config:clear
php artisan migrate
```

As migrations existentes criam as tabelas padrão de usuários, sessões, cache e filas. Para usar sessões e cache no banco, altere `SESSION_DRIVER=database` e `CACHE_STORE=database` **depois** de executar as migrations, e rode `php artisan config:clear` novamente.

## Telas disponíveis

| História | Tela | Caminho |
| --- | --- | --- |
| — | Início | `/` |
| H01.001 | Cadastrar produto | `/produtos/cadastrar` |
| H01.002 | Editar produto | `/produtos/editar` |
| H01.003 | Inativar produto | `/produtos/inativar` |
| H01.004 | Consultar produtos | `/produtos/consultar` |
| H02.001 | Entrada de mercadoria | `/estoque/entrada` |
| H02.002 | Saída de mercadoria | `/estoque/saida` |
| H02.003 | Consultar saldos | `/estoque/consultar` |
| H02.004 | Ajustar estoque | `/estoque/ajustar` |
| H03.001 | Finalizar venda / PDV | `/caixa/finalizar` |
| H03.002 | Cancelar item | `/caixa/cancelar` |
| H03.003 | Selecionar pagamento | `/caixa/pagamento` |
| H03.004 | Estornar venda | `/caixa/estornar` |

No caixa, os códigos `1001` e `1002` representam arroz e leite. Use **F2** para focar a leitura de código e **F4** para conferir o pagamento nas telas correspondentes.

## Organização dos arquivos

```text
resources/views/
├── layouts/mercado.blade.php  # Layout e carregamento dos arquivos
├── inicio.blade.php
├── produtos/                 # HTML Blade e história de cada tela
├── estoque/
└── caixa/

public/css/
├── comum.css                 # Identidade visual compartilhada
├── inicio.css
├── produtos/                 # Um CSS por tela
├── estoque/
└── caixa/

public/js/
├── comum.js                  # Dados fictícios e funções compartilhadas
├── inicio.js
├── produtos/                 # Um JavaScript por tela
├── estoque/
└── caixa/

routes/web.php                # Rotas de apresentação
```

Por exemplo, a tela de estorno utiliza:

- `resources/views/caixa/estornar.blade.php`;
- `public/css/caixa/estornar.css`;
- `public/js/caixa/estornar.js`;
- o layout e os arquivos `comum.css` e `comum.js` compartilhados.

## Verificações

```powershell
composer check-platform-reqs
php artisan route:list --except-vendor
php artisan view:cache
php artisan test --compact
```

`view:cache` verifica a compilação Blade. Os testes PHP atuais verificam o funcionamento básico da aplicação; não substituem a conferência das interações no navegador. Existe também um teste de interface em `tests/ui/prototipos.cjs`, que usa Playwright e depende de configuração própria do navegador e da URL local.

## Problemas frequentes

| Problema | O que conferir |
| --- | --- |
| PHP incompatível no Composer | Execute `php -v` e `where.exe php`. Use PHP 8.4.1 ou superior para o lock atual; PHP 8.5 é recomendado. |
| `php` ou `composer` não reconhecido | Instale a ferramenta, ajuste o `Path` e reabra o terminal/VS Code. |
| Extensão PHP ausente | Confira `php --ini` e `php -m`; habilite a extensão solicitada no PHP do terminal e, quando aplicável, no Apache. |
| `vendor/autoload.php` não encontrado | Execute `composer install` na pasta do projeto. |
| Chave de aplicação ausente | Crie o `.env` e execute `php artisan key:generate` na primeira instalação. |
| Erro de SQLite ou tabela `sessions` inexistente | No modo protótipo, use `SESSION_DRIVER=file`, `CACHE_STORE=file` e `QUEUE_CONNECTION=sync`; depois execute `php artisan config:clear`. |
| `could not find driver` ao usar MySQL | Habilite `pdo_mysql` no PHP correto e reinicie os serviços. |
| Conexão MySQL recusada | Confira serviço, porta, banco e credenciais do `.env`. |
| Domínio local não encontrado | Confira a entrada no arquivo `hosts` e reinicie os serviços. |
| Erro 403 ou listagem de diretório | Confira o VirtualHost, o caminho terminado em `public`, as permissões e a configuração de acesso local. |
| Início abre, mas outras rotas dão 404 | Confira `rewrite_module`, `AllowOverride All` e o arquivo `public/.htaccess`. |
| CSS ou JavaScript não carregam | Confira o DocumentRoot em `public`, a aba Rede do navegador e recarregue com `Ctrl+F5`. As telas atuais não precisam de build Vite. |
| Erro 500 | Consulte `storage/logs/laravel.log`, os requisitos de plataforma e as permissões de escrita em `storage` e `bootstrap/cache`. |
| Apache não inicia | Confira os logs do WampServer e se outro programa ocupa a porta 80. Se usar outra porta, ajuste o VirtualHost, a URL e `APP_URL`. |

## Atualizar uma instalação existente

Com suas alterações locais já salvas em commit ou separadas antes da atualização:

```powershell
git pull
composer install
php artisan config:clear
php artisan view:clear
```

Preserve seu `.env` e sua `APP_KEY`. Se estiver usando o banco opcional e houver novas migrations, execute `php artisan migrate`.

## Referências

- [WampServer: instaladores e addons oficiais](https://wampserver.aviatechno.net/)
- [Laravel: configuração do servidor e pasta pública](https://laravel.com/docs/13.x/deployment)
- [Composer: instalação no Windows](https://getcomposer.org/doc/00-intro.md#installation-windows)
