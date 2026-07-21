@extends('layouts.main')

@section('content')
{{-- ═══════════════════════════════════════════════════════════
     STEPPER SHELL — variables available in all steps:
     $batch, $step (1-4), $masterCodes, $errorRows, $validRows
═══════════════════════════════════════════════════════════ --}}

{{-- ── Status / cancelled banner ──────────────────────────── --}}
@if($batch->status === \App\Models\StokUpload::STATUS_DIBATALKAN)
<div class="mb-6 p-4 bg-gray-100 border border-gray-300 rounded-xl flex items-center gap-3 text-gray-600">
    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
    <div>
        <p class="text-sm font-extrabold text-gray-700">Upload Ini Telah Dibatalkan</p>
        <p class="text-xs mt-0.5">Dibatalkan oleh <strong>{{ $batch->cancelled_by }}</strong> pada {{ $batch->cancelled_at?->format('d M Y H:i') }}. Alasan: {{ $batch->cancellation_reason }}</p>
    </div>
</div>
@endif

{{-- ── Page header ─────────────────────────────────────────── --}}
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
    <div>
        <div class="flex items-center gap-2 flex-wrap mb-1">
            <span class="text-xs font-mono font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded">BATCH #{{ $batch->id }}</span>
            <span class="text-xs text-slate-400">{{ $batch->upload_date->format('d M Y, H:i') }}</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $batch->statusColor() }}">{{ $batch->status }}</span>
        </div>
        <h1 class="text-base font-extrabold text-slate-900 tracking-tight">{{ $batch->file_name_original }}</h1>
        <p class="text-xs text-slate-500 mt-0.5">Diupload oleh {{ $batch->user?->name ?? '—' }}</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('stok-upload.riwayat') }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">← Riwayat</a>
        @if($batch->isDeletable())
        <button type="button" onclick="openConfirmModal('delStepperModal')"
                class="px-3 py-1.5 rounded-lg border border-rose-200 text-xs font-semibold text-rose-600 hover:bg-rose-50">
            Hapus
        </button>
        @endif
    </div>
</div>

{{-- ── 4-Step indicator ────────────────────────────────────── --}}
@php
$stepLabels = ['Upload File', 'Pemeriksaan Data', 'Verifikasi Kode', 'Review & Finalisasi'];
@endphp
<div class="mb-8">
    <div class="flex items-center gap-0">
        @foreach($stepLabels as $i => $label)
        @php $n = $i + 1; $isActive = $n === $step; $isDone = $n < $step; @endphp
        <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => $n]) }}"
           class="flex-1 group relative flex flex-col items-center text-center {{ $isDone ? 'cursor-pointer' : ($isActive ? 'cursor-default' : 'cursor-not-allowed pointer-events-none opacity-40') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-extrabold transition-all
                {{ $isActive ? 'bg-indigo-600 text-white shadow-md ring-4 ring-indigo-100' : ($isDone ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500') }}">
                @if($isDone)<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                @else{{ $n }}@endif
            </div>
            <span class="mt-1.5 text-xs font-bold {{ $isActive ? 'text-indigo-700' : ($isDone ? 'text-emerald-600' : 'text-slate-400') }} hidden sm:block">{{ $label }}</span>
        </a>
        @if($n < 4)
        <div class="flex-1 h-0.5 {{ $n < $step ? 'bg-emerald-400' : 'bg-slate-200' }} -mt-5 sm:-mt-3 max-w-[60px]"></div>
        @endif
        @endforeach
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     STEP 1 — Upload File (show after a fresh upload)
══════════════════════════════════════════════════════════ --}}
@if($step === 1)
<div class="bg-white border border-slate-200 rounded-xl p-8 shadow-xs text-center">
    <div class="text-emerald-500 mx-auto mb-3 flex justify-center">
        <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h2 class="text-lg font-extrabold text-slate-800">File Berhasil Diunggah</h2>
    <p class="text-sm text-slate-500 mt-1">{{ $batch->file_name_original }}</p>
    <p class="text-xs text-slate-400 mt-0.5">{{ $batch->sheets_count }} sheet • {{ $batch->rows_count }} baris terdeteksi</p>
    <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 2]) }}"
       class="mt-6 inline-block px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition-colors">
        Lihat Hasil Pemeriksaan →
    </a>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════
     STEP 2 — Pemeriksaan Data
