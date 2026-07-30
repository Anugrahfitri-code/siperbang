import React from "react";
import { ItemRequest, ReceiptData, RequestStatus } from "../types";
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

  // Receipts stats (only verified ones count toward official accounting)
  const verifiedReceipts = receipts.filter((r) => r.isVerified);
  const totalSpend = verifiedReceipts.reduce((sum, r) => sum + r.total, 0);
  const totalTax = verifiedReceipts.reduce((sum, r) => sum + r.taxAmount, 0);

  const formatIDR = (num: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(num);
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      {/* Total Belanja Kuitansi */}
      <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm flex items-center justify-between transition-all hover:border-slate-300">
        <div>
          <span className="text-xs text-slate-400 font-bold tracking-wider uppercase block">
            Total Belanja Terverifikasi
          </span>
          <h3 className="text-lg font-extrabold text-slate-900 mt-1.5 tracking-tight">
            {formatIDR(totalSpend)}
          </h3>
          <p className="text-xs text-slate-500 mt-1">
            Dari {verifiedReceipts.length} kuitansi valid
          </p>
        </div>
        <div className="bg-emerald-50 text-emerald-600 p-3.5 rounded">
          <TrendingUp size={20} />
        </div>
      </div>

      {/* Total PPN Disetor */}
      <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm flex items-center justify-between transition-all hover:border-slate-300">
        <div>
          <span className="text-xs text-slate-400 font-bold tracking-wider uppercase block">
            Total Pajak (PPN) Disetor
          </span>
          <h3 className="text-lg font-extrabold text-slate-900 mt-1.5 tracking-tight">
            {formatIDR(totalTax)}
          </h3>
          <p className="text-xs text-slate-500 mt-1">
            Akumulasi penyesuaian toko
          </p>
        </div>
        <div className="bg-indigo-50 text-indigo-600 p-3.5 rounded">
          <Percent size={20} />
        </div>
      </div>

      {/* Usulan Selesai */}
      <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm flex items-center justify-between transition-all hover:border-slate-300">
        <div>
          <span className="text-xs text-slate-400 font-bold tracking-wider uppercase block">
            Pemenuhan Usulan Selesai
          </span>
          <h3 className="text-lg font-extrabold text-slate-900 mt-1.5 tracking-tight">
            {completed} <span className="text-xs font-normal text-slate-400">/ {totalRequests}</span>
          </h3>
          <p className="text-xs text-slate-500 mt-1">
            Permintaan didistribusikan
          </p>
        </div>
        <div className="bg-amber-50 text-amber-600 p-3.5 rounded">
          <CheckCircle size={20} />
        </div>
      </div>

      {/* Status Antrean */}
      <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm flex items-center justify-between transition-all hover:border-slate-300">
        <div>
          <span className="text-xs text-slate-400 font-bold tracking-wider uppercase block">
            Status Tindakan Petugas
          </span>
          <div className="flex gap-3.5 mt-2.5">
            <div>
              <span className="text-xs font-extrabold text-amber-600 block leading-tight">
                {pendingCheck}
              </span>
              <span className="text-2xs text-slate-400 font-bold uppercase tracking-wider">Cek Stok</span>
            </div>
            <div className="border-r border-slate-200 h-6 self-center" />
            <div>
              <span className="text-xs font-extrabold text-indigo-600 block leading-tight">
                {inProcurement}
              </span>
              <span className="text-2xs text-slate-400 font-bold uppercase tracking-wider">Pengadaan</span>
            </div>
            <div className="border-r border-slate-200 h-6 self-center" />
            <div>
              <span className="text-xs font-extrabold text-rose-500 block leading-tight">
                {receipts.filter((r) => !r.isVerified).length}
              </span>
              <span className="text-2xs text-slate-400 font-bold uppercase tracking-wider">Verif</span>
            </div>
          </div>
        </div>
        <div className="bg-slate-50 text-slate-500 p-3.5 rounded">
          <Clock size={20} />
        </div>
      </div>
    </div>
  );
};
