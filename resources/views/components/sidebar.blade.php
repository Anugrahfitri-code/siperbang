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
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
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
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
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
                            <path d="M16.5 9.4 7.55 4.24a1 1 0 0 0-1.1 0L3.5 6.1a1 1 0 0 0-.5.87v9.97a1 1 0 0 0 .5.86l3 1.87a1 1 0 0 0 1.1 0l4.9-3.08A1 1 0 0 0 13 15.5V8.4a1 1 0 0 0-.5-.86L9.5 5.5"/>
                            <path d="m19 7-4.5-2.82A1 1 0 0 0 13.5 5v12a1 1 0 0 0 .5.86L18.5 21"/>
                            <path d="M16.5 9.4V16"/>
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
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
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
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/>
                            <path d="M8 7h8"/>
                            <path d="M8 11h8"/>
                            <path d="M8 15h5"/>
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
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
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
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
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
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
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
                            <polyline points="9 11 12 14 22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
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
                            <path d="M16.5 9.4 7.55 4.24a1 1 0 0 0-1.1 0L3.5 6.1a1 1 0 0 0-.5.87v9.97a1 1 0 0 0 .5.86l3 1.87a1 1 0 0 0 1.1 0l4.9-3.08A1 1 0 0 0 13 15.5V8.4a1 1 0 0 0-.5-.86L9.5 5.5"/>
                            <path d="m19 7-4.5-2.82A1 1 0 0 0 13.5 5v12a1 1 0 0 0 .5.86L18.5 21"/>
                            <path d="M16.5 9.4V16"/>
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
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
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
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
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
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
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
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </span>
                    <span class="truncate">Excel & Kode Persediaan</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1 2 1-2-1z"/>
                            <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/>
                            <path d="M12 17.5v-11"/>
                        </svg>
                    </span>
                    <span class="truncate">OCR Kuitansi & Pajak</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
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
                    <span class="truncate">Rekap Laporan Excel</span>
                </span>
            </a>

            <a href="{{ route('master-barang.index') }}"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isMasterActive ? 'bg-blue-50 text-blue-700 border-blue-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                    </span>
                    <span class="truncate">Master Barang</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
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
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        </svg>
                    </span>
                    <span class="truncate">BON Digital / Ajukan Baru</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </span>
                    <span class="truncate">Pantau Pengajuan Saya</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16.5 9.4 7.55 4.24a1 1 0 0 0-1.1 0L3.5 6.1a1 1 0 0 0-.5.87v9.97a1 1 0 0 0 .5.86l3 1.87a1 1 0 0 0 1.1 0l4.9-3.08A1 1 0 0 0 13 15.5V8.4a1 1 0 0 0-.5-.86L9.5 5.5"/>
                            <path d="m19 7-4.5-2.82A1 1 0 0 0 13.5 5v12a1 1 0 0 0 .5.86L18.5 21"/>
                            <path d="M16.5 9.4V16"/>
                        </svg>
                    </span>
                    <span class="truncate">Katalog Stok Gudang</span>
                </span>
            </a>

            <a href="/"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </span>
                    <span class="truncate">Histori Pengajuan</span>
                </span>
            </a>

            <a href="{{ route('stok-upload.index') }}"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isExcelActive ? 'bg-amber-50 text-amber-700 border-amber-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700' }}">
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

            <a href="{{ route('master-barang.index') }}"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200
               {{ $isMasterActive ? 'bg-amber-50 text-amber-700 border-amber-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                    </span>
                    <span class="truncate">Master Barang</span>
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
