@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-4 mt-4">

        {{-- Info teks --}}
        <p class="text-xs text-slate-500 font-medium shrink-0">
            @if ($paginator->firstItem())
                Menampilkan
                <span class="font-bold text-slate-700">{{ $paginator->firstItem() }}</span>
                &ndash;
                <span class="font-bold text-slate-700">{{ $paginator->lastItem() }}</span>
                dari
                <span class="font-bold text-slate-700">{{ $paginator->total() }}</span>
                data
            @else
                Menampilkan {{ $paginator->count() }} data
            @endif
        </p>

        {{-- Navigasi halaman --}}
        <div class="flex items-center gap-1">

            {{-- Tombol Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-300 bg-slate-50 border border-slate-200 cursor-not-allowed select-none">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 transition-all duration-150 shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Sebelumnya
                </a>
            @endif

            {{-- Nomor Halaman --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex items-center px-2 py-1.5 text-xs font-medium text-slate-400 select-none">
                        &hellip;
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold text-white bg-indigo-600 border border-indigo-600 shadow-sm select-none">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all duration-150"
                               aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Tombol Berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 transition-all duration-150 shadow-xs">
                    Berikutnya
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-300 bg-slate-50 border border-slate-200 cursor-not-allowed select-none">
                    Berikutnya
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif

        </div>
    </nav>
@endif
