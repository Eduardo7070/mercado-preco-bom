@extends('layouts.mercado')

@section('content')
<section class="boas-vindas">
    <div>
        <span class="etiqueta">{{ $profileName }} · {{ $today }}</span>
        <h2>{{ $greeting }}, {{ auth()->user()->name }}</h2>
        <p>{{ $scopeLabel }}. Dados atualizados em {{ $updatedAt }} (Brasília).</p>
    </div>
    @can('caixa.finalizar')
        <a class="botao" href="{{ route('caixa.finalizar') }}">Abrir frente de caixa</a>
    @endcan
</section>

<div class="indicadores">
    @forelse($metrics as $metric)
        <div>
            <span>{{ $metric['label'] }}</span>
            <strong>{{ $metric['value'] }}</strong>
            <small>{{ $metric['description'] }}</small>
        </div>
    @empty
        <p class="aviso">Seu perfil ainda não possui acesso aos indicadores. Solicite as permissões ao administrador.</p>
    @endforelse
</div>

@if($sales !== null)
<section class="painel inicio-secao">
    <div class="painel-titulo"><h2>Últimas vendas</h2><a href="{{ route('caixa.vendas') }}">Consultar todas</a></div>
    <div class="tabela-scroll">
        <table>
            <thead><tr><th>Venda</th><th>Cliente</th><th>Operador</th><th>Data e hora</th><th>Valor</th><th>Situação</th><th>Ação</th></tr></thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td>#{{ $sale->id }}</td>
                        <td>{{ $sale->customer_name ?: 'Consumidor não identificado' }}</td>
                        <td>{{ $sale->user->name }}</td>
                        <td>{{ $sale->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                        <td>R$ {{ number_format($sale->total_cents / 100,2,',','.') }}</td>
                        <td>{{ $sale->status_label }}</td>
                        <td><a href="{{ route('caixa.vendas.show',$sale) }}">Detalhes</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">Nenhuma venda registrada neste escopo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<section class="painel inicio-secao">
    <div class="painel-titulo"><h2>Pagamentos de hoje</h2><span class="etiqueta">Sem estornos</span></div>
    <div class="tabela-scroll"><table>
        <thead><tr><th>Forma de pagamento</th><th>Vendas pagas</th><th>Total</th></tr></thead>
        <tbody>
        @forelse($payments as $payment)
            <tr><td>{{ $payment->payment_method }} (simulado)</td><td>{{ $payment->sales_count }}</td><td>R$ {{ number_format($payment->amount_cents / 100,2,',','.') }}</td></tr>
        @empty
            <tr><td colspan="3">Nenhum pagamento registrado hoje.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endif

@if($lowStock !== null)
<section class="painel inicio-secao">
    <div class="painel-titulo"><div><h2>Reposição de gôndola</h2><p class="ajuda">Até 8 produtos ativos com saldo de 5 ou menos na gôndola.</p></div><a href="{{ route('estoque.consultar') }}">Consultar estoque</a></div>
    <div class="tabela-scroll"><table>
        <thead><tr><th>Produto</th><th>Depósito</th><th>Gôndola</th><th>Saldo total</th></tr></thead><tbody>
        @forelse($lowStock as $product)
            <tr><td>{{ $product->nome }}<small>{{ $product->codigo ?? $product->barras }}</small></td><td>{{ $product->deposito }} {{ $product->unidade }}</td><td>{{ $product->gondola }} {{ $product->unidade }}</td><td>{{ $product->deposito + $product->gondola }} {{ $product->unidade }}</td></tr>
        @empty
            <tr><td colspan="4">Nenhum produto ativo precisa de reposição pelo limite atual.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endif

<div class="modulos">
    @foreach($modules as $group)
        <section class="painel">
            <div class="painel-titulo"><h2>{{ $group['title'] }}</h2></div>
            <div class="painel-corpo">
                <p>{{ $group['description'] }}</p>
                @foreach($group['links'] as $link)
                    <a href="{{ route($link['route']) }}">{{ $link['label'] }}</a>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
<p class="ajuda inicio-nota">Indicadores calculados a partir dos registros do banco. Vendas e estoque são registrados; os pagamentos são simulados. O dia considera o horário de Brasília.</p>
@endsection