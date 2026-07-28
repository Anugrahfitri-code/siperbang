import React, {
  useMemo,
  useRef,
  useState,
  type DragEvent,
  type FormEvent,
} from "react";
import {
  AlertCircle,
  ArrowRight,
  BookOpen,
  Check,
  CheckCircle2,
  CloudUpload,
  Database,
  Download,
  FileCheck2,
  FileSpreadsheet,
  History,
  Package,
  Search,
  ShieldCheck,
  X,
} from "lucide-react";
import { UploadHistoryReact } from "./UploadHistoryReact";
import { StepperReact } from "./StepperReact";

interface StockManagementProps {
  stockList: StockItem[];
  onUploadStock?: (newStock: StockItem[]) => void;
}

const MAX_FILE_SIZE = 10 * 1024 * 1024;
const ALLOWED_EXTENSIONS = ["xlsx", "xls"];

const getCsrfToken = (): string => {
  if (typeof document === "undefined") {
    return "";
  }

  return (
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
      ?.content ?? ""
  );
};

const formatFileSize = (bytes: number): string => {
  if (bytes < 1024 * 1024) {
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
  }

  return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
};

const formatDate = (value: string): string => {
  if (!value) {
    return "-";
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(parsed);
};

export const StockManagement: React.FC<StockManagementProps> = ({
  stockList,
}) => {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [isDragging, setIsDragging] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [fileError, setFileError] = useState<string | null>(null);
  const [fileErrorDetails, setFileErrorDetails] = useState<any[]>([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [activeTab, setActiveTab] = useState<"current" | "verify">("current");
  const [activeNav, setActiveNav] = useState<"upload" | "riwayat" | "verify">(() => {
    if (typeof window !== "undefined") {
      const params = new URLSearchParams(window.location.search);
      if (params.get("tab") === "riwayat") {
        return "riwayat";
      }
    }
    return "upload";
  });
  const [activeBatchId, setActiveBatchId] = useState<number | null>(null);

  const csrfToken = getCsrfToken();

  const categories = useMemo(
    () =>
      Array.from(
        new Set(
          stockList
            .map((item) => item.category?.trim())
            .filter((value): value is string => Boolean(value))
        )
      ).sort((a, b) => a.localeCompare(b, "id-ID")),
    [stockList]
  );

  const filteredStock = useMemo(() => {
    const normalizedQuery = searchTerm.trim().toLowerCase();

    return stockList.filter((item) => {
      const categoryMatches =
        selectedCategory === "all" || item.category === selectedCategory;
      const queryMatches =
        normalizedQuery === "" ||
        item.code.toLowerCase().includes(normalizedQuery) ||
        item.name.toLowerCase().includes(normalizedQuery) ||
        item.category.toLowerCase().includes(normalizedQuery);

      return categoryMatches && queryMatches;
    });
  }, [searchTerm, selectedCategory, stockList]);

  const validateFile = (file: File): string | null => {
    const extension = file.name.split(".").pop()?.toLowerCase() ?? "";

    if (!ALLOWED_EXTENSIONS.includes(extension)) {
      return "Format file tidak didukung. Gunakan file .xlsx atau .xls.";
    }

    if (file.size > MAX_FILE_SIZE) {
      return "Ukuran file melebihi batas 10 MB.";
    }

    if (file.size === 0) {
      return "File kosong dan tidak dapat diproses.";
    }

    return null;
  };

  const applyFile = (file: File | null) => {
    if (!file) {
      setSelectedFile(null);
      setFileError(null);
      setFileErrorDetails([]);
      return;
    }

    const validationError = validateFile(file);
    if (validationError) {
      setSelectedFile(null);
      setFileError(validationError);
      setFileErrorDetails([]);
      if (fileInputRef.current) {
        fileInputRef.current.value = "";
      }
      return;
    }

    setSelectedFile(file);
    setFileError(null);
    setFileErrorDetails([]);
  };

  const handleInputChange = (
    event: React.ChangeEvent<HTMLInputElement>
  ) => {
    applyFile(event.target.files?.[0] ?? null);
  };

  const handleDragOver = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = "copy";
    setIsDragging(true);
  };

  const handleDragLeave = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    if (!event.currentTarget.contains(event.relatedTarget as Node | null)) {
      setIsDragging(false);
    }
  };

  const handleDrop = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    setIsDragging(false);

    const file = event.dataTransfer.files?.[0] ?? null;
    if (!file) {
      return;
    }

    const validationError = validateFile(file);
    if (validationError) {
      applyFile(file);
      return;
    }

    if (fileInputRef.current) {
      const transfer = new DataTransfer();
      transfer.items.add(file);
      fileInputRef.current.files = transfer.files;
    }

    applyFile(file);
  };

  const clearSelectedFile = () => {
    if (fileInputRef.current) {
      fileInputRef.current.value = "";
    }
    setSelectedFile(null);
    setFileError(null);
    setFileErrorDetails([]);
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!selectedFile) {
      setFileError("Pilih file Excel terlebih dahulu.");
      return;
    }

    if (!csrfToken) {
      setFileError(
        "Token keamanan tidak ditemukan. Muat ulang halaman lalu coba kembali."
      );
      return;
    }

    setIsSubmitting(true);
    setFileError(null);
    setFileErrorDetails([]);

    const formData = new FormData();
    formData.append("file_excel", selectedFile);
    formData.append("_token", csrfToken);

    try {
      const response = await fetch("/stok-upload", {
        method: "POST",
        body: formData,
        headers: {
          "Accept": "application/json",
        },
      });

      const data = await response.json().catch(() => ({}));

      if (response.ok && data.success) {
        setActiveBatchId(data.batch_id);
        setActiveNav("verify");
        clearSelectedFile();
      } else {
        setFileError(data.error || "Gagal mengunggah file. Pastikan format benar.");
        if (data.details && Array.isArray(data.details)) {
          setFileErrorDetails(data.details);
        }
      }
    } catch (error) {
      setFileError("Terjadi kesalahan jaringan saat mengunggah file.");
      setFileErrorDetails([]);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="relative bg-gradient-to-r from-[#f8faff] to-[#f0f4ff] rounded-2xl border border-indigo-50/50 p-6 shadow-sm overflow-hidden flex flex-col xl:flex-row xl:items-center justify-between gap-5">
        {/* Glow effects */}
        <div className="absolute right-0 top-0 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
        <div className="absolute left-0 bottom-0 w-48 h-48 bg-blue-500/5 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>

        <div className="flex items-center gap-4 relative z-10">
          <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm border border-slate-100 text-amber-500">
            <Database size={26} strokeWidth={2.5} />
          </div>
          <div>
            <h2 className="text-base font-extrabold text-slate-800 uppercase tracking-wide">
              MANAJEMEN STOK & KODE PERSEDIAAN
            </h2>
            <p className="text-xs font-medium text-slate-500 mt-1">
              Unggah file Excel stok dan persediaan, verifikasi kode barang, dan kelola data inventori dengan mudah
            </p>
          </div>
        </div>

        {/* View Tabs */}
        <div className="flex bg-white border border-slate-200 rounded-md overflow-hidden shadow-xs self-start md:self-auto relative z-10">
          <button 
            onClick={() => setActiveTab("current")}
            className={`px-6 py-2.5 text-xs font-bold transition-all ${
              activeTab === "current" 
                ? "bg-white text-blue-600 shadow-sm ring-1 ring-inset ring-slate-200" 
                : "text-slate-500 hover:text-slate-700 hover:bg-slate-50/50"
            }`}
          >
            Stok Aktif ({stockList.length})
          </button>
        </div>
      </div>

      <div className="border border-slate-200 rounded-xl bg-white px-3 sm:px-5 shadow-sm">
        <div className="flex bg-slate-100/50 p-1.5 rounded-xl border border-slate-200 shadow-inner w-full md:w-auto relative z-10 overflow-x-auto">
          <button
            onClick={() => setActiveNav("upload")}
            className={`flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold transition-all duration-300 ${
              activeNav === "upload"
                ? "bg-white text-indigo-700 shadow-sm border border-slate-200/50"
                : "text-slate-500 hover:text-slate-700 hover:bg-slate-200/50"
            }`}
          >
            <CloudUpload size={18} className={activeNav === "upload" ? "text-indigo-500" : "text-slate-400"} />
            <span className="whitespace-nowrap">Upload Excel</span>
          </button>
          
          <button
            onClick={() => setActiveNav("verify")}
            className={`flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold transition-all duration-300 ${
              activeNav === "verify"
                ? "bg-white text-emerald-700 shadow-sm border border-slate-200/50"
                : "text-slate-500 hover:text-slate-700 hover:bg-slate-200/50"
            }`}
          >
            <ShieldCheck size={18} className={activeNav === "verify" ? "text-emerald-500" : "text-slate-400"} />
            <span className="whitespace-nowrap">Proses Verifikasi</span>
          </button>

          <button
            onClick={() => {
              setActiveNav("riwayat");
              setActiveBatchId(null);
            }}
            className={`flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold transition-all duration-300 ${
              activeNav === "riwayat"
                ? "bg-white text-blue-700 shadow-sm border border-slate-200/50"
                : "text-slate-500 hover:text-slate-700 hover:bg-slate-200/50"
            }`}
          >
            <History size={18} className={activeNav === "riwayat" ? "text-blue-500" : "text-slate-400"} />
            <span className="whitespace-nowrap">Riwayat Upload</span>
          </button>

          <button
            onClick={() => {
              window.location.href = "/?module=master";
            }}
            className="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-slate-500 hover:text-slate-700 hover:bg-slate-200/50 transition-all duration-300"
          >
            <Package size={18} className="text-slate-400" />
            <span className="whitespace-nowrap">Master Barang</span>
          </button>
        </div>
      </div>

      {activeNav === "riwayat" && <UploadHistoryReact onOpenStepper={(id) => { setActiveBatchId(id); setActiveNav("verify"); }} />}
      {activeNav === "verify" && <StepperReact batchId={activeBatchId} onClose={() => { setActiveNav("riwayat"); setActiveBatchId(null); }} />}

      {activeNav === "upload" && (
        <>
          <div className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(300px,0.75fr)]">
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <div className="mb-5 flex items-start justify-between gap-4">
            <div>
              <div className="flex items-center gap-2 text-sm font-extrabold text-slate-900">
                <CloudUpload size={18} className="text-blue-600" />
                Upload Stok & Persediaan Excel
              </div>
              <p className="mt-1 text-xs leading-5 text-slate-500">
                Sistem membaca seluruh sheet dan memvalidasi setiap baris sebelum
                data masuk ke tahap verifikasi kode.
              </p>
            </div>
            <span className="hidden rounded-lg border border-blue-100 bg-blue-50 px-2.5 py-1 text-2xs font-bold text-blue-700 sm:inline-flex">
              Maks. 10 MB
            </span>
          </div>

          <form
            action="/stok-upload"
            method="POST"
            encType="multipart/form-data"
            onSubmit={handleSubmit}
            className="space-y-4"
          >
            <input type="hidden" name="_token" value={csrfToken} />
            <input
              ref={fileInputRef}
              type="file"
              name="file_excel"
              accept=".xlsx,.xls,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
              onChange={handleInputChange}
              className="sr-only"
              required
            />

            <div
              onDragOver={handleDragOver}
              onDragLeave={handleDragLeave}
              onDrop={handleDrop}
              className={`relative rounded-2xl border-2 border-dashed px-5 py-10 text-center transition-all sm:py-12 ${
                isDragging
                  ? "border-blue-500 bg-blue-50 ring-4 ring-blue-100/70"
                  : selectedFile
                    ? "border-emerald-300 bg-emerald-50/50"
                    : "border-slate-300 bg-slate-50/60 hover:border-blue-400 hover:bg-blue-50/40"
              }`}
            >
              {selectedFile ? (
                <div className="mx-auto max-w-xl">
                  <div className="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl border border-emerald-200 bg-white text-emerald-600 shadow-sm">
                    <FileCheck2 size={27} />
                  </div>
                  <p className="text-sm font-extrabold text-slate-900">
                    File siap diproses
                  </p>
                  <div className="mx-auto mt-3 flex max-w-lg items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-left shadow-sm">
                    <div className="min-w-0">
                      <p className="truncate text-xs font-extrabold text-slate-800">
                        {selectedFile.name}
                      </p>
                      <p className="mt-0.5 text-2xs font-semibold text-slate-400">
                        {formatFileSize(selectedFile.size)} · Excel
                      </p>
                    </div>
                    <button
                      type="button"
                      onClick={clearSelectedFile}
                      className="flex size-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                      aria-label="Hapus file terpilih"
                    >
                      <X size={17} />
                    </button>
                  </div>
                  <button
                    type="button"
                    onClick={() => fileInputRef.current?.click()}
                    className="mt-4 text-xs font-bold text-blue-700 hover:text-blue-800"
                  >
                    Ganti file
                  </button>
                </div>
              ) : (
                <div className="mx-auto max-w-xl">
                  <div className="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl border border-blue-100 bg-white text-blue-600 shadow-sm">
                    <CloudUpload size={27} />
                  </div>
                  <p className="text-sm font-extrabold text-slate-800">
                    Seret dan lepas file Excel di area ini
                  </p>
                  <p className="mt-1 text-xs leading-5 text-slate-500">
                    atau pilih file dari perangkat. Format yang diterima .xlsx dan
                    .xls.
                  </p>
                  <button
                    type="button"
                    onClick={() => fileInputRef.current?.click()}
                    className="mt-5 inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-xs font-extrabold text-blue-700 shadow-sm transition-colors hover:bg-blue-50"
                  >
                    <FileSpreadsheet size={15} />
                    Pilih File Excel
                  </button>
                </div>
              )}
            </div>

            {fileError && (
              <div className="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700">
                <AlertCircle size={16} className="mt-0.5 shrink-0" />
                <div className="flex-1 min-w-0">
                  <span className="block text-sm">{fileError}</span>
                  {fileErrorDetails.length > 0 && (
                    <div className="mt-2.5 space-y-2 border-t border-rose-200/60 pt-2.5 text-rose-600 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                      {fileErrorDetails.slice(0, 10).map((detail, idx) => (
                        <div key={idx} className="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-2 bg-rose-100/50 p-2 rounded-lg">
                          <span className="font-bold shrink-0 whitespace-nowrap bg-rose-200/50 text-rose-800 px-1.5 py-0.5 rounded text-2xs border border-rose-200">
                            Sheet "{detail.sheet}" {detail.no_urut ? `- Baris ${detail.no_urut}` : ''}
                          </span>
                          <span className="font-normal leading-relaxed text-xs break-words">{detail.messages.join(", ")}</span>
                        </div>
                      ))}
                      {fileErrorDetails.length > 10 && (
                        <div className="italic text-rose-500 mt-2 pt-2 border-t border-rose-200/40 text-2xs text-center font-bold">
                          ... dan {fileErrorDetails.length - 10} kesalahan lainnya. Silakan perbaiki file terlebih dahulu.
                        </div>
                      )}
                    </div>
                  )}
                </div>
              </div>
            )}

            <div className="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
              <a
                href="/stok-upload/template"
                className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-xs font-extrabold text-slate-600 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
              >
                <Download size={15} />
                Download Template Excel
              </a>
              <button
                type="submit"
                disabled={!selectedFile || isSubmitting}
                className="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none"
              >
                {isSubmitting ? (
                  <>
                    <span className="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                    Memproses File...
                  </>
                ) : (
                  <>
                    Mulai Proses Upload
                    <ArrowRight size={15} />
                  </>
                )}
              </button>
            </div>
          </form>
        </section>

        <aside className="space-y-4">
          <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4 flex items-center gap-2">
              <ShieldCheck size={18} className="text-indigo-600" />
              <h3 className="text-sm font-extrabold text-slate-900">
                Alur Pemrosesan
              </h3>
            </div>
            <div className="space-y-3">
              {[
                ["1", "Upload file", "Pilih Excel sesuai template."],
                ["2", "Pemeriksaan data", "Sistem memvalidasi semua baris."],
                ["3", "Verifikasi kode", "Petugas memeriksa kode persediaan."],
                ["4", "Finalisasi", "Stok disimpan ke master barang."],
              ].map(([number, title, description], index) => (
                <div key={number} className="flex gap-3">
                  <div className="flex flex-col items-center">
                    <span className="flex size-7 items-center justify-center rounded-full bg-blue-600 text-2xs font-extrabold text-white">
                      {number}
                    </span>
                    {index < 3 && (
                      <span className="mt-1 h-full min-h-5 w-px bg-slate-200" />
                    )}
                  </div>
                  <div className="pb-2">
                    <p className="text-xs font-extrabold text-slate-800">
                      {title}
                    </p>
                    <p className="mt-0.5 text-xs leading-5 text-slate-500">
                      {description}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </section>

          <section className="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <div className="flex items-start gap-3">
              <AlertCircle size={18} className="mt-0.5 shrink-0 text-amber-600" />
              <div>
                <h3 className="text-xs font-extrabold text-amber-900">
                  Validasi bersifat menyeluruh
                </h3>
                <p className="mt-1 text-xs leading-5 text-amber-800">
                  Satu baris yang tidak valid akan menolak seluruh file. Sistem
                  menampilkan sheet dan baris yang perlu diperbaiki.
                </p>
              </div>
            </div>
          </section>
        </aside>
      </div>

      <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div className="mb-5 flex items-center gap-3 border-b border-slate-100 pb-4">
          <div className="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
            <BookOpen size={18} />
          </div>
          <div>
            <h3 className="text-sm font-extrabold uppercase tracking-wide text-slate-900">
              Petunjuk Format Dokumen Excel
            </h3>
            <p className="mt-0.5 text-xs text-slate-500">
              Gunakan struktur berikut agar sistem membaca file secara konsisten.
            </p>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
            <div className="mb-3 flex items-center gap-2">
              <span className="flex size-6 items-center justify-center rounded-lg bg-blue-600 text-2xs font-extrabold text-white">
                1
              </span>
              <h4 className="text-xs font-extrabold text-slate-800">
                Struktur dokumen
              </h4>
            </div>
            <ul className="space-y-2 text-xs leading-5 text-slate-600">
              <li className="flex gap-2"><Check size={14} className="mt-0.5 shrink-0 text-emerald-600" /> Sistem membaca seluruh sheet.</li>
              <li className="flex gap-2"><Check size={14} className="mt-0.5 shrink-0 text-emerald-600" /> Setiap sheet mewakili satu nota atau transaksi.</li>
              <li className="flex gap-2"><Check size={14} className="mt-0.5 shrink-0 text-emerald-600" /> Supplier dapat ditempatkan pada baris 1 sampai 5.</li>
              <li className="flex gap-2"><Check size={14} className="mt-0.5 shrink-0 text-emerald-600" /> Header harus memuat Kode dan Nama Barang.</li>
            </ul>
          </div>

          <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
            <div className="mb-3 flex items-center gap-2">
              <span className="flex size-6 items-center justify-center rounded-lg bg-indigo-600 text-2xs font-extrabold text-white">
                2
              </span>
              <h4 className="text-xs font-extrabold text-slate-800">
                Kolom yang didukung
              </h4>
            </div>
            <ul className="space-y-2 text-xs leading-5 text-slate-600">
              <li className="flex gap-2"><Check size={14} className="mt-0.5 shrink-0 text-emerald-600" /> Tanpa pajak: No, Kode, Nama, Jumlah, Satuan, Harga, Total.</li>
              <li className="flex gap-2"><Check size={14} className="mt-0.5 shrink-0 text-emerald-600" /> Dengan pajak: tambahkan Harga + Pajak, Total, dan Pajak.</li>
              <li className="flex gap-2"><Check size={14} className="mt-0.5 shrink-0 text-emerald-600" /> Kolom lokasi atau rak dapat ditambahkan bila tersedia.</li>
              <li className="flex gap-2"><Check size={14} className="mt-0.5 shrink-0 text-emerald-600" /> Baris subtotal dan total dilewati otomatis.</li>
            </ul>
          </div>
        </div>
      </section>

        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div className="flex flex-col gap-4 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
              <div className="flex size-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <FileSpreadsheet size={18} />
              </div>
              <div>
                <h3 className="text-sm font-extrabold text-slate-900">
                  Daftar Barang Stok Aktif
                </h3>
                <p className="mt-0.5 text-xs text-slate-500">
                  Menampilkan {filteredStock.length} dari {stockList.length} barang.
                </p>
              </div>
            </div>

            <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
              <div className="relative min-w-0 sm:w-72">
                <Search
                  size={15}
                  className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                />
                <input
                  type="search"
                  value={searchTerm}
                  onChange={(event) => setSearchTerm(event.target.value)}
                  placeholder="Cari kode atau nama barang..."
                  className="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-xs font-semibold text-slate-700 outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                />
              </div>
              <select
                value={selectedCategory}
                onChange={(event) => setSelectedCategory(event.target.value)}
                className="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs font-bold text-slate-600 outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
              >
                <option value="all">Semua kategori</option>
                {categories.map((category) => (
                  <option key={category} value={category}>
                    {category}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full min-w-[900px] border-collapse text-left">
              <thead>
                <tr className="border-b border-slate-200 bg-slate-50 text-xs font-extrabold uppercase tracking-wider text-slate-500">
                  <th className="px-5 py-3">Kode Persediaan</th>
                  <th className="px-5 py-3">Nama Barang</th>
                  <th className="px-5 py-3">Kategori</th>
                  <th className="px-5 py-3 text-right">Stok</th>
                  <th className="px-5 py-3">Satuan</th>
                  <th className="px-5 py-3">Diperbarui</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredStock.length > 0 ? (
                  filteredStock.map((item) => (
                    <tr
                      key={item.id}
                      className="text-xs transition-colors hover:bg-blue-50/30"
                    >
                      <td className="px-5 py-3.5 font-mono font-bold text-indigo-700">
                        {item.code}
                      </td>
                      <td className="px-5 py-3.5 font-bold text-slate-800">
                        {item.name}
                      </td>
                      <td className="px-5 py-3.5 text-slate-500">
                        {item.category}
                      </td>
                      <td className="px-5 py-3.5 text-right font-extrabold text-slate-800">
                        {Number(item.qty).toLocaleString("id-ID")}
                      </td>
                      <td className="px-5 py-3.5 font-semibold text-slate-500">
                        {item.unit}
                      </td>
                      <td className="px-5 py-3.5 text-slate-400">
                        {formatDate(item.lastUpdated)}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={6} className="px-5 py-14 text-center">
                      <CheckCircle2
                        size={38}
                        strokeWidth={1.4}
                        className="mx-auto mb-3 text-slate-300"
                      />
                      <p className="text-sm font-extrabold text-slate-700">
                        Data tidak ditemukan
                      </p>
                      <p className="mt-1 text-xs text-slate-400">
                        Ubah kata kunci atau filter kategori.
                      </p>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </section>
        </>
      )}
    </div>
  );
};
