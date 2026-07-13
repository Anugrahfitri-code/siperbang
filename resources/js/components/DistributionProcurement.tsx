import React, { useState } from "react";
import { ItemRequest, StockItem, RequestStatus, ProcurementMethod, Distribution, Procurement } from "../types";
import { Truck, ShoppingCart, Package, CheckCircle, AlertTriangle, FileText, Calculator, Percent, Store, Building2 } from "lucide-react";

interface DistributionProcurementProps {
  request: ItemRequest;
  stockList: StockItem[];
  onDistribute: (reqId: string, data: {
    stockItemId: string;
    qtyDistributed: number;
    distributedBy: string;
    notes?: string;
  }) => void;
  onProcure: (reqId: string, data: {
    method: ProcurementMethod;
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
  }) => void;
  onCompleteProcurement: (reqId: string, procurementId: string, processedBy: string) => void;
  currentUser: string;
}

export const DistributionProcurement: React.FC<DistributionProcurementProps> = ({
  request,
  stockList,
  onDistribute,
  onProcure,
  onCompleteProcurement,
  currentUser,
}) => {
  const [activeTab, setActiveTab] = useState<"distribute" | "procure">("distribute");
  const [selectedStockItem, setSelectedStockItem] = useState<string>("");
  const [qtyDistributed, setQtyDistributed] = useState<number>(0);
  const [distributionNotes, setDistributionNotes] = useState<string>("");

  // Procurement form state
  const [procurementMethod, setProcurementMethod] = useState<ProcurementMethod>(ProcurementMethod.SENDIRI);
  const [vendorName, setVendorName] = useState<string>("");
  const [storeName, setStoreName] = useState<string>("");
  const [qtyProcured, setQtyProcured] = useState<number>(request.qtyToProcure || request.qtyRequested);
  const [unitPrice, setUnitPrice] = useState<number>(0);
  const [isTaxed, setIsTaxed] = useState<boolean>(true);
  const [taxRate, setTaxRate] = useState<number>(11);
  const [invoiceNo, setInvoiceNo] = useState<string>("");
  const [bastName, setBastName] = useState<string>("");
  const [bastDate, setBastDate] = useState<string>("");
  const [contractNo, setContractNo] = useState<string>("");

  // Calculate totals
  const taxFactor = 1 + (isTaxed ? taxRate / 100 : 0);
  const totalPrice = Math.round(unitPrice * taxFactor * qtyProcured * 100) / 100;

  // Auto-select matching stock item
  React.useEffect(() => {
    const matchingStock = stockList.find(
      (s) => s.name.toLowerCase() === request.itemName.toLowerCase()
    );
    if (matchingStock) {
      setSelectedStockItem(matchingStock.id);
    }
  }, [request.itemName, stockList]);

  const handleDistribute = () => {
    if (!selectedStockItem || qtyDistributed <= 0) return;
    
    onDistribute(request.id, {
      stockItemId: selectedStockItem,
      qtyDistributed,
      distributedBy: currentUser,
      notes: distributionNotes || undefined,
    });
  };

  const handleProcure = () => {
    onProcure(request.id, {
      method: procurementMethod,
      vendorName: procurementMethod === ProcurementMethod.VENDOR ? vendorName : undefined,
      storeName: procurementMethod === ProcurementMethod.SENDIRI ? storeName : undefined,
      qtyProcured,
      unitPrice,
      isTaxed,
      taxRate,
      invoiceNo: invoiceNo || undefined,
      bastName: bastName || undefined,
      bastDate: bastDate || undefined,
      contractNo: contractNo || undefined,
      processedBy: currentUser,
    });
  };

  const formatIDR = (num: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(num);
  };

  const canDistribute = 
    !request.distribution && 
    (request.status === RequestStatus.TERPENUHI || 
     request.status === RequestStatus.TERPENUHI_SEBAGIAN ||
     request.status === RequestStatus.SIAP_DIDISTRIBUSIKAN) &&
    request.qtyFulfilled > 0;

  const canProcure = 
    request.qtyToProcure > 0 &&
    (request.status === RequestStatus.PERLU_PENGADAAN ||
     request.status === RequestStatus.TERPENUHI_SEBAGIAN ||
     request.status === RequestStatus.DALAM_PENGADAAN);

  if (!canDistribute && !canProcure) {
    return (
      <div className="bg-slate-50 border border-slate-200 rounded p-6 text-center">
        <CheckCircle className="text-emerald-500 mx-auto mb-2" size={32} />
        <h3 className="text-sm font-bold text-slate-800 mb-1">Tidak Ada Aksi yang Diperlukan</h3>
        <p className="text-xs text-slate-500">
          Status pengajuan ini tidak memerlukan distribusi atau pengadaan tambahan.
        </p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      <div className="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
        <div className="bg-indigo-50 text-indigo-600 p-2.5 rounded border border-indigo-150">
          <Package size={18} />
        </div>
        <div>
          <h2 className="text-base font-extrabold text-slate-800 tracking-tight">Distribusi & Pengadaan</h2>
          <p className="text-[11px] text-slate-500">
            Proses distribusi barang dari stok atau pengadaan untuk {request.bonNo}
          </p>
        </div>
      </div>

      {/* Request Summary */}
      <div className="bg-slate-50 border border-slate-200 rounded p-4 mb-6 text-xs space-y-2">
        <div className="flex justify-between">
          <span className="text-slate-500">Nomor BON:</span>
          <span className="font-mono font-bold text-slate-700">{request.bonNo}</span>
        </div>
        <div className="flex justify-between">
          <span className="text-slate-500">Nama Barang:</span>
          <span className="font-extrabold text-indigo-700">{request.itemName}</span>
        </div>
        <div className="flex justify-between">
          <span className="text-slate-500">Jumlah Diminta:</span>
          <span className="font-bold text-slate-800">{request.qtyRequested} {request.unit}</span>
        </div>
        <div className="flex justify-between border-t border-slate-200/50 pt-2">
          <span className="text-slate-500">Terpenuhi dari Stok:</span>
          <span className="font-bold text-emerald-600">{request.qtyFulfilled} {request.unit}</span>
        </div>
        <div className="flex justify-between">
          <span className="text-slate-500">Perlu Pengadaan:</span>
          <span className="font-bold text-amber-600">{request.qtyToProcure} {request.unit}</span>
        </div>
        <div className="flex justify-between">
          <span className="text-slate-500">Status Saat Ini:</span>
          <span className="font-bold text-slate-800">{request.status}</span>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex bg-slate-100 border border-slate-200 rounded p-0.5 mb-6">
        {canDistribute && (
          <button
            onClick={() => setActiveTab("distribute")}
            className={`flex-1 px-3 py-2 rounded text-xs font-bold transition-all flex items-center justify-center gap-2 ${
              activeTab === "distribute"
                ? "bg-white text-slate-800 shadow-xs"
                : "text-slate-500 hover:text-slate-800"
            }`}
          >
            <Truck size={14} />
            Distribusi Barang
          </button>
        )}
        {canProcure && (
          <button
            onClick={() => setActiveTab("procure")}
            className={`flex-1 px-3 py-2 rounded text-xs font-bold transition-all flex items-center justify-center gap-2 ${
              activeTab === "procure"
                ? "bg-white text-slate-800 shadow-xs"
                : "text-slate-500 hover:text-slate-800"
            }`}
          >
            <ShoppingCart size={14} />
            Pengadaan Barang
          </button>
        )}
      </div>

      {/* Distribution Tab */}
      {activeTab === "distribute" && canDistribute && (
        <div className="space-y-4">
          {request.distribution ? (
            <div className="bg-emerald-50 border border-emerald-200 rounded p-4">
              <div className="flex items-center gap-2 mb-3">
                <CheckCircle className="text-emerald-600" size={16} />
                <span className="text-xs font-bold text-emerald-800">Sudah Didistribusikan</span>
              </div>
              <div className="text-xs space-y-1 text-slate-700">
                <div className="flex justify-between">
                  <span>Jumlah:</span>
                  <span className="font-bold">{request.distribution.qtyDistributed} {request.unit}</span>
                </div>
                <div className="flex justify-between">
                  <span>Oleh:</span>
                  <span className="font-bold">{request.distribution.distributedBy}</span>
                </div>
                <div className="flex justify-between">
                  <span>Tanggal:</span>
                  <span className="font-bold">{request.distribution.distributedAt}</span>
                </div>
              </div>
            </div>
          ) : (
            <>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Pilih Barang Stok
                  </label>
                  <select
                    value={selectedStockItem}
                    onChange={(e) => setSelectedStockItem(e.target.value)}
                    className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  >
                    <option value="">-- Pilih Barang --</option>
                    {stockList.map((stock) => (
                      <option key={stock.id} value={stock.id}>
                        {stock.code} - {stock.name} (Stok: {stock.qty} {stock.unit})
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Jumlah Didistribusikan
                  </label>
                  <input
                    type="number"
                    min="1"
                    max={request.qtyFulfilled}
                    value={qtyDistributed}
                    onChange={(e) => setQtyDistributed(Math.min(request.qtyFulfilled, Math.max(1, parseInt(e.target.value) || 0)))}
                    className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  />
                  <p className="text-[10px] text-slate-400 mt-1">Maks: {request.qtyFulfilled} {request.unit}</p>
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                  Catatan (Opsional)
                </label>
                <textarea
                  value={distributionNotes}
                  onChange={(e) => setDistributionNotes(e.target.value)}
                  rows={2}
                  className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  placeholder="Catatan distribusi..."
                />
              </div>

              <button
                onClick={handleDistribute}
                disabled={!selectedStockItem || qtyDistributed <= 0}
                className="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-xs font-bold py-2.5 px-4 rounded transition-all shadow-xs flex items-center justify-center gap-2"
              >
                <Truck size={14} />
                Proses Distribusi
              </button>
            </>
          )}
        </div>
      )}

      {/* Procurement Tab */}
      {activeTab === "procure" && canProcure && (
        <div className="space-y-4">
          {/* Existing Procurements */}
          {request.procurements && request.procurements.length > 0 && (
            <div className="bg-amber-50 border border-amber-200 rounded p-4 mb-4">
              <span className="text-[10px] font-bold text-amber-800 uppercase tracking-wider block mb-2">
                Pengadaan Berjalan
              </span>
              {request.procurements.map((proc) => (
                <div key={proc.id} className="bg-white border border-amber-100 rounded p-3 mb-2 last:mb-0 text-xs">
                  <div className="flex justify-between items-center mb-2">
                    <span className="font-bold text-slate-800">{proc.method}</span>
                    <span className={`px-2 py-0.5 rounded text-[9px] font-bold ${
                      proc.status === "Diterima" 
                        ? "bg-emerald-100 text-emerald-800" 
                        : proc.status === "Dibatalkan"
                        ? "bg-rose-100 text-rose-800"
                        : "bg-amber-100 text-amber-800"
                    }`}>
                      {proc.status}
                    </span>
                  </div>
                  <div className="space-y-1 text-slate-600">
                    <div className="flex justify-between">
                      <span>Jumlah:</span>
                      <span className="font-bold">{proc.qtyProcured} {request.unit}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Total:</span>
                      <span className="font-bold">{formatIDR(proc.totalPrice)}</span>
                    </div>
                    {proc.status === "Diproses" && (
                      <button
                        onClick={() => onCompleteProcurement(request.id, proc.id, currentUser)}
                        className="mt-2 w-full bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold py-1.5 px-3 rounded transition-all"
                      >
                        Tanda Terima Barang
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* New Procurement Form */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                Metode Pengadaan
              </label>
              <select
                value={procurementMethod}
                onChange={(e) => setProcurementMethod(e.target.value as ProcurementMethod)}
                className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              >
                <option value={ProcurementMethod.SENDIRI}>Pengadaan Sendiri (Toko)</option>
                <option value={ProcurementMethod.VENDOR}>Pengadaan Vendor</option>
              </select>
            </div>

            {procurementMethod === ProcurementMethod.VENDOR ? (
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                  Nama Vendor
                </label>
                <input
                  type="text"
                  value={vendorName}
                  onChange={(e) => setVendorName(e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  placeholder="Nama vendor..."
                />
              </div>
            ) : (
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                  Nama Toko
                </label>
                <input
                  type="text"
                  value={storeName}
                  onChange={(e) => setStoreName(e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  placeholder="Nama toko..."
                />
              </div>
            )}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                Jumlah Diadakan
              </label>
              <input
                type="number"
                min="1"
                max={request.qtyToProcure}
                value={qtyProcured}
                onChange={(e) => setQtyProcured(Math.min(request.qtyToProcure, Math.max(1, parseInt(e.target.value) || 0)))}
                className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
              <p className="text-[10px] text-slate-400 mt-1">Kebutuhan: {request.qtyToProcure} {request.unit}</p>
            </div>

            <div>
              <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                Harga Satuan (Rp)
              </label>
              <input
                type="number"
                min="0"
                value={unitPrice}
                onChange={(e) => setUnitPrice(Math.max(0, parseInt(e.target.value) || 0))}
                className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>

          {/* Tax Configuration */}
          <div className="bg-slate-50 border border-slate-200 rounded p-4">
            <span className="text-xs font-extrabold text-slate-700 uppercase tracking-wider block mb-2.5">
              Konfigurasi Pajak
            </span>
            <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
              <div className="flex gap-4">
                <label className="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                  <input
                    type="radio"
                    checked={isTaxed}
                    onChange={() => setIsTaxed(true)}
                    className="accent-indigo-600"
                  />
                  Dikenakan Pajak
                </label>
                <label className="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                  <input
                    type="radio"
                    checked={!isTaxed}
                    onChange={() => setIsTaxed(false)}
                    className="accent-indigo-600"
                  />
                  Bebas Pajak
                </label>
              </div>

              {isTaxed && (
                <div className="flex items-center gap-2">
                  <span className="text-xs text-slate-500 font-semibold">Tarif Pajak:</span>
                  <div className="relative flex items-center">
                    <input
                      type="number"
                      min="0"
                      max="100"
                      value={taxRate}
                      onChange={(e) => setTaxRate(Math.max(0, Math.min(100, parseInt(e.target.value) || 0)))}
                      className="w-16 bg-white border border-slate-200 rounded px-2.5 py-1 text-xs font-bold text-slate-800 text-center focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                    <Percent size={11} className="absolute right-1.5 text-slate-400" />
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Document Fields */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                Nomor Invoice (Opsional)
              </label>
              <input
                type="text"
                value={invoiceNo}
                onChange={(e) => setInvoiceNo(e.target.value)}
                className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                placeholder="INV/..."
              />
            </div>

            {procurementMethod === ProcurementMethod.VENDOR && (
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                  Nomor Kontrak (Opsional)
                </label>
                <input
                  type="text"
                  value={contractNo}
                  onChange={(e) => setContractNo(e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  placeholder="Kontrak No..."
                />
              </div>
            )}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                Nama BAST
              </label>
              <input
                type="text"
                value={bastName}
                onChange={(e) => setBastName(e.target.value)}
                className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                placeholder="Nama penerima..."
              />
            </div>

            <div>
              <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                Tanggal BAST
              </label>
              <input
                type="date"
                value={bastDate}
                onChange={(e) => setBastDate(e.target.value)}
                className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>

          {/* Price Summary */}
          <div className="bg-indigo-50 border border-indigo-200 rounded p-4">
            <div className="flex items-center gap-2 mb-3">
              <Calculator className="text-indigo-600" size={16} />
              <span className="text-xs font-bold text-indigo-800 uppercase tracking-wider">Ringkasan Harga</span>
            </div>
            <div className="space-y-2 text-xs">
              <div className="flex justify-between">
                <span className="text-slate-600">Subtotal:</span>
                <span className="font-bold text-slate-800">{formatIDR(unitPrice * qtyProcured)}</span>
              </div>
              {isTaxed && (
                <div className="flex justify-between text-indigo-700">
                  <span>Pajak ({taxRate}%):</span>
                  <span className="font-bold">+ {formatIDR(totalPrice - unitPrice * qtyProcured)}</span>
                </div>
              )}
              <div className="flex justify-between text-sm font-extrabold text-slate-900 border-t border-indigo-200 pt-2">
                <span>Total:</span>
                <span>{formatIDR(totalPrice)}</span>
              </div>
            </div>
          </div>

          <button
            onClick={handleProcure}
            disabled={qtyProcured <= 0 || unitPrice <= 0 || (procurementMethod === ProcurementMethod.VENDOR && !vendorName) || (procurementMethod === ProcurementMethod.SENDIRI && !storeName)}
            className="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-xs font-bold py-2.5 px-4 rounded transition-all shadow-xs flex items-center justify-center gap-2"
          >
            <ShoppingCart size={14} />
            Buat Pengadaan Baru
          </button>
        </div>
      )}
    </div>
  );
};