══════════════════════════════════════════════════════════ --}}
@if($step === 2)
{{-- Stats bar --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Total Baris</span>
        <span class="block text-2xl font-extrabold text-slate-800 mt-1">{{ $batch->rows_count }}</span>
    </div>
    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-emerald-600 uppercase tracking-wider">Valid</span>
        <span class="block text-2xl font-extrabold text-emerald-700 mt-1">{{ $validRows->count() }}</span>
    </div>
    <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-rose-600 uppercase tracking-wider">Perlu Perbaikan</span>
        <span class="block text-2xl font-extrabold text-rose-700 mt-1">{{ $errorRows->count() }}</span>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Sheet</span>
        <span class="block text-2xl font-extrabold text-slate-700 mt-1">{{ $batch->sheets_count }}</span>
    </div>
</div>

{{-- Action toolbar --}}
<div class="flex flex-wrap gap-2 mb-5">
    <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 3]) }}"
       class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-xs transition-colors">
        Lanjut Verifikasi Kode →
    </a>
    <a href="{{ route('stok-upload.index') }}"
       class="px-4 py-2 border border-amber-300 text-xs font-semibold text-amber-700 hover:bg-amber-50 rounded-lg">
        ↺ Upload File Baru
    </a>
</div>

{{-- Error rows — READ ONLY, no inline editing --}}
@if($errorRows->count() > 0)
<div class="bg-rose-50 border border-rose-200 rounded-xl p-5 text-center">
    <svg class="h-10 w-10 text-rose-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="text-sm font-extrabold text-rose-800">Batch ini tidak seharusnya ada di sini.</p>
    <p class="text-xs text-rose-600 mt-1">Sistem menolak upload ketika ada error. Jika batch ini sudah terlanjur tersimpan, hapus dan upload ulang file yang sudah diperbaiki.</p>
    <button type="button" onclick="openConfirmModal('delReuploadModal')"
            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg transition-colors">
        Hapus & Upload Ulang
    </button>
</div>
@endif

{{-- Valid rows summary (collapsible) --}}
@if($validRows->count() > 0)
<div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
    <div class="px-5 py-3 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between">
        <h3 class="text-xs font-extrabold text-emerald-800 uppercase tracking-wider">Baris Valid ({{ $validRows->count() }})</h3>
        <button type="button" onclick="toggleEl('validTable2')" class="text-xs text-emerald-600 font-semibold">Tampilkan ▼</button>
    </div>
    <div id="validTable2" class="hidden overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <th class="px-4 py-3">Sheet / No</th><th class="px-4 py-3">Nama Barang</th>
                    <th class="px-4 py-3">Kode</th><th class="px-3 py-3 text-right">Qty</th>
                    <th class="px-3 py-3">Satuan</th><th class="px-3 py-3 text-right">Harga</th>
                    <th class="px-3 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($validRows as $row)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $row->sheet_name }} • {{ $row->no_urut }}</td>
                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $row->nama_barang }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-indigo-700 font-bold">{{ $row->verified_kode_persediaan ?? $row->kode_persediaan_excel }}</td>
                    <td class="px-3 py-3 text-right font-bold">{{ number_format($row->qty) }}</td>
                    <td class="px-3 py-3 text-slate-500">{{ $row->unit }}</td>
                    <td class="px-3 py-3 text-right font-mono text-slate-600">Rp{{ number_format((int)$row->price_unit,0,',','.') }}</td>
                    <td class="px-3 py-3 text-right font-mono font-bold text-emerald-700">Rp{{ number_format((int)$row->total_calculated,0,',','.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endif {{-- end step 2 --}}

{{-- ══════════════════════════════════════════════════════════
     STEP 3 — Verifikasi Kode Persediaan
══════════════════════════════════════════════════════════ --}}
@if($step === 3)

{{-- Info banner if there are still pending rows --}}


<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Total Baris</span>
        <span class="block text-2xl font-extrabold text-slate-800 mt-1">{{ $allDetails->count() }}</span>
    </div>
    <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-amber-600 uppercase tracking-wider">Menunggu</span>
        <span class="block text-2xl font-extrabold text-amber-700 mt-1">{{ $pendingRows->count() }}</span>
    </div>
    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-emerald-600 uppercase tracking-wider">Disetujui</span>
        <span class="block text-2xl font-extrabold text-emerald-700 mt-1">{{ $approvedRows->count() }}</span>
    </div>
    <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-rose-600 uppercase tracking-wider">Ditolak</span>
        <span class="block text-2xl font-extrabold text-rose-700 mt-1">{{ $rejectedRows->count() }}</span>
    </div>
</div>

@if($batch->status === \App\Models\StokUpload::STATUS_PERLU_PERBAIKAN)
<div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-700 font-semibold flex items-center gap-2">
    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Masih ada baris yang perlu diperbaiki.
    <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 2]) }}" class="underline font-bold">Kembali ke Pemeriksaan Data →</a>
