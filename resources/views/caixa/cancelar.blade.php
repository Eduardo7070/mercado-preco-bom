@extends('layouts.mercado')
@section('content')
@include('partials.caixa-config')
@if($session)
<section class="painel">
    <div class="painel-titulo"><h2>Itens da venda em andamento</h2></div>
    <p class="painel-corpo">Escolha o produto que deseja retirar da compra.</p>
    @include('partials.caixa-cupom', ['cancel'=>true])
    <div class="painel-corpo atendimento-acoes">
        <a class="botao secundario" href="{{ route('caixa.finalizar') }}">Voltar à venda</a>
        @can('caixa.pagamento')<a class="botao" data-payment-link href="{{ route('caixa.pagamento') }}">Ir para pagamento</a>@endcan
    </div>
</section>
@endif
@endsection
