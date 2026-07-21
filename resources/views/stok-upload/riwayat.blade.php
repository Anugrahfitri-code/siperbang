@extends('layouts.main')

@section('content')
@php
use App\Models\StokUpload as SU;

$statusColors = [
    SU::STATUS_DRAFT              => 'bg-slate-100 text-slate-700',
    SU::STATUS_PERLU_PERBAIKAN    => 'bg-rose-100 text-rose-800',
    SU::STATUS_MENUNGGU_VERIFIKASI => 'bg-amber-100 text-amber-800',
    SU::STATUS_SIAP_DIFINALISASI  => 'bg-indigo-100 text-indigo-800',
    SU::STATUS_SELESAI            => 'bg-emerald-100 text-emerald-800',
    SU::STATUS_DIBATALKAN         => 'bg-gray-200 text-gray-500',
];
@endphp

{{-- ── Page header ─────────────────────────────────────────── --}}
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-lg font-extrabold text-slate-900 tracking-tight">Riwayat Upload Stok</h1>
        <p class="text-xs text-slate-500 mt-0.5">Semua batch upload file Excel persediaan yang aktif.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('stok-upload.trash') }}"
           class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Sampah
        </a>
        <a href="{{ route('stok-upload.index') }}"
           class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Upload Baru
        </a>
    </div>
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
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-right">Statistik</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($batches as $batch)
                <tr class="hover:bg-slate-50/50 transition-colors {{ $batch->status === SU::STATUS_DIBATALKAN ? 'opacity-60' : '' }}">

                    {{-- Tanggal --}}
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="font-semibold text-slate-800">{{ $batch->upload_date->format('d M Y') }}</span>
                        <span class="block text-xs text-slate-400 mt-0.5">{{ $batch->upload_date->format('H:i') }}</span>
                    </td>

                    {{-- File --}}
                    <td class="px-5 py-4">
                        <span class="font-semibold text-slate-800 block max-w-[220px] truncate" title="{{ $batch->file_name_original }}">
                            {{ $batch->file_name_original }}
                        </span>
                        <span class="text-xs text-slate-400 mt-0.5 block">
                            {{ $batch->sheets_count }} sheet &bull; {{ $batch->rows_count }} baris
                        </span>
                    </td>

                    {{-- User --}}
                    <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                        {{ $batch->user?->name ?? '—' }}
                    </td>

                    {{-- Status --}}
                    <td class="px-5 py-4 text-center whitespace-nowrap">
                        <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded-full uppercase tracking-wider
                            {{ $statusColors[$batch->status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $batch->status }}
                        </span>
                        @if($batch->status === SU::STATUS_DIBATALKAN && $batch->cancelled_at)
                        <span class="block text-2xs text-gray-400 mt-0.5">{{ $batch->cancelled_at->format('d M Y') }}</span>
                        @endif
                    </td>

                    {{-- Statistik --}}
                    <td class="px-5 py-4 text-right whitespace-nowrap leading-snug">
                        <span class="text-emerald-600 font-semibold block">Valid: {{ $batch->valid_rows_count }}</span>
                        <span class="text-rose-600 font-semibold block">Error: {{ $batch->error_rows_count }}</span>
                        @if($batch->rejected_rows_count > 0)
                        <span class="text-slate-400 block">Ditolak: {{ $batch->rejected_rows_count }}</span>
                        @endif
                    </td>

                    {{-- Aksi — status-aware --}}
                    <td class="px-5 py-4 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-2 flex-wrap">

                            @if($batch->status === SU::STATUS_MENUNGGU_VERIFIKASI)
                                <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 3]) }}"
                                   class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 hover:bg-amber-200 transition-colors">
                                    Verifikasi Kode
                                </a>
                                <button type="button" onclick="openConfirmModal('delRiwayat{{ $batch->id }}')"
                                        class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 hover:bg-rose-100 hover:text-rose-700 transition-colors">
                                    Hapus
                                </button>

                            @elseif($batch->status === SU::STATUS_SIAP_DIFINALISASI)
                                <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 4]) }}"
                                   class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition-colors">
                                    Review &amp; Finalisasi
                                </a>
                                <button type="button" onclick="openConfirmModal('delRiwayat{{ $batch->id }}')"
                                        class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 hover:bg-rose-100 hover:text-rose-700 transition-colors">
                                    Hapus
                                </button>

                            @elseif($batch->status === SU::STATUS_SELESAI)
                                <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 4]) }}"
                                   class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                                    Lihat Detail
                                </a>
                                <button type="button"
                                        onclick="openConfirmModal('batalkanRiwayat{{ $batch->id }}')"
                                        class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700 hover:bg-rose-200 transition-colors">
                                    Batalkan
                                </button>

                            @elseif($batch->status === SU::STATUS_DIBATALKAN)
                                <span class="text-xs text-slate-400 italic px-1">
                                    Tidak dapat dibuka
                                </span>
                            @endif

                            {{-- Hapus (soft) — hanya untuk status yang belum final dan belum ditampilkan di atas --}}
                            @if($batch->isDeletable() && !in_array($batch->status, [SU::STATUS_MENUNGGU_VERIFIKASI, SU::STATUS_SIAP_DIFINALISASI]))
                            <button type="button" onclick="openConfirmModal('delRiwayat{{ $batch->id }}')"
                                    class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 hover:bg-rose-100 hover:text-rose-700 transition-colors">
                                Hapus
                            </button>
                            @endif

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-14 text-center">
                        <div class="flex flex-col items-center gap-2 text-slate-400">
                            <svg class="h-10 w-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-sm font-semibold">Belum ada riwayat upload.</p>
                            <a href="{{ route('stok-upload.index') }}" class="text-xs text-indigo-600 hover:underline">Upload file Excel sekarang →</a>
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

<style>
.btn-action {
    @apply inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold transition-colors;
}
</style>

{{-- Per-batch confirmation modals --}}
@foreach($batches as $batch)
    @if($batch->isDeletable())
    <x-confirm-modal id="delRiwayat{{ $batch->id }}"
        title="Hapus Upload"
        message="Pindahkan <strong>{{ $batch->file_name_original }}</strong> ke sampah?"
        variant="danger"
        confirmText="Ya, Hapus"
        :formAction="route('stok-upload.destroy', $batch->id)"
        formMethod="DELETE"
    />
    @endif

    @if($batch->status === SU::STATUS_SELESAI)
    <x-confirm-modal id="batalkanRiwayat{{ $batch->id }}"
        title="Batalkan Transaksi Upload"
        message="File: <strong>{{ $batch->file_name_original }}</strong>. Stok yang sudah ditambahkan akan dikembalikan melalui transaksi pembalik."
        variant="danger"
        confirmText="Ya, Batalkan"
        :formAction="route('stok-upload.batalkan', $batch->id)"
        formMethod="POST"
    >
        <input type="hidden" name="cancellation_reason" value="Pembatalan oleh pengguna">
    </x-confirm-modal>
    @endif
@endforeach

@endsection
