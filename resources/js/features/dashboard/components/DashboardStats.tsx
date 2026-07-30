import React, { useEffect, useState } from "react";
import { ItemRequest, ReceiptData, RequestStatus } from "../../../shared/types";
import {
  FileText,
  TrendingUp,
  Percent,
  CheckCircle,
  AlertCircle,
  Clock,
} from "lucide-react";

interface DashboardStatsProps {
  requests: ItemRequest[];
  receipts: ReceiptData[];
}

export const DashboardStats: React.FC<DashboardStatsProps> = ({
  requests,
  receipts,
}) => {
  const [excelStats, setExcelStats] = useState({
    total_belanja: 0,
    total_pajak: 0,
    kuitansi_valid: 0,
    pending_verify: 0,
  });

  useEffect(() => {
    fetch("/api/stok-upload/stats")
      .then((res) => res.json())
      .then((data) => setExcelStats(data))
      .catch((err) => console.error("Failed to fetch excel stats", err));
  }, []);
  // Requests stats
  const totalRequests = requests.length;
  const pendingCheck = requests.filter(
    (r) => r.status === RequestStatus.DIAJUKAN
  ).length;
  const inProcurement = requests.filter(
    (r) =>
      r.status === RequestStatus.PERLU_PENGADAAN ||
      r.status === RequestStatus.DALAM_PENGADAAN
  ).length;
  const completed = requests.filter(
    (r) => r.status === RequestStatus.SELESAI
  ).length;

  const totalSpend = excelStats.total_belanja;
  const totalTax = excelStats.total_pajak;
  const validDocsCount = excelStats.kuitansi_valid;

  const formatIDR = (num: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(num);
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
      {/* Total Belanja Kuitansi */}
      <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm transition-all duration-300 hover:shadow-md hover:border-emerald-200 hover:-translate-y-1 flex flex-col justify-between">
        <div className="flex justify-between items-start gap-4">
          <span className="text-xs text-slate-400 font-bold tracking-wider uppercase block leading-snug">
            Total Belanja Terverifikasi
          </span>
          <div className="bg-emerald-50 text-emerald-600 p-2 rounded flex-shrink-0">
            <TrendingUp size={20} />
          </div>
        </div>
        <div className="mt-4">
          <h3 className="text-lg font-extrabold text-slate-900 tracking-tight leading-none">
            {formatIDR(totalSpend)}
          </h3>
          <p className="text-xs text-slate-500 mt-1.5">
            Dari {validDocsCount} dokumen diverifikasi
          </p>
        </div>
      </div>

      {/* Total PPN Disetor */}
      <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm transition-all duration-300 hover:shadow-md hover:border-indigo-200 hover:-translate-y-1 flex flex-col justify-between">
        <div className="flex justify-between items-start gap-4">
          <span className="text-xs text-slate-400 font-bold tracking-wider uppercase block leading-snug">
            Total Pajak (PPN) Disetor
          </span>
          <div className="bg-indigo-50 text-indigo-600 p-2 rounded flex-shrink-0">
            <Percent size={20} />
          </div>
        </div>
        <div className="mt-4">
          <h3 className="text-lg font-extrabold text-slate-900 tracking-tight leading-none">
            {formatIDR(totalTax)}
          </h3>
          <p className="text-xs text-slate-500 mt-1.5">
            Total PPN dari data belanja yang disetujui
          </p>
        </div>
      </div>

      {/* Usulan Selesai */}
      <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm transition-all duration-300 hover:shadow-md hover:border-amber-200 hover:-translate-y-1 flex flex-col justify-between">
        <div className="flex justify-between items-start gap-4">
          <span className="text-xs text-slate-400 font-bold tracking-wider uppercase block leading-snug">
            Pemenuhan Usulan Selesai
          </span>
          <div className="bg-amber-50 text-amber-600 p-2 rounded flex-shrink-0">
            <CheckCircle size={20} />
          </div>
        </div>
        <div className="mt-4">
          <h3 className="text-lg font-extrabold text-slate-900 tracking-tight leading-none">
            {completed} <span className="text-xs font-normal text-slate-400">/ {totalRequests}</span>
          </h3>
          <p className="text-xs text-slate-500 mt-1.5">
            Permintaan didistribusikan
          </p>
        </div>
      </div>

      {/* Status Antrean */}
      <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm transition-all duration-300 hover:shadow-md hover:border-slate-300 hover:-translate-y-1 flex flex-col justify-between">
        <div className="flex justify-between items-start gap-4">
          <span className="text-xs text-slate-400 font-bold tracking-wider uppercase block leading-snug">
            Status Tindakan Petugas
          </span>
          <div className="bg-slate-50 text-slate-500 p-2 rounded flex-shrink-0">
            <Clock size={20} />
          </div>
        </div>
        
        <div className="flex justify-between items-end mt-4">
          <div>
            <h3 className="text-lg font-extrabold text-amber-600 tracking-tight leading-none">
              {pendingCheck}
            </h3>
            <p className="text-[10px] text-slate-500 mt-1.5 uppercase whitespace-nowrap">
              Cek Stok
            </p>
          </div>
          <div className="w-px h-6 bg-slate-200 self-center" />
          <div>
            <h3 className="text-lg font-extrabold text-indigo-600 tracking-tight leading-none">
              {inProcurement}
            </h3>
            <p className="text-[10px] text-slate-500 mt-1.5 uppercase whitespace-nowrap">
              Pengadaan
            </p>
          </div>
          <div className="w-px h-6 bg-slate-200 self-center" />
          <div>
            <h3 className="text-lg font-extrabold text-rose-500 tracking-tight leading-none">
              {excelStats.pending_verify}
            </h3>
            <p className="text-[10px] text-slate-500 mt-1.5 uppercase whitespace-nowrap">
              Verifikasi
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};
