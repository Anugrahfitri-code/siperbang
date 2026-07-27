<?php

/*
 * Master kategori resmi untuk kelompok 1.01.03
 * "ALAT/BAHAN UNTUK KEGIATAN KANTOR".
 *
 * Nama kategori mengikuti dokumen Daftar Sub Sub Kelompok Barang
 * Persediaan. Urutan memakai kode kelompok, bukan urutan alfabet.
 */

return [
    'code_prefix' => '10103',
    'formatted_prefix' => '1.01.03',

    'groups' => [
        '01' => 'ALAT TULIS KANTOR',
        '02' => 'KERTAS DAN COVER',
        '03' => 'BAHAN CETAK',
        '04' => 'BAHAN KOMPUTER',
        '05' => 'PERABOT KANTOR',
        '06' => 'ALAT LISTRIK',
        '07' => 'PERLENGKAPAN DINAS',
        '08' => 'KAPORLAP DAN PERLENGKAPAN SATWA',
        // Ejaan mengikuti dokumen sumber resmi.
        '09' => 'PERLENGKAPAN PENUNJANG KEGAITAN KANTOR',
        '10' => 'ALAT PENUNJANG KEGIATAN KANTOR',
        '11' => 'BAHAN PENUNJANG KEGIATAN KANTOR',
        '12' => 'ALAT/BAHAN PENUNJANG KEGIATAN KEAMANAN',
        '13' => 'BAHAN BAKAR DAN PELUMAS',
        '14' => 'OBAT-OBATAN',
        '15' => 'DOKUMEN LAYANAN KEIMIGRASIAN',
        '16' => 'BLANGKO NIKAH',
        '99' => 'ALAT/BAHAN UNTUK KEGIATAN KANTOR LAINNYA',
    ],

    /*
     * Alias lama yang pernah dipakai proyek. Seeder dan migrasi akan
     * mengarahkannya ke kategori resmi agar dropdown tidak ganda.
     */
    'aliases' => [
        'ATK' => '01',
        'ALAT TULIS KANTOR (ATK)' => '01',
        'ALAT/BAHAN KEBERSIHAN' => '05',
        'ALAT BAHAN KEBERSIHAN' => '05',
        'PERALATAN KOMPUTER / ELEKTRONIK' => '04',
        'PERALATAN KOMPUTER/ELEKTRONIK' => '04',
        'LAIN-LAIN' => '99',
        'LAIN LAIN' => '99',
        // Alias ejaan yang sempat dikoreksi di database lama.
        'PERLENGKAPAN PENUNJANG KEGIATAN KANTOR' => '09',
    ],
];
