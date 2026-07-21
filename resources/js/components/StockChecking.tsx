import React, { useState, useMemo } from "react";
import { ItemRequest, RequestStatus, StockItem } from "../types";
import { SearchCode, CheckCircle, AlertTriangle, Play, HelpCircle, Package, ArrowRight, ShieldAlert, Truck, ShoppingCart, XCircle, Loader2, Search, Trash2, User } from "lucide-react";
import { DistributionProcurement } from "./DistributionProcurement";

interface StockCheckingProps {
  requests: ItemRequest[];
  stockList: StockItem[];
  onUpdateStatus: (
    reqId: string,
    status: RequestStatus,
    qtyAvailable: number,
    qtyFulfilled: number,
    logMessage: string,
    deductStock?: { code: string; qtyToDeduct: number }
  ) => void;
  onDistribute: (reqId: string, data: {
    stockItemId: string;
    qtyDistributed: number;
    distributedBy: string;
    notes?: string;
  }) => Promise<void>;
  onProcure: (reqId: string, data: {
    method: "Pengadaan Vendor" | "Pengadaan Sendiri (Toko)";
    vendorName?: string;
    storeName?: string;
    qtyProcured: number;
    unitPrice: number;
    isTaxed: boolean;
    taxRate: number;
    invoiceNo?: string;
    bastName?: string;
    bastDate?: string;
    contractNo?: string;
    processedBy: string;
  }) => Promise<void>;
  onCompleteProcurement: (reqId: string, procurementId: string, processedBy: string) => Promise<void>;
  /** Batalkan/tolak satu item request — berlaku untuk status apapun */
  onReject: (reqId: string, alasan: string) => Promise<void>;
  currentUser: string;
}

