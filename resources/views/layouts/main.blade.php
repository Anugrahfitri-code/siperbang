<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'SIPERBANG'))</title>
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans">

@php
    $user = auth()->user();
    $role = $user?->role ?? '';
    $roleLabel = match($role) {
        'Superadmin' => 'Superadmin',
        'Petugas Persediaan' => 'Petugas',
        'Ketua Tim Kerja' => 'Ketua Tim',
        default => $role
    };
    $roleFull = match($role) {
        'Superadmin' => 'Superadmin',
        'Petugas Persediaan' => 'Petugas Persediaan',
        'Ketua Tim Kerja' => 'Ketua Tim Kerja',
        default => $role
    };
@endphp

<div class="min-h-screen bg-slate-50 flex flex-col font-sans">
    {{-- ═══════════════════════════════════════════════════════════
         NAVBAR (matches Navbar.tsx)
    ═══════════════════════════════════════════════════════════ --}}
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-xs h-16 shrink-0 flex items-center">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-full">
                {{-- Left: Hamburger + Logos --}}
                <div class="flex items-center gap-4 sm:gap-6">
                    <button
                        id="sidebarToggle"
                        onclick="toggleSidebar()"
                        class="p-2 -ml-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors focus:outline-none"
                        aria-label="Toggle Menu"
                    >
                        {{-- Menu icon (lucide Menu) --}}
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="4" y1="6" x2="20" y2="6"/>
                            <line x1="4" y1="12" x2="20" y2="12"/>
                            <line x1="4" y1="18" x2="20" y2="18"/>
                        </svg>
                    </button>

                    {{-- SiperbangLogo --}}
                    <a href="/" class="flex items-center gap-3">
                        <div class="relative w-12 h-12 flex-shrink-0">
                            <img
                                src="{{ asset('images/siperbang-logo.png') }}"
                                alt="Logo SIPERBANG"
                                class="w-full h-full object-contain select-none pointer-events-none"
                            >
                        </div>
                        <div class="flex-col select-none hidden sm:flex">
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

                    {{-- KomdigiLogo --}}
                    <div class="items-center gap-2 hidden md:flex">
                        <div class="relative w-9 h-9 flex-shrink-0">
                            <img src="{{ asset('images/komdigi-logo.png') }}" alt="Logo KOMDIGI" class="w-full h-full object-contain select-none pointer-events-none">
                        </div>
                        <div class="flex-col select-none hidden md:flex">
                            <span class="text-sm font-extrabold text-[#4A4A4A] tracking-wider leading-none">
                                KOMDIGI
                            </span>
                            <span class="text-[10px] text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">
                                Kementerian Komunikasi dan Digital<br>Republik Indonesia
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Right: User Profile --}}
                <div class="flex items-center gap-4 relative" id="profileContainer">
                    <button
                        onclick="toggleProfile()"
                        id="profileButton"
                        class="flex items-center gap-2 hover:bg-slate-50 p-1.5 pr-2 rounded-lg transition-colors border border-transparent hover:border-slate-200"
                    >
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm">
                            {{-- User icon --}}
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div class="flex-col text-left hidden sm:flex">
                            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider leading-none mb-1">
                                {{ $roleLabel }}
                            </span>
                            <span class="text-xs font-bold text-slate-800 leading-none flex items-center gap-1">
                                {{ $user ? explode(' ', $user->name)[0] : 'Guest' }}
                                {{-- ChevronDown --}}
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </span>
                        </div>
                    </button>

                    {{-- Profile Dropdown --}}
                    <div id="profileDropdown" class="hidden absolute right-0 top-[110%] w-64 bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden z-50">
                        <div class="p-4 border-b border-slate-100 bg-slate-50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                                    {{ $user ? strtoupper(substr($user->name, 0, 1)) : 'G' }}
                                </div>
                                <div>
                                    <p class="text-sm font-extrabold text-slate-800 line-clamp-1">{{ $user?->name ?? 'Guest' }}</p>
                                    <p class="text-xs font-medium text-slate-500 mt-0.5">{{ $roleFull }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-2 bg-white">
                            <form method="POST" action="/api/logout" onsubmit="event.preventDefault(); fetch('/api/logout', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).then(function(r) { if (r.ok) window.location.href = '/'; });">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors text-sm font-bold"
                                >
                                    {{-- LogOut icon --}}
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                        <polyline points="16 17 21 12 16 7"/>
                                        <line x1="21" y1="12" x2="9" y2="12"/>
                                    </svg>
                                    <span>Keluar Akun</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════════
         SIDEBAR (matches Sidebar.tsx)
    ═══════════════════════════════════════════════════════════ --}}

    {{-- Overlay --}}
    <div id="sidebarOverlay"
         class="fixed inset-x-0 bottom-0 top-16 z-30 bg-slate-900/50 backdrop-blur-sm lg:hidden hidden"
         onclick="closeSidebar()">
    </div>

    {{-- Sidebar --}}
    <aside id="sidebar"
           class="fixed bottom-0 left-0 top-16 z-40 flex w-72 flex-col border-r border-slate-200 bg-white shadow-2xl transition-transform duration-300 ease-in-out lg:shadow-none -translate-x-full"
    >
        {{-- Mobile header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 lg:hidden">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800">
                Menu Utama
            </h2>
            <button
                type="button"
                onclick="closeSidebar()"
                aria-label="Tutup sidebar"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
            >
                {{-- X icon --}}
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        @include('components.sidebar')
    </aside>

    {{-- ═══════════════════════════════════════════════════════════
         MAIN CONTENT AREA
    ═══════════════════════════════════════════════════════════ --}}
    <div id="mainContent"
         class="flex min-h-[calc(100vh-4rem)] flex-1 flex-col transition-[margin] duration-300 ease-in-out lg:ml-0">

        {{-- ═══════════════════════════════════════════════════════════
             SUB-NAVIGASI MODUL PERSEDIAAN (Upload Excel | Riwayat Upload | Master Barang)
        ═══════════════════════════════════════════════════════════ --}}
        @if(request()->is('stok-upload*') || request()->is('master-barang*'))
        <div class="border-b border-slate-200 bg-white shrink-0">
            <div class="mx-auto w-full max-w-[1600px] px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center h-10 w-full">
                    <a href="{{ route('stok-upload.index') }}"
                       class="h-full inline-flex gap-2 items-center justify-center flex-1 px-4 text-[13px] font-semibold border-b-2 transition-colors
                       {{ request()->is('stok-upload') && !request()->is('stok-upload/*')
                            ? 'border-blue-600 text-blue-700'
                            : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242" />
                            <path d="M12 12v9" />
                            <path d="m8 16 4-4 4 4" />
                        </svg>
                        Upload Excel
                    </a>
                    <a href="{{ route('stok-upload.riwayat') }}"
                       class="h-full inline-flex gap-2 items-center justify-center flex-1 px-4 text-[13px] font-semibold border-b-2 transition-colors
                       {{ request()->is('stok-upload/riwayat*') || request()->is('stok-upload/*/stepper') || request()->is('stok-upload/sampah')
                            ? 'border-blue-600 text-blue-700'
                            : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                        Riwayat Upload
                    </a>
                    <a href="{{ route('master-barang.index') }}"
                       class="h-full inline-flex gap-2 items-center justify-center flex-1 px-4 text-[13px] font-semibold border-b-2 transition-colors
                       {{ request()->is('master-barang*')
                            ? 'border-blue-600 text-blue-700'
                            : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m7.5 4.27 9 5.15" />
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                            <path d="m3.3 7 8.7 5 8.7-5" />
                            <path d="M12 22V12" />
                        </svg>
                        Master Barang
                    </a>
                </nav>
            </div>
        </div>
        @endif

        <main class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-7 sm:px-6 lg:px-8">

            {{-- Session Alerts --}}
            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-150 rounded-lg text-emerald-800 text-xs font-medium flex items-center gap-2 shadow-xs">
                    <svg class="h-4 w-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-rose-50 border border-rose-150 rounded-lg text-rose-800 text-xs font-medium flex items-center gap-2 shadow-xs">
                    <svg class="h-4 w-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @yield('content')

        </main>

        {{-- Footer --}}
        <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400 mt-auto">
            <p>&copy; 2026 SIPERBANG - Kementerian Komunikasi dan Digital. All Rights Reserved.</p>
        </footer>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     JAVASCRIPT: Sidebar toggle + Profile dropdown
═══════════════════════════════════════════════════════════ --}}
<script>
    // ── Sidebar state ──────────────────────────────────────────
    function getSidebar() { return document.getElementById('sidebar'); }
    function getOverlay() { return document.getElementById('sidebarOverlay'); }
    function getMainContent() { return document.getElementById('mainContent'); }
    function isSidebarOpen() { return getSidebar().classList.contains('translate-x-0'); }
    function isDesktop() { return window.innerWidth >= 1024; }

    function openSidebar() {
        getSidebar().classList.remove('-translate-x-full');
        getSidebar().classList.add('translate-x-0');
        getMainContent().classList.add('lg:ml-72');
        getMainContent().classList.remove('lg:ml-0');
        if (!isDesktop()) {
            getOverlay().classList.remove('hidden');
        }
    }

    function closeSidebar() {
        getSidebar().classList.remove('translate-x-0');
        getSidebar().classList.add('-translate-x-full');
        getMainContent().classList.remove('lg:ml-72');
        getMainContent().classList.add('lg:ml-0');
        getOverlay().classList.add('hidden');
    }

    function toggleSidebar() {
        if (isSidebarOpen()) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    // Restore sidebar open on desktop resize from mobile
    function handleResize() {
        if (isDesktop() && !isSidebarOpen()) {
            // Desktop should start with sidebar open
            openSidebar();
        }
        if (!isDesktop() && isSidebarOpen()) {
            // Switching to mobile: show overlay
            getOverlay().classList.remove('hidden');
        }
    }

    // Init: open sidebar on desktop
    if (isDesktop()) {
        openSidebar();
    }

    window.addEventListener('resize', handleResize);

    // ── Profile dropdown ──────────────────────────────────────
    function toggleProfile() {
        var dropdown = document.getElementById('profileDropdown');
        dropdown.classList.toggle('hidden');
    }

    document.addEventListener('click', function(event) {
        var container = document.getElementById('profileContainer');
        var dropdown = document.getElementById('profileDropdown');
        if (container && !container.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Close sidebar on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && isSidebarOpen()) {
            closeSidebar();
        }
    });
</script>

@stack('scripts')
</body>
</html>
