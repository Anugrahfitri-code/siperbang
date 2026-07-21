import React, { useEffect, useState } from "react";
import { HistoryLog as LogType } from "../types";
import { History, User, Clock, Loader2, Settings } from "lucide-react";

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
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
      <div className="flex items-center gap-4 mb-8">
        <div className="flex size-14 shrink-0 items-center justify-center rounded-xl border bg-blue-50 text-blue-600 border-blue-100">
          <History size={24} />
        </div>
        <div className="flex-1">
          <h2 className="text-lg font-semibold leading-7 text-slate-900">Histori Perubahan & Audit Log</h2>
          <p className="text-sm font-normal leading-5 text-slate-500 mt-0.5">
            Riwayat lengkap semua tindakan dari seluruh pengguna sistem.
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
          className="flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-200 text-xs font-bold text-blue-600 hover:bg-slate-50 transition-colors shadow-sm"
          title="Muat ulang log"
        >
          <svg className={`h-4 w-4 ${loading ? "animate-spin" : ""}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
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
        <div className="relative ml-[14px] max-h-[600px] overflow-y-auto pr-2 pb-4">
          {/* Vertical Line */}
          <div className="absolute left-[7px] top-6 bottom-6 w-[2px] bg-slate-200"></div>

          <div className="space-y-4">
            {logs.map((log) => {
              // Deteksi warna badge & dot berdasarkan konten
              let badgeColor = "bg-slate-100 text-slate-700";
              let dotColor = "border-blue-600";
              let iconBg = "bg-slate-50 border-slate-200 text-slate-500";
              let ActorIcon = User;
              
              const actionLower = log.action.toLowerCase();
              const actorLower = log.actor.toLowerCase();

              // Icon & Bg
              if (actorLower.includes("system") || actorLower.includes("sistem")) {
                ActorIcon = Settings;
                iconBg = "bg-blue-50 border-blue-100 text-blue-600";
              } else {
                iconBg = "bg-rose-50 border-rose-100 text-rose-500";
              }

              // Badges & Dots
              if (actionLower.includes("berhasil") || actionLower.includes("verifikasi") || actionLower.includes("valid")) {
                badgeColor = "bg-emerald-100 text-emerald-700";
                dotColor = "border-blue-600";
              } else if (actionLower.includes("ajukan") || actionLower.includes("buat")) {
                badgeColor = "bg-amber-100 text-amber-700";
                dotColor = "border-blue-600";
              } else if (actionLower.includes("tolak") || actionLower.includes("batal")) {
                badgeColor = "bg-rose-100 text-rose-700";
                dotColor = "border-rose-500";
              }

              return (
                <div key={log.id} className="relative flex items-center gap-6 pl-10">
                  {/* Timeline Marker Dot */}
                  <div className={`absolute left-0 top-1/2 -translate-y-1/2 bg-white border-2 rounded-full w-4 h-4 z-10 ${dotColor}`} />

                  {/* Card */}
                  <div className="bg-white border border-slate-200 rounded-xl p-4 flex-1 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:shadow-sm transition-shadow">
                    
                    <div className="flex items-center gap-4">
                      {/* Avatar/Icon Box */}
                      <div className={`p-2.5 rounded-xl border flex-shrink-0 ${iconBg}`}>
                        <ActorIcon size={20} strokeWidth={2} />
                      </div>
                      
                      <div className="flex flex-col gap-1">
                        <div className="flex items-center gap-3">
                          <h4 className="text-sm font-extrabold text-slate-800">
                            {log.actor}
                          </h4>
                          <span className={`text-xs font-bold px-2 py-0.5 rounded ${badgeColor}`}>
                            {log.action}
                          </span>
                        </div>
                        <div className="flex flex-col gap-1">
                          <span className="text-xs font-medium text-slate-500">
                            {actorLower.includes("system") ? "Sistem" : "Petugas Persediaan"}
                          </span>
                          <p className="text-sm text-slate-600 font-medium">
                            {log.details}
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* Timestamp */}
                    <div className="flex-shrink-0 flex items-center gap-2 text-slate-500 font-mono text-xs font-medium">
                      <Clock size={14} className="text-slate-400" />
                      {log.timestamp}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

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