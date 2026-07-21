import React from "react";
import { HistoryLog as LogType } from "../types";
import { History, User, Clock, ShieldCheck, ArrowRight, Activity } from "lucide-react";

interface HistoryLogProps {
  logs: LogType[];
}

export const HistoryLog: React.FC<HistoryLogProps> = ({ logs }) => {
  return (
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      <div className="flex items-center gap-3 mb-6 border-b border-slate-100 pb-5">
        <div className="bg-slate-50 text-slate-700 p-2.5 rounded border border-slate-200">
          <History size={18} />
        </div>
        <div>
          <h2 className="text-base font-extrabold text-slate-800 tracking-tight">Histori Perubahan & Audit Log</h2>
          <p className="text-xs text-slate-500">
            Riwayat lengkap penambahan, pengurangan, perubahan status, dan verifikasi dokumen kuitansi
          </p>
        </div>
      </div>

      <div className="relative border-l border-slate-200 ml-4 pl-6 space-y-6 max-h-[500px] overflow-y-auto">
        {logs.map((log) => {
          // Detect tag colors based on content
          let badgeColor = "bg-slate-50 text-slate-700 border-slate-200";
          if (log.action.toLowerCase().includes("ajukan") || log.action.toLowerCase().includes("buat")) {
            badgeColor = "bg-amber-50 text-amber-800 border-amber-200";
          } else if (log.action.toLowerCase().includes("verifikasi") || log.action.toLowerCase().includes("valid")) {
            badgeColor = "bg-emerald-50 text-emerald-800 border-emerald-200";
          } else if (log.action.toLowerCase().includes("stok") || log.action.toLowerCase().includes("kurang")) {
            badgeColor = "bg-indigo-50 text-indigo-700 border-indigo-150";
          } else if (log.action.toLowerCase().includes("tolak") || log.action.toLowerCase().includes("batal")) {
            badgeColor = "bg-rose-50 text-rose-800 border-rose-200";
          }

          return (
            <div key={log.id} className="relative group">
              {/* Timeline Marker Dot */}
              <div className="absolute -left-[31px] top-1 bg-white border-2 border-indigo-600 rounded-full w-3.5 h-3.5 group-hover:bg-indigo-600 transition-all" />

              <div className="bg-slate-50/50 hover:bg-slate-50 border border-slate-150 rounded p-4 transition-colors">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="text-xs font-bold text-slate-800 flex items-center gap-1">
                      <User size={12} className="text-slate-400" />
                      {log.actor}
                    </span>
                    <span className="text-slate-300">•</span>
                    <span className={`text-xs font-bold border px-2 py-0.5 rounded-full ${badgeColor}`}>
                      {log.action}
                    </span>
                  </div>

                  <span className="text-xs text-slate-400 font-semibold flex items-center gap-1 font-mono">
                    <Clock size={11} />
                    {log.timestamp}
                  </span>
                </div>

                <p className="text-xs text-slate-600 mt-2 leading-relaxed">
                  {log.details}
                </p>
              </div>
            </div>
          );
        })}

        {logs.length === 0 && (
          <div className="text-center py-8 text-slate-400 text-xs">
            Tidak ada riwayat aktivitas yang tercatat.
          </div>
        )}
      </div>
    </div>
  );
};
