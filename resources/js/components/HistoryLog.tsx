import React, { useEffect, useState } from "react";
import { HistoryLog as LogType } from "../types";
import { History, User, Clock, Loader2 } from "lucide-react";

interface HistoryLogProps {
  // Kita jadikan props logs bersifat opsional (?) agar jika frontend 
  // tidak mengirimkan data, komponen otomatis mengambil langsung dari API backend.
  logs?: LogType[];
}

export const HistoryLog: React.FC<HistoryLogProps> = ({ logs: incomingLogs }) => {
  const [logs, setLogs]       = useState<LogType[]>(incomingLogs || []);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError]     = useState<string | null>(null);

  // Always fetch fresh from DB when opened — so ALL roles see the same log
  useEffect(() => {
    const fetchLogs = async () => {
      try {
        setLoading(true);
        setError(null);
        const response = await fetch("/api/logs", {
          headers: { Accept: "application/json" },
          credentials: "same-origin",
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data: any[] = await response.json();
        // Normalize backend shape → frontend shape
        setLogs(data.map((l) => ({
          id:        String(l.id),
          timestamp: l.created_at ?? l.timestamp ?? "",
          actor:     l.actor,
          action:    l.action,
          details:   l.details,
        })));
      } catch (err: any) {
        console.error("Gagal mengambil log:", err);
        // Fall back to prop data if available
        if (incomingLogs && incomingLogs.length > 0) {
          setLogs(incomingLogs);
        } else {
          setError("Gagal memuat histori perubahan sistem.");
        }
      } finally {
        setLoading(false);
      }
    };

    fetchLogs();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // fetch once on mount

  return (
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      <div className="flex items-center gap-3 mb-6 border-b border-slate-100 pb-5">
        <div className="bg-slate-50 text-slate-700 p-2.5 rounded border border-slate-200">
          <History size={18} />
        </div>
        <div className="flex-1">
          <h2 className="text-base font-extrabold text-slate-800 tracking-tight">Histori Perubahan & Audit Log</h2>
          <p className="text-[11px] text-slate-500">
            Riwayat lengkap semua tindakan dari seluruh pengguna sistem
          </p>
        </div>
        <button
          onClick={() => {
            setLoading(true);
            setError(null);
            fetch("/api/logs", { headers: { Accept: "application/json" }, credentials: "same-origin" })
              .then((r) => r.json())
              .then((data: any[]) => setLogs(data.map((l) => ({
                id: String(l.id), timestamp: l.created_at ?? l.timestamp ?? "",
                actor: l.actor, action: l.action, details: l.details,
              }))))
              .catch(() => setError("Gagal memuat ulang log."))
              .finally(() => setLoading(false));
          }}
          className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
          title="Muat ulang log"
        >
          <svg className={`h-3.5 w-3.5 ${loading ? "animate-spin" : ""}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>
      </div>

      {/* 3. Tampilkan UI state Loading jika data sedang dimuat dari API */}
      {loading && (
        <div className="flex items-center justify-center py-12 gap-2 text-xs text-slate-500 font-medium">
          <Loader2 size={16} className="animate-spin text-indigo-600" />
          Memuat riwayat aktivitas...
        </div>
      )}

      {/* 4. Tampilkan UI state Error jika terjadi kegagalan fetch API */}
      {error && !loading && (
        <div className="bg-rose-50 border border-rose-150 text-rose-800 text-xs p-3.5 rounded-md text-center font-medium">
          {error}
        </div>
      )}

      {/* 5. Tampilkan Timeline Data jika data sukses dimuat */}
      {!loading && !error && (
        <div className="relative border-l border-slate-200 ml-4 pl-6 space-y-6 max-h-[500px] overflow-y-auto">
          {logs.map((log) => {
            // Deteksi warna badge berdasarkan konten
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
                      <span className={`text-[10px] font-bold border px-2 py-0.5 rounded-full ${badgeColor}`}>
                        {log.action}
                      </span>
                    </div>

                    <span className="text-[10px] text-slate-400 font-semibold flex items-center gap-1 font-mono">
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
      )}
    </div>
  );
};