export const StockChecking: React.FC<StockCheckingProps> = ({
  requests,
  stockList,
  onUpdateStatus,
  onDistribute,
  onProcure,
  onCompleteProcurement,
  onReject,
  currentUser,
}) => {
  const [selectedRequest,   setSelectedRequest]   = useState<ItemRequest | null>(null);
  const [selectedForAction, setSelectedForAction] = useState<ItemRequest | null>(null);
  const [qtyAvailable,      setQtyAvailable]      = useState<number>(0);
  const [qtyFulfilled,      setQtyFulfilled]      = useState<number>(0);
  // Barang stok yang dipilih manual oleh Petugas saat cek stok
  const [selectedStockItem, setSelectedStockItem] = useState<StockItem | null>(null);
  const [stockSearchQuery,  setStockSearchQuery]  = useState("");
  const [showStockDropdown, setShowStockDropdown] = useState(false);

  // ── Batalkan state ────────────────────────────────────────────
  const [rejectTarget,  setRejectTarget]  = useState<ItemRequest | null>(null);
  const [rejectAlasan,  setRejectAlasan]  = useState("");
  const [rejectLoading, setRejectLoading] = useState(false);
  const [rejectError,   setRejectError]   = useState<string | null>(null);

  /** Cari barang yang paling mirip berdasarkan kata kunci (fuzzy keyword) */
  const findBestMatch = (itemName: string): StockItem | null => {
    const needle = itemName.toLowerCase();
    // Exact match dulu
    const exact = stockList.find((s) => s.name.toLowerCase() === needle);
    if (exact) return exact;
    // Tokenised partial match: cari barang yang mengandung paling banyak kata dari nama pengajuan
    const tokens = needle.split(/\s+/).filter((t) => t.length > 2);
    let bestScore = 0;
    let bestItem: StockItem | null = null;
    for (const s of stockList) {
      const haystack = s.name.toLowerCase();
      const score = tokens.filter((t) => haystack.includes(t)).length;
      if (score > bestScore) {
        bestScore = score;
        bestItem = s;
      }
    }
    return bestScore > 0 ? bestItem : null;
  };

  const openChecker = (req: ItemRequest) => {
    // Cari otomatis dengan fuzzy match
    const match = findBestMatch(req.itemName);
    const available = match ? match.qty : 0;

    setSelectedRequest(req);
    setSelectedStockItem(match);
    setQtyAvailable(available);
    setQtyFulfilled(Math.min(req.qtyRequested, available));
    setStockSearchQuery(match ? match.name : "");
    setShowStockDropdown(false);
  };

  const handleConfirmCheck = () => {
    if (!selectedRequest) return;

    const requested = selectedRequest.qtyRequested;
    const fulfilled = Number(qtyFulfilled);
    const unfulfilled = requested - fulfilled;

    let finalStatus: RequestStatus;
    let logMessage = "";
    let stockItemToDeduct: { code: string; qtyToDeduct: number } | undefined;

    // Gunakan barang yang dipilih manual (sudah di-set di selectedStockItem)
    const stockItem = selectedStockItem;

    if (fulfilled === requested) {
      finalStatus = RequestStatus.TERPENUHI;
      logMessage = `Pengecekan Stok: Seluruh barang tersedia (${fulfilled} ${selectedRequest.unit}). Dialokasikan untuk didistribusikan.`;
      if (stockItem) {
        stockItemToDeduct = { code: stockItem.code, qtyToDeduct: fulfilled };
      }
    } else if (fulfilled > 0 && fulfilled < requested) {
      finalStatus = RequestStatus.TERPENUHI_SEBAGIAN;
      logMessage = `Pengecekan Stok: Hanya tersedia ${fulfilled} dari ${requested} ${selectedRequest.unit}. Sisanya ${unfulfilled} ${selectedRequest.unit} diteruskan ke proses pengadaan.`;
      if (stockItem) {
        stockItemToDeduct = { code: stockItem.code, qtyToDeduct: fulfilled };
      }
    } else {
      finalStatus = RequestStatus.PERLU_PENGADAAN;
      logMessage = `Pengecekan Stok: Stok kosong (0/${requested} ${selectedRequest.unit}). Pengajuan diteruskan seluruhnya ke proses pengadaan barang.`;
    }

    onUpdateStatus(
      selectedRequest.id,
      finalStatus,
      qtyAvailable,
      fulfilled,
      logMessage,
      stockItemToDeduct
    );

    setSelectedRequest(null);
    setSelectedStockItem(null);
    setStockSearchQuery("");
  };

  const getStatusColor = (status: RequestStatus) => {
    switch (status) {
      case RequestStatus.DIAJUKAN:
        return "bg-amber-50 text-amber-800 border-amber-200";
      case RequestStatus.DICEK:
        return "bg-sky-50 text-sky-800 border-sky-200";
      case RequestStatus.TERPENUHI:
        return "bg-emerald-50 text-emerald-800 border-emerald-200";
      case RequestStatus.TERPENUHI_SEBAGIAN:
        return "bg-amber-50 text-amber-700 border-amber-200";
      case RequestStatus.SIAP_DIDISTRIBUSIKAN:
        return "bg-blue-50 text-blue-800 border-blue-200";
      case RequestStatus.PERLU_PENGADAAN:
        return "bg-rose-50 text-rose-800 border-rose-200";
      case RequestStatus.DALAM_PENGADAAN:
        return "bg-purple-50 text-purple-800 border-purple-200";
      case RequestStatus.SELESAI:
        return "bg-emerald-100 text-emerald-800 border-emerald-200";
      case RequestStatus.DITOLAK:
        return "bg-gray-100 text-gray-800 border-gray-200";
    }
  };

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
      <div className="flex items-center gap-3 mb-6">
        <div className="flex size-14 shrink-0 items-center justify-center rounded-xl border bg-amber-50 text-amber-600 border-amber-100">
          <Package size={24} />
        </div>
        <div>
          <h2 className="text-lg font-semibold leading-7 text-slate-900">Pengecekan Stok & Pemenuhan</h2>
          <p className="text-sm font-normal leading-5 text-slate-500 mt-0.5">
            Periksa ketersediaan barang persediaan, alokasikan barang, atau teruskan ke pengadaan
          </p>
        </div>
      </div>

      {/* Requests List awaiting check */}
      <div className="space-y-4">
        {requests.length === 0 ? (
          <div className="text-center py-10 bg-slate-50 rounded border border-slate-200">
            <CheckCircle className="text-emerald-500 mx-auto mb-2" size={24} />
            <p className="text-xs font-bold text-slate-700">Tidak ada pengajuan aktif saat ini</p>
          </div>
        ) : (
          requests.map((req) => {
            const isPendingCheck = req.status === RequestStatus.DIAJUKAN;
            const stockItem = stockList.find(
              (s) => s.name.toLowerCase() === req.itemName.toLowerCase()
            );
            const stockQty = stockItem ? stockItem.qty : 0;

            return (
              <div
                key={req.id}
                className={`border bg-white rounded-lg p-5 transition-all mb-4 shadow-xs ${
                  isPendingCheck
                    ? "border-amber-200 border-l-[4px] border-l-amber-400 hover:border-amber-300"
                    : "border-slate-200 border-l-[4px] border-l-slate-300 hover:border-slate-300"
                }`}
              >
                <div className="flex flex-col lg:flex-row justify-between lg:items-start gap-4">
                  <div>
                    <div className="flex items-center gap-2 flex-wrap mb-1.5">
                      <span className="text-xs font-mono font-bold text-slate-500 uppercase tracking-wider">
                        {req.bonNo}
                      </span>
                      <span className="text-slate-300">•</span>
                      <span className="text-xs font-mono font-bold text-slate-500">
                        {req.date}
                      </span>
                      <span className="text-slate-300">•</span>
                      <span className="text-xs font-bold text-slate-700">
                        {req.section}
                      </span>
                    </div>

                    <div className="flex items-center">
                      <h3 className="text-lg font-extrabold text-slate-800">
                        {req.itemName}
                      </h3>
                      <span className="ml-3 px-2 py-0.5 rounded border border-slate-200 text-slate-600 text-xs font-semibold bg-slate-50">
                        {req.qtyRequested} {req.unit}
                      </span>
                    </div>

                    {req.notes && (
                      <p className="text-xs text-slate-500 italic mt-1.5 font-sans">
                        &ldquo;{req.notes}&rdquo;
                      </p>
                    )}
                  </div>

                  <div className="flex items-center gap-3 justify-end flex-wrap">
                    {/* ── Tombol Batalkan — tampil untuk semua status kecuali Ditolak & Selesai ── */}
                    {req.status !== RequestStatus.DITOLAK && req.status !== RequestStatus.SELESAI && (
                      <button
                        onClick={() => {
                          setRejectTarget(req);
                          setRejectAlasan("");
                          setRejectError(null);
                        }}
                        className="border border-rose-300 text-rose-600 hover:bg-rose-50 text-sm font-bold px-4 py-2 rounded-md transition-all flex items-center gap-1.5 shadow-sm"
                      >
                        <Trash2 size={14} />
                        Batalkan
                      </button>
                    )}

                    {isPendingCheck ? (
                      <button
                        onClick={() => openChecker(req)}
                        className="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-2 rounded-md transition-all flex items-center gap-1.5 shadow-sm"
                      >
                        <Play size={14} />
                        Proses Cek Stok
                      </button>
                    ) : (
                      <>
                        {(req.status === RequestStatus.TERPENUHI ||
                          req.status === RequestStatus.TERPENUHI_SEBAGIAN ||
                          req.status === RequestStatus.SIAP_DIDISTRIBUSIKAN ||
                          req.status === RequestStatus.PERLU_PENGADAAN ||
                          req.status === RequestStatus.DALAM_PENGADAAN) && (
                          <button
                            onClick={() => setSelectedForAction(req)}
                            className="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-4 py-2 rounded-md transition-all flex items-center gap-1.5 shadow-sm"
                          >
                            {req.qtyFulfilled > 0 ? <Truck size={14} /> : <ShoppingCart size={14} />}
                            Proses Pemenuhan
                          </button>
                        )}
                      </>
                    )}
                  </div>
                </div>

                <hr className="my-4 border-slate-100" />

                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 items-start">
                  <div>
                    <span className="block text-xs text-slate-400 font-bold mb-1">Jumlah Diminta</span>
                    <span className="text-xs font-semibold text-slate-800">{req.qtyRequested} {req.unit}</span>
                  </div>
                  <div>
                    <span className="block text-xs text-slate-400 font-bold mb-1">Diajukan oleh</span>
                    <div className="flex items-center gap-1.5 text-xs font-semibold text-slate-800">
                      <User size={12} className="text-slate-500" />
                      {req.requester}
                    </div>
                  </div>
                  <div>
                    <span className="block text-xs text-slate-400 font-bold mb-1">Stok di Gudang</span>
                    {stockItem ? (
                      <span className="inline-block text-xs text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                        Stok di gudang: {stockQty} {req.unit}
                      </span>
                    ) : (
                      <span className="inline-block text-xs text-rose-700 font-bold bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200">
                        Baru (0 {req.unit})
                      </span>
                    )}
                  </div>
                  <div>
                    <span className="block text-xs text-slate-400 font-bold mb-1">Status Permintaan</span>
                    <span className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-bold border ${getStatusColor(req.status)}`}>
                      {req.status}
                    </span>
                  </div>
                  {!isPendingCheck && (
                    <div>
                      <span className="block text-xs text-slate-400 font-bold mb-1">Pemenuhan</span>
                      <span className="text-xs font-bold text-slate-800">
                        {req.qtyFulfilled} / {req.qtyRequested} {req.unit}
                      </span>
                    </div>
                  )}
                </div>
              </div>
            );
          })
        )}
      </div>

      {/* Interactive Stock Check Workbench Drawer / Overlay Panel */}
      {selectedRequest && (
        <div className="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-xs animate-fade-in">
          <div className="bg-white rounded border border-slate-200 p-5 shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto animate-slide-up">
            <h3 className="text-sm font-extrabold text-slate-800 border-b border-slate-100 pb-3 mb-4 uppercase tracking-wider">
              Konfirmasi Cek Stok & Pemenuhan
            </h3>

            <div className="space-y-4 mb-6">
              <div className="bg-slate-50 rounded border border-slate-200 p-4 text-xs space-y-2">
                <div className="flex justify-between">
                  <span className="text-slate-500">Nomor BON:</span>
                  <span className="font-mono font-bold text-slate-700">{selectedRequest.bonNo}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-500">Unit Pengaju:</span>
                  <span className="font-bold text-slate-700">{selectedRequest.section}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-500">Nama di Pengajuan:</span>
                  <span className="font-extrabold text-indigo-700">{selectedRequest.itemName}</span>
                </div>
                <div className="flex justify-between border-t border-slate-200/50 pt-2 font-semibold">
                  <span className="text-slate-700">Jumlah Diminta:</span>
                  <span className="text-slate-900 font-extrabold">{selectedRequest.qtyRequested} {selectedRequest.unit}</span>
                </div>
              </div>

              {/* ── Pilih Barang dari Stok Gudang ── */}
              <div>
                <label className="block text-xs font-bold text-indigo-600 uppercase tracking-wider mb-1.5">
                  Cocokkan dengan Barang di Stok Gudang
                </label>
                {selectedStockItem && (
                  <div className="mb-2 flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded px-3 py-2">
                    <CheckCircle size={13} className="text-emerald-500 shrink-0" />
                    <span className="text-xs font-bold text-emerald-700 truncate">{selectedStockItem.name}</span>
                    <span className="text-xs text-emerald-500 font-semibold ml-auto shrink-0">Stok: {selectedStockItem.qty} {selectedStockItem.unit}</span>
                  </div>
                )}
                <div className="relative">
                  <div className="relative">
                    <Search size={12} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                      type="text"
                      placeholder="Cari nama barang di stok gudang..."
                      value={stockSearchQuery}
                      onChange={(e) => {
                        setStockSearchQuery(e.target.value);
                        setShowStockDropdown(true);
                      }}
                      onFocus={() => setShowStockDropdown(true)}
                      className="w-full pl-7 pr-3 py-2 border border-indigo-200 rounded text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white"
                    />
                  </div>
                  {showStockDropdown && (
                    <div className="absolute z-20 w-full bg-white border border-slate-200 rounded shadow-lg mt-1 max-h-48 overflow-y-auto">
                      {stockList
                        .filter((s) =>
                          stockSearchQuery === "" ||
                          s.name.toLowerCase().includes(stockSearchQuery.toLowerCase())
                        )
                        .slice(0, 20)
                        .map((s) => (
                          <button
                            key={s.id ?? s.code}
                            type="button"
                            onClick={() => {
                              setSelectedStockItem(s);
                              setQtyAvailable(s.qty);
                              setQtyFulfilled(Math.min(selectedRequest!.qtyRequested, s.qty));
                              setStockSearchQuery(s.name);
                              setShowStockDropdown(false);
                            }}
                            className="w-full text-left px-3 py-2 text-xs hover:bg-indigo-50 flex justify-between items-center gap-3 border-b border-slate-100 last:border-0"
                          >
                            <span className="font-medium text-slate-800 truncate">{s.name}</span>
                            <span className="text-xs text-slate-400 shrink-0 font-semibold">Stok: {s.qty} {s.unit}</span>
                          </button>
                        ))}
                      {stockList.filter((s) =>
                        stockSearchQuery === "" ||
                        s.name.toLowerCase().includes(stockSearchQuery.toLowerCase())
                      ).length === 0 && (
                        <div className="px-3 py-3 text-xs text-slate-400 text-center">Tidak ada barang yang sesuai</div>
                      )}
                    </div>
                  )}
                </div>
                {!selectedStockItem && (
                  <p className="mt-1.5 text-xs text-amber-600 font-semibold">
                    ⚠ Nama pengajuan tidak cocok otomatis — pilih barang dari dropdown di atas.
                  </p>
                )}
              </div>

              {/* Stock Input fields */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Stok Gudang Terbaca
                  </label>
                  <input
                    type="number"
                    disabled
                    value={qtyAvailable}
                    className="w-full bg-slate-100 border border-slate-200 rounded px-3 py-2 text-xs font-bold text-slate-400 cursor-not-allowed"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-amber-600 uppercase tracking-wider mb-1.5">
                    Jumlah Terpenuhi
                  </label>
                  <input
                    type="number"
                    min="0"
                    max={selectedRequest.qtyRequested}
                    value={qtyFulfilled}
                    onChange={(e) => {
                      const val = Math.max(0, parseInt(e.target.value) || 0);
                      setQtyFulfilled(Math.min(selectedRequest.qtyRequested, val));
                    }}
                    className="w-full bg-white border border-amber-300 rounded px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-1 focus:ring-amber-500"
                  />
                </div>
              </div>

              {/* Status Preview Card */}
              <div className="bg-amber-50/30 border border-amber-200/50 rounded p-3">
                <span className="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-1">Status Akhir Pengajuan:</span>
                <div className="flex items-center gap-2">
                  <ArrowRight size={14} className="text-amber-500" />
                  <span className="text-xs font-extrabold text-slate-800">
                    {qtyFulfilled === selectedRequest.qtyRequested ? (
                      <span className="text-emerald-600">Terpenuhi Seluruhnya</span>
                    ) : qtyFulfilled > 0 ? (
                      <span className="text-amber-600">Terpenuhi Sebagian (Sisa {selectedRequest.qtyRequested - qtyFulfilled} dialihkan ke Pengadaan)</span>
                    ) : (
                      <span className="text-rose-600">Tidak Tersedia (Lanjut ke Pengadaan)</span>
                    )}
                  </span>
                </div>
              </div>

              <div className="bg-rose-50 border border-rose-100 text-rose-900 rounded p-3 text-xs flex gap-2">
                <ShieldAlert size={14} className="text-rose-600 flex-shrink-0 mt-0.5" />
                <div>
                  <span className="font-bold block mb-0.5">Pengurangan Stok Gudang</span>
                  Jika Anda mensetujui, jumlah barang yang terpenuhi ({qtyFulfilled} {selectedRequest.unit}) akan otomatis dipotong dari stok master gudang secara realtime.
                </div>
              </div>
            </div>

            <div className="flex gap-2.5">
              <button
                onClick={() => {
                  setSelectedRequest(null);
                  setSelectedStockItem(null);
                  setStockSearchQuery("");
                  setShowStockDropdown(false);
                }}
                className="flex-1 px-3 py-2 rounded text-xs font-bold text-slate-500 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-all text-center"
              >
                Kembali
              </button>
              <button
                onClick={handleConfirmCheck}
                disabled={qtyFulfilled > 0 && !selectedStockItem}
                title={qtyFulfilled > 0 && !selectedStockItem ? "Pilih dulu barang dari stok gudang" : undefined}
                className="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-xs font-bold py-2 px-3 rounded transition-all shadow-xs text-center"
              >
                Konfirmasi Pemenuhan
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ── Modal Konfirmasi Batalkan ─────────────────────────── */}
      {rejectTarget && (
        <div className="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6">

            {/* Header */}
            <div className="flex items-start gap-3 mb-5">
              <div className="flex size-14 shrink-0 items-center justify-center rounded-xl border bg-rose-50 text-rose-600 border-rose-100">
                <XCircle size={24} />
              </div>
              <div>
                <h3 className="text-sm font-extrabold text-slate-900">Batalkan Pengajuan</h3>
                <p className="text-xs text-slate-500 mt-0.5">
                  {(rejectTarget.status as string) === "Terpenuhi Sebagian"
                    ? "Hanya sisa barang yang belum terpenuhi yang akan dibatalkan. Barang yang sudah didistribusikan tidak dikembalikan ke gudang."
                    : "Membatalkan pengajuan akan mengubah statusnya menjadi Ditolak. Jika stok sudah dialokasikan, stok akan dikembalikan ke gudang."
                  }
                </p>
              </div>
            </div>

            {/* Info BON */}
            <div className="bg-slate-50 border border-slate-200 rounded-lg p-3.5 mb-4 text-xs space-y-1.5">
              <div className="flex justify-between">
                <span className="text-slate-500">Nomor BON:</span>
                <span className="font-mono font-bold text-slate-700">{rejectTarget.bonNo}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Nama Barang:</span>
                <span className="font-bold text-indigo-700">{rejectTarget.itemName}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Jumlah:</span>
                <span className="font-bold text-slate-700">{rejectTarget.qtyRequested} {rejectTarget.unit}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Status saat ini:</span>
                <span className={`px-2 py-0.5 rounded text-xs font-bold border ${getStatusColor(rejectTarget.status)}`}>
                  {rejectTarget.status}
                </span>
              </div>
            </div>

            {/* Input alasan */}
            <div className="mb-4">
              <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                Alasan Pembatalan <span className="text-rose-500">*</span>
              </label>
              <textarea
                rows={3}
                value={rejectAlasan}
                onChange={(e) => {
                  setRejectAlasan(e.target.value);
                  setRejectError(null);
                }}
                placeholder="Contoh: Barang sudah tidak diperlukan, permintaan ditarik oleh pengaju..."
                className={`w-full px-3 py-2 rounded-lg border text-xs text-slate-700 resize-none
                  focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-rose-400
                  ${rejectError ? "border-rose-400 bg-rose-50" : "border-slate-200 bg-slate-50"}`}
              />
              {rejectError && (
                <p className="mt-1 text-xs text-rose-600 font-semibold flex items-center gap-1">
                  <XCircle size={11} /> {rejectError}
                </p>
              )}
              <p className="mt-1 text-xs text-slate-400">Minimal 3 karakter. Alasan ini akan dicatat.</p>
            </div>

            {/* Actions */}
            <div className="flex gap-3 justify-end">
              <button
                disabled={rejectLoading}
                onClick={() => { setRejectTarget(null); setRejectAlasan(""); setRejectError(null); }}
                className="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50 transition-colors"
              >
                Batal
              </button>
              <button
                disabled={rejectLoading}
                onClick={async () => {
                  if (!rejectAlasan.trim() || rejectAlasan.trim().length < 3) {
                    setRejectError("Alasan pembatalan wajib diisi (minimal 3 karakter).");
                    return;
                  }
                  setRejectLoading(true);
                  setRejectError(null);
                  try {
                    await onReject(rejectTarget.id, rejectAlasan.trim());
                    setRejectTarget(null);
                    setRejectAlasan("");
                  } catch (err: any) {
                    setRejectError(err?.message ?? "Gagal membatalkan. Coba lagi.");
                  } finally {
                    setRejectLoading(false);
                  }
                }}
                className="flex items-center gap-2 px-5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold shadow-sm disabled:opacity-50 transition-colors"
              >
                {rejectLoading
                  ? <><Loader2 size={13} className="animate-spin" /> Membatalkan...</>
                  : <><XCircle size={13} /> Konfirmasi Batalkan</>
                }
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Distribution & Procurement Modal */}
      {selectedForAction && (
        <div className="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-xs animate-fade-in">
          <div className="bg-white rounded border border-slate-200 shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto animate-slide-up">
            <div className="sticky top-0 bg-white border-b border-slate-100 p-4 flex justify-between items-center">
              <h3 className="text-sm font-extrabold text-slate-800 uppercase tracking-wider">
                Proses Distribusi & Pengadaan
              </h3>
              <button
                onClick={() => setSelectedForAction(null)}
                className="text-slate-400 hover:text-slate-600 transition-colors"
              >
                <CheckCircle size={20} />
              </button>
            </div>
            <div className="p-5">
              <DistributionProcurement
                request={selectedForAction}
                stockList={stockList}
                onDistribute={onDistribute}
                onProcure={onProcure}
                onCompleteProcurement={onCompleteProcurement}
                currentUser={currentUser}
                onClose={() => setSelectedForAction(null)}
              />
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
