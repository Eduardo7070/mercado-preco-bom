@extends('layouts.mercado')
@section('content')
<section class="painel">
    <div class="painel-titulo"><h2>Histórico de vendas</h2><a class="botao" href="{{ route('caixa.finalizar') }}">Nova venda</a></div>
    <form method="GET" action="{{ route('caixa.vendas') }}" class="painel-corpo" data-server-form>
        <div class="campos">
            <label class="campo">Código da venda ou cliente<input name="q" value="{{ request('q') }}" placeholder="Pesquise por venda ou cliente"></label>
            <label class="campo">Situação<select name="status"><option value="">Todas</option><option value="paid" @selected(request('status') === 'paid')>Pagas</option><option value="partially_refunded" @selected(request('status') === 'partially_refunded')>Com devolução parcial</option><option value="refunded" @selected(request('status') === 'refunded')>Estornadas</option></select></label>
            <label class="campo">De<input type="date" name="from" value="{{ request('from') }}"></label>
            <label class="campo">Até<input type="date" name="to" value="{{ request('to') }}"></label>
        </div>
        @if($errors->any())<p class="aviso erro" role="alert">{{ $errors->first() }}</p>@endif
        <div class="acoes"><button class="botao">Filtrar vendas</button><a href="{{ route('caixa.vendas') }}" class="botao secundario">Limpar filtros</a></div>
    </form>
    <div class="painel-corpo"><strong>{{ $sales->total() }} vendas encontradas</strong><p>Valor líquido das vendas encontradas: R$ {{ number_format($total/100,2,',','.') }}</p></div>
    <div class="tabela-scroll"><table><thead><tr><th>Venda / caixa</th><th>Cliente</th><th>Operador</th><th>Data e hora</th><th>Pagamento</th><th>Valor</th><th>Situação</th><th>Ação</th></tr></thead><tbody>
    @forelse($sales as $sale)<tr>
        <td>#{{ $sale->id }}<small>{{ $sale->terminal }}</small></td><td>{{ $sale->customer_name ?: 'Consumidor não identificado' }}</td><td>{{ $sale->user->name }}</td><td>{{ $sale->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td><td>{{ $sale->payment_method }}</td><td>R$ {{ number_format($sale->total_cents/100,2,',','.') }}</td><td>{{ $sale->status_label }}</td><td><a href="{{ route('caixa.vendas.show',$sale) }}">Ver recibo</a>@can('caixa.estornar') @if($sale->status!=='refunded')<a class="link" href="{{ route('caixa.estornar',['sale'=>$sale->id]) }}">Estornar</a>@endif @endcan</td>
    </tr>@empty<tr><td colspan="8">Nenhuma venda encontrada para os filtros informados.</td></tr>@endforelse
    </tbody></table></div>
    <div class="painel-corpo">{{ $sales->links() }}</div>
</section>
@endsection