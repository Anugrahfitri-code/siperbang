/**
 * BonDigitalForm.tsx
 *
 * Digunakan untuk DUA mode:
 *   1. Buat BON baru   — initialData tidak diberikan
 *   2. Edit draft      — initialData berisi data draft yang ada
 *
 * Kontrak payload ke App.tsx (sama untuk buat dan edit):
 *   { keperluan, catatan, status, items: [{ barang_id, jumlah_diminta, catatan }] }
 *
 * App.tsx yang memutuskan POST /api/requests (baru) atau
 * PUT /api/requests/bon/{id} (edit draft) berdasarkan editingDraft state.
 */

import React, { useState, useEffect, useCallback, useRef } from "react";
import {
  ClipboardList,
  Loader2,
  Search,
  Trash2,
  Send,
  Save,
  AlertCircle,
  CheckCircle,
  ArrowLeft,
  Package,
  Edit3,
  Info,
  ShoppingBag,
  Box,
  Tag,
  CircleDollarSign,
  Calendar,
  ShieldCheck,
  Filter,
  Sparkles,
} from "lucide-react";
import { apiFetch } from "../api";

// ── Types ──────────────────────────────────────────────────────────────────

interface BonItem {
  barang_id:     number;
  nama_barang:   string;
  satuan:        string;
  stok_tersedia: number;
  jumlah_diminta: number;
  catatan:       string;
}

export interface BonSubmitPayload {
  keperluan: string;
  catatan:   string;
  status:    "draft" | "menunggu_verifikasi";
  items: Array<{
    barang_id:      number;
    jumlah_diminta: number;
    catatan:        string;
  }>;
}

/** Shape of a draft BonHeader passed from the monitoring list */
export interface BonDraft {
  id:        number;
  bonNo:     string;
  keperluan: string;
  catatan:   string;
  items: Array<{
    stockItemId:   number;
    namaBarang:    string;
    satuan:        string;
    jumlahDiminta: number;
    catatan:       string;
  }>;
}

interface SearchResult {
  id:       number;
  kode:     string;
  nama:     string;
  satuan:   string;
  stok:     number;
  kategori: string;
}

interface BonDigitalFormProps {
  onSubmit:     (payload: BonSubmitPayload) => Promise<void>;
  currentUser:  string;
  /** Pre-fill the form with an existing draft (edit mode) */
  initialData?: BonDraft | null;
  /** Called when the user cancels editing a draft */
  onCancel?:    () => void;
}

// ── Component ─────────────────────────────────────────────────────────────

