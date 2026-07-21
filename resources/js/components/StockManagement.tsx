import React, { useState } from "react";
import { StockItem } from "../types";
import { FileUp, FileSpreadsheet, Check, CheckCircle2, ShieldCheck, Database, RefreshCcw } from "lucide-react";

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
          category: "Alat Tulis Kantor (ATK)",
          suggestedCode: "ATK-PAP-F4",
          name: "Kertas F4 80gr Sinar Dunia",
          qty: 25,
          unit: "Rim",
        },
        {
          id: "df-2",
          category: "Alat Tulis Kantor (ATK)",
          suggestedCode: "ATK-MKR-SND-RED",
          name: "Spidol Boardmarker Snowman Red",
          qty: 15,
          unit: "Buah",
        },
        {
          id: "df-3",
          category: "Peralatan Komputer",
          suggestedCode: "KOM-USB-SAN",
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
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
        <div className="flex items-center gap-4">
          <div className="bg-indigo-50 text-indigo-600 p-3 rounded-lg border border-indigo-100 shadow-xs">
            <Database size={22} strokeWidth={2} />
          </div>
          <div>
            <h2 className="text-lg font-semibold leading-7 text-slate-900">Manajemen Stok & Kode Persediaan</h2>
            <p className="text-sm font-normal leading-5 text-slate-500 mt-0.5">
              Unggah file Excel stok dan verifikasi kode persediaan barang masuk
            </p>
          </div>
        </div>

        {/* View Tabs */}
        <div className="flex bg-slate-50 border border-slate-200 rounded-md overflow-hidden self-start md:self-auto">
          <button
            onClick={() => setActiveTab("current")}
            className={`px-6 py-2.5 text-xs font-bold transition-all border-b-2 ${
              activeTab === "current"
                ? "bg-white text-blue-600 border-blue-600 shadow-sm"
                : "text-slate-500 hover:text-slate-700 border-transparent hover:bg-slate-100"
            }`}
          >
            Stok Aktif ({stockList.length})
          </button>
          <button
            onClick={() => setActiveTab("verify")}
            className={`px-6 py-2.5 text-xs font-bold transition-all flex items-center gap-1.5 border-b-2 ${
              activeTab === "verify"
                ? "bg-white text-blue-600 border-blue-600 shadow-sm"
                : "text-slate-500 hover:text-slate-700 border-transparent hover:bg-slate-100"
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
      <div className="mb-8">
        <div
          onClick={() => window.location.href = '/stok-upload'}
          className="block border-2 border-dashed border-indigo-200 bg-indigo-50/20 rounded-xl py-12 px-6 text-center hover:bg-indigo-50/50 cursor-pointer transition-all"
        >
          <div className="mx-auto w-12 h-12 bg-white rounded-full border border-indigo-100 flex items-center justify-center shadow-xs mb-3">
            <FileSpreadsheet size={24} className="text-emerald-500" strokeWidth={2} />
          </div>
          <h4 className="text-base font-extrabold text-slate-800 mb-1">
            Buka Modul Upload File Excel Stok (Laravel)
          </h4>
          <p className="text-xs text-slate-500 mb-5">
            Klik di sini untuk berpindah ke halaman khusus Upload & Verifikasi Excel yang baru dibuat.
          </p>
          <div className="inline-flex items-center gap-2 bg-emerald-600 text-white px-6 py-2.5 rounded-lg font-bold text-xs hover:bg-emerald-700 shadow-sm transition-colors">
            <FileUp size={14} />
            <span>Buka Halaman Upload</span>
          </div>
        </div>
      </div>

      {/* Main Tab Contents */}
      <div className="mb-2">
        <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-3">
          {activeTab === "current" ? "DAFTAR BARANG STOK AKTIF" : "DAFTAR BARANG MENUNGGU VERIFIKASI KODE"}
        </h3>
      </div>
      {activeTab === "current" ? (
        <div className="overflow-x-auto border border-slate-200 rounded">
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
  );
};
