/**
 * BonMonitoringList.tsx
 *
 * Menampilkan daftar BonHeader milik Ketua Tim beserta status, jumlah item,
 * dan tombol aksi yang sesuai status.
 */

import React, { useState, useMemo } from "react";
import { ConfirmDialog } from "./ConfirmDialog";
import {
  ClipboardList,
  Edit3,
  Trash2,
  Eye,
  ChevronDown,
  ChevronUp,
  CheckCircle,
  Clock,
  AlertCircle,
  XCircle,
  Package,
  Loader2,
  RefreshCw,
  ChevronRight,
  Filter,
  Box,
  Battery,
  Scissors,
  Calendar,
  Trash
} from "lucide-react";
import type { BonDraft } from "./BonDigitalForm";

// ── Types ──────────────────────────────────────────────────────────────────

export interface BonHeaderRow {
  id:          number;
  bonNo:       string;
  date:        string;
  section:     string;
  requester:   string;
  status:      string;
  keperluan:   string | null;
  catatan:     string | null;
  itemsCount:  number;
  lastUpdated: string | null;
  items?: BonHeaderItem[];
}

export interface BonHeaderItem {
  id:           number;
  itemName?:    string;
  item_name?:   string;
  unit:         string;
  qtyRequested?: number;
  qty_requested?: number;
  qtyFulfilled?: number;
  qty_fulfilled?: number;
  status:       string;
  notes:        string | null;
  stockItemId?: number | null;
  stock_item_id?: number | null;
}

interface BonMonitoringListProps {
  bons:         BonHeaderRow[];
  loading:      boolean;
  error:        string | null;
  onRefresh:    () => void;
  onEditDraft:  (bon: BonDraft) => void;
  onDeleteDraft:(id: number, bonNo: string) => Promise<void>;
}

// ── Helpers ────────────────────────────────────────────────────────────────

const statusConfig: Record<string, { label: string; color: string; icon: React.ReactNode }> = {
  Draft:               { label: "Draft",               color: "bg-slate-100 text-slate-700 border-slate-200",   icon: <Edit3 size={12} /> },
  "Menunggu Verifikasi":{ label: "Menunggu Verifikasi", color: "bg-amber-50 text-amber-800 border-amber-200",    icon: <Clock size={12} /> },
  Diproses:            { label: "Diproses",             color: "bg-indigo-50 text-indigo-800 border-indigo-200", icon: <Clock size={12} /> },
  Disetujui:           { label: "Disetujui",            color: "bg-blue-50 text-blue-800 border-blue-200",       icon: <CheckCircle size={12} /> },
  Ditolak:             { label: "Ditolak",              color: "bg-rose-50 text-rose-800 border-rose-200",       icon: <XCircle size={12} /> },
  Selesai:             { label: "Selesai",              color: "bg-emerald-50 text-emerald-800 border-emerald-200", icon: <CheckCircle size={12} /> },
  Diajukan:            { label: "Diajukan",             color: "bg-amber-50 text-amber-800 border-amber-200",    icon: <Clock size={12} /> },
};

const itemStatusColor: Record<string, string> = {
  Draft:              "text-slate-500",
  Diajukan:           "text-amber-600",
  Terpenuhi:          "text-emerald-600",
  "Terpenuhi Sebagian":"text-amber-600",
  "Perlu Pengadaan":  "text-rose-600",
  "Dalam Pengadaan":  "text-indigo-600",
  Ditolak:            "text-rose-600",
  Selesai:            "text-emerald-700",
};

function formatDate(d: string | null): string {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
}

function getIconForCategory(name: string | null) {
  if (!name) return <Package size={24} strokeWidth={1.5} />;
  const n = name.toLowerCase();
  if (n.includes('baterai')) return <Battery size={24} strokeWidth={1.5} />;
  if (n.includes('gunting')) return <Scissors size={24} strokeWidth={1.5} />;
  return <Box size={24} strokeWidth={1.5} />;
}

