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
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-xs">
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
                        <span class="block text-xs text-slate-400 mt-0.5">{{ $batch->upload_date->format('H:i') }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="font-semibold text-slate-600 block max-w-[200px] truncate line-through" title="{{ $batch->file_name_original }}">
                            {{ $batch->file_name_original }}
                        </span>
                        <span class="text-xs text-slate-400 mt-0.5 block">
                            {{ $batch->sheets_count }} sheet &bull; {{ $batch->rows_count }} baris
                        </span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-slate-500">
                        {{ $batch->user?->name ?? '—' }}
                    </td>
                    <td class="px-5 py-4 text-center whitespace-nowrap">
                        <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-slate-100 text-slate-600 uppercase tracking-wider">
                            {{ $batch->status }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-center whitespace-nowrap text-slate-500">
                        {{ $batch->deleted_at->format('d M Y, H:i') }}
                    </td>
                    <td class="px-5 py-4 text-center whitespace-nowrap">
                        @if($isExpired)
                            <span class="text-xs font-bold text-rose-600">Kedaluwarsa</span>
                        @elseif($isExpiringSoon)
                            <span class="text-xs font-bold text-amber-600">{{ $daysLeft }} hari lagi</span>
                        @else
                            <span class="text-xs text-slate-400">{{ $daysLeft }} hari lagi</span>
                        @endif
                        <span class="block text-2xs text-slate-300 mt-0.5">{{ $expiresAt->format('d M Y') }}</span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-2">
                            {{-- Restore --}}
                            <button type="button" onclick="openConfirmModal('restoreTrash{{ $batch->id }}')"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold border border-emerald-200 text-emerald-700 hover:bg-emerald-50 transition-colors">
                                Pulihkan
                            </button>

                            {{-- Force delete --}}
                            <button type="button" onclick="openConfirmModal('forceDelTrash{{ $batch->id }}')"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors">
                                Hapus Permanen
                            </button>
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

{{-- Confirmation Modals --}}
@foreach($batches as $batch)
<x-confirm-modal id="restoreTrash{{ $batch->id }}"
    title="Pulihkan Upload"
    message="Pulihkan <strong>{{ $batch->file_name_original }}</strong> dari sampah?"
    variant="success"
    confirmText="Ya, Pulihkan"
    :formAction="route('stok-upload.restore', $batch->id)"
    formMethod="POST"
/>

<x-confirm-modal id="forceDelTrash{{ $batch->id }}"
    title="Hapus Permanen"
    message="Hapus PERMANEN <strong>{{ $batch->file_name_original }}</strong>? Data tidak dapat dikembalikan."
    variant="danger"
    confirmText="Ya, Hapus Permanen"
    :formAction="route('stok-upload.force-delete', $batch->id)"
    formMethod="DELETE"
/>
@endforeach

@endsection
