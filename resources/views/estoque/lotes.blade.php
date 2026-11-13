@extends('layouts.mercado')

@section('content')
@include('partials.form-feedback')
<section class="painel">
    <div class="painel-titulo">
        <h2>Lotes, validade e bloqueios</h2>
        <a href="{{ route('estoque.consultar') }}">Consultar saldos</a>
    </div>
    <div class="painel-corpo">
        <p class="ajuda">Lotes vencidos ou bloqueados não são vendidos. Saldos antigos sem rastreabilidade de validade precisam ser conferidos antes da liberação.</p>
        <form method="GET" class="filtros filtros-lotes" data-server-form>
            <label class="campo">Produto ou lote
                <input type="search" name="q" value="{{ request('q') }}" maxlength="191">
            </label>
            <button type="submit" class="botao secundario">Pesquisar</button>
        </form>
    </div>
    <div class="tabela-scroll">
        <table>
            <thead>
                <tr><th>Produto / local</th><th>Lote</th><th>Saldo</th><th>Validade</th><th>Situação</th><th>Ação</th></tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                <tr>
                    <td>{{ $batch->nome }}<small>{{ $batch->local }}</small></td>
                    <td>{{ $batch->lot }}</td>
                    <td>{{ $batch->quantity }} {{ $batch->unidade }}</td>
                    <td>{{ $batch->expires_on?->format('d/m/Y') ?? 'Não informada' }}</td>
                    <td>{{ $batch->quarantine ? 'Bloqueado' : ($batch->expires_on && $batch->expires_on->toDateString() < now('America/Sao_Paulo')->toDateString() ? 'Vencido' : 'Liberado') }}</td>
                    <td>
                        @can('estoque.ajustar')
                        <button type="button" class="botao secundario regularizar-lote" aria-haspopup="dialog"
                            data-id="{{ $batch->id }}" data-url="{{ route('estoque.lotes.update', $batch) }}"
                            data-produto="{{ $batch->nome }}" data-local="{{ $batch->local }}"
                            data-saldo="{{ $batch->quantity }} {{ $batch->unidade }}"
                            data-lote="{{ $batch->lot }}" data-validade="{{ $batch->expires_on?->toDateString() }}"
                            data-bloqueado="{{ $batch->quarantine ? '1' : '0' }}" data-exige-validade="{{ $batch->validade ? '1' : '0' }}">
                            Regularizar lote
                        </button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">Nenhum lote com saldo encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="painel-corpo">{{ $batches->links() }}</div>
</section>

@can('estoque.ajustar')
<dialog id="modal-lote" aria-labelledby="modal-lote-titulo" aria-describedby="modal-lote-contexto">
    <form id="form-lote" method="POST" data-server-form>
        @csrf
        @method('PUT')
        <input type="hidden" name="regularized_batch" value="{{ old('regularized_batch') }}">
        <div class="dialogo-cabecalho">
            <h2 id="modal-lote-titulo">Regularizar lote</h2>
            <button type="button" class="fechar" data-fechar-lote aria-label="Fechar">×</button>
        </div>
        <p id="modal-lote-contexto" class="lote-contexto"></p>
        @if($errors->any() && old('regularized_batch'))
        <div class="aviso erro" role="alert">
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        @endif
        <div class="campos">
            <label class="campo">Lote real *
                <input name="lot" required maxlength="191" value="{{ old('lot') }}">
            </label>
            <label class="campo"><span>Validade real<span id="validade-obrigatoria" hidden> *</span></span>
                <input name="expires_on" type="date" value="{{ old('expires_on') }}">
            </label>
            <label class="campo inteiro">Situação do lote *
                <select name="quarantine" required>
                    <option value="1" @selected(old('quarantine', '1') === '1')>Bloqueado</option>
                    <option value="0" @selected(old('quarantine') === '0')>Liberado</option>
                </select>
            </label>
            <label class="campo inteiro">Motivo da conferência *
                <textarea name="reason" required minlength="5" maxlength="2000" rows="3" placeholder="Descreva o que foi conferido ou corrigido">{{ old('reason') }}</textarea>
            </label>
        </div>
        <div class="acoes">
            <button type="button" class="botao secundario" data-fechar-lote>Cancelar</button>
            <button type="submit" class="botao">Salvar conferência</button>
        </div>
    </form>
</dialog>
@endcan
@endsection