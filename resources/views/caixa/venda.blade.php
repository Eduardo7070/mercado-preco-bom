@extends('layouts.mercado')
@section('content')
@if(session('status'))<p class="aviso" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<p class="aviso erro" role="alert">{{ $errors->first() }}</p>@endif
<section class="painel recibo" @if(session('checkout_completed')===$sale->id) data-clear-cart="{{ auth()->id() }}" @endif>
    <div class="painel-titulo"><h2>Recibo da venda #{{ $sale->id }}</h2><strong>{{ $sale->status_label }}</strong></div>
    <div class="painel-corpo"><dl class="resumo">
        <div><dt>Cliente</dt><dd>{{ $sale->customer_name ?: 'Consumidor não identificado' }}</dd></div>
        <div><dt>Data e hora</dt><dd>{{ $sale->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s') }}</dd></div>
        <div><dt>Operador</dt><dd>{{ $sale->user->name }}</dd></div>
        <div><dt>Terminal</dt><dd>{{ $sale->terminal }}</dd></div>
        <div><dt>Pagamento</dt><dd>{{ $sale->payment_method }} (simulado)</dd></div>
        <div><dt>Total</dt><dd>R$ {{ number_format($sale->total_cents/100,2,',','.') }}</dd></div>
        <div><dt>Recebido</dt><dd>R$ {{ number_format($sale->received_cents/100,2,',','.') }}</dd></div>
        <div><dt>Valor devolvido</dt><dd>R$ {{ number_format($sale->returned_cents/100,2,',','.') }}</dd></div><div><dt>Valor líquido</dt><dd>R$ {{ number_format(($sale->total_cents-$sale->returned_cents)/100,2,',','.') }}</dd></div><div><dt>Troco</dt><dd>R$ {{ number_format($sale->change_cents/100,2,',','.') }}</dd></div>
    </dl></div>
    <div class="tabela-scroll"><table><thead><tr><th>Produto</th><th>Quantidade</th><th>Valor unitário</th><th>Total</th></tr></thead><tbody>
    @foreach($sale->items as $item)<tr><td>{{ $item->name }}<small>{{ $item->code }}</small></td><td>{{ $item->quantity }} {{ $item->unit }}</td><td>R$ {{ number_format($item->unit_price_cents/100,2,',','.') }}</td><td>R$ {{ number_format($item->total_cents/100,2,',','.') }}</td></tr>@endforeach
    </tbody></table></div>
    <div class="painel-corpo"><p class="ajuda">Pagamento e recibo demonstrativos, sem valor fiscal. Nenhuma cobrança externa foi realizada.</p>
    @if($sale->status === 'refunded')<p class="aviso">Estornada em {{ $sale->refunded_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} por {{ $sale->refundedBy?->name }}.<br>Motivo: {{ $sale->refund_reason }}</p>@endif
    <div class="acoes"><a class="botao" href="{{ route('caixa.finalizar') }}">Nova venda</a><a class="botao secundario" href="{{ route('caixa.vendas') }}">Todas as vendas</a><button type="button" class="botao secundario" id="imprimir-recibo">Imprimir recibo</button></div></div>
</section>
@can('caixa.estornar')
@if($sale->status !== 'refunded')
<div class="painel-corpo sem-impressao"><a class="botao secundario" href="{{ route('caixa.estornar',['sale'=>$sale->id]) }}">Estornar esta venda</a></div>
@endif
@endcan
@if($returns->isNotEmpty())
<section class="painel"><div class="painel-titulo"><h2>Histórico de estornos</h2></div><div class="painel-corpo">
@forelse($returns as $return)
<p>#{{ $return->id }} · {{ $return->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} · R$ {{ number_format($return->amount_cents/100,2,',','.') }} · {{ $return->reason }}</p>
@foreach($return->items as $returned)<p class="ajuda">{{ $returned['product_name'] }} · {{ $returned['quantity'] }} · {{ $returned['disposition']==='restock'?'Reposição no estoque':'Avaria / descarte' }}</p>@endforeach
@empty<p>Nenhuma devolução registrada.</p>@endforelse
</div></section>
@endif
@endsection