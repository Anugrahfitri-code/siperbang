/**
 * RequesterStockList
 *
 * Read-only stock search for Ketua Tim Kerja.
 * Fetches directly from /api/stocks/search — no prop dependency.
 * Supports: search (code/name/category), category filter, stock-status filter,
 * server-side pagination, loading / empty / error states.
 *
 * Permissions enforced:
 *   – No add / edit / delete / adjust-stock actions anywhere in this component.
 *   – Data is fetched from a read-only endpoint accessible to all authenticated roles.
 */

import React, { useState, useEffect, useCallback, useRef } from "react";
import {
  Search,
  Filter,
  Package,
  AlertCircle,
  CheckCircle,
  XCircle,
  RefreshCw,
  ChevronLeft,
  ChevronRight,
  X,
  Loader2,
} from "lucide-react";
import { apiFetch } from "../api";

// ── Types ──────────────────────────────────────────────────────────────────

interface StockRow {
  kode: string;
  nama: string;
  kategori: string;
  satuan: string;
  stok: number;
  status_stok: "Tersedia" | "Stok Terbatas" | "Tidak Tersedia";
  update_terakhir: string | null;
}

interface Meta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

interface ApiResponse {
  data: StockRow[];
  categories: string[];
  meta: Meta;
}

type StockStatusFilter = "" | "tersedia" | "terbatas" | "kosong";

// ── Helpers ────────────────────────────────────────────────────────────────

const statusBadge = (status: StockRow["status_stok"]) => {
  switch (status) {
    case "Tersedia":
      return (
        <span className="inline-flex items-center gap-1 text-xs font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">
          <CheckCircle size={10} />
          Tersedia
        </span>
      );
    case "Stok Terbatas":
      return (
        <span className="inline-flex items-center gap-1 text-xs font-extrabold text-amber-700 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-full">
          <AlertCircle size={10} />
          Stok Terbatas
        </span>
      );
    default:
      return (
        <span className="inline-flex items-center gap-1 text-xs font-extrabold text-rose-700 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-full">
          <XCircle size={10} />
          Tidak Tersedia
        </span>
      );
  }
};

const qtyColor = (qty: number) => {
  if (qty <= 0) return "text-rose-600";
  if (qty <= 5) return "text-amber-600";
  return "text-emerald-600";
};

// ── Main Component ─────────────────────────────────────────────────────────