</div>
@endif

<div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
        <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">Verifikasi Kode Persediaan</h3>
    </div>

    <form action="{{ route('stok-upload.verifikasi.store', $batch->id) }}" method="POST" id="formVerifikasi">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider text-xs">
                        <th class="px-4 py-3">Sheet / No</th>
                        <th class="px-4 py-3">Nama Barang</th>
                        <th class="px-4 py-3">Kode dari Excel</th>
                        <th class="px-4 py-3 min-w-[260px]">Kode Terverifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($allDetails as $i => $row)
                    @php
                        $rowStatus = $row->status_verification;
                        $rowBg = match($rowStatus) {
                            'Setuju' => 'bg-emerald-50/40',
                            'Tolak'  => 'bg-rose-50/40 opacity-60',
                            default  => '',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors align-middle {{ $rowBg }}">
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-slate-500">
                            {{ $row->sheet_name }}<br>Baris {{ $row->no_urut }}
                            <input type="hidden" name="items[{{ $i }}][detail_id]" value="{{ $row->id }}">
                            <input type="hidden" name="items[{{ $i }}][action]" value="Setuju">
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $row->nama_barang }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">
                            {{ $row->kode_persediaan_excel ?? '—' }}
                        </td>
                        <td class="px-4 py-3 min-w-[260px]">
                            <select id="kode_v_{{ $i }}" name="items[{{ $i }}][kode_persediaan]"
                                    class="w-full px-2 py-1.5 rounded border border-slate-200 bg-white text-xs font-mono focus:ring-1 focus:ring-indigo-400">
                                <option value="">-- Pilih Kode --</option>
                                @foreach($masterCodes as $mc)
                                <option value="{{ $mc->kode }}"
                                    {{ ($row->verified_kode_persediaan ?? $row->kode_persediaan_excel) === $mc->kode ? 'selected' : '' }}>
                                    {{ $mc->kode }} — {{ Str::limit($mc->nama_barang, 40) }}
                                </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap justify-between items-center gap-3">
            <p class="text-xs text-slate-500">
                Pastikan kode terverifikasi sudah sesuai dengan nama barang sebelum menyimpan.
            </p>
            <div class="flex gap-3">
                <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 2]) }}"
                   class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    ← Kembali
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-xs transition-colors">
                    Simpan Verifikasi & Lanjut Review →
                </button>
            </div>
        </div>
    </form>
</div>
@endif {{-- end step 3 --}}

{{-- ══════════════════════════════════════════════════════════
     STEP 4 — Review & Finalisasi
══════════════════════════════════════════════════════════ --}}
@if($step === 4)


{{-- Summary stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Total Baris</span>
        <span class="block text-2xl font-extrabold text-slate-800 mt-1">{{ $allDetails->count() }}</span>
    </div>
    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-emerald-600 uppercase tracking-wider">Disetujui</span>
        <span class="block text-2xl font-extrabold text-emerald-700 mt-1">{{ $totalApproved }}</span>
    </div>
    <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-rose-600 uppercase tracking-wider">Ditolak</span>
        <span class="block text-2xl font-extrabold text-rose-700 mt-1">{{ $rejectedRows->count() }}</span>
    </div>
    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-center shadow-xs">
        <span class="block text-xs font-bold text-indigo-600 uppercase tracking-wider">Total Nilai</span>
        <span class="block text-sm font-extrabold text-indigo-700 mt-1">Rp{{ number_format($totalValue,0,',','.') }}</span>
    </div>
</div>

{{-- Warning: pending rows not yet verified --}}
@if($pendingRows->count() > 0)
<div class="mb-5 p-3.5 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 font-semibold flex items-center gap-2">
    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    {{ $pendingRows->count() }} baris belum diverifikasi. Kembali ke Langkah 3 untuk menyelesaikan verifikasi.
    <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 3]) }}" class="underline font-bold">Verifikasi Kode →</a>
