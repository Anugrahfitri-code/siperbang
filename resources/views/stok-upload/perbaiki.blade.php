@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- ── Page Header ── --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[10px] font-mono font-bold text-slate-400 uppercase bg-slate-100 px-2 py-0.5 rounded">BATCH #{{ $batch->id }}</span>
                <span class="text-slate-300">•</span>
                <span class="text-xs text-slate-500 font-semibold">{{ $batch->file_name_original }}</span>
                <span class="text-slate-300">•</span>
                <span class="text-xs text-slate-500">{{ $batch->upload_date->format('d M Y H:i') }}</span>
            </div>
            <h1 class="text-lg font-extrabold text-slate-900 tracking-tight mt-1.5">Perbaiki Data</h1>
            <p class="text-xs text-slate-500 mt-0.5">Edit kolom yang bermasalah pada baris di bawah. Kolom hasil kalkulasi sistem dikunci otomatis.</p>
        </div>
        <a href="{{ route('stok-upload.preview', $batch->id) }}"
           class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 shadow-xs shrink-0">
            ← Kembali ke Preview
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

    {{-- ── Stats Bar ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs text-center">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Baris</span>
            <span class="text-2xl font-extrabold text-slate-800 block mt-1">{{ $batch->rows_count }}</span>
        </div>
        <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 shadow-xs text-center">
            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Sudah Valid</span>
            <span class="text-2xl font-extrabold text-emerald-700 block mt-1">{{ $validRows->count() }}</span>
        </div>
        <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 shadow-xs text-center">
            <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider block">Perlu Diperbaiki</span>
            <span class="text-2xl font-extrabold text-rose-700 block mt-1">{{ $errorRows->count() }}</span>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs text-center">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Batch</span>
            <span class="text-sm font-extrabold mt-1 block
                @if($batch->status === 'Perlu Perbaikan') text-rose-700
                @elseif($batch->status === 'Sebagian Valid') text-amber-700
                @else text-emerald-700 @endif">
                {{ $batch->status }}
            </span>
        </div>
    </div>

    {{-- ── No errors left — ready to submit ── --}}
    @if($errorRows->count() === 0)
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3 text-emerald-800">
                <svg class="h-6 w-6 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="text-sm font-extrabold">Semua baris sudah valid!</p>
                    <p class="text-xs mt-0.5">Klik <strong>Ajukan Ulang</strong> untuk mengirim data ke antrian verifikasi.</p>
                </div>
            </div>
            <form action="{{ route('stok-upload.ajukan-ulang', $batch->id) }}" method="POST"
                  onsubmit="return confirm('Ajukan ulang batch ini ke Menunggu Verifikasi?')">
                @csrf
                <button type="submit"
                        class="px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-colors">
                    Ajukan Ulang →
                </button>
            </form>
        </div>
    @endif

    {{-- ── Error Rows Edit Form ── --}}
    @if($errorRows->count() > 0)
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 bg-rose-50 border-b border-rose-100 flex items-center gap-2">
            <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <h2 class="text-xs font-extrabold text-rose-800 uppercase tracking-wider">
                Baris Bermasalah ({{ $errorRows->count() }} baris)
            </h2>
        </div>

        <form action="{{ route('stok-upload.perbaiki.store', $batch->id) }}" method="POST" id="formPerbaiki">
            @csrf
            <div class="divide-y divide-slate-100">
                @foreach($errorRows as $i => $row)
                <div class="p-5 {{ $loop->even ? 'bg-slate-50/40' : '' }}">

                    {{-- Row header --}}
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[10px] font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">
                                    {{ $row->sheet_name }} • No. {{ $row->no_urut }}
                                </span>
                                @if($row->is_duplicate)
                                    <span class="text-[10px] font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded">DUPLIKAT</span>
                                @endif
                            </div>
                            <p class="text-sm font-extrabold text-slate-900 mt-1">{{ $row->nama_barang }}</p>
                            @if($row->supplier)
                                <p class="text-[11px] text-slate-400 mt-0.5">Supplier: {{ $row->supplier }}</p>
                            @endif
                        </div>

                        {{-- Error reason badge --}}
                        <div class="bg-rose-50 border border-rose-200 rounded-lg px-3 py-2 text-xs text-rose-700 max-w-sm">
                            <div class="flex items-start gap-1.5">
                                <svg class="h-3.5 w-3.5 text-rose-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="font-semibold leading-snug">{{ $row->notes_error ?? 'Data tidak valid.' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Kode persediaan info + suggestion --}}
                    @if($row->kode_persediaan_excel || $row->suggested_kode_persediaan)
                    <div class="mb-4 flex flex-wrap gap-3 text-xs">
                        @if($row->kode_persediaan_excel)
                        <div class="bg-slate-100 rounded-lg px-3 py-2">
                            <span class="text-slate-500 font-medium block">Kode dari Excel</span>
                            <span class="font-mono font-bold text-indigo-700">{{ $row->kode_persediaan_excel }}</span>
                        </div>
                        @endif
                        @if($row->suggested_kode_persediaan)
                        <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 flex items-center gap-2">
                            <div>
                                <span class="text-indigo-500 font-medium block">Saran Sistem</span>
                                <span class="font-mono font-bold text-indigo-800">{{ $row->suggested_kode_persediaan }}</span>
                            </div>
                            <button type="button"
                                    onclick="useSuggestedCode({{ $i }}, '{{ $row->suggested_kode_persediaan }}')"
                                    class="ml-2 px-2.5 py-1 rounded bg-indigo-600 text-white text-[10px] font-bold hover:bg-indigo-700 transition-colors">
                                Gunakan Kode Saran
                            </button>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Hidden detail_id --}}
                    <input type="hidden" name="rows[{{ $i }}][detail_id]" value="{{ $row->id }}">

                    {{-- Editable fields grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                        {{-- Kode Persediaan --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                                Kode Persediaan
                            </label>
                            <select name="rows[{{ $i }}][kode_persediaan]"
                                    id="kode_select_{{ $i }}"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs font-mono focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition-all">
                                <option value="">-- Pilih Kode --</option>
                                @foreach($masterCodes as $code)
                                    <option value="{{ $code->kode }}"
                                        {{ ($row->verified_kode_persediaan ?? $row->kode_persediaan_excel) === $code->kode ? 'selected' : '' }}>
                                        {{ $code->kode }} — {{ $code->nama_barang }}
                                        ({{ $code->kategoriBarang->nama ?? 'Umum' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Nama Barang --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                                Nama Barang
                            </label>
                            <input type="text"
                                   name="rows[{{ $i }}][nama_barang]"
                                   value="{{ old("rows.{$i}.nama_barang", $row->nama_barang) }}"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition-all"
                                   required>
                        </div>

                        {{-- Jumlah --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                                Jumlah
                            </label>
                            <input type="number"
                                   name="rows[{{ $i }}][qty]"
                                   value="{{ old("rows.{$i}.qty", $row->qty) }}"
                                   min="1"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition-all"
                                   required
                                   onchange="recalcRow({{ $i }})"
                                   oninput="recalcRow({{ $i }})">
                        </div>

                        {{-- Satuan --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                                Satuan
                            </label>
                            <input type="text"
                                   name="rows[{{ $i }}][unit]"
                                   value="{{ old("rows.{$i}.unit", $row->unit) }}"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition-all"
                                   required>
                        </div>

                        {{-- Harga Unit --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                                Harga Unit (Rp)
                            </label>
                            <input type="number"
                                   name="rows[{{ $i }}][price_unit]"
                                   value="{{ old("rows.{$i}.price_unit", (int) $row->price_unit) }}"
                                   min="0"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition-all"
                                   required
                                   onchange="recalcRow({{ $i }})"
                                   oninput="recalcRow({{ $i }})">
                        </div>

                        {{-- Jenis Pajak --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                                Jenis Pajak
                            </label>
                            <select name="rows[{{ $i }}][is_taxed]"
                                    id="tax_select_{{ $i }}"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition-all"
                                    onchange="recalcRow({{ $i }})">
                                <option value="0" {{ !$row->is_taxed ? 'selected' : '' }}>Nett (Tanpa PPN)</option>
                                <option value="1" {{ $row->is_taxed ? 'selected' : '' }}>PPN 11%</option>
                            </select>
                        </div>
                    </div>

                    {{-- Calculated (read-only) preview --}}
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Harga Setelah Pajak</span>
                            <span id="display_taxed_{{ $i }}" class="font-mono font-bold text-slate-700 text-sm">
                                Rp{{ number_format((int) $row->price_unit_taxed, 0, ',', '.') }}
                            </span>
                            <span class="text-[9px] text-slate-400 block mt-0.5">Dihitung otomatis</span>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Excel (Asli)</span>
                            <span class="font-mono font-bold text-slate-500 text-sm">
                                Rp{{ number_format((int) $row->total_excel, 0, ',', '.') }}
                            </span>
                            <span class="text-[9px] text-slate-400 block mt-0.5">Tidak dapat diubah</span>
                        </div>
                        <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2">
                            <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider block">Hitungan Sistem</span>
                            <span id="display_total_{{ $i }}" class="font-mono font-bold text-indigo-700 text-sm">
                                Rp{{ number_format((int) $row->total_calculated, 0, ',', '.') }}
                            </span>
                            <span class="text-[9px] text-indigo-400 block mt-0.5">Dihitung otomatis</span>
                        </div>
                    </div>

                    {{-- Data stored for JS recalc --}}
                    <input type="hidden" id="price_unit_raw_{{ $i }}" value="{{ (int) $row->price_unit }}">

                </div>
                @endforeach
            </div>

            {{-- Form Footer --}}
            <div class="px-5 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3">
                <p class="text-xs text-slate-500">
                    Simpan perubahan akan mengunci kolom kalkulasi dan mereset status baris ke
                    <strong class="text-slate-700">Menunggu Verifikasi</strong>.
                </p>
                <div class="flex gap-3">
                    <a href="{{ route('stok-upload.preview', $batch->id) }}"
                       class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition-colors">
                        Simpan Perbaikan
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endif

    {{-- ── Valid Rows (read-only summary) ── --}}
    @if($validRows->count() > 0)
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <h2 class="text-xs font-extrabold text-emerald-800 uppercase tracking-wider">
                    Baris Valid ({{ $validRows->count() }} baris) — Tidak perlu diedit
                </h2>
            </div>
            <button type="button" onclick="toggleValidRows()"
                    class="text-xs text-emerald-600 font-semibold hover:text-emerald-800 transition-colors" id="toggleBtn">
                Sembunyikan ▲
            </button>
        </div>

        <div id="validRowsTable" class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <th class="px-4 py-3">Sheet / No</th>
                        <th class="px-4 py-3">Nama Barang</th>
                        <th class="px-4 py-3">Kode Persediaan</th>
                        <th class="px-3 py-3 text-right">Qty</th>
                        <th class="px-3 py-3">Satuan</th>
                        <th class="px-3 py-3 text-right">Harga Unit</th>
                        <th class="px-3 py-3 text-center">Pajak</th>
                        <th class="px-3 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($validRows as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-500">
                            {{ $row->sheet_name }} • {{ $row->no_urut }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $row->nama_barang }}</td>
                        <td class="px-4 py-3 font-mono text-[11px] text-indigo-700 font-bold">
                            {{ $row->verified_kode_persediaan ?? $row->kode_persediaan_excel ?? '-' }}
                        </td>
                        <td class="px-3 py-3 text-right font-bold text-slate-700">{{ number_format($row->qty) }}</td>
                        <td class="px-3 py-3 text-slate-500">{{ $row->unit }}</td>
                        <td class="px-3 py-3 text-right font-mono text-slate-600">Rp{{ number_format((int) $row->price_unit, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-center">
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold {{ $row->is_taxed ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $row->is_taxed ? 'PPN 11%' : 'Nett' }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-right font-mono font-bold text-emerald-700">
                            Rp{{ number_format((int) $row->total_calculated, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── Ajukan Ulang footer (always visible when no errors left) ── --}}
    @if($errorRows->count() === 0)
    <div class="flex justify-end">
        <form action="{{ route('stok-upload.ajukan-ulang', $batch->id) }}" method="POST"
              onsubmit="return confirm('Ajukan ulang batch ini ke Menunggu Verifikasi?')">
            @csrf
            <button type="submit"
                    class="px-8 py-3 rounded-xl text-sm font-extrabold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md transition-colors">
                ✓ Ajukan Ulang ke Verifikasi
            </button>
        </form>
    </div>
    @endif

</div>

<script>
    const TAX_RATE = 0.11;

    function formatRupiah(number) {
        return 'Rp' + Math.round(number).toLocaleString('id-ID');
    }

    function recalcRow(index) {
        const qtyInput       = document.querySelector(`input[name="rows[${index}][qty]"]`);
        const priceInput     = document.querySelector(`input[name="rows[${index}][price_unit]"]`);
        const taxSelect      = document.getElementById(`tax_select_${index}`);
        const displayTaxed   = document.getElementById(`display_taxed_${index}`);
        const displayTotal   = document.getElementById(`display_total_${index}`);

        if (!qtyInput || !priceInput || !taxSelect) return;

        const qty       = parseInt(qtyInput.value) || 0;
        const price     = parseFloat(priceInput.value) || 0;
        const isTaxed   = taxSelect.value === '1';

        const priceTaxed = isTaxed ? price * (1 + TAX_RATE) : price;
        const total      = priceTaxed * qty;

        if (displayTaxed) displayTaxed.textContent = formatRupiah(priceTaxed);
        if (displayTotal) displayTotal.textContent  = formatRupiah(total);
    }

    function useSuggestedCode(index, kode) {
        const select = document.getElementById(`kode_select_${index}`);
        if (select) {
            for (let opt of select.options) {
                if (opt.value === kode) {
                    opt.selected = true;
                    break;
                }
            }
            // Briefly highlight the dropdown
            select.classList.add('ring-2', 'ring-indigo-400', 'border-indigo-400');
            setTimeout(() => select.classList.remove('ring-2', 'ring-indigo-400', 'border-indigo-400'), 1500);
        }
    }

    function toggleValidRows() {
        const table = document.getElementById('validRowsTable');
        const btn   = document.getElementById('toggleBtn');
        if (!table) return;
        if (table.classList.contains('hidden')) {
            table.classList.remove('hidden');
            btn.textContent = 'Sembunyikan ▲';
        } else {
            table.classList.add('hidden');
            btn.textContent = 'Tampilkan ▼';
        }
    }
</script>
@endsection