export function RequesterStockList() {
  // ── Filter state ──────────────────────────────────────────────
  const [query, setQuery]         = useState("");
  const [category, setCategory]   = useState("");
  const [statusFilter, setStatus] = useState<StockStatusFilter>("");
  const [page, setPage]           = useState(1);

  // ── Data state ────────────────────────────────────────────────
  const [rows, setRows]               = useState<StockRow[]>([]);
  const [categories, setCategories]   = useState<string[]>([]);
  const [meta, setMeta]               = useState<Meta | null>(null);
  const [loading, setLoading]         = useState(true);
  const [error, setError]             = useState<string | null>(null);

  // Debounce ref for search input
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // ── Fetch ─────────────────────────────────────────────────────
  const fetchData = useCallback(async (
    q: string,
    cat: string,
    st: string,
    pg: number,
  ) => {
    setLoading(true);
    setError(null);

    try {
      const params = new URLSearchParams();
      if (q)   params.set("q",        q);
      if (cat) params.set("category", cat);
      if (st)  params.set("status",   st);
      params.set("page",     String(pg));
      params.set("per_page", "20");

      const res = await apiFetch(`/api/stocks/search?${params.toString()}`);

      if (!res.ok) {
        if (res.status === 401) {
          throw new Error("Sesi telah berakhir. Silakan login kembali.");
        }
        if (res.status === 403) {
          throw new Error("Akses ditolak. Hubungi administrator.");
        }
        throw new Error(`Gagal memuat data stok (HTTP ${res.status}).`);
      }

      const json: ApiResponse = await res.json();
      setRows(json.data);
      setMeta(json.meta);
      // Preserve categories after first successful load
      if (json.categories.length > 0) {
        setCategories(json.categories);
      }
    } catch (err: any) {
      setError(err.message ?? "Terjadi kesalahan. Coba lagi.");
      setRows([]);
      setMeta(null);
    } finally {
      setLoading(false);
    }
  }, []);

  // Initial load + re-fetch when filter/page changes
  useEffect(() => {
    fetchData(query, category, statusFilter, page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [category, statusFilter, page]);

  // Debounced search — wait 400ms after user stops typing
  const handleQueryChange = (value: string) => {
    setQuery(value);
    setPage(1);
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => {
      fetchData(value, category, statusFilter, 1);
    }, 400);
  };

  // Reset all filters
  const handleReset = () => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    setQuery("");
    setCategory("");
    setStatus("");
    setPage(1);
    fetchData("", "", "", 1);
  };

  const isFiltered = query !== "" || category !== "" || statusFilter !== "";

  // ── Render ────────────────────────────────────────────────────
  return (
    <div className="space-y-5">

      {/* ── Header ── */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs p-5 flex items-center gap-4">
        <div className="flex size-14 shrink-0 items-center justify-center rounded-xl border bg-amber-50 text-amber-600 border-amber-100">
          <Package size={24} />
        </div>
        <div>
          <h2 className="text-lg font-semibold leading-7 text-slate-900">
            Katalog Stok Gudang
          </h2>
          <p className="text-sm font-normal leading-5 text-slate-500 mt-0.5">
            Cari dan periksa ketersediaan barang sebelum mengajukan BON.
            Data diperbarui secara langsung dari database gudang.
          </p>
        </div>
      </div>

      {/* ── Filter bar ── */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
        <div className="flex flex-col sm:flex-row gap-3">

          {/* Search input */}
          <div className="relative flex-1">
            <Search
              size={15}
              className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
            />
            <input
              type="text"
              value={query}
              onChange={(e) => handleQueryChange(e.target.value)}
              placeholder="Cari kode, nama, atau kategori barang..."
              className="w-full pl-9 pr-8 py-2 text-xs border border-slate-200 rounded-lg bg-slate-50 text-slate-800 font-medium
                         focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all"
            />
            {query && (
              <button
                onClick={() => handleQueryChange("")}
                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                aria-label="Hapus pencarian"
              >
                <X size={13} />
              </button>
            )}
          </div>

          {/* Category filter */}
          <div className="flex items-center gap-2 shrink-0">
            <Filter size={14} className="text-slate-400 shrink-0" />
            <select
              value={category}
              onChange={(e) => { setCategory(e.target.value); setPage(1); }}
              className="py-2 pl-3 pr-8 text-xs border border-slate-200 rounded-lg bg-slate-50 text-slate-700 font-semibold
                         focus:outline-none focus:ring-2 focus:ring-amber-400 transition-all min-w-[160px]"
            >
              <option value="">Semua Kategori</option>
              {categories.map((cat) => (
                <option key={cat} value={cat}>{cat}</option>
              ))}
            </select>
          </div>

          {/* Stock status filter */}
          <select
            value={statusFilter}
            onChange={(e) => { setStatus(e.target.value as StockStatusFilter); setPage(1); }}
            className="py-2 pl-3 pr-8 text-xs border border-slate-200 rounded-lg bg-slate-50 text-slate-700 font-semibold
                       focus:outline-none focus:ring-2 focus:ring-amber-400 transition-all shrink-0"
          >
            <option value="">Semua Status</option>
            <option value="tersedia">Tersedia (&gt; 5)</option>
            <option value="terbatas">Stok Terbatas (1–5)</option>
            <option value="kosong">Tidak Tersedia (0)</option>
          </select>

          {/* Reset */}
          {isFiltered && (
            <button
              onClick={handleReset}
              className="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 text-xs font-semibold
                         text-slate-600 bg-white hover:bg-slate-50 transition-colors shrink-0"
            >
              <X size={13} />
              Reset
            </button>
          )}
        </div>

        {/* Active filter chips */}
        {isFiltered && (
          <div className="mt-3 flex flex-wrap gap-2">
            {query && (
              <span className="inline-flex items-center gap-1 text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-full">
                Pencarian: "{query}"
                <button onClick={() => handleQueryChange("")} className="ml-0.5 hover:text-amber-900"><X size={10} /></button>
              </span>
            )}
            {category && (
              <span className="inline-flex items-center gap-1 text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded-full">
                Kategori: {category}
                <button onClick={() => { setCategory(""); setPage(1); }} className="ml-0.5 hover:text-indigo-900"><X size={10} /></button>
              </span>
            )}
            {statusFilter && (
              <span className="inline-flex items-center gap-1 text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 px-2 py-0.5 rounded-full">
                Status: {statusFilter === "tersedia" ? "Tersedia" : statusFilter === "terbatas" ? "Stok Terbatas" : "Tidak Tersedia"}
                <button onClick={() => { setStatus(""); setPage(1); }} className="ml-0.5 hover:text-slate-900"><X size={10} /></button>
              </span>
            )}
          </div>
        )}
      </div>

      {/* ── Table area ── */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">

        {/* Loading state */}
        {loading && (
          <div className="flex flex-col items-center justify-center py-20 text-slate-400 gap-3">
            <Loader2 size={28} className="animate-spin text-amber-500" />
            <p className="text-sm font-semibold">Memuat data stok...</p>
          </div>
        )}

        {/* Error state */}
        {!loading && error && (
          <div className="flex flex-col items-center justify-center py-16 gap-4 px-6 text-center">
            <div className="bg-rose-50 rounded-full p-3">
              <AlertCircle size={24} className="text-rose-500" />
            </div>
            <div>
              <p className="text-sm font-extrabold text-slate-800">Gagal memuat data</p>
              <p className="text-xs text-slate-500 mt-1">{error}</p>
            </div>
            <button
              onClick={() => fetchData(query, category, statusFilter, page)}
              className="flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition-colors"
            >
              <RefreshCw size={13} />
              Coba Lagi
            </button>
          </div>
        )}

        {/* Empty state */}
        {!loading && !error && rows.length === 0 && (
          <div className="flex flex-col items-center justify-center py-16 gap-3 px-6 text-center">
            <div className="bg-slate-100 rounded-full p-3">
              <Package size={24} className="text-slate-400" />
            </div>
            <div>
              <p className="text-sm font-extrabold text-slate-700">
                {isFiltered ? "Tidak ada barang yang cocok" : "Belum ada data stok"}
              </p>
              <p className="text-xs text-slate-400 mt-1">
                {isFiltered
                  ? "Coba ubah kata kunci pencarian atau hapus filter yang aktif."
                  : "Data stok akan muncul setelah Petugas Persediaan mengunggah file Excel."}
              </p>
            </div>
            {isFiltered && (
              <button
                onClick={handleReset}
                className="text-xs font-bold text-amber-600 hover:text-amber-700 underline"
              >
                Hapus semua filter
              </button>
            )}
          </div>
        )}

        {/* Data table */}
        {!loading && !error && rows.length > 0 && (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  <tr className="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-xs">
                    <th className="px-5 py-3">Kode Persediaan</th>
                    <th className="px-5 py-3">Nama Barang</th>
                    <th className="px-5 py-3">Kategori</th>
                    <th className="px-4 py-3 text-center">Satuan</th>
                    <th className="px-4 py-3 text-right">Stok Tersedia</th>
                    <th className="px-4 py-3 text-center">Status</th>
                    <th className="px-4 py-3 text-right">Update Terakhir</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {rows.map((item) => (
                    <tr
                      key={`${item.kode}-${item.nama}`}
                      className="hover:bg-amber-50/30 transition-colors"
                    >
                      <td className="px-5 py-3.5 whitespace-nowrap">
                        <span className="font-mono text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">
                          {item.kode}
                        </span>
                      </td>
                      <td className="px-5 py-3.5 font-semibold text-slate-800 max-w-[220px]">
                        <span className="line-clamp-2" title={item.nama}>
                          {item.nama}
                        </span>
                      </td>
                      <td className="px-5 py-3.5 text-slate-500 whitespace-nowrap">
                        {item.kategori}
                      </td>
                      <td className="px-4 py-3.5 text-center text-slate-500 whitespace-nowrap">
                        {item.satuan}
                      </td>
                      <td className={`px-4 py-3.5 text-right font-extrabold text-base whitespace-nowrap ${qtyColor(item.stok)}`}>
                        {item.stok.toLocaleString("id-ID")}
                      </td>
                      <td className="px-4 py-3.5 text-center whitespace-nowrap">
                        {statusBadge(item.status_stok)}
                      </td>
                      <td className="px-4 py-3.5 text-right text-slate-400 whitespace-nowrap text-xs">
                        {item.update_terakhir
                          ? new Date(item.update_terakhir).toLocaleDateString("id-ID", {
                              day: "2-digit",
                              month: "short",
                              year: "numeric",
                            })
                          : "—"}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Pagination + summary */}
            {meta && (
              <div className="px-5 py-3.5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/50">
                <p className="text-xs text-slate-400 font-medium">
                  {meta.total === 0
                    ? "Tidak ada data"
                    : `Menampilkan ${meta.from ?? 0}–${meta.to ?? 0} dari ${meta.total.toLocaleString("id-ID")} barang`}
                </p>

                {meta.last_page > 1 && (
                  <div className="flex items-center gap-1.5">
                    <button
                      disabled={page <= 1 || loading}
                      onClick={() => setPage((p) => p - 1)}
                      className="flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold
                                 text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                      <ChevronLeft size={13} />
                      Sebelumnya
                    </button>

                    {/* Page numbers — show up to 5 around current */}
                    <div className="flex gap-1">
                      {Array.from({ length: meta.last_page }, (_, i) => i + 1)
                        .filter((p) => Math.abs(p - page) <= 2)
                        .map((p) => (
                          <button
                            key={p}
                            onClick={() => setPage(p)}
                            disabled={loading}
                            className={`w-8 h-8 rounded-lg text-xs font-extrabold border transition-colors
                              ${p === page
                                ? "bg-amber-500 text-white border-amber-500 shadow-xs"
                                : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50"
                              } disabled:opacity-40`}
                          >
                            {p}
                          </button>
                        ))}
                    </div>

                    <button
                      disabled={page >= meta.last_page || loading}
                      onClick={() => setPage((p) => p + 1)}
                      className="flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold
                                 text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                      Berikutnya
                      <ChevronRight size={13} />
                    </button>
                  </div>
                )}
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