</div>
@endif

{{-- Approved rows table --}}
@if($approvedRows->count() > 0)
<div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden mb-6">
    <div class="px-5 py-3.5 bg-emerald-50 border-b border-emerald-100">
        <h3 class="text-xs font-extrabold text-emerald-800 uppercase tracking-wider">Baris yang Akan Difinalisasi ({{ $totalApproved }} baris)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider text-xs">
                    <th class="px-4 py-3">Sheet / No</th>
                    <th class="px-4 py-3">Nama Barang</th>
                    <th class="px-4 py-3">Kode Final</th>
                    <th class="px-3 py-3 text-right">Qty</th>
                    <th class="px-3 py-3">Satuan</th>
                    <th class="px-3 py-3 text-right">Harga Satuan</th>
                    <th class="px-3 py-3 text-right">Harga Satuan + Pajak</th>
                    <th class="px-3 py-3 text-center">PPN</th>
                    <th class="px-3 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($approvedRows as $row)
                <tr class="hover:bg-emerald-50/30 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $row->sheet_name }} • {{ $row->no_urut }}</td>
                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $row->nama_barang }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-indigo-700 font-extrabold">{{ $row->verified_kode_persediaan ?? $row->kode_persediaan_excel }}</td>
                    <td class="px-3 py-3 text-right font-bold text-slate-700">{{ number_format($row->qty) }}</td>
                    <td class="px-3 py-3 text-slate-500">{{ $row->unit }}</td>
                    <td class="px-3 py-3 text-right font-mono text-slate-600">Rp{{ number_format((int)$row->price_unit,0,',','.') }}</td>
                    <td class="px-3 py-3 text-right font-mono text-slate-700 font-semibold">Rp{{ number_format((int)$row->price_unit_taxed,0,',','.') }}</td>
                    <td class="px-3 py-3 text-center">
                        <span class="px-1.5 py-0.5 rounded text-2xs font-bold {{ $row->is_taxed ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500' }}">
                            {{ $row->is_taxed ? 'PPN 11%' : 'Nett' }}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-right font-mono font-extrabold text-emerald-700">Rp{{ number_format((int)$row->total_calculated,0,',','.') }}</td>
                </tr>
                @endforeach
                <tr class="bg-slate-50 border-t-2 border-slate-300 font-extrabold">
                    <td colspan="8" class="px-4 py-3 text-right text-xs text-slate-700 uppercase tracking-wider">Grand Total</td>
                    <td class="px-3 py-3 text-right font-mono text-emerald-700 text-sm">Rp{{ number_format($totalValue,0,',','.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Rejected rows (collapsed) --}}
@if($rejectedRows->count() > 0)
<div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden mb-6">
    <div class="px-5 py-3 bg-rose-50 border-b border-rose-100 flex items-center justify-between">
        <h3 class="text-xs font-extrabold text-rose-700 uppercase tracking-wider">Baris Ditolak ({{ $rejectedRows->count() }}) — Tidak Akan Difinalisasi</h3>
        <button type="button" onclick="toggleEl('rejectedTable')" class="text-xs text-rose-600 font-semibold">Tampilkan ▼</button>
    </div>
    <div id="rejectedTable" class="hidden overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 text-xs">
                    <th class="px-4 py-3">Sheet / No</th><th class="px-4 py-3">Nama Barang</th>
                    <th class="px-4 py-3">Kode</th><th class="px-3 py-3 text-right">Qty</th><th class="px-3 py-3">Satuan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($rejectedRows as $row)
                <tr class="opacity-60">
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $row->sheet_name }} • {{ $row->no_urut }}</td>
                    <td class="px-4 py-3 text-slate-600 line-through">{{ $row->nama_barang }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ $row->kode_persediaan_excel ?? '—' }}</td>
                    <td class="px-3 py-3 text-right text-slate-500">{{ $row->qty }}</td>
                    <td class="px-3 py-3 text-slate-400">{{ $row->unit }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Action panel --}}
