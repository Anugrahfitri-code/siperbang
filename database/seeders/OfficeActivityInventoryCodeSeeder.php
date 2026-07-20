<?php
namespace Database\Seeders;
use App\Models\KategoriBarang;
use App\Models\KodePersediaan;
use Illuminate\Database\Seeder;

class OfficeActivityInventoryCodeSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = [];

        $categoryIds['01'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'ALAT TULIS KANTOR'])
            ->id;
        $categoryIds['02'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'KERTAS DAN COVER'])
            ->id;
        $categoryIds['03'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'BAHAN CETAK'])
            ->id;
        $categoryIds['04'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'BAHAN KOMPUTER'])
            ->id;
        $categoryIds['05'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'PERABOT KANTOR'])
            ->id;
        $categoryIds['06'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'ALAT LISTRIK'])
            ->id;
        $categoryIds['07'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'PERLENGKAPAN DINAS'])
            ->id;
        $categoryIds['08'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'KAPORLAP DAN PERLENGKAPAN SATWA'])
            ->id;
        $categoryIds['09'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'PERLENGKAPAN PENUNJANG KEGIATAN KANTOR'])
            ->id;
        $categoryIds['10'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'ALAT PENUNJANG KEGIATAN KANTOR'])
            ->id;
        $categoryIds['11'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'BAHAN PENUNJANG KEGIATAN KANTOR'])
            ->id;
        $categoryIds['12'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'ALAT/BAHAN PENUNJANG KEGIATAN KEAMANAN'])
            ->id;
        $categoryIds['13'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'BAHAN BAKAR DAN PELUMAS'])
            ->id;
        $categoryIds['14'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'OBAT-OBATAN'])
            ->id;
        $categoryIds['15'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'DOKUMEN LAYANAN KEIMIGRASIAN'])
            ->id;
        $categoryIds['16'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'BLANGKO NIKAH'])
            ->id;
        $categoryIds['99'] = KategoriBarang::query()
            ->firstOrCreate(['nama' => 'ALAT/BAHAN UNTUK KEGIATAN KANTOR LAINNYA'])
            ->id;

        $items = [
            ['group' => '01', 'kode' => '1010301001', 'nama_barang' => 'Alat Tulis'],
            ['group' => '01', 'kode' => '1010301002', 'nama_barang' => 'Tinta Tulis, Tinta Stempel'],
            ['group' => '01', 'kode' => '1010301003', 'nama_barang' => 'Penjepit Kertas'],
            ['group' => '01', 'kode' => '1010301004', 'nama_barang' => 'Penghapus/Korektor'],
            ['group' => '01', 'kode' => '1010301005', 'nama_barang' => 'Buku Tulis'],
            ['group' => '01', 'kode' => '1010301006', 'nama_barang' => 'Ordner Dan Map'],
            ['group' => '01', 'kode' => '1010301007', 'nama_barang' => 'Penggaris'],
            ['group' => '01', 'kode' => '1010301008', 'nama_barang' => 'Cutter (Alat Tulis Kantor)'],
            ['group' => '01', 'kode' => '1010301009', 'nama_barang' => 'Pita Mesin Ketik'],
            ['group' => '01', 'kode' => '1010301010', 'nama_barang' => 'Alat Perekat'],
            ['group' => '01', 'kode' => '1010301011', 'nama_barang' => 'Stadler HD'],
            ['group' => '01', 'kode' => '1010301012', 'nama_barang' => 'Staples'],
            ['group' => '01', 'kode' => '1010301013', 'nama_barang' => 'Isi Staples'],
            ['group' => '01', 'kode' => '1010301014', 'nama_barang' => 'Barang Cetakan'],
            ['group' => '01', 'kode' => '1010301015', 'nama_barang' => 'Seminar Kit'],
            ['group' => '01', 'kode' => '1010301999', 'nama_barang' => 'Alat Tulis Kantor Lainnya'],
            ['group' => '02', 'kode' => '1010302001', 'nama_barang' => 'Kertas HVS'],
            ['group' => '02', 'kode' => '1010302002', 'nama_barang' => 'Berbagai Kertas'],
            ['group' => '02', 'kode' => '1010302003', 'nama_barang' => 'Kertas Cover'],
            ['group' => '02', 'kode' => '1010302004', 'nama_barang' => 'Amplop'],
            ['group' => '02', 'kode' => '1010302005', 'nama_barang' => 'Kop Surat'],
            ['group' => '02', 'kode' => '1010302999', 'nama_barang' => 'Kertas Dan Cover Lainnya'],
            ['group' => '03', 'kode' => '1010303001', 'nama_barang' => 'Transparant Sheet'],
            ['group' => '03', 'kode' => '1010303002', 'nama_barang' => 'Tinta Cetak'],
            ['group' => '03', 'kode' => '1010303003', 'nama_barang' => 'Plat Cetak'],
            ['group' => '03', 'kode' => '1010303004', 'nama_barang' => 'Stensil Sheet'],
            ['group' => '03', 'kode' => '1010303005', 'nama_barang' => 'Chenical/Bahan Kimia Cetak'],
            ['group' => '03', 'kode' => '1010303006', 'nama_barang' => 'Film Cetak'],
            ['group' => '03', 'kode' => '1010303999', 'nama_barang' => 'Bahan Cetak Lainnya'],
            ['group' => '04', 'kode' => '1010304001', 'nama_barang' => 'Continuous Form'],
            ['group' => '04', 'kode' => '1010304002', 'nama_barang' => 'Computer File/Tempat Disket'],
            ['group' => '04', 'kode' => '1010304003', 'nama_barang' => 'Pita Printer'],
            ['group' => '04', 'kode' => '1010304004', 'nama_barang' => 'Tinta/Toner Printer'],
            ['group' => '04', 'kode' => '1010304005', 'nama_barang' => 'Disket'],
            ['group' => '04', 'kode' => '1010304006', 'nama_barang' => 'USB/Flash Disk'],
            ['group' => '04', 'kode' => '1010304007', 'nama_barang' => 'kartu Memori'],
            ['group' => '04', 'kode' => '1010304008', 'nama_barang' => 'CD/DVD Drive'],
            ['group' => '04', 'kode' => '1010304009', 'nama_barang' => 'Harddisk Internal'],
            ['group' => '04', 'kode' => '1010304010', 'nama_barang' => 'Mouse'],
            ['group' => '04', 'kode' => '1010304011', 'nama_barang' => 'CD/DVD'],
            ['group' => '04', 'kode' => '1010304999', 'nama_barang' => 'Bahan Komputer Lainnya'],
            ['group' => '05', 'kode' => '1010305001', 'nama_barang' => 'Sapu Dan Sikat'],
            ['group' => '05', 'kode' => '1010305002', 'nama_barang' => 'Alat-Alat Pel Dan Lap'],
            ['group' => '05', 'kode' => '1010305003', 'nama_barang' => 'Ember, Slang, Dan Tempat Air Lainnya'],
            ['group' => '05', 'kode' => '1010305004', 'nama_barang' => 'Keset Dan Tempat Sampah'],
            ['group' => '05', 'kode' => '1010305005', 'nama_barang' => 'Kunci, Kran Dan Semprotan'],
            ['group' => '05', 'kode' => '1010305006', 'nama_barang' => 'Alat Pengikat'],
            ['group' => '05', 'kode' => '1010305007', 'nama_barang' => 'Peralatan Ledeng'],
            ['group' => '05', 'kode' => '1010305008', 'nama_barang' => 'Bahan Kimia Untuk Pembersih'],
            ['group' => '05', 'kode' => '1010305009', 'nama_barang' => 'Alat Untuk Makan Dan Minum'],
            ['group' => '05', 'kode' => '1010305010', 'nama_barang' => 'Kaos Lampu Petromak'],
            ['group' => '05', 'kode' => '1010305011', 'nama_barang' => 'Kaca Lampu Petromak'],
            ['group' => '05', 'kode' => '1010305012', 'nama_barang' => 'Pengharum Ruangan'],
            ['group' => '05', 'kode' => '1010305013', 'nama_barang' => 'Kuas'],
            ['group' => '05', 'kode' => '1010305014', 'nama_barang' => 'Segel/Tanda Pengaman'],
            ['group' => '05', 'kode' => '1010305999', 'nama_barang' => 'Perabot Kantor Lainnya'],
            ['group' => '06', 'kode' => '1010306001', 'nama_barang' => 'Kabel Listrik'],
            ['group' => '06', 'kode' => '1010306002', 'nama_barang' => 'Lampu Listrik'],
            ['group' => '06', 'kode' => '1010306003', 'nama_barang' => 'Stop Kontak'],
            ['group' => '06', 'kode' => '1010306004', 'nama_barang' => 'Saklar'],
            ['group' => '06', 'kode' => '1010306005', 'nama_barang' => 'Stacker'],
            ['group' => '06', 'kode' => '1010306006', 'nama_barang' => 'Balast'],
            ['group' => '06', 'kode' => '1010306007', 'nama_barang' => 'Starter'],
            ['group' => '06', 'kode' => '1010306008', 'nama_barang' => 'Vitting'],
            ['group' => '06', 'kode' => '1010306009', 'nama_barang' => 'Accu'],
            ['group' => '06', 'kode' => '1010306010', 'nama_barang' => 'Batu Baterai'],
            ['group' => '06', 'kode' => '1010306011', 'nama_barang' => 'Stavol'],
            ['group' => '06', 'kode' => '1010306999', 'nama_barang' => 'Alat Listrik Lainnya'],
            ['group' => '07', 'kode' => '1010307001', 'nama_barang' => 'Bahan Baku Pakaian'],
            ['group' => '07', 'kode' => '1010307002', 'nama_barang' => 'Penutup Kepala'],
            ['group' => '07', 'kode' => '1010307003', 'nama_barang' => 'Penutup Badan'],
            ['group' => '07', 'kode' => '1010307004', 'nama_barang' => 'Penutup Tangan'],
            ['group' => '07', 'kode' => '1010307005', 'nama_barang' => 'Penutup Kaki'],
            ['group' => '07', 'kode' => '1010307006', 'nama_barang' => 'Atribut'],
            ['group' => '07', 'kode' => '1010307007', 'nama_barang' => 'Perlengkapan Lapangan'],
            ['group' => '07', 'kode' => '1010307999', 'nama_barang' => 'Perlengkapan Dinas Lainnya'],
            ['group' => '08', 'kode' => '1010308001', 'nama_barang' => 'Kaporlap dan Perlengkapan Satwa Anjing'],
            ['group' => '08', 'kode' => '1010308002', 'nama_barang' => 'Kaporlap dan Perlengkapan Satwa Kuda'],
            ['group' => '08', 'kode' => '1010308999', 'nama_barang' => 'Kaporlap Dan Perlengkapan Satwa Lainnya'],
            ['group' => '09', 'kode' => '1010309001', 'nama_barang' => 'Meterai'],
            ['group' => '09', 'kode' => '1010309002', 'nama_barang' => 'Prangko'],
            ['group' => '09', 'kode' => '1010309003', 'nama_barang' => 'Stempel'],
            ['group' => '09', 'kode' => '1010309999', 'nama_barang' => 'Perlengkapan Penunjang Kegiatan Kantor Lainnya'],
            ['group' => '10', 'kode' => '1010310001', 'nama_barang' => 'Persediaan Berupa Alat Penunjang Kedokteran'],
            ['group' => '10', 'kode' => '1010310002', 'nama_barang' => 'Persediaan Berupa Alat Penunjang Laboratorium'],
            ['group' => '10', 'kode' => '1010310003', 'nama_barang' => 'Persediaan Berupa Alat Penunjang Studio Dan Komunikasi'],
            ['group' => '10', 'kode' => '1010310999', 'nama_barang' => 'Alat Penunjang Kegiatan Kantor Lainnya'],
            ['group' => '11', 'kode' => '1010311001', 'nama_barang' => 'Persediaan Berupa Bahan Penunjang Kedokteran'],
            ['group' => '11', 'kode' => '1010311002', 'nama_barang' => 'Persediaan Berupa Bahan Penunjang Laboratorium'],
            ['group' => '11', 'kode' => '1010311003', 'nama_barang' => 'Persediaan Berupa Bahan Penunjang Pertanian'],
            ['group' => '11', 'kode' => '1010311999', 'nama_barang' => 'Bahan Penunjang Kegiatan Kantor Lainnya'],
            ['group' => '12', 'kode' => '1010312001', 'nama_barang' => 'Persediaan Berupa Alat/Bahan Daktiloskopi'],
            ['group' => '12', 'kode' => '1010312002', 'nama_barang' => 'Persediaan Berupa Alat/Bahan Lalu Lintas'],
            ['group' => '12', 'kode' => '1010312999', 'nama_barang' => 'Alat/Bahan Penunjang Kegiatan Keamanan Lainnya'],
            ['group' => '13', 'kode' => '1010313001', 'nama_barang' => 'Bahan Bakar Minyak (Barang Konsumsi)'],
            ['group' => '13', 'kode' => '1010313002', 'nama_barang' => 'Minyak Pelumas (Barang Konsumsi)'],
            ['group' => '13', 'kode' => '1010313999', 'nama_barang' => 'Bahan Bakar Dan Pelumas Lainnya (Barang Konsumsi)'],
            ['group' => '14', 'kode' => '1010314001', 'nama_barang' => 'Obat Cair (Barang Konsumsi)'],
            ['group' => '14', 'kode' => '1010314002', 'nama_barang' => 'Obat Padat (Barang Konsumsi)'],
            ['group' => '14', 'kode' => '1010314003', 'nama_barang' => 'Obat Gas (Barang Konsumsi)'],
            ['group' => '14', 'kode' => '1010314004', 'nama_barang' => 'Obat Serbuk/Tepung (Barang Konsumsi)'],
            ['group' => '14', 'kode' => '1010314005', 'nama_barang' => 'Obat Gel/ Salep (Barang Konsumsi)'],
            ['group' => '14', 'kode' => '1010314999', 'nama_barang' => 'Obat Lainnya (Barang Konsumsi)'],
            ['group' => '15', 'kode' => '1010315001', 'nama_barang' => 'Dokumen Keimigrasian'],
            ['group' => '15', 'kode' => '1010315999', 'nama_barang' => 'Dokumen Layanan Keimigrasian Lainnya'],
            ['group' => '16', 'kode' => '1010316001', 'nama_barang' => 'Akte Nikah'],
            ['group' => '16', 'kode' => '1010316002', 'nama_barang' => 'Buku Nikah'],
            ['group' => '16', 'kode' => '1010316003', 'nama_barang' => 'Daftar Pemeriksaan Nikah'],
            ['group' => '16', 'kode' => '1010316004', 'nama_barang' => 'Duplikat Nikah'],
            ['group' => '16', 'kode' => '1010316005', 'nama_barang' => 'Kartu Nikah'],
            ['group' => '99', 'kode' => '1010399999', 'nama_barang' => 'Alat/bahan Untuk Kegiatan Kantor Lainnya'],
        ];

        foreach ($items as $item) {
            KodePersediaan::query()->updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'kategori_barang_id' => $categoryIds[$item['group']],
                    'nama_barang' => $item['nama_barang'],
                ],
            );
        }
    }
}
