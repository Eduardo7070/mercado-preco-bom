@php
    $config = [
        'userId' => auth()->id(),
        'canCancel' => auth()->user()->can('caixa.cancelar'),
        'draftUrl' => route('caixa.drafts.store'),
        'saleUrl' => route('caixa.finalizar'),
        'paymentUrl' => route('caixa.pagamento'),
        'csrf' => csrf_token(),
        'hasOld' => session()->hasOldInput('items'),
        'hasResume' => (bool) $resume,
        'discardedDraftId' => session('discarded_draft'),
        'draftId' => old('draft_id', $resume?->id),
        'operationId' => old('operation_id', (string) \Illuminate\Support\Str::uuid()),
        'customerName' => old('customer_name', $resume?->customer_name ?? ''),
        'customerId' => old('customer_id', ''),
        'method' => old('payment_method', 'Dinheiro'),
        'received' => old('received', ''),
    ];
@endphp
<script type="application/json" id="pdv-products">@json($products)</script>
<script type="application/json" id="pdv-old-items">@json(old('items', $resume?->items ?? []))</script>
<script type="application/json" id="pdv-config">@json($config)</script>
@include('partials.form-feedback')
@if(!$session)
<section class="painel"><div class="painel-corpo">
    <h2>Abra o caixa para começar</h2>
    <p>Escolha o terminal e informe o valor inicial em dinheiro.</p>
    <a class="botao" href="{{ route('caixa.sessoes') }}">Abrir caixa</a>
</div></section>
@endif
