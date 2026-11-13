@extends('layouts.mercado')
@section('content')
@include('partials.form-feedback')
<section class="painel">
    <div class="painel-titulo">
        <h2>{{ $session ? 'Caixa aberto #'.$session->id : 'Abrir caixa' }}</h2>
        <a href="{{ route('caixa.finalizar') }}" class="botao">Frente de caixa</a>
    </div>
    @if(!$session)
    <form method="POST" action="{{ route('caixa.sessoes.store') }}" class="painel-corpo atendimento-form" data-server-form>
        @csrf
        <input type="hidden" name="action" value="open">
        <div class="campos">
            <label class="campo">Terminal
                <select name="register_id" required>
                @foreach($registers as $register)
                    <option value="{{ $register->id }}" @selected(old('register_id')==$register->id) @disabled($register->active_session_id)>{{ $register->name }}{{ $register->active_session_id?' (em uso)':'' }}</option>
                @endforeach
                </select>
            </label>
            <label class="campo">Fundo inicial em dinheiro (R$)
                <input name="amount" type="number" min="0" step="0.01" max="999999999.99" required value="{{ old('action')==='open' ? old('amount',0) : 0 }}">
            </label>
        </div>
        <div class="atendimento-acoes"><button type="submit" class="botao">Abrir caixa</button></div>
    </form>
    @else
    <div class="painel-corpo saldo-caixa">
        <div><span>Dinheiro esperado na gaveta</span><strong>R$ {{ number_format($expected/100,2,',','.') }}</strong></div>
        <p class="ajuda">Inclui o fundo inicial, as vendas em dinheiro, os suprimentos, as sangrias e as devoluções.</p>
    </div>
    @endif
</section>
@if($session)
<div class="sessoes-paineis">
    <section class="painel" id="movimentar-dinheiro" aria-labelledby="titulo-movimentacao">
        <div class="painel-titulo"><h2 id="titulo-movimentacao">Movimentar dinheiro</h2></div>
        <form method="POST" action="{{ route('caixa.sessoes.store') }}" id="movimentar-form" class="painel-corpo atendimento-form" data-server-form>
            @csrf
            <input type="hidden" name="session_id" value="{{ $session->id }}">
            <input type="hidden" name="operation_id" value="{{ in_array(old('action'),['supply','withdraw']) ? old('operation_id',(string)\Illuminate\Support\Str::uuid()) : (string)\Illuminate\Support\Str::uuid() }}">
            <div class="campos">
                <label class="campo">O que deseja fazer?
                    <select name="action">
                        <option value="supply" @selected(old('action')!=='withdraw')>Adicionar dinheiro (suprimento)</option>
                        <option value="withdraw" @selected(old('action')==='withdraw')>Retirar dinheiro (sangria)</option>
                    </select>
                </label>
                <label class="campo">Valor da movimentação (R$)
                    <input name="amount" type="number" required min="0.01" step="0.01" max="999999999.99" value="{{ in_array(old('action'),['supply','withdraw']) ? old('amount') : '' }}">
                </label>
                <label class="campo inteiro">Motivo da movimentação
                    <textarea name="reason" required minlength="5" maxlength="2000" placeholder="Ex.: reforço de troco ou retirada para o cofre">{{ in_array(old('action'),['supply','withdraw']) ? old('reason') : '' }}</textarea>
                </label>
            </div>
            <div class="atendimento-acoes"><button type="submit" class="botao" id="registrar-dinheiro">Registrar movimentação</button></div>
        </form>
    </section>
    <section class="painel fechamento-caixa" id="fechar-caixa" aria-labelledby="titulo-fechamento">
        <div class="painel-titulo"><h2 id="titulo-fechamento">Fechar caixa</h2></div>
        <form method="POST" action="{{ route('caixa.sessoes.store') }}" id="fechar-form" class="painel-corpo atendimento-form" data-server-form data-confirm="Fechar este caixa? Novas vendas exigirão uma nova abertura.">
            @csrf
            <input type="hidden" name="action" value="close">
            <input type="hidden" name="session_id" value="{{ $session->id }}">
            <p class="fechamento-instrucao">Conte o dinheiro da gaveta e informe o valor para encerrar o atendimento deste caixa.</p>
            <div class="campos">
                <label class="campo">Dinheiro contado na gaveta (R$)
                    <input name="amount" type="number" required min="0" step="0.01" max="999999999.99" value="{{ old('action')==='close' ? old('amount') : '' }}">
                </label>
                <label class="campo">Dinheiro esperado (R$)
                    <input value="{{ number_format($expected/100,2,',','.') }}" readonly aria-label="Dinheiro esperado na gaveta">
                </label>
                <label class="campo inteiro">Observação do fechamento
                    <textarea name="reason" maxlength="2000" placeholder="Se o valor contado for diferente do esperado, explique o motivo.">{{ old('action')==='close' ? old('reason') : '' }}</textarea>
                </label>
            </div>
            <div class="atendimento-acoes"><button type="submit" class="botao perigo">Conferir e fechar caixa</button></div>
        </form>
    </section>
