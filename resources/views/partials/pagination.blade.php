@if ($paginator->hasPages())
    <nav class="paginacao" aria-label="Paginação">
        <p class="paginacao-resumo">
            Mostrando {{ $paginator->firstItem() }} a {{ $paginator->lastItem() }} de {{ $paginator->total() }} registros
        </p>
        <div class="paginacao-links">
            @if ($paginator->onFirstPage())
                <span class="paginacao-link" aria-disabled="true">Anterior</span>
            @else
                <a class="paginacao-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="paginacao-reticencias" aria-hidden="true">{{ $element }}</span>
                @else
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="paginacao-link paginacao-atual" aria-current="page" aria-label="Página {{ $page }}">{{ $page }}</span>
                        @else
                            <a class="paginacao-link" href="{{ $url }}" aria-label="Página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="paginacao-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Próxima</a>
            @else
                <span class="paginacao-link" aria-disabled="true">Próxima</span>
            @endif
        </div>
    </nav>
@endif
