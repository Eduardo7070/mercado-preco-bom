@extends('layouts.mercado')
@section('content')
@include('partials.caixa-config')
@if($session)
<div class="atendimento-grid">
    <section class="painel">
        <div class="painel-titulo"><h2>Adicionar produto</h2></div>
        <form id="adicionar" class="painel-corpo atendimento-form">
            <label class="campo">Código, código de barras ou nome
                <input id="codigo" list="catalogo" autocomplete="off" required autofocus placeholder="Leia ou digite o código">
            </label>
            <datalist id="catalogo">@foreach($products as $product)<option value="{{ $product->codigo ?? $product->barras ?? $product->nome }}">{{ $product->nome }}</option>@endforeach</datalist>
            <label class="campo">Quantidade<input id="quantidade" type="number" min="0.001" step="0.001" max="100000" value="1" required></label>
            <button type="submit" class="botao">Incluir produto</button>
        </form>
    </section>
    <section class="painel">
        <div class="painel-titulo"><h2>Produtos da venda</h2><span>Caixa aberto #{{ $session->id }}</span></div>
        @include('partials.caixa-cupom')
        <div class="painel-corpo atendimento-acoes">
            @can('caixa.cancelar')<a class="botao secundario" href="{{ route('caixa.cancelar') }}">Cancelar item</a>@endcan
            @can('caixa.pagamento')<a class="botao" id="ir-pagamento" data-payment-link href="{{ route('caixa.pagamento') }}">Ir para pagamento · F4</a>@endcan
        </div>
    </section>
</div>
<section class="painel atendimento-espera"><div class="painel-corpo">
    <details><summary>Suspender atendimento</summary>
        <div class="atendimento-form">
            <label class="campo">Identificação<input id="rotulo-suspensao" maxlength="191" placeholder="Ex.: cliente aguardando"></label>
            <button type="button" class="botao secundario" id="suspender">Salvar para continuar depois</button>
        </div>
    </details>
</div></section>
@endif
@if($drafts->isNotEmpty())
<section class="painel"><div class="painel-titulo"><h2>Atendimentos em espera</h2></div><div class="painel-corpo atendimento-form">
@foreach($drafts as $draft)
<div class="atendimento-acoes"><a href="{{ route('caixa.finalizar',['draft'=>$draft->id]) }}">{{ $draft->label }}</a>
@can('caixa.cancelar')<form method="POST" action="{{ route('caixa.drafts.destroy',$draft) }}" data-server-form data-confirm="Descartar este atendimento?">@csrf @method('DELETE')<button class="link perigo-link">Descartar</button></form>@endcan
</div>
@endforeach
</div></section>
@endif
@endsection
