/**
 * BonMonitoringList.tsx
 *
 * Menampilkan daftar BonHeader milik Ketua Tim beserta status, jumlah item,
 * dan tombol aksi yang sesuai status:
 *
 *  Draft             → Lanjutkan Draft (buka form edit) | Hapus Draft
 *  Menunggu Verifikasi / Diproses / Disetujui → Lihat Detail
 *  Ditolak           → Lihat Detail
 *  Selesai           → Lihat Detail
 */

import React, { useState } from "react";
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
  itemName:     string;
  unit:         string;
  qtyRequested: number;
  qtyFulfilled: number;
  status:       string;
  notes:        string | null;
  stockItemId:  number | null;
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
  Draft:               { label: "Draft",               color: "bg-slate-100 text-slate-700 border-slate-200",   icon: <Edit3 size={10} /> },
  "Menunggu Verifikasi":{ label: "Menunggu Verifikasi", color: "bg-amber-50 text-amber-800 border-amber-200",    icon: <Clock size={10} /> },
  Diproses:            { label: "Diproses",             color: "bg-indigo-50 text-indigo-800 border-indigo-200", icon: <Clock size={10} /> },
  Disetujui:           { label: "Disetujui",            color: "bg-blue-50 text-blue-800 border-blue-200",       icon: <CheckCircle size={10} /> },
  Ditolak:             { label: "Ditolak",              color: "bg-rose-50 text-rose-800 border-rose-200",       icon: <XCircle size={10} /> },
  Selesai:             { label: "Selesai",              color: "bg-emerald-50 text-emerald-800 border-emerald-200", icon: <CheckCircle size={10} /> },
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
  return new Date(d).toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
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

      <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm space-y-5">

        {/* Header */}
        <div className="flex items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <div className="bg-amber-50 text-amber-600 p-2 rounded border border-amber-100 shrink-0">
              <ClipboardList size={18} />
            </div>
            <div>
              <h2 className="text-lg font-semibold leading-7 text-slate-900">Monitoring Pengajuan BON</h2>
              <p className="text-sm font-normal leading-5 text-slate-500 mt-0.5">Pantau status, lanjutkan draft, atau lihat detail pengajuan</p>
            </div>
          </div>
          <button onClick={onRefresh}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-500 hover:bg-slate-50 transition-colors">
            <RefreshCw size={12} /> Refresh
          </button>
        </div>

        {/* BON list */}
        {bons.length === 0 ? (
          <div className="border-2 border-dashed border-slate-200 rounded-lg py-12 text-center">
            <Package className="mx-auto text-slate-300 mb-2" size={28} />
            <p className="text-sm font-bold text-slate-500">Belum ada pengajuan BON</p>
            <p className="text-xs text-slate-400 mt-0.5">
              Buat pengajuan baru melalui tab <strong>BON Digital</strong>
            </p>
          </div>
        ) : (
          <div className="space-y-3">
            {bons.map((bon) => {
              const cfg       = statusConfig[bon.status] ?? statusConfig["Draft"];
              const isExpanded = expandedId === bon.id;
              const isDraft    = bon.status === "Draft";

              return (
                <div key={bon.id}
                  className={`rounded-xl border transition-all
                    ${isDraft ? "border-amber-200 bg-amber-50/20" : "border-slate-200 bg-white"}
                    ${isExpanded ? "shadow-sm" : ""}`}>

                  {/* Row header */}
                  <div className="p-4 flex flex-col sm:flex-row sm:items-center gap-3">

                    {/* Left: BON info */}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 flex-wrap mb-1">
                        <span className="font-mono text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                          {bon.bonNo}
                        </span>
                        <span className="text-slate-300">•</span>
                        <span className="text-xs text-slate-400">{formatDate(bon.date)}</span>
                        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold border ${cfg.color}`}>
                          {cfg.icon} {cfg.label}
                        </span>
                      </div>
                      <p className="text-sm font-extrabold text-slate-800 leading-snug truncate">
                        {bon.keperluan || <span className="italic text-slate-400">Tanpa keperluan</span>}
                      </p>
                      <p className="text-xs text-slate-400 mt-0.5">
                        {bon.itemsCount} jenis barang &bull; {bon.section}
                      </p>
                    </div>

                    {/* Right: Actions */}
                    <div className="flex items-center gap-2 flex-wrap shrink-0">
                      {isDraft ? (
                        <>
                          <button
                            onClick={() => onEditDraft({
                              id:        bon.id,
                              bonNo:     bon.bonNo,
                              keperluan: bon.keperluan ?? "",
                              catatan:   bon.catatan   ?? "",
                              items:     (bon.items ?? []).map((it) => ({
                                stockItemId:   it.stockItemId ?? 0,
                                namaBarang:    it.itemName,
                                satuan:        it.unit,
                                jumlahDiminta: it.qtyRequested,
                                catatan:       it.notes ?? "",
                              })),
                            })}
                            className="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors">
                            <Edit3 size={12} /> Lanjutkan Draft
                          </button>
                          <button
                            onClick={() => setConfirmDelete({ id: bon.id, bonNo: bon.bonNo })}
                            disabled={deletingId === bon.id}
                            className="flex items-center gap-1.5 px-3 py-1.5 border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-bold rounded-lg transition-colors disabled:opacity-50">
                            {deletingId === bon.id
                              ? <Loader2 size={12} className="animate-spin" />
                              : <Trash2 size={12} />}
                            Hapus
                          </button>
                        </>
                      ) : (
                        <button
                          onClick={() => toggleExpand(bon.id)}
                          className="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold rounded-lg transition-colors">
                          <Eye size={12} />
                          {isExpanded ? "Sembunyikan" : "Lihat Detail"}
                          {isExpanded ? <ChevronUp size={12} /> : <ChevronDown size={12} />}
                        </button>
                      )}
                    </div>
                  </div>

                  {/* Expandable detail */}
                  {isExpanded && (
                    <div className="border-t border-slate-100 px-4 pb-4 pt-3 space-y-3">
                      {bon.catatan && (
                        <p className="text-xs text-slate-500 italic border-l-2 border-slate-200 pl-3">
                          {bon.catatan}
                        </p>
                      )}

                      {bon.items && bon.items.length > 0 ? (
                        <div className="overflow-x-auto">
                          <table className="w-full text-left text-xs border-collapse">
                            <thead>
                              <tr className="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-xs border-b border-slate-200">
                                <th className="px-3 py-2">Nama Barang</th>
                                <th className="px-3 py-2 text-right">Diminta</th>
                                <th className="px-3 py-2 text-right">Dipenuhi</th>
                                <th className="px-3 py-2">Status</th>
                              </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                              {bon.items.map((it) => (
                                <tr key={it.id} className="hover:bg-slate-50/50">
                                  <td className="px-3 py-2 font-semibold text-slate-800">{it.itemName}</td>
                                  <td className="px-3 py-2 text-right font-mono text-slate-600">
                                    {it.qtyRequested} {it.unit}
                                  </td>
                                  <td className="px-3 py-2 text-right font-mono font-bold text-emerald-600">
                                    {it.qtyFulfilled} {it.unit}
                                  </td>
                                  <td className="px-3 py-2">
                                    <span className={`font-bold ${itemStatusColor[it.status] ?? "text-slate-500"}`}>
                                      {it.status}
                                    </span>
                                  </td>
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>
                      ) : (
                        <p className="text-xs text-slate-400 italic text-center py-2">
                          Data item belum dimuat.
                        </p>
                      )}

                      <p className="text-xs text-slate-400 text-right">
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
