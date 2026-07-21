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

        {{-- Nav items --}}
        <nav class="flex-1 space-y-1 overflow-y-auto pt-5 pb-5">

@if($role === 'Superadmin')
            {{-- ═══ Superadmin ═══ --}}
            <p class="px-7 pb-2 pt-5 text-xs font-extrabold uppercase tracking-[0.14em] text-slate-400">Manajemen Sistem</p>

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700 rounded-none">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700 rounded-none">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700 rounded-none">
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
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 rounded-none
               {{ request()->is('stok-upload*') ? 'bg-blue-50 text-blue-700 border-blue-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' }}">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700 rounded-none">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </span>
                    <span class="truncate">OCR Kuitansi & Pajak</span>
                </span>
            </a>

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700 rounded-none">
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
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 rounded-none
               {{ request()->is('master-barang*') ? 'bg-blue-50 text-blue-700 border-blue-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' }}">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700 rounded-none">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700 rounded-none">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700 rounded-none">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-blue-50/70 hover:text-blue-700 rounded-none">
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
            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-indigo-50/70 hover:text-indigo-700 rounded-none">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </span>
                    <span class="truncate">Daftar Tindakan</span>
                </span>
            </a>

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-indigo-50/70 hover:text-indigo-700 rounded-none">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16.5 9.4 7.55 4.24a1 1 0 0 0-1.1 0L3.5 6.1a1 1 0 0 0-.5.87v9.97a1 1 0 0 0 .5.86l3 1.87a1 1 0 0 0 1.1 0l4.9-3.08A1 1 0 0 0 13 15.5V8.4a1 1 0 0 0-.5-.86L9.5 5.5"/>
                            <path d="m19 7-4.5-2.82A1 1 0 0 0 13.5 5v12a1 1 0 0 0 .5.86L18.5 21"/>
                            <path d="M16.5 9.4V16"/>
                        </svg>
                    </span>
                    <span class="truncate">Pengecekan & Pemenuhan</span>
                </span>
            </a>

            <a href="{{ route('stok-upload.index') }}"
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 rounded-none
               {{ request()->is('stok-upload*') ? 'bg-indigo-50 text-indigo-700 border-indigo-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-indigo-50/70 hover:text-indigo-700' }}">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-indigo-50/70 hover:text-indigo-700 rounded-none">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </span>
                    <span class="truncate">OCR Kuitansi & Pajak</span>
                </span>
            </a>

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-indigo-50/70 hover:text-indigo-700 rounded-none">
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
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 rounded-none
               {{ request()->is('master-barang*') ? 'bg-indigo-50 text-indigo-700 border-indigo-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-indigo-50/70 hover:text-indigo-700' }}">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-indigo-50/70 hover:text-indigo-700 rounded-none">
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
            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700 rounded-none">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700 rounded-none">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700 rounded-none">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700 rounded-none">
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

            <a href="/" class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700 rounded-none">
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
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 rounded-none
               {{ request()->is('stok-upload*') ? 'bg-amber-50 text-amber-700 border-amber-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700' }}">
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
               class="group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 rounded-none
               {{ request()->is('master-barang*') ? 'bg-amber-50 text-amber-700 border-amber-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700' }}">
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
    </aside>

    {{-- ═══════════════════════════════════════════════════════════
         MAIN CONTENT AREA
    ═══════════════════════════════════════════════════════════ --}}
    <div id="mainContent"
         class="flex min-h-[calc(100vh-4rem)] flex-1 flex-col transition-[margin] duration-300 ease-in-out lg:ml-0">

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
