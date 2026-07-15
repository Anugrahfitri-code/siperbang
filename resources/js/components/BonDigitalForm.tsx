/**
 * BonDigitalForm.tsx
 *
 * Form pengajuan BON Digital untuk Ketua Tim.
 * Mengirim payload yang sesuai kontrak backend RequestController@store:
 *   { keperluan, catatan, status, items: [{ barang_id, jumlah_diminta, catatan }] }
 *
 * Tombol "Simpan Draft"  → status = "draft"
 * Tombol "Kirim Pengajuan" → status = "menunggu_verifikasi"
 *
 * Barang dipilih dari /api/stocks/search (data asli dari database).
 * Tidak ada data dummy atau data statis.
 */

import React, { useState, useEffect, useCallback, useRef } from "react";
import {
  ClipboardList,
  Loader2,
  Search,
  Plus,
  Trash2,
  Send,
  Save,
  AlertCircle,
  CheckCircle,
  X,
  Package,
} from "lucide-react";
import { apiFetch } from "../api";

// ── Types ──────────────────────────────────────────────────────────────────

interface StockOption {
  kode: string;
  nama: string;
  kategori: string;
  satuan: string;
  stok: number;
  /** numeric id needed for barang_id — fetched via separate detail endpoint */
  id?: number;
}

interface BonItem {
  /** Numeric PK dari tabel stock_items */
  barang_id: number;
  /** Nama untuk tampilan UI saja — tidak dikirim ke backend */
  nama_barang: string;
  satuan: string;
  stok_tersedia: number;
  jumlah_diminta: number;
  catatan: string;
}

export interface BonSubmitPayload {
  keperluan: string;
  catatan: string;
  status: "draft" | "menunggu_verifikasi";
  items: Array<{
    barang_id: number;
    jumlah_diminta: number;
    catatan: string;
  }>;
}

interface BonDigitalFormProps {
  /**
   * Dipanggil setelah form divalidasi di frontend.
   * App.tsx bertanggung jawab atas POST ke /api/requests.
   */
  onSubmit: (payload: BonSubmitPayload) => Promise<void>;
  currentUser: string;
}

// ── Stock search helper ────────────────────────────────────────────────────

interface SearchResult {
  /** Numeric DB id — diperoleh dari /api/stocks/search detail */
  id: number;
  kode: string;
  nama: string;
  satuan: string;
  stok: number;
  kategori: string;
}

// ── Component ─────────────────────────────────────────────────────────────

