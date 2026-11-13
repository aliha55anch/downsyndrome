@if ($paginator->hasPages())
    <nav class="al-pagination" role="navigation" aria-label="Activity pagination">
        <p class="al-pagination__summary">
            Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
            of <strong>{{ $paginator->total() }}</strong> activities
        </p>

        <div class="al-pagination__links">
            @if ($paginator->onFirstPage())
                <span class="al-pagination__link is-disabled" aria-disabled="true" aria-label="Previous page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </span>
            @else
                <a class="al-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="al-pagination__gap" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="al-pagination__current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="al-pagination__link" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="al-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <span class="al-pagination__link is-disabled" aria-disabled="true" aria-label="Next page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