// ── Component ─────────────────────────────────────────────────────────────

export const BonMonitoringList: React.FC<BonMonitoringListProps> = ({
  bons,
  loading,
  error,
  onRefresh,
  onEditDraft,
  onDeleteDraft,
}) => {
  const [expandedId,   setExpandedId]   = useState<number | null>(null);
  const [deletingId,   setDeletingId]   = useState<number | null>(null);
  const [confirmDelete, setConfirmDelete] = useState<{ id: number; bonNo: string } | null>(null);
  const [activeFilter, setActiveFilter] = useState<string>("Semua");

  const toggleExpand = (id: number) =>
    setExpandedId((prev) => (prev === id ? null : id));

  const handleDeleteConfirm = async () => {
    if (!confirmDelete) return;
    setDeletingId(confirmDelete.id);
    try {
      await onDeleteDraft(confirmDelete.id, confirmDelete.bonNo);
    } finally {
      setDeletingId(null);
      setConfirmDelete(null);
    }
  };

  // Filter Logic
  const filteredBons = useMemo(() => {
    if (activeFilter === "Semua") return bons;
    if (activeFilter === "Diajukan") return bons.filter(b => b.status === "Diajukan" || b.status === "Menunggu Verifikasi" || b.status === "Diproses");
    return bons.filter(b => b.status === activeFilter);
  }, [bons, activeFilter]);

  const counts = useMemo(() => ({
    Semua: bons.length,
    Diajukan: bons.filter(b => b.status === "Diajukan" || b.status === "Menunggu Verifikasi" || b.status === "Diproses").length,
    Ditolak: bons.filter(b => b.status === "Ditolak").length,
    Draft: bons.filter(b => b.status === "Draft").length,
    Selesai: bons.filter(b => b.status === "Selesai" || b.status === "Disetujui").length,
  }), [bons]);

  const filterTabs = [
    { id: "Semua", label: "Semua", icon: <ClipboardList size={14} />, color: "text-amber-600 bg-amber-50 border-amber-200" },
    { id: "Diajukan", label: "Diajukan", icon: <CheckCircle size={14} />, color: "text-blue-600 bg-blue-50 border-blue-200" },
    { id: "Ditolak", label: "Ditolak", icon: <XCircle size={14} />, color: "text-rose-600 bg-rose-50 border-rose-200" },
    { id: "Draft", label: "Draft", icon: <Edit3 size={14} />, color: "text-indigo-600 bg-indigo-50 border-indigo-200" },
    { id: "Selesai", label: "Selesai", icon: <CheckCircle size={14} />, color: "text-emerald-600 bg-emerald-50 border-emerald-200" },
  ];

  // ── Loading / Error / Empty states ───────────────────────────
  if (loading) {
    return (
      <div className="bg-white rounded-lg border border-slate-200 p-10 shadow-sm flex flex-col items-center gap-3 text-slate-400">
        <Loader2 size={24} className="animate-spin text-indigo-500" />
        <p className="text-xs font-semibold">Memuat daftar pengajuan...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-white rounded-lg border border-slate-200 p-8 shadow-sm flex flex-col items-center gap-3">
        <AlertCircle size={24} className="text-rose-400" />
        <p className="text-sm font-extrabold text-slate-700">Gagal memuat data</p>
        <p className="text-xs text-slate-400">{error}</p>
        <button onClick={onRefresh}
          className="mt-1 flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors">
          <RefreshCw size={13} /> Coba Lagi
        </button>
      </div>
    );
  }

  return (
    <>
      {confirmDelete && (
        <ConfirmDialog
          open
          title="Hapus Draft?"
          message={
            <>
              Draft <strong>{confirmDelete.bonNo}</strong> akan dihapus permanen dan tidak dapat dikembalikan.
            </>
          }
          variant="danger"
          confirmText={deletingId === confirmDelete.id ? "Menghapus..." : "Hapus Draft"}
          loading={deletingId === confirmDelete.id}
          onConfirm={handleDeleteConfirm}
          onClose={() => { if (deletingId === null) setConfirmDelete(null); }}
        />
      )}

      <div className="space-y-6">
        
        {/* HEADER BANNER */}
        <div className="relative bg-gradient-to-r from-[#f8faff] to-[#f0f4ff] rounded-2xl border border-indigo-50/50 p-6 shadow-sm overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
          <div className="absolute -top-24 -left-24 w-64 h-64 bg-blue-100/40 rounded-full blur-3xl pointer-events-none"></div>
          
          <div className="relative z-10 flex-1 space-y-4">
            <div className="flex items-center gap-4">
              <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm border border-orange-100 text-orange-400">
                <ClipboardList size={28} strokeWidth={2.5} />
              </div>
              <div>
                <h2 className="text-xl font-extrabold text-slate-900 tracking-tight">
                  Pantau Pengajuan Saya
                </h2>
                <p className="text-sm font-medium text-slate-500 mt-1">
                  Pantau status real-time, ketersediaan stok, hasil pengecekan, serta status pengadaan unit kerja Anda
                </p>
              </div>
            </div>
          </div>

          {/* 3D Illustration / Graphic - Removed as requested */}
        </div>

        {/* FILTERS & SORT */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="flex flex-wrap gap-2">
            {filterTabs.map(tab => (
              <button
                key={tab.id}
                onClick={() => setActiveFilter(tab.id)}
                className={`flex items-center gap-2 px-3.5 py-2 rounded-full border text-xs font-bold transition-colors
                  ${activeFilter === tab.id ? tab.color : "bg-white border-slate-200 text-slate-500 hover:bg-slate-50"}`}
              >
                <span className={activeFilter === tab.id ? "" : "text-slate-400"}>{tab.icon}</span>
                {tab.label}
                <span className={`px-1.5 py-0.5 rounded-full text-[10px] bg-white/50 ${activeFilter === tab.id ? "" : "text-slate-400 font-semibold"}`}>
                  {counts[tab.id as keyof typeof counts]}
                </span>
              </button>
            ))}
          </div>

          <button className="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 shadow-sm shrink-0">
            <Filter size={14} />
            Terbaru
            <ChevronDown size={14} className="opacity-50 ml-1" />
          </button>
        </div>

        {/* BON list */}
        {filteredBons.length === 0 ? (
          <div className="bg-white border-2 border-dashed border-slate-200 rounded-2xl py-16 text-center shadow-sm">
            <Package className="mx-auto text-slate-300 mb-3" size={32} />
            <p className="text-sm font-bold text-slate-500">Belum ada pengajuan</p>
            <p className="text-xs text-slate-400 mt-1">
              Tidak ada data yang sesuai dengan filter ini.
            </p>
          </div>
        ) : (
          <div className="space-y-3">
            {filteredBons.map((bon) => {
              const cfg       = statusConfig[bon.status] ?? statusConfig["Draft"];
              const isExpanded = expandedId === bon.id;
              const isDraft    = bon.status === "Draft";
              const firstItem = bon.items && bon.items.length > 0 ? bon.items[0] : null;
              const firstItemName = firstItem ? (firstItem.itemName || firstItem.item_name) : null;
              const firstItemReq = firstItem ? (firstItem.qtyRequested ?? firstItem.qty_requested ?? 0) : 0;
              const firstItemFulf = firstItem ? (firstItem.qtyFulfilled ?? firstItem.qty_fulfilled ?? 0) : 0;
              const firstItemStockId = firstItem ? (firstItem.stockItemId ?? firstItem.stock_item_id) : null;
              
              // Icon color mapping
              let iconBg = "bg-orange-50 text-orange-400 border-orange-100";
              if(bon.status === "Ditolak") iconBg = "bg-rose-50 text-rose-400 border-rose-100";
              else if(bon.status === "Draft") iconBg = "bg-indigo-50 text-indigo-400 border-indigo-100";
              else if(bon.status === "Selesai" || bon.status === "Disetujui") iconBg = "bg-emerald-50 text-emerald-400 border-emerald-100";

              return (
                <div key={bon.id} className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all hover:border-indigo-200">
                  <div className="p-4 sm:p-5 flex items-stretch gap-4 sm:gap-6">
                    
                    {/* Left Icon */}
                    <div className={`shrink-0 flex items-center justify-center size-14 sm:size-16 rounded-2xl border ${iconBg}`}>
                      {getIconForCategory(firstItemName || bon.keperluan)}
                    </div>

                    {/* Content */}
                    <div className="flex-1 min-w-0 flex flex-col justify-center">
                      <div className="flex items-center gap-2 mb-1.5 flex-wrap">
                        <span className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{bon.bonNo}</span>
                        <span className="text-slate-300 text-[10px]">•</span>
                        <div className="flex items-center gap-1 text-[10px] font-bold text-slate-400">
                           <Calendar size={10} />
                           {formatDate(bon.date)}
                        </div>
                      </div>
                      
                      <h4 className="text-sm font-extrabold text-slate-800 leading-snug truncate mb-1.5">
                        {firstItemName || bon.keperluan || "Tanpa keperluan"} 
                        {firstItem && <span className="text-slate-400 font-medium"> ({firstItemReq} {firstItem.unit})</span>}
                        {bon.itemsCount > 1 && <span className="text-indigo-500 font-bold ml-1">+{bon.itemsCount - 1} Item</span>}
                      </h4>

                      <div className="flex items-center gap-3 flex-wrap text-xs font-semibold">
                         <span className="text-slate-500">Jumlah Dipenuhi: <span className="text-slate-800">{firstItemFulf} {firstItem?.unit || ''}</span></span>
                         <span className="text-slate-300">|</span>
                         <span className="text-slate-500">Hasil Cek Stok: <span className="text-blue-600">{firstItemStockId ? `${firstItemReq} tersedia di gudang` : "0 tersedia di gudang"}</span></span>
                      </div>
                    </div>

                    {/* Right Actions */}
                    <div className="shrink-0 flex flex-col sm:flex-row items-end sm:items-center justify-center gap-3 border-l border-slate-100 pl-4 sm:pl-6">
                      {isDraft ? (
                         <div className="flex flex-col gap-2 items-end">
                            <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-extrabold border ${cfg.color}`}>
                              {cfg.icon} {cfg.label}
                            </span>
                            <div className="flex gap-2">
                              <button
                                onClick={() => setConfirmDelete({ id: bon.id, bonNo: bon.bonNo })}
                                disabled={deletingId === bon.id}
                                className="flex items-center justify-center size-8 border border-rose-200 text-rose-500 hover:bg-rose-50 hover:border-rose-300 rounded-lg transition-colors"
                                title="Hapus Draft">
                                {deletingId === bon.id ? <Loader2 size={14} className="animate-spin" /> : <Trash size={14} />}
                              </button>
                              <button
                                onClick={() => onEditDraft({
                                  id:        bon.id,
                                  bonNo:     bon.bonNo,
                                  keperluan: bon.keperluan ?? "",
                                  catatan:   bon.catatan   ?? "",
                                  items:     (bon.items ?? []).map((it) => ({
                                    stockItemId:   it.stockItemId ?? it.stock_item_id ?? 0,
                                    namaBarang:    it.itemName ?? it.item_name ?? "",
                                    satuan:        it.unit,
                                    jumlahDiminta: it.qtyRequested ?? it.qty_requested ?? 0,
                                    catatan:       it.notes ?? "",
                                  })),
                                })}
                                className="flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm whitespace-nowrap">
                                <Edit3 size={12} /> Lanjutkan Draft
                              </button>
                            </div>
                         </div>
                      ) : (
                        <>
                          <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-extrabold border ${cfg.color}`}>
                            {cfg.icon} {cfg.label}
                          </span>
                          <button
                            onClick={() => toggleExpand(bon.id)}
                            className="flex items-center justify-center size-9 border border-slate-200 text-slate-400 hover:text-slate-600 hover:border-slate-300 bg-slate-50 hover:bg-slate-100 rounded-xl transition-colors shrink-0">
                            {isExpanded ? <ChevronDown size={18} /> : <ChevronRight size={18} />}
                          </button>
                        </>
                      )}
                    </div>
                  </div>

                  {/* Expandable detail */}
                  {isExpanded && !isDraft && (
                    <div className="border-t border-slate-100 bg-slate-50/50 px-5 sm:px-6 pb-6 pt-5">
                      {(bon.keperluan || bon.catatan) && (
                        <div className="mb-5">
                          <h5 className="text-[10px] font-extrabold text-slate-500 mb-2 uppercase tracking-wider">Informasi Pengajuan</h5>
                          <div className="bg-white border border-slate-200/60 rounded-lg p-3 text-xs shadow-sm">
                            {bon.keperluan && (
                              <div className="mb-2">
                                <span className="block text-slate-400 font-bold mb-0.5">Keperluan / Tujuan:</span>
                                <span className="font-semibold text-slate-700">{bon.keperluan}</span>
                              </div>
                            )}
                            {bon.catatan && (
                              <div className={bon.keperluan ? "pt-2 border-t border-slate-100" : ""}>
                                <span className="block text-slate-400 font-bold mb-0.5">Catatan Tambahan:</span>
                                <span className="text-slate-600">{bon.catatan}</span>
                              </div>
                            )}
                          </div>
                        </div>
                      )}
                      
                      <h5 className="text-[10px] font-extrabold text-slate-500 mb-2 uppercase tracking-wider">Detail Item Barang</h5>

                      {bon.items && bon.items.length > 0 ? (
                        <div className="overflow-x-auto bg-white rounded-xl border border-slate-200 shadow-sm">
                          <table className="w-full text-left text-xs border-collapse">
                            <thead>
                              <tr className="bg-slate-50 border-b border-slate-100 text-slate-400 font-extrabold uppercase tracking-wider text-[10px]">
                                <th className="px-4 py-3">Nama Barang</th>
                                <th className="px-4 py-3 text-right">Diminta</th>
                                <th className="px-4 py-3 text-right">Dipenuhi</th>
                                <th className="px-4 py-3">Status Item</th>
                              </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                              {bon.items.map((it) => {
                                const name = it.itemName || it.item_name;
                                const reqQty = it.qtyRequested ?? it.qty_requested;
                                const fulfQty = it.qtyFulfilled ?? it.qty_fulfilled;
                                return (
                                <tr key={it.id} className="hover:bg-slate-50/50">
                                  <td className="px-4 py-3 font-bold text-slate-800">{name}</td>
                                  <td className="px-4 py-3 text-right font-mono font-semibold text-slate-600">
                                    {reqQty} {it.unit}
                                  </td>
                                  <td className="px-4 py-3 text-right font-mono font-bold text-emerald-600 bg-emerald-50/30">
                                    {fulfQty} {it.unit}
                                  </td>
                                  <td className="px-4 py-3">
                                    <span className={`font-bold ${itemStatusColor[it.status] ?? "text-slate-500"}`}>
                                      {it.status}
                                    </span>
                                  </td>
                                </tr>
                                );
                              })}
                            </tbody>
                          </table>
                        </div>
                      ) : (
                        <p className="text-xs text-slate-400 italic py-2">
                          Data item belum dimuat.
                        </p>
                      )}

                      <p className="text-[10px] font-bold text-slate-400 mt-4 text-right">
                        Terakhir diperbarui: {formatDate(bon.lastUpdated)}
                      </p>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </div>
    </>
  );
};
