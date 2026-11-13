@extends('layouts.mercado')
@section('content')
@if(session('status'))<p class="aviso" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<p class="aviso erro" role="alert">{{ $errors->first() }}</p>@endif
<section class="painel recibo">
    <div class="painel-titulo"><h2>Recibo da venda #{{ $sale->id }}</h2><strong>{{ $sale->status === 'paid' ? 'Paga · simulação' : 'Estornada' }}</strong></div>
    <div class="painel-corpo"><dl class="resumo">
        <div><dt>Cliente</dt><dd>{{ $sale->customer_name ?: 'Consumidor não identificado' }}</dd></div>
        <div><dt>Data e hora</dt><dd>{{ $sale->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s') }}</dd></div>
        <div><dt>Operador</dt><dd>{{ $sale->user->name }}</dd></div>
        <div><dt>Terminal</dt><dd>{{ $sale->terminal }}</dd></div>
        <div><dt>Pagamento</dt><dd>{{ $sale->payment_method }} (simulado)</dd></div>
        <div><dt>Total</dt><dd>R$ {{ number_format($sale->total_cents/100,2,',','.') }}</dd></div>
        <div><dt>Recebido</dt><dd>R$ {{ number_format($sale->received_cents/100,2,',','.') }}</dd></div>
        <div><dt>Troco</dt><dd>R$ {{ number_format($sale->change_cents/100,2,',','.') }}</dd></div>
    </dl></div>
    <div class="tabela-scroll"><table><thead><tr><th>Produto</th><th>Quantidade</th><th>Valor unitário</th><th>Total</th></tr></thead><tbody>
    @foreach($sale->items as $item)<tr><td>{{ $item->name }}<small>{{ $item->code }}</small></td><td>{{ $item->quantity }} {{ $item->unit }}</td><td>R$ {{ number_format($item->unit_price_cents/100,2,',','.') }}</td><td>R$ {{ number_format($item->total_cents/100,2,',','.') }}</td></tr>@endforeach
    </tbody></table></div>
    <div class="painel-corpo"><p class="ajuda">Pagamento e recibo demonstrativos, sem valor fiscal. Nenhuma cobrança externa foi realizada.</p>
    @if($sale->status === 'refunded')<p class="aviso">Estornada em {{ $sale->refunded_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} por {{ $sale->refundedBy?->name }}.<br>Motivo: {{ $sale->refund_reason }}</p>@endif
    <div class="acoes"><a class="botao" href="{{ route('caixa.finalizar') }}">Nova venda</a><a class="botao secundario" href="{{ route('caixa.vendas') }}">Todas as vendas</a><button type="button" class="botao secundario" id="imprimir-recibo">Imprimir recibo</button></div></div>
</section>
@can('caixa.estornar')
@if($sale->status === 'paid')
<section class="painel sem-impressao"><div class="painel-titulo"><h2>Estornar venda</h2></div><form method="POST" action="{{ route('caixa.vendas.refund',$sale) }}" class="painel-corpo" data-server-form>
@csrf
<label class="campo">Motivo do estorno<textarea name="reason" required minlength="5" maxlength="2000">{{ old('reason') }}</textarea></label>
<p class="ajuda">O estorno integral devolve todos os itens à gôndola e registra o responsável. O reembolso é simulado.</p>
<button class="botao" type="submit">Confirmar estorno integral</button>
</form></section>
@endif
@endcan
@endsection