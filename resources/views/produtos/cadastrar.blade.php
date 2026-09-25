@extends('layouts.mercado')

@section('content')
<div class="colunas produto-formulario">
    <section class="painel">
        <div class="painel-titulo">
            <h2>Dados do produto</h2>
            <span class="etiqueta">Cadastro de produtos</span>
        </div>
        <form id="formulario" class="painel-corpo">
            <p class="ajuda">
                Informe código interno ou código de barras. Campos com * são
                obrigatórios.
            </p>
            <div class="campos">
                <label class="campo"
                    >Código interno<input
                        id="codigo"
                        type="text"
                        placeholder="Ex.: 1006" /></label
                ><label class="campo"
                    >Código de barras<input
                        id="barras"
                        type="text"
                        inputmode="numeric"
                        placeholder="Leia ou digite o código" /></label
                ><label class="campo inteiro"
                    >Descrição *<input
                        id="nome"
                        required
                        placeholder="Nome e apresentação do produto" /></label
                ><label class="campo"
                    >Categoria *<select id="categoria" required>
                        <option value="">Selecione</option>
                        <option>Mercearia</option>
                        <option>Laticínios</option>
                        <option>Limpeza</option>
                        <option>Hortifruti</option>
                        <option>Bebidas</option>
                    </select></label
                ><label class="campo"
                    >Unidade *<select id="unidade">
                        <option>UN</option>
                        <option>KG</option>
                        <option>L</option>
                        <option>CX</option>
                    </select></label
                ><label class="campo"
                    >Preço de venda (R$) *<input
                        id="preco"
                        type="number"
                        required
                        min="0.01"
                        step="0.01" /></label
                ><label class="check"
                    ><input id="validade" type="checkbox" /> Controlar lote e
                    validade</label
                >
            </div>
            <div class="acoes">
                <button id="limpar" type="button" class="botao secundario">
                    Limpar campos</button
                ><button class="botao">Revisar cadastro</button>
            </div>
        </form>
    </section>
    <aside class="painel">
        <div class="painel-titulo"><h2>Informações do cadastro</h2></div>
        <div class="painel-corpo">
            <dl class="resumo">
                <div>
                    <dt>Situação</dt>
                    <dd id="situacao">Ativo</dd>
                </div>
                <div>
                    <dt>Saldo inicial</dt>
                    <dd id="saldo">0 UN</dd>
                </div>
                <div>
                    <dt>Responsável</dt>
                    <dd>Mariana Oliveira</dd>
                </div>
                <div>
                    <dt>Data de exemplo</dt>
                    <dd>25/09/2026 · 09:30</dd>
                </div>
            </dl>
            <p class="ajuda">
                O produto começa ativo e sem estoque. As quantidades são
                informadas na entrada de mercadorias.
            </p>
        </div>
    </aside>
</div>
@endsection

@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H01.001</span><h2 id="historia-titulo">Cadastrar produto</h2></div>
<p class="historia-ator"><strong>Ator:</strong> Responsável pelo cadastro</p>
<p class="historia-descricao">Como responsável pelo cadastro, quero cadastrar produtos com suas informações comerciais, para disponibilizá-los nas operações do supermercado.</p>
<details class="historia-criterios"><summary>Critérios de aceitação</summary>
<ul>
    <li>Informar código interno ou código de barras único, descrição, categoria, unidade e preço de venda maior que zero.</li>
    <li>Indicar se o produto exige controle de lote e validade.</li>
    <li>O produto cadastrado deve iniciar ativo e com saldo de estoque igual a zero.</li>
    <li>Validar os campos obrigatórios e impedir identificadores duplicados.</li>
</ul>
<p class="historia-limite">Neste protótipo, as operações são apenas simuladas. Não há gravação de dados, cobrança ou movimentação real de estoque.</p>
</details>
@endsection
