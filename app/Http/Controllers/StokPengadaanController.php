<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\PengajuanBarang;
use App\Models\StockItem;
use App\Models\Distribusi;

class StokPengadaanController extends Controller
{

    /**
     * Menampilkan daftar pengajuan
     * yang perlu dicek stoknya.
     */
    public function index()
    {
        $pengajuan = PengajuanBarang::with('barang')
            ->where('status', 'Disetujui')
            ->get();

        return view('stok-pengadaan.index', compact('pengajuan'));
    }

    /**
     * Menampilkan detail pengajuan
     * dan membandingkan dengan stok.
     */
    public function cekStok($id)
    {
        $pengajuan = PengajuanBarang::with('barang')->findOrFail($id);

        $stok = StockItem::where('barang_id', $pengajuan->barang_id)
            ->first();

        return view('stok-pengadaan.cek-stok', compact(
            'pengajuan',
            'stok'
        ));
    }

    /**
     * Distribusi barang apabila stok mencukupi.
     */
    public function prosesDistribusi($id)
    {
        DB::beginTransaction();

        try {

            $pengajuan = PengajuanBarang::findOrFail($id);

            $stok = StockItem::where('barang_id', $pengajuan->barang_id)
                ->firstOrFail();

            if ($stok->jumlah < $pengajuan->jumlah) {

                return redirect()->back()
                    ->with('error', 'Stok tidak mencukupi.');
            }

            // Kurangi stok
            $stok->jumlah -= $pengajuan->jumlah;
            $stok->save();

            // Simpan riwayat distribusi
            Distribusi::create([
                'pengajuan_id' => $pengajuan->id,
                'barang_id'    => $pengajuan->barang_id,
                'jumlah'       => $pengajuan->jumlah,
                'tanggal'      => now(),
            ]);

            // Update status pengajuan
            $pengajuan->status = 'Selesai';
            $pengajuan->save();

            DB::commit();

            return redirect()->route('stok-pengadaan.index')
                ->with('success', 'Distribusi berhasil dilakukan.');

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Proses apabila stok tidak mencukupi.
     */
    public function prosesPengadaan(Request $request, $id)
    {
        $request->validate([
            'status' => 'required',
            'kuitansi' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'bast' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        $pengajuan = PengajuanBarang::findOrFail($id);

        if ($request->hasFile('kuitansi')) {

            $kuitansi = $request->file('kuitansi')
                ->store('kuitansi', 'public');

            $pengajuan->kuitansi = $kuitansi;
        }

        if ($request->hasFile('bast')) {

            $bast = $request->file('bast')
                ->store('bast', 'public');

            $pengajuan->bast = $bast;
        }

        $pengajuan->status = $request->status;

        $pengajuan->save();

        return redirect()->route('stok-pengadaan.index')
            ->with('success', 'Pengadaan berhasil diproses.');
    }

}