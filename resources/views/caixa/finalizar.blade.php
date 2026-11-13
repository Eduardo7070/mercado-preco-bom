@extends('layouts.mercado')
@section('content')
<script type="application/json" id="pdv-products">@json($products)</script>
<script type="application/json" id="pdv-old-items">@json(old('items', []))</script>
@if($errors->any())<div class="aviso erro" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="pdv" aria-label="Terminal de caixa">
    <header class="pdv-barra"><strong>PREÇO BOM · PDV</strong><span>Caixa 01 | {{ auth()->user()->name }} | {{ now()->timezone('America/Sao_Paulo')->format('d/m/Y') }}</span></header>
    <div class="pdv-situacao">CAIXA ABERTO</div>
    <div class="pdv-corpo">
        <section class="pdv-leitura">
            <div class="pdv-logo"><strong>Mercado Preço Bom</strong><small>Atendimento no caixa</small></div>
            <form id="adicionar">
                <label class="pdv-campo"><span>Código, código de barras ou nome</span><input id="codigo" list="catalogo" autocomplete="off" required autofocus placeholder="Leia ou procure um produto"></label>
                <datalist id="catalogo">@foreach($products as $p)<option value="{{ $p->codigo ?? $p->barras ?? $p->nome }}">{{ $p->nome }}</option>@endforeach</datalist>
                <label class="pdv-campo"><span>Quantidade (unidade comercial inteira)</span><input id="quantidade" type="number" min="1" step="1" max="100000" value="1" required></label>
                <button class="botao" type="submit">Incluir produto</button>
            </form>
            <p class="ajuda">Somente produtos ativos com saldo na gôndola. F2: pesquisar produto.</p>
            <a href="{{ route('caixa.vendas') }}" class="botao secundario">Consultar vendas</a>
        </section>
        <section class="pdv-cupom">
            <h2>Lista de produtos</h2>
            <div class="cupom-info"><span>Nova venda</span><span id="itens">0 itens</span></div>
            <div class="tabela-scroll"><table><thead><tr><th>Produto</th><th>Qtd.</th><th>Unitário</th><th>Total</th><th>Ação</th></tr></thead><tbody id="cupom"></tbody></table></div>
            <p id="carrinho-vazio" class="vazio">Inclua um produto para começar o atendimento.</p>
            <p id="pdv-aviso" class="aviso" role="status" hidden></p>
        </section>
        <form id="recebimento" class="pdv-pagamento" method="POST" action="{{ route('caixa.vendas.store') }}" data-server-form>
            @csrf
            <input type="hidden" name="operation_id" value="{{ old('operation_id', (string) \Illuminate\Support\Str::uuid()) }}">
            <div id="campos-itens"></div>
            <div class="pdv-numero"><span>Total a pagar</span><strong id="total">R$ 0,00</strong></div>
            <label class="pdv-campo"><span>Nome do cliente (opcional)</span><input name="customer_name" value="{{ old('customer_name') }}" maxlength="191" placeholder="Consumidor não identificado"></label>
            <label class="pdv-campo"><span>Forma de pagamento</span><select name="payment_method" id="metodo">
                @foreach(['Dinheiro','Pix','Débito','Crédito'] as $method)<option @selected(old('payment_method') === $method)>{{ $method }}</option>@endforeach
            </select></label>
            <label class="pdv-campo" id="campo-recebido"><span>Valor recebido (R$)</span><input id="recebido" name="received" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('received') }}"></label>
            <label class="check" id="campo-aprovado" hidden><input type="checkbox" name="approved" value="1" id="aprovado">Confirmar aprovação simulada</label>
            <div class="pdv-numero troco"><span>Troco</span><strong id="troco">R$ 0,00</strong></div>
            @can('caixa.pagamento')<button class="botao" id="finalizar" type="submit">Finalizar venda · F4</button>@endcan
            <p class="ajuda">Pagamento simulado. A venda e a baixa do estoque são reais no sistema. Nenhuma cobrança externa.</p>
        </form>
    </div>
</section>
<section class="painel ultimas-vendas">
    <div class="painel-titulo"><h2>Últimas vendas</h2><a href="{{ route('caixa.vendas') }}">Ver todas</a></div>
    <div class="tabela-scroll"><table><thead><tr><th>Venda</th><th>Cliente</th><th>Quando</th><th>Valor</th><th>Situação</th></tr></thead><tbody>
    @forelse($recent as $sale)<tr><td><a href="{{ route('caixa.vendas.show',$sale) }}">#{{ $sale->id }}</a></td><td>{{ $sale->customer_name ?: 'Consumidor não identificado' }}</td><td>{{ $sale->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td><td>R$ {{ number_format($sale->total_cents / 100,2,',','.') }}</td><td>{{ $sale->status === 'paid' ? 'Paga (simulada)' : 'Estornada' }}</td></tr>
    @empty<tr><td colspan="5">Nenhuma venda registrada.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
@section('historia')
<div class="historia-cabecalho"><span class="historia-codigo">H03.001</span><h2 id="historia-titulo">Finalizar venda</h2></div>
<p class="historia-descricao">Como operador de caixa, quero registrar os produtos comprados e confirmar um pagamento simulado, para emitir o recibo e atualizar o estoque.</p>
@endsection