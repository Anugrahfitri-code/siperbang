@extends('layouts.main')

@section('content')
<div class="w-full space-y-6">
    
    <!-- Title Page Header -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs flex items-center gap-4">
        <div class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/20">
            <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                <line x1="12" y1="22.08" x2="12" y2="12" />
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold leading-7 text-slate-900">Master Barang & Stok</h1>
            <p class="text-sm font-normal leading-5 text-slate-500 mt-1">Kelola data master barang dan pantau stok persediaan secara real-time.</p>
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
                <div class="w-full md:w-56 shrink-0 relative">
                    <select name="kategori_id" class="block w-full pl-4 pr-10 py-2.5 text-sm font-medium text-slate-700 border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm appearance-none">
                        <option value="">Semua Kategori</option>
                        @foreach($kategoris as $kat)
                            <option value="{{ $kat->nama }}" {{ request('kategori_id') == $kat->nama ? 'selected' : '' }}>
                                {{ $kat->nama }}
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
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800">
                            {{ $barang->code }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-slate-700">{{ $barang->name }}</span>
                                @php
                                    $catName = $barang->kategori->nama ?? 'Umum';
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
                            <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors p-1 rounded hover:bg-slate-100">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="1" />
                                    <circle cx="12" cy="5" r="1" />
                                    <circle cx="12" cy="19" r="1" />
                                </svg>
                            </button>
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
@endsection