export const BonDigitalForm: React.FC<BonDigitalFormProps> = ({
  onSubmit,
  currentUser,
}) => {
  // ── Form fields ───────────────────────────────────────────────
  const [keperluan, setKeperluan] = useState("");
  const [catatan, setCatatan]     = useState("");
  const [items, setItems]         = useState<BonItem[]>([]);

  // ── Stock search state ────────────────────────────────────────
  const [searchQuery, setSearchQuery]       = useState("");
  const [searchResults, setSearchResults]   = useState<SearchResult[]>([]);
  const [searchLoading, setSearchLoading]   = useState(false);
  const [searchError, setSearchError]       = useState<string | null>(null);
  const [showDropdown, setShowDropdown]     = useState(false);
  const searchDebounce = useRef<ReturnType<typeof setTimeout> | null>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // ── Submission state ──────────────────────────────────────────
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitMode, setSubmitMode]     = useState<"draft" | "kirim" | null>(null);
  const [successMsg, setSuccessMsg]     = useState("");
  const [errorMsg, setErrorMsg]         = useState("");
  const [fieldErrors, setFieldErrors]   = useState<Record<string, string>>({});

  // ── Close dropdown on outside click ──────────────────────────
  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setShowDropdown(false);
      }
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  // ── Search stock from API ─────────────────────────────────────
  const searchStock = useCallback(async (q: string) => {
    if (q.trim().length < 2) {
      setSearchResults([]);
      setShowDropdown(false);
      return;
    }

    setSearchLoading(true);
    setSearchError(null);

    try {
      const params = new URLSearchParams({ q: q.trim(), per_page: "15" });
      const res = await apiFetch(`/api/stocks/search?${params}`);

      if (!res.ok) {
        throw new Error("Gagal memuat data barang.");
      }

      const json = await res.json();
      // /api/stocks/search returns { data: [...], categories, meta }
      const rows: any[] = Array.isArray(json) ? json : (json.data ?? []);

      // The search endpoint returns kode/nama/satuan/stok but we also need the
      // numeric id.  We fetch it via /api/stocks/search which now returns id too.
      const mapped: SearchResult[] = rows.map((r: any) => ({
        id:       Number(r.id ?? r.stock_id ?? 0),
        kode:     r.kode ?? r.code ?? "",
        nama:     r.nama ?? r.name ?? "",
        satuan:   r.satuan ?? r.unit ?? "",
        stok:     Number(r.stok ?? r.qty ?? 0),
        kategori: r.kategori ?? r.category ?? "",
      }));

      setSearchResults(mapped);
      setShowDropdown(mapped.length > 0);
    } catch (err: any) {
      setSearchError(err.message ?? "Terjadi kesalahan saat mencari barang.");
      setSearchResults([]);
      setShowDropdown(false);
    } finally {
      setSearchLoading(false);
    }
  }, []);

  const handleSearchChange = (value: string) => {
    setSearchQuery(value);
    if (searchDebounce.current) clearTimeout(searchDebounce.current);
    searchDebounce.current = setTimeout(() => searchStock(value), 350);
  };

  // ── Add item from search result ───────────────────────────────
  const addItem = (result: SearchResult) => {
    if (result.id === 0) {
      setSearchError("Barang tidak memiliki ID yang valid. Hubungi administrator.");
      return;
    }

    // Prevent duplicate
    const alreadyAdded = items.some((i) => i.barang_id === result.id);
    if (alreadyAdded) {
      setSearchQuery("");
      setShowDropdown(false);
      return;
    }

    setItems((prev) => [
      ...prev,
      {
        barang_id:      result.id,
        nama_barang:    result.nama,
        satuan:         result.satuan,
        stok_tersedia:  result.stok,
        jumlah_diminta: 1,
        catatan:        "",
      },
    ]);

    setSearchQuery("");
    setSearchResults([]);
    setShowDropdown(false);
    setFieldErrors((prev) => ({ ...prev, items: "" }));
  };

  // ── Update / remove items ─────────────────────────────────────
  const updateItem = (index: number, field: keyof BonItem, value: any) => {
    setItems((prev) =>
      prev.map((item, i) => (i === index ? { ...item, [field]: value } : item))
    );
  };

  const removeItem = (index: number) => {
    setItems((prev) => prev.filter((_, i) => i !== index));
  };

  // ── Frontend validation ───────────────────────────────────────
  const validate = (): boolean => {
    const errors: Record<string, string> = {};

    if (!keperluan.trim()) {
      errors.keperluan = "Keperluan / tujuan pengajuan wajib diisi.";
    }

    if (items.length === 0) {
      errors.items = "Tambahkan minimal satu barang sebelum mengirim pengajuan.";
    } else {
      for (let i = 0; i < items.length; i++) {
        const item = items[i];
        if (!item.jumlah_diminta || item.jumlah_diminta < 1) {
          errors[`items_${i}`] = `Jumlah pada barang #${i + 1} harus minimal 1.`;
        }
      }
    }

    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  };

  // ── Submit ────────────────────────────────────────────────────
  const handleSubmit = async (status: "draft" | "menunggu_verifikasi") => {
    setSuccessMsg("");
    setErrorMsg("");

    if (!validate()) return;

    setIsSubmitting(true);
    setSubmitMode(status === "draft" ? "draft" : "kirim");

    const payload: BonSubmitPayload = {
      keperluan: keperluan.trim(),
      catatan:   catatan.trim(),
      status,
      items: items.map((item) => ({
        barang_id:     item.barang_id,
        jumlah_diminta: item.jumlah_diminta,
        catatan:        item.catatan.trim(),
      })),
    };

    try {
      await onSubmit(payload);

      // Reset form on success
      setKeperluan("");
      setCatatan("");
      setItems([]);
      setFieldErrors({});
      setSuccessMsg(
        status === "draft"
          ? "Draft BON berhasil disimpan. Anda dapat melengkapi dan mengirimnya nanti."
          : "BON berhasil dikirim dan menunggu verifikasi Petugas Persediaan."
      );
      setTimeout(() => setSuccessMsg(""), 6000);
    } catch (err: any) {
      setErrorMsg(
        err instanceof Error
          ? err.message
          : "Pengajuan BON gagal disimpan. Silakan coba kembali."
      );
    } finally {
      setIsSubmitting(false);
      setSubmitMode(null);
    }
  };

  // ── Render ────────────────────────────────────────────────────
  return (
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm space-y-6">

      {/* Header */}
      <div className="flex items-center gap-3">
        <div className="bg-amber-50 text-amber-600 p-2 rounded border border-amber-100 shrink-0">
          <ClipboardList size={18} />
        </div>
        <div>
          <h2 className="text-base font-extrabold text-slate-800 tracking-tight">
            BON Digital
          </h2>
          <p className="text-[11px] text-slate-500">
            Form pengajuan kebutuhan barang persediaan unit kerja
          </p>
        </div>
      </div>

      {/* Flash messages */}
      {successMsg && (
        <div className="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg p-3.5 text-xs font-semibold flex items-start gap-2">
          <CheckCircle size={14} className="text-emerald-600 mt-0.5 shrink-0" />
          <span>{successMsg}</span>
        </div>
      )}
      {errorMsg && (
        <div className="bg-rose-50 border border-rose-200 text-rose-700 rounded-lg p-3.5 text-xs font-semibold flex items-start gap-2">
          <AlertCircle size={14} className="text-rose-500 mt-0.5 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      {/* ── Section 1: Info dasar ── */}
      <div className="space-y-4">
        <h3 className="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2">
          Informasi Pengajuan
        </h3>

        {/* Keperluan — required */}
        <div>
          <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Keperluan / Tujuan Pengajuan <span className="text-rose-500">*</span>
          </label>
          <textarea
            rows={2}
            disabled={isSubmitting}
            placeholder="Contoh: Kebutuhan ATK untuk operasional bulanan Subbagian TU..."
            value={keperluan}
            onChange={(e) => {
              setKeperluan(e.target.value);
              if (fieldErrors.keperluan) setFieldErrors((p) => ({ ...p, keperluan: "" }));
            }}
            className={`w-full bg-slate-50 border rounded px-3 py-2 text-xs font-medium text-slate-700
              focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60 resize-none
              ${fieldErrors.keperluan ? "border-rose-400 bg-rose-50" : "border-slate-200"}`}
          />
          {fieldErrors.keperluan && (
            <p className="mt-1 text-[11px] text-rose-600 font-semibold flex items-center gap-1">
              <AlertCircle size={11} /> {fieldErrors.keperluan}
            </p>
          )}
        </div>

        {/* Pengaju — read-only */}
        <div>
          <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Nama Pengaju
          </label>
          <input
            type="text"
            value={currentUser}
            disabled
            className="w-full bg-slate-100 border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-500 cursor-not-allowed"
          />
        </div>

        {/* Catatan umum — optional */}
        <div>
          <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Catatan Tambahan
          </label>
          <textarea
            rows={2}
            disabled={isSubmitting}
            placeholder="Catatan opsional untuk Petugas Persediaan..."
            value={catatan}
            onChange={(e) => setCatatan(e.target.value)}
            className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-medium text-slate-700
              focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60 resize-none"
          />
        </div>
      </div>

      {/* ── Section 2: Daftar barang ── */}
      <div className="space-y-4">
        <h3 className="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2">
          Daftar Barang yang Diminta <span className="text-rose-500">*</span>
        </h3>

        {/* Search box */}
        <div className="relative" ref={dropdownRef}>
          <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Cari & Tambah Barang dari Gudang
          </label>
          <div className="relative">
            <Search
              size={14}
              className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
            />
            {searchLoading && (
              <Loader2
                size={14}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-indigo-500 animate-spin"
              />
            )}
            <input
              type="text"
              disabled={isSubmitting}
              value={searchQuery}
              onChange={(e) => handleSearchChange(e.target.value)}
              onFocus={() => searchResults.length > 0 && setShowDropdown(true)}
              placeholder="Ketik nama atau kode barang (min. 2 karakter)..."
              className="w-full pl-9 pr-9 py-2 bg-slate-50 border border-slate-200 rounded text-xs font-medium
                text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60"
            />
          </div>

          {/* Search error */}
          {searchError && (
            <p className="mt-1 text-[11px] text-rose-600 font-semibold">{searchError}</p>
          )}

          {/* Dropdown results */}
          {showDropdown && searchResults.length > 0 && (
            <div className="absolute z-50 top-full mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
              {searchResults.map((result) => {
                const alreadyAdded = items.some((i) => i.barang_id === result.id);
                return (
                  <button
                    key={result.id}
                    type="button"
                    disabled={alreadyAdded || result.id === 0}
                    onClick={() => addItem(result)}
                    className={`w-full text-left px-4 py-3 text-xs hover:bg-indigo-50 transition-colors border-b border-slate-100 last:border-0
                      ${alreadyAdded ? "opacity-40 cursor-not-allowed" : "cursor-pointer"}`}
                  >
                    <div className="flex items-center justify-between gap-3">
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                          <span className="font-mono text-[10px] text-indigo-600 font-bold">{result.kode}</span>
                          <span className="text-[10px] text-slate-400">{result.kategori}</span>
                        </div>
                        <p className="font-semibold text-slate-800 mt-0.5 truncate">{result.nama}</p>
                      </div>
                      <div className="text-right shrink-0">
                        <p className={`font-extrabold text-sm ${result.stok === 0 ? "text-rose-500" : result.stok <= 5 ? "text-amber-600" : "text-emerald-600"}`}>
                          {result.stok}
                        </p>
                        <p className="text-[10px] text-slate-400">{result.satuan}</p>
                      </div>
                    </div>
                    {alreadyAdded && (
                      <span className="text-[10px] text-indigo-500 font-bold">✓ Sudah ditambahkan</span>
                    )}
                  </button>
                );
              })}
            </div>
          )}
        </div>

        {/* Items error */}
        {fieldErrors.items && (
          <p className="text-[11px] text-rose-600 font-semibold flex items-center gap-1">
            <AlertCircle size={11} /> {fieldErrors.items}
          </p>
        )}

        {/* Item list */}
        {items.length === 0 ? (
          <div className="border-2 border-dashed border-slate-200 rounded-lg py-8 text-center">
            <Package className="mx-auto text-slate-300 mb-2" size={24} />
            <p className="text-xs font-semibold text-slate-400">Belum ada barang yang dipilih</p>
            <p className="text-[10px] text-slate-300 mt-0.5">
              Gunakan kotak pencarian di atas untuk menambah barang
            </p>
          </div>
        ) : (
          <div className="space-y-3">
            {items.map((item, index) => (
              <div
                key={item.barang_id}
                className="border border-slate-200 rounded-lg p-4 bg-slate-50/50 space-y-3"
              >
                {/* Item header */}
                <div className="flex items-start justify-between gap-3">
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-extrabold text-slate-800 truncate">{item.nama_barang}</p>
                    <p className="text-[10px] text-slate-400 mt-0.5">
                      Stok gudang:{" "}
                      <span className={`font-bold ${item.stok_tersedia === 0 ? "text-rose-500" : item.stok_tersedia <= 5 ? "text-amber-600" : "text-emerald-600"}`}>
                        {item.stok_tersedia} {item.satuan}
                      </span>
                    </p>
                  </div>
                  <button
                    type="button"
                    disabled={isSubmitting}
                    onClick={() => removeItem(index)}
                    className="text-slate-400 hover:text-rose-500 transition-colors p-1 rounded hover:bg-rose-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    aria-label="Hapus barang"
                  >
                    <Trash2 size={14} />
                  </button>
                </div>

                {/* Qty + catatan */}
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                      Jumlah Diminta <span className="text-rose-500">*</span>
                    </label>
                    <div className="flex items-center gap-2">
                      <input
                        type="number"
                        min={1}
                        disabled={isSubmitting}
                        value={item.jumlah_diminta}
                        onChange={(e) => {
                          const val = Math.max(1, Number(e.target.value) || 1);
                          updateItem(index, "jumlah_diminta", val);
                          if (fieldErrors[`items_${index}`]) {
                            setFieldErrors((p) => ({ ...p, [`items_${index}`]: "" }));
                          }
                        }}
                        className={`w-full bg-white border rounded px-3 py-1.5 text-xs font-semibold text-slate-700
                          focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60
                          ${fieldErrors[`items_${index}`] ? "border-rose-400" : "border-slate-200"}`}
                      />
                      <span className="text-[11px] text-slate-500 font-semibold shrink-0">
                        {item.satuan}
                      </span>
                    </div>
                    {fieldErrors[`items_${index}`] && (
                      <p className="mt-0.5 text-[10px] text-rose-600 font-semibold">
                        {fieldErrors[`items_${index}`]}
                      </p>
                    )}
                  </div>

                  <div>
                    <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                      Catatan Item
                    </label>
                    <input
                      type="text"
                      disabled={isSubmitting}
                      placeholder="Opsional..."
                      value={item.catatan}
                      onChange={(e) => updateItem(index, "catatan", e.target.value)}
                      className="w-full bg-white border border-slate-200 rounded px-3 py-1.5 text-xs font-medium
                        text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60"
                    />
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Total items badge */}
        {items.length > 0 && (
          <p className="text-[11px] text-slate-500 font-semibold text-right">
            {items.length} jenis barang dipilih
          </p>
        )}
      </div>

      {/* ── Section 3: Action buttons ── */}
      <div className="flex flex-col sm:flex-row gap-3 pt-2 border-t border-slate-100">
        {/* Simpan Draft */}
        <button
          type="button"
          disabled={isSubmitting}
          onClick={() => handleSubmit("draft")}
          className="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 border border-slate-300
            text-slate-700 bg-white hover:bg-slate-50 font-bold text-xs rounded transition-all
            disabled:opacity-50 disabled:cursor-not-allowed shadow-xs"
        >
          {isSubmitting && submitMode === "draft" ? (
            <Loader2 size={13} className="animate-spin" />
          ) : (
            <Save size={13} />
          )}
          {isSubmitting && submitMode === "draft" ? "Menyimpan Draft..." : "Simpan Draft"}
        </button>

        {/* Kirim Pengajuan */}
        <button
          type="button"
          disabled={isSubmitting}
          onClick={() => handleSubmit("menunggu_verifikasi")}
          className="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600
            hover:bg-indigo-700 text-white font-bold text-xs rounded transition-all
            disabled:opacity-50 disabled:cursor-not-allowed shadow-xs"
        >
          {isSubmitting && submitMode === "kirim" ? (
            <Loader2 size={13} className="animate-spin" />
          ) : (
            <Send size={13} />
          )}
          {isSubmitting && submitMode === "kirim" ? "Mengirim Pengajuan..." : "Kirim Pengajuan"}
        </button>
      </div>
    </div>
  );
};

// end of file
