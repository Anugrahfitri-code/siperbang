<?php

return [
    'disk' => env('SITE_BRANDING_DISK', 'public'),

    'defaults' => [
        'app_name' => 'SIPERBANG',
        'app_subtitle' => 'Sistem Informasi Persediaan Barang',
        'instansi_name' => 'KOMDIGI',
        'instansi_sub' => 'Kementerian Komunikasi dan Digital Republik Indonesia',
        'login_heading' => 'Selamat Datang di Portal SIPERBANG.',
        'login_description' => 'Pusat pengelolaan persediaan barang secara digital, dilengkapi fitur verifikasi nota otomatis menggunakan teknologi OCR, dan pemantauan stok real-time.',
        'footer_copyright' => '© {year} {instansi_name}. Seluruh hak cipta dilindungi.',
        'app_logo_path' => '/images/brand/siperbang-symbol.png',
        'instansi_logo_path' => '/images/brand/komdigi-logo.png',
        'favicon_path' => '/images/brand/siperbang-symbol.png',
        'app_name_colors' => '[]',
        'instansi_name_colors' => '[]',
        'contact_address' => 'Jl. Prof. Abdurrahman Basalamah II No.25, Karampuang, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 9023',
        'contact_phone' => '0851-1729-7705',
        'contact_email' => 'bblsdm.makassar@komdigi.go.id',
    ],

    'text_keys' => [
        'app_name',
        'app_name_colors',
        'app_subtitle',
        'instansi_name',
        'instansi_name_colors',
        'instansi_sub',
        'login_heading',
        'login_description',
        'footer_copyright',
        'contact_address',
        'contact_phone',
        'contact_email',
    ],

    'asset_keys' => [
        'app_logo_path',
        'instansi_logo_path',
        'favicon_path',
    ],

    'tokens' => [
        '{year}',
        '{app_name}',
        '{instansi_name}',
        '{instansi_full_name}',
    ],
];
