@extends('layouts.app')

@section('content')

{{-- ── Page header ─────────────────────────────────────────── --}}
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-lg font-extrabold text-slate-900 tracking-tight">Sampah Upload Stok</h1>
        <p class="text-xs text-slate-500 mt-0.5">Upload yang dihapus disimpan di sini selama 30 hari sebelum dihapus permanen.</p>
    </div>
    <a href="{{ route('stok-upload.riwayat') }}"
       class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 flex items-center gap-1.5">
        ← Kembali ke Riwayat
    </a>
</div>

{{-- ── Table ────────────────────────────────────────────────── --}}
<div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                    <th class="px-5 py-3">Tanggal Upload</th>
                    <th class="px-5 py-3">File</th>
                    <th class="px-5 py-3">Diupload Oleh</th>
                    <th class="px-5 py-3 text-center">Status Saat Dihapus</th>
                    <th class="px-5 py-3 text-center">Dihapus Pada</th>
                    <th class="px-5 py-3 text-center">Kedaluwarsa</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($batches as $batch)
                @php
                    $expiresAt   = $batch->deleted_at->addDays(30);
                    $daysLeft    = now()->diffInDays($expiresAt, false);
                    $isExpiringSoon = $daysLeft <= 7 && $daysLeft >= 0;
                    $isExpired   = $daysLeft < 0;
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors opacity-80">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="font-semibold text-slate-700">{{ $batch->upload_date->format('d M Y') }}</span>
                        <span class="block text-[10px] text-slate-400 mt-0.5">{{ $batch->upload_date->format('H:i') }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="font-semibold text-slate-600 block max-w-[200px] truncate line-through" title="{{ $batch->file_name_original }}">
                            {{ $batch->file_name_original }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">
                            {{ $batch->sheets_count }} sheet &bull; {{ $batch->rows_count }} baris
                        </span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-slate-500">
                        {{ $batch->user?->name ?? '—' }}
                    </td>
                    <td class="px-5 py-4 text-center whitespace-nowrap">
                        <span class="px-2.5 py-1 inline-flex text-[10px] font-bold rounded-full bg-slate-100 text-slate-600 uppercase tracking-wider">
                            {{ $batch->status }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-center whitespace-nowrap text-slate-500">
                        {{ $batch->deleted_at->format('d M Y, H:i') }}
                    </td>
                    <td class="px-5 py-4 text-center whitespace-nowrap">
                        @if($isExpired)
                            <span class="text-[10px] font-bold text-rose-600">Kedaluwarsa</span>
                        @elseif($isExpiringSoon)
                            <span class="text-[10px] font-bold text-amber-600">{{ $daysLeft }} hari lagi</span>
                        @else
                            <span class="text-[10px] text-slate-400">{{ $daysLeft }} hari lagi</span>
                        @endif
                        <span class="block text-[9px] text-slate-300 mt-0.5">{{ $expiresAt->format('d M Y') }}</span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-2">
                            {{-- Restore --}}
                            <form action="{{ route('stok-upload.restore', $batch->id) }}" method="POST"
                                  onsubmit="return confirm('Pulihkan upload ini dari sampah?')">
                                @csrf
                                <button class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] font-bold border border-emerald-200 text-emerald-700 hover:bg-emerald-50 transition-colors">
                                    Pulihkan
                                </button>
                            </form>

                            {{-- Force delete --}}
                            <form action="{{ route('stok-upload.force-delete', $batch->id) }}" method="POST"
                                  onsubmit="return confirm('Hapus PERMANEN \'{{ addslashes($batch->file_name_original) }}\'? Data tidak dapat dikembalikan.')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] font-bold border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors">
                                    Hapus Permanen
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-14 text-center">
                        <div class="flex flex-col items-center gap-2 text-slate-400">
                            <svg class="h-10 w-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <p class="text-sm font-semibold">Sampah kosong.</p>
                            <a href="{{ route('stok-upload.riwayat') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke Riwayat</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($batches->hasPages())
    <div class="px-5 py-4 border-t border-slate-100">
        {{ $batches->links() }}
    </div>
    @endif
</div>

@endsection
