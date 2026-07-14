@extends('layouts.app')

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
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
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
                        <span class="block text-[10px] text-slate-400 mt-0.5">{{ $batch->upload_date->format('H:i') }}</span>
                    </td>

                    {{-- File --}}
                    <td class="px-5 py-4">
                        <span class="font-semibold text-slate-800 block max-w-[220px] truncate" title="{{ $batch->file_name_original }}">
                            {{ $batch->file_name_original }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">
                            {{ $batch->sheets_count }} sheet &bull; {{ $batch->rows_count }} baris
                        </span>
                    </td>

                    {{-- User --}}
                    <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                        {{ $batch->user?->name ?? '—' }}
                    </td>

                    {{-- Status --}}
                    <td class="px-5 py-4 text-center whitespace-nowrap">
                        <span class="px-2.5 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full uppercase tracking-wider
                            {{ $statusColors[$batch->status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $batch->status }}
                        </span>
                        @if($batch->status === SU::STATUS_DIBATALKAN && $batch->cancelled_at)
                        <span class="block text-[9px] text-gray-400 mt-0.5">{{ $batch->cancelled_at->format('d M Y') }}</span>
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
                                   class="btn-action bg-amber-500 hover:bg-amber-600 text-white">
                                    Verifikasi Kode
                                </a>

                            @elseif($batch->status === SU::STATUS_SIAP_DIFINALISASI)
                                <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 4]) }}"
                                   class="btn-action bg-indigo-600 hover:bg-indigo-700 text-white">
                                    Review &amp; Finalisasi
                                </a>

                            @elseif($batch->status === SU::STATUS_SELESAI)
                                <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 4]) }}"
                                   class="btn-action border border-emerald-300 text-emerald-700 hover:bg-emerald-50">
                                    Lihat Detail
                                </a>
                                <button type="button"
                                        onclick="openBatalkanModal({{ $batch->id }}, '{{ addslashes($batch->file_name_original) }}')"
                                        class="btn-action border border-rose-200 text-rose-600 hover:bg-rose-50">
                                    Batalkan
                                </button>

                            @elseif($batch->status === SU::STATUS_DIBATALKAN)
                                {{-- Dibatalkan: no action allowed, show info text only --}}
                                <span class="text-[11px] text-slate-400 italic">
                                    Dibatalkan — tidak dapat dibuka
                                </span>
                            @endif

                            {{-- Hapus (soft) — only non-finalised --}}
                            @if($batch->isDeletable())
                            <form action="{{ route('stok-upload.destroy', $batch->id) }}" method="POST"
                                  onsubmit="return confirm('Pindahkan \'{{ addslashes($batch->file_name_original) }}\' ke sampah?')">
                                @csrf @method('DELETE')
                                <button class="btn-action border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200">
                                    Hapus
                                </button>
                            </form>
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

{{-- ── Batalkan Transaksi Modal (shared, opened via JS) ─────── --}}
<div id="modalBatalkanRiwayat" class="hidden fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full p-6">
        <div class="flex items-start gap-3 mb-5">
            <div class="bg-rose-100 rounded-full p-2 shrink-0">
                <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Batalkan Transaksi Upload</h3>
                <p class="text-xs text-slate-500 mt-0.5" id="modalBatalkanDesc">
                    Stok yang sudah ditambahkan akan dikembalikan melalui transaksi pembalik.
                </p>
            </div>
        </div>

        <form id="formBatalkanRiwayat" action="" method="POST" onsubmit="return validateBatalkanRiwayat()">
            @csrf
            <div class="mb-4">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                    Alasan Pembatalan <span class="text-rose-500">*</span>
                </label>
                <textarea id="cancelReasonRiwayat" name="cancellation_reason" rows="3" required minlength="10"
                          placeholder="Jelaskan alasan pembatalan secara singkat dan jelas (min. 10 karakter)..."
                          class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-rose-400 focus:border-rose-400 resize-none"></textarea>
                <p class="text-[10px] text-slate-400 mt-1">Alasan dicatat dalam audit log dan tidak dapat diubah.</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('modalBatalkanRiwayat').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold shadow-sm transition-colors">
                    Konfirmasi Batalkan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.btn-action {
    @apply inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] font-bold transition-colors border border-transparent;
}
</style>

<script>
function openBatalkanModal(batchId, fileName) {
    document.getElementById('modalBatalkanDesc').textContent =
        'File: ' + fileName + '. Stok yang sudah ditambahkan akan dikembalikan melalui transaksi pembalik.';
    document.getElementById('cancelReasonRiwayat').value = '';
    document.getElementById('formBatalkanRiwayat').action =
        '/stok-upload/' + batchId + '/batalkan';
    document.getElementById('modalBatalkanRiwayat').classList.remove('hidden');
}

function validateBatalkanRiwayat() {
    const reason = document.getElementById('cancelReasonRiwayat')?.value?.trim();
    if (!reason || reason.length < 10) {
        alert('Alasan pembatalan harus diisi minimal 10 karakter.');
        return false;
    }
    return confirm('Yakin ingin membatalkan transaksi ini? Stok akan dikembalikan. Tindakan ini tidak dapat diurungkan.');
}
</script>

@endsection
