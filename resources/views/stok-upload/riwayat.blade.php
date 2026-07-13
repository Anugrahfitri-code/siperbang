@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- ── Page Header ── --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-extrabold text-slate-900 tracking-tight">Riwayat Upload Stok</h1>
            <p class="text-xs text-slate-500 mt-0.5">Daftar semua batch upload file Excel persediaan.</p>
        </div>
        <a href="{{ route('stok-upload.index') }}"
           class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-colors shrink-0">
            + Upload Baru
        </a>
    </div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold px-4 py-3 rounded-lg flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold px-4 py-3 rounded-lg flex items-center gap-2">
            <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Table ── --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <th class="px-5 py-3 text-left uppercase tracking-wider">Tanggal Upload</th>
                        <th class="px-5 py-3 text-left uppercase tracking-wider">File</th>
                        <th class="px-5 py-3 text-left uppercase tracking-wider">Diupload Oleh</th>
                        <th class="px-5 py-3 text-center uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-right uppercase tracking-wider">Statistik Baris</th>
                        <th class="px-5 py-3 text-right uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($batches as $batch)
                    <tr class="hover:bg-slate-50/50 transition-colors">

                        {{-- Tanggal --}}
                        <td class="px-5 py-4 whitespace-nowrap text-slate-500">
                            {{ $batch->upload_date->format('d M Y') }}
                            <span class="block text-[10px] text-slate-400">{{ $batch->upload_date->format('H:i') }}</span>
                        </td>

                        {{-- File info --}}
                        <td class="px-5 py-4">
                            <div class="font-semibold text-slate-800 max-w-[220px] truncate" title="{{ $batch->file_name_original }}">
                                {{ $batch->file_name_original }}
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5">
                                {{ $batch->sheets_count }} sheet &bull; {{ $batch->rows_count }} baris
                            </div>
                        </td>

                        {{-- User --}}
                        <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                            {{ $batch->user->name ?? '—' }}
                        </td>

                        {{-- Status badge --}}
                        <td class="px-5 py-4 whitespace-nowrap text-center">
                            <span class="px-2.5 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full uppercase tracking-wider
                                @if($batch->status === 'Selesai')              bg-emerald-100 text-emerald-800
                                @elseif($batch->status === 'Menunggu Verifikasi') bg-amber-100 text-amber-800
                                @elseif($batch->status === 'Sebagian Valid')   bg-amber-100 text-amber-800
                                @elseif($batch->status === 'Perlu Perbaikan')  bg-rose-100 text-rose-800
                                @else                                          bg-slate-100 text-slate-600
                                @endif">
                                {{ $batch->status }}
                            </span>
                        </td>

                        {{-- Statistik --}}
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <div class="text-emerald-600 font-semibold">
                                Valid: {{ $batch->valid_rows_count }}
                            </div>
                            <div class="text-rose-600 font-semibold">
                                Perbaikan: {{ $batch->error_rows_count }}
                            </div>
                            @if($batch->rejected_rows_count > 0)
                            <div class="text-slate-400">
                                Ditolak: {{ $batch->rejected_rows_count }}
                            </div>
                            @endif
                        </td>

                        {{-- Aksi — status-aware --}}
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            @if($batch->status === 'Draft')
                                <a href="{{ route('stok-upload.preview', $batch->id) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                                    Lanjutkan Upload
                                </a>

                            @elseif($batch->status === 'Perlu Perbaikan')
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('stok-upload.perbaiki.index', $batch->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-colors">
                                        Perbaiki Data
                                    </a>
                                    <a href="{{ route('stok-upload.preview', $batch->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-50 transition-colors">
                                        Preview
                                    </a>
                                </div>

                            @elseif($batch->status === 'Sebagian Valid')
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('stok-upload.perbaiki.index', $batch->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-xs transition-colors">
                                        Tinjau &amp; Perbaiki
                                    </a>
                                    <a href="{{ route('stok-upload.preview', $batch->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-50 transition-colors">
                                        Preview
                                    </a>
                                </div>

                            @elseif($batch->status === 'Menunggu Verifikasi')
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('stok-upload.verifikasi.index', $batch->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition-colors">
                                        Preview Verifikasi
                                    </a>
                                    <a href="{{ route('stok-upload.preview', $batch->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-50 transition-colors">
                                        Preview
                                    </a>
                                </div>

                            @elseif($batch->status === 'Selesai')
                                <a href="{{ route('stok-upload.preview', $batch->id) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-50 transition-colors">
                                    Lihat Detail
                                </a>

                            @else
                                <a href="{{ route('stok-upload.preview', $batch->id) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-500 bg-white hover:bg-slate-50 transition-colors">
                                    Lihat Detail
                                </a>
                            @endif
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-10 w-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="font-semibold text-slate-400">Belum ada riwayat upload.</span>
                                <a href="{{ route('stok-upload.index') }}" class="text-xs text-indigo-600 hover:underline">Upload file Excel sekarang →</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($batches->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $batches->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
