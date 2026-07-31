import React, { useState, useEffect, useRef } from "react";
import {
  Globe,
  Save,
  Upload,
  CheckCircle,
  AlertCircle,
  Loader2,
  Building2,
  LogIn,
  Layout,
  Type,
} from "lucide-react";
import { apiFetch } from "../api";
import { useSettings } from "../context/SettingsContext";
import { sanitizeHtml } from "../utils/sanitizeHtml";

interface SiteSettingsData {
  app_name?: string;
  app_subtitle?: string;
  instansi_name?: string;
  instansi_sub?: string;
  login_heading?: string;
  login_description?: string;
  footer_copyright?: string;
  app_logo_url?: string;
  instansi_logo_url?: string;
}

interface SiteSettingsProps {
  onSettingsUpdated?: () => void;
}

interface RichTextEditorProps {
  value: string;
  onChange: (html: string) => void;
  placeholder?: string;
  sizeOptions?: string[];
  defaultColor?: string;
  minHeight?: string;
}

/* ────────────────────────────────────────────────────────────
   WYSIWYG Rich Text Editor Component (Selection & Sticky Cursor Preservation)
──────────────────────────────────────────────────────────── */
const RichTextEditor: React.FC<RichTextEditorProps> = ({
  value,
  onChange,
  placeholder = "Ketik teks di sini...",
  sizeOptions = ["14px", "16px", "18px", "20px", "24px", "28px", "32px", "36px", "40px", "48px"],
  defaultColor = "#0055A5",
  minHeight = "90px",
}) => {
  const editorRef = useRef<HTMLDivElement>(null);
  const [color, setColor] = useState(defaultColor);
  const [selectedSize, setSelectedSize] = useState(sizeOptions[Math.floor(sizeOptions.length / 2)] || "16px");
  const savedRangeRef = useRef<Range | null>(null);

  useEffect(() => {
    if (editorRef.current && editorRef.current.innerHTML !== value) {
      editorRef.current.innerHTML = value || "";
    }
  }, [value]);

  const handleInput = () => {
    if (editorRef.current) {
      onChange(editorRef.current.innerHTML);
    }
  };

  // Simpan posisi / seleksi kursor
  const saveSelection = () => {
    const selection = window.getSelection();
    if (selection && selection.rangeCount > 0) {
      const range = selection.getRangeAt(0);
      if (editorRef.current && editorRef.current.contains(range.commonAncestorContainer)) {
        savedRangeRef.current = range.cloneRange();
      }
    }
  };

  // Kembalikan posisi / seleksi kursor
  const restoreSelection = () => {
    if (savedRangeRef.current && editorRef.current) {
      const selection = window.getSelection();
      if (selection) {
        selection.removeAllRanges();
        selection.addRange(savedRangeRef.current);
      }
    }
  };

  const applyFormat = (command: string, arg: string | null = null) => {
    if (!editorRef.current) return;
    editorRef.current.focus();
    restoreSelection();
    document.execCommand(command, false, arg ?? undefined);
    saveSelection();
    handleInput();
  };

  const applyStyleToSelectionOrCursor = (styleProperty: string, styleValue: string) => {
    if (!editorRef.current) return;
    editorRef.current.focus();
    restoreSelection();

    const selection = window.getSelection();

    if (selection && selection.rangeCount > 0) {
      const range = selection.getRangeAt(0);

      if (editorRef.current.contains(range.commonAncestorContainer)) {
        if (!range.collapsed) {
          // Kasus 1: Teks sedang disorot/diblok -> Bungkus seleksi & pertahankan seleksi terblok
          const span = document.createElement("span");
          span.style.setProperty(styleProperty, styleValue);
          try {
            span.appendChild(range.extractContents());
            range.insertNode(span);

            // Jaga agar teks tetap dalam keadaan terblok
            selection.removeAllRanges();
            const newRange = document.createRange();
            newRange.selectNodeContents(span);
            selection.addRange(newRange);
            savedRangeRef.current = newRange.cloneRange();
          } catch {
            if (styleProperty === "color") {
              document.execCommand("foreColor", false, styleValue);
            }
          }
        } else {
          // Kasus 2: Kursor hanya berkedip (sebelum mengetik) -> Buat span gaya melekat untuk teks baru
          if (styleProperty === "color") {
            document.execCommand("foreColor", false, styleValue);
          } else {
            const span = document.createElement("span");
            span.style.setProperty(styleProperty, styleValue);
            const zeroWidthSpace = document.createTextNode("\u200B");
            span.appendChild(zeroWidthSpace);
            range.insertNode(span);

            // Tempatkan kursor di dalam span gaya baru
            const newRange = document.createRange();
            newRange.setStartAfter(zeroWidthSpace);
            newRange.setEndAfter(zeroWidthSpace);
            selection.removeAllRanges();
            selection.addRange(newRange);
            savedRangeRef.current = newRange.cloneRange();
          }
        }
      }
    } else {
      if (styleProperty === "color") {
        document.execCommand("foreColor", false, styleValue);
      }
    }
    handleInput();
  };

  return (
    <div className="border border-slate-200 rounded-xl overflow-hidden bg-slate-50 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20 transition-all">
      {/* Toolbar */}
      <div className="flex items-center gap-2 flex-wrap p-2.5 bg-slate-100/80 border-b border-slate-200">
        <span className="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Format Seleksi / Teks:</span>

        {/* Bold */}
        <button
          type="button"
          title="Tebalkan Teks Terpilih (Bold)"
          onMouseDown={(e) => {
            e.preventDefault();
            applyFormat("bold");
          }}
          className="w-8 h-8 rounded-lg text-sm font-black border bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-400 active:bg-slate-200 transition-all select-none flex items-center justify-center shadow-xs"
        >
          B
        </button>

        {/* Italic */}
        <button
          type="button"
          title="Miringkan Teks Terpilih (Italic)"
          onMouseDown={(e) => {
            e.preventDefault();
            applyFormat("italic");
          }}
          className="w-8 h-8 rounded-lg text-sm italic font-bold border bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-400 active:bg-slate-200 transition-all select-none flex items-center justify-center shadow-xs"
        >
          I
        </button>

        {/* Underline */}
        <button
          type="button"
          title="Garis Bawah Teks Terpilih (Underline)"
          onMouseDown={(e) => {
            e.preventDefault();
            applyFormat("underline");
          }}
          className="w-8 h-8 rounded-lg text-sm underline font-bold border bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-400 active:bg-slate-200 transition-all select-none flex items-center justify-center shadow-xs"
        >
          U
        </button>

        <div className="w-px h-5 bg-slate-300 mx-0.5 shrink-0" />

        {/* Color Picker */}
        <label
          className="flex items-center gap-1.5 px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-slate-400 transition-all shadow-xs"
          title="Ubah Warna Teks Terpilih / Gaya Mengetik"
          onMouseDown={(e) => e.preventDefault()}
        >
          <span className="text-[10px] font-bold text-slate-600 uppercase">Warna</span>
          <div className="w-4 h-4 rounded-full border border-slate-300 shrink-0" style={{ backgroundColor: color }} />
          <input
            type="color"
            value={color}
            onFocus={() => saveSelection()}
            onChange={(e) => {
              setColor(e.target.value);
              applyStyleToSelectionOrCursor("color", e.target.value);
            }}
            className="sr-only"
          />
        </label>

        {/* Font Size */}
        <select
          value={selectedSize}
          onFocus={() => saveSelection()}
          onChange={(e) => {
            setSelectedSize(e.target.value);
            applyStyleToSelectionOrCursor("font-size", e.target.value);
          }}
          title="Ubah Ukuran Font Teks Terpilih / Gaya Mengetik"
          className="px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:outline-none focus:border-indigo-500 cursor-pointer hover:border-slate-400 transition-all shadow-xs"
        >
          {sizeOptions.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>

        {/* Reset Formatting */}
        <button
          type="button"
          title="Hapus Format Teks Terpilih"
          onMouseDown={(e) => {
            e.preventDefault();
            applyFormat("removeFormat");
          }}
          className="px-2 py-1.5 rounded-lg text-[11px] font-semibold border bg-white text-rose-600 border-slate-200 hover:bg-rose-50 hover:border-rose-300 transition-all shadow-xs ml-auto"
        >
          Reset Format
        </button>
      </div>

      {/* Editable Area */}
      <div
        ref={editorRef}
        contentEditable
        onInput={handleInput}
        onBlur={handleInput}
        onKeyUp={() => saveSelection()}
        onMouseUp={() => saveSelection()}
        style={{ minHeight }}
        className="p-4 bg-white text-slate-800 text-base focus:outline-none leading-relaxed font-sans cursor-text"
      />
    </div>
  );
};

/* ────────────────────────────────────────────────────────────
   Main SiteSettings Component
──────────────────────────────────────────────────────────── */
export const SiteSettings: React.FC<SiteSettingsProps> = ({ onSettingsUpdated }) => {
  const { settings, isLoading, refetchSettings } = useSettings();
  const [saving, setSaving] = useState(false);
  const [successMsg, setSuccessMsg] = useState("");
  const [errorMsg, setErrorMsg] = useState("");

  // State Form Teks
  const [appName, setAppName] = useState("SIPERBANG");
  const [appSubtitle, setAppSubtitle] = useState("Sistem Informasi Persediaan Barang");
  const [instansiName, setInstansiName] = useState("KOMDIGI");
  const [instansiSub, setInstansiSub] = useState("Kementerian Komunikasi dan Digital Republik Indonesia");
  const [loginHeading, setLoginHeading] = useState("Selamat Datang di Portal SIPERBANG.");
  const [loginDescription, setLoginDescription] = useState(
    "Pusat pengelolaan persediaan barang secara digital, dilengkapi fitur verifikasi nota otomatis menggunakan teknologi OCR AI, dan pemantauan stok real-time."
  );
  const [footerCopyright, setFooterCopyright] = useState(
    "© 2026 BBPSDM Komunikasi dan Digital Makassar. Seluruh hak cipta dilindungi."
  );

  // State File Logo & Preview URL
  const [appLogoFile, setAppLogoFile] = useState<File | null>(null);
  const [appLogoPreview, setAppLogoPreview] = useState<string>("/images/icon baru.png");

  const [instansiLogoFile, setInstansiLogoFile] = useState<File | null>(null);
  const [instansiLogoPreview, setInstansiLogoPreview] = useState<string>("/images/komdigi-logo.png");

  // Fetch settings dari API saat pertama dimuat
  useEffect(() => {
    if (!settings) {
      return;
    }

    setAppName(settings.app_name || "SIPERBANG");
    setAppSubtitle(settings.app_subtitle || "Sistem Informasi Persediaan Barang");
    setInstansiName(settings.instansi_name || "KOMDIGI");
    setInstansiSub(settings.instansi_sub || "Kementerian Komunikasi dan Digital Republik Indonesia");

    setLoginHeading(settings.login_heading || "Selamat Datang di Portal SIPERBANG.");
    setLoginDescription(
      settings.login_description ||
        "Pusat pengelolaan persediaan barang secara digital, dilengkapi fitur verifikasi nota otomatis menggunakan teknologi OCR AI, dan pemantauan stok real-time."
    );

    setFooterCopyright(settings.footer_copyright || "© 2026 BBPSDM Komunikasi dan Digital Makassar. Seluruh hak cipta dilindungi.");
    setAppLogoPreview(settings.app_logo_url || "/images/icon baru.png");
    setInstansiLogoPreview(settings.instansi_logo_url || "/images/komdigi-logo.png");
  }, [settings]);

  const handleAppLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      const file = e.target.files[0];
      setAppLogoFile(file);
      setAppLogoPreview(URL.createObjectURL(file));
    }
  };

  const handleInstansiLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      const file = e.target.files[0];
      setInstansiLogoFile(file);
      setInstansiLogoPreview(URL.createObjectURL(file));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setSuccessMsg("");
    setErrorMsg("");

    try {
      const formData = new FormData();
      formData.append("app_name", appName);
      formData.append("app_subtitle", appSubtitle);
      formData.append("instansi_name", instansiName);
      formData.append("instansi_sub", instansiSub);
      formData.append("login_heading", loginHeading);
      formData.append("login_description", loginDescription);
      formData.append("footer_copyright", footerCopyright);

      if (appLogoFile) {
        formData.append("app_logo", appLogoFile);
      }
      if (instansiLogoFile) {
        formData.append("instansi_logo", instansiLogoFile);
      }

      const res = await apiFetch("/api/settings", {
        method: "POST",
        body: formData,
      });

      const data = await res.json();

      if (res.ok) {
        setSuccessMsg("Pengaturan situs dan identitas visual berhasil diperbarui.");
        await refetchSettings();
        if (onSettingsUpdated) {
          onSettingsUpdated();
        }
        setTimeout(() => setSuccessMsg(""), 5000);
      } else {
        setErrorMsg(data.message || "Gagal menyimpan pengaturan situs.");
      }
    } catch (err: any) {
      setErrorMsg("Terjadi kesalahan jaringan saat menyimpan data.");
    } finally {
      setSaving(false);
    }
  };

  if (isLoading) {
    return (
      <div className="bg-white rounded-2xl border border-slate-200 p-12 text-center flex flex-col items-center justify-center">
        <Loader2 className="animate-spin text-indigo-600 mb-3" size={32} />
        <p className="text-sm font-semibold text-slate-600">Memuat pengaturan situs...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header Banner */}
      <div className="bg-gradient-to-r from-indigo-900 via-blue-900 to-indigo-800 rounded-2xl p-6 text-white shadow-md flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <div className="size-14 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white shrink-0">
            <Globe size={28} />
          </div>
          <div>
            <h2 className="text-lg font-extrabold tracking-wide uppercase">Kelola Identitas Situs & Branding</h2>
            <p className="text-xs font-medium text-blue-200 mt-1">
              Atur logo, nama instansi, teks portal login, dan format kata secara dinamis
            </p>
          </div>
        </div>
      </div>

      {/* Flash Messages */}
      {successMsg && (
        <div className="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 text-sm font-semibold flex items-center gap-3 animate-fade-in shadow-xs">
          <CheckCircle size={18} className="text-emerald-600 shrink-0" />
          <span>{successMsg}</span>
        </div>
      )}

      {errorMsg && (
        <div className="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-4 text-sm font-semibold flex items-center gap-3 animate-fade-in shadow-xs">
          <AlertCircle size={18} className="text-rose-500 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* SECTION 1: Identitas Aplikasi & Logo */}
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-5">
            <div className="flex items-center gap-3 pb-4 border-b border-slate-100">
              <div className="size-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                <Layout size={18} />
              </div>
              <div>
                <h3 className="text-sm font-extrabold text-slate-800">Identitas Aplikasi & Logo</h3>
                <p className="text-xs text-slate-500">Nama aplikasi dan logo yang tampil di Navbar & Sidebar</p>
              </div>
            </div>

            {/* Nama Aplikasi */}
            <div>
              <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                Nama Aplikasi <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                required
                value={appName}
                onChange={(e) => setAppName(e.target.value)}
                placeholder="Contoh: SIPERBANG"
                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              />
            </div>

            {/* Subtitle Aplikasi */}
            <div>
              <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                Deskripsi Singkat / Subtitle
              </label>
              <input
                type="text"
                value={appSubtitle}
                onChange={(e) => setAppSubtitle(e.target.value)}
                placeholder="Contoh: Sistem Informasi Persediaan Barang"
                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              />
            </div>

            {/* Unggah Logo Aplikasi */}
            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3">
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Logo Utama Aplikasi (SIPERBANG)
              </label>
              <div className="flex items-center gap-4">
                <div className="size-16 rounded-xl bg-white border border-slate-200 p-2 flex items-center justify-center shrink-0 shadow-xs">
                  <img src={appLogoPreview} alt="Preview Logo App" className="max-h-full max-w-full object-contain" />
                </div>
                <div className="flex-1 min-w-0">
                  <input
                    type="file"
                    id="appLogoInput"
                    accept="image/*"
                    onChange={handleAppLogoChange}
                    className="hidden"
                  />
                  <label
                    htmlFor="appLogoInput"
                    className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:border-indigo-300 rounded-lg text-xs font-bold text-slate-700 cursor-pointer shadow-xs transition-colors"
                  >
                    <Upload size={14} className="text-indigo-600" />
                    Pilih File Logo Baru
                  </label>
                  <p className="text-[10px] text-slate-500 font-medium mt-1.5">
                    Format: PNG, JPG, SVG (Maks. 2MB). Rekomendasi latar belakang transparan.
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* SECTION 2: Identitas Instansi / Kementerian */}
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-5">
            <div className="flex items-center gap-3 pb-4 border-b border-slate-100">
              <div className="size-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                <Building2 size={18} />
              </div>
              <div>
                <h3 className="text-sm font-extrabold text-slate-800">Identitas Instansi / Kementerian</h3>
                <p className="text-xs text-slate-500">Informasi dan logo lembaga pendukung</p>
              </div>
            </div>

            {/* Nama Instansi Short */}
            <div>
              <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                Singkatan Instansi <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                required
                value={instansiName}
                onChange={(e) => setInstansiName(e.target.value)}
                placeholder="Contoh: KOMDIGI"
                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              />
            </div>

            {/* Nama Lengkap Instansi */}
            <div>
              <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                Nama Lengkap Instansi
              </label>
              <input
                type="text"
                value={instansiSub}
                onChange={(e) => setInstansiSub(e.target.value)}
                placeholder="Contoh: Kementerian Komunikasi dan Digital Republik Indonesia"
                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              />
            </div>

            {/* Unggah Logo Instansi */}
            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3">
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Logo Instansi (KOMDIGI)
              </label>
              <div className="flex items-center gap-4">
                <div className="size-16 rounded-xl bg-white border border-slate-200 p-2 flex items-center justify-center shrink-0 shadow-xs">
                  <img src={instansiLogoPreview} alt="Preview Logo Instansi" className="max-h-full max-w-full object-contain" />
                </div>
                <div className="flex-1 min-w-0">
                  <input
                    type="file"
                    id="instansiLogoInput"
                    accept="image/*"
                    onChange={handleInstansiLogoChange}
                    className="hidden"
                  />
                  <label
                    htmlFor="instansiLogoInput"
                    className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:border-blue-300 rounded-lg text-xs font-bold text-slate-700 cursor-pointer shadow-xs transition-colors"
                  >
                    <Upload size={14} className="text-blue-600" />
                    Pilih File Logo Instansi
                  </label>
                  <p className="text-[10px] text-slate-500 font-medium mt-1.5">
                    Format: PNG, JPG, SVG (Maks. 2MB).
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* SECTION 3: Teks Portal Login & Format Per-Kata */}
        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-6">
          <div className="flex items-center gap-3 pb-4 border-b border-slate-100">
            <div className="size-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm">
              <LogIn size={18} />
            </div>
            <div>
              <h3 className="text-sm font-extrabold text-slate-800">Teks Portal Login & Format Per-Kata</h3>
              <p className="text-xs text-slate-500">
                Blok kata tertentu atau pilih gaya sebelum mengetik. Seleksi kata akan tetap terjaga saat tombol diatur.
              </p>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Judul Utama Login */}
            <div className="space-y-3">
              <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                <span>Judul Utama Landing Page Login</span>
                <span className="text-[10px] text-indigo-600 font-semibold normal-case">
                  *Blok kata atau atur gaya sebelum mengetik
                </span>
              </label>

              <RichTextEditor
                value={loginHeading}
                onChange={setLoginHeading}
                placeholder="Selamat Datang di Portal SIPERBANG."
                sizeOptions={["24px", "28px", "32px", "36px", "40px", "44px", "48px", "56px"]}
                defaultColor="#0055A5"
                minHeight="100px"
              />

              {/* Preview Box */}
              <div className="p-4 bg-gradient-to-br from-slate-50 to-indigo-50/30 rounded-xl border border-slate-200">
                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                  <Type size={12} /> Live Preview Halaman Login:
                </div>
                <div
                  className="text-slate-900 text-lg font-bold leading-snug break-words"
                  dangerouslySetInnerHTML={{ __html: sanitizeHtml(loginHeading) }}
                />
              </div>
            </div>

            {/* Copyright Footer */}
            <div className="space-y-3">
              <div>
                <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                  Teks Hak Cipta (Footer)
                </label>
                <input
                  type="text"
                  value={footerCopyright}
                  onChange={(e) => setFooterCopyright(e.target.value)}
                  placeholder="Contoh: © 2026 BBPSDM Komunikasi dan Digital Makassar..."
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                />
              </div>
            </div>
          </div>

          {/* Deskripsi Sub-Judul Login */}
          <div className="space-y-3">
            <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
              <span>Deskripsi Sub-Judul Login</span>
              <span className="text-[10px] text-indigo-600 font-semibold normal-case">
                *Blok kata atau atur gaya sebelum mengetik
              </span>
            </label>

            <RichTextEditor
              value={loginDescription}
              onChange={setLoginDescription}
              placeholder="Pusat pengelolaan persediaan barang secara digital..."
              sizeOptions={["12px", "13px", "14px", "15px", "16px", "17px", "18px", "20px"]}
              defaultColor="#334155"
              minHeight="110px"
            />

            {/* Preview Box */}
            <div className="p-4 bg-gradient-to-br from-slate-50 to-indigo-50/30 rounded-xl border border-slate-200">
              <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                <Type size={12} /> Live Preview Sub-Judul:
              </div>
              <div
                className="text-slate-600 text-sm font-medium leading-relaxed break-words"
                dangerouslySetInnerHTML={{ __html: sanitizeHtml(loginDescription) }}
              />
            </div>
          </div>
        </div>

        {/* Tombol Simpan */}
        <div className="flex justify-end pt-2">
          <button
            type="submit"
            disabled={saving}
            className="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 disabled:opacity-50"
          >
            {saving ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />}
            {saving ? "Menyimpan Pengaturan..." : "Simpan Perubahan Identitas"}
          </button>
        </div>
      </form>
    </div>
  );
};