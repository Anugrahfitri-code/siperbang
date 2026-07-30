@extends('layouts.inventory')

@section('content')
<div class="w-full space-y-6">
    
    <!-- Title Page Header -->
    <div class="relative bg-gradient-to-r from-[#f8faff] to-[#f0f4ff] rounded-2xl border border-indigo-50/50 p-6 shadow-sm overflow-hidden flex flex-col md:flex-row md:items-center gap-5">
        <!-- Glow effects -->
        <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
        <div class="absolute left-0 bottom-0 w-48 h-48 bg-blue-500/5 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>

        <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm border border-slate-100 text-amber-500 relative z-10">
            <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                <line x1="12" y1="22.08" x2="12" y2="12" />
            </svg>
        </div>
        <div class="relative z-10">
            <h2 class="text-base font-extrabold text-slate-800 uppercase tracking-wide">MASTER BARANG & STOK</h2>
            <p class="text-xs font-medium text-slate-500 mt-1">Kelola data master barang dan pantau stok persediaan secara real-time</p>
        </div>
    </div>

    <!-- Main Content Box -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs flex flex-col">
        <div class="p-6">
            <!-- Search Form -->
            <form action="{{ route('master-barang.index') }}" method="GET" class="mb-6 flex flex-col md:flex-row gap-4 items-center">
                <div class="flex-1 w-full">
                    <label for="search" class="sr-only">Cari Barang</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-4.5 w-4.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" class="block w-full pl-10 pr-3 py-2.5 text-sm font-medium text-slate-900 border border-slate-200 rounded-lg bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm" placeholder="Cari kode atau nama barang...">
                    </div>
                </div>
                <div class="w-full md:w-96 shrink-0 relative">
                    <label for="kategori_id" class="sr-only">Filter Subkategori 1.01.03</label>
                    <select id="kategori_id" name="kategori_id" class="block w-full pl-4 pr-10 py-2.5 text-sm font-medium text-slate-700 border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm appearance-none">
                        <option value="">Semua Subkategori 1.01.03</option>
                        @foreach($categoryOptions as $categoryOption)
                            <option value="{{ $categoryOption['name'] }}" {{ $selectedCategory === $categoryOption['name'] ? 'selected' : '' }}>
                                {{ $categoryOption['code'] }} - {{ $categoryOption['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </div>
                <div class="w-full md:w-auto shrink-0 flex gap-2">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 py-2.5 px-6 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                        </svg>
                        Cari
                    </button>
                    @if(request('search') || request('kategori_id'))
                        <a href="{{ route('master-barang.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center py-2.5 px-4 border border-slate-300 shadow-sm text-sm font-bold rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

        <!-- Data Table -->
        <div class="overflow-x-auto border-t border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                 <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Kode Persediaan</th>
                        <th scope="col" class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Nama Barang</th>
                        <th scope="col" class="px-6 py-4 text-center text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Satuan</th>
                        <th scope="col" class="px-6 py-4 text-center text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Stok Tersedia</th>
                        <th scope="col" class="px-6 py-4 text-center text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-4 text-center text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Update Terakhir</th>
                        <th scope="col" class="px-6 py-4 relative">
                            <span class="sr-only">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($barangs as $barang)
                    <tr class="hover:bg-slate-50/50 transition-colors" data-id="{{ $barang->id }}" data-code="{{ $barang->code }}" data-name="{{ $barang->name }}" data-unit="{{ $barang->unit }}" data-category="{{ $barang->canonical_category }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800">
                            {{ $barang->code }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-slate-700">{{ $barang->name }}</span>
                                @php
                                    $catName = $barang->canonical_category ?? $barang->category ?? '';
                                    $isElektronik = stripos($catName, 'elektronik') !== false;
                                    $isKebersihan = stripos($catName, 'bersih') !== false;
                                    $isAtk = stripos($catName, 'atk') !== false;
                                    $pillClass = 'bg-blue-50 text-blue-600'; // default
                                    if ($isElektronik) $pillClass = 'bg-blue-50 text-blue-600';
                                    elseif ($isKebersihan) $pillClass = 'bg-emerald-50 text-emerald-600';
                                    elseif ($isAtk) $pillClass = 'bg-purple-50 text-purple-700';
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $pillClass }} flex items-center gap-1">
                                    {{ $catName }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-slate-600">
                            {{ $barang->unit }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-extrabold {{ $barang->qty > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ number_format($barang->qty, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            @if($barang->qty > 5)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-50 text-emerald-700">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                    Tersedia
                                </span>
                            @elseif($barang->qty > 0 && $barang->qty <= 5)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-amber-50 text-amber-700">
                                    <span class="size-1.5 rounded-full bg-amber-500"></span>
                                    Stok Terbatas
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-rose-50 text-rose-700">
                                    <span class="size-1.5 rounded-full bg-rose-500"></span>
                                    Tidak Tersedia
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-slate-600">
                            {{ $barang->updated_at ? $barang->updated_at->format('d M Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="relative dropdown-container">
                                <button type="button" onclick="toggleDropdown(event, this)"
                                        class="text-slate-400 hover:text-slate-600 transition-colors p-1 rounded hover:bg-slate-100">
                                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="1" />
                                        <circle cx="12" cy="5" r="1" />
                                        <circle cx="12" cy="19" r="1" />
                                    </svg>
                                </button>
                                <div class="hidden absolute right-0 mt-1.5 w-44 bg-white rounded-xl border border-slate-200 shadow-lg z-50 py-1.5 origin-top-right">
                                    <button type="button" onclick="openEditModal(this)"
                                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                                        <svg class="size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                        Edit
                                    </button>
                                    <button type="button" onclick="confirmDelete(this)"
                                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
                                        <svg class="size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                        </svg>
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 whitespace-nowrap text-center text-sm text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="size-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <span>Data barang tidak ditemukan.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-500 font-medium">
                Menampilkan {{ $barangs->firstItem() ?? 0 }} - {{ $barangs->lastItem() ?? 0 }} dari {{ $barangs->total() ?? 0 }} data
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Dropdown -->
                <div class="relative">
                    <select onchange="window.location.href=this.value" class="appearance-none bg-white border border-slate-200 text-slate-700 text-xs font-bold rounded-lg pl-3 pr-8 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="{{ request()->fullUrlWithQuery(['per_page' => 10]) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 / halaman</option>
                        <option value="{{ request()->fullUrlWithQuery(['per_page' => 25]) }}" {{ request('per_page') == 25 ? 'selected' : '' }}>25 / halaman</option>
                        <option value="{{ request()->fullUrlWithQuery(['per_page' => 50]) }}" {{ request('per_page') == 50 ? 'selected' : '' }}>50 / halaman</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </div>

                <!-- Pagination Buttons -->
                @if($barangs->hasPages())
                <nav class="flex items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if ($barangs->onFirstPage())
                        <span class="flex items-center justify-center size-8 rounded-lg border border-slate-200 text-slate-300 cursor-not-allowed">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </span>
                    @else
                        <a href="{{ $barangs->previousPageUrl() }}" class="flex items-center justify-center size-8 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </a>
                    @endif

                    {{-- Page Links --}}
                    @foreach ($barangs->linkCollection() as $link)
                        @if (str_contains(strtolower($link['label']), 'previous') || str_contains(strtolower($link['label']), 'next') || str_contains(strtolower($link['label']), 'sebelumnya') || str_contains(strtolower($link['label']), 'berikutnya'))
                            @continue
                        @endif

                        @if ($link['url'] === null)
                            <span class="flex items-center justify-center size-8 text-slate-400 text-xs font-bold tracking-widest">...</span>
                        @elseif ($link['active'])
                            <span class="flex items-center justify-center size-8 rounded-lg bg-blue-600 text-white text-xs font-bold shadow-sm">{{ $link['label'] }}</span>
                        @else
                            <a href="{{ $link['url'] }}" class="flex items-center justify-center size-8 rounded-lg text-slate-700 hover:bg-slate-100 text-xs font-bold transition-colors">{{ $link['label'] }}</a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($barangs->hasMorePages())
                        <a href="{{ $barangs->nextPageUrl() }}" class="flex items-center justify-center size-8 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </a>
                    @else
                        <span class="flex items-center justify-center size-8 rounded-lg border border-slate-200 text-slate-300 cursor-not-allowed">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                    @endif
                </nav>
                @endif
            </div>
        </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     EDIT MODAL
═══════════════════════════════════════════════════════════ --}}
<div id="editModal"
     class="hidden fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-all duration-200"
     onclick="if(event.target===this) closeEditModal()">

    <div id="editModalPanel"
         class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden opacity-0 scale-95 translate-y-4 transition-all duration-200">

        <form method="POST" id="editForm">
            @csrf
            <input type="hidden" name="id" id="editId">

            <div class="p-6">
                <div class="flex items-start justify-between gap-2 mb-6">
                    <h3 class="text-lg font-extrabold text-slate-900">Edit Barang</h3>
                    <button type="button" onclick="closeEditModal()"
                            class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors -mr-1 -mt-1 shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-5">

                    {{-- Kode Persediaan --}}
                    <div>
                        <label for="editKodePersediaan" class="block text-sm font-bold text-slate-700 mb-1.5">Kode Persediaan</label>
                        <div class="relative">
                            <select name="kode_persediaan" id="editKodePersediaan" onchange="autoFillKategori(this)"
                                    class="block w-full pl-3.5 pr-10 py-2.5 text-sm font-medium text-slate-700 border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm appearance-none">
                                <option value="">Pilih Kode Persediaan</option>
                                @php
                                    $categoryOptionsByGroup = $categoryOptions->keyBy('group');
                                    $groupedKodes = $kodePersediaans->groupBy(
                                        fn ($kp) => substr($kp->kode, 5, 2)
                                    );
                                @endphp
                                @foreach($groupedKodes as $group => $items)
                                    @php
                                        $codeCategory = $categoryOptionsByGroup->get($group);
                                    @endphp
                                    @if($codeCategory)
                                    <optgroup label="{{ $codeCategory['code'] }} - {{ $codeCategory['name'] }}">
                                        @foreach($items as $kp)
                                        <option value="{{ $kp->kode }}" data-kategori="{{ $codeCategory['name'] }}">
                                            {{ $kp->kode }} - {{ $kp->nama_barang }}
                                        </option>
                                        @endforeach
                                    </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">Hanya kode resmi kelompok 1.01.03 yang tersedia. Kategori terisi otomatis.</p>
                        <p id="editErrorCode" class="mt-1.5 text-xs font-medium text-rose-600 hidden"></p>
                    </div>

                    {{-- Nama Barang --}}
                    <div>
                        <label for="editName" class="block text-sm font-bold text-slate-700 mb-1.5">Nama Barang</label>
                        <input type="text" name="name" id="editName" required maxlength="255"
                               class="block w-full px-3.5 py-2.5 text-sm font-medium text-slate-900 border border-slate-200 rounded-lg bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm"
                               placeholder="Masukkan nama barang">
                        <p id="editErrorName" class="mt-1.5 text-xs font-medium text-rose-600 hidden"></p>
                    </div>

                    {{-- Kategori --}}
                    <div>
                        <label for="editCategory" class="block text-sm font-bold text-slate-700 mb-1.5">Kategori</label>
                        <div class="relative">
                            <select id="editCategory" disabled
                                    class="block w-full pl-3.5 pr-10 py-2.5 text-sm font-medium text-slate-700 border border-slate-200 rounded-lg bg-slate-50 focus:outline-none transition-colors shadow-sm appearance-none cursor-not-allowed">
                                <option value="">Kategori mengikuti kode persediaan</option>
                                @foreach($categoryOptions as $categoryOption)
                                    <option value="{{ $categoryOption['name'] }}">
                                        {{ $categoryOption['code'] }} - {{ $categoryOption['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">Kategori tidak dapat dipisahkan dari subkelompok kode persediaan.</p>
                    </div>

                    {{-- Satuan --}}
                    <div>
                        <label for="editUnit" class="block text-sm font-bold text-slate-700 mb-1.5">Satuan</label>
                        <div class="relative">
                            <select name="unit" id="editUnit" 
                                    class="block w-full pl-3.5 pr-10 py-2.5 text-sm font-medium text-slate-900 border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm appearance-none">
                                <option value="">Pilih Satuan</option>
                                <option value="Buah">Buah</option>
                                <option value="Unit">Unit</option>
                                <option value="Rim">Rim</option>
                                <option value="Pak">Pak</option>
                                <option value="Box">Box</option>
                                <option value="Set">Set</option>
                                <option value="Lembar">Lembar</option>
                                <option value="Buku">Buku</option>
                                <option value="Roll">Roll</option>
                                <option value="Dus">Dus</option>
                                <option value="Lusin">Lusin</option>
                                <option value="Botol">Botol</option>
                                <option value="Pcs">Pcs</option>
                                <option value="Bungkus">Bungkus</option>
                                <option value="Pasang">Pasang</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeEditModal()"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-white hover:border-slate-300 transition-all">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-sm bg-blue-600 hover:bg-blue-700 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     DELETE CONFIRMATION MODAL
═══════════════════════════════════════════════════════════ --}}
<x-feedback.confirm-modal
    id="deleteModal"
    title="Hapus Barang"
    message='<span id="deleteMessage"></span>'
    variant="danger"
    confirmText="Ya, Hapus"
    cancelText="Batal"
    formAction="/master-barang/placeholder"
    formMethod="POST"
    formId="deleteForm"
    :showCancel="true"
/>

@push('scripts')
<script>
// ── Dropdown toggle ─────────────────────────────────────
function toggleDropdown(event, button) {
    event.stopPropagation();
    var container = button.closest('.dropdown-container');
    var dropdown = container.querySelector('.dropdown-container > div:last-child');
    var isOpen = !dropdown.classList.contains('hidden');
    closeAllDropdowns();
    if (!isOpen) {
        dropdown.classList.remove('hidden');
    }
}

function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-container > div:last-child').forEach(function(el) {
        el.classList.add('hidden');
    });
}

document.addEventListener('click', function() {
    closeAllDropdowns();
});

// ── Auto-fill kategori dari kode persediaan ────────────
function autoFillKategori(select) {
    var selected = select.options[select.selectedIndex];
    var kategori = selected ? selected.dataset.kategori : '';
    if (kategori) {
        document.getElementById('editCategory').value = kategori;
    }
}

// ── Edit Modal ──────────────────────────────────────────
function openEditModal(button) {
    var tr = button.closest('tr');
    var data = {
        id: tr.dataset.id,
        code: tr.dataset.code,
        name: tr.dataset.name,
        unit: tr.dataset.unit,
        category: tr.dataset.category
    };

    document.getElementById('editId').value = data.id;
    document.getElementById('editKodePersediaan').value = data.code;
    document.getElementById('editName').value = data.name;
    document.getElementById('editUnit').value = data.unit;
    document.getElementById('editCategory').value = data.category;
    document.getElementById('editForm').action = '/master-barang/' + data.id + '/update';

    var errEl = document.getElementById('editErrorName');
    errEl.classList.add('hidden');
    errEl.textContent = '';

    var codeErrEl = document.getElementById('editErrorCode');
    codeErrEl.classList.add('hidden');
    codeErrEl.textContent = '';

    var modal = document.getElementById('editModal');
    var panel = document.getElementById('editModalPanel');
    modal.classList.remove('hidden');
    requestAnimationFrame(function() {
        panel.classList.remove('opacity-0', 'scale-95', 'translate-y-4');
        panel.classList.add('opacity-100', 'scale-100', 'translate-y-0');
    });
}

function closeEditModal() {
    var modal = document.getElementById('editModal');
    var panel = document.getElementById('editModalPanel');
    panel.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
    panel.classList.add('opacity-0', 'scale-95', 'translate-y-4');
    setTimeout(function() { modal.classList.add('hidden'); }, 200);
}

// ── Delete Confirmation ────────────────────────────────
function confirmDelete(button) {
    var tr = button.closest('tr');
    var id = tr.dataset.id;
    var name = tr.dataset.name;

    document.getElementById('deleteForm').action = '/master-barang/' + id + '/delete';
    document.getElementById('deleteMessage').innerHTML = 'Apakah Anda yakin ingin menghapus barang <strong>' + name.replace(/</g, '&lt;') + '</strong>?<br><br>Data yang sudah dihapus tidak dapat dikembalikan.';

    openConfirmModal('deleteModal');
}

// ── Re-open edit modal on validation error ─────────────
@if($errors->any() && session('edit_id'))
document.addEventListener('DOMContentLoaded', function() {
    var editId = '{{ session('edit_id') }}';
    document.getElementById('editId').value = editId;
    document.getElementById('editKodePersediaan').value = '{{ old('kode_persediaan', '') }}';
    document.getElementById('editName').value = '{{ old('name', '') }}';
    document.getElementById('editUnit').value = '{{ old('unit', '') }}';
    autoFillKategori(document.getElementById('editKodePersediaan'));
    document.getElementById('editForm').action = '/master-barang/' + editId + '/update';

    @if($errors->has('name'))
    var errEl = document.getElementById('editErrorName');
    errEl.textContent = @json($errors->first('name'));
    errEl.classList.remove('hidden');
    @endif

    @if($errors->has('kode_persediaan'))
    var codeErrEl = document.getElementById('editErrorCode');
    codeErrEl.textContent = @json($errors->first('kode_persediaan'));
    codeErrEl.classList.remove('hidden');
    @endif

    var modal = document.getElementById('editModal');
    var panel = document.getElementById('editModalPanel');
    modal.classList.remove('hidden');
    requestAnimationFrame(function() {
        panel.classList.remove('opacity-0', 'scale-95', 'translate-y-4');
        panel.classList.add('opacity-100', 'scale-100', 'translate-y-0');
    });
});
@endif
</script>
@endpush

@endsection
