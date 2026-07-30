<?php

namespace Database\Seeders\Inventory;

use App\Models\KategoriBarang;
use App\Models\KodePersediaan;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk seluruh kode barang persediaan berdasarkan
 * "Daftar Sub Sub Kelompok Barang Persediaan" – Aplikasi Persediaan
 * Unit Akuntansi Kuasa Pengguna Barang.
 *
 * Kode disimpan tanpa titik, mis. 1.01.01.01.001 → 1010101001.
 * Seeder menggunakan updateOrCreate sehingga aman dijalankan berulang.
 */
class FullPersediaanSeeder extends Seeder
{
    public function run(): void
    {
        /* ----------------------------------------------------------------
         * 1. Buat / ambil semua kategori barang (level sub-kelompok)
         * ---------------------------------------------------------------- */
        $cat = [];

        // ── 1.01.01 BAHAN PAKAI HABIS ────────────────────────────────────
        $cat['010101'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN BANGUNAN DAN KONSTRUKSI'])->id;
        $cat['010102'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN KIMIA'])->id;
        $cat['010103'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN PELEDAK'])->id;
        $cat['010104'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN BAKAR DAN PELUMAS (BAHAN BAKU)'])->id;
        $cat['010105'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN BAKU'])->id;
        $cat['010106'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN KIMIA NUKLIR'])->id;
        $cat['010107'] = KategoriBarang::firstOrCreate(['nama' => 'BARANG DALAM PROSES'])->id;
        $cat['010199'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN LAINNYA'])->id;

        // ── 1.01.02 SUKU CADANG ──────────────────────────────────────────
        $cat['010201'] = KategoriBarang::firstOrCreate(['nama' => 'SUKU CADANG ALAT ANGKUTAN'])->id;
        $cat['010202'] = KategoriBarang::firstOrCreate(['nama' => 'SUKU CADANG ALAT BESAR'])->id;
        $cat['010203'] = KategoriBarang::firstOrCreate(['nama' => 'SUKU CADANG ALAT KEDOKTERAN'])->id;
        $cat['010204'] = KategoriBarang::firstOrCreate(['nama' => 'SUKU CADANG ALAT LABORATORIUM'])->id;
        $cat['010205'] = KategoriBarang::firstOrCreate(['nama' => 'SUKU CADANG ALAT PEMANCAR'])->id;
        $cat['010206'] = KategoriBarang::firstOrCreate(['nama' => 'SUKU CADANG ALAT STUDIO DAN KOMUNIKASI'])->id;
        $cat['010207'] = KategoriBarang::firstOrCreate(['nama' => 'SUKU CADANG ALAT PERTANIAN'])->id;
        $cat['010208'] = KategoriBarang::firstOrCreate(['nama' => 'SUKU CADANG ALAT BENGKEL'])->id;
        $cat['010209'] = KategoriBarang::firstOrCreate(['nama' => 'SUKU CADANG ALAT PERSENJATAAN'])->id;
        $cat['010299'] = KategoriBarang::firstOrCreate(['nama' => 'SUKU CADANG LAINNYA'])->id;

        // ── 1.01.03 ALAT/BAHAN UNTUK KEGIATAN KANTOR ────────────────────
        $cat['010301'] = KategoriBarang::firstOrCreate(['nama' => 'ALAT TULIS KANTOR'])->id;
        $cat['010302'] = KategoriBarang::firstOrCreate(['nama' => 'KERTAS DAN COVER'])->id;
        $cat['010303'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN CETAK'])->id;
        $cat['010304'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN KOMPUTER'])->id;
        $cat['010305'] = KategoriBarang::firstOrCreate(['nama' => 'PERABOT KANTOR'])->id;
        $cat['010306'] = KategoriBarang::firstOrCreate(['nama' => 'ALAT LISTRIK'])->id;
        $cat['010307'] = KategoriBarang::firstOrCreate(['nama' => 'PERLENGKAPAN DINAS'])->id;
        $cat['010308'] = KategoriBarang::firstOrCreate(['nama' => 'KAPORLAP DAN PERLENGKAPAN SATWA'])->id;
        $cat['010309'] = KategoriBarang::firstOrCreate(['nama' => 'PERLENGKAPAN PENUNJANG KEGAITAN KANTOR'])->id;
        $cat['010310'] = KategoriBarang::firstOrCreate(['nama' => 'ALAT PENUNJANG KEGIATAN KANTOR'])->id;
        $cat['010311'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN PENUNJANG KEGIATAN KANTOR'])->id;
        $cat['010312'] = KategoriBarang::firstOrCreate(['nama' => 'ALAT/BAHAN PENUNJANG KEGIATAN KEAMANAN'])->id;
        $cat['010313'] = KategoriBarang::firstOrCreate(['nama' => 'BAHAN BAKAR DAN PELUMAS'])->id;
        $cat['010314'] = KategoriBarang::firstOrCreate(['nama' => 'OBAT-OBATAN'])->id;
        $cat['010315'] = KategoriBarang::firstOrCreate(['nama' => 'DOKUMEN LAYANAN KEIMIGRASIAN'])->id;
        $cat['010316'] = KategoriBarang::firstOrCreate(['nama' => 'BLANGKO NIKAH'])->id;
        $cat['010399'] = KategoriBarang::firstOrCreate(['nama' => 'ALAT/BAHAN UNTUK KEGIATAN KANTOR LAINNYA'])->id;

        // ── 1.01.04 OBAT-OBATAN ──────────────────────────────────────────
        $cat['010401'] = KategoriBarang::firstOrCreate(['nama' => 'OBAT (PERSEDIAAN LAINNYA)'])->id;

        // ── 1.01.05 PERSEDIAAN UNTUK DIJUAL/DISERAHKAN ───────────────────
        $cat['010501'] = KategoriBarang::firstOrCreate(['nama' => 'PERSEDIAAN UNTUK DIJUAL/DISERAHKAN KEPADA MASYARAKAT'])->id;

        // ── 1.01.06 PERSEDIAAN TUJUAN STRATEGIS ──────────────────────────
        $cat['010601'] = KategoriBarang::firstOrCreate(['nama' => 'PERSEDIAAN UNTUK TUJUAN STRATEGIS/BERJAGA-JAGA'])->id;

        // ── 1.01.07 NATURA DAN PAKAN ─────────────────────────────────────
        $cat['010701'] = KategoriBarang::firstOrCreate(['nama' => 'NATURA'])->id;
        $cat['010702'] = KategoriBarang::firstOrCreate(['nama' => 'PAKAN'])->id;
        $cat['010799'] = KategoriBarang::firstOrCreate(['nama' => 'NATURA DAN PAKAN LAINNYA'])->id;

        // ── 1.01.08 PERSEDIAAN PENELITIAN ────────────────────────────────
        $cat['010801'] = KategoriBarang::firstOrCreate(['nama' => 'PERSEDIAAN PENELITIAN BIOLOGI'])->id;
        $cat['010802'] = KategoriBarang::firstOrCreate(['nama' => 'PERSEDIAAN PENELITIAN TEKNOLOGI'])->id;
        $cat['010899'] = KategoriBarang::firstOrCreate(['nama' => 'PERSEDIAAN PENELITIAN LAINNYA'])->id;

        // ── 1.01.09 PERSEDIAAN DALAM PROSES ──────────────────────────────
        $cat['010901'] = KategoriBarang::firstOrCreate(['nama' => 'PERSEDIAAN DALAM PROSES'])->id;
        $cat['010999'] = KategoriBarang::firstOrCreate(['nama' => 'PERSEDIAAN DALAM PROSES LAINNYA'])->id;

        // ── 1.01.10 PERSEDIAAN DARI BELANJA BANTUAN SOSIAL ───────────────
        $cat['011001'] = KategoriBarang::firstOrCreate(['nama' => 'PERSEDIAAN DARI BELANJA BANTUAN SOSIAL'])->id;

        // ── 1.02 BARANG TAK HABIS PAKAI ──────────────────────────────────
        $cat['020101'] = KategoriBarang::firstOrCreate(['nama' => 'KOMPONEN JEMBATAN BAJA'])->id;
        $cat['020102'] = KategoriBarang::firstOrCreate(['nama' => 'KOMPONEN JEMBATAN PRATEKAN'])->id;
        $cat['020103'] = KategoriBarang::firstOrCreate(['nama' => 'KOMPONEN PERALATAN'])->id;
        $cat['020104'] = KategoriBarang::firstOrCreate(['nama' => 'KOMPONEN RAMBU-RAMBU'])->id;
        $cat['020105'] = KategoriBarang::firstOrCreate(['nama' => 'ATTACHMENT'])->id;
        $cat['020199'] = KategoriBarang::firstOrCreate(['nama' => 'KOMPONEN LAINNYA'])->id;

        $cat['020201'] = KategoriBarang::firstOrCreate(['nama' => 'PIPA AIR BESI TUANG (DCI)'])->id;
        $cat['020202'] = KategoriBarang::firstOrCreate(['nama' => 'PIPA ASBES SEMEN (ACP)'])->id;
        $cat['020203'] = KategoriBarang::firstOrCreate(['nama' => 'PIPA BAJA'])->id;
        $cat['020204'] = KategoriBarang::firstOrCreate(['nama' => 'PIPA BETON PRATEKAN'])->id;
        $cat['020205'] = KategoriBarang::firstOrCreate(['nama' => 'PIPA FIBER GLASS'])->id;
        $cat['020206'] = KategoriBarang::firstOrCreate(['nama' => 'PIPA PLASTIK PVC (UPVC)'])->id;
        $cat['020299'] = KategoriBarang::firstOrCreate(['nama' => 'PIPA LAINNYA'])->id;

        $cat['020301'] = KategoriBarang::firstOrCreate(['nama' => 'RAMBU-RAMBU'])->id;

        // ── 1.03 BARANG BEKAS DIPAKAI ─────────────────────────────────────
        $cat['030101'] = KategoriBarang::firstOrCreate(['nama' => 'KOMPONEN BEKAS'])->id;
        $cat['030102'] = KategoriBarang::firstOrCreate(['nama' => 'PIPA BEKAS'])->id;
        $cat['030199'] = KategoriBarang::firstOrCreate(['nama' => 'KOMPONEN BEKAS DAN PIPA BEKAS LAINNYA'])->id;

        /* ----------------------------------------------------------------
         * 2. Daftar seluruh item kode persediaan
         * ---------------------------------------------------------------- */
        $items = [

            // ============================================================
            // 1.01.01.01 – BAHAN BANGUNAN DAN KONSTRUKSI
            // ============================================================
            ['g' => '010101', 'kode' => '1010101001', 'nama' => 'Aspal'],
            ['g' => '010101', 'kode' => '1010101002', 'nama' => 'Semen'],
            ['g' => '010101', 'kode' => '1010101003', 'nama' => 'Kaca'],
            ['g' => '010101', 'kode' => '1010101004', 'nama' => 'Pasir'],
            ['g' => '010101', 'kode' => '1010101005', 'nama' => 'Batu'],
            ['g' => '010101', 'kode' => '1010101006', 'nama' => 'Cat'],
            ['g' => '010101', 'kode' => '1010101007', 'nama' => 'Seng'],
            ['g' => '010101', 'kode' => '1010101008', 'nama' => 'Baja'],
            ['g' => '010101', 'kode' => '1010101009', 'nama' => 'Electro Dalas'],
            ['g' => '010101', 'kode' => '1010101010', 'nama' => 'Patok Beton'],
            ['g' => '010101', 'kode' => '1010101011', 'nama' => 'Tiang Beton'],
            ['g' => '010101', 'kode' => '1010101012', 'nama' => 'Besi Beton'],
            ['g' => '010101', 'kode' => '1010101013', 'nama' => 'Tegel'],
            ['g' => '010101', 'kode' => '1010101014', 'nama' => 'Genteng'],
            ['g' => '010101', 'kode' => '1010101015', 'nama' => 'Bis Beton'],
            ['g' => '010101', 'kode' => '1010101016', 'nama' => 'Plat'],
            ['g' => '010101', 'kode' => '1010101017', 'nama' => 'Steel Sheet Pile'],
            ['g' => '010101', 'kode' => '1010101018', 'nama' => 'Concrete Sheet Pile'],
            ['g' => '010101', 'kode' => '1010101019', 'nama' => 'Kawat Bronjong'],
            ['g' => '010101', 'kode' => '1010101020', 'nama' => 'Karung'],
            ['g' => '010101', 'kode' => '1010101021', 'nama' => 'Minyak Cat/Thinner'],
            ['g' => '010101', 'kode' => '1010101999', 'nama' => 'Bahan Bangunan Dan Konstruksi Lainnya'],

            // ============================================================
            // 1.01.01.02 – BAHAN KIMIA
            // ============================================================
            ['g' => '010102', 'kode' => '1010102001', 'nama' => 'Bahan Kimia Padat'],
            ['g' => '010102', 'kode' => '1010102002', 'nama' => 'Bahan Kimia Cair'],
            ['g' => '010102', 'kode' => '1010102003', 'nama' => 'Bahan Kimia Gas'],
            ['g' => '010102', 'kode' => '1010102005', 'nama' => 'Bahan Kimia Nuklir'],
            ['g' => '010102', 'kode' => '1010102999', 'nama' => 'Bahan Kimia Lainnya'],

            // ============================================================
            // 1.01.01.03 – BAHAN PELEDAK
            // ============================================================
            ['g' => '010103', 'kode' => '1010103001', 'nama' => 'Anfo'],
            ['g' => '010103', 'kode' => '1010103002', 'nama' => 'Detonator'],
            ['g' => '010103', 'kode' => '1010103003', 'nama' => 'Dinamit'],
            ['g' => '010103', 'kode' => '1010103004', 'nama' => 'Gelatine'],
            ['g' => '010103', 'kode' => '1010103005', 'nama' => 'Sumbu Ledak/Api'],
            ['g' => '010103', 'kode' => '1010103006', 'nama' => 'Amunisi'],
            ['g' => '010103', 'kode' => '1010103999', 'nama' => 'Bahan Peledak Lainnya'],

            // ============================================================
            // 1.01.01.04 – BAHAN BAKAR DAN PELUMAS (BAHAN BAKU)
            // ============================================================
            ['g' => '010104', 'kode' => '1010104001', 'nama' => 'Bahan Bakar Minyak (Bahan Baku)'],
            ['g' => '010104', 'kode' => '1010104002', 'nama' => 'Minyak Pelumas (Bahan Baku)'],
            ['g' => '010104', 'kode' => '1010104003', 'nama' => 'Minyak Hydrolis (Bahan Baku)'],
            ['g' => '010104', 'kode' => '1010104004', 'nama' => 'Bahan Bakar Gas (Bahan Baku)'],
            ['g' => '010104', 'kode' => '1010104005', 'nama' => 'Batubara (Bahan Baku)'],
            ['g' => '010104', 'kode' => '1010104999', 'nama' => 'Bahan Bakar Dan Pelumas Lainnya (Bahan Baku)'],

            // ============================================================
            // 1.01.01.05 – BAHAN BAKU
            // ============================================================
            ['g' => '010105', 'kode' => '1010105001', 'nama' => 'Kawat'],
            ['g' => '010105', 'kode' => '1010105002', 'nama' => 'Kayu'],
            ['g' => '010105', 'kode' => '1010105003', 'nama' => 'Logam/Metalorgi'],
            ['g' => '010105', 'kode' => '1010105004', 'nama' => 'Latex'],
            ['g' => '010105', 'kode' => '1010105005', 'nama' => 'Biji Plastik'],
            ['g' => '010105', 'kode' => '1010105006', 'nama' => 'Karet (Bahan Baku)'],
            ['g' => '010105', 'kode' => '1010105999', 'nama' => 'Bahan Baku Lainnya'],

            // ============================================================
            // 1.01.01.06 – BAHAN KIMIA NUKLIR
            // ============================================================
            ['g' => '010106', 'kode' => '1010106001', 'nama' => 'Uranium - 233'],
            ['g' => '010106', 'kode' => '1010106002', 'nama' => 'Uranium - 235'],
            ['g' => '010106', 'kode' => '1010106003', 'nama' => 'Uranium - 238'],
            ['g' => '010106', 'kode' => '1010106004', 'nama' => 'Plutonium (PU)'],
            ['g' => '010106', 'kode' => '1010106005', 'nama' => 'Neptarim (NP)'],
            ['g' => '010106', 'kode' => '1010106006', 'nama' => 'Uranium Dioksida'],
            ['g' => '010106', 'kode' => '1010106007', 'nama' => 'Thorium'],
            ['g' => '010106', 'kode' => '1010106999', 'nama' => 'Bahan Kimia Nuklir Lainnya'],

            // ============================================================
            // 1.01.01.07 – BARANG DALAM PROSES
            // ============================================================
            ['g' => '010107', 'kode' => '1010107001', 'nama' => 'Barang Dalam Proses'],
            ['g' => '010107', 'kode' => '1010107999', 'nama' => 'Barang Dalam Proses Lainnya'],

            // ============================================================
            // 1.01.01.99 – BAHAN LAINNYA
            // ============================================================
            ['g' => '010199', 'kode' => '1010199001', 'nama' => 'Film Dosimeter'],
            ['g' => '010199', 'kode' => '1010199999', 'nama' => 'Bahan Lainnya'],

            // ============================================================
            // 1.01.02.01 – SUKU CADANG ALAT ANGKUTAN
            // ============================================================
            ['g' => '010201', 'kode' => '1010201001', 'nama' => 'Suku Cadang Alat Angkutan Darat Bermotor'],
            ['g' => '010201', 'kode' => '1010201002', 'nama' => 'Suku Cadang Alat Angkutan Darat Tak Bermotor'],
            ['g' => '010201', 'kode' => '1010201003', 'nama' => 'Suku Cadang Alat Angkutan Apung Bermotor'],
            ['g' => '010201', 'kode' => '1010201004', 'nama' => 'Suku Cadang Alat Angkutan Apung Tak Bermotor'],
            ['g' => '010201', 'kode' => '1010201005', 'nama' => 'Suku Cadang Alat Angkutan Udara Bermotor'],
            ['g' => '010201', 'kode' => '1010201999', 'nama' => 'Suku Cadang Alat Angkutan Lainnya'],

            // ============================================================
            // 1.01.02.02 – SUKU CADANG ALAT BESAR
            // ============================================================
            ['g' => '010202', 'kode' => '1010202001', 'nama' => 'Suku Cadang Alat Besar Darat'],
            ['g' => '010202', 'kode' => '1010202002', 'nama' => 'Suku Cadang Alat Besar Apung'],
            ['g' => '010202', 'kode' => '1010202003', 'nama' => 'Suku Cadang Alat Besar Bantu'],
            ['g' => '010202', 'kode' => '1010202999', 'nama' => 'Suku Cadang Alat Besar Lainnya'],

            // ============================================================
            // 1.01.02.03 – SUKU CADANG ALAT KEDOKTERAN
            // ============================================================
            ['g' => '010203', 'kode' => '1010203001', 'nama' => 'Suku Cadang Alat Kedokteran Umum'],
            ['g' => '010203', 'kode' => '1010203002', 'nama' => 'Suku Cadang Alat Kedokteran Gigi'],
            ['g' => '010203', 'kode' => '1010203003', 'nama' => 'Suku Cadang Alat Kedokteran Keluarga Berencana'],
            ['g' => '010203', 'kode' => '1010203004', 'nama' => 'Suku Cadang Alat Kedokteran Bedah'],
            ['g' => '010203', 'kode' => '1010203005', 'nama' => 'Suku Cadang Alat Kedokteran Kebidanan Dan Penyakit Kandungan'],
            ['g' => '010203', 'kode' => '1010203006', 'nama' => 'Suku Cadang Alat Kedokteran THT'],
            ['g' => '010203', 'kode' => '1010203007', 'nama' => 'Suku Cadang Alat Kedokteran Mata'],
            ['g' => '010203', 'kode' => '1010203008', 'nama' => 'Suku Cadang Alat Kedokteran Penyakit Dalam'],
            ['g' => '010203', 'kode' => '1010203009', 'nama' => 'Suku Cadang Alat Kedokteran Alat Kesehatan Anak'],
            ['g' => '010203', 'kode' => '1010203010', 'nama' => 'Suku Cadang Alat Kedokteran Poliklinik Set'],
            ['g' => '010203', 'kode' => '1010203011', 'nama' => 'Suku Cadang Alat Kedokteran Untuk Penderita Cacat Tubuh'],
            ['g' => '010203', 'kode' => '1010203012', 'nama' => 'Suku Cadang Alat Kedokteran Syaraf'],
            ['g' => '010203', 'kode' => '1010203013', 'nama' => 'Suku Cadang Alat Kedokteran Jantung'],
            ['g' => '010203', 'kode' => '1010203014', 'nama' => 'Suku Cadang Alat Kedokteran Nuklir'],
            ['g' => '010203', 'kode' => '1010203015', 'nama' => 'Suku Cadang Alat Kedokteran Radiologi'],
            ['g' => '010203', 'kode' => '1010203016', 'nama' => 'Suku Cadang Alat Kedokteran Kulit Dan Kelamin'],
            ['g' => '010203', 'kode' => '1010203017', 'nama' => 'Suku Cadang Alat Kedokteran Ugd'],
            ['g' => '010203', 'kode' => '1010203018', 'nama' => 'Suku Cadang Alat Kedokteran Hematologi'],
            ['g' => '010203', 'kode' => '1010203019', 'nama' => 'Suku Cadang Alat Kedokteran Hewan'],
            ['g' => '010203', 'kode' => '1010203999', 'nama' => 'Suku Cadang Alat Kedokteran Lainnya'],

            // ============================================================
            // 1.01.02.04 – SUKU CADANG ALAT LABORATORIUM
            // ============================================================
            ['g' => '010204', 'kode' => '1010204001', 'nama' => 'Suku Cadang Alat Laboratorium Kimia Air Teknik Penyehatan'],
            ['g' => '010204', 'kode' => '1010204002', 'nama' => 'Suku Cadang Alat Laboratorium Micro Biologi Penyehatan'],
            ['g' => '010204', 'kode' => '1010204003', 'nama' => 'Suku Cadang Alat Laboratorium Hidro Kimia'],
            ['g' => '010204', 'kode' => '1010204004', 'nama' => 'Suku Cadang Alat Laboratorium Model Hidrolika'],
            ['g' => '010204', 'kode' => '1010204005', 'nama' => 'Suku Cadang Alat Laboratorium Batuan/Geologi'],
            ['g' => '010204', 'kode' => '1010204006', 'nama' => 'Suku Cadang Alat Laboratorium Bahan Bangunan Konstruksi'],
            ['g' => '010204', 'kode' => '1010204007', 'nama' => 'Suku Cadang Alat Laboratorium Aspal, Cat Dan Kimia'],
            ['g' => '010204', 'kode' => '1010204008', 'nama' => 'Suku Cadang Alat Laboratorium Mekanika Tanah Dan Batuan'],
            ['g' => '010204', 'kode' => '1010204009', 'nama' => 'Suku Cadang Alat Laboratorium Cocok Tanam'],
            ['g' => '010204', 'kode' => '1010204010', 'nama' => 'Suku Cadang Alat Laboratorium Logam, Mesin Dan Listrik'],
            ['g' => '010204', 'kode' => '1010204011', 'nama' => 'Suku Cadang Alat Laboratorium Umum'],
            ['g' => '010204', 'kode' => '1010204012', 'nama' => 'Suku Cadang Alat Laboratorium Microbiologi'],
            ['g' => '010204', 'kode' => '1010204013', 'nama' => 'Suku Cadang Alat Laboratorium Kimia'],
            ['g' => '010204', 'kode' => '1010204014', 'nama' => 'Suku Cadang Alat Laboratorium Patologi'],
            ['g' => '010204', 'kode' => '1010204015', 'nama' => 'Suku Cadang Alat Laboratorium Immunologi'],
            ['g' => '010204', 'kode' => '1010204016', 'nama' => 'Suku Cadang Alat Laboratorium Film'],
            ['g' => '010204', 'kode' => '1010204017', 'nama' => 'Suku Cadang Alat Laboratorium Radio Isotop'],
            ['g' => '010204', 'kode' => '1010204018', 'nama' => 'Suku Cadang Alat Laboratorium Makanan'],
            ['g' => '010204', 'kode' => '1010204019', 'nama' => 'Suku Cadang Alat Laboratorium Aero Dinamika'],
            ['g' => '010204', 'kode' => '1010204020', 'nama' => 'Suku Cadang Alat Laboratorium Standarisasi Kaliberasi Dan Instrumen'],
            ['g' => '010204', 'kode' => '1010204021', 'nama' => 'Suku Cadang Alat Laboratorium Farmasi'],
            ['g' => '010204', 'kode' => '1010204022', 'nama' => 'Suku Cadang Alat Laboratorium Pemantauan Kualitas Udara'],
            ['g' => '010204', 'kode' => '1010204023', 'nama' => 'Suku Cadang Alat Laboratorium Fisika'],
            ['g' => '010204', 'kode' => '1010204024', 'nama' => 'Suku Cadang Alat Laboratorium Hidrodinamika'],
            ['g' => '010204', 'kode' => '1010204025', 'nama' => 'Suku Cadang Alat Laboratorium Pengkajian Teknik Pantai'],
            ['g' => '010204', 'kode' => '1010204026', 'nama' => 'Suku Cadang Alat Laboratorium Kematologi'],
            ['g' => '010204', 'kode' => '1010204027', 'nama' => 'Suku Cadang Alat Laboratorium Proses Peleburan'],
            ['g' => '010204', 'kode' => '1010204028', 'nama' => 'Suku Cadang Alat Laboratorium Pasir'],
            ['g' => '010204', 'kode' => '1010204029', 'nama' => 'Suku Cadang Alat Laboratorium Proses Pembuatan Cetakan'],
            ['g' => '010204', 'kode' => '1010204030', 'nama' => 'Suku Cadang Alat Laboratorium Proses Pembuatan Pola'],
            ['g' => '010204', 'kode' => '1010204031', 'nama' => 'Suku Cadang Alat Laboratorium Metalography'],
            ['g' => '010204', 'kode' => '1010204032', 'nama' => 'Suku Cadang Alat Laboratorium Proses Pengelasan'],
            ['g' => '010204', 'kode' => '1010204033', 'nama' => 'Suku Cadang Alat Laboratorium Uji Proses Pengelasan'],
            ['g' => '010204', 'kode' => '1010204034', 'nama' => 'Suku Cadang Alat Laboratorium Proses Pembuatan Logam'],
            ['g' => '010204', 'kode' => '1010204035', 'nama' => 'Suku Cadang Alat Laboratorium Metrologie'],
            ['g' => '010204', 'kode' => '1010204036', 'nama' => 'Suku Cadang Alat Laboratorium Proses Pelapisan Logam'],
            ['g' => '010204', 'kode' => '1010204037', 'nama' => 'Suku Cadang Alat Laboratorium Proses Pengolahan Panas'],
            ['g' => '010204', 'kode' => '1010204038', 'nama' => 'Suku Cadang Alat Laboratorium Proses Teknologi Tekstil'],
            ['g' => '010204', 'kode' => '1010204039', 'nama' => 'Suku Cadang Alat Laboratorium Uji Tekstil'],
            ['g' => '010204', 'kode' => '1010204040', 'nama' => 'Suku Cadang Alat Laboratorium Proses Teknologi Keramik'],
            ['g' => '010204', 'kode' => '1010204041', 'nama' => 'Suku Cadang Alat Laboratorium Proses Teknologi Kulit Karet'],
            ['g' => '010204', 'kode' => '1010204042', 'nama' => 'Suku Cadang Alat Laboratorium Uji Kulit Karet Dan Plastik'],
            ['g' => '010204', 'kode' => '1010204043', 'nama' => 'Suku Cadang Alat Laboratorium Alat Uji Keramik'],
            ['g' => '010204', 'kode' => '1010204044', 'nama' => 'Suku Cadang Alat Laboratorium Proses Teknologi Selulosa'],
            ['g' => '010204', 'kode' => '1010204045', 'nama' => 'Suku Cadang Alat Laboratorium Paska Panen'],
            ['g' => '010204', 'kode' => '1010204046', 'nama' => 'Suku Cadang Alat Laboratorium Pertanian'],
            ['g' => '010204', 'kode' => '1010204047', 'nama' => 'Suku Cadang Alat Laboratorium Kualitas Air'],
            ['g' => '010204', 'kode' => '1010204048', 'nama' => 'Suku Cadang Alat Laboratorium Elektronika Dan Daya'],
            ['g' => '010204', 'kode' => '1010204049', 'nama' => 'Suku Cadang Alat Laboratorium Energi Surya'],
            ['g' => '010204', 'kode' => '1010204050', 'nama' => 'Suku Cadang Alat Laboratorium Konversi Batubara Dan Bioma'],
            ['g' => '010204', 'kode' => '1010204051', 'nama' => 'Suku Cadang Alat Laboratorium Oceanografi'],
            ['g' => '010204', 'kode' => '1010204052', 'nama' => 'Suku Cadang Alat Laboratorium Perairan'],
            ['g' => '010204', 'kode' => '1010204053', 'nama' => 'Suku Cadang Alat Laboratorium Biologi'],
            ['g' => '010204', 'kode' => '1010204054', 'nama' => 'Suku Cadang Alat Laboratorium Geofisika'],
            ['g' => '010204', 'kode' => '1010204055', 'nama' => 'Suku Cadang Alat Laboratorium Tambang'],
            ['g' => '010204', 'kode' => '1010204056', 'nama' => 'Suku Cadang Alat Laboratorium Tambang Proses/Teknik Kimia'],
            ['g' => '010204', 'kode' => '1010204057', 'nama' => 'Suku Cadang Alat Laboratorium Proses Industri'],
            ['g' => '010204', 'kode' => '1010204058', 'nama' => 'Suku Cadang Alat Laboratorium Kesehatan Kerja'],
            ['g' => '010204', 'kode' => '1010204059', 'nama' => 'Suku Cadang Alat Laboratorium Kearsipan'],
            ['g' => '010204', 'kode' => '1010204060', 'nama' => 'Suku Cadang Alat Laboratorium Perikanan dan Kelautan'],
            ['g' => '010204', 'kode' => '1010204999', 'nama' => 'Suku Cadang Alat Laboratorium Lainnya'],

            // ============================================================
            // 1.01.02.05 – SUKU CADANG ALAT PEMANCAR
            // ============================================================
            ['g' => '010205', 'kode' => '1010205001', 'nama' => 'Suku Cadang Alat Pemancar MF/MW'],
            ['g' => '010205', 'kode' => '1010205002', 'nama' => 'Suku Cadang Alat Pemancar HF/SW'],
            ['g' => '010205', 'kode' => '1010205003', 'nama' => 'Suku Cadang Alat Pemancar FHF/MF'],
            ['g' => '010205', 'kode' => '1010205004', 'nama' => 'Suku Cadang Alat Pemancar UHF'],
            ['g' => '010205', 'kode' => '1010205005', 'nama' => 'Suku Cadang Alat Pemancar SHF'],
            ['g' => '010205', 'kode' => '1010205999', 'nama' => 'Suku Cadang Alat Pemancar Lainnya'],

            // ============================================================
            // 1.01.02.06 – SUKU CADANG ALAT STUDIO DAN KOMUNIKASI
            // ============================================================
            ['g' => '010206', 'kode' => '1010206001', 'nama' => 'Suku Cadang Alat Studio'],
            ['g' => '010206', 'kode' => '1010206002', 'nama' => 'Suku Cadang Alat Komunikasi'],
            ['g' => '010206', 'kode' => '1010206999', 'nama' => 'Suku Cadang Alat Studio Dan Komunikasi Lainnya'],

            // ============================================================
            // 1.01.02.07 – SUKU CADANG ALAT PERTANIAN
            // ============================================================
            ['g' => '010207', 'kode' => '1010207001', 'nama' => 'Suku Cadang Alat Pengolahan Ternak Dan Tanaman'],
            ['g' => '010207', 'kode' => '1010207002', 'nama' => 'Suku Cadang Alat Pemeliharaan Tanaman/Ikan/Ternak'],
            ['g' => '010207', 'kode' => '1010207003', 'nama' => 'Suku Cadang Alat Panen'],
            ['g' => '010207', 'kode' => '1010207004', 'nama' => 'Suku Cadang Alat Penyimpanan Hasil Percobaan Pertanian'],
            ['g' => '010207', 'kode' => '1010207005', 'nama' => 'Suku Cadang Alat Laboratorium Pertanian'],
            ['g' => '010207', 'kode' => '1010207006', 'nama' => 'Suku Cadang Alat Prossesing'],
            ['g' => '010207', 'kode' => '1010207007', 'nama' => 'Suku Cadang Alat Paska Panen'],
            ['g' => '010207', 'kode' => '1010207008', 'nama' => 'Suku Cadang Alat Produksi'],
            ['g' => '010207', 'kode' => '1010207999', 'nama' => 'Suku Cadang Alat Pertanian Lainnya'],

            // ============================================================
            // 1.01.02.08 – SUKU CADANG ALAT BENGKEL
            // ============================================================
            ['g' => '010208', 'kode' => '1010208001', 'nama' => 'Suku Cadang Alat Bengkel Bermesin'],
            ['g' => '010208', 'kode' => '1010208002', 'nama' => 'Suku Cadang Alat Bengkel Tidak Bermesin'],
            ['g' => '010208', 'kode' => '1010208999', 'nama' => 'Suku Cadang Alat Bengkel Lainnya'],

            // ============================================================
            // 1.01.02.09 – SUKU CADANG ALAT PERSENJATAAN
            // ============================================================
            ['g' => '010209', 'kode' => '1010209001', 'nama' => 'Suku Cadang Pistol'],
            ['g' => '010209', 'kode' => '1010209002', 'nama' => 'Suku Cadang Senapan Mesin'],
            ['g' => '010209', 'kode' => '1010209003', 'nama' => 'Suku Cadang Senapan Otomatis (SO)'],
            ['g' => '010209', 'kode' => '1010209004', 'nama' => 'Suku Cadang Meriam'],
            ['g' => '010209', 'kode' => '1010209005', 'nama' => 'Suku Cadang Rudal'],
            ['g' => '010209', 'kode' => '1010209006', 'nama' => 'Suku Cadang Jihandak'],
            ['g' => '010209', 'kode' => '1010209007', 'nama' => 'Suku Cadang Ranjau'],
            ['g' => '010209', 'kode' => '1010209008', 'nama' => 'Suku Cadang Penyamaran'],
            ['g' => '010209', 'kode' => '1010209009', 'nama' => 'Suku Cadang Alat Pembuat Senjata'],
            ['g' => '010209', 'kode' => '1010209010', 'nama' => 'Suku Cadang Alat Pembuat Amunisi'],
            ['g' => '010209', 'kode' => '1010209011', 'nama' => 'Suku Cadang Pendukung'],
            ['g' => '010209', 'kode' => '1010209999', 'nama' => 'Suku Cadang Senjata Lainnya'],

            // ============================================================
            // 1.01.02.99 – SUKU CADANG LAINNYA
            // ============================================================
            ['g' => '010299', 'kode' => '1010299999', 'nama' => 'Suku Cadang Lainnya'],

            // ============================================================
            // 1.01.03.01 – ALAT TULIS KANTOR
            // ============================================================
            ['g' => '010301', 'kode' => '1010301001', 'nama' => 'Alat Tulis'],
            ['g' => '010301', 'kode' => '1010301002', 'nama' => 'Tinta Tulis, Tinta Stempel'],
            ['g' => '010301', 'kode' => '1010301003', 'nama' => 'Penjepit Kertas'],
            ['g' => '010301', 'kode' => '1010301004', 'nama' => 'Penghapus/Korektor'],
            ['g' => '010301', 'kode' => '1010301005', 'nama' => 'Buku Tulis'],
            ['g' => '010301', 'kode' => '1010301006', 'nama' => 'Ordner Dan Map'],
            ['g' => '010301', 'kode' => '1010301007', 'nama' => 'Penggaris'],
            ['g' => '010301', 'kode' => '1010301008', 'nama' => 'Cutter (Alat Tulis Kantor)'],
            ['g' => '010301', 'kode' => '1010301009', 'nama' => 'Pita Mesin Ketik'],
            ['g' => '010301', 'kode' => '1010301010', 'nama' => 'Alat Perekat'],
            ['g' => '010301', 'kode' => '1010301011', 'nama' => 'Stadler HD'],
            ['g' => '010301', 'kode' => '1010301012', 'nama' => 'Staples'],
            ['g' => '010301', 'kode' => '1010301013', 'nama' => 'Isi Staples'],
            ['g' => '010301', 'kode' => '1010301014', 'nama' => 'Barang Cetakan'],
            ['g' => '010301', 'kode' => '1010301015', 'nama' => 'Seminar Kit'],
            ['g' => '010301', 'kode' => '1010301999', 'nama' => 'Alat Tulis Kantor Lainnya'],

            // ============================================================
            // 1.01.03.02 – KERTAS DAN COVER
            // ============================================================
            ['g' => '010302', 'kode' => '1010302001', 'nama' => 'Kertas HVS'],
            ['g' => '010302', 'kode' => '1010302002', 'nama' => 'Berbagai Kertas'],
            ['g' => '010302', 'kode' => '1010302003', 'nama' => 'Kertas Cover'],
            ['g' => '010302', 'kode' => '1010302004', 'nama' => 'Amplop'],
            ['g' => '010302', 'kode' => '1010302005', 'nama' => 'Kop Surat'],
            ['g' => '010302', 'kode' => '1010302999', 'nama' => 'Kertas Dan Cover Lainnya'],

            // ============================================================
            // 1.01.03.03 – BAHAN CETAK
            // ============================================================
            ['g' => '010303', 'kode' => '1010303001', 'nama' => 'Transparant Sheet'],
            ['g' => '010303', 'kode' => '1010303002', 'nama' => 'Tinta Cetak'],
            ['g' => '010303', 'kode' => '1010303003', 'nama' => 'Plat Cetak'],
            ['g' => '010303', 'kode' => '1010303004', 'nama' => 'Stensil Sheet'],
            ['g' => '010303', 'kode' => '1010303005', 'nama' => 'Chenical/Bahan Kimia Cetak'],
            ['g' => '010303', 'kode' => '1010303006', 'nama' => 'Film Cetak'],
            ['g' => '010303', 'kode' => '1010303999', 'nama' => 'Bahan Cetak Lainnya'],

            // ============================================================
            // 1.01.03.04 – BAHAN KOMPUTER
            // ============================================================
            ['g' => '010304', 'kode' => '1010304001', 'nama' => 'Continuous Form'],
            ['g' => '010304', 'kode' => '1010304002', 'nama' => 'Computer File/Tempat Disket'],
            ['g' => '010304', 'kode' => '1010304003', 'nama' => 'Pita Printer'],
            ['g' => '010304', 'kode' => '1010304004', 'nama' => 'Tinta/Toner Printer'],
            ['g' => '010304', 'kode' => '1010304005', 'nama' => 'Disket'],
            ['g' => '010304', 'kode' => '1010304006', 'nama' => 'USB/Flash Disk'],
            ['g' => '010304', 'kode' => '1010304007', 'nama' => 'Kartu Memori'],
            ['g' => '010304', 'kode' => '1010304008', 'nama' => 'CD/DVD Drive'],
            ['g' => '010304', 'kode' => '1010304009', 'nama' => 'Harddisk Internal'],
            ['g' => '010304', 'kode' => '1010304010', 'nama' => 'Mouse'],
            ['g' => '010304', 'kode' => '1010304011', 'nama' => 'CD/DVD'],
            ['g' => '010304', 'kode' => '1010304999', 'nama' => 'Bahan Komputer Lainnya'],

            // ============================================================
            // 1.01.03.05 – PERABOT KANTOR
            // ============================================================
            ['g' => '010305', 'kode' => '1010305001', 'nama' => 'Sapu Dan Sikat'],
            ['g' => '010305', 'kode' => '1010305002', 'nama' => 'Alat-Alat Pel Dan Lap'],
            ['g' => '010305', 'kode' => '1010305003', 'nama' => 'Ember, Slang, Dan Tempat Air Lainnya'],
            ['g' => '010305', 'kode' => '1010305004', 'nama' => 'Keset Dan Tempat Sampah'],
            ['g' => '010305', 'kode' => '1010305005', 'nama' => 'Kunci, Kran Dan Semprotan'],
            ['g' => '010305', 'kode' => '1010305006', 'nama' => 'Alat Pengikat'],
            ['g' => '010305', 'kode' => '1010305007', 'nama' => 'Peralatan Ledeng'],
            ['g' => '010305', 'kode' => '1010305008', 'nama' => 'Bahan Kimia Untuk Pembersih'],
            ['g' => '010305', 'kode' => '1010305009', 'nama' => 'Alat Untuk Makan Dan Minum'],
            ['g' => '010305', 'kode' => '1010305010', 'nama' => 'Kaos Lampu Petromak'],
            ['g' => '010305', 'kode' => '1010305011', 'nama' => 'Kaca Lampu Petromak'],
            ['g' => '010305', 'kode' => '1010305012', 'nama' => 'Pengharum Ruangan'],
            ['g' => '010305', 'kode' => '1010305013', 'nama' => 'Kuas'],
            ['g' => '010305', 'kode' => '1010305014', 'nama' => 'Segel/Tanda Pengaman'],
            ['g' => '010305', 'kode' => '1010305999', 'nama' => 'Perabot Kantor Lainnya'],

            // ============================================================
            // 1.01.03.06 – ALAT LISTRIK
            // ============================================================
            ['g' => '010306', 'kode' => '1010306001', 'nama' => 'Kabel Listrik'],
            ['g' => '010306', 'kode' => '1010306002', 'nama' => 'Lampu Listrik'],
            ['g' => '010306', 'kode' => '1010306003', 'nama' => 'Stop Kontak'],
            ['g' => '010306', 'kode' => '1010306004', 'nama' => 'Saklar'],
            ['g' => '010306', 'kode' => '1010306005', 'nama' => 'Stacker'],
            ['g' => '010306', 'kode' => '1010306006', 'nama' => 'Balast'],
            ['g' => '010306', 'kode' => '1010306007', 'nama' => 'Starter'],
            ['g' => '010306', 'kode' => '1010306008', 'nama' => 'Vitting'],
            ['g' => '010306', 'kode' => '1010306009', 'nama' => 'Accu'],
            ['g' => '010306', 'kode' => '1010306010', 'nama' => 'Batu Baterai'],
            ['g' => '010306', 'kode' => '1010306011', 'nama' => 'Stavol'],
            ['g' => '010306', 'kode' => '1010306999', 'nama' => 'Alat Listrik Lainnya'],

            // ============================================================
            // 1.01.03.07 – PERLENGKAPAN DINAS
            // ============================================================
            ['g' => '010307', 'kode' => '1010307001', 'nama' => 'Bahan Baku Pakaian'],
            ['g' => '010307', 'kode' => '1010307002', 'nama' => 'Penutup Kepala'],
            ['g' => '010307', 'kode' => '1010307003', 'nama' => 'Penutup Badan'],
            ['g' => '010307', 'kode' => '1010307004', 'nama' => 'Penutup Tangan'],
            ['g' => '010307', 'kode' => '1010307005', 'nama' => 'Penutup Kaki'],
            ['g' => '010307', 'kode' => '1010307006', 'nama' => 'Atribut'],
            ['g' => '010307', 'kode' => '1010307007', 'nama' => 'Perlengkapan Lapangan'],
            ['g' => '010307', 'kode' => '1010307999', 'nama' => 'Perlengkapan Dinas Lainnya'],

            // ============================================================
            // 1.01.03.08 – KAPORLAP DAN PERLENGKAPAN SATWA
            // ============================================================
            ['g' => '010308', 'kode' => '1010308001', 'nama' => 'Kaporlap dan Perlengkapan Satwa Anjing'],
            ['g' => '010308', 'kode' => '1010308002', 'nama' => 'Kaporlap dan Perlengkapan Satwa Kuda'],
            ['g' => '010308', 'kode' => '1010308999', 'nama' => 'Kaporlap Dan Perlengkapan Satwa Lainnya'],

            // ============================================================
            // 1.01.03.09 – PERLENGKAPAN PENUNJANG KEGIATAN KANTOR
            // ============================================================
            ['g' => '010309', 'kode' => '1010309001', 'nama' => 'Meterai'],
            ['g' => '010309', 'kode' => '1010309002', 'nama' => 'Prangko'],
            ['g' => '010309', 'kode' => '1010309003', 'nama' => 'Stempel'],
            ['g' => '010309', 'kode' => '1010309999', 'nama' => 'Perlengkapan Penunjang Kegiatan Kantor Lainnya'],

            // ============================================================
            // 1.01.03.10 – ALAT PENUNJANG KEGIATAN KANTOR
            // ============================================================
            ['g' => '010310', 'kode' => '1010310001', 'nama' => 'Persediaan Berupa Alat Penunjang Kedokteran'],
            ['g' => '010310', 'kode' => '1010310002', 'nama' => 'Persediaan Berupa Alat Penunjang Laboratorium'],
            ['g' => '010310', 'kode' => '1010310003', 'nama' => 'Persediaan Berupa Alat Penunjang Studio Dan Komunikasi'],
            ['g' => '010310', 'kode' => '1010310999', 'nama' => 'Alat Penunjang Kegiatan Kantor Lainnya'],

            // ============================================================
            // 1.01.03.11 – BAHAN PENUNJANG KEGIATAN KANTOR
            // ============================================================
            ['g' => '010311', 'kode' => '1010311001', 'nama' => 'Persediaan Berupa Bahan Penunjang Kedokteran'],
            ['g' => '010311', 'kode' => '1010311002', 'nama' => 'Persediaan Berupa Bahan Penunjang Laboratorium'],
            ['g' => '010311', 'kode' => '1010311003', 'nama' => 'Persediaan Berupa Bahan Penunjang Pertanian'],
            ['g' => '010311', 'kode' => '1010311999', 'nama' => 'Bahan Penunjang Kegiatan Kantor Lainnya'],

            // ============================================================
            // 1.01.03.12 – ALAT/BAHAN PENUNJANG KEGIATAN KEAMANAN
            // ============================================================
            ['g' => '010312', 'kode' => '1010312001', 'nama' => 'Persediaan Berupa Alat/Bahan Daktiloskopi'],
            ['g' => '010312', 'kode' => '1010312002', 'nama' => 'Persediaan Berupa Alat/Bahan Lalu Lintas'],
            ['g' => '010312', 'kode' => '1010312999', 'nama' => 'Alat/Bahan Penunjang Kegiatan Keamanan Lainnya'],

            // ============================================================
            // 1.01.03.13 – BAHAN BAKAR DAN PELUMAS (BARANG KONSUMSI)
            // ============================================================
            ['g' => '010313', 'kode' => '1010313001', 'nama' => 'Bahan Bakar Minyak (Barang Konsumsi)'],
            ['g' => '010313', 'kode' => '1010313002', 'nama' => 'Minyak Pelumas (Barang Konsumsi)'],
            ['g' => '010313', 'kode' => '1010313999', 'nama' => 'Bahan Bakar Dan Pelumas Lainnya (Barang Konsumsi)'],

            // ============================================================
            // 1.01.03.14 – OBAT-OBATAN (BARANG KONSUMSI)
            // ============================================================
            ['g' => '010314', 'kode' => '1010314001', 'nama' => 'Obat Cair (Barang Konsumsi)'],
            ['g' => '010314', 'kode' => '1010314002', 'nama' => 'Obat Padat (Barang Konsumsi)'],
            ['g' => '010314', 'kode' => '1010314003', 'nama' => 'Obat Gas (Barang Konsumsi)'],
            ['g' => '010314', 'kode' => '1010314004', 'nama' => 'Obat Serbuk/Tepung (Barang Konsumsi)'],
            ['g' => '010314', 'kode' => '1010314005', 'nama' => 'Obat Gel/Salep (Barang Konsumsi)'],
            ['g' => '010314', 'kode' => '1010314999', 'nama' => 'Obat Lainnya (Barang Konsumsi)'],

            // ============================================================
            // 1.01.03.15 – DOKUMEN LAYANAN KEIMIGRASIAN
            // ============================================================
            ['g' => '010315', 'kode' => '1010315001', 'nama' => 'Dokumen Keimigrasian'],
            ['g' => '010315', 'kode' => '1010315999', 'nama' => 'Dokumen Layanan Keimigrasian Lainnya'],

            // ============================================================
            // 1.01.03.16 – BLANGKO NIKAH
            // ============================================================
            ['g' => '010316', 'kode' => '1010316001', 'nama' => 'Akte Nikah'],
            ['g' => '010316', 'kode' => '1010316002', 'nama' => 'Buku Nikah'],
            ['g' => '010316', 'kode' => '1010316003', 'nama' => 'Daftar Pemeriksaan Nikah'],
            ['g' => '010316', 'kode' => '1010316004', 'nama' => 'Duplikat Nikah'],
            ['g' => '010316', 'kode' => '1010316005', 'nama' => 'Kartu Nikah'],

            // ============================================================
            // 1.01.03.99 – ALAT/BAHAN UNTUK KEGIATAN KANTOR LAINNYA
            // ============================================================
            ['g' => '010399', 'kode' => '1010399999', 'nama' => 'Alat/Bahan Untuk Kegiatan Kantor Lainnya'],

            // ============================================================
            // 1.01.04.01 – OBAT (PERSEDIAAN LAINNYA)
            // ============================================================
            ['g' => '010401', 'kode' => '1010401001', 'nama' => 'Obat Cair (Persediaan Lainnya)'],
            ['g' => '010401', 'kode' => '1010401002', 'nama' => 'Obat Padat (Persediaan Lainnya)'],
            ['g' => '010401', 'kode' => '1010401003', 'nama' => 'Obat Gas (Persediaan Lainnya)'],
            ['g' => '010401', 'kode' => '1010401004', 'nama' => 'Obat Serbuk/Tepung (Persediaan Lainnya)'],
            ['g' => '010401', 'kode' => '1010401005', 'nama' => 'Obat Gel/Salep (Persediaan Lainnya)'],
            ['g' => '010401', 'kode' => '1010401006', 'nama' => 'Alat/Obat Kontrasepsi Keluarga Berencana (Persediaan Lainnya)'],
            ['g' => '010401', 'kode' => '1010401007', 'nama' => 'Non Alat/Obat Kontrasepsi Keluarga Berencana (Persediaan Lainnya)'],
            ['g' => '010401', 'kode' => '1010401999', 'nama' => 'Obat Lainnya (Persediaan Lainnya)'],

            // ============================================================
            // 1.01.05.01 – PERSEDIAAN UNTUK DIJUAL/DISERAHKAN KEPADA MASYARAKAT
            // ============================================================
            ['g' => '010501', 'kode' => '1010501001', 'nama' => 'Pita Cukai, Materai, Leges'],
            ['g' => '010501', 'kode' => '1010501002', 'nama' => 'Tanah dan Bangunan'],
            ['g' => '010501', 'kode' => '1010501003', 'nama' => 'Hewan dan Tanaman'],
            ['g' => '010501', 'kode' => '1010501004', 'nama' => 'Peralatan dan Mesin'],
            ['g' => '010501', 'kode' => '1010501005', 'nama' => 'Jalan, Irigasi, dan Jaringan'],
            ['g' => '010501', 'kode' => '1010501006', 'nama' => 'Aset Tetap Lainnya'],
            ['g' => '010501', 'kode' => '1010501007', 'nama' => 'Aset Lain-lain'],
            ['g' => '010501', 'kode' => '1010501008', 'nama' => 'Barang Persediaan'],

            // ============================================================
            // 1.01.06.01 – PERSEDIAAN UNTUK TUJUAN STRATEGIS/BERJAGA-JAGA
            // ============================================================
            ['g' => '010601', 'kode' => '1010601001', 'nama' => 'Cadangan Energi'],
            ['g' => '010601', 'kode' => '1010601002', 'nama' => 'Cadangan Pangan'],
            ['g' => '010601', 'kode' => '1010601999', 'nama' => 'Persediaan Untuk Tujuan Strategis/Berjaga-jaga Lainnya'],

            // ============================================================
            // 1.01.07.01 – NATURA
            // ============================================================
            ['g' => '010701', 'kode' => '1010701001', 'nama' => 'Makanan/Sembako'],
            ['g' => '010701', 'kode' => '1010701002', 'nama' => 'Minuman'],
            ['g' => '010701', 'kode' => '1010701999', 'nama' => 'Natura Lainnya'],

            // ============================================================
            // 1.01.07.02 – PAKAN
            // ============================================================
            ['g' => '010702', 'kode' => '1010702001', 'nama' => 'Pakan Hewan'],
            ['g' => '010702', 'kode' => '1010702002', 'nama' => 'Pakan Ikan'],
            ['g' => '010702', 'kode' => '1010702999', 'nama' => 'Pakan Lainnya'],

            // ============================================================
            // 1.01.07.99 – NATURA DAN PAKAN LAINNYA
            // ============================================================
            ['g' => '010799', 'kode' => '1010799999', 'nama' => 'Natura Dan Pakan Lainnya'],

            // ============================================================
            // 1.01.08.01 – PERSEDIAAN PENELITIAN BIOLOGI
            // ============================================================
            ['g' => '010801', 'kode' => '1010801001', 'nama' => 'Hewan/Ternak'],
            ['g' => '010801', 'kode' => '1010801002', 'nama' => 'Biota Laut/Ikan'],
            ['g' => '010801', 'kode' => '1010801003', 'nama' => 'Tanaman'],
            ['g' => '010801', 'kode' => '1010801999', 'nama' => 'Persediaan Penelitian Biologi Lainnya'],

            // ============================================================
            // 1.01.08.02 – PERSEDIAAN PENELITIAN TEKNOLOGI
            // ============================================================
            ['g' => '010802', 'kode' => '1010802001', 'nama' => 'Antariksa'],
            ['g' => '010802', 'kode' => '1010802002', 'nama' => 'Pertanian'],
            ['g' => '010802', 'kode' => '1010802003', 'nama' => 'Perikanan'],
            ['g' => '010802', 'kode' => '1010802004', 'nama' => 'Peternakan'],
            ['g' => '010802', 'kode' => '1010802005', 'nama' => 'Perkebunan dan Kehutanan'],
            ['g' => '010802', 'kode' => '1010802006', 'nama' => 'Militer'],
            ['g' => '010802', 'kode' => '1010802999', 'nama' => 'Persediaan Penelitian Teknologi Lainnya'],

            // ============================================================
            // 1.01.08.99 – PERSEDIAAN PENELITIAN LAINNYA
            // ============================================================
            ['g' => '010899', 'kode' => '1010899999', 'nama' => 'Persediaan Penelitian Lainnya'],

            // ============================================================
            // 1.01.09.01 – PERSEDIAAN DALAM PROSES
            // ============================================================
            ['g' => '010901', 'kode' => '1010901001', 'nama' => 'Tanah dan Bangunan Dalam Proses'],
            ['g' => '010901', 'kode' => '1010901002', 'nama' => 'Peralatan dan Mesin Dalam Proses'],
            ['g' => '010901', 'kode' => '1010901003', 'nama' => 'Jalan, Irigasi, dan Jaringan Dalam Proses'],
            ['g' => '010901', 'kode' => '1010901004', 'nama' => 'Aset Tetap Lainnya Dalam Proses'],
            ['g' => '010901', 'kode' => '1010901005', 'nama' => 'Aset Lain-lain Dalam Proses'],
            ['g' => '010901', 'kode' => '1010901006', 'nama' => 'Barang Persediaan Dalam Proses'],

            // ============================================================
            // 1.01.09.99 – PERSEDIAAN DALAM PROSES LAINNYA
            // ============================================================
            ['g' => '010999', 'kode' => '1010999999', 'nama' => 'Persediaan Dalam Proses Lainnya'],

            // ============================================================
            // 1.01.10.01 – PERSEDIAAN DARI BELANJA BANTUAN SOSIAL
            // ============================================================
            ['g' => '011001', 'kode' => '1011001001', 'nama' => 'Tanah dan Bangunan'],
            ['g' => '011001', 'kode' => '1011001002', 'nama' => 'Hewan dan Tanaman'],
            ['g' => '011001', 'kode' => '1011001003', 'nama' => 'Peralatan dan Mesin'],
            ['g' => '011001', 'kode' => '1011001004', 'nama' => 'Jalan, Irigasi dan Jaringan'],
            ['g' => '011001', 'kode' => '1011001005', 'nama' => 'Aset Tetap Lainnya'],
            ['g' => '011001', 'kode' => '1011001006', 'nama' => 'Aset Lain-Lain'],
            ['g' => '011001', 'kode' => '1011001007', 'nama' => 'Barang Persediaan'],

            // ============================================================
            // 1.02.01.01 – KOMPONEN JEMBATAN BAJA
            // ============================================================
            ['g' => '020101', 'kode' => '1020101001', 'nama' => 'Komponen Jembatan Bailley'],
            ['g' => '020101', 'kode' => '1020101002', 'nama' => 'Komponen Jembatan Baja Prefab'],
            ['g' => '020101', 'kode' => '1020101999', 'nama' => 'Komponen Jembatan Baja Lainnya'],

            // ============================================================
            // 1.02.01.02 – KOMPONEN JEMBATAN PRATEKAN
            // ============================================================
            ['g' => '020102', 'kode' => '1020102001', 'nama' => 'Komponen Jembatan Pratekan Prefab'],
            ['g' => '020102', 'kode' => '1020102999', 'nama' => 'Komponen Jembatan Pratekan Lainnya'],

            // ============================================================
            // 1.02.01.03 – KOMPONEN PERALATAN
            // ============================================================
            ['g' => '020103', 'kode' => '1020103001', 'nama' => 'Dinamo Amper'],
            ['g' => '020103', 'kode' => '1020103002', 'nama' => 'Dinamo Start'],
            ['g' => '020103', 'kode' => '1020103003', 'nama' => 'Transmisi'],
            ['g' => '020103', 'kode' => '1020103004', 'nama' => 'Injection Pump'],
            ['g' => '020103', 'kode' => '1020103005', 'nama' => 'Karburator Unit'],
            ['g' => '020103', 'kode' => '1020103006', 'nama' => 'Motor Hidrolik'],
            ['g' => '020103', 'kode' => '1020103007', 'nama' => 'Engine Bensin'],
            ['g' => '020103', 'kode' => '1020103008', 'nama' => 'Engine Diesel'],
            ['g' => '020103', 'kode' => '1020103999', 'nama' => 'Komponen Peralatan Lainnya'],

            // ============================================================
            // 1.02.01.04 – KOMPONEN RAMBU-RAMBU
            // ============================================================
            ['g' => '020104', 'kode' => '1020104001', 'nama' => 'Komponen Rambu-Rambu Darat'],
            ['g' => '020104', 'kode' => '1020104002', 'nama' => 'Komponen Rambu-Rambu Udara'],
            ['g' => '020104', 'kode' => '1020104999', 'nama' => 'Komponen Rambu-Rambu Lainnya'],

            // ============================================================
            // 1.02.01.05 – ATTACHMENT
            // ============================================================
            ['g' => '020105', 'kode' => '1020105001', 'nama' => 'Blade'],
            ['g' => '020105', 'kode' => '1020105002', 'nama' => 'Boom'],
            ['g' => '020105', 'kode' => '1020105003', 'nama' => 'Bucket'],
            ['g' => '020105', 'kode' => '1020105004', 'nama' => 'Scarifier'],
            ['g' => '020105', 'kode' => '1020105999', 'nama' => 'Attachment Lainnya'],

            // ============================================================
            // 1.02.01.99 – KOMPONEN LAINNYA
            // ============================================================
            ['g' => '020199', 'kode' => '1020199999', 'nama' => 'Komponen Lainnya'],

            // ============================================================
            // 1.02.02.01 – PIPA AIR BESI TUANG (DCI)
            // ============================================================
            ['g' => '020201', 'kode' => '1020201001', 'nama' => 'DCI Filter'],
            ['g' => '020201', 'kode' => '1020201002', 'nama' => 'Pipa Air Besi Tuang'],
            ['g' => '020201', 'kode' => '1020201999', 'nama' => 'Pipa Air Besi Tuang (DCI) Lainnya'],

            // ============================================================
            // 1.02.02.02 – PIPA ASBES SEMEN (ACP)
            // ============================================================
            ['g' => '020202', 'kode' => '1020202001', 'nama' => 'A C P 1,0'],
            ['g' => '020202', 'kode' => '1020202002', 'nama' => 'A C P 1,5'],
            ['g' => '020202', 'kode' => '1020202003', 'nama' => 'A C P 2,0'],
            ['g' => '020202', 'kode' => '1020202004', 'nama' => 'A C P 2,5'],
            ['g' => '020202', 'kode' => '1020202005', 'nama' => 'A C P 3,0'],
            ['g' => '020202', 'kode' => '1020202999', 'nama' => 'Pipa Asbes Semen (ACP) Lainnya'],

            // ============================================================
            // 1.02.02.03 – PIPA BAJA
            // ============================================================
            ['g' => '020203', 'kode' => '1020203001', 'nama' => 'Pipa Baja Gelombang'],
            ['g' => '020203', 'kode' => '1020203002', 'nama' => 'Pipa Baja Konstruksi (CSP)'],
            ['g' => '020203', 'kode' => '1020203003', 'nama' => 'Pipa Baja Lapis Polyethelene'],
            ['g' => '020203', 'kode' => '1020203004', 'nama' => 'Pipa Baja Lapis Seng (GIP)'],
            ['g' => '020203', 'kode' => '1020203999', 'nama' => 'Pipa Baja Lainnya'],

            // ============================================================
            // 1.02.02.04 – PIPA BETON PRATEKAN
            // ============================================================
            ['g' => '020204', 'kode' => '1020204001', 'nama' => 'Fitter Pipa Beton Pratekan'],
            ['g' => '020204', 'kode' => '1020204002', 'nama' => 'Pipa Beton Pratekan'],
            ['g' => '020204', 'kode' => '1020204999', 'nama' => 'Pipa Beton Pratekan Lainnya'],

            // ============================================================
            // 1.02.02.05 – PIPA FIBER GLASS
            // ============================================================
            ['g' => '020205', 'kode' => '1020205001', 'nama' => 'Filter Pipa Fiber Glass'],
            ['g' => '020205', 'kode' => '1020205002', 'nama' => 'Pipa Fiber Glass'],
            ['g' => '020205', 'kode' => '1020205999', 'nama' => 'Pipa Fiber Glass Lainnya'],

            // ============================================================
            // 1.02.02.06 – PIPA PLASTIK PVC (UPVC)
            // ============================================================
            ['g' => '020206', 'kode' => '1020206001', 'nama' => 'Pipa Plastik PVC'],
            ['g' => '020206', 'kode' => '1020206002', 'nama' => 'UPVC Fitter'],
            ['g' => '020206', 'kode' => '1020206999', 'nama' => 'Pipa Plastik PVC (UPVC) Lainnya'],

            // ============================================================
            // 1.02.02.99 – PIPA LAINNYA
            // ============================================================
            ['g' => '020299', 'kode' => '1020299999', 'nama' => 'P I P A Lainnya'],

            // ============================================================
            // 1.02.03.01 – RAMBU-RAMBU
            // ============================================================
            ['g' => '020301', 'kode' => '1020301001', 'nama' => 'Rambu - Rambu Lalu Lintas'],
            ['g' => '020301', 'kode' => '1020301999', 'nama' => 'Rambu-rambu Lainnya'],

            // ============================================================
            // 1.03.01.01 – KOMPONEN BEKAS
            // ============================================================
            ['g' => '030101', 'kode' => '1030101001', 'nama' => 'Komponen Jembatan Baja Bekas'],
            ['g' => '030101', 'kode' => '1030101002', 'nama' => 'Komponen Jembatan Pratekan Bekas'],
            ['g' => '030101', 'kode' => '1030101003', 'nama' => 'Komponen Peralatan Bekas'],
            ['g' => '030101', 'kode' => '1030101004', 'nama' => 'Attachment Bekas'],
            ['g' => '030101', 'kode' => '1030101005', 'nama' => 'Kotak dan Bilik Suara'],
            ['g' => '030101', 'kode' => '1030101999', 'nama' => 'Komponen Bekas Lainnya'],

            // ============================================================
            // 1.03.01.02 – PIPA BEKAS
            // ============================================================
            ['g' => '030102', 'kode' => '1030102001', 'nama' => 'Pipa Air Besi Tuang Bekas'],
            ['g' => '030102', 'kode' => '1030102002', 'nama' => 'Pipa Asbes Semen Bekas'],
            ['g' => '030102', 'kode' => '1030102003', 'nama' => 'Pipa Baja Bekas'],
            ['g' => '030102', 'kode' => '1030102004', 'nama' => 'Pipa Beton Pratekan Bekas'],
            ['g' => '030102', 'kode' => '1030102005', 'nama' => 'Pipa Fiber Gelas Bekas'],
            ['g' => '030102', 'kode' => '1030102006', 'nama' => 'Pipa Plastik PVC (UPVC) Bekas'],
            ['g' => '030102', 'kode' => '1030102999', 'nama' => 'Pipa Bekas Lainnya'],

            // ============================================================
            // 1.03.01.99 – KOMPONEN BEKAS DAN PIPA BEKAS LAINNYA
            // ============================================================
            ['g' => '030199', 'kode' => '1030199999', 'nama' => 'Komponen Bekas Dan Pipa Bekas Lainnya'],
        ];

        /* ----------------------------------------------------------------
         * 3. Simpan ke database (updateOrCreate agar aman dijalankan ulang)
         * ---------------------------------------------------------------- */
        foreach ($items as $item) {
            KodePersediaan::updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'kategori_barang_id' => $cat[$item['g']],
                    'nama_barang'        => $item['nama'],
                ],
            );
        }
    }
}
