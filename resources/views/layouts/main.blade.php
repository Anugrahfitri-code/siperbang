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
                            <svg viewBox="0 0 100 100" class="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="30" y="36" width="10" height="10" rx="1.5" fill="#B90015" />
                                <rect x="42" y="44" width="10" height="10" rx="1.5" fill="#0055A5" />
                                <rect x="44" y="24" width="10" height="10" rx="1.5" fill="#00A1E4" />
                                <rect x="56" y="32" width="10" height="10" rx="1.5" fill="#00A1E4" />
                                <rect x="52" y="42" width="6" height="6" rx="1" fill="#F2B818" />
                                <path d="M20 50 L48 64 L48 90 L20 74 Z" fill="#013A70" />
                                <path d="M48 64 L80 50 L80 74 L48 90 Z" fill="#00A1E4" />
                                <path d="M52 78 L72 68 M72 68 L64 67 M72 68 L71 74" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M20 50 L48 36 L48 64 L20 50 Z" fill="#E5A800" opacity="0.8" />
                                <path d="M48 36 L80 50 L48 64 L48 36 Z" fill="#F2B818" />
                            </svg>
                        </div>
                        <div class="flex-col select-none hidden sm:flex">
                            <div class="text-2xl font-bold tracking-tight leading-none flex items-center">
                                <span class="text-[#0055A5]">S</span>
                                <span class="text-[#B90015]">I</span>
                                <span class="text-[#0055A5]">PERB</span>
                                <span class="text-[#F2B818]">A</span>
                                <span class="text-[#4A4A4A]">NG</span>
                            </div>
                            <span class="text-2xs font-medium tracking-wide mt-1 leading-none uppercase text-[#7A7A7A]">
                                Sistem Informasi Penyediaan Barang
                            </span>
                        </div>
                    </a>

                    <div class="hidden md:block h-8 w-px bg-slate-200"></div>

                    {{-- KomdigiLogo --}}
                    <div class="items-center gap-2 hidden md:flex">
                        <div class="relative w-10 h-10 flex-shrink-0">
                            <svg viewBox="0 0 100 100" class="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M40 30 H60" stroke="#0055A5" stroke-width="4" stroke-linecap="round" />
                                <path d="M40 50 H60" stroke="#00A1E4" stroke-width="4" stroke-linecap="round" />
                                <path d="M30 40 V60" stroke="#013A70" stroke-width="4" stroke-linecap="round" />
                                <path d="M70 30 V50" stroke="#F2B818" stroke-width="4" stroke-linecap="round" />
                                <rect x="24" y="24" width="16" height="16" rx="4" fill="#B90015" />
                                <rect x="56" y="20" width="20" height="20" rx="5" fill="#00A1E4" />
                                <rect x="18" y="48" width="22" height="22" rx="5" fill="#013A70" />
                                <rect x="54" y="44" width="22" height="22" rx="5" fill="#00A1E4" />
                                <rect x="68" y="70" width="14" height="14" rx="3.5" fill="#F2B818" />
                            </svg>
                        </div>
                        <div class="flex-col select-none border-l border-gray-300 pl-2 hidden md:flex">
                            <span class="text-sm font-extrabold text-[#4A4A4A] tracking-wider leading-none">
                                KOMDIGI
                            </span>
                            <span class="text-2xs text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">
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
                <nav class="flex items-center h-10 gap-0">
                    <a href="{{ route('stok-upload.index') }}"
                       class="h-full inline-flex items-center px-4 text-[13px] font-semibold border-b-2 transition-colors
                       {{ request()->is('stok-upload') && !request()->is('stok-upload/*')
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        Upload Excel
                    </a>
                    <a href="{{ route('stok-upload.riwayat') }}"
                       class="h-full inline-flex items-center px-4 text-[13px] font-semibold border-b-2 transition-colors
                       {{ request()->is('stok-upload/riwayat*') || request()->is('stok-upload/*/stepper') || request()->is('stok-upload/sampah')
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        Riwayat Upload
                    </a>
                    <a href="{{ route('master-barang.index') }}"
                       class="h-full inline-flex items-center px-4 text-[13px] font-semibold border-b-2 transition-colors
                       {{ request()->is('master-barang*')
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
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
