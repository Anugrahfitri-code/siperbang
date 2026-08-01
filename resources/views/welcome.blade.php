<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $siteSettings['app_name'] ?? config('app.name', 'SIPERBANG') }}</title>
        <meta name="application-name" content="{{ $siteSettings['app_name'] ?? config('app.name', 'SIPERBANG') }}">
        <meta name="description" content="{{ $siteSettings['app_subtitle'] ?? 'Sistem Informasi Persediaan Barang' }}">
        <link rel="icon" href="{{ $siteSettings['favicon_url'] ?? asset('images/brand/siperbang-symbol.png') }}">
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/main.tsx'])
    </head>
    <body class="antialiased">
        <div id="root"></div>
    </body>
</html>
