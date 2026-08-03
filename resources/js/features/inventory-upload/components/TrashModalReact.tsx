import React, { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { X, Trash2, RefreshCw, AlertCircle, Loader2 } from "lucide-react";
import { apiFetch } from "../../../shared/api";
import { AlertDialog } from "../../../shared/components/feedback/AlertDialog";
import { ConfirmDialog } from "../../../shared/components/feedback/ConfirmDialog";

interface Batch {
  id: number;
  file_name_original: string;
  sheets_count: number;
  rows_count: number;
  upload_date: string;
  status: string;
  deleted_at: string;
  user?: { name: string };
}

interface TrashModalProps {
  onClose: () => void;
  onRestored: () => void;
}

export function TrashModalReact({ onClose, onRestored }: TrashModalProps) {
  const [batches, setBatches] = useState<Batch[]>([]);
  const [loading, setLoading] = useState(true);
  const [processingId, setProcessingId] = useState<number | null>(null);
  
  const [alertDialog, setAlertDialog] = useState<{ title: string; message: string; variant: "success" | "danger" } | null>(null);
  const [confirmDialog, setConfirmDialog] = useState<{ id: number; action: "restore" | "forceDelete"; title: string; message: string } | null>(null);

  useEffect(() => {
    fetchTrash();
  }, []);

  const fetchTrash = () => {
    setLoading(true);
    apiFetch("/stok-upload/sampah", {
      headers: { Accept: "application/json" }
    })
      .then(res => res.json())
      .then(data => {
        setBatches(data.data || data); // handle pagination wrapper or direct array
      })
      .catch(err => {
        setAlertDialog({ title: "Error", message: "Gagal mengambil data sampah.", variant: "danger" });
      })
      .finally(() => setLoading(false));
  };

  const handleAction = async (id: number, action: "restore" | "forceDelete") => {
    setProcessingId(id);
    try {
      const endpoint = action === "restore" ? `/stok-upload/${id}/restore` : `/stok-upload/${id}/force`;
      const method = action === "restore" ? "POST" : "DELETE";
      
      const res = await apiFetch(endpoint, { method });
      if (!res.ok) throw new Error("Aksi gagal dilakukan.");
      
      setBatches(batches.filter(b => b.id !== id));
      if (action === "restore") onRestored();
    } catch (err: any) {
      setAlertDialog({ title: "Error", message: err.message || "Gagal memproses data.", variant: "danger" });
    } finally {
      setProcessingId(null);
    }
  };

  const executeConfirm = () => {
    if (!confirmDialog) return;
    handleAction(confirmDialog.id, confirmDialog.action);
    setConfirmDialog(null);
  };

  const formatDate = (dateString: string) => {
    const d = new Date(dateString);
    return d.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
  };

  return createPortal(
    <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-fade-in">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-5xl flex flex-col max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between p-5 border-b border-slate-200">
          <div className="flex items-center gap-3">
            <div className="flex items-center justify-center size-10 rounded-xl bg-rose-50 text-rose-600">
              <Trash2 size={20} />
            </div>
            <div>
              <h2 className="text-lg font-bold text-slate-900">Recycle Bin Upload Stok</h2>
              <p className="text-xs text-slate-500 mt-0.5">Upload yang dihapus disimpan selama 30 hari sebelum dihapus permanen.</p>
            </div>
          </div>
          <button onClick={onClose} className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
            <X size={20} />
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-5 bg-slate-50/50">
          {loading ? (
            <div className="flex flex-col items-center justify-center py-12 text-slate-500">
              <Loader2 className="animate-spin mb-4" size={24} />
              <p className="text-sm font-medium">Memuat data...</p>
            </div>
          ) : batches.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-16 text-center">
              <div className="size-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
                <Trash2 size={32} />
              </div>
              <h3 className="text-sm font-bold text-slate-900 mb-1">Recycle Bin Kosong</h3>
              <p className="text-sm text-slate-500 max-w-xs">Tidak ada data upload yang dihapus saat ini.</p>
            </div>
          ) : (
            <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr className="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                      <th className="px-5 py-3">File</th>
                      <th className="px-5 py-3">Dihapus Pada</th>
                      <th className="px-5 py-3 text-center">Status Terakhir</th>
                      <th className="px-5 py-3 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {batches.map((batch) => (
                      <tr key={batch.id} className="hover:bg-slate-50/50 transition-colors opacity-80">
                        <td className="px-5 py-4 whitespace-nowrap">
                          <span className="font-semibold text-slate-600 block line-through">{batch.file_name_original}</span>
                          <span className="text-xs text-slate-400 mt-0.5 block">
                            {batch.sheets_count} sheet &bull; {batch.rows_count} baris
                          </span>
                        </td>
                        <td className="px-5 py-4 whitespace-nowrap">
                          <span className="font-semibold text-slate-700 block">{formatDate(batch.deleted_at)}</span>
                          <span className="text-xs text-slate-400 block mt-0.5">Oleh {batch.user?.name || '-'}</span>
                        </td>
                        <td className="px-5 py-4 text-center whitespace-nowrap">
                          <span className="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 uppercase tracking-wider">
                            {batch.status}
                          </span>
                        </td>
                        <td className="px-5 py-4 text-right whitespace-nowrap">
                          <div className="flex justify-end gap-2">
                            <button
                              onClick={() => setConfirmDialog({ id: batch.id, action: "restore", title: "Pulihkan Data", message: `Apakah Anda yakin ingin memulihkan file "${batch.file_name_original}"?` })}
                              disabled={processingId === batch.id}
                              className="px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors disabled:opacity-50 flex items-center gap-1"
                            >
                              {processingId === batch.id && confirmDialog?.action === "restore" ? <Loader2 size={14} className="animate-spin" /> : <RefreshCw size={14} />} Pulihkan
                            </button>
                            <button
                              onClick={() => setConfirmDialog({ id: batch.id, action: "forceDelete", title: "Hapus Permanen", message: `File "${batch.file_name_original}" akan dihapus permanen dan tidak dapat dipulihkan lagi.` })}
                              disabled={processingId === batch.id}
                              className="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors disabled:opacity-50 flex items-center gap-1"
                            >
                              {processingId === batch.id && confirmDialog?.action === "forceDelete" ? <Loader2 size={14} className="animate-spin" /> : <Trash2 size={14} />} Permanen
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      </div>

      {alertDialog && (
        <AlertDialog
          open={true}
          title={alertDialog.title}
          message={alertDialog.message}
          variant={alertDialog.variant}
          onClose={() => setAlertDialog(null)}
        />
      )}

      {confirmDialog && (
        <ConfirmDialog
          open={true}
          title={confirmDialog.title}
          message={confirmDialog.message}
          variant={confirmDialog.action === "restore" ? "info" : "danger"}
          confirmText={confirmDialog.action === "restore" ? "Pulihkan" : "Hapus Permanen"}
          onConfirm={executeConfirm}
          onClose={() => setConfirmDialog(null)}
        />
      )}
    </div>,
    document.body
  );
}
