<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPERBANG - Modul Stok & Persediaan</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Vite CSS -->
    @vite(['resources/css/app.css'])
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">

        {{-- ── Baris 1: Logo | KOMDIGI | User + Back ── --}}
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-8">
                    <a href="/" class="flex items-center gap-2">
                        <div class="relative w-12 h-12 flex-shrink-0">
                            <img
                                src="{{ asset('images/siperbang-logo.png') }}"
                                alt="Logo SIPERBANG"
                                class="w-full h-full object-contain select-none pointer-events-none"
                            >
                        </div>
                        <div class="flex flex-col select-none text-left">
                          <div class="text-2xl font-bold tracking-tight leading-none flex items-center">
                            <span class="text-[#0055A5]">S</span>
                                <span class="text-[#00A1E4]">I</span>
                                <span class="text-[#013A70]">PERB</span>
                                <span class="text-[#00A1E4]">A</span>
                                <span class="text-[#0055A5]">NG</span>
                          </div>
                          <span class="text-xs font-medium tracking-wide mt-1 leading-none uppercase text-[#7A7A7A]">
                            Sistem Informasi Penyediaan Barang
                          </span>
                        </div>
                    </a>

                    <div class="hidden md:block h-8 w-px bg-slate-200"></div>

                    <div class="hidden md:flex items-center gap-2">
                        <div class="relative w-9 h-9 flex-shrink-0">
                            <img src="{{ asset('images/komdigi-logo.png') }}" alt="Logo KOMDIGI" class="w-full h-full object-contain select-none pointer-events-none">
                        </div>
                      <div class="flex flex-col select-none text-left">
                        <span class="text-sm font-extrabold text-[#4A4A4A] tracking-wider leading-none">
                          KOMDIGI
                        </span>
                        <span class="text-[10px] text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">
                          Kementerian Komunikasi dan Digital<br />Republik Indonesia
                        </span>
                      </div>
                    </div>
                    
                    <!-- Top small nav links removed (duplicate of lower nav) -->
                </div>



                {{-- Kanan: User info + Tombol kembali --}}
                <div class="flex items-center gap-3 shrink-0">
                    <div class="hidden sm:block text-right">
                        <span class="text-xs font-bold text-slate-800 block leading-tight">
                            {{ auth()->check() ? auth()->user()->name : 'Petugas Persediaan' }}
                        </span>
                        <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider block">
                            {{ auth()->check() ? auth()->user()->role : 'Petugas Persediaan' }}
                        </span>
                    </div>
                    <button onclick="window.location.href='/'" type="button"
                            title="Kembali ke aplikasi utama"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-white border-2 border-slate-200
                                   hover:border-slate-400 hover:bg-slate-50 text-slate-600 transition-all shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Baris 2: Nav Links (desktop) ── --}}
        <div class="hidden md:block border-t border-slate-100 bg-white">
            <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center h-10">
                    <a href="/stok-upload"
                       class="flex-1 h-full inline-flex items-center justify-center text-[13px] font-semibold border-b-2 transition-colors
                       {{ request()->is('stok-upload') && !request()->is('stok-upload/*')
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        Upload Excel
                    </a>
                    <a href="/stok-upload/riwayat"
                       class="flex-1 h-full inline-flex items-center justify-center text-[13px] font-semibold border-b-2 transition-colors
                       {{ request()->is('stok-upload/riwayat*') || request()->is('stok-upload/*/stepper') || request()->is('stok-upload/sampah')
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        Riwayat Upload
                    </a>
                    <a href="/master-barang"
                       class="flex-1 h-full inline-flex items-center justify-center text-[13px] font-semibold border-b-2 transition-colors
                       {{ request()->is('master-barang*')
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        Master Barang
                    </a>
                </nav>
            </div>
        </div>

    </header>


    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Alerts Block -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-150 rounded-lg text-emerald-800 text-xs font-medium flex items-center gap-2 shadow-xs">
                <svg class="h-4 w-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-rose-50 border border-rose-150 rounded-lg text-rose-800 text-xs font-medium flex items-center gap-2 shadow-xs">
                <svg class="h-4 w-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400 mt-auto">
        <p>&copy; 2026 SIPERBANG - Kementerian Komunikasi dan Digital. All Rights Reserved.</p>
    </footer>

</body>
</html>
