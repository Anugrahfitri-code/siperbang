@extends('layouts.app')

@section('content')
<div class="space-y-6">
    
    <!-- Header Summary Card -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs flex flex-col md:flex-row justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[10px] font-mono font-bold text-slate-400 uppercase bg-slate-100 px-2 py-0.5 rounded">BATCH #{{ $batch->id }}</span>
                <span class="text-slate-300">•</span>
                <span class="text-xs text-slate-500 font-semibold">{{ $batch->upload_date->format('d M Y H:i') }}</span>
            </div>
            <h1 class="text-lg font-extrabold text-slate-900 tracking-tight mt-1.5 flex items-center gap-2">
                <span>Preview Data Excel:</span>
                <span class="text-indigo-600 font-mono">{{ $batch->file_name_original }}</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Tinjau baris data, status kalkulasi pajak, dan kesesuaian kode persediaan sebelum finalisasi.</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider
                @if($batch->status === 'Selesai') bg-emerald-100 text-emerald-800
                @elseif($batch->status === 'Menunggu Verifikasi') bg-amber-100 text-amber-800
                @elseif($batch->status === 'Sebagian Valid') bg-amber-100 text-amber-800
                @elseif($batch->status === 'Perlu Perbaikan') bg-rose-100 text-rose-800
                @else bg-slate-100 text-slate-800 @endif">
                {{ $batch->status }}
            </span>
        </div>
    </div>

    <!-- Batch Summary Metrics Grid -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs text-center">
            <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">Total Sheet</span>
            <span class="text-xl font-extrabold text-slate-800 block mt-1">{{ $batch->sheets_count }}</span>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs text-center">
            <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">Total Baris</span>
            <span class="text-xl font-extrabold text-slate-800 block mt-1">{{ $batch->rows_count }}</span>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs text-center bg-emerald-50/20 border-emerald-100">
            <span class="text-[10px] font-bold text-emerald-700 uppercase block tracking-wider">Kondisi Valid</span>
            <span class="text-xl font-extrabold text-emerald-600 block mt-1">{{ $batch->valid_rows_count }}</span>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs text-center bg-rose-50/20 border-rose-100">
            <span class="text-[10px] font-bold text-rose-700 uppercase block tracking-wider">Perlu Perbaikan</span>
            <span class="text-xl font-extrabold text-rose-600 block mt-1">{{ $batch->error_rows_count }}</span>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs text-center bg-slate-100/55 border-slate-200">
            <span class="text-[10px] font-bold text-slate-500 uppercase block tracking-wider">Ditolak</span>
            <span class="text-xl font-extrabold text-slate-700 block mt-1">{{ $batch->rejected_rows_count }}</span>
        </div>
    </div>

    <!-- Actions Control Panel -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-xs text-slate-500 max-w-lg">
            <span class="font-bold text-slate-800 block mb-0.5">Alur Tindakan Selanjutnya:</span>
            Petugas Persediaan wajib memverifikasi kecocokan kode persediaan. Data yang disetujui (status verifikasi disetujui) akan masuk ke Master Barang saat finalisasi dilakukan.
        </div>
        
        <div class="flex flex-wrap gap-2 w-full sm:w-auto">
            @if($batch->status === 'Selesai')
                {{-- Finalized — read only --}}
                <span class="text-emerald-700 font-bold text-xs flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 px-4 py-2.5 rounded-lg">
                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Batch ini telah difinalisasi ke Master Barang.
                </span>

            @elseif($batch->status === 'Perlu Perbaikan')
                {{-- Must fix errors first --}}
                <a href="{{ route('stok-upload.perbaiki.index', $batch->id) }}"
                   class="flex-1 sm:flex-initial text-center px-4 py-2.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-colors">
                    Perbaiki Data
                </a>
                <button class="flex-1 sm:flex-initial px-5 py-2.5 rounded-lg text-xs font-bold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed"
                        disabled title="Selesaikan perbaikan data terlebih dahulu sebelum finalisasi">
                    Finalisasi Stok (Terkunci)
                </button>

            @elseif($batch->status === 'Sebagian Valid')
                {{-- Some rows need fixing, some are ready --}}
                <a href="{{ route('stok-upload.perbaiki.index', $batch->id) }}"
                   class="flex-1 sm:flex-initial text-center px-4 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-xs transition-colors">
                    Tinjau &amp; Perbaiki
                </a>
                <a href="{{ route('stok-upload.verifikasi.index', $batch->id) }}"
                   class="flex-1 sm:flex-initial text-center px-4 py-2.5 rounded-lg border border-indigo-600 text-xs font-bold text-indigo-700 bg-white hover:bg-indigo-50 shadow-xs transition-colors">
                    Verifikasi Kode
                </a>

            @else
                {{-- Menunggu Verifikasi / Draft — normal flow --}}
                <a href="{{ route('stok-upload.verifikasi.index', $batch->id) }}"
                   class="flex-1 sm:flex-initial text-center px-4 py-2.5 rounded-lg border border-indigo-600 text-xs font-bold text-indigo-700 bg-white hover:bg-indigo-50 shadow-xs transition-colors">
                    Verifikasi Kode Persediaan
                </a>
                <form action="{{ route('stok-upload.finalisasi', $batch->id) }}" method="POST"
                      onsubmit="return confirm('Apakah Anda yakin ingin memfinalisasi data stok yang disetujui ke Master Barang?')"
                      class="flex-1 sm:flex-initial">
                    @csrf
                    <button type="submit"
                            class="w-full px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                        Finalisasi Stok
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Data Preview Table -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Detail Baris Transaksi Excel</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/50 text-slate-500 font-bold border-b border-slate-200">
                        <th class="px-4 py-3">Sheet</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-2 py-3 text-center">No</th>
                        <th class="px-4 py-3">Kode Excel</th>
                        <th class="px-4 py-3">Verified/Suggested Code</th>
                        <th class="px-4 py-3">Nama Barang</th>
                        <th class="px-3 py-3 text-right">Jumlah</th>
                        <th class="px-3 py-3">Satuan</th>
                        <th class="px-3 py-3 text-right">Harga Unit</th>
                        <th class="px-3 py-3 text-right">Harga + Pajak</th>
                        <th class="px-3 py-3 text-right">Total Excel</th>
                        <th class="px-3 py-3 text-right">Hitungan Sistem</th>
                        <th class="px-3 py-3 text-center">Pajak</th>
                        <th class="px-3 py-3 text-center">Verifikasi</th>
                        <th class="px-4 py-3">Status & Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @foreach($batch->details as $row)
                        <tr class="hover:bg-slate-50/50 transition-colors {{ $row->status_validation === 'Perlu Perbaikan' ? 'bg-rose-50/10' : '' }}">
                            <td class="px-4 py-3 font-mono font-medium text-[11px] text-slate-500">{{ $row->sheet_name }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800 max-w-[150px] truncate" title="{{ $row->supplier }}">{{ $row->supplier }}</td>
                            <td class="px-2 py-3 text-center text-slate-400 font-mono">{{ $row->no_urut }}</td>
                            <td class="px-4 py-3 font-mono text-[11px] {{ $row->kode_persediaan_excel ? 'text-indigo-600 font-semibold' : 'text-rose-500' }}">
                                {{ $row->kode_persediaan_excel ?? '-' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-700 font-semibold bg-slate-50/80">
                                {{ $row->verified_kode_persediaan ?? '-' }}
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-800">{{ $row->nama_barang }}</td>
                            <td class="px-3 py-3 text-right font-bold text-slate-700">{{ number_format($row->qty) }}</td>
                            <td class="px-3 py-3 text-slate-500 font-medium">{{ $row->unit }}</td>
                            <td class="px-3 py-3 text-right font-mono text-slate-600">Rp{{ number_format($row->price_unit) }}</td>
                            <td class="px-3 py-3 text-right font-mono text-slate-600">Rp{{ number_format($row->price_unit_taxed) }}</td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-slate-700">Rp{{ number_format($row->total_excel) }}</td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-indigo-700 bg-indigo-50/10">Rp{{ number_format($row->total_calculated) }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold {{ $row->is_taxed ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $row->is_taxed ? 'PPN 11%' : 'Nett' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                    @if($row->status_verification === 'Setuju') bg-emerald-100 text-emerald-800
                                    @elseif($row->status_verification === 'Tolak') bg-rose-100 text-rose-800
                                    @else bg-amber-100 text-amber-800 @endif">
                                    {{ $row->status_verification }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($row->status_validation === 'Perlu Perbaikan')
                                    <div class="flex items-start gap-1 text-rose-600">
                                        <svg class="h-3.5 w-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span class="text-[10px] leading-tight font-medium" title="{{ $row->notes_error }}">{{ $row->notes_error }}</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-0.5 text-emerald-600">
                                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-[10px] font-semibold">Valid</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