export const BonDigitalForm: React.FC<BonDigitalFormProps> = ({
  onSubmit,
  currentUser,
  initialData,
  onCancel,
}) => {
  const isEditMode = !!initialData;

  // ── Form fields ───────────────────────────────────────────────
  const [keperluan, setKeperluan] = useState("");
  const [catatan,   setCatatan]   = useState("");
  const [items,     setItems]     = useState<BonItem[]>([]);

  // Pre-fill when initialData changes (entering edit mode)
  useEffect(() => {
    if (!initialData) {
      // Reset ke mode buat baru
      setKeperluan("");
      setCatatan("");
      setItems([]);
      setSuccessMsg("");
      setErrorMsg("");
      setFieldErrors({});
      return;
    }

    setKeperluan(initialData.keperluan ?? "");
    setCatatan(initialData.catatan   ?? "");
    setSuccessMsg("");
    setErrorMsg("");
    setFieldErrors({});

    // Set items dengan data dari draft dulu (tanpa stok)
    const baseItems: BonItem[] = (initialData.items ?? []).map((it) => ({
      barang_id:      it.stockItemId,
      nama_barang:    it.namaBarang,
      satuan:         it.satuan,
      stok_tersedia:  0,
      jumlah_diminta: it.jumlahDiminta,
      catatan:        it.catatan ?? "",
    }));
    setItems(baseItems);

    // Fetch stok aktual untuk tiap item dari API supaya stok_tersedia akurat
    if (baseItems.length === 0) return;

    const fetchStokItems = async () => {
      try {
        // Gunakan search API dengan ID list — cari satu per satu dengan kode/nama
        const updated = await Promise.all(
          baseItems.map(async (item) => {
            if (item.barang_id === 0) return item;
            try {
              // Search by nama barang untuk dapatkan stok terkini
              const params = new URLSearchParams({ q: item.nama_barang, per_page: "5" });
              const res    = await apiFetch(`/api/stocks/search?${params}`);
              if (!res.ok) return item;
              const json   = await res.json();
              const rows: any[] = Array.isArray(json) ? json : (json.data ?? []);
              // Cari item dengan ID yang cocok
              const match = rows.find((r: any) => Number(r.id) === item.barang_id);
              if (match) {
                return {
                  ...item,
                  nama_barang:   match.nama ?? match.name ?? item.nama_barang,
                  satuan:        match.satuan ?? match.unit ?? item.satuan,
                  stok_tersedia: Number(match.stok ?? match.qty ?? 0),
                };
              }
              return item;
            } catch {
              return item;
            }
          })
        );
        setItems(updated);
      } catch {
        // Gagal fetch stok — form tetap bisa dipakai dengan data draft
      }
    };

    fetchStokItems();
  }, [initialData]);

  // ── Stock search ──────────────────────────────────────────────
  const [searchQuery,   setSearchQuery]   = useState("");
  const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
  const [searchLoading, setSearchLoading] = useState(false);
  const [searchError,   setSearchError]   = useState<string | null>(null);
  const [showDropdown,  setShowDropdown]  = useState(false);
  const debounceRef  = useRef<ReturnType<typeof setTimeout> | null>(null);
  const dropdownRef  = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setShowDropdown(false);
      }
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  const searchStock = useCallback(async (q: string) => {
    if (q.trim().length < 2) { setSearchResults([]); setShowDropdown(false); return; }
    setSearchLoading(true);
    setSearchError(null);
    try {
      const params = new URLSearchParams({ q: q.trim(), per_page: "15" });
      const res    = await apiFetch(`/api/stocks/search?${params}`);
      if (!res.ok) throw new Error("Gagal memuat data barang.");
      const json   = await res.json();
      const rows: any[] = Array.isArray(json) ? json : (json.data ?? []);
      const mapped: SearchResult[] = rows.map((r: any) => ({
        id:       Number(r.id ?? 0),
        kode:     r.kode  ?? r.code     ?? "",
        nama:     r.nama  ?? r.name     ?? "",
        satuan:   r.satuan ?? r.unit    ?? "",
        stok:     Number(r.stok ?? r.qty ?? 0),
        kategori: r.kategori ?? r.category ?? "",
      }));
      setSearchResults(mapped);
      setShowDropdown(mapped.length > 0);
    } catch (err: any) {
      setSearchError(err.message ?? "Kesalahan saat mencari barang.");
      setSearchResults([]);
      setShowDropdown(false);
    } finally {
      setSearchLoading(false);
    }
  }, []);

  const handleSearchChange = (value: string) => {
    setSearchQuery(value);
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => searchStock(value), 350);
  };

  const addItem = (result: SearchResult) => {
    if (result.id === 0) { setSearchError("ID barang tidak valid."); return; }
    if (items.some((i) => i.barang_id === result.id)) {
      setSearchQuery(""); setShowDropdown(false); return;
    }
    setItems((prev) => [
      ...prev,
      { barang_id: result.id, nama_barang: result.nama, satuan: result.satuan,
        stok_tersedia: result.stok, jumlah_diminta: 1, catatan: "" },
    ]);
    setSearchQuery(""); setSearchResults([]); setShowDropdown(false);
    setFieldErrors((p) => ({ ...p, items: "" }));
  };

  const updateItem = (index: number, field: keyof BonItem, value: any) =>
    setItems((prev) => prev.map((item, i) => (i === index ? { ...item, [field]: value } : item)));

  const removeItem = (index: number) =>
    setItems((prev) => prev.filter((_, i) => i !== index));

  // ── Submission state ──────────────────────────────────────────
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitMode,   setSubmitMode]   = useState<"draft" | "kirim" | null>(null);
  const [successMsg,   setSuccessMsg]   = useState("");
  const [errorMsg,     setErrorMsg]     = useState("");
  const [fieldErrors,  setFieldErrors]  = useState<Record<string, string>>({});

  const validate = (): boolean => {
    const errors: Record<string, string> = {};
    if (!keperluan.trim()) errors.keperluan = "Keperluan / tujuan pengajuan wajib diisi.";
    if (items.length === 0) {
      errors.items = "Tambahkan minimal satu barang.";
    } else {
      items.forEach((item, i) => {
        if (!item.jumlah_diminta || item.jumlah_diminta < 1)
          errors[`items_${i}`] = `Jumlah barang #${i + 1} harus minimal 1.`;
      });
    }
    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (status: "draft" | "menunggu_verifikasi") => {
    setSuccessMsg(""); setErrorMsg("");
    if (!validate()) return;

    setIsSubmitting(true);
    setSubmitMode(status === "draft" ? "draft" : "kirim");

    const payload: BonSubmitPayload = {
      keperluan: keperluan.trim(),
      catatan:   catatan.trim(),
      status,
      items: items.map((it) => ({
        barang_id:      it.barang_id,
        jumlah_diminta: it.jumlah_diminta,
        catatan:        it.catatan.trim(),
      })),
    };

    try {
      await onSubmit(payload);

      if (!isEditMode) {
        setKeperluan(""); setCatatan(""); setItems([]); setFieldErrors({});
      }

      if (status === "draft") {
        setSuccessMsg(
          isEditMode
            ? "Draft berhasil diperbarui."
            : "Draft BON berhasil disimpan. Buka tab Monitoring untuk melanjutkan."
        );
      } else {
        setSuccessMsg("BON berhasil dikirim dan menunggu verifikasi Petugas Persediaan.");
        if (isEditMode) onCancel?.(); // kembali ke monitoring setelah kirim
      }
      setTimeout(() => setSuccessMsg(""), 6000);
    } catch (err: any) {
      setErrorMsg(err instanceof Error ? err.message : "Pengajuan gagal disimpan.");
    } finally {
      setIsSubmitting(false);
      setSubmitMode(null);
    }
  };

  // ── Render ────────────────────────────────────────────────────
  return (
    <div className="space-y-6">
      {/* HEADER BANNER */}
      <div className="relative bg-gradient-to-r from-[#f0f4ff] to-[#f8faff] rounded-2xl border border-indigo-50/50 p-6 shadow-sm overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
        {/* Background shapes (optional) */}
        <div className="absolute -top-24 -right-24 w-64 h-64 bg-indigo-100/40 rounded-full blur-3xl pointer-events-none"></div>
        <div className="absolute -bottom-24 right-32 w-64 h-64 bg-purple-100/40 rounded-full blur-3xl pointer-events-none"></div>

        <div className="relative z-10 flex-1 space-y-4">
          <div className="flex items-center gap-4">
            <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm border border-indigo-100 text-indigo-600">
              {isEditMode ? <Edit3 size={28} strokeWidth={2.5} /> : <ClipboardList size={28} strokeWidth={2.5} />}
            </div>
            <div>
              <h2 className="text-xl font-extrabold text-slate-900 tracking-tight">
                {isEditMode ? `Edit Draft — ${initialData?.bonNo}` : "BON Digital"}
              </h2>
              <p className="text-sm font-medium text-slate-500 mt-1">
                {isEditMode
                  ? "Lengkapi dan kirim draft pengajuan ini, atau simpan kembali sebagai draft."
                  : "Form pengajuan kebutuhan barang persediaan unit kerja."}
              </p>
            </div>
          </div>

          {!isEditMode && (
            <div className="inline-flex items-start gap-3 bg-indigo-50/80 rounded-xl p-3 border border-indigo-100/50 max-w-2xl">
              <Info size={18} className="text-indigo-500 shrink-0 mt-0.5" />
              <p className="text-xs font-semibold text-indigo-900 leading-relaxed">
                Pastikan data yang diisi sudah benar sebelum mengajukan.<br />
                <span className="font-medium text-indigo-700">Pengajuan akan diverifikasi oleh Petugas Persediaan.</span>
              </p>
            </div>
          )}
        </div>
      </div>

      {/* Flash Messages */}
      {successMsg && (
        <div className="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 text-sm font-semibold flex items-center gap-3 animate-fade-in shadow-sm">
          <CheckCircle size={18} className="text-emerald-600 shrink-0" />
          <span>{successMsg}</span>
        </div>
      )}
      {errorMsg && (
        <div className="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-4 text-sm font-semibold flex items-center gap-3 animate-fade-in shadow-sm">
          <AlertCircle size={18} className="text-rose-500 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      {/* MAIN GRID LAYOUT */}
      <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {/* LEFT COLUMN: FORMS */}
        <div className="xl:col-span-2 space-y-6">
          
          {/* Section 1: Informasi Pengajuan */}
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div className="flex items-center gap-3 mb-6">
              <div className="flex items-center justify-center size-8 rounded-lg bg-indigo-500 text-white font-bold text-sm shadow-sm">
                1
              </div>
              <div>
                <h3 className="text-sm font-extrabold text-slate-800">Informasi Pengajuan</h3>
                <p className="text-xs font-medium text-slate-500 mt-0.5">Lengkapi informasi dasar pengajuan kebutuhan barang.</p>
              </div>
            </div>

            <div className="space-y-5">
              <div>
                <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                  Keperluan / Tujuan Pengajuan <span className="text-rose-500">*</span>
                </label>
                <div className="relative">
                  <textarea
                    rows={2} disabled={isSubmitting}
                    placeholder="Contoh: Kebutuhan ATK untuk operasional bulanan Subbagian TU..."
                    value={keperluan}
                    onChange={(e) => {
                      setKeperluan(e.target.value);
                      if (fieldErrors.keperluan) setFieldErrors((p) => ({ ...p, keperluan: "" }));
                    }}
                    className={`w-full bg-slate-50/50 border rounded-xl px-4 py-3 text-sm font-medium text-slate-700
                      focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 disabled:opacity-60 resize-none transition-all
                      ${fieldErrors.keperluan ? "border-rose-400 bg-rose-50/50" : "border-slate-200 hover:border-slate-300"}`}
                  />
                  <Edit3 size={14} className="absolute right-3 bottom-3 text-slate-400" />
                </div>
                {fieldErrors.keperluan && (
                  <p className="mt-1.5 text-xs text-rose-600 font-semibold flex items-center gap-1.5">
                    <AlertCircle size={12} /> {fieldErrors.keperluan}
                  </p>
                )}
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    Nama Pengaju
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                       <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-slate-400"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <input type="text" value={`${currentUser} (Ketua Tim Kerja)`} disabled
                      className="w-full pl-9 pr-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 cursor-not-allowed" />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    Catatan Tambahan
                  </label>
                  <div className="relative">
                    <textarea rows={1} disabled={isSubmitting}
                      placeholder="Catatan opsional untuk Petugas Persediaan..."
                      value={catatan}
                      onChange={(e) => setCatatan(e.target.value)}
                      className="w-full bg-slate-50/50 border border-slate-200 hover:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700
                        focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 disabled:opacity-60 resize-none transition-all" />
                     <Edit3 size={14} className="absolute right-3 bottom-3 text-slate-400" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Section 2: Daftar Barang */}
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div className="flex items-center gap-3 mb-6">
              <div className="flex items-center justify-center size-8 rounded-lg bg-indigo-500 text-white font-bold text-sm shadow-sm">
                2
              </div>
              <div>
                <h3 className="text-sm font-extrabold text-slate-800">Daftar Barang yang Diminta</h3>
                <p className="text-xs font-medium text-slate-500 mt-0.5">Cari dan tambahkan barang dari katalog gudang.</p>
              </div>
            </div>

            {/* Stock search */}
            <div className="relative mb-5" ref={dropdownRef}>
              <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                Cari &amp; Tambah Barang dari Gudang
              </label>
              <div className="flex items-center gap-3">
                <div className="relative flex-1">
                  <Search size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                  {searchLoading && (
                    <Loader2 size={16} className="absolute right-16 top-1/2 -translate-y-1/2 text-indigo-500 animate-spin" />
                  )}
                  <input
                    type="text" disabled={isSubmitting} value={searchQuery}
                    onChange={(e) => handleSearchChange(e.target.value)}
                    onFocus={() => searchResults.length > 0 && setShowDropdown(true)}
                    placeholder="Ketik nama atau kode barang (min. 2 karakter)..."
                    className="w-full pl-10 pr-16 py-2.5 bg-white border border-slate-200 hover:border-indigo-300 rounded-xl text-sm font-medium
                      text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 disabled:opacity-60 transition-all shadow-sm"
                  />
                  <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                    <span className="text-[10px] font-bold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">Ctrl + K</span>
                  </div>
                </div>
                
                <button type="button" className="flex items-center gap-2 px-4 py-2.5 border border-slate-200 hover:border-slate-300 bg-white rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors shadow-sm whitespace-nowrap">
                  <Filter size={14} />
                  Filter Kategori
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="ml-1 opacity-50"><path d="m6 9 6 6 6-6"/></svg>
                </button>
              </div>
              
              {searchError && <p className="mt-1.5 text-xs text-rose-600 font-semibold">{searchError}</p>}

              {showDropdown && searchResults.length > 0 && (
                <div className="absolute z-50 top-[calc(100%+0.5rem)] w-[calc(100%-140px)] bg-white border border-slate-200 rounded-xl shadow-xl max-h-64 overflow-y-auto">
                  {searchResults.map((result) => {
                    const alreadyAdded = items.some((i) => i.barang_id === result.id);
                    return (
                      <button key={result.id} type="button"
                        disabled={alreadyAdded || result.id === 0}
                        onClick={() => addItem(result)}
                        className={`w-full text-left px-4 py-3 hover:bg-indigo-50/50 transition-colors border-b border-slate-100 last:border-0
                          ${alreadyAdded ? "opacity-40 cursor-not-allowed bg-slate-50" : "cursor-pointer"}`}
                      >
                        <div className="flex items-center justify-between gap-4">
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 flex-wrap mb-1">
                              <span className="font-mono text-[10px] bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold border border-indigo-100">{result.kode}</span>
                              <span className="text-[10px] font-semibold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">{result.kategori}</span>
                            </div>
                            <p className="font-bold text-sm text-slate-800 truncate">{result.nama}</p>
                          </div>
                          <div className="text-right shrink-0">
                            <p className={`font-extrabold text-sm ${result.stok === 0 ? "text-rose-500" : result.stok <= 5 ? "text-amber-600" : "text-emerald-600"}`}>
                              {result.stok}
                            </p>
                            <p className="text-[10px] font-semibold text-slate-400 uppercase">{result.satuan}</p>
                          </div>
                        </div>
                        {alreadyAdded && <span className="text-[10px] text-indigo-500 font-bold mt-1 block">✓ Sudah ditambahkan ke daftar</span>}
                      </button>
                    );
                  })}
                </div>
              )}
            </div>

            {fieldErrors.items && (
              <p className="text-xs text-rose-600 font-semibold flex items-center gap-1.5 mb-4">
                <AlertCircle size={12} /> {fieldErrors.items}
              </p>
            )}

            {items.length === 0 ? (
              <div className="border border-dashed border-slate-200 bg-slate-50/50 rounded-xl py-12 text-center flex flex-col items-center justify-center">
                <div className="relative mb-4">
                  <div className="size-16 bg-indigo-50 rounded-full flex items-center justify-center border border-indigo-100">
                    <Package className="text-indigo-300" size={32} />
                  </div>
                  <Sparkles className="absolute -top-1 -right-1 text-indigo-400" size={16} />
                </div>
                <h4 className="text-sm font-extrabold text-slate-800 mb-1">Belum ada barang yang dipilih</h4>
                <p className="text-xs font-medium text-slate-500 mb-5">Gunakan kotak pencarian di atas untuk menambah barang</p>
                <button type="button" onClick={() => document.querySelector('input[placeholder*="Ketik nama"]')?.focus()} className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 transition-colors">
                  <Search size={14} />
                  Cari Barang Sekarang
                </button>
              </div>
            ) : (
              <div className="space-y-3">
                {items.map((item, index) => (
                  <div key={`${item.barang_id}-${index}`}
                    className="border border-slate-200 rounded-xl p-4 bg-white shadow-sm hover:border-indigo-200 transition-colors">
                    <div className="flex items-start justify-between gap-4 mb-4">
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-bold text-slate-800 leading-tight">{item.nama_barang}</p>
                        {item.stok_tersedia > 0 && (
                          <p className="text-xs font-medium text-slate-500 mt-1">
                            Stok gudang:{" "}
                            <span className={`font-bold ${item.stok_tersedia <= 5 ? "text-amber-600" : "text-emerald-600"}`}>
                              {item.stok_tersedia} {item.satuan}
                            </span>
                          </p>
                        )}
                      </div>
                      <button type="button" disabled={isSubmitting} onClick={() => removeItem(index)}
                        className="text-slate-400 hover:text-rose-500 bg-white hover:bg-rose-50 border border-transparent hover:border-rose-100 transition-all p-1.5 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed"
                        aria-label="Hapus barang">
                        <Trash2 size={16} />
                      </button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50/50 p-3 rounded-lg border border-slate-100">
                      <div>
                        <label className="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-1.5">
                          Jumlah Diminta <span className="text-rose-500">*</span>
                        </label>
                        <div className="flex items-center gap-2">
                          <input type="number" min={1} disabled={isSubmitting}
                            value={item.jumlah_diminta}
                            onChange={(e) => {
                              const val = Math.max(1, Number(e.target.value) || 1);
                              updateItem(index, "jumlah_diminta", val);
                              if (fieldErrors[`items_${index}`])
                                setFieldErrors((p) => ({ ...p, [`items_${index}`]: "" }));
                            }}
                            className={`w-full max-w-[120px] bg-white border rounded-lg px-3 py-2 text-sm font-bold text-slate-800
                              focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 disabled:opacity-60 shadow-sm
                              ${fieldErrors[`items_${index}`] ? "border-rose-400" : "border-slate-200"}`}
                          />
                          <span className="text-xs text-slate-600 font-bold bg-white border border-slate-200 px-3 py-2 rounded-lg shadow-sm shrink-0">{item.satuan}</span>
                        </div>
                        {fieldErrors[`items_${index}`] && (
                          <p className="mt-1 text-[10px] text-rose-600 font-bold">{fieldErrors[`items_${index}`]}</p>
                        )}
                      </div>

                      <div>
                        <label className="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-1.5">
                          Catatan Item (Opsional)
                        </label>
                        <input type="text" disabled={isSubmitting} placeholder="Contoh: Ukuran F4..."
                          value={item.catatan}
                          onChange={(e) => updateItem(index, "catatan", e.target.value)}
                          className="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-medium
                            text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 disabled:opacity-60 shadow-sm"
                        />
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* RIGHT COLUMN: SIDEBAR SUMMARY */}
        <div className="xl:col-span-1 space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm sticky top-6">
            <div className="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
              <div className="flex items-center gap-2 text-slate-800">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-indigo-600"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                <h3 className="text-sm font-extrabold">Ringkasan Pengajuan</h3>
              </div>
              <button className="text-slate-400 hover:text-slate-600"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg></button>
            </div>

            <div className="grid grid-cols-1 gap-3 mb-6">
              <div className="flex items-center justify-between p-3.5 bg-slate-50/50 border border-slate-100 rounded-xl">
                <div className="flex items-center gap-3">
                  <div className="size-8 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center"><ShoppingBag size={16} /></div>
                  <span className="text-xs font-bold text-slate-600">Jumlah Item</span>
                </div>
                <span className="text-sm font-extrabold text-slate-800">{items.reduce((acc, curr) => acc + (curr.jumlah_diminta || 0), 0)}</span>
              </div>
              
              <div className="flex items-center justify-between p-3.5 bg-slate-50/50 border border-slate-100 rounded-xl">
                <div className="flex items-center gap-3">
                  <div className="size-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center"><Box size={16} /></div>
                  <span className="text-xs font-bold text-slate-600">Total Barang (Jenis)</span>
                </div>
                <span className="text-sm font-extrabold text-slate-800">{items.length}</span>
              </div>

              <div className="flex items-center justify-between p-3.5 bg-slate-50/50 border border-slate-100 rounded-xl">
                <div className="flex items-center gap-3">
                  <div className="size-8 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center"><Tag size={16} /></div>
                  <span className="text-xs font-bold text-slate-600">Kategori</span>
                </div>
                <span className="text-sm font-extrabold text-slate-800">
                  {new Set(items.map(it => searchResults.find(s => s.id === it.barang_id)?.kategori).filter(Boolean)).size}
                </span>
              </div>

              <div className="flex items-center justify-between p-3.5 bg-slate-50/50 border border-slate-100 rounded-xl">
                <div className="flex items-center gap-3">
                  <div className="size-8 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center"><CircleDollarSign size={16} /></div>
                  <span className="text-xs font-bold text-slate-600">Estimasi Nilai</span>
                </div>
                <span className="text-sm font-extrabold text-slate-800">Rp 0</span>
              </div>
            </div>

            <div className="space-y-4 pt-4 border-t border-slate-100 mb-6">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-slate-500">Status Pengajuan</span>
                <span className="px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold rounded uppercase tracking-wider">{isEditMode ? "Draft Tersimpan" : "Draft"}</span>
              </div>
              <div className="flex items-center gap-2 text-slate-500">
                <Calendar size={14} />
                <div className="flex flex-col">
                  <span className="text-[10px] font-bold uppercase tracking-wider">Dibuat</span>
                  <span className="text-xs font-medium text-slate-700">{new Date().toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })} WIB</span>
                </div>
              </div>
            </div>

            <div className="flex items-start gap-2.5 bg-blue-50/50 border border-blue-100 p-3 rounded-xl mb-6">
              <ShieldCheck size={16} className="text-blue-500 shrink-0 mt-0.5" />
              <p className="text-[10px] font-semibold text-blue-800 leading-relaxed">
                Data pengajuan disimpan otomatis.<br />
                <span className="text-blue-600/80 font-medium">Anda dapat melanjutkan nanti</span>
              </p>
            </div>

            {/* Actions */}
            <div className="space-y-3">
              <button type="button" disabled={isSubmitting} onClick={() => handleSubmit("menunggu_verifikasi")}
                className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600
                  hover:bg-blue-700 text-white font-bold text-xs rounded-xl transition-all
                  disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                {isSubmitting && submitMode === "kirim"
                  ? <Loader2 size={14} className="animate-spin" />
                  : <Send size={14} />}
                {isSubmitting && submitMode === "kirim"
                  ? "Mengirim..."
                  : isEditMode ? "Kirim Sekarang" : "Kirim Pengajuan"}
              </button>

              <button type="button" disabled={isSubmitting} onClick={() => handleSubmit("draft")}
                className="w-full flex items-center justify-center gap-2 px-4 py-3 border border-slate-200
                  text-slate-700 bg-white hover:bg-slate-50 font-bold text-xs rounded-xl transition-all
                  disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                {isSubmitting && submitMode === "draft"
                  ? <Loader2 size={14} className="animate-spin" />
                  : <Save size={14} />}
                {isSubmitting && submitMode === "draft"
                  ? "Menyimpan..."
                  : isEditMode ? "Simpan Perubahan Draft" : "Simpan Draft"}
              </button>
              
              {isEditMode && onCancel && (
                <button type="button" onClick={onCancel} disabled={isSubmitting}
                  className="w-full flex items-center justify-center gap-2 px-4 py-2 text-slate-500 hover:text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-lg transition-all
                    disabled:opacity-50 disabled:cursor-not-allowed mt-2">
                  <ArrowLeft size={14} />
                  Batal / Kembali
                </button>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
