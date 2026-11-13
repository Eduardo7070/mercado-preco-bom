@extends('layouts.mercado')
@section('content')
@include('partials.caixa-config')
@if($session)
<div class="atendimento-grid pagamento-grid">
    <section class="painel">
        <div class="painel-titulo"><h2>Conferir a compra</h2></div>
        @include('partials.caixa-cupom')
        <div class="painel-corpo atendimento-acoes"><a class="botao secundario" href="{{ route('caixa.finalizar') }}">Voltar à venda</a>@can('caixa.cancelar')<a href="{{ route('caixa.cancelar') }}">Cancelar item</a>@endcan</div>
    </section>
    <section class="painel">
        <div class="painel-titulo"><h2>Receber pagamento</h2></div>
        <form id="recebimento" class="painel-corpo atendimento-form" method="POST" action="{{ route('caixa.vendas.store') }}" data-server-form>
            @csrf
            <input type="hidden" name="cash_session_id" value="{{ $session->id }}">
            <input type="hidden" name="draft_id" value="{{ old('draft_id',$resume?->id) }}">
            <input type="hidden" name="operation_id" value="{{ old('operation_id', (string) \Illuminate\Support\Str::uuid()) }}">
            <div id="campos-itens"></div>
            <label class="campo">Forma de pagamento<select name="payment_method" id="metodo">@foreach(['Dinheiro','Pix','Débito','Crédito'] as $method)<option>{{ $method }}</option>@endforeach</select></label>
            <label class="campo" id="campo-recebido">Valor recebido (R$)<input id="recebido" name="received" type="number" min="0" max="99999999.99" step="0.01"></label>
            <label class="check" id="campo-aprovado" hidden><input type="checkbox" name="approved" value="1" id="aprovado">Pagamento simulado aprovado</label>
            <div class="pagamento-troco" id="resumo-troco">Troco <strong id="troco">R$ 0,00</strong></div>
            <details id="identificar-cliente"><summary>Identificar cliente (opcional)</summary><div class="atendimento-form">
                <label class="campo">Cliente cadastrado<select name="customer_id"><option value="">Consumidor final</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select></label>
                <label class="campo">Ou informe o nome<input name="customer_name" maxlength="191" placeholder="Nome do cliente"></label>
            </div></details>
            <p class="ajuda">Pagamento simulado. A confirmação registra a venda e atualiza o estoque.</p>
            <button class="botao" id="finalizar" type="submit">Confirmar pagamento · F4</button>
        </form>
    </section>
</div>
@endif
@endsection
