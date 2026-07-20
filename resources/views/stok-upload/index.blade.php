@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    
    <!-- Title Page Header -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs flex items-center gap-4">
        <div class="bg-indigo-50 text-indigo-600 p-3.5 rounded-lg border border-indigo-100 flex-shrink-0">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="17 8 12 3 7 8" />
                <line x1="12" x2="12" y1="3" y2="15" />
            </svg>
        </div>
        <div>
            <h1 class="text-lg font-extrabold text-slate-900 tracking-tight">Upload Stok & Persediaan Excel</h1>
            <p class="text-xs text-slate-500 mt-0.5">Unggah file laporan belanja belanja barang persediaan untuk memproses penambahan kuantiti stok gudang.</p>
        </div>
    </div>

    <!-- Upload Box Form -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">

        {{-- ── Upload Rejected: error details ── --}}
        @if(session('upload_rejected') && session('upload_errors'))
        @php $uploadErrors = session('upload_errors'); @endphp
        <div class="mb-6 rounded-xl border border-rose-200 overflow-hidden">
            <div class="bg-rose-600 px-5 py-3.5 flex items-center gap-3">
                <svg class="h-5 w-5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-extrabold text-white">File ditolak — {{ session('upload_error_count') }} baris bermasalah dari {{ session('upload_total_count') }} baris total</p>
                    <p class="text-xs text-rose-100 mt-0.5">Tidak ada data yang disimpan. Perbaiki file Excel Anda sesuai daftar di bawah, lalu upload ulang.</p>
                </div>
            </div>
            <div class="bg-rose-50 px-5 py-4 divide-y divide-rose-100">
                @foreach($uploadErrors as $err)
                <div class="py-3 flex gap-4 items-start">
                    <div class="shrink-0 text-[10px] font-mono font-bold text-rose-500 bg-rose-100 px-2 py-1 rounded mt-0.5 whitespace-nowrap">
                        {{ $err['sheet'] }}<br>Baris {{ $err['no_urut'] ?? '?' }}
                    </div>
                    <div class="flex-1">
                        @if($err['nama'] !== '(kosong)')
                        <p class="text-xs font-bold text-slate-800 mb-1">{{ $err['nama'] }}</p>
                        @endif
                        @foreach($err['messages'] as $msg)
                        <p class="text-xs text-rose-700 flex items-start gap-1.5">
                            <span class="text-rose-400 mt-0.5 shrink-0">•</span>{{ $msg }}
                        </p>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            <div class="bg-amber-50 border-t border-amber-200 px-5 py-3 flex items-center gap-2 text-xs text-amber-800">
                <svg class="h-4 w-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Langkah selanjutnya:</strong> buka file Excel Anda, perbaiki baris yang tercantum di atas, simpan, lalu upload kembali melalui form di bawah.</span>
            </div>
        </div>
        @endif
        <form action="{{ route('stok-upload.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih File Excel Laporan Belanja</label>
                
                <div class="border-2 border-dashed border-slate-200 hover:border-indigo-500 rounded-lg p-8 text-center bg-slate-50/50 hover:bg-indigo-50/10 transition-all cursor-pointer relative group">
                    <input type="file" name="file_excel" id="file_excel" accept=".xlsx, .xls" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required onchange="updateFileName(this)">
                    
                    <div class="space-y-2 pointer-events-none">
                        <div class="mx-auto w-12 h-12 bg-white rounded-lg shadow-sm border border-slate-200 flex items-center justify-center text-slate-400 group-hover:text-indigo-600 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-700" id="file-name-label">Seret & lepas file Anda ke sini, atau klik untuk menelusuri</p>
                        <p class="text-[10px] text-slate-400">Hanya menerima format Excel (.xlsx, .xls) dengan ukuran maks 10MB</p>
                    </div>
                </div>
                
                @error('file_excel')
                    <p class="text-rose-600 text-xs font-semibold mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-2">
                <a href="{{ route('stok-upload.template') }}" class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg border border-indigo-200 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Template Excel
                </a>

                <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                    <span>Mulai Proses Upload →</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Excel Layout Format Guidance Card -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
        <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">Petunjuk Format Dokumen Excel</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-2">
                <h4 class="font-bold text-indigo-700">1. Struktur Dokumen</h4>
                <ul class="list-disc pl-4 space-y-1 text-slate-500">
                    <li>Dapat berisi banyak sheet (sistem akan membaca <strong class="text-slate-700">seluruh sheet</strong>).</li>
                    <li>Setiap sheet mewakili satu nota/transaksi (contoh nama: <code class="bg-slate-100 px-1 rounded text-slate-700">020126 RP</code>).</li>
                    <li>Informasi Supplier/Nama Toko di baris 2 Kolom A (contoh: <code class="bg-slate-100 px-1 rounded text-indigo-700">SUPPLIER : REDZKY PLASTIK</code>).</li>
                    <li>Header tabel di baris 4 dan baris data dimulai dari baris 5.</li>
                </ul>
            </div>
            
            <div class="space-y-2">
                <h4 class="font-bold text-indigo-700">2. Layout Tabel yang Didukung</h4>
                <ul class="list-disc pl-4 space-y-1 text-slate-500">
                    <li><strong class="text-slate-700">Format Tanpa Pajak</strong>: A (No), B (Kode), C (Nama), D (Jumlah), E (Satuan), F (Harga Satuan), G (Total).</li>
                    <li><strong class="text-slate-700">Format Dengan Pajak</strong>: A (No), B (Kode), C (Nama), D (Jumlah), E (Satuan), F (Harga Satuan), G (Harga + Pajak), H (Total), I (Pajak).</li>
                    <li>Baris total di baris paling bawah akan dilewati secara otomatis.</li>
                </ul>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-100 rounded-lg p-4 text-[11px] text-slate-600 flex items-start gap-2.5">
            <svg class="h-4 w-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <span class="font-extrabold text-amber-800">Catatan Perhitungan Pajak:</span> Jika sheet memuat kolom <strong class="text-slate-700">Pajak</strong> (bernilai 1.11 atau formula serupa) atau kolom <strong class="text-slate-700">Harga Satuan + Pajak</strong>, sistem akan otomatis melakukan perbandingan total belanja dengan menyertakan PPN 11% sesuai aturan instansi.
            </div>
        </div>
    </div>
</div>

<script>
    function updateFileName(input) {
        const file = input.files[0];
        const label = document.getElementById('file-name-label');
        if (file) {
            label.innerHTML = `File terpilih: <span class="text-indigo-600 font-bold font-mono text-sm">${file.name}</span> (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
        } else {
            label.innerText = 'Seret & lepas file Anda ke sini, atau klik untuk menelusuri';
        }
    }
</script>
@endsection
