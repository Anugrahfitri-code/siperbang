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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-8">
                    <a href="/" class="flex items-center gap-3">
                        <img src="/logo.png" alt="SIPERBANG Logo" class="h-12 w-auto" onerror="this.src='https://tailwindui.com/img/logos/mark.svg?color=indigo&shade=600'">
                        <div class="flex flex-col">
                            <span class="text-xl font-extrabold text-slate-800 tracking-tight leading-none">SIPERBANG</span>
                            <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Sistem Informasi Persediaan Barang</span>
                        </div>
                    </a>
                    
                    <!-- Desktop Nav Links -->
                    <nav class="hidden md:flex items-center gap-1">
                        <a href="/stok-upload" class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->is('stok-upload') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            Upload Excel
                        </a>
                        <a href="/stok-upload/riwayat" class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->is('stok-upload/riwayat*') || request()->is('stok-upload/*/preview') || request()->is('stok-upload/*/verifikasi') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            Riwayat Upload
                        </a>
                        <a href="/master-barang" class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->is('master-barang*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            Master Barang
                        </a>
                    </nav>
                </div>

                <!-- User Info & Back Button -->
                <div class="flex items-center gap-4">
                    <div class="hidden lg:block text-right">
                        <span class="text-xs font-bold text-slate-800 block">
                            {{ auth()->check() ? auth()->user()->name : 'Petugas Persediaan' }}
                        </span>
                        <span class="text-[10px] font-medium text-indigo-600 uppercase tracking-wider block">
                            {{ auth()->check() ? auth()->user()->role : 'Petugas Persediaan' }}
                        </span>
                    </div>
                    
                    <button onclick="window.history.back()" type="button" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border-2 border-slate-300 hover:bg-slate-50 hover:border-slate-400 text-slate-900 transition-all shadow-sm hover:shadow-md cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Nav Bar -->
    <div class="md:hidden bg-white border-b border-slate-200 py-2.5 px-4 flex justify-around gap-2 text-center">
        <a href="/stok-upload" class="flex-1 py-1.5 rounded text-xs font-bold {{ request()->is('stok-upload') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50' }}">
            Upload Stok
        </a>
        <a href="/stok-upload/riwayat" class="flex-1 py-1.5 rounded text-xs font-bold {{ request()->is('stok-upload/riwayat*') || request()->is('stok-upload/*/preview') || request()->is('stok-upload/*/verifikasi') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50' }}">
            Riwayat
        </a>
        <a href="/master-barang" class="flex-1 py-1.5 rounded text-xs font-bold {{ request()->is('master-barang*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50' }}">
            Master Barang
        </a>
    </div>

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
