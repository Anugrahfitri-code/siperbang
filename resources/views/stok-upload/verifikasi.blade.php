@extends('layouts.app')

@section('content')
<div class="space-y-6">
    
    <!-- Title Page Header -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-mono font-bold text-slate-400 uppercase bg-slate-100 px-2 py-0.5 rounded">BATCH #{{ $batch->id }}</span>
                <span class="text-slate-300">•</span>
                <span class="text-xs text-slate-500 font-semibold">{{ $batch->file_name_original }}</span>
            </div>
            <h1 class="text-lg font-extrabold text-slate-900 tracking-tight mt-1.5">Verifikasi Kode Persediaan</h1>
            <p class="text-xs text-slate-500 mt-0.5">Tentukan keputusan verifikasi untuk setiap item belanja. Setujui, koreksi kode persediaan, atau tolak barang.</p>
        </div>
        
        <a href="{{ route('stok-upload.preview', $batch->id) }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 shadow-xs">
            ← Kembali ke Preview
        </a>
    </div>

    <!-- Alert Note -->
    <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-xs text-amber-800 flex items-start gap-2.5 shadow-xs">
        <svg class="h-4.5 w-4.5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <div>
            <span class="font-extrabold">Verifikasi Kepatuhan Kode Persediaan:</span> Silakan tinjau kecocokan nama barang dengan kode persediaan yang diusulkan oleh sistem. Anda dapat membetulkan kode dengan memilih opsi <strong class="text-slate-800">Perbaiki</strong> dan memilih dari katalog kode persediaan resmi.
        </div>
    </div>

    <!-- Verification Form -->
    <form action="{{ route('stok-upload.verifikasi.store', $batch->id) }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                            <th class="px-4 py-3">Nama Barang / Deskripsi Excel</th>
                            <th class="px-3 py-3">Kuantitas</th>
                            <th class="px-4 py-3">Kode Excel</th>
                            <th class="px-4 py-3">Kode Sistem (Saran)</th>
                            <th class="px-4 py-3">Keputusan Tindakan</th>
                            <th class="px-4 py-3">Pilih Kode Koreksi (Untuk 'Perbaiki')</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-sans">
                        @foreach($batch->details as $index => $row)
                            <tr class="hover:bg-slate-50/50 transition-colors {{ $row->status_validation === 'Perlu Perbaikan' ? 'bg-rose-50/5' : '' }}">
                                <!-- Item name -->
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-slate-850 text-sm">{{ $row->nama_barang }}</div>
                                    <div class="text-xs text-slate-400 font-medium mt-0.5">Sheet: {{ $row->sheet_name }} • Supplier: {{ $row->supplier }}</div>
                                    @if($row->notes_error)
                                        <div class="text-xs text-rose-500 mt-1 font-semibold flex items-center gap-0.5">
                                            <svg class="h-3 w-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            {{ $row->notes_error }}
                                        </div>
                                    @endif
                                </td>
                                
                                <!-- Quantity -->
                                <td class="px-3 py-3.5 font-bold text-slate-700">
                                    {{ number_format($row->qty) }} {{ $row->unit }}
                                </td>

                                <!-- Original Excel Code -->
                                <td class="px-4 py-3.5 font-mono text-xs {{ $row->kode_persediaan_excel ? 'text-indigo-600 font-semibold' : 'text-slate-400' }}">
                                    {{ $row->kode_persediaan_excel ?? '[KOSONG]' }}
                                </td>

                                <!-- Suggested System Code -->
                                <td class="px-4 py-3.5 font-mono text-xs text-indigo-700 font-extrabold bg-indigo-50/20">
                                    {{ $row->suggested_kode_persediaan ?? '-' }}
                                </td>

                                <!-- Decisions -->
                                <td class="px-4 py-3.5">
                                    <input type="hidden" name="items[{{ $index }}][detail_id]" value="{{ $row->id }}">
                                    
                                    <div class="flex items-center gap-3">
                                        <!-- Radio Setuju -->
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" name="items[{{ $index }}][action]" value="Setuju" 
                                                class="text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5"
                                                {{ $row->status_verification === 'Setuju' || $row->status_verification === 'Pending' ? 'checked' : '' }}
                                                onchange="toggleCorrectionDropdown({{ $index }}, false)">
                                            <span class="text-xs font-bold text-emerald-700">Setujui</span>
                                        </label>

                                        <!-- Radio Perbaiki -->
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" name="items[{{ $index }}][action]" value="Perbaiki" 
                                                class="text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5"
                                                {{ $row->status_verification === 'Perbaiki' ? 'checked' : '' }}
                                                onchange="toggleCorrectionDropdown({{ $index }}, true)">
                                            <span class="text-xs font-bold text-amber-700">Perbaiki</span>
                                        </label>

                                        <!-- Radio Tolak -->
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" name="items[{{ $index }}][action]" value="Tolak" 
                                                class="text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5"
                                                {{ $row->status_verification === 'Tolak' ? 'checked' : '' }}
                                                onchange="toggleCorrectionDropdown({{ $index }}, false)">
                                            <span class="text-xs font-bold text-rose-700">Tolak</span>
                                        </label>
                                    </div>
                                </td>

                                <!-- Correction Dropdown -->
                                <td class="px-4 py-3.5">
                                    <select name="items[{{ $index }}][kode_persediaan]" id="select_koreksi_{$index}"
                                        class="w-full max-w-[220px] px-2.5 py-1.5 rounded border border-slate-200 bg-white text-xs font-mono disabled:opacity-50 disabled:bg-slate-50 transition-all"
                                        {{ $row->status_verification === 'Perbaiki' ? '' : 'disabled' }}>
                                        <option value="">-- Pilih Kode Resmi --</option>
                                        @foreach($masterCodes as $code)
                                            <option value="{{ $code->kode }}" {{ $row->verified_kode_persediaan == $code->kode ? 'selected' : '' }}>
                                                {{ $code->kode }} - {{ $code->nama_barang }} ({{ $code->kategoriBarang->nama ?? 'Lain' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Submit Footer -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <a href="{{ route('stok-upload.preview', $batch->id) }}" class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                    Simpan Hasil Verifikasi
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function toggleCorrectionDropdown(index, isEnabled) {
        const select = document.getElementById(`select_koreksi_{$index}`);
        if (isEnabled) {
            select.removeAttribute('disabled');
            select.setAttribute('required', 'required');
            select.focus();
        } else {
            select.setAttribute('disabled', 'disabled');
            select.removeAttribute('required');
        }
    }

    // Initialize dropdowns on page load based on check states
    document.addEventListener("DOMContentLoaded", function() {
        @foreach($batch->details as $index => $row)
            const radioPerbaiki = document.querySelector(`input[name="items[{{ $index }}][action]"][value="Perbaiki"]`);
            if (radioPerbaiki && radioPerbaiki.checked) {
                toggleCorrectionDropdown({{ $index }}, true);
            }
        @endforeach
    });
</script>
@endsection
