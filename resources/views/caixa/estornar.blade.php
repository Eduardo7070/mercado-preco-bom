@extends('layouts.mercado')
@section('content')
@include('partials.form-feedback')
@if(!$sale)
<section class="painel">
    <div class="painel-titulo"><h2>Escolha a venda para estornar</h2></div>
    <form method="GET" action="{{ route('caixa.estornar') }}" class="painel-corpo filtros" data-server-form>
        <label class="campo">Número da venda ou nome do cliente<input type="search" name="q" maxlength="191" value="{{ request('q') }}" placeholder="Ex.: 15 ou Maria"></label>
        <button class="botao" type="submit">Buscar venda</button>
    </form>
    <div class="tabela-scroll"><table><thead><tr><th>Venda</th><th>Cliente</th><th>Data</th><th>Valor disponível</th><th>Ação</th></tr></thead><tbody>
    @forelse($sales as $row)<tr><td>#{{ $row->id }}</td><td>{{ $row->customer_name ?: 'Consumidor final' }}</td><td>{{ $row->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td><td>R$ {{ number_format(($row->total_cents-$row->returned_cents)/100,2,',','.') }}</td><td><a class="botao secundario" href="{{ route('caixa.estornar',['sale'=>$row->id]) }}">Selecionar venda</a></td></tr>
    @empty<tr><td colspan="5">Nenhuma venda disponível para estorno.</td></tr>@endforelse
    </tbody></table></div><div class="painel-corpo">{{ $sales->links() }}</div>
</section>
@else
<section class="painel">
    <div class="painel-titulo"><h2>Estornar venda #{{ $sale->id }}</h2><a href="{{ route('caixa.estornar') }}">Escolher outra venda</a></div>
    <div class="estorno-resumo"><div><p>{{ $sale->customer_name ?: 'Consumidor final' }}</p><p>{{ $sale->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} · {{ $sale->payment_method }}</p></div><strong>R$ {{ number_format(($sale->total_cents-$sale->returned_cents)/100,2,',','.') }}</strong></div>
    @if($sale->status==='refunded')
        <div class="painel-corpo"><p>Esta venda já foi estornada.</p><a class="botao secundario" href="{{ route('caixa.vendas.show',$sale) }}">Ver recibo</a></div>
    @elseif(!$session)
        <div class="painel-corpo"><p>Abra seu caixa para registrar o reembolso desta venda.</p><a class="botao" href="{{ route('caixa.sessoes') }}">Abrir caixa</a></div>
    @else
    <form method="POST" action="{{ route('caixa.returns',$sale) }}" id="form-estorno" class="painel-corpo atendimento-form estorno-form" data-server-form data-confirm="Confirmar o estorno dos produtos informados? O valor e o estoque serão atualizados.">
        @csrf
        <input type="hidden" name="operation_id" value="{{ old('operation_id',(string)\Illuminate\Support\Str::uuid()) }}">
        <div class="campos">
            <label class="campo">O que deseja estornar?<select id="tipo-estorno"><option value="total">Toda a venda restante</option><option value="parcial" @selected($errors->any())>Apenas alguns produtos</option></select></label>
            <label class="campo">Condição dos produtos<select id="destino-estorno"><option value="restock">Podem voltar ao estoque</option><option value="discard">Estão avariados / devem ser descartados</option></select></label>
        </div>
        @php($oldReturnItems = collect(old('items', []))->keyBy('sale_item_id'))
        <div class="tabela-scroll"><table><thead><tr><th>Produto</th><th>Disponível para devolver</th><th>Quantidade a devolver</th></tr></thead><tbody>
        @foreach($sale->items as $index=>$item)
        @if($item->quantity>$item->returned_quantity)
        @php($oldReturn = $oldReturnItems->get($item->id, []))
        <tr data-return-item data-unit-price="{{ $item->unit_price_cents }}" data-remaining-cents="{{ $item->total_cents-$item->returned_cents }}" data-returned-quantity="{{ $item->returned_quantity }}" data-returned-cents="{{ $item->returned_cents }}"><td>{{ $item->name }}<input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}"><input type="hidden" data-disposition name="items[{{ $index }}][disposition]" value="{{ $oldReturn['disposition'] ?? 'restock' }}"></td><td>{{ $item->quantity-$item->returned_quantity }} {{ $item->unit }}</td><td><input class="estorno-quantidade" type="number" name="items[{{ $index }}][quantity]" min="0" max="{{ $item->quantity-$item->returned_quantity }}" step="{{ in_array($item->unit,['UN','CX'])?'1':'0.001' }}" data-return-quantity value="{{ $oldReturn['quantity'] ?? ($errors->any() ? 0 : $item->quantity-$item->returned_quantity) }}" required></td></tr>
        @endif
        @endforeach
        </tbody></table></div>
        <label class="campo">Motivo do estorno<textarea name="reason" required minlength="5" maxlength="2000" placeholder="Descreva o motivo da devolução">{{ old('reason') }}</textarea></label>
        <div class="estorno-resumo"><span>Valor a devolver</span><strong id="valor-estorno">R$ {{ number_format(($sale->total_cents-$sale->returned_cents)/100,2,',','.') }}</strong></div>
        <p class="ajuda">O reembolso é simulado. A opção de produtos avariados registra o descarte.</p>
        <div class="atendimento-acoes"><a class="botao secundario" href="{{ route('caixa.vendas.show',$sale) }}">Voltar ao recibo</a><button type="submit" class="botao" id="confirmar-estorno">Confirmar estorno</button></div>
    </form>
    @endif
</section>
@endif
@endsection
