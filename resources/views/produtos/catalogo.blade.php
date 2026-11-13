@extends('layouts.mercado')
@section('content')
@php
$config = [
    'stock'=>$stock,
    'search'=>route('produtos.search'),
    'show'=>route('produtos.show',['product'=>'__ID__']),
    'update'=>route('produtos.update',['product'=>'__ID__']),
    'status'=>route('produtos.status',['product'=>'__ID__']),
    'destroy'=>route('produtos.destroy',['product'=>'__ID__']),
    'balances'=>route('estoque.balances',['product'=>'__ID__']),
    'transfer'=>route('estoque.transfer',['product'=>'__ID__']),
    'history'=>route('estoque.history',['product'=>'__ID__']),
    'csrf'=>csrf_token(),
    'canEdit'=>auth()->user()->can($stock?'estoque.ajustar':'produtos.editar'),
    'canStatus'=>!$stock && auth()->user()->can('produtos.inativar'),
    'canDelete'=>auth()->user()->can($stock?'estoque.ajustar':'produtos.excluir'),
    'canHistory'=>auth()->user()->can('estoque.consultar'),
];
@endphp
<script type="application/json" id="catalogo-config">@json($config)</script>
<section class="painel consulta">
    <div class="painel-titulo"><h2>{{ $stock ? 'Saldos por local' : 'Lista de produtos' }}</h2><span id="contador" class="etiqueta"></span></div>
    <div class="filtros">
        <label class="campo">Código, código de barras ou nome<input id="busca" type="search" maxlength="191" placeholder="Digite para pesquisar..." autocomplete="off"></label>
        <label class="campo">Categoria<select id="categoria-filtro"><option value="">Todas</option>@foreach($catalogCategories as $category)<option>{{ $category }}</option>@endforeach</select></label>
        <label class="campo">Situação<select id="filtro"><option value="todos">Todos</option><option value="ativos">Ativos</option><option value="inativos">Inativos</option></select></label>
        @if($stock)<label class="campo">Saldo<select id="saldo-filtro"><option value="todos">Todos</option><option value="zero">Saldo total zerado</option><option value="baixo">Gôndola com 5 ou menos</option></select></label>@endif
        <button id="limpar-filtros" type="button" class="botao secundario">Limpar filtros</button>
    </div>
    <div class="painel-corpo catalogo-atalhos">
        @if(!$stock)
            @can('produtos.cadastrar')<a class="botao" href="{{ route('produtos.cadastrar') }}">Cadastrar produto</a>@endcan
        @else
            @can('estoque.entrada')<a class="botao" href="{{ route('estoque.entrada') }}">Registrar entrada</a>@endcan
            @can('estoque.saida')<a class="botao secundario" href="{{ route('estoque.saida') }}">Registrar saída</a>@endcan
        @endif
        <p class="ajuda">{{ $stock ? 'Os saldos são alterados por movimentações rastreáveis. Zerar saldo não apaga o histórico.' : 'Exclusão disponível somente para produtos sem saldo, vendas ou movimentações. Para os demais, utilize a inativação.' }}</p>
    </div>
    <p id="busca-status" class="aviso" role="status" aria-live="polite"></p>
    <div class="tabela-scroll"><table>
        <thead><tr><th>Produto</th><th>Categoria</th>
        @if($stock)<th>Depósito</th><th>Gôndola</th><th>Total</th>@else<th>Unidade</th><th>Preço</th>@endif
        <th>Situação</th><th>Ações</th></tr></thead><tbody id="resultados"></tbody>
    </table></div>
    <p id="vazio" class="vazio" hidden>Nenhum produto encontrado. Cadastre um produto ou ajuste os filtros.</p>
    <div class="acoes catalogo-paginacao"><button type="button" id="pagina-anterior" class="botao secundario">Anterior</button><span id="pagina-atual"></span><button type="button" id="pagina-proxima" class="botao secundario">Próxima</button></div>
</section>

<dialog id="modal-detalhes" class="modal-consulta" aria-labelledby="titulo-detalhes">
    <div class="dialogo-cabecalho"><h2 id="titulo-detalhes">Detalhes</h2><button type="button" class="fechar" data-close aria-label="Fechar">×</button></div>
    <dl id="dados-detalhes" class="resumo modal-resumo"></dl>
    <div id="auditoria"></div>
    <section id="historico-area" hidden><h3>Histórico de movimentações</h3>
        <div class="tabela-scroll"><table><thead><tr><th>Quando</th><th>Operação / local</th><th>Quantidade</th><th>Saldo anterior → final</th><th>Responsável / motivo</th></tr></thead><tbody id="historico-itens"></tbody></table></div>
        <p id="historico-status" role="status"></p><div class="acoes"><button type="button" id="historico-anterior" class="botao secundario">Anterior</button><button type="button" id="historico-proxima" class="botao secundario">Próxima</button></div>
    </section>
    <div class="acoes"><button type="button" data-close class="botao secundario">Fechar</button></div>
