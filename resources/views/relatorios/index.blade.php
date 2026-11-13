@extends('layouts.mercado')
@section('content')
@include('partials.form-feedback')
<section class="painel"><div class="painel-titulo"><h2>Vendas emitidas no período</h2><div class="acoes"><a class="botao" href="{{ route('relatorios.excel',request()->only('from','to')) }}" download>Exportar Excel</a><a class="botao secundario" href="{{ route('relatorios.export',request()->only('from','to')) }}" download>Exportar CSV</a></div></div>
<form class="painel-corpo" method="GET" data-server-form><div class="campos"><label class="campo">De<input name="from" type="date" value="{{ request('from') }}"></label><label class="campo">Até<input name="to" type="date" value="{{ request('to') }}"></label></div><div class="acoes"><button class="botao">Filtrar</button><a href="{{ route('relatorios.index') }}">Limpar</a></div></form>
<div class="painel-corpo"><dl class="resumo">
@foreach(['Vendas brutas'=>$gross,'Devoluções das vendas selecionadas'=>$returned,'Vendas líquidas'=>$net,'Custo vendido, incluindo avarias'=>$cost,'Margem bruta estimada'=>$margin] as $label=>$value)<div><dt>{{ $label }}</dt><dd>R$ {{ number_format($value/100,2,',','.') }}</dd></div>@endforeach
</dl><p class="ajuda">O filtro considera a data da venda. Totais incluem devoluções posteriores dessas vendas. A margem é estimada a partir do custo na venda, sem despesas ou tributos.</p>
@if($unknown)<p class="aviso">Há {{ $unknown }} itens sem custo registrado na venda; a margem não representa o resultado completo.</p>@endif
</div>
<div class="tabela-scroll"><table><thead><tr><th>Venda</th><th>Cliente</th><th>Operador</th><th>Data</th><th>Bruto</th><th>Devolvido</th><th>Líquido</th></tr></thead><tbody>
@forelse($sales as $sale)<tr><td><a href="{{ route('caixa.vendas.show',$sale) }}">#{{ $sale->id }}</a></td><td>{{ $sale->customer_name??'Não identificado' }}</td><td>{{ $sale->user->name }}</td><td>{{ $sale->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td><td>R$ {{ number_format($sale->total_cents/100,2,',','.') }}</td><td>R$ {{ number_format($sale->returned_cents/100,2,',','.') }}</td><td>R$ {{ number_format(($sale->total_cents-$sale->returned_cents)/100,2,',','.') }}</td></tr>@empty<tr><td colspan="7">Nenhuma venda neste período.</td></tr>@endforelse
</tbody></table></div><div class="painel-corpo">{{ $sales->links() }}</div></section>
@endsection