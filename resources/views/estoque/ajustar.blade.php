@extends('layouts.mercado')

@section('content')
<script type="application/json" id="produtos-estoque">@json($products)</script>
<div class="colunas movimentacao">
    <section class="painel">
        <div class="painel-titulo"><h2>Conferência e ajuste de saldo</h2></div>
        <form id="formulario" class="painel-corpo" method="POST" action="{{ route('estoque.store', ['operation' => 'ajustar']) }}" data-server-form>
            @csrf
            <input type="hidden" name="operation_id" value="{{ old('operation_id', (string) \Illuminate\Support\Str::uuid()) }}">
            @if(session('status'))<p class="aviso" role="status">{{ session('status') }}</p>@endif
            @if($errors->any())<div class="aviso erro" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="campos">
                <label class="campo inteiro"
                    >Produto *<select id="produto" name="product_id" required>
<option value="">Selecione um produto</option>
@foreach($products as $product)
<option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->codigo ?? $product->barras }} — {{ $product->nome }}</option>
@endforeach
</select></label
                ><label class="campo"
                    >Local do estoque<select id="local" name="local">
                        <option value="deposito" @selected(old('local') === 'deposito')>Depósito</option>
                        <option value="gondola" @selected(old('local') === 'gondola')>Gôndola</option>
                    </select></label
                ><label class="campo"
                    >Tipo de ajuste<select id="tipo" name="tipo">
                        <option value="contagem" @selected(old('tipo') === 'contagem')>Contagem física</option>
                        <option value="avaria" @selected(old('tipo') === 'avaria')>Avaria</option>
                    </select></label
                ><label class="campo"
                    >Quantidade contada / avariada *<input
                        id="quantidade" name="quantidade"
                        type="number"
                        required
                        min="0"
                        step="1"
                        value="{{ old('quantidade', 1) }}" /></label
                ><label class="campo inteiro"
                    >Justificativa do ajuste *<textarea
                        id="motivo" name="motivo"
                        required
                        placeholder="Descreva o motivo da movimentação"
                    >{{ old('motivo') }}</textarea>
                </label>
            </div>
            <div class="acoes">
                <button class="botao">Registrar ajuste</button>
            </div>
        </form>
    </section>
    <aside class="painel">
        <div class="painel-titulo">
            <h2>Prévia do saldo</h2>
            <span class="etiqueta">Conferência</span>
        </div>
        <div class="painel-corpo">
            <span class="legenda">Saldo após a operação</span
            ><strong id="saldo-novo" class="saldo-grande">—</strong>
            <dl class="resumo">
                <div>
                    <dt>Saldo atual</dt>
                    <dd id="saldo-atual"></dd>
                </div>
                <div>
                    <dt>Variação</dt>
                    <dd id="variacao"></dd>
                </div>
                <div>
                    <dt>Responsável</dt>
                    <dd>{{ auth()->user()->name }}</dd>
                </div>
            </dl>
            <p class="ajuda">
                O saldo é calculado para conferência e atualizado após registrar.
            </p>
        </div>
    </aside>
</div>
@endsection

@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H02.004</span><h2 id="historia-titulo">Ajustar estoque por contagem ou avaria</h2></div>
<p class="historia-ator"><strong>Ator:</strong> Responsável pelo estoque</p>
<p class="historia-descricao">Como responsável pelo estoque, quero ajustar o saldo após contagem física ou identificação de avarias, para refletir a quantidade real disponível.</p>
<details class="historia-criterios"><summary>Critérios de aceitação</summary>
<ul>
    <li>Selecionar produto, local e tipo de ajuste: contagem física ou avaria.</li>
    <li>Exigir quantidade válida e justificativa do ajuste.</li>
    <li>Exibir saldo atual, diferença e saldo previsto; impedir saldo negativo.</li>
    <li>A avaria deve reduzir o saldo; identificar responsável e data e hora do ajuste.</li>
</ul>
<p class="historia-limite">As movimentações são gravadas no banco, com responsável, data e hora.</p>
</details>
@endsection
