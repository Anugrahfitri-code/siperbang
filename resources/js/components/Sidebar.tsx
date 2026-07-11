import React from "react";
import { UserRole, ItemRequest, ReceiptData, RequestStatus } from "../types";
import { 
  LayoutDashboard, Package, FileSpreadsheet, Receipt, History, 
  ClipboardList, CheckSquare, X, Users
} from "lucide-react";

interface SidebarProps {
  isOpen: boolean;
  onClose: () => void;
  currentRole: UserRole;
  
  officerTab: string;
  setOfficerTab: (tab: "dashboard" | "checking" | "stock" | "ocr" | "report" | "history") => void;
  
  requesterTab: string;
  setRequesterTab: (tab: "bon" | "monitoring" | "history" | "stock") => void;

  superadminTab: string;
  setSuperadminTab: (tab: "users" | "dashboard" | "checking" | "stock_manage" | "ocr" | "report" | "bon" | "monitoring" | "stock_catalog" | "history") => void;
  
  requests: ItemRequest[];
  receipts: ReceiptData[];
}

export function Sidebar({
  isOpen, onClose, currentRole,
  officerTab, setOfficerTab,
  requesterTab, setRequesterTab,
  superadminTab, setSuperadminTab,
  requests, receipts
}: SidebarProps) {
  
  const handleOfficerTab = (tab: any) => {
    setOfficerTab(tab);
    onClose();
  };

  const handleRequesterTab = (tab: any) => {
    setRequesterTab(tab);
    onClose();
  };

  const handleSuperadminTab = (tab: any) => {
    setSuperadminTab(tab);
    onClose();
  };

  return (
    <>
      {/* Backdrop */}
      {isOpen && (
        <div 
          className="fixed inset-0 bg-slate-900/50 z-40 backdrop-blur-sm transition-opacity" 
          onClick={onClose}
        />
      )}

      {/* Sidebar Panel */}
      <div 
        className={`fixed top-0 left-0 h-full w-72 bg-white shadow-2xl z-50 transform transition-transform duration-300 ease-in-out flex flex-col ${
          isOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <div className="p-5 flex items-center justify-between border-b border-slate-100">
          <h2 className="text-sm font-extrabold text-slate-800 uppercase tracking-wider">
            Menu Utama
          </h2>
          <button 
            onClick={onClose}
            className="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
          >
            <X size={18} />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-4 space-y-1.5">
          {currentRole === UserRole.SUPERADMIN ? (
            <>
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block px-3 mb-3 mt-2">
                Manajemen Sistem
              </span>
              <button
                onClick={() => handleSuperadminTab("users")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  superadminTab === "users"
                    ? "bg-emerald-600 text-white border-emerald-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <Users size={16} />
                  <span>Kelola Pengguna</span>
                </div>
              </button>

              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block px-3 mb-3 mt-4">
                Petugas Persediaan
              </span>

              <button
                onClick={() => handleSuperadminTab("dashboard")}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded text-xs font-bold transition-all border ${
                  superadminTab === "dashboard"
                    ? "bg-emerald-600 text-white border-emerald-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <LayoutDashboard size={14} />
                  <span>Daftar Tindakan</span>
                </div>
              </button>

              <button
                onClick={() => handleSuperadminTab("checking")}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded text-xs font-bold transition-all border ${
                  superadminTab === "checking"
                    ? "bg-emerald-600 text-white border-emerald-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <Package size={14} />
                  <span>Pengecekan & Pemenuhan</span>
                </div>
              </button>

              <button
                onClick={() => handleSuperadminTab("stock_manage")}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded text-xs font-bold transition-all border ${
                  superadminTab === "stock_manage"
                    ? "bg-emerald-600 text-white border-emerald-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <FileSpreadsheet size={14} />
                  <span>Excel & Kode Persediaan</span>
                </div>
              </button>

              <button
                onClick={() => handleSuperadminTab("ocr")}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded text-xs font-bold transition-all border ${
                  superadminTab === "ocr"
                    ? "bg-emerald-600 text-white border-emerald-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <Receipt size={14} />
                  <span>OCR Kuitansi & Pajak</span>
                </div>
              </button>

              <button
                onClick={() => handleSuperadminTab("report")}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded text-xs font-bold transition-all border ${
                  superadminTab === "report"
                    ? "bg-emerald-600 text-white border-emerald-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <FileSpreadsheet size={14} />
                  <span>Rekap Laporan Excel</span>
                </div>
              </button>

              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block px-3 mb-3 mt-4">
                Ketua Tim Kerja
              </span>

              <button
                onClick={() => handleSuperadminTab("bon")}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded text-xs font-bold transition-all border ${
                  superadminTab === "bon"
                    ? "bg-emerald-600 text-white border-emerald-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <ClipboardList size={14} />
                  <span>BON Digital / Ajukan Baru</span>
                </div>
              </button>

              <button
                onClick={() => handleSuperadminTab("monitoring")}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded text-xs font-bold transition-all border ${
                  superadminTab === "monitoring"
                    ? "bg-emerald-600 text-white border-emerald-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <CheckSquare size={14} />
                  <span>Pantau Pengajuan</span>
                </div>
              </button>

              <button
                onClick={() => handleSuperadminTab("stock_catalog")}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded text-xs font-bold transition-all border ${
                  superadminTab === "stock_catalog"
                    ? "bg-emerald-600 text-white border-emerald-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <Package size={14} />
                  <span>Katalog Stok Gudang</span>
                </div>
              </button>

              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block px-3 mb-3 mt-4">
                Laporan & Audit
              </span>

              <button
                onClick={() => handleSuperadminTab("history")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  superadminTab === "history"
                    ? "bg-emerald-600 text-white border-emerald-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <History size={16} />
                  <span>Audit Log Sistem</span>
                </div>
              </button>
            </>
          ) : currentRole === UserRole.PETUGAS_PERSERDIAN ? (
            <>
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block px-3 mb-3 mt-2">
                Petugas Persediaan
              </span>

              <button
                onClick={() => handleOfficerTab("dashboard")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  officerTab === "dashboard"
                    ? "bg-indigo-600 text-white border-indigo-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <LayoutDashboard size={16} />
                  <span>Daftar Tindakan</span>
                </div>
                {requests.filter((r) => r.status === RequestStatus.DIAJUKAN).length > 0 && (
                  <span className={`text-[9px] px-2 py-0.5 rounded font-extrabold ${
                    officerTab === "dashboard" ? "bg-white text-indigo-600" : "bg-amber-50 text-amber-600 border border-amber-200"
                  }`}>
                    {requests.filter((r) => r.status === RequestStatus.DIAJUKAN).length}
                  </span>
                )}
              </button>

              <button
                onClick={() => handleOfficerTab("checking")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  officerTab === "checking"
                    ? "bg-indigo-600 text-white border-indigo-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <Package size={16} />
                  <span>Pengecekan & Pemenuhan</span>
                </div>
              </button>

              <button
                onClick={() => handleOfficerTab("stock")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  officerTab === "stock"
                    ? "bg-indigo-600 text-white border-indigo-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <FileSpreadsheet size={16} />
                  <span>Excel & Kode Persediaan</span>
                </div>
              </button>

              <button
                onClick={() => handleOfficerTab("ocr")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  officerTab === "ocr"
                    ? "bg-indigo-600 text-white border-indigo-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <Receipt size={16} />
                  <span>OCR Kuitansi & Pajak</span>
                </div>
                {receipts.filter((r) => !r.isVerified).length > 0 && (
                  <span className={`text-[9px] px-2 py-0.5 rounded font-extrabold ${
                    officerTab === "ocr" ? "bg-white text-indigo-600" : "bg-rose-50 text-rose-600 border border-rose-200"
                  }`}>
                    {receipts.filter((r) => !r.isVerified).length}
                  </span>
                )}
              </button>

              <button
                onClick={() => handleOfficerTab("report")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  officerTab === "report"
                    ? "bg-indigo-600 text-white border-indigo-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <FileSpreadsheet size={16} />
                  <span>Rekap Laporan Excel</span>
                </div>
              </button>

              <button
                onClick={() => handleOfficerTab("history")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  officerTab === "history"
                    ? "bg-indigo-600 text-white border-indigo-600 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <History size={16} />
                  <span>Histori & Audit Log</span>
                </div>
              </button>
            </>
          ) : (
            <>
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block px-3 mb-3 mt-2">
                Ketua Tim Kerja
              </span>

              <button
                onClick={() => handleRequesterTab("bon")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  requesterTab === "bon"
                    ? "bg-amber-400 text-slate-900 border-amber-400 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <ClipboardList size={16} />
                  <span>BON Digital / Ajukan Baru</span>
                </div>
              </button>

              <button
                onClick={() => handleRequesterTab("monitoring")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  requesterTab === "monitoring"
                    ? "bg-amber-400 text-slate-900 border-amber-400 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <CheckSquare size={16} />
                  <span>Pantau Pengajuan Saya</span>
                </div>
              </button>

              <button
                onClick={() => handleRequesterTab("stock")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  requesterTab === "stock"
                    ? "bg-amber-400 text-slate-900 border-amber-400 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <Package size={16} />
                  <span>Katalog Stok Gudang</span>
                </div>
              </button>

              <button
                onClick={() => handleRequesterTab("history")}
                className={`w-full flex items-center justify-between px-3.5 py-3 rounded text-xs font-bold transition-all border ${
                  requesterTab === "history"
                    ? "bg-amber-400 text-slate-900 border-amber-400 shadow-xs"
                    : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900"
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <History size={16} />
                  <span>Histori Pengajuan</span>
                </div>
              </button>
            </>
          )}
        </div>
        
        <div className="p-4 border-t border-slate-100 bg-slate-50 mt-auto">
          <p className="text-[9px] text-slate-400 font-bold uppercase tracking-wider text-center">
            SIPERBANG v1.1
          </p>
        </div>
      </div>
    </>
  );
}
