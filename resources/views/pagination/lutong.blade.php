@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3">
        <p class="w-full text-sm font-semibold text-ink-soft sm:w-auto">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>

        <ul class="flex w-full flex-wrap items-center justify-between gap-1 sm:w-auto sm:justify-start">
            {{-- Previous --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" class="block rounded-lg px-3 py-2 text-sm font-bold text-ink-soft/50">Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="block rounded-lg px-3 py-2.5 text-sm font-bold text-teal-700 hover:bg-teal-100">Previous</a>
                @endif
            </li>

            {{-- Phones: just the current position --}}
            <li class="sm:hidden"><span class="block px-2 py-2 text-sm font-bold text-ink-soft">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span></li>

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="max-sm:hidden"><span class="block px-2 py-2 text-sm font-bold text-ink-soft">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="max-sm:hidden">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="block min-w-9 rounded-lg bg-teal-700 px-3 py-2 text-center text-sm font-bold text-cream-50">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Go to page {{ $page }}" class="block min-w-9 rounded-lg px-3 py-2 text-center text-sm font-bold text-teal-700 hover:bg-teal-100">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="block rounded-lg px-3 py-2.5 text-sm font-bold text-teal-700 hover:bg-teal-100">Next</a>
                @else
                    <span aria-disabled="true" class="block rounded-lg px-3 py-2 text-sm font-bold text-ink-soft/50">Next</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