<div class="bg-white border border-slate-200 rounded-xl shadow-xs p-5 flex flex-col sm:flex-row justify-between items-center gap-4">
    <div class="text-xs text-slate-500 max-w-sm">
        @if($batch->status === \App\Models\StokUpload::STATUS_SELESAI)
            <span class="font-extrabold text-emerald-700">✓ Batch ini sudah difinalisasi pada {{ $batch->updated_at->format('d M Y, H:i') }}.</span>
        @elseif($canFinalize)
            Klik <strong>Finalisasi Stok</strong> untuk menambahkan {{ $totalApproved }} baris ke Master Barang. Tindakan ini tidak dapat dibatalkan secara langsung.
        @else
            Selesaikan verifikasi kode terlebih dahulu sebelum finalisasi.
        @endif
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('stok-upload.stepper', ['id' => $batch->id, 'step' => 3]) }}"
           class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
            ← Kembali ke Verifikasi
        </a>

        @if($canFinalize)
        <button type="button" onclick="openConfirmModal('finalConfirmModal')"
                class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-lg shadow-sm transition-colors">
            ✓ Finalisasi Stok
        </button>
        @endif

        @if($batch->status === \App\Models\StokUpload::STATUS_SELESAI)
        <button type="button" onclick="openConfirmModal('batalkanStepperModal')"
                class="px-4 py-2 rounded-lg border border-rose-300 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 transition-colors">
            ↩ Batalkan Transaksi
        </button>
        @endif
    </div>
</div>

@endif {{-- end step 4 --}}

<script>
function toggleEl(id) {
    const el  = document.getElementById(id);
    const btn = el?.previousElementSibling?.querySelector('button');
    if (!el) return;
    const hidden = el.classList.toggle('hidden');
    if (btn) btn.textContent = hidden ? 'Tampilkan ▼' : 'Sembunyikan ▲';
}

function setAllAction(action) {
    document.querySelectorAll('[name*="[action]"]').forEach(radio => {
        if (radio.value === action) radio.checked = true;
    });
}

function applySuggestion(index, kode) {
    const sel = document.getElementById('kode_v_' + index);
    if (!sel) return;
    for (let opt of sel.options) {
        if (opt.value === kode) { opt.selected = true; break; }
    }
    const radio = document.getElementById('radio_' + index + '_Perbaiki');
    if (radio) radio.checked = true;
    sel.classList.add('ring-2', 'ring-indigo-400');
    setTimeout(() => sel.classList.remove('ring-2', 'ring-indigo-400'), 1500);
}
</script>

{{-- Confirmation Modals --}}
<x-confirm-modal id="delStepperModal"
    title="Hapus Upload"
    message="Pindahkan upload <strong>{{ $batch->file_name_original }}</strong> ke sampah?"
    variant="danger"
    confirmText="Ya, Hapus"
    :formAction="route('stok-upload.destroy', $batch->id)"
    formMethod="DELETE"
/>

<x-confirm-modal id="delReuploadModal"
    title="Hapus & Upload Ulang"
    message="Hapus batch ini dan upload ulang file Excel yang sudah diperbaiki?"
    variant="danger"
    confirmText="Ya, Hapus"
    :formAction="route('stok-upload.destroy', $batch->id)"
    formMethod="DELETE"
/>

@if($step === 4 && $canFinalize)
<x-confirm-modal
    id="finalConfirmModal"
    title="Finalisasi Stok"
    message="Finalisasi <strong>{{ $totalApproved }} baris</strong> ke Master Barang? Stok akan diperbarui sekarang."
    variant="success"
    confirmText="Ya, Finalisasi"
    :formAction="route(
        'stok-upload.finalisasi',
        $batch->id
    )"
    formMethod="POST"
/>
@endif

@if($batch->status === \App\Models\StokUpload::STATUS_SELESAI)
<x-confirm-modal id="batalkanStepperModal"
    title="Batalkan Transaksi Upload"
    message="Tindakan ini akan membuat transaksi pembalik yang mengurangi stok kembali ke nilai sebelum upload. Stok tidak boleh sudah negatif."
    variant="danger"
    confirmText="Ya, Batalkan"
    :formAction="route('stok-upload.batalkan', $batch->id)"
    formMethod="POST"
>
    <input type="hidden" name="cancellation_reason" value="Pembatalan oleh pengguna">
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800">
        <strong>Batch #{{ $batch->id }}</strong> — {{ $batch->file_name_original }}<br>
        Difinalisasi: {{ $batch->updated_at->format('d M Y, H:i') }} oleh {{ $batch->user?->name ?? '—' }}
    </div>
</x-confirm-modal>
@endif

@endsection
