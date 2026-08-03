import React, { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { FileSpreadsheet, AlertCircle, CheckCircle2, History, ArrowRight, Trash2 } from "lucide-react";
import { AlertDialog } from "../../../shared/components/feedback/AlertDialog";
import { ConfirmDialog } from "../../../shared/components/feedback/ConfirmDialog";
import { apiFetch } from "../../../shared/api";
import { TrashModalReact } from "./TrashModalReact";

interface User {
  id: number;
  name: string;
}

interface StokUpload {
  id: number;
  file_name_original: string;
  upload_date: string;
  sheets_count: number;
  rows_count: number;
  valid_rows_count: number;
  error_rows_count: number;
  rejected_rows_count: number;
  status: string;
  user?: User;
  cancelled_at?: string;
  created_at?: string;
}

export const UploadHistoryReact: React.FC<{ filterPending?: boolean; onOpenStepper?: (id: number) => void }> = ({ filterPending = false, onOpenStepper }) => {
  const [batches, setBatches] = useState<StokUpload[]>([]);
  const [loading, setLoading] = useState(true);
  const [showTrashModal, setShowTrashModal] = useState(false);
  const [deleteConfirm, setDeleteConfirm] = useState<{id: number, name: string} | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);
  const [alertDialog, setAlertDialog] = useState<{ title: string; message: string; variant: "danger" | "warning" | "info" | "success" } | null>(null);

  const fetchRiwayat = () => {
    setLoading(true);
    fetch("/api/stok-upload/riwayat", {
      headers: {
        "Accept": "application/json",
      },
    })
      .then((res) => res.json())
      .then((data) => {
        let fetchedBatches = data.data || [];
        if (filterPending) {
          fetchedBatches = fetchedBatches.filter(
            (b: StokUpload) => b.status === "Menunggu Verifikasi" || b.status === "Siap Difinalisasi"
          );
        }
        setBatches(fetchedBatches);
        setLoading(false);
      })
      .catch((err) => {
        console.error("Failed to fetch riwayat", err);
        setLoading(false);
      });
  };

  useEffect(() => {
    fetchRiwayat();
  }, [filterPending]);

  const getStatusColor = (status: string) => {
    switch (status) {
      case "Draft": return "bg-slate-100 text-slate-700";
      case "Perlu Perbaikan": return "bg-rose-100 text-rose-800";
      case "Menunggu Verifikasi": return "bg-amber-100 text-amber-800";
      case "Siap Difinalisasi": return "bg-indigo-100 text-indigo-800";
      case "Selesai": return "bg-emerald-100 text-emerald-800";
      case "Dibatalkan": return "bg-gray-200 text-gray-500";
      default: return "bg-slate-100 text-slate-600";
    }
  };

  const getCsrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? "";

  const handleDelete = (id: number, name: string) => {
    setDeleteConfirm({ id, name });
  };

  const confirmDelete = () => {
    if (!deleteConfirm) return;
    setIsDeleting(true);
    fetch(`/stok-upload/${deleteConfirm.id}`, {
      method: "DELETE",
      headers: {
        "X-CSRF-TOKEN": getCsrfToken(),
        "Accept": "application/json"
      }
    }).then(res => {
      // Re-fetch after delete
      fetchRiwayat();
      setDeleteConfirm(null);
      setIsDeleting(false);
    }).catch(err => {
      setAlertDialog({ title: "Gagal", message: err.message || "Gagal menghapus data.", variant: "danger" });
      setIsDeleting(false);
    });
  };

  return (
    <>
      <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden animate-fade-in">
        <div className="flex flex-col gap-4 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-3">
            <div className="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
              <History size={18} />
            </div>
            <div>
              <h3 className="text-sm font-extrabold text-slate-900">
                {filterPending ? "Antrean Verifikasi & Finalisasi" : "Riwayat Upload"}
              </h3>
              <p className="mt-0.5 text-xs text-slate-500">
                {filterPending 
                  ? "Daftar file Excel yang menunggu verifikasi kode atau siap difinalisasi" 
                  : "Pantau status pemeriksaan dan verifikasi"}
              </p>
            </div>
          </div>
          
          <button
            onClick={() => setShowTrashModal(true)}
            className="flex items-center gap-2 rounded-xl bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-600 transition-all hover:bg-rose-100 hover:text-rose-700"
          >
            <Trash2 size={16} />
            Recycle Bin
          </button>
        </div>

      <div className="overflow-x-auto">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-xs">
              <th className="px-6 py-4">Tanggal Upload</th>
              <th className="px-6 py-4">File</th>
              <th className="px-6 py-4">Diupload Oleh</th>
              <th className="px-6 py-4 text-center">Status</th>
              <th className="px-6 py-4 text-center">Statistik</th>
              <th className="px-6 py-4 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {loading ? (
              <tr>
                <td colSpan={6} className="px-5 py-14 text-center">
                  <div className="animate-pulse flex flex-col items-center">
                    <div className="h-8 w-8 bg-slate-200 rounded-full mb-3"></div>
                    <div className="h-4 w-32 bg-slate-200 rounded mb-1"></div>
                  </div>
                </td>
              </tr>
            ) : batches.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-5 py-14 text-center">
                  <div className="flex flex-col items-center gap-2 text-slate-400">
                    <History size={38} className="text-slate-200" strokeWidth={1.5} />
                    <p className="text-sm font-semibold">Belum ada riwayat upload.</p>
                  </div>
                </td>
              </tr>
            ) : (
              batches.map((batch) => (
                <tr key={batch.id} className="hover:bg-slate-50/50 transition-colors">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className="font-semibold text-slate-800">
                      {new Date(batch.upload_date || batch.created_at || "").toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <span className="font-semibold text-slate-800 block max-w-[220px] truncate" title={batch.file_name_original}>
                      {batch.file_name_original}
                    </span>
                    <span className="text-xs text-slate-400 mt-0.5 block">
                      {batch.sheets_count} sheet &bull; {batch.rows_count} baris
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-slate-600">
                    {batch.user?.name ?? '—'}
                  </td>
                  <td className="px-6 py-4 text-center whitespace-nowrap">
                    <span className={`px-3 py-1.5 inline-flex text-xs leading-4 font-bold rounded-full uppercase tracking-wider ${getStatusColor(batch.status)}`}>
                      {batch.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-center whitespace-nowrap leading-snug">
                    <span className="text-emerald-600 font-semibold block">Valid: {batch.valid_rows_count}</span>
                    <span className="text-rose-600 font-semibold block">Error: {batch.error_rows_count}</span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    <div className="flex items-center justify-center gap-2">
                      <button 
                        onClick={() => onOpenStepper ? onOpenStepper(batch.id) : window.location.href = `/stok-upload/${batch.id}/stepper`}
                        className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors shadow-sm"
                      >
                        Buka
                        <ArrowRight size={14} />
                      </button>
                      <button 
                        onClick={() => handleDelete(batch.id, batch.file_name_original)}
                        className="inline-flex items-center justify-center p-2 rounded-lg text-rose-500 bg-rose-50 hover:bg-rose-100 hover:text-rose-600 transition-colors shadow-sm"
                        title="Hapus"
                      >
                        <Trash2 size={16} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>

      {alertDialog && (
        <AlertDialog
          open={!!alertDialog}
          title={alertDialog.title}
          message={alertDialog.message}
          variant={alertDialog.variant}
          onClose={() => setAlertDialog(null)}
        />
      )}

      {deleteConfirm && typeof document !== 'undefined' && createPortal(
        <div 
          className="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
          style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0 }}
        >
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center transform animate-in zoom-in-95 duration-200">
            <div className="w-16 h-16 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-white shadow-sm">
              <Trash2 size={32} strokeWidth={2.5} />
            </div>
            <h3 className="text-lg font-extrabold text-slate-800 tracking-tight">Hapus Riwayat?</h3>
            <p className="text-sm text-slate-500 mt-2 leading-relaxed">
              Anda yakin ingin menghapus data <strong>"{deleteConfirm.name}"</strong>? 
              Stok yang sudah berhasil masuk tidak akan terpengaruh.
            </p>
            <div className="mt-6 flex gap-3">
              <button 
                onClick={() => setDeleteConfirm(null)}
                disabled={isDeleting}
                className="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold text-sm rounded-xl hover:bg-slate-200 active:scale-95 transition-all shadow-sm disabled:opacity-50"
              >
                Batal
              </button>
              <button 
                onClick={confirmDelete}
                disabled={isDeleting}
                className="flex-1 py-2.5 bg-rose-600 text-white font-bold text-sm rounded-xl hover:bg-rose-700 active:scale-95 transition-all shadow-md disabled:opacity-70 flex items-center justify-center"
              >
                {isDeleting ? <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> : "Ya, Hapus"}
              </button>
            </div>
          </div>
        </div>,
        document.body
      )}

      {showTrashModal && (
        <TrashModalReact 
          onClose={() => setShowTrashModal(false)} 
          onRestored={() => {
            fetchRiwayat();
            setShowTrashModal(false);
          }} 
        />
      )}
    </>
  );
};