</dialog>

<dialog id="modal-editar" class="modal-consulta" aria-labelledby="titulo-editar">
    <form id="form-editar" data-server-form>
        <div class="dialogo-cabecalho"><h2 id="titulo-editar">Editar</h2><button type="button" class="fechar" data-close aria-label="Fechar">×</button></div>
        <div class="campos">
        @if($stock)
            <label class="campo">Saldo depósito<input name="deposito" type="number" required min="0" max="1000000000" step="0.001"></label>
            <label class="campo">Saldo gôndola<input name="gondola" type="number" required min="0" max="1000000000" step="0.001"></label>
            <label class="campo inteiro">Justificativa *<textarea name="motivo" required minlength="5" maxlength="2000"></textarea></label>
        @else
            <label class="campo">Código interno (preservado)<input name="codigo" readonly></label>
            <label class="campo">Código de barras<input name="barras" maxlength="80" pattern="[0-9]*"></label>
            <label class="campo inteiro">Descrição *<input name="nome" required maxlength="191"></label>
            <label class="campo">Categoria *<select name="categoria" required>@foreach($catalogCategories as $category)<option>{{ $category }}</option>@endforeach</select></label>
            <label class="campo">Unidade *<select name="unidade">@foreach(['UN','KG','L','CX'] as $unit)<option>{{ $unit }}</option>@endforeach</select></label>
            <label class="campo">Preço (R$) *<input name="preco" type="number" min="0.01" step="0.01" max="9999999999.99" required></label>
            <label class="campo">Custo unitário (R$)<input name="custo" type="number" min="0" step="0.01"></label><label class="check"><input type="checkbox" name="validade" value="1"> Controlar lote e validade</label>
        @endif
        </div>
        <p class="aviso erro erro-modal" role="alert" hidden></p>
        <div class="acoes"><button type="button" data-close class="botao secundario">Cancelar</button><button type="submit" class="botao">Salvar alterações</button></div>
    </form>
</dialog>

<dialog id="modal-deletar" class="modal-consulta" aria-labelledby="titulo-deletar">
    <form id="form-deletar" data-server-form>
        <div class="dialogo-cabecalho"><h2 id="titulo-deletar">{{ $stock ? 'Zerar os saldos' : 'Excluir produto' }}</h2><button type="button" class="fechar" data-close aria-label="Fechar">×</button></div>
        <p id="texto-deletar" class="texto-modal"></p>
        <p class="ajuda">{{ $stock ? 'Registra contagens zeradas nos dois locais, preservando o histórico.' : 'A exclusão será bloqueada se existir saldo, venda ou movimentação.' }}</p>
        <label class="campo">Motivo *<textarea name="motivo" required minlength="5" maxlength="2000"></textarea></label>
        <p class="aviso erro erro-modal" role="alert" hidden></p>
        <div class="acoes"><button type="button" data-close class="botao secundario">Cancelar</button><button type="submit" class="botao perigo">Confirmar</button></div>
    </form>
</dialog>

<dialog id="modal-status" class="modal-consulta" aria-labelledby="titulo-status">
    <form id="form-status" data-server-form>
        <div class="dialogo-cabecalho"><h2 id="titulo-status">Alterar situação</h2><button type="button" class="fechar" data-close aria-label="Fechar">×</button></div>
        <p id="texto-status"></p>
        <label class="campo">Motivo *<textarea name="motivo" required minlength="5" maxlength="2000"></textarea></label>
        <p class="aviso erro erro-modal" role="alert" hidden></p>
        <div class="acoes"><button type="button" data-close class="botao secundario">Cancelar</button><button type="submit" class="botao">Confirmar alteração</button></div>
    </form>
</dialog>

<dialog id="modal-transferir" class="modal-consulta" aria-labelledby="titulo-transferir">
    <form id="form-transferir" data-server-form>
        <div class="dialogo-cabecalho"><h2 id="titulo-transferir">Transferir estoque</h2><button type="button" class="fechar" data-close aria-label="Fechar">×</button></div>
        <p id="texto-transferir"></p>
        <div class="campos">
            <label class="campo">Direção<select name="origem"><option value="deposito">Depósito → gôndola</option><option value="gondola">Gôndola → depósito</option></select></label>
            <label class="campo">Quantidade *<input name="quantidade" type="number" min="0.001" max="1000000000" step="0.001" required></label>
            <label class="campo inteiro">Justificativa *<textarea name="motivo" required minlength="5" maxlength="2000"></textarea></label>
        </div>
        <p class="aviso erro erro-modal" role="alert" hidden></p>
        <div class="acoes"><button type="button" data-close class="botao secundario">Cancelar</button><button type="submit" class="botao">Confirmar transferência</button></div>
    </form>
</dialog>
@endsection
