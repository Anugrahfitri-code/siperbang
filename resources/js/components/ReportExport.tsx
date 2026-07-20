import React, { useState } from "react";
import { ReceiptData, ProcurementMethod } from "../types";
import { FileSpreadsheet, Search, Filter, DownloadCloud, Sparkles, Check, RefreshCw } from "lucide-react";
import { AlertDialog } from "./AlertDialog";

interface ReportExportProps {
  receipts: ReceiptData[];
}

export const ReportExport: React.FC<ReportExportProps> = ({ receipts }) => {
  const [filterMonth, setFilterMonth] = useState("All");
  const [alertMsg, setAlertMsg] = useState<{ title: string; message: string } | null>(null);
  const [filterYear, setFilterYear] = useState("2026");
  const [searchQuery, setSearchQuery] = useState("");
  const [isAnnualRecap, setIsAnnualRecap] = useState(false);
  const [isExporting, setIsExporting] = useState(false);
  const [exportSuccess, setExportSuccess] = useState(false);

  const months = [
    { value: "All", label: "Semua Bulan" },
    { value: "01", label: "Januari" },
    { value: "02", label: "Februari" },
    { value: "03", label: "Maret" },
    { value: "04", label: "April" },
    { value: "05", label: "Mei" },
    { value: "06", label: "Juni" },
    { value: "07", label: "Juli" },
    { value: "08", label: "Agustus" },
    { value: "09", label: "September" },
    { value: "10", label: "Oktober" },
    { value: "11", label: "November" },
    { value: "12", label: "Desember" },
  ];

  // Helper to check if receipt matches filter
  const verifiedReceipts = receipts.filter((r) => r.isVerified);

  const filteredReceipts = verifiedReceipts.filter((r) => {
    // Year filter
    if (filterYear !== "All") {
      const year = r.date.split("-")[0];
      if (year !== filterYear) return false;
    }

    // Month filter (only if not annual recap, which forces all months)
    if (!isAnnualRecap && filterMonth !== "All") {
      const month = r.date.split("-")[1];
      if (month !== filterMonth) return false;
    }

    // Search query
    if (searchQuery.trim() !== "") {
      const query = searchQuery.toLowerCase();
      const matchesStore = r.storeName.toLowerCase().includes(query);
      const matchesInvoice = r.invoiceNo.toLowerCase().includes(query);
      const matchesItem = r.items.some((it) => it.name.toLowerCase().includes(query));
      return matchesStore || matchesInvoice || matchesItem;
    }

    return true;
  });

  // Flatten receipts into row items for detailed Excel representation
  const reportRows = filteredReceipts.flatMap((rc) =>
    rc.items.map((it) => ({
      invoiceNo: rc.invoiceNo,
      date: rc.date,
      storeName: rc.storeName,
      itemName: it.name,
      qty: it.qty,
      price: it.price,
      subtotal: it.subtotal,
      // Proportional tax allocation per item
      taxAmount: rc.isTaxed ? Math.round(it.subtotal * (rc.taxRate / 100)) : 0,
      total: it.subtotal + (rc.isTaxed ? Math.round(it.subtotal * (rc.taxRate / 100)) : 0),
      method: rc.method,
      // Section 2.5: BAST columns are emptied on Annual Recap!
      bastName: isAnnualRecap ? "" : rc.bastName || "-",
      bastDate: isAnnualRecap ? "" : rc.bastDate || "-",
      bookDate: isAnnualRecap ? "" : rc.date, // tanggal buku
    }))
  );

  const handleRealExport = async () => {
    try {
      setIsExporting(true);
      setExportSuccess(false);

      const queryParams = new URLSearchParams({
        month: isAnnualRecap ? "All" : filterMonth,
        year: filterYear,
        search: searchQuery,
        annual: isAnnualRecap ? "true" : "false"
      });

      const response = await fetch(`/api/export-excel?${queryParams.toString()}`, {
        method: "GET"
      });

      if (!response.ok) {
        throw new Error("Gagal mengunduh berkas laporan dari server.");
      }

      // 1. Ambil response dalam bentuk Teks (karena backend mengirim streaming CSV)
      const csvText = await response.text();
      
      // 2. Bungkus teks tersebut ke dalam Blob dengan encoding data Spreadsheet Excel
      const blob = new Blob([csvText], { type: "text/csv;charset=utf-8;" });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      
      // 3. Simpan sementara dengan ekstensi .csv agar Excel di komputermu bisa membacanya tanpa protes
      a.download = isAnnualRecap 
        ? `SIPERBANG_REKAP_TAHUNAN_${filterYear}.csv`
        : `SIPERBANG_REKAP_BULANAN_${filterMonth}_${filterYear}.csv`;
      
      document.body.appendChild(a);
      a.click();
      
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      
      setExportSuccess(true);
      setTimeout(() => setExportSuccess(false), 4000);
    } catch (error) {
      console.error("Proses ekspor gagal:", error);
      setAlertMsg({ title: "Gagal Ekspor", message: "Terjadi kesalahan saat mengekspor laporan ke Excel." });
    } finally {
      setIsExporting(false);
    }
  };

  const formatIDR = (num: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(num);
  };

  return (
    <>
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6 border-b border-slate-100 pb-5">
        <div className="flex items-center gap-3">
          <div className="bg-emerald-50 text-emerald-600 p-2.5 rounded border border-emerald-100">
            <FileSpreadsheet size={18} />
          </div>
          <div>
            <h2 className="text-base font-extrabold text-slate-800 tracking-tight">Rekap Laporan & Export Excel</h2>
            <p className="text-[11px] text-slate-500">
              Saring laporan kuitansi tervalidasi dan unduh spreadsheet Excel untuk pembukuan
            </p>
          </div>
        </div>

        <button
          onClick={handleRealExport}
          disabled={isExporting || reportRows.length === 0}
          className={`px-4 py-2 rounded text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-xs ${
            reportRows.length === 0
              ? "bg-slate-100 text-slate-400 cursor-not-allowed"
              : "bg-emerald-600 hover:bg-emerald-700 text-white"
          }`}
        >
          {isExporting ? (
            <>
              <RefreshCw className="animate-spin" size={13} />
              Mengekspor ke Excel...
            </>
          ) : (
            <>
              <DownloadCloud size={13} />
              Ekspor Rekap Laporan (.xlsx)
            </>
          )}
        </button>
      </div>

      {exportSuccess && (
        <div className="bg-emerald-50 border border-emerald-150 text-emerald-800 rounded p-3.5 mb-6 text-xs font-semibold animate-fade-in flex items-center gap-2">
          <Check size={14} className="text-emerald-600" />
          Ekspor Berhasil! File <strong className="font-extrabold text-emerald-950">SIPERBANG_REKAP_KUITANSI.xlsx</strong> berhasil diunduh ke komputer Anda.
        </div>
      )}

      {/* Filters bar */}
      <div className="bg-slate-50 rounded p-4 mb-6 border border-slate-200 space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {/* Month filter */}
          <div>
            <label className="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">
              Saring Bulan
            </label>
            <select
              disabled={isAnnualRecap}
              value={filterMonth}
              onChange={(e) => setFilterMonth(e.target.value)}
              className="w-full bg-white border border-slate-200 rounded px-2.5 py-1.5 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-slate-100 disabled:text-slate-400"
            >
              {months.map((m) => (
                <option key={m.value} value={m.value}>
                  {m.label}
                </option>
              ))}
            </select>
          </div>

          {/* Year Filter */}
          <div>
            <label className="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">
              Saring Tahun
            </label>
            <select
              value={filterYear}
              onChange={(e) => setFilterYear(e.target.value)}
              className="w-full bg-white border border-slate-200 rounded px-2.5 py-1.5 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="2026">2026</option>
              <option value="All">Semua Tahun</option>
            </select>
          </div>

          {/* Search */}
          <div className="md:col-span-2">
            <label className="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">
              Cari Kuitansi / Barang
            </label>
            <div className="relative">
              <Search className="absolute left-3 top-2 text-slate-400" size={13} />
              <input
                type="text"
                placeholder="Cari toko, no invoice, nama barang..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full bg-white border border-slate-200 rounded pl-9 pr-4 py-1.5 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>
        </div>

        {/* PRD Rule 17 Trigger: Annual Recap overrides */}
        <div className="flex items-center justify-between border-t border-slate-200/60 pt-3 flex-wrap gap-2">
          <label className="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer select-none">
            <input
              type="checkbox"
              checked={isAnnualRecap}
              onChange={(e) => {
                setIsAnnualRecap(e.target.checked);
                if (e.target.checked) setFilterMonth("All");
              }}
              className="rounded-sm accent-indigo-600 h-3.5 w-3.5"
            />
            Aktifkan Rekap Tahunan (Kosongkan Kolom BAST & Tanggal Buku)
          </label>
          <span className="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-2.5 py-1 rounded border border-indigo-100 italic">
            * Rekap tahunan akan secara otomatis mengosongkan kolom BAST dan tanggal buku sesuai ketentuan Section 4.11
          </span>
        </div>
      </div>

      {/* Spreadsheet Preview */}
      <div>
        <div className="flex justify-between items-center mb-3">
          <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
            Pratinjau Spreadsheet Excel: {reportRows.length} Baris Data Terpilih
          </span>
        </div>

        <div className="overflow-x-auto border border-slate-200 rounded">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50 text-slate-700 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                <th className="px-4 py-3">No Nota</th>
                <th className="px-4 py-3">Tanggal</th>
                <th className="px-4 py-3">Nama Toko</th>
                <th className="px-4 py-3">Nama Barang</th>
                <th className="px-4 py-3 text-center">Jumlah</th>
                <th className="px-4 py-3 text-right">Harga Satuan</th>
                <th className="px-4 py-3 text-right">Subtotal</th>
                <th className="px-4 py-3 text-right">PPN (Pajak)</th>
                <th className="px-4 py-3 text-right">Total</th>
                <th className="px-4 py-3">BAST (Nama)</th>
                <th className="px-4 py-3">BAST (Tgl)</th>
                <th className="px-4 py-3">Tgl Buku</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {reportRows.map((row, idx) => (
                <tr key={idx} className="hover:bg-slate-50/50 transition-colors text-xs font-mono">
                  <td className="px-4 py-3 text-slate-600 font-bold">{row.invoiceNo}</td>
                  <td className="px-4 py-3 text-slate-500 font-sans">{row.date}</td>
                  <td className="px-4 py-3 font-sans font-bold text-slate-800">{row.storeName}</td>
                  <td className="px-4 py-3 font-sans font-medium text-slate-700">{row.itemName}</td>
                  <td className="px-4 py-3 text-center font-sans font-bold text-slate-800">{row.qty}</td>
                  <td className="px-4 py-3 text-right text-slate-600">{formatIDR(row.price)}</td>
                  <td className="px-4 py-3 text-right text-slate-700 font-semibold">{formatIDR(row.subtotal)}</td>
                  <td className="px-4 py-3 text-right text-indigo-700 font-semibold">
                    {row.taxAmount > 0 ? formatIDR(row.taxAmount) : "-"}
                  </td>
                  <td className="px-4 py-3 text-right text-slate-900 font-extrabold">{formatIDR(row.total)}</td>
                  <td className="px-4 py-3 font-sans text-slate-400">
                    {row.bastName ? (
                      <span className="text-slate-600 font-semibold">{row.bastName}</span>
                    ) : (
                      <span className="text-slate-300 italic">NIL (Kosong)</span>
                    )}
                  </td>
                  <td className="px-4 py-3 font-sans text-slate-400">
                    {row.bastDate ? (
                      <span className="text-slate-600 font-semibold">{row.bastDate}</span>
                    ) : (
                      <span className="text-slate-300 italic">NIL (Kosong)</span>
                    )}
                  </td>
                  <td className="px-4 py-3 font-sans text-slate-400">
                    {row.bookDate ? (
                      <span className="text-slate-600 font-semibold">{row.bookDate}</span>
                    ) : (
                      <span className="text-slate-300 italic">NIL (Kosong)</span>
                    )}
                  </td>
                </tr>
              ))}
              {reportRows.length === 0 && (
                <tr>
                  <td colSpan={12} className="text-center py-12 text-slate-400 font-sans text-xs">
                    Tidak ada data kuitansi tervalidasi yang cocok dengan kriteria saringan Anda.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
      {alertMsg && (
        <AlertDialog
          open
          title={alertMsg.title}
          message={alertMsg.message}
          variant="danger"
          onClose={() => setAlertMsg(null)}
        />
      )}
    </>
  );
};
