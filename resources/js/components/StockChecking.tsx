import React, { useState } from "react";
import { ItemRequest, RequestStatus, StockItem } from "../types";
import { SearchCode, CheckCircle, AlertTriangle, Play, HelpCircle, Package, ArrowRight, ShieldAlert } from "lucide-react";

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
}

export const StockChecking: React.FC<StockCheckingProps> = ({
  requests,
  stockList,
  onUpdateStatus,
}) => {
  const [selectedRequest, setSelectedRequest] = useState<ItemRequest | null>(null);
  const [qtyAvailable, setQtyAvailable] = useState<number>(0);
  const [qtyFulfilled, setQtyFulfilled] = useState<number>(0);

  const openChecker = (req: ItemRequest) => {
    // Find matching stock item
    const stockItem = stockList.find(
      (s) => s.name.toLowerCase() === req.itemName.toLowerCase()
    );
    const available = stockItem ? stockItem.qty : 0;

    setSelectedRequest(req);
    setQtyAvailable(available);
    // Suggest default fulfilled as Min(requested, available)
    setQtyFulfilled(Math.min(req.qtyRequested, available));
  };

  const handleConfirmCheck = () => {
    if (!selectedRequest) return;

    const requested = selectedRequest.qtyRequested;
    const fulfilled = Number(qtyFulfilled);
    const unfulfilled = requested - fulfilled;

    let finalStatus: RequestStatus;
    let logMessage = "";
    let stockItemToDeduct: { code: string; qtyToDeduct: number } | undefined;

    // Find stock code
    const stockItem = stockList.find(
      (s) => s.name.toLowerCase() === selectedRequest.itemName.toLowerCase()
    );

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
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      <div className="flex items-center gap-3 mb-6">
        <div className="bg-amber-50 text-amber-600 p-2 rounded border border-amber-100">
          <Package size={18} />
        </div>
        <div>
          <h2 className="text-base font-extrabold text-slate-800 tracking-tight">Pengecekan Stok & Pemenuhan</h2>
          <p className="text-[11px] text-slate-500">
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
                className={`border rounded p-4 transition-all ${
                  isPendingCheck
                    ? "border-amber-200 bg-amber-50/10 hover:border-amber-300"
                    : "border-slate-200 hover:border-slate-300"
                }`}
              >
                <div className="flex flex-col lg:flex-row justify-between lg:items-center gap-4">
                  <div>
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider">
                        {req.bonNo}
                      </span>
                      <span className="text-slate-300">•</span>
                      <span className="text-[10px] text-slate-400 font-semibold font-mono">
                        {req.date}
                      </span>
                      <span className="text-slate-300">•</span>
                      <span className="text-xs font-bold text-slate-700">
                        {req.section}
                      </span>
                    </div>

                    <h3 className="text-sm font-extrabold text-slate-800 mt-1.5 flex items-center gap-1.5">
                      {req.itemName}
                      <span className="text-xs font-normal text-slate-500 font-mono">
                        ({req.qtyRequested} {req.unit})
                      </span>
                    </h3>

                    {req.notes && (
                      <p className="text-xs text-slate-500 italic mt-1 font-sans">
                        &ldquo;{req.notes}&rdquo;
                      </p>
                    )}

                    <div className="flex items-center gap-4 mt-3">
                      <span className="text-xs text-slate-400 font-medium">
                        Diajukan oleh: <strong className="text-slate-600 font-bold">{req.requester}</strong>
                      </span>
                      {stockItem ? (
                        <span className="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2.5 py-0.5 rounded border border-emerald-100">
                          Stok di Gudang: {stockQty} {req.unit}
                        </span>
                      ) : (
                        <span className="text-[10px] text-rose-600 font-bold bg-rose-50 px-2.5 py-0.5 rounded border border-rose-100">
                          Barang Baru (Belum Terdaftar di Stok)
                        </span>
                      )}
                    </div>
                  </div>

                  <div className="flex items-center gap-3 justify-end">
                    <span className={`px-2.5 py-0.5 rounded text-[10px] font-bold border ${getStatusColor(req.status)}`}>
                      {req.status}
                    </span>

                    {isPendingCheck ? (
                      <button
                        onClick={() => openChecker(req)}
                        className="bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold px-3.5 py-1.5 rounded transition-all flex items-center gap-1 shadow-xs"
                      >
                        <Play size={11} />
                        Proses Cek Stok
                      </button>
                    ) : (
                      <div className="text-right text-xs">
                        <span className="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">Status Pemenuhan:</span>
                        <span className="font-extrabold text-slate-700">
                          {req.qtyFulfilled} / {req.qtyRequested} {req.unit}
                        </span>
                      </div>
                    )}
                  </div>
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
                  <span className="text-slate-500">Nama Barang:</span>
                  <span className="font-extrabold text-indigo-700">{selectedRequest.itemName}</span>
                </div>
                <div className="flex justify-between border-t border-slate-200/50 pt-2 font-semibold">
                  <span className="text-slate-700">Jumlah Diminta:</span>
                  <span className="text-slate-900 font-extrabold">{selectedRequest.qtyRequested} {selectedRequest.unit}</span>
                </div>
              </div>

              {/* Stock Input fields */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
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
                  <label className="block text-[10px] font-bold text-amber-600 uppercase tracking-wider mb-1.5">
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
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-1">Status Akhir Pengajuan:</span>
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
                onClick={() => setSelectedRequest(null)}
                className="flex-1 px-3 py-2 rounded text-xs font-bold text-slate-500 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-all text-center"
              >
                Kembali
              </button>
              <button
                onClick={handleConfirmCheck}
                className="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-3 rounded transition-all shadow-xs text-center"
              >
                Konfirmasi Pemenuhan
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
