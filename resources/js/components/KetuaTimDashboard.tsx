import React from "react";
import { ItemRequest, RequestStatus } from "../types";
import {
  FileText,
  Clock,
  RefreshCw,
  CheckCircle2,
  XCircle,
  FileCheck,
  AlertCircle,
  Inbox,
  Building,
  Edit3,
} from "lucide-react";

interface KetuaTimDashboardProps {
  requests: ItemRequest[];
  loading: boolean;
  error: string | null;
  onRefresh: () => Promise<void>;
  currentUser: string;
  /** Called when user clicks Lanjutkan Draft on a draft row */
  onEditDraft?: (bonNo: string) => void;
}

export const KetuaTimDashboard: React.FC<KetuaTimDashboardProps> = ({
  requests,
  loading,
  error,
  onRefresh,
  currentUser,
  onEditDraft,
}) => {
  // Extract user section from currentUser string (e.g., "Budi Santoso (Ketua Tim TU)" -> "Tata Usaha" or similar)
  const sectionName = requests.length > 0 ? requests[0].section : "Unit Kerja Anda";

  // Calculate statistics based on status
  const totalCount = requests.length;
  const draftCount = requests.filter((r) => r.status === ("Draft" as any)).length;
  const pendingCount = requests.filter(
    (r) => r.status === RequestStatus.DIAJUKAN || r.status === RequestStatus.DICEK
  ).length;
  const processedCount = requests.filter(
    (r) =>
      r.status === RequestStatus.TERPENUHI_SEBAGIAN ||
      r.status === RequestStatus.PERLU_PENGADAAN ||
      r.status === RequestStatus.DALAM_PENGADAAN
  ).length;
  const approvedCount = requests.filter(
    (r) => r.status === RequestStatus.TERPENUHI || r.status === RequestStatus.SIAP_DIDISTRIBUSIKAN
  ).length;
  const rejectedCount = requests.filter((r) => r.status === RequestStatus.DITOLAK).length;
  const completedCount = requests.filter((r) => r.status === RequestStatus.SELESAI).length;

  const getStatusBadgeClass = (status: RequestStatus) => {
    switch (status) {
      case RequestStatus.SELESAI:
        return "bg-emerald-50 text-emerald-800 border-emerald-200";
      case RequestStatus.TERPENUHI:
      case RequestStatus.SIAP_DIDISTRIBUSIKAN:
        return "bg-teal-50 text-teal-800 border-teal-200";
      case RequestStatus.DIAJUKAN:
        return "bg-amber-50 text-amber-800 border-amber-300";
      case RequestStatus.DICEK:
        return "bg-sky-50 text-sky-800 border-sky-200";
      case RequestStatus.TERPENUHI_SEBAGIAN:
      case RequestStatus.DALAM_PENGADAAN:
      case RequestStatus.PERLU_PENGADAAN:
        return "bg-orange-50 text-orange-800 border-orange-200";
      case RequestStatus.DITOLAK:
        return "bg-rose-50 text-rose-800 border-rose-200";
      default:
        return "bg-slate-50 text-slate-800 border-slate-200";
    }
  };

  return (
    <div className="space-y-6">
      {/* Welcome Banner */}
      <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4 relative overflow-hidden">
        <div className="absolute right-0 top-0 w-32 h-32 bg-indigo-50 rounded-full -mr-8 -mt-8 opacity-50 blur-xl"></div>
        <div className="relative z-10 space-y-1">
          <div className="flex items-center gap-2">
            <span className="bg-indigo-50 text-indigo-700 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider">
              Dashboard Ketua Tim
            </span>
          </div>
          <h1 className="text-xl font-extrabold text-slate-800 tracking-tight">
            Selamat Datang Kembali, {currentUser.split(" (")[0]}
          </h1>
          <p className="text-xs text-slate-500 flex items-center gap-1.5 font-medium">
            <Building size={13} className="text-slate-400" />
            Unit Kerja: <span className="font-semibold text-slate-700">{sectionName}</span>
          </p>
        </div>
        <button
          onClick={onRefresh}
          disabled={loading}
          className="self-start md:self-auto flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 disabled:opacity-50 px-4 py-2 rounded-xl text-xs font-bold transition-all border border-slate-200 hover:shadow-xs active:scale-95"
        >
          <RefreshCw size={14} className={loading ? "animate-spin" : ""} />
          Refresh Data
        </button>
      </div>

      {/* Error State */}
      {error && (
        <div className="bg-rose-50 border border-rose-200 rounded-xl p-4 flex items-start gap-3 text-rose-800 text-xs font-medium animate-fade-in shadow-xs">
          <AlertCircle className="text-rose-600 flex-shrink-0" size={16} />
          <div className="flex-1">
            <p className="font-bold">Gagal memuat data dashboard</p>
            <p className="text-xs text-rose-600/80 mt-0.5">{error}</p>
          </div>
          <button
            onClick={onRefresh}
            className="bg-white text-rose-800 border border-rose-200 hover:bg-rose-100/50 px-3 py-1 rounded-lg text-xs font-bold transition-colors"
          >
            Coba Lagi
          </button>
        </div>
      )}

      {/* Statistics Grid */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        {/* Total Pengajuan */}
        <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between transition-all hover:border-indigo-300 hover:shadow-sm">
          <span className="text-2xs text-slate-400 font-bold tracking-wider uppercase block">
            Total Pengajuan
          </span>
          <div className="flex items-baseline gap-1 mt-3">
            <h3 className="text-2xl font-extrabold text-slate-900 tracking-tight">
              {loading ? "..." : totalCount}
            </h3>
            <span className="text-xs text-slate-500 font-semibold">berkas</span>
          </div>
          <div className="mt-3 flex items-center gap-1.5 text-indigo-600 bg-indigo-50 p-1.5 rounded-lg w-fit">
            <FileText size={13} />
          </div>
        </div>

        {/* Pengajuan Draft */}
        <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between transition-all hover:border-slate-300 hover:shadow-sm">
          <span className="text-2xs text-slate-400 font-bold tracking-wider uppercase block">
            Pengajuan Draft
          </span>
          <div className="flex items-baseline gap-1 mt-3">
            <h3 className="text-2xl font-extrabold text-slate-900 tracking-tight">
              {loading ? "..." : draftCount}
            </h3>
            <span className="text-xs text-slate-500 font-semibold">berkas</span>
          </div>
          <div className="mt-3 flex items-center gap-1.5 text-slate-500 bg-slate-50 p-1.5 rounded-lg w-fit">
            <FileText size={13} />
          </div>
        </div>

        {/* Menunggu Verifikasi */}
        <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between transition-all hover:border-amber-300 hover:shadow-sm">
          <span className="text-2xs text-slate-400 font-bold tracking-wider uppercase block">
            Menunggu Verif
          </span>
          <div className="flex items-baseline gap-1 mt-3">
            <h3 className="text-2xl font-extrabold text-slate-900 tracking-tight">
              {loading ? "..." : pendingCount}
            </h3>
            <span className="text-xs text-slate-500 font-semibold">berkas</span>
          </div>
          <div className="mt-3 flex items-center gap-1.5 text-amber-600 bg-amber-50 p-1.5 rounded-lg w-fit">
            <Clock size={13} />
          </div>
        </div>

        {/* Diproses */}
        <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between transition-all hover:border-orange-300 hover:shadow-sm">
          <span className="text-2xs text-slate-400 font-bold tracking-wider uppercase block">
            Pengajuan Diproses
          </span>
          <div className="flex items-baseline gap-1 mt-3">
            <h3 className="text-2xl font-extrabold text-slate-900 tracking-tight">
              {loading ? "..." : processedCount}
            </h3>
            <span className="text-xs text-slate-500 font-semibold">berkas</span>
          </div>
          <div className="mt-3 flex items-center gap-1.5 text-orange-600 bg-orange-50 p-1.5 rounded-lg w-fit">
            <RefreshCw size={13} />
          </div>
        </div>

        {/* Disetujui */}
        <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between transition-all hover:border-teal-300 hover:shadow-sm">
          <span className="text-2xs text-slate-400 font-bold tracking-wider uppercase block">
            Pengajuan Disetujui
          </span>
          <div className="flex items-baseline gap-1 mt-3">
            <h3 className="text-2xl font-extrabold text-slate-900 tracking-tight">
              {loading ? "..." : approvedCount}
            </h3>
            <span className="text-xs text-slate-500 font-semibold">berkas</span>
          </div>
          <div className="mt-3 flex items-center gap-1.5 text-teal-600 bg-teal-50 p-1.5 rounded-lg w-fit">
            <FileCheck size={13} />
          </div>
        </div>

        {/* Ditolak */}
        <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between transition-all hover:border-rose-300 hover:shadow-sm">
          <span className="text-2xs text-slate-400 font-bold tracking-wider uppercase block">
            Pengajuan Ditolak
          </span>
          <div className="flex items-baseline gap-1 mt-3">
            <h3 className="text-2xl font-extrabold text-slate-900 tracking-tight">
              {loading ? "..." : rejectedCount}
            </h3>
            <span className="text-xs text-slate-500 font-semibold">berkas</span>
          </div>
          <div className="mt-3 flex items-center gap-1.5 text-rose-600 bg-rose-50 p-1.5 rounded-lg w-fit">
            <XCircle size={13} />
          </div>
        </div>

        {/* Selesai */}
        <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between transition-all hover:border-emerald-300 hover:shadow-sm">
          <span className="text-2xs text-slate-400 font-bold tracking-wider uppercase block">
            Pengajuan Selesai
          </span>
          <div className="flex items-baseline gap-1 mt-3">
            <h3 className="text-2xl font-extrabold text-slate-900 tracking-tight">
              {loading ? "..." : completedCount}
            </h3>
            <span className="text-xs text-slate-500 font-semibold">berkas</span>
          </div>
          <div className="mt-3 flex items-center gap-1.5 text-emerald-600 bg-emerald-50 p-1.5 rounded-lg w-fit">
            <CheckCircle2 size={13} />
          </div>
        </div>
      </div>

      {/* Main Section - Recent Requests */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div className="p-5 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h2 className="text-base font-extrabold text-slate-800 uppercase tracking-wider">
              Daftar Pengajuan Terbaru
            </h2>
            <p className="text-xs text-slate-500 mt-1 font-medium">
              Riwayat usulan kebutuhan barang persediaan yang diajukan oleh seksi Anda
            </p>
          </div>
          <span className="bg-slate-100 text-slate-700 px-3 py-1 rounded-full text-xs font-bold">
            {requests.length} Pengajuan
          </span>
        </div>

        {/* Loading State */}
        {loading && requests.length === 0 ? (
          <div className="p-12 text-center text-slate-500">
            <div className="flex flex-col items-center justify-center gap-3">
              <RefreshCw size={24} className="text-indigo-600 animate-spin" />
              <span className="text-xs font-semibold">Sedang memuat data dari database...</span>
            </div>
          </div>
        ) : requests.length === 0 ? (
          /* Empty State */
          <div className="p-16 text-center text-slate-500">
            <div className="flex flex-col items-center justify-center gap-3 max-w-sm mx-auto">
              <div className="bg-slate-50 p-4 rounded-full border border-slate-150">
                <Inbox size={32} className="text-slate-400" />
              </div>
              <h3 className="text-sm font-bold text-slate-800 mt-2">Belum ada pengajuan</h3>
              <p className="text-sm text-slate-500 leading-relaxed font-medium">
                Unit kerja Anda belum mengirimkan usulan kebutuhan barang. Silakan ajukan melalui menu **BON Digital**.
              </p>
            </div>
          </div>
        ) : (
          /* Data List */
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-slate-50/75 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                  <th className="py-3.5 px-5">No. BON / Tanggal</th>
                  <th className="py-3.5 px-5">Nama Barang</th>
                  <th className="py-3.5 px-5">Jumlah Permintaan</th>
                  <th className="py-3.5 px-5">Jumlah Dipenuhi</th>
                  <th className="py-3.5 px-5">Status</th>
                  <th className="py-3.5 px-5">Catatan / Detail</th>
                  <th className="py-3.5 px-5">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                {requests.map((req) => (
                  <tr key={req.id} className="hover:bg-slate-50/50 transition-colors">
                    <td className="py-4 px-5">
                      <span className="font-mono font-bold text-slate-800 block">{req.bonNo}</span>
                      <span className="text-xs text-slate-400 block mt-0.5">{req.date}</span>
                    </td>
                    <td className="py-4 px-5">
                      <span className="font-bold text-slate-800">{req.itemName}</span>
                    </td>
                    <td className="py-4 px-5">
                      <span>{req.qtyRequested} {req.unit}</span>
                    </td>
                    <td className="py-4 px-5">
                      {req.qtyFulfilled > 0 ? (
                        <span className="text-emerald-600 font-bold">{req.qtyFulfilled} {req.unit}</span>
                      ) : (
                        <span className="text-slate-400">-</span>
                      )}
                    </td>
                    <td className="py-4 px-5">
                      <span className={`px-2.5 py-0.5 rounded text-xs font-extrabold border ${getStatusBadgeClass(req.status)}`}>
                        {req.status}
                      </span>
                    </td>
                    <td className="py-4 px-5 max-w-xs truncate">
                      <span className="text-sm text-slate-500 block truncate" title={req.notes || "Tidak ada catatan"}>
                        {req.notes || "-"}
                      </span>
                      {req.procurementMethod && (
                        <span className="text-2xs text-indigo-600 font-bold block mt-1">
                          {req.procurementMethod} {req.vendorName ? `• ${req.vendorName}` : ""}
                        </span>
                      )}
                    </td>
                    <td className="py-4 px-5 whitespace-nowrap">
                      {(req.status as string) === "Draft" && onEditDraft ? (
                        <button
                          onClick={() => onEditDraft(req.bonNo)}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors"
                        >
                          <Edit3 size={11} />
                          Lanjutkan Draft
                        </button>
                      ) : (
                        <span className="text-slate-300 text-sm">—</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};
