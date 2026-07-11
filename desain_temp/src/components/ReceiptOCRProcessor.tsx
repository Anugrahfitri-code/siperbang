import React, { useState } from "react";
import { ReceiptData, ReceiptItem, ProcurementMethod, RequestStatus, ItemRequest } from "../types";
import { SAMPLE_RECEIPTS, SampleReceiptPayload } from "../data";
import { FileDown, UploadCloud, FileText, CheckCircle, RefreshCw, Plus, Trash2, Edit3, Settings, Calculator, Percent, Sparkles, Receipt } from "lucide-react";

interface ReceiptOCRProcessorProps {
  receipts: ReceiptData[];
  requests: ItemRequest[];
  onAddReceipt: (newReceipt: ReceiptData) => void;
  onVerifyReceipt: (id: string, updatedReceipt: ReceiptData, logMsg: string) => void;
}

export const ReceiptOCRProcessor: React.FC<ReceiptOCRProcessorProps> = ({
  receipts,
  requests,
  onAddReceipt,
  onVerifyReceipt,
}) => {
  const [isScanning, setIsScanning] = useState(false);
  const [activeDraft, setActiveDraft] = useState<ReceiptData | null>(null);
  const [activeTab, setActiveTab] = useState<"pending" | "valid">("pending");

  // Local state for the verification form
  const [storeName, setStoreName] = useState("");
  const [invoiceNo, setInvoiceNo] = useState("");
  const [date, setDate] = useState("");
  const [isTaxed, setIsTaxed] = useState(true);
  const [taxRate, setTaxRate] = useState<number>(11);
  const [method, setMethod] = useState<ProcurementMethod>(ProcurementMethod.SENDIRI);
  const [items, setItems] = useState<ReceiptItem[]>([]);
  const [bastName, setBastName] = useState("");
  const [bastDate, setBastDate] = useState("");

  const handleTriggerOCR = (payload: SampleReceiptPayload) => {
    setIsScanning(true);
    setActiveDraft(null);

    // Simulate AI parsing using Gemini 3.5 Flash OCR engine
    setTimeout(() => {
      setIsScanning(false);
      const subtotal = payload.items.reduce((sum, item) => sum + item.qty * item.price, 0);
      const taxAmount = payload.isTaxed ? Math.round(subtotal * (payload.taxRate / 100)) : 0;
      const total = subtotal + taxAmount;

      const newDraft: ReceiptData = {
        id: "rc-draft-" + Math.random().toString(36).substring(2, 9),
        invoiceNo: payload.invoiceNo,
        storeName: payload.storeName,
        date: payload.date,
        isTaxed: payload.isTaxed,
        taxRate: payload.taxRate,
        subtotal,
        taxAmount,
        total,
        isVerified: false,
        status: "Menunggu Verifikasi",
        method: payload.method,
        items: payload.items.map((it, index) => ({
          id: "it-draft-" + index,
          name: it.name,
          qty: it.qty,
          price: it.price,
          subtotal: it.qty * it.price,
        })),
        bastName: payload.storeName,
        bastDate: payload.date,
      };

      // Set form states
      setActiveDraft(newDraft);
      setStoreName(newDraft.storeName);
      setInvoiceNo(newDraft.invoiceNo);
      setDate(newDraft.date);
      setIsTaxed(newDraft.isTaxed);
      setTaxRate(newDraft.taxRate);
      setMethod(newDraft.method);
      setItems(newDraft.items);
      setBastName(newDraft.bastName || "");
      setBastDate(newDraft.bastDate || "");
    }, 1800);
  };

  const handleAddItem = () => {
    const newItem: ReceiptItem = {
      id: "it-draft-" + Date.now(),
      name: "",
      qty: 1,
      price: 0,
      subtotal: 0,
    };
    setItems([...items, newItem]);
  };

  const handleUpdateItem = (id: string, field: keyof ReceiptItem, val: any) => {
    setItems(
      items.map((it) => {
        if (it.id === id) {
          const updated = { ...it, [field]: val };
          if (field === "qty" || field === "price") {
            updated.subtotal = Number(updated.qty) * Number(updated.price);
          }
          return updated;
        }
        return it;
      })
    );
  };

  const handleRemoveItem = (id: string) => {
    setItems(items.filter((it) => it.id !== id));
  };

  // Live Calculations based on input fields
  const calculatedSubtotal = items.reduce((sum, item) => sum + (item.qty * item.price || 0), 0);
  const calculatedTaxRate = isTaxed ? taxRate : 0;
  const calculatedTaxAmount = Math.round(calculatedSubtotal * (calculatedTaxRate / 100));
  const calculatedTotal = calculatedSubtotal + calculatedTaxAmount;

  const handleVerifySave = () => {
    if (!activeDraft) return;

    const finalReceipt: ReceiptData = {
      ...activeDraft,
      storeName,
      invoiceNo,
      date,
      isTaxed,
      taxRate: isTaxed ? Number(taxRate) : 0,
      subtotal: calculatedSubtotal,
      taxAmount: calculatedTaxAmount,
      total: calculatedTotal,
      isVerified: true,
      status: "Dokumen Valid",
      items,
      method,
      bastName,
      bastDate,
    };

    const logMsg = `Verifikasi Kuitansi: Petugas memverifikasi kuitansi nomor ${invoiceNo} dari ${storeName}. Total belanja ${formatIDR(calculatedTotal)} dengan pajak PPN ${isTaxed ? `${taxRate}%` : "0% (Bebas Pajak)"}. BAST tercatat atas nama ${bastName || "-"} tanggal ${bastDate || "-"}.`;

    onVerifyReceipt(activeDraft.id, finalReceipt, logMsg);
    setActiveDraft(null);
  };

  const formatIDR = (num: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(num);
  };

  return (
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6 border-b border-slate-100 pb-5">
        <div className="flex items-center gap-3">
          <div className="bg-indigo-50 text-indigo-600 p-2.5 rounded border border-indigo-150">
            <Receipt size={18} />
          </div>
          <div>
            <h2 className="text-base font-extrabold text-slate-800 tracking-tight">Pembacaan Kuitansi Otomatis (OCR)</h2>
            <p className="text-[11px] text-slate-500">
              Unggah struk belanja, baca otomatis dengan AI, verifikasi manual, sesuaikan pajak toko
            </p>
          </div>
        </div>

        {/* View Tabs */}
        <div className="flex bg-slate-100 border border-slate-200 rounded p-0.5 self-start sm:self-auto">
          <button
            onClick={() => setActiveTab("pending")}
            className={`px-3 py-1 rounded text-xs font-bold transition-all ${
              activeTab === "pending"
                ? "bg-white text-slate-800 shadow-xs"
                : "text-slate-500 hover:text-slate-800"
            }`}
          >
            Menunggu Verifikasi ({receipts.filter((r) => !r.isVerified).length})
          </button>
          <button
            onClick={() => setActiveTab("valid")}
            className={`px-3 py-1 rounded text-xs font-bold transition-all ${
              activeTab === "valid"
                ? "bg-white text-slate-800 shadow-xs"
                : "text-slate-500 hover:text-slate-800"
            }`}
          >
            Kuitansi Valid ({receipts.filter((r) => r.isVerified).length})
          </button>
        </div>
      </div>

      {/* Simulator Quick OCR Panel */}
      <div className="bg-slate-50 rounded p-4 mb-6 border border-slate-200">
        <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2.5">
          Simulasikan Pengunggahan & Pembacaan OCR (Pilih Struk di Bawah):
        </span>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {SAMPLE_RECEIPTS.map((sample, idx) => (
            <button
              key={idx}
              onClick={() => handleTriggerOCR(sample)}
              className="bg-white hover:bg-slate-50 border border-slate-200 rounded p-3 text-left transition-all hover:border-indigo-500 group"
            >
              <div className="flex items-center gap-2 mb-2">
                <div className="bg-indigo-50 text-indigo-700 p-2 rounded border border-indigo-100 transition-all">
                  <FileText size={14} />
                </div>
                <span className="text-xs font-bold text-slate-850 line-clamp-1">{sample.name}</span>
              </div>
              <p className="text-[11px] text-slate-400 line-clamp-2 leading-relaxed mb-3 font-sans">
                {sample.description}
              </p>
              <div className="flex justify-between items-center border-t border-slate-100 pt-2 text-[10px] font-bold text-indigo-600">
                <span>Format: {sample.method}</span>
                <span className="bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">Pajak: {sample.isTaxed ? `${sample.taxRate}%` : "Bebas"}</span>
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* File Drag Drop fallback */}
      <div className="border border-dashed border-slate-200 rounded p-6 text-center hover:bg-slate-50/50 cursor-pointer mb-6" onClick={() => handleTriggerOCR(SAMPLE_RECEIPTS[0])}>
        <UploadCloud size={24} className="text-indigo-600 mx-auto mb-2" />
        <h4 className="text-xs font-bold text-slate-700">Atau Unggah File Kuitansi / Foto Struk Baru</h4>
        <p className="text-[10px] text-slate-400 mt-1">Dukung format JPG, PNG, PDF. Sistem akan membaca detail kuitansi secara otomatis.</p>
      </div>

      {/* OCR Scanner Loading Animation */}
      {isScanning && (
        <div className="flex flex-col items-center py-10 bg-slate-50 rounded border border-slate-200 mb-6">
          <RefreshCw className="animate-spin text-indigo-600 mb-3" size={24} />
          <h3 className="text-xs font-bold text-slate-800">Mesin OCR Gemini Sedang Membaca Dokumen...</h3>
          <p className="text-[11px] text-slate-400 max-w-sm text-center mt-1 leading-relaxed">
            Mengekstrak nama toko, nomor kuitansi, daftar barang belanjaan, subtotal, dan mendeteksi persentase PPN...
          </p>
        </div>
      )}

      {/* Double Column Verification Workspace */}
      {activeDraft && (
        <div className="grid grid-cols-1 xl:grid-cols-12 gap-6 mb-8 border-t border-slate-200 pt-6">
          {/* LEFT: Live Receipt Visual Preview */}
          <div className="xl:col-span-5 bg-slate-50 border border-slate-200 rounded p-4 self-start">
            <div className="bg-white border border-slate-200 rounded p-4 shadow-xs font-mono text-xs text-slate-700 space-y-4">
              <div className="text-center border-b border-dashed border-slate-200 pb-4">
                <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">{storeName || "NAMA TOKO"}</h4>
                <p className="text-[10px] text-slate-400 mt-0.5">Makassar, Sulawesi Selatan</p>
              </div>

              <div className="space-y-1">
                <div className="flex justify-between">
                  <span>No Nota:</span>
                  <span className="font-bold text-slate-800">{invoiceNo || "-"}</span>
                </div>
                <div className="flex justify-between">
                  <span>Tanggal:</span>
                  <span className="font-bold text-slate-800">{date || "-"}</span>
                </div>
                <div className="flex justify-between">
                  <span>Metode:</span>
                  <span className="font-bold text-slate-800">{method}</span>
                </div>
              </div>

              <div className="border-b border-dashed border-slate-200" />

              {/* Items List */}
              <div className="space-y-2">
                {items.map((it, idx) => (
                  <div key={idx} className="flex justify-between items-start">
                    <div className="max-w-[60%]">
                      <p className="font-bold text-slate-850">{it.name || "Nama Barang"}</p>
                      <p className="text-[10px] text-slate-450">
                        {it.qty} x {formatIDR(it.price)}
                      </p>
                    </div>
                    <span className="font-bold text-slate-850">{formatIDR(it.qty * it.price || 0)}</span>
                  </div>
                ))}
              </div>

              <div className="border-b border-dashed border-slate-200" />

              {/* Financial calculations */}
              <div className="space-y-1">
                <div className="flex justify-between">
                  <span>Subtotal:</span>
                  <span>{formatIDR(calculatedSubtotal)}</span>
                </div>
                <div className="flex justify-between text-indigo-700 font-bold">
                  <span>PPN ({calculatedTaxRate}%):</span>
                  <span>+ {formatIDR(calculatedTaxAmount)}</span>
                </div>
                <div className="flex justify-between text-xs font-extrabold text-slate-950 border-t border-dashed border-slate-200 pt-2">
                  <span>TOTAL:</span>
                  <span>{formatIDR(calculatedTotal)}</span>
                </div>
              </div>

              <div className="text-center bg-indigo-50 text-indigo-700 p-2 rounded border border-indigo-150 font-sans text-[10px] font-bold uppercase tracking-wider">
                DRAFT PEMBACAAN OCR GEMINI AI
              </div>
            </div>
          </div>

          {/* RIGHT: Manual Override Form (Double Check) */}
          <div className="xl:col-span-7 bg-white border border-slate-200 rounded p-5 shadow-xs space-y-5">
            <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
              <Calculator size={16} className="text-indigo-600" />
              <h3 className="text-xs font-bold text-slate-700 uppercase tracking-wider">Workspace Verifikasi Manual & Penyesuaian Pajak</h3>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {/* Nama Toko */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Nama Toko / Penyedia
                </label>
                <input
                  type="text"
                  value={storeName}
                  onChange={(e) => setStoreName(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
              </div>

              {/* No Nota */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Nomor Nota / Invoice
                </label>
                <input
                  type="text"
                  value={invoiceNo}
                  onChange={(e) => setInvoiceNo(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
              </div>

              {/* Tanggal */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Tanggal Kuitansi
                </label>
                <input
                  type="date"
                  value={date}
                  onChange={(e) => setDate(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
              </div>

              {/* Metode Pengadaan */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Metode Pengadaan
                </label>
                <select
                  value={method}
                  onChange={(e) => setMethod(e.target.value as ProcurementMethod)}
                  className="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                  <option value={ProcurementMethod.SENDIRI}>{ProcurementMethod.SENDIRI}</option>
                  <option value={ProcurementMethod.VENDOR}>{ProcurementMethod.VENDOR}</option>
                </select>
              </div>
            </div>

            {/* TAX CONTROLS: adjustable store taxes */}
            <div className="bg-slate-50 rounded p-4 border border-slate-200">
              <span className="text-xs font-extrabold text-slate-700 uppercase tracking-wider block mb-2.5">Penyesuaian Pajak Kuitansi (PPN)</span>
              <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <div className="flex gap-4">
                  <label className="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                    <input
                      type="radio"
                      checked={isTaxed}
                      onChange={() => setIsTaxed(true)}
                      className="accent-indigo-600"
                    />
                    Kuitansi dengan Pajak (PPN)
                  </label>
                  <label className="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                    <input
                      type="radio"
                      checked={!isTaxed}
                      onChange={() => setIsTaxed(false)}
                      className="accent-indigo-600"
                    />
                    Bebas Pajak (Tanpa PPN)
                  </label>
                </div>

                {isTaxed && (
                  <div className="flex items-center gap-2">
                    <span className="text-xs text-slate-500 font-semibold">Tarif Pajak Toko:</span>
                    <div className="relative flex items-center">
                      <input
                        type="number"
                        min="0"
                        max="100"
                        value={taxRate}
                        onChange={(e) => setTaxRate(Math.max(0, parseInt(e.target.value) || 0))}
                        className="w-16 bg-white border border-slate-200 rounded px-2.5 py-1 text-xs font-bold text-slate-800 text-center focus:outline-none focus:ring-1 focus:ring-indigo-500"
                      />
                      <Percent size={11} className="absolute right-1.5 text-slate-400" />
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Editable Items Table */}
            <div>
              <div className="flex justify-between items-center mb-2">
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Item Barang Belanja</span>
                <button
                  type="button"
                  onClick={handleAddItem}
                  className="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 bg-indigo-50 px-2.5 py-1 rounded border border-indigo-150"
                >
                  <Plus size={11} />
                  Tambah Barang
                </button>
              </div>

              <div className="overflow-x-auto border border-slate-200 rounded max-h-[250px] overflow-y-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-slate-50 text-slate-600 text-[9px] font-bold uppercase tracking-wider border-b border-slate-200">
                      <th className="px-3 py-2">Nama Barang</th>
                      <th className="px-3 py-2 w-16 text-center">Jumlah</th>
                      <th className="px-3 py-2 w-24">Harga Satuan (Rp)</th>
                      <th className="px-3 py-2 w-24 text-right">Subtotal</th>
                      <th className="px-3 py-2 w-10 text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {items.map((it) => (
                      <tr key={it.id} className="hover:bg-slate-50/50 transition-colors">
                        <td className="px-3 py-1.5">
                          <input
                            type="text"
                            value={it.name}
                            onChange={(e) => handleUpdateItem(it.id, "name", e.target.value)}
                            placeholder="Nama item barang..."
                            className="w-full bg-white border border-slate-200 rounded px-2 py-1 text-xs text-slate-800 font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500"
                          />
                        </td>
                        <td className="px-3 py-1.5">
                          <input
                            type="number"
                            min="1"
                            value={it.qty}
                            onChange={(e) => handleUpdateItem(it.id, "qty", parseInt(e.target.value) || 1)}
                            className="w-full bg-white border border-slate-200 rounded px-2 py-1 text-xs text-center text-slate-800 font-bold focus:outline-none"
                          />
                        </td>
                        <td className="px-3 py-1.5">
                          <input
                            type="number"
                            min="0"
                            value={it.price}
                            onChange={(e) => handleUpdateItem(it.id, "price", parseInt(e.target.value) || 0)}
                            className="w-full bg-white border border-slate-200 rounded px-2 py-1 text-xs text-slate-800 font-semibold focus:outline-none"
                          />
                        </td>
                        <td className="px-3 py-1.5 text-right text-xs font-bold text-slate-700">
                          {formatIDR(it.qty * it.price)}
                        </td>
                        <td className="px-3 py-1.5 text-center">
                          <button
                            type="button"
                            onClick={() => handleRemoveItem(it.id)}
                            className="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1 rounded"
                          >
                            <Trash2 size={12} />
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>

            {/* BAST input: as requested by the PRD */}
            <div className="bg-slate-50 border border-slate-200 rounded p-4 space-y-3">
              <span className="text-xs font-extrabold text-slate-700 uppercase tracking-wider block border-b border-slate-200 pb-1.5">
                Pencatatan Dokumen BAST (Berita Acara Serah Terima)
              </span>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Nama Toko / Mitra BAST
                  </label>
                  <input
                    type="text"
                    value={bastName}
                    onChange={(e) => setBastName(e.target.value)}
                    placeholder="Nama toko..."
                    className="w-full bg-white border border-slate-200 rounded px-3 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  />
                </div>
                <div>
                  <label className="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Tanggal BAST
                  </label>
                  <input
                    type="date"
                    value={bastDate}
                    onChange={(e) => setBastDate(e.target.value)}
                    className="w-full bg-white border border-slate-200 rounded px-3 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  />
                </div>
              </div>
              <p className="text-[10px] text-slate-450 leading-relaxed italic">
                * Sesuai ketentuan PRD, dokumen BAST cukup memuat nama toko/penyedia dan tanggal serah terima. Detail transaksi dan nilai barang akan mengacu sepenuhnya pada data kuitansi kuitansi_detail (Section 4.9).
              </p>
            </div>

            {/* Form Actions */}
            <div className="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setActiveDraft(null)}
                className="px-3.5 py-2 rounded text-xs font-bold text-slate-500 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-all"
              >
                Tolak Draft
              </button>
              <button
                type="button"
                onClick={handleVerifySave}
                className="px-4 py-2 rounded text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-all shadow-xs flex items-center gap-1.5"
              >
                <CheckCircle size={13} />
                Selesaikan & Verifikasi Dokumen
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Verified / Historical Receipts List */}
      <div className="mt-6">
        <div className="flex justify-between items-center mb-3">
          <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
            {activeTab === "pending" ? "Daftar Dokumen Masuk Menunggu Verifikasi" : "Daftar Kuitansi Valid Terverifikasi"}
          </span>
        </div>

        <div className="overflow-x-auto border border-slate-200 rounded">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50 text-slate-600 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                <th className="px-5 py-3">No Nota / Invoice</th>
                <th className="px-5 py-3">Nama Toko</th>
                <th className="px-5 py-3">Tanggal Belanja</th>
                <th className="px-5 py-3">Metode Pengadaan</th>
                <th className="px-5 py-3 text-center">Tarif Pajak PPN</th>
                <th className="px-5 py-3 text-right">Total Nilai</th>
                <th className="px-5 py-3 text-center">Status Verifikasi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {receipts
                .filter((r) => (activeTab === "pending" ? !r.isVerified : r.isVerified))
                .map((rc) => (
                  <tr key={rc.id} className="hover:bg-slate-50/50 transition-colors text-xs font-mono">
                    <td className="px-5 py-3 font-semibold text-slate-700">
                      {rc.invoiceNo}
                    </td>
                    <td className="px-5 py-3 font-bold text-slate-800 font-sans">
                      {rc.storeName}
                    </td>
                    <td className="px-5 py-3 text-slate-500 font-sans">
                      {rc.date}
                    </td>
                    <td className="px-5 py-3 font-semibold text-slate-600 font-sans">
                      {rc.method}
                    </td>
                    <td className="px-5 py-3 text-center font-bold text-indigo-600">
                      {rc.isTaxed ? `${rc.taxRate}%` : "0% (Bebas)"}
                    </td>
                    <td className="px-5 py-3 text-right font-bold text-slate-800 font-sans">
                      {formatIDR(rc.total)}
                    </td>
                    <td className="px-5 py-3 text-center font-sans">
                      <span
                        className={`inline-flex items-center gap-1 text-[10px] px-2.5 py-0.5 rounded border font-bold ${
                          rc.isVerified
                            ? "bg-emerald-50 text-emerald-800 border-emerald-100"
                            : "bg-amber-50 text-amber-800 border-amber-100"
                        }`}
                      >
                        {rc.isVerified ? "Dokumen Valid" : "Menunggu Verifikasi"}
                      </span>
                    </td>
                  </tr>
                ))}
              {receipts.filter((r) => (activeTab === "pending" ? !r.isVerified : r.isVerified)).length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-slate-400 text-xs font-medium font-sans">
                    Tidak ada kuitansi dalam daftar ini.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
