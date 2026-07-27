import React, { useState } from "react";
import { StockItem } from "../types";
import { FileUp, FileSpreadsheet, Check, CheckCircle2, ShieldCheck, Database, RefreshCcw, CloudUpload, Clock, Package, DownloadCloud, ArrowRight, BookOpen, AlertCircle, Filter, Search } from "lucide-react";

interface StockManagementProps {
  stockList: StockItem[];
  onUploadStock: (newStock: StockItem[]) => void;
}

interface DraftUploadItem {
  id: string;
  category: string;
  suggestedCode: string;
  name: string;
  qty: number;
  unit: string;
}

export const StockManagement: React.FC<StockManagementProps> = ({
  stockList,
  onUploadStock,
}) => {
  const [isDragging, setIsDragging] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [drafts, setDrafts] = useState<DraftUploadItem[]>([]);
  const [activeTab, setActiveTab] = useState<"current" | "verify">("current");

  const handleSimulateUpload = () => {
    setIsProcessing(true);
    // Simulate reading excel with small delay
    setTimeout(() => {
      setIsProcessing(false);
      setDrafts([
        {
          id: "df-1",
          category: "KERTAS DAN COVER",
          suggestedCode: "1010302001",
          name: "Kertas F4 80gr Sinar Dunia",
          qty: 25,
          unit: "Rim",
        },
        {
          id: "df-2",
          category: "ALAT TULIS KANTOR",
          suggestedCode: "1010301001",
          name: "Spidol Boardmarker Snowman Red",
          qty: 15,
          unit: "Buah",
        },
        {
          id: "df-3",
          category: "BAHAN KOMPUTER",
          suggestedCode: "1010304006",
          name: "Flashdisk SanDisk 32GB USB 3.0",
          qty: 10,
          unit: "Buah",
        },
      ]);
      setActiveTab("verify");
    }, 1500);
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => {
    setIsDragging(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    handleSimulateUpload();
  };

  const handleCodeChange = (id: string, newCode: string) => {
    setDrafts(
      drafts.map((d) => (d.id === id ? { ...d, suggestedCode: newCode } : d))
    );
  };

  const handleVerifyAndApprove = () => {
    const formattedNewStock: StockItem[] = drafts.map((d) => ({
      id: "st-upload-" + Math.random().toString(36).substring(2, 9),
      category: d.category,
      code: d.suggestedCode,
      name: d.name,
      qty: d.qty,
      unit: d.unit,
      lastUpdated: new Date().toISOString().split("T")[0],
    }));

    onUploadStock(formattedNewStock);
    setDrafts([]);
    setActiveTab("current");
  };

  return (
    <div className="space-y-6">
      {/* Banner */}
      <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex flex-col xl:flex-row xl:items-center justify-between gap-5 relative">
        <div className="flex items-center gap-4">
          <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600">
            <Database size={26} strokeWidth={2.5} />
          </div>
          <div>
            <h2 className="text-base font-extrabold text-slate-800 uppercase tracking-wide">Manajemen Stok & Kode Persediaan</h2>
            <p className="text-xs font-medium text-slate-500 mt-1">
              Unggah file Excel stok dan persediaan, verifikasi kode barang,<br className="hidden xl:block"/> dan kelola data inventori dengan mudah.
            </p>
          </div>
        </div>

        {/* Center Tabs */}
        <div className="flex items-center gap-6 xl:absolute xl:left-1/2 xl:-translate-x-1/2 xl:bottom-0 xl:h-full mt-4 xl:mt-0">
          <button className="flex items-center gap-2 text-blue-600 font-bold text-sm border-b-2 border-blue-600 h-full pb-2 xl:pb-0 xl:pt-1">
            <CloudUpload size={16} /> Upload Excel
          </button>
          <button onClick={() => window.location.href = '/stok-upload/riwayat'} className="flex items-center gap-2 text-slate-500 font-bold text-sm h-full pb-2 xl:pb-0 xl:pt-1 hover:text-slate-700">
            <Clock size={16} /> Riwayat Upload
          </button>
          <button onClick={() => window.location.href = "/master-barang"} className="flex items-center gap-2 text-slate-500 font-bold text-sm h-full pb-2 xl:pb-0 xl:pt-1 hover:text-slate-700">
            <Package size={16} /> Master Barang
          </button>
        </div>

        {/* View Tabs */}
        <div className="flex bg-white border border-slate-200 rounded-md overflow-hidden relative z-10 shadow-xs self-start xl:self-auto">
          <button
            onClick={() => setActiveTab("current")}
            className={`px-6 py-2.5 text-xs font-bold transition-all ${
              activeTab === "current"
                ? "bg-white text-blue-600 shadow-sm ring-1 ring-inset ring-slate-200"
                : "text-slate-500 hover:text-slate-700 hover:bg-slate-50/50"
            }`}
          >
            Stok Aktif ({stockList.length})
          </button>
          <button
            onClick={() => setActiveTab("verify")}
            className={`px-6 py-2.5 text-xs font-bold transition-all flex items-center gap-1.5 ${
              activeTab === "verify"
                ? "bg-white text-blue-600 shadow-sm ring-1 ring-inset ring-slate-200"
                : "text-slate-500 hover:text-slate-700 hover:bg-slate-50/50"
            }`}
          >
            Verifikasi Kode
            {drafts.length > 0 && (
              <span className={`text-2xs px-1.5 py-0.5 rounded font-bold ${
                activeTab === "verify" ? "bg-blue-100 text-blue-700" : "bg-slate-200 text-slate-600"
              }`}>
                {drafts.length}
              </span>
            )}
          </button>
        </div>
      </div>

      {/* Upload Drag & Drop Area */}
      <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div className="flex items-center gap-2 mb-4 text-blue-600 font-bold text-sm">
          <CloudUpload size={18} /> Upload Stok & Persediaan Excel
        </div>
        <div
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
          onDrop={handleDrop}
          onClick={handleSimulateUpload}
          className={`border-2 border-dashed rounded-xl py-12 px-6 text-center cursor-pointer transition-all ${
            isDragging ? 'border-blue-400 bg-blue-100/50' : 'border-blue-200 bg-blue-50/30 hover:bg-blue-50/50'
          }`}
        >
          <div className="mx-auto w-12 h-12 bg-white rounded-full border border-blue-100 flex items-center justify-center shadow-xs mb-3 text-blue-500">
            <CloudUpload size={24} strokeWidth={2} />
          </div>
          <p className="text-sm font-bold text-slate-700 mb-1">
            Seret & lepas file Anda ke sini, atau klik untuk menelusuri
          </p>
          <p className="text-xs text-slate-500">
            Hanya menerima format Excel (.xlsx, .xls) dengan ukuran maks 10MB
          </p>
        </div>
        
        <div className="flex flex-col sm:flex-row items-center justify-between mt-4 gap-4">
          <button className="flex items-center gap-2 text-blue-600 font-bold text-xs hover:text-blue-700">
            <DownloadCloud size={14} /> Download Template Excel
          </button>
          <button onClick={handleSimulateUpload} className="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-2">
            Mulai Proses Upload <ArrowRight size={14} />
          </button>
        </div>
      </div>

      {/* Petunjuk Area */}
      <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4 mb-8">
        <div className="flex items-center gap-2 text-blue-600 font-bold text-sm uppercase tracking-wide">
          <BookOpen size={16} /> PETUNJUK FORMAT DOKUMEN EXCEL
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 text-xs text-slate-600">
          <div className="space-y-2">
            <h4 className="font-bold text-blue-600">1. Struktur Dokumen</h4>
            <ul className="list-disc pl-4 space-y-1.5 marker:text-slate-400">
              <li>Dapat berisi banyak sheet (sistem akan membaca <strong>seluruh sheet</strong>).</li>
              <li>Setiap sheet mewakili satu nota/transaksi (contoh nama: <code className="bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded">020126 RP</code>).</li>
              <li>Informasi Supplier/Nama Toko di baris 2 Kolom A (contoh: <code className="bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded">SUPPLIER : REDZKY PLASTIK</code>).</li>
              <li>Header tabel di baris 4 dan baris data dimulai dari baris 5.</li>
            </ul>
          </div>
          <div className="space-y-2">
            <h4 className="font-bold text-blue-600">2. Layout Tabel yang Didukung</h4>
            <ul className="list-disc pl-4 space-y-1.5 marker:text-slate-400">
              <li><strong>Format Tanpa Pajak:</strong> A (No), B (Kode), C (Nama), D (Jumlah), E (Satuan), F (Harga Satuan), G (Total).</li>
              <li><strong>Format Dengan Pajak:</strong> A (No), B (Kode), C (Nama), D (Jumlah), E (Satuan), F (Harga Satuan), G (Harga + Pajak), H (Total), I (Pajak).</li>
              <li>Baris total di baris paling bawah akan dilewati secara otomatis.</li>
            </ul>
          </div>
        </div>
        <div className="bg-amber-50 border border-amber-100 rounded-lg p-3 text-xs text-amber-800 flex gap-2 mt-4">
          <AlertCircle size={14} className="text-amber-600 shrink-0 mt-0.5" />
          <p>
            <strong>Catatan Perhitungan Pajak:</strong> Jika sheet memuat kolom Pajak (bernilai 1.11 atau formula serupa) atau kolom Harga Satuan + Pajak, sistem akan otomatis melakukan perbandingan total belanja dengan menyertakan PPN 11% sesuai aturan instansi.
          </p>
        </div>
      </div>

      {/* Main Tab Contents */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-4 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <div className="bg-blue-50 text-blue-600 p-1.5 rounded-lg">
              <FileSpreadsheet size={16} />
            </div>
            <h3 className="text-sm font-extrabold text-slate-800">
              {activeTab === "current" ? "Daftar Barang Stok Aktif" : "Daftar Barang Menunggu Verifikasi"}
            </h3>
          </div>
          <div className="flex items-center gap-2 w-full sm:w-auto">
            <div className="relative flex-1 sm:flex-none">
              <Search className="absolute left-3 top-2.5 text-slate-400" size={14} />
              <input type="text" placeholder="Cari kode atau nama barang..." className="w-full sm:w-64 pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-500" />
            </div>
            <button className="flex items-center gap-2 px-4 py-2 border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-50">
              <Filter size={14} /> Filter
            </button>
          </div>
        </div>
      {activeTab === "current" ? (
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50 text-slate-600 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                <th className="px-5 py-3">Kode Persediaan</th>
                <th className="px-5 py-3">Nama Barang</th>
                <th className="px-5 py-3">Kategori</th>
                <th className="px-5 py-3 text-right">Stok Tersedia</th>
                <th className="px-5 py-3">Satuan</th>
                <th className="px-5 py-3">Terakhir Diperbarui</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {stockList.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50/50 transition-colors text-xs font-mono">
                  <td className="px-5 py-3 font-semibold text-indigo-600">
                    {item.code}
                  </td>
                  <td className="px-5 py-3 font-bold text-slate-800 font-sans">
                    {item.name}
                  </td>
                  <td className="px-5 py-3 text-xs font-medium text-slate-500 font-sans">
                    {item.category}
                  </td>
                  <td className="px-5 py-3 text-right font-bold text-slate-700 font-sans">
                    {item.qty}
                  </td>
                  <td className="px-5 py-3 font-medium text-slate-500 font-sans">
                    {item.unit}
                  </td>
                  <td className="px-5 py-3 text-slate-400 font-sans">
                    {item.lastUpdated}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : (
        /* Code Verification Workspace */
        <div>
          {drafts.length === 0 ? (
            <div className="py-12 border border-slate-200 rounded-lg">
              <div className="flex flex-col items-center justify-center text-center">
                <CheckCircle2 size={40} className="text-slate-300 mb-3" strokeWidth={1} />
                <h4 className="text-sm font-extrabold text-slate-800 mb-1">Tidak ada draf dalam antrean verifikasi</h4>
                <p className="text-xs text-slate-500">Gunakan pengunggah Excel di atas untuk memproses baris baru.</p>
              </div>
            </div>
          ) : (
            <div>
              <div className="bg-amber-50 border border-amber-100 rounded p-3.5 mb-4 text-xs text-amber-800 flex items-start gap-2">
                <ShieldCheck size={14} className="text-amber-600 mt-0.5 flex-shrink-0" />
                <div>
                  <span className="font-extrabold">Pemeriksaan Ganda Diperlukan:</span> Petugas Persediaan wajib melakukan pemeriksaan dan memverifikasi kesesuaian kategori, nama barang, dan kode persediaan sebelum data masuk ke database utama (Section 3.2, 4.12).
                </div>
              </div>

              <div className="overflow-x-auto border border-slate-200 rounded mb-4">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-slate-50 text-slate-600 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                      <th className="px-5 py-3">Kategori Barang</th>
                      <th className="px-5 py-3">Nama Barang</th>
                      <th className="px-5 py-3">Jumlah Excel</th>
                      <th className="px-5 py-3">Satuan</th>
                      <th className="px-5 py-3">Kode Persediaan (Bisa Diedit)</th>
                      <th className="px-5 py-3 text-center">Status Verif</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {drafts.map((d) => (
                      <tr key={d.id} className="hover:bg-slate-50/50 transition-colors text-xs font-mono">
                        <td className="px-5 py-3 text-xs text-slate-500 font-semibold font-sans">
                          {d.category}
                        </td>
                        <td className="px-5 py-3 font-bold text-slate-800 font-sans">
                          {d.name}
                        </td>
                        <td className="px-5 py-3 font-bold text-slate-700 font-sans">
                          {d.qty}
                        </td>
                        <td className="px-5 py-3 text-slate-500 font-sans">
                          {d.unit}
                        </td>
                        <td className="px-5 py-3">
                          <input
                            type="text"
                            value={d.suggestedCode}
                            onChange={(e) => handleCodeChange(d.id, e.target.value.toUpperCase())}
                            className="bg-white border border-slate-200 rounded px-2 py-1 text-xs font-mono font-bold text-indigo-700 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                          />
                        </td>
                        <td className="px-5 py-3 text-center">
                          <span className="inline-flex items-center gap-1 text-xs text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 font-bold">
                            <Check size={11} />
                            Valid (Auto)
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <div className="flex justify-end gap-2.5">
                <button
                  onClick={() => setDrafts([])}
                  className="px-3.5 py-2 rounded text-xs font-bold text-slate-500 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-all"
                >
                  Batalkan Draf
                </button>
                <button
                  onClick={handleVerifyAndApprove}
                  className="px-4 py-2 rounded text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition-all shadow-xs flex items-center gap-1.5"
                >
                  <Check size={13} />
                  Setujui & Simpan ke Stok Master
                </button>
              </div>
            </div>
          )}
        </div>
      )}
      </div>
    </div>
  );
};
