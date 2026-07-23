@php
    $role = auth()->user()->role ?? '';
    $isStokUpload = request()->is('stok-upload*');
    $isMasterBarang = request()->is('master-barang*');
    $isHome = request()->routeIs('home');

    $isExcelActive = $isStokUpload;
    $isMasterActive = $isMasterBarang;
    $isTindakanActive = $isHome;
@endphp
        {{-- Nav items --}}
        <nav class="flex-1 space-y-1 overflow-y-auto pt-5 pb-5">

@if($role === 'Superadmin')
            {{-- ═══ Superadmin ═══ --}}
            <p class="px-7 pb-2 pt-5 text-xs font-extrabold uppercase tracking-[0.14em] text-slate-400">Manajemen Sistem</p>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isTindakanActive ? 'bg-blue-50 text-blue-700 border-blue-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    <span class="truncate">Kelola Pengguna</span>
                </span>
            </a>

            <p class="px-7 pb-2 pt-5 text-xs font-extrabold uppercase tracking-[0.14em] text-slate-400">Petugas Persediaan</p>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isTindakanActive ? 'bg-blue-50 text-blue-700 border-blue-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>
                        </svg>
                    </span>
                    <span class="truncate">Daftar Tindakan</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.5l-5-3-4.03 2.42Z"/><path d="m7 16.5-4.74-2.85"/><path d="m7 16.5 5-3"/><path d="M7 16.5v5.17"/><path d="M12 13.5V19l3.97 2.38a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5l-5 3Z"/><path d="m17 16.5-5-3"/><path d="m17 16.5 4.74-2.85"/><path d="M17 16.5v5.17"/><path d="M7.97 4.42A2 2 0 0 0 7 6.13v4.37l5 3 5-3V6.13a2 2 0 0 0-.97-1.71l-3-1.8a2 2 0 0 0-2.06 0l-3 1.8Z"/><path d="M12 8 7.26 5.15"/><path d="m12 8 4.74-2.85"/><path d="M12 13.5V8"/>
                        </svg>
                    </span>
                    <span class="truncate">Pengecekan & Pemenuhan</span>
                </span>
            </a>

            <a href="{{ route('stok-upload.index') }}"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isExcelActive ? 'bg-blue-50 text-blue-700 border-blue-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/>
                        </svg>
                    </span>
                    <span class="truncate">Excel & Kode Persediaan</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17V7"/>
                        </svg>
                    </span>
                    <span class="truncate">OCR Kuitansi & Pajak</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 18v-1"/><path d="M12 18v-6"/><path d="M16 18v-3"/>
                        </svg>
                    </span>
                    <span class="truncate">Rekap Laporan Excel</span>
                </span>
            </a>

            <a href="{{ route('master-barang.index') }}"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isMasterActive ? 'bg-blue-50 text-blue-700 border-blue-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
                        </svg>
                    </span>
                    <span class="truncate">Master Barang</span>
                </span>
            </a>

            <p class="px-7 pb-2 pt-5 text-xs font-extrabold uppercase tracking-[0.14em] text-slate-400">Ketua Tim Kerja</p>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/>
                        </svg>
                    </span>
                    <span class="truncate">BON Digital / Ajukan Baru</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>
                        </svg>
                    </span>
                    <span class="truncate">Pantau Pengajuan</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><circle cx="12" cy="12" r="3"/><path d="m16 16-1.9-1.9"/>
                        </svg>
                    </span>
                    <span class="truncate">Katalog Stok Gudang</span>
                </span>
            </a>

            <p class="px-7 pb-2 pt-5 text-xs font-extrabold uppercase tracking-[0.14em] text-slate-400">Laporan & Audit</p>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>
                        </svg>
                    </span>
                    <span class="truncate">Audit Log Sistem</span>
                </span>
            </a>

@elseif($role === 'Petugas Persediaan')
            {{-- ═══ Petugas Persediaan ═══ --}}
            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isTindakanActive ? 'bg-blue-50 text-blue-700 border-blue-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>
                        </svg>
                    </span>
                    <span class="truncate">Daftar Tindakan</span>
                </span>
                <span class="ml-2 min-w-6 shrink-0 rounded-full px-2 py-0.5 text-center text-xs font-extrabold {{ $isTindakanActive ? 'bg-blue-200 text-blue-800' : 'border border-amber-200 bg-amber-50 text-amber-700' }}">
                    1
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.5l-5-3-4.03 2.42Z"/><path d="m7 16.5-4.74-2.85"/><path d="m7 16.5 5-3"/><path d="M7 16.5v5.17"/><path d="M12 13.5V19l3.97 2.38a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5l-5 3Z"/><path d="m17 16.5-5-3"/><path d="m17 16.5 4.74-2.85"/><path d="M17 16.5v5.17"/><path d="M7.97 4.42A2 2 0 0 0 7 6.13v4.37l5 3 5-3V6.13a2 2 0 0 0-.97-1.71l-3-1.8a2 2 0 0 0-2.06 0l-3 1.8Z"/><path d="M12 8 7.26 5.15"/><path d="m12 8 4.74-2.85"/><path d="M12 13.5V8"/>
                        </svg>
                    </span>
                    <span class="truncate">Pengecekan & Pemenuhan</span>
                </span>
            </a>

            <a href="{{ route('stok-upload.index') }}"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isExcelActive ? 'bg-blue-50 text-blue-700 border-blue-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/>
                        </svg>
                    </span>
                    <span class="truncate">Excel & Kode Persediaan</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17V7"/>
                        </svg>
                    </span>
                    <span class="truncate">OCR Kuitansi & Pajak</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 18v-1"/><path d="M12 18v-6"/><path d="M16 18v-3"/>
                        </svg>
                    </span>
                    <span class="truncate">Rekap Laporan Excel</span>
                </span>
            </a>

            <a href="{{ route('master-barang.index') }}"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isMasterActive ? 'bg-blue-50 text-blue-700 border-blue-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
                        </svg>
                    </span>
                    <span class="truncate">Master Barang</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>
                        </svg>
                    </span>
                    <span class="truncate">Histori & Audit Log</span>
                </span>
            </a>

@else
            {{-- ═══ Ketua Tim Kerja ═══ --}}
            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isTindakanActive ? 'bg-amber-50 text-amber-700 border-amber-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </span>
                    <span class="truncate">Dashboard Ketua Tim</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/>
                        </svg>
                    </span>
                    <span class="truncate">BON Digital / Ajukan Baru</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>
                        </svg>
                    </span>
                    <span class="truncate">Pantau Pengajuan Saya</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><circle cx="12" cy="12" r="3"/><path d="m16 16-1.9-1.9"/>
                        </svg>
                    </span>
                    <span class="truncate">Katalog Stok Gudang</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>
                        </svg>
                    </span>
                    <span class="truncate">Histori Pengajuan</span>
                </span>
            </a>

            <a href="{{ route('stok-upload.index') }}"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isExcelActive ? 'bg-emerald-50 text-emerald-700 border-emerald-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-emerald-50/70 hover:text-emerald-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </span>
                    <span class="truncate">Upload Stok Excel</span>
                </span>
            </a>

@endif

        </nav>

        {{-- Sidebar footer --}}
        <div class="mt-auto border-t border-slate-200 px-5 py-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                    {{-- ShieldCheck icon --}}
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <polyline points="9 12 11 14 15 10"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-extrabold text-slate-800">SIPERBANG</p>
                    <p class="mt-0.5 text-xs font-semibold text-slate-400">v1.1.0</p>
                    <p class="mt-1 text-xs font-semibold text-slate-400">© 2026 KOMDIGI</p>
                </div>
            </div>
        </div>
