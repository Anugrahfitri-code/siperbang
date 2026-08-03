import React, { useEffect, useState } from "react";
import { HistoryLog as LogType } from "../../../shared/types";
import { History, User, Clock, Loader2, Settings, MapPin, Package, ChevronDown, ChevronUp } from "lucide-react";

// ── Sub-component: each log card (needs its own useState for expand/collapse) ─
const LogItem: React.FC<{ log: LogType }> = ({ log }) => {
  const [expanded, setExpanded] = useState(false);

  let badgeColor = "bg-slate-100 text-slate-700";
  let dotColor   = "border-blue-600";
  let iconBg     = "bg-slate-50 border-slate-200 text-slate-500";
  let ActorIcon: React.ElementType = User;

  const actionLower  = log.action.toLowerCase();
  const actorLower   = log.actor?.toLowerCase() ?? "";
  const isFinalisasi = actionLower.includes("finalisasi") || log.action === "FINALISASI_STOK";

  if (actorLower.includes("system") || actorLower.includes("sistem")) {
    ActorIcon = Settings;
    iconBg    = "bg-blue-50 border-blue-100 text-blue-600";
  } else if (isFinalisasi) {
    ActorIcon = Package;
    iconBg    = "bg-indigo-50 border-indigo-100 text-indigo-600";
  } else {
    iconBg = "bg-rose-50 border-rose-100 text-rose-500";
  }

  if (isFinalisasi) {
    badgeColor = "bg-indigo-100 text-indigo-700";
    dotColor   = "border-indigo-500";
  } else if (actionLower.includes("berhasil") || actionLower.includes("verifikasi") || actionLower.includes("valid")) {
    badgeColor = "bg-emerald-100 text-emerald-700";
  } else if (actionLower.includes("ajukan") || actionLower.includes("buat")) {
    badgeColor = "bg-amber-100 text-amber-700";
  } else if (actionLower.includes("tolak") || actionLower.includes("batal")) {
    badgeColor = "bg-rose-100 text-rose-700";
    dotColor   = "border-rose-500";
  }

  const meta     = log.metadata;
  const hasItems = meta?.items && meta.items.length > 0;

  const ts = log.timestamp ? new Date(log.timestamp) : null;
  const formattedTime = ts
    ? ts.toLocaleString("id-ID", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit", second: "2-digit" })
    : log.timestamp;

  return (
    <div className="relative pl-7">
      {/* Timeline Dot — centered on the absolute line at left:0 */}
      <div className={`absolute left-[-7px] top-5 bg-white border-2 rounded-full w-3.5 h-3.5 z-10 ${dotColor}`} />

      {/* Card */}
      <div className="bg-white border border-slate-200 rounded-xl p-4 hover:shadow-sm transition-shadow">

        {/* Header row */}
        <div className="flex flex-col md:flex-row md:items-start justify-between gap-3">
          <div className="flex items-start gap-3">
            <div className={`p-2.5 rounded-xl border flex-shrink-0 ${iconBg}`}>
              <ActorIcon size={20} strokeWidth={2} />
            </div>

            <div className="flex flex-col gap-1">
              <div className="flex items-center gap-2 flex-wrap">
                <h4 className="text-sm font-extrabold text-slate-800">{log.actor}</h4>
                <span className={`text-xs font-bold px-2 py-0.5 rounded ${badgeColor}`}>{log.action}</span>
                {isFinalisasi && meta && (
                  <span className="text-xs text-white font-semibold bg-indigo-500 px-2 py-0.5 rounded">
                    +{meta.inserted ?? 0} baru · {meta.updated ?? 0} update
                  </span>
                )}
              </div>
              <span className="text-xs font-medium text-slate-400">
                {actorLower.includes("system") ? "Sistem" : "Pengguna"}
              </span>

              <p className="text-xs text-slate-600 font-medium line-clamp-2 mt-0.5">
                {log.details?.split("\n")[0]}
              </p>

              {/* Meta chips */}
              <div className="flex flex-wrap gap-2 mt-1.5">
                {log.ip_address && (
                  <span className="inline-flex items-center gap-1 text-[10px] font-mono font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                    <MapPin size={9} /> {log.ip_address}
                  </span>
                )}
                {meta?.batch_id && (
                  <span className="inline-flex items-center gap-1 text-[10px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                    Batch #{meta.batch_id}
                  </span>
                )}
              </div>
            </div>
          </div>

          {/* Timestamp */}
          <div className="flex-shrink-0 flex items-center gap-1.5 text-slate-400 font-mono text-xs">
            <Clock size={13} />
            {formattedTime}
          </div>
        </div>

        {/* Expandable detail panel (only for finalisasi) */}
        {isFinalisasi && hasItems && (
          <div className="mt-3">
            <button
              onClick={() => setExpanded((v) => !v)}
              className="flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors"
            >
              {expanded ? <ChevronUp size={13} /> : <ChevronDown size={13} />}
              {expanded ? "Sembunyikan detail barang" : `Lihat detail barang (${meta!.items!.length} item)`}
            </button>

            {expanded && (
              <div className="mt-2 border border-indigo-100 rounded-lg overflow-hidden">
                <table className="w-full text-xs">
                  <thead>
                    <tr className="bg-indigo-50 text-indigo-700 font-bold uppercase tracking-wider text-[10px]">
                      <th className="px-3 py-2 text-left">Aksi</th>
                      <th className="px-3 py-2 text-left">Nama Barang</th>
                      <th className="px-3 py-2 text-left">Kode</th>
                      <th className="px-3 py-2 text-right">Perubahan Stok</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-indigo-50">
                    {meta!.items!.map((item, i) => (
                      <tr key={i} className="hover:bg-slate-50/50">
                        <td className="px-3 py-2">
                          <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded ${item.action === "insert" ? "bg-emerald-100 text-emerald-700" : "bg-blue-100 text-blue-700"}`}>
                            {item.action === "insert" ? "BARU" : "UPDATE"}
                          </span>
                        </td>
                        <td className="px-3 py-2 font-semibold text-slate-800">{item.name}</td>
                        <td className="px-3 py-2 font-mono text-slate-500">{item.code}</td>
                        <td className="px-3 py-2 text-right font-mono font-bold text-slate-700">
                          {item.action === "insert"
                            ? `+${item.qty} ${item.unit}`
                            : `${item.qty_before} + ${item.qty_added} = ${item.qty_after} ${item.unit}`}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {meta?.user_agent && (
                  <div className="px-3 py-2 bg-slate-50 border-t border-indigo-50 text-[10px] text-slate-400 font-mono truncate">
                    Browser: {meta.user_agent}
                  </div>
                )}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

// ── Main component ────────────────────────────────────────────────────────────
interface HistoryLogProps {
  logs?: LogType[];
}

export const HistoryLog: React.FC<HistoryLogProps> = ({ logs: incomingLogs }) => {
  const [logs, setLogs]       = useState<LogType[]>(incomingLogs || []);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError]     = useState<string | null>(null);

  const fetchLogs = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await fetch("/api/logs", {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data: any[] = await response.json();
      setLogs(data.map((l) => ({
        id:         String(l.id),
        timestamp:  l.created_at ?? l.timestamp ?? "",
        actor:      l.actor,
        action:     l.action,
        details:    l.details,
        ip_address: l.ip_address ?? null,
        metadata:   l.metadata ?? null,
      })));
    } catch (err: any) {
      console.error("Gagal mengambil log:", err);
      if (incomingLogs && incomingLogs.length > 0) {
        setLogs(incomingLogs);
      } else {
        setError("Gagal memuat histori perubahan sistem.");
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLogs();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="relative bg-gradient-to-r from-[#f8faff] to-[#f0f4ff] rounded-2xl border border-indigo-50/50 p-6 shadow-sm overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="absolute -top-24 -left-24 w-64 h-64 bg-blue-100/40 rounded-full blur-3xl pointer-events-none"></div>
        <div className="relative z-10 flex-1 flex items-center gap-4">
          <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm border border-indigo-100 text-indigo-600">
            <History size={28} strokeWidth={2.5} />
          </div>
          <div className="flex-1">
            <h2 className="text-base font-extrabold text-slate-900 uppercase tracking-wide">Histori Perubahan &amp; Audit Log</h2>
            <p className="text-sm font-medium text-slate-500 mt-1">
              Riwayat lengkap semua tindakan dari seluruh pengguna sistem.
            </p>
          </div>
        </div>
        <button
          onClick={fetchLogs}
          className="relative z-10 flex items-center gap-2 px-4 py-2.5 rounded-xl border border-indigo-100 text-xs font-bold text-indigo-600 bg-white hover:bg-indigo-50 transition-colors shadow-sm"
          title="Muat ulang log"
        >
          <svg className={`h-4 w-4 ${loading ? "animate-spin" : ""}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>
      </div>

      {/* Loading */}
      {loading && (
        <div className="flex items-center justify-center py-12 gap-2 text-xs text-slate-500 font-medium">
          <Loader2 size={16} className="animate-spin text-indigo-600" />
          Memuat riwayat aktivitas...
        </div>
      )}

      {/* Error */}
      {error && !loading && (
        <div className="bg-rose-50 border border-rose-200 text-rose-800 text-xs p-3.5 rounded-md text-center font-medium">
          {error}
        </div>
      )}

      {/* Timeline */}
      {!loading && !error && (
        <div className="ml-3 pl-4 max-h-[700px] overflow-y-auto pr-2 pb-4">
          {/* Inner wrapper: position:relative so the absolute line fills full content height */}
          <div className="relative">
            {/* Continuous vertical line — absolute top-0 bottom-0 = full content height */}
            <div className="absolute left-0 top-0 bottom-0 w-0.5 bg-slate-200" />

            <div className="space-y-4">
              {logs.map((log) => (
                <LogItem key={log.id} log={log} />
              ))}
            </div>

            {logs.length === 0 && (
              <div className="text-center py-8 text-slate-400 text-xs pl-7">
                Tidak ada riwayat aktivitas yang tercatat.
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};