</div>
@endif
@if($session)<section class="painel sessoes-historico"><div class="painel-titulo"><h2>Movimentações da sessão atual</h2></div><div class="tabela-scroll"><table><thead><tr><th>Quando</th><th>Tipo</th><th>Valor</th><th>Descrição</th></tr></thead><tbody>
@forelse($entries as $entry)<tr><td>{{ $entry->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td><td>{{ ['sale'=>'Venda','refund'=>'Devolução','supply'=>'Suprimento','withdraw'=>'Sangria'][$entry->kind]??$entry->kind }}</td><td>R$ {{ number_format($entry->amount_cents/100,2,',','.') }}</td><td>{{ $entry->reason }}</td></tr>@empty<tr><td colspan="4">Nenhuma movimentação.</td></tr>@endforelse
</tbody></table></div></section>@endif
<section class="painel sessoes-historico"><div class="painel-titulo"><h2>Histórico de sessões</h2></div><div class="tabela-scroll"><table><thead><tr><th>Sessão / operador</th><th>Terminal</th><th>Abertura</th><th>Fechamento</th><th>Esperado</th><th>Contado</th><th>Diferença</th></tr></thead><tbody>
@forelse($history as $row)<tr><td>#{{ $row->id }} · {{ $row->user?->name }}</td><td>{{ $row->register?->name }}</td><td>{{ $row->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td><td>{{ $row->closed_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i')??'Aberto' }}</td><td>{{ $row->expected_cents===null?'—':'R$ '.number_format($row->expected_cents/100,2,',','.') }}</td><td>{{ $row->counted_cents===null?'—':'R$ '.number_format($row->counted_cents/100,2,',','.') }}</td><td>{{ $row->difference_cents===null?'—':'R$ '.number_format($row->difference_cents/100,2,',','.') }}<small>{{ $row->closing_note }}</small>
@can('caixa.gerenciar')
@if($row->status==='open' && $row->user_id!==auth()->id())
<details><summary>Fechar sessão do operador</summary><form method="POST" action="{{ route('caixa.sessoes.store') }}" data-server-form data-confirm="Fechar a sessão deste operador? Novas vendas nessa sessão serão recusadas.">@csrf
<input type="hidden" name="action" value="manager_close"><input type="hidden" name="session_id" value="{{ $row->id }}">
<label class="campo">Dinheiro contado (R$)<input name="amount" type="number" min="0" step="0.01" required></label>
<label class="campo">Justificativa<textarea name="reason" minlength="5" maxlength="2000" required></textarea></label><button class="botao">Confirmar fechamento</button></form></details>
@endif
@endcan
</td></tr>@empty<tr><td colspan="7">Nenhuma sessão registrada.</td></tr>@endforelse
</tbody></table></div><div class="painel-corpo">{{ $history->links() }}</div></section>
@endsection