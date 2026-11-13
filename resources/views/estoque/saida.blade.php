@extends('layouts.mercado')

@section('content')
<script type="application/json" id="produto-estoque-config">@json($productSearch)</script>
<div class="colunas movimentacao">
    <section class="painel">
        <div class="painel-titulo"><h2>Registrar movimentação</h2></div>
        <form id="formulario" class="painel-corpo" method="POST" action="{{ route('estoque.store', ['operation' => 'saida']) }}" data-server-form>
            @csrf
            <input type="hidden" name="operation_id" value="{{ old('operation_id', (string) \Illuminate\Support\Str::uuid()) }}">
            @if(session('status'))<p class="aviso" role="status">{{ session('status') }}</p>@endif
            @if($errors->any())<div class="aviso erro" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="campos">
                <label class="campo inteiro" for="tipo">Tipo de movimentação
                    <select id="tipo" name="tipo">
                        @can('estoque.saida')
                        <option value="saida" @selected(old('tipo', request('tipo', 'saida')) === 'saida')>Retirada / devolução ao fornecedor</option>
                        @endcan
                        @can('estoque.ajustar')
                        <option value="avaria" @selected(old('tipo', request('tipo')) === 'avaria')>Perda / avaria</option>
                        <option value="contagem" @selected(old('tipo', request('tipo')) === 'contagem')>Corrigir saldo por contagem</option>
                        @endcan
                    </select>
                </label>
                <p id="tipo-ajuda" class="ajuda inteiro"></p>
                @include('partials.produto-estoque')
                <label class="campo"
                    >Local do estoque<select id="local" name="local">
                        <option value="deposito" @selected(old('local') === 'deposito')>Depósito</option>
                        <option value="gondola" @selected(old('local') === 'gondola')>Gôndola</option>
                    </select></label
                ><label class="campo"
                    ><span id="quantidade-label">Quantidade a retirar *</span><input
                        id="quantidade" name="quantidade"
                        type="number"
                        required
                        min="0.001"
                        step="0.001"
                        value="{{ old('quantidade', 1) }}" /></label
                ><label class="campo inteiro"
                    >Justificativa *<textarea
                        id="motivo" name="motivo"
                        required
                        placeholder="Descreva o motivo da movimentação"
                    >{{ old('motivo') }}</textarea>
                </label>
            </div>
            <div class="acoes">
                <button class="botao">Registrar movimentação</button>
            </div>
        </form>
    </section>
    <aside class="painel">
        <div class="painel-titulo">
            <h2>Prévia do saldo</h2>
            <span class="etiqueta">Conferência</span>
        </div>
        <div class="painel-corpo">
            <span class="legenda">Saldo após a operação</span
            ><strong id="saldo-novo" class="saldo-grande">—</strong>
            <dl class="resumo">
                <div>
                    <dt>Saldo atual</dt>
                    <dd id="saldo-atual"></dd>
                </div>
                <div>
                    <dt>Variação</dt>
                    <dd id="variacao"></dd>
                </div>
                <div>
                    <dt>Responsável</dt>
                    <dd>{{ auth()->user()->name }}</dd>
                </div>
            </dl>
            <p class="ajuda">
                O saldo é calculado para conferência e atualizado após registrar.
            </p>
        </div>
    </aside>
</div>
@endsection

