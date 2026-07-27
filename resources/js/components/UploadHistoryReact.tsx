import React, { useEffect, useState } from "react";
import { FileSpreadsheet, AlertCircle, CheckCircle2, History, ArrowRight } from "lucide-react";

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
}

export const UploadHistoryReact: React.FC<{ filterPending?: boolean }> = ({ filterPending = false }) => {
  const [batches, setBatches] = useState<StokUpload[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
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

  return (
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
      </div>

      <div className="overflow-x-auto">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-xs">
              <th className="px-5 py-3">Tanggal Upload</th>
              <th className="px-5 py-3">File</th>
              <th className="px-5 py-3">Diupload Oleh</th>
              <th className="px-5 py-3 text-center">Status</th>
              <th className="px-5 py-3 text-right">Statistik</th>
              <th className="px-5 py-3 text-right">Aksi</th>
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
                  <td className="px-5 py-4 whitespace-nowrap">
                    <span className="font-semibold text-slate-800">
                      {new Date(batch.upload_date || batch.created_at || "").toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                    </span>
                  </td>
                  <td className="px-5 py-4">
                    <span className="font-semibold text-slate-800 block max-w-[220px] truncate" title={batch.file_name_original}>
                      {batch.file_name_original}
                    </span>
                    <span className="text-xs text-slate-400 mt-0.5 block">
                      {batch.sheets_count} sheet &bull; {batch.rows_count} baris
                    </span>
                  </td>
                  <td className="px-5 py-4 whitespace-nowrap text-slate-600">
                    {batch.user?.name ?? '—'}
                  </td>
                  <td className="px-5 py-4 text-center whitespace-nowrap">
                    <span className={`px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded-full uppercase tracking-wider ${getStatusColor(batch.status)}`}>
                      {batch.status}
                    </span>
                  </td>
                  <td className="px-5 py-4 text-right whitespace-nowrap leading-snug">
                    <span className="text-emerald-600 font-semibold block">Valid: {batch.valid_rows_count}</span>
                    <span className="text-rose-600 font-semibold block">Error: {batch.error_rows_count}</span>
                  </td>
                  <td className="px-5 py-4 whitespace-nowrap text-right">
                    <a href={`/stok-upload/${batch.id}/stepper`} className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                      Buka
                      <ArrowRight size={14} />
                    </a>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};
