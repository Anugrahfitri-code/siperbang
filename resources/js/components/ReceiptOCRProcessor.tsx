import React, { useState, useRef, useEffect } from "react";
import { ReceiptData, ReceiptItem, ProcurementMethod, RequestStatus, ItemRequest, ParsedReceiptResult, OcrWarning, OcrField, ReceiptDocument, ReceiptManualDraft } from "../types";
import { apiFetch } from "../api";
import { FileDown, UploadCloud, FileText, CheckCircle, RefreshCw, Plus, Trash2, Edit3, Settings, Calculator, Percent, Sparkles, Receipt, AlertTriangle, ShieldCheck, ShieldAlert, Cpu, Save, FolderOpen, X } from "lucide-react";

interface ReceiptOCRProcessorProps {
  receipts: ReceiptData[];
  requests: ItemRequest[];
  onAddReceipt: (newReceipt: ReceiptData) => void;
  onVerifyReceipt: (id: string, updatedReceipt: ReceiptData, logMsg: string) => void;
  onUnverifyReceipt?: (id: string, logMsg: string) => void;
}

export const ReceiptOCRProcessor: React.FC<ReceiptOCRProcessorProps> = ({
  receipts,
  requests,
  onAddReceipt,
  onVerifyReceipt,
  onUnverifyReceipt,
}) => {
  const [isScanning, setIsScanning] = useState(false);
  const [activeDraft, setActiveDraft] = useState<ReceiptData | null>(null);
  const [activeTab, setActiveTab] = useState<"pending" | "valid">("pending");
  const [selectedImage, setSelectedImage] = useState<string | null>(null);
  const [selectedMimeType, setSelectedMimeType] = useState<string | null>(null);
  const [activeDocumentId, setActiveDocumentId] = useState<number | null>(null);
  const [ocrWarnings, setOcrWarnings] = useState<OcrWarning[]>([]);
  const [ocrStatus, setOcrStatus] = useState<string>("");
  const [rawText, setRawText] = useState<string>("");

  const [
    pendingDocuments,
    setPendingDocuments,
  ] = useState<ReceiptDocument[]>([]);

  const [
    isSavingDraft,
    setIsSavingDraft,
  ] = useState(false);

  const [
    isVerifying,
    setIsVerifying,
  ] = useState(false);
  const pollTimerRef = useRef<number | NodeJS.Timeout | null>(null);

  useEffect(() => {
    return () => {
      if (pollTimerRef.current) clearTimeout(pollTimerRef.current as number);
      if (selectedImage) URL.revokeObjectURL(selectedImage);
    };
  }, [selectedImage]);

  // Local state for the verification form
  const [storeName, setStoreName] = useState("");
  const [invoiceNo, setInvoiceNo] = useState("");
  const [date, setDate] = useState("");
  const [isTaxed, setIsTaxed] = useState(false);
  const [taxRate, setTaxRate] = useState<number>(0);
  const [method, setMethod] = useState<ProcurementMethod>(ProcurementMethod.SENDIRI);
  const [items, setItems] = useState<ReceiptItem[]>([]);
  const [bastName, setBastName] = useState("");
  const [bastDate, setBastDate] = useState("");

    const readApiError = async (
    response: Response
  ): Promise<string> => {
    const payload = await response
      .json()
      .catch(() => ({}));

    if (
      payload?.errors
      && typeof payload.errors
        === "object"
    ) {
      const messages = Object
        .values(payload.errors)
        .flatMap((value) =>
          Array.isArray(value)
            ? value
            : [value]
        )
        .filter(
          (
            value
          ): value is string =>
            typeof value === "string"
        );

      if (messages.length > 0) {
        return messages.join("\n");
      }
    }

    return (
      payload?.message
      || payload?.error
      || (
        `HTTP ${response.status} `
        + response.statusText
      )
    );
  };

  const handleDeleteDraft = async (id: number) => {
    if (!confirm("Yakin ingin menghapus draft kuitansi ini? Aksi ini tidak dapat dibatalkan.")) return;
    
    try {
      const res = await apiFetch(`/api/receipt-documents/${id}`, {
        method: "DELETE",
      });
      if (!res.ok) {
        throw new Error(await readApiError(res));
      }
      alert("Draft kuitansi berhasil dihapus.");
      await loadPendingDocuments();
    } catch (e: any) {
      console.error(e);
      alert("Gagal menghapus draft: " + (e.message || "Kesalahan server"));
    }
  };

  const loadPendingDocuments =
    async () => {
      try {
        const response = await apiFetch(
          "/api/receipt-documents"
          + "?scope=pending"
        );

        if (!response.ok) {
          throw new Error(
            await readApiError(
              response
            )
          );
        }

        const documents =
          await response.json();

        setPendingDocuments(
          Array.isArray(documents)
            ? documents
            : []
        );
      } catch (error) {
        console.error(
          "Gagal memuat draft kuitansi:",
          error
        );
      }
    };

  useEffect(() => {
    void loadPendingDocuments();
  }, []);

  const normalizeWarnings = (warnings: Array<OcrWarning | string>): OcrWarning[] => {
    return warnings.map((w) => {
      if (typeof w === "string") {
        return {
          code: "legacy_warning",
          field: null,
          message: w,
          severity: "warning",
        };
      }
      return w;
    });
  };

  const pollDocumentStatus = async (
    id: number,
    startedAt = Date.now()
  ) => {
    const elapsedMs = Date.now() - startedAt;
    const SOFT_LIMIT_MS = 20_000;
    const HARD_LIMIT_MS = 120_000;

    if (elapsedMs > HARD_LIMIT_MS) {
      setIsScanning(false);
      setOcrStatus("timeout");
      setActiveDocumentId(id);

      alert(
        "Tampilan berhenti menunggu setelah 2 menit. " +
        "Dokumen tetap tersimpan. Periksa status dokumen " +
        "dan terminal queue untuk mengetahui hasil akhirnya."
      );

      return;
    }

    try {
      const res = await apiFetch(`/api/receipt-documents/${id}`);

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }

      const data = await res.json();

      if (
          data.status === "needs_review"
          || data.status === "draft"
          || data.status === "verified"
        ) {
        setIsScanning(false);
        setActiveDocumentId(id);
        const p: ParsedReceiptResult = data.parsed_result || { items: [], warnings: [], pages: [] };

        const normalizedWarnings = normalizeWarnings(
          p.warnings || []
        );

        setRawText(data.raw_text || "");

        const extractValue = (
          field?: OcrField<any>,
          defaultVal: any = ""
        ) => field?.value ?? defaultVal;

        const numericValue = (
          value: unknown,
          fallback = 0
        ): number => {
          const parsed = Number(value);

          return Number.isFinite(parsed)
            ? parsed
            : fallback;
        };

        const extractedTaxAmount = numericValue(
          extractValue(p.tax_amount, 0)
        );

        const extractedTaxRate = numericValue(
          extractValue(p.tax_rate, 0)
        );

        const hasTax =
          extractedTaxAmount > 0;

        const documentSubtotal = numericValue(
          extractValue(p.subtotal, 0)
        );

        const documentTotal = numericValue(
          extractValue(p.total, 0)
        );

        const documentAnchor =
          documentSubtotal > 0
            ? documentSubtotal
            : (
                !hasTax
                  ? documentTotal
                  : 0
              );

        const parsedItems = Array.isArray(
          p.items
        )
          ? p.items
          : [];

        const safeItems = parsedItems.map(
          (
            it: any,
            index: number
          ) => {
            const qty = numericValue(
              extractValue(it.qty, 1),
              1
            );

            const price = numericValue(
              extractValue(it.price, 0)
            );

            const itemSubtotal = numericValue(
              extractValue(it.subtotal, 0)
            );

            const expected =
              qty > 0 && price > 0
                ? qty * price
                : 0;

            const implausible = (
              documentAnchor > 0
              && expected >
                documentAnchor * 5
            );

            if (!implausible) {
              return {
                id: `it-draft-${index}`,
                name: extractValue(it.name),
                qty,
                price,
                subtotal: itemSubtotal,
              };
            }

            const warningAlreadyExists =
              normalizedWarnings.some(
                (warning) =>
                  warning.code ===
                  "frontend_plausibility_guard"
              );

            if (!warningAlreadyExists) {
              normalizedWarnings.push({
                code:
                  "frontend_plausibility_guard",

                field:
                  `items.${index}`,

                message:
                  "Nilai item yang tidak masuk akal ditahan agar tidak menghasilkan subtotal miliaran.",

                severity:
                  "error",
              });
            }

            /*
             * Untuk satu item tanpa pajak,
             * gunakan total sebagai anchor.
             */
            if (
              parsedItems.length === 1
              && documentAnchor > 0
            ) {
              return {
                id: `it-draft-${index}`,
                name: extractValue(it.name),
                qty: 1,
                price: documentAnchor,
                subtotal: documentAnchor,
              };
            }

            /*
             * Untuk banyak item, jangan menebak.
             * Kosongkan nominal dan minta verifikasi.
             */
            return {
              id: `it-draft-${index}`,
              name: extractValue(it.name),
              qty: 1,
              price: 0,
              subtotal: 0,
            };
          }
        );

        setOcrWarnings(
          normalizedWarnings
        );

        const m = data.manual_draft;
        
        let finalItems = safeItems;
        if (m && Array.isArray(m.items) && m.items.length > 0) {
          finalItems = m.items.map((item: any, index: number) => ({
            id: `it-draft-manual-${index}`,
            name: item.name || "",
            qty: Number(item.qty) || 1,
            price: Number(item.price) || 0,
            subtotal: (Number(item.qty) || 1) * (Number(item.price) || 0),
          }));
        }

        const newDraft: ReceiptData = {
          id: `rc-draft-${data.id}`,

          invoiceNo:
            m?.invoiceNo ?? extractValue(p.invoice_no),

          storeName:
            m?.storeName ?? extractValue(p.store_name),

          date:
            m?.date ?? extractValue(p.date),

          isTaxed:
            m?.isTaxed ?? hasTax,

          taxRate:
            m?.taxRate ?? (hasTax ? extractedTaxRate : 0),

          subtotal:
            m?.subtotal ?? documentSubtotal,

          taxAmount:
            m?.taxAmount ?? extractedTaxAmount,

          total:
            m?.total ?? documentTotal,

          isVerified:
            data.status === "verified",

          status:
            data.status === "verified"
              ? "Dokumen Valid"
              : "Menunggu Verifikasi",

          method:
            (m?.method as ProcurementMethod) ?? ProcurementMethod.SENDIRI,

          items:
            finalItems,

          bastName:
            m?.bastName ?? extractValue(p.store_name),

          bastDate:
            m?.bastDate ?? extractValue(p.date),
        };

        setActiveDraft(newDraft);
        await loadPendingDocuments();
        setStoreName(newDraft.storeName);
        setInvoiceNo(newDraft.invoiceNo);
        setDate(newDraft.date);
        setIsTaxed(newDraft.isTaxed);
        setTaxRate(newDraft.taxRate);
        setItems(newDraft.items);
        setBastName(newDraft.bastName || "");
        setBastDate(newDraft.bastDate || "");

        // Load dokumen asli dari server — selalu di-refresh setiap buka draft
        try {
          // Cabut objectURL lama agar tidak bocor memori
          if (selectedImage) URL.revokeObjectURL(selectedImage);
          setSelectedImage(null);
          setSelectedMimeType(null);

          const fileRes = await apiFetch(`/api/receipt-documents/${id}/file`);
          if (fileRes.ok) {
            const contentType = fileRes.headers.get("Content-Type") || "image/jpeg";
            const blob = await fileRes.blob();
            const objectUrl = URL.createObjectURL(blob);
            setSelectedImage(objectUrl);
            setSelectedMimeType(contentType.startsWith("application/pdf") ? "application/pdf" : "image/jpeg");
          }
        } catch (_e) {
          // Jika gagal memuat preview, workspace tetap bisa dipakai
          setSelectedImage(null);
          setSelectedMimeType(null);
        }
        return;
      }

      if (data.status === "failed") {
        setIsScanning(false);
        setActiveDocumentId(null);
        setOcrStatus("failed");
        alert("Proses OCR gagal: " + (data.error_message || "Kesalahan tidak diketahui"));
        return;
      }

      if (
        elapsedMs > SOFT_LIMIT_MS
        && (
          data.status === "queued"
          || data.status === "processing"
        )
      ) {
        setOcrStatus("processing_slow");
      } else {
        setOcrStatus(data.status);
      }
      pollTimerRef.current = setTimeout(
        () => pollDocumentStatus(id, startedAt),
        1000
      );
    } catch (e) {
      console.error(e);
      setIsScanning(false);
      setOcrStatus("error");
      alert("Terjadi kesalahan saat mengecek status OCR.");
    }
  };

  const handleFileUpload = async (file: File) => {
    if (isScanning) return;
    setIsScanning(true);
    setOcrStatus("uploading");
    setActiveDraft(null);
    setOcrWarnings([]);
    setActiveDocumentId(null);
    
    if (selectedImage) URL.revokeObjectURL(selectedImage);
    const fileUrl = URL.createObjectURL(file);
    setSelectedImage(fileUrl);
    setSelectedMimeType(file.type);

    try {
      const formData = new FormData();
      formData.append("document", file);

      const res = await apiFetch("/api/receipt-documents", {
        method: "POST",
        body: formData,
      });

      if (!res.ok) {
        let errMsg = "Upload failed";
        try {
          const errData = await res.json();
          errMsg = errData.message || JSON.stringify(errData);
        } catch (e) {
          errMsg = `HTTP ${res.status} ${res.statusText}`;
        }
        throw new Error(errMsg);
      }

      const data = await res.json();
      const docId = data.data ? data.data.id : (data.document ? data.document.id : data.id);
      if (!docId) throw new Error("Server response is missing document ID");
      pollDocumentStatus(docId, Date.now());
    } catch (e: any) {
      console.error(e);
      setIsScanning(false);
      setOcrStatus("error");
      alert("Gagal mengunggah dokumen OCR: " + (e.message || "Kesalahan tidak diketahui"));
    }
  };

  const handleAddItem = () => {
    const newItem: ReceiptItem = {
      id: "it-draft-" + Date.now(),
      name: "",
      qty: 1,
      price: 0,
      subtotal: 0,
    };
    setItems([...items, newItem]);
  };

  const handleUpdateItem = (id: string, field: keyof ReceiptItem, val: any) => {
    setItems(
      items.map((it) => {
        if (it.id === id) {
          const updated = { ...it, [field]: val };
          if (field === "qty" || field === "price") {
            updated.subtotal = Number(updated.qty) * Number(updated.price);
          }
          return updated;
        }
        return it;
      })
    );
  };

  const handleRemoveItem = (id: string) => {
    setItems(items.filter((it) => it.id !== id));
  };

  // Live Calculations based on input fields
  const roundMoney = (
    value: number
  ): number => {
    return Math.round(
      (
        value
        + Number.EPSILON
      ) * 100
    ) / 100;
  };

  const calculatedSubtotal = roundMoney(
    items.reduce(
      (
        sum,
        item
      ) => (
        sum
        + (
          item.qty
          * item.price
          || 0
        )
      ),
      0
    )
  );

  const calculatedTaxRate =
    isTaxed ? taxRate : 0;

  const calculatedTaxAmount = roundMoney(
    calculatedSubtotal
    * (
      calculatedTaxRate
      / 100
    )
  );

  const calculatedTotal = roundMoney(
    calculatedSubtotal
    + calculatedTaxAmount
  );

  const buildManualPayload = () => ({
    invoiceNo: invoiceNo.trim(),
    storeName: storeName.trim(),
    date: date || null,
    isTaxed,
    taxRate: isTaxed ? Number(taxRate) : 0,
    items: items.map((item) => ({
      name: item.name.trim(),
      qty: Number(item.qty),
      price: Number(item.price),
    })),
    method,
    bastName: bastName.trim(),
    bastDate: bastDate || null,
  });

  const closeWorkspace = () => {
    setActiveDraft(null);
    setActiveDocumentId(null);
    setRawText("");
    setOcrWarnings([]);
    setSelectedImage(null);
    setSelectedMimeType(null);
  };

  const handleSaveDraft = async () => {
    if (!activeDocumentId || isSavingDraft || isVerifying) return;
    setIsSavingDraft(true);
    try {
      const response = await apiFetch(`/api/receipt-documents/${activeDocumentId}/draft`, {
        method: "PUT",
        body: JSON.stringify(buildManualPayload()),
      });
      if (!response.ok) throw new Error(await readApiError(response));
      await loadPendingDocuments();
      closeWorkspace();
      alert("Draft verifikasi berhasil disimpan. Dokumen dapat dibuka kembali dari daftar Menunggu Verifikasi.");
    } catch (error: any) {
      console.error(error);
      alert("Gagal menyimpan draft:\n" + (error?.message || "Kesalahan tidak diketahui"));
    } finally {
      setIsSavingDraft(false);
    }
  };

  const handleVerifySave = async () => {
    if (!activeDraft || !activeDocumentId || isSavingDraft || isVerifying) return;

    if (!storeName.trim()) {
      alert("Nama toko/penyedia wajib diisi sebelum verifikasi.");
      return;
    }
    if (!date) {
      alert("Tanggal kuitansi wajib diisi sebelum verifikasi.");
      return;
    }

    const invalidItemIndex = items.findIndex(
      (item) =>
        !item.name.trim() ||
        !Number.isInteger(Number(item.qty)) ||
        Number(item.qty) < 1 ||
        !Number.isFinite(Number(item.price)) ||
        Number(item.price) <= 0
    );

    if (items.length === 0 || invalidItemIndex >= 0) {
      alert(
        invalidItemIndex >= 0
          ? `Periksa barang ke-${invalidItemIndex + 1}. Nama, jumlah, dan harga wajib valid.`
          : "Minimal satu barang wajib diisi."
      );
      return;
    }

    setIsVerifying(true);
    try {
      const response = await apiFetch(`/api/receipt-documents/${activeDocumentId}/verify`, {
        method: "PUT",
        body: JSON.stringify(buildManualPayload()),
      });

      if (!response.ok) throw new Error(await readApiError(response));

      const responsePayload = await response.json();
      const receipt = responsePayload?.data?.receipt;

      if (!receipt) {
        throw new Error("Server tidak mengembalikan data kuitansi yang sudah diverifikasi.");
      }

      const serverItems = Array.isArray(receipt.items)
        ? receipt.items.map((item: any) => ({
            id: String(item.id),
            name: String(item.name),
            qty: Number(item.qty),
            price: Number(item.price),
            subtotal: Number(item.subtotal),
          }))
        : items;

      const finalReceipt: ReceiptData = {
        id: String(receipt.id),
        storeName: String(receipt.store_name),
        invoiceNo: String(receipt.invoice_no),
        date: String(receipt.date).slice(0, 10),
        isTaxed: Boolean(receipt.is_taxed),
        taxRate: Number(receipt.tax_rate),
        subtotal: Number(receipt.subtotal),
        taxAmount: Number(receipt.tax_amount),
        total: Number(receipt.total),
        isVerified: true,
        status: "Dokumen Valid",
        items: serverItems,
        method: (receipt.method as ProcurementMethod) || method,
        bastName: receipt.bast_name || bastName,
        bastDate: receipt.bast_date ? String(receipt.bast_date).slice(0, 10) : bastDate,
      };

      const displayInvoice = finalReceipt.invoiceNo || "tanpa nomor";
      const logMsg = `Verifikasi Kuitansi: Petugas memverifikasi kuitansi nomor ${displayInvoice} dari ${finalReceipt.storeName}. Total belanja ${formatIDR(finalReceipt.total)}.`;

      onVerifyReceipt(activeDraft.id, finalReceipt, logMsg);
      setPendingDocuments((prev) => prev.filter((d) => d.id !== activeDocumentId));
      closeWorkspace();
      alert(responsePayload.message || "Dokumen berhasil diverifikasi.");
    } catch (error: any) {
      console.error(error);
      alert("Gagal menyimpan verifikasi:\n" + (error?.message || "Kesalahan tidak diketahui"));
    } finally {
      setIsVerifying(false);
    }
  };

  const handleUnverify = async (id: string, invoiceNo: string, storeName: string) => {
    if (!window.confirm("Apakah Anda yakin ingin membatalkan validasi dokumen ini? Dokumen akan kembali ke status draft/menunggu verifikasi.")) return;

    try {
      const response = await apiFetch(`/api/receipts/${id}/unverify`, {
        method: "PUT",
      });

      if (!response.ok) throw new Error(await readApiError(response));
      
      const logMsg = `Pembatalan Verifikasi: Petugas membatalkan kuitansi nomor ${invoiceNo || "tanpa nomor"} dari ${storeName}.`;
      if (onUnverifyReceipt) onUnverifyReceipt(id, logMsg);
      
      await loadPendingDocuments(); // refresh list
      alert("Validasi kuitansi berhasil dibatalkan.");
    } catch (error: any) {
      console.error(error);
      alert("Gagal membatalkan verifikasi:\n" + (error?.message || "Kesalahan tidak diketahui"));
    }
  };

  const formatIDR = (
    num: number
  ) => {
    const safeNumber = (
      Number.isFinite(num)
        ? num
        : 0
    );

    const hasFraction = (
      Math.abs(
        safeNumber
        - Math.round(safeNumber)
      ) > 0.000001
    );

    return new Intl.NumberFormat(
      "id-ID",
      {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits:
          hasFraction ? 2 : 0,
        maximumFractionDigits: 2,
      }
    ).format(safeNumber);
  };

  return (
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6 border-b border-slate-100 pb-5">
        <div className="flex items-center gap-3">
          <div className="bg-indigo-50 text-indigo-600 p-2.5 rounded border border-indigo-150">
            <Receipt size={18} />
          </div>
          <div>
            <h2 className="text-base font-extrabold text-slate-800 tracking-tight">Pembacaan Kuitansi Otomatis (OCR)</h2>
            <p className="text-[11px] text-slate-500">
              Unggah struk belanja, baca otomatis dengan AI, verifikasi manual, sesuaikan pajak toko
            </p>
          </div>
        </div>

        {/* View Tabs */}
        <div className="flex bg-slate-100 border border-slate-200 rounded p-0.5 self-start sm:self-auto">
          <button
            onClick={() => setActiveTab("pending")}
            className={`px-3 py-1 rounded text-xs font-bold transition-all ${
              activeTab === "pending"
                ? "bg-white text-slate-800 shadow-xs"
                : "text-slate-500 hover:text-slate-800"
            }`}
          >
            Menunggu Verifikasi ({receipts.filter((r) => !r.isVerified).length})
          </button>
          <button
            onClick={() => setActiveTab("valid")}
            className={`px-3 py-1 rounded text-xs font-bold transition-all ${
              activeTab === "valid"
                ? "bg-white text-slate-800 shadow-xs"
                : "text-slate-500 hover:text-slate-800"
            }`}
          >
            Kuitansi Valid ({receipts.filter((r) => r.isVerified).length})
          </button>
        </div>
      </div>

      {/* File Drag Drop fallback */}
      <label className="block border border-dashed border-slate-200 rounded p-6 text-center hover:bg-slate-50/50 cursor-pointer mb-6 transition-all">
        <input 
          type="file" 
          accept="image/*,application/pdf" 
          className="hidden" 
          onChange={(e) => {
            if (e.target.files && e.target.files.length > 0) {
              handleFileUpload(e.target.files[0]);
            }
          }}
        />
        <UploadCloud size={24} className="text-indigo-600 mx-auto mb-2" />
        <h4 className="text-xs font-bold text-slate-700">Atau Unggah File Kuitansi / Foto Struk Baru</h4>
        <p className="text-[10px] text-slate-400 mt-1">Dukung format JPG, PNG, PDF. Sistem akan membaca detail kuitansi secara otomatis.</p>
      </label>

      {/* OCR Scanner Loading Animation */}
      {isScanning && (
        <div className="flex flex-col items-center py-10 bg-slate-50 rounded border border-slate-200 mb-6">
          <RefreshCw className="animate-spin text-indigo-600 mb-3" size={24} />
          <h3 className="text-xs font-bold text-slate-800">
            {ocrStatus === "uploading" ? "Mengunggah Dokumen..." : "Mesin OCR Sedang Membaca Dokumen..."}
          </h3>
          <p className="text-[11px] text-slate-400 max-w-sm text-center mt-1 leading-relaxed">
            {ocrStatus === "uploading" 
              ? "Sistem sedang mengunggah dokumen ke server sebelum diproses." 
              : "Mengekstrak nama toko, nomor kuitansi, daftar barang belanjaan, subtotal, dan mendeteksi persentase PPN..."}
          </p>
        </div>
      )}

      {/* Double Column Verification Workspace */}
      {activeDraft && (
        <div className="grid grid-cols-1 xl:grid-cols-12 gap-6 mb-8 border-t border-slate-200 pt-6">
          {/* LEFT: Live Receipt Visual Preview */}
          <div className="xl:col-span-5 bg-slate-50 border border-slate-200 rounded p-4 self-start">
            {selectedImage && (
              <div className="mb-4">
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Dokumen Asli:</span>
                {selectedMimeType === "application/pdf" ? (
                  <iframe src={selectedImage} className="w-full h-64 border border-slate-200 rounded" title="PDF Preview" />
                ) : (
                  <img src={selectedImage} alt="Struk" className="w-full max-h-48 object-contain rounded border border-slate-200 shadow-sm" />
                )}
              </div>
            )}
            
            <div className="bg-white border border-slate-200 rounded p-4 shadow-xs font-mono text-xs text-slate-700 space-y-4">
              <div className="text-center border-b border-dashed border-slate-200 pb-4">
                <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">{storeName || "NAMA TOKO"}</h4>
                <p className="text-[10px] text-slate-400 mt-0.5">Makassar, Sulawesi Selatan</p>
              </div>

              <div className="space-y-1">
                {ocrWarnings.length > 0 && (
                  <div className="bg-amber-50 border border-amber-200 rounded p-2 mb-3">
                    <div className="flex items-center gap-1.5 mb-1 text-amber-800">
                      <AlertTriangle size={12} />
                      <span className="text-[10px] font-bold uppercase">Peringatan OCR</span>
                    </div>
                    <ul className="list-disc pl-4 text-[9px] text-amber-700 space-y-0.5">
                      {ocrWarnings.map((w, idx) => (
                        <li key={idx}>{w.message}</li>
                      ))}
                    </ul>
                  </div>
                )}
                <div className="flex justify-between">
                  <span>No Nota:</span>
                  <span className="font-bold text-slate-800">{invoiceNo || "-"}</span>
                </div>
                <div className="flex justify-between">
                  <span>Tanggal:</span>
                  <span className="font-bold text-slate-800">{date || "-"}</span>
                </div>
                <div className="flex justify-between">
                  <span>Metode:</span>
                  <span className="font-bold text-slate-800">{method}</span>
                </div>
              </div>

              <div className="border-b border-dashed border-slate-200" />

              {/* Items List */}
              <div className="space-y-2">
                {items.map((it, idx) => (
                  <div key={idx} className="flex justify-between items-start">
                    <div className="max-w-[60%]">
                      <p className="font-bold text-slate-850">{it.name || "Nama Barang"}</p>
                      <p className="text-[10px] text-slate-450">
                        {it.qty} x {formatIDR(it.price)}
                      </p>
                    </div>
                    <span className="font-bold text-slate-850">{formatIDR(it.qty * it.price || 0)}</span>
                  </div>
                ))}
              </div>

              <div className="border-b border-dashed border-slate-200" />

              {/* Financial calculations */}
              <div className="space-y-1">
                <div className="flex justify-between">
                  <span>Subtotal:</span>
                  <span>{formatIDR(calculatedSubtotal)}</span>
                </div>
                <div className="flex justify-between text-indigo-700 font-bold">
                  <span>PPN ({calculatedTaxRate}%):</span>
                  <span>+ {formatIDR(calculatedTaxAmount)}</span>
                </div>
                <div className="flex justify-between text-xs font-extrabold text-slate-950 border-t border-dashed border-slate-200 pt-2">
                  <span>TOTAL:</span>
                  <span>{formatIDR(calculatedTotal)}</span>
                </div>
              </div>

              <div className="text-center bg-indigo-50 text-indigo-700 p-2 rounded border border-indigo-150 font-sans text-[10px] font-bold uppercase tracking-wider">
                DRAFT PEMBACAAN OCR
              </div>
            </div>

            {rawText && (
              <div className="mt-4 p-3 bg-slate-800 text-emerald-400 font-mono text-[9px] rounded whitespace-pre-wrap max-h-60 overflow-auto">
                <div className="text-slate-400 mb-2 uppercase tracking-wider font-bold">DEBUG: Raw OCR Text</div>
                {rawText}
              </div>
            )}
          </div>

          {/* RIGHT: Manual Override Form (Double Check) */}
          <div className="xl:col-span-7 bg-white border border-slate-200 rounded p-5 shadow-xs space-y-5">
            <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
              <Calculator size={16} className="text-indigo-600" />
              <h3 className="text-xs font-bold text-slate-700 uppercase tracking-wider">Workspace Verifikasi Manual & Penyesuaian Pajak</h3>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {/* Nama Toko */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Nama Toko / Penyedia
                </label>
                <input
                  type="text"
                  value={storeName}
                  onChange={(e) => setStoreName(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
              </div>

              {/* No Nota */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Nomor Nota / Invoice
                </label>
                <input
                  type="text"
                  value={invoiceNo}
                  onChange={(e) => setInvoiceNo(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
              </div>

              {/* Tanggal */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Tanggal Kuitansi
                </label>
                <input
                  type="date"
                  value={date}
                  onChange={(e) => setDate(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
              </div>

              {/* Metode Pengadaan */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Metode Pengadaan
                </label>
                <select
                  value={method}
                  onChange={(e) => setMethod(e.target.value as ProcurementMethod)}
                  className="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                  <option value={ProcurementMethod.SENDIRI}>{ProcurementMethod.SENDIRI}</option>
                  <option value={ProcurementMethod.VENDOR}>{ProcurementMethod.VENDOR}</option>
                </select>
              </div>
            </div>

            {/* TAX CONTROLS: adjustable store taxes */}
            <div className="bg-slate-50 rounded p-4 border border-slate-200">
              <span className="text-xs font-extrabold text-slate-700 uppercase tracking-wider block mb-2.5">Penyesuaian Pajak Kuitansi (PPN)</span>
              <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <div className="flex gap-4">
                  <label className="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                    <input
                      type="radio"
                      checked={isTaxed}
                      onChange={() => setIsTaxed(true)}
                      className="accent-indigo-600"
                    />
                    Kuitansi dengan Pajak (PPN)
                  </label>
                  <label className="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                    <input
                      type="radio"
                      checked={!isTaxed}
                      onChange={() => setIsTaxed(false)}
                      className="accent-indigo-600"
                    />
                    Bebas Pajak (Tanpa PPN)
                  </label>
                </div>

                {isTaxed && (
                  <div className="flex items-center gap-2">
                    <span className="text-xs text-slate-500 font-semibold">Tarif Pajak Toko:</span>
                    <div className="relative flex items-center">
                      <input
                        type="number"
                        min="0"
                        max="100"
                        value={taxRate}
                        onChange={(e) => setTaxRate(Math.max(0, parseInt(e.target.value) || 0))}
                        className="w-16 bg-white border border-slate-200 rounded px-2.5 py-1 text-xs font-bold text-slate-800 text-center focus:outline-none focus:ring-1 focus:ring-indigo-500"
                      />
                      <Percent size={11} className="absolute right-1.5 text-slate-400" />
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Editable Items Table */}
            <div>
              <div className="flex justify-between items-center mb-2">
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Item Barang Belanja</span>
                <button
                  type="button"
                  onClick={handleAddItem}
                  className="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 bg-indigo-50 px-2.5 py-1 rounded border border-indigo-150"
                >
                  <Plus size={11} />
                  Tambah Barang
                </button>
              </div>

              <div className="overflow-x-auto border border-slate-200 rounded max-h-[250px] overflow-y-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-slate-50 text-slate-600 text-[9px] font-bold uppercase tracking-wider border-b border-slate-200">
                      <th className="px-3 py-2">Nama Barang</th>
                      <th className="px-3 py-2 w-24 text-center">Jumlah</th>
                      <th className="px-3 py-2 w-24">Harga Satuan (Rp)</th>
                      <th className="px-3 py-2 w-24 text-right">Subtotal</th>
                      <th className="px-3 py-2 w-10 text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {items.map((it) => (
                      <tr key={it.id} className="hover:bg-slate-50/50 transition-colors">
                        <td className="px-3 py-1.5">
                          <input
                            type="text"
                            value={it.name}
                            onChange={(e) => handleUpdateItem(it.id, "name", e.target.value)}
                            placeholder="Nama item barang..."
                            className="w-full bg-white border border-slate-200 rounded px-2 py-1 text-xs text-slate-800 font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500"
                          />
                        </td>
                        <td className="px-3 py-1.5">
                          <input
                            type="number"
                            min="1"
                            value={it.qty}
                            onChange={(e) => handleUpdateItem(it.id, "qty", parseInt(e.target.value) || 1)}
                            className="w-full bg-white border border-slate-200 rounded px-2 py-1 text-xs text-center text-slate-800 font-bold focus:outline-none"
                          />
                        </td>
                        <td className="px-3 py-1.5">
                          <input
                            type="number"
                            min="0"
                            step="0.01"
                            value={it.price}
                            onChange={(e) =>
                              handleUpdateItem(
                                it.id,
                                "price",
                                Number.parseFloat(
                                  e.target.value
                                ) || 0
                              )
                            }
                            className="w-full bg-white border border-slate-200 rounded px-2 py-1 text-xs text-slate-800 font-semibold focus:outline-none"
                          />
                        </td>
                        <td className="px-3 py-1.5 text-right text-xs font-bold text-slate-700">
                          {formatIDR(it.qty * it.price)}
                        </td>
                        <td className="px-3 py-1.5 text-center">
                          <button
                            type="button"
                            onClick={() => handleRemoveItem(it.id)}
                            className="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1 rounded"
                          >
                            <Trash2 size={12} />
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>



            {/* Form Actions */}
            <div className="flex flex-col sm:flex-row sm:justify-end gap-2.5 pt-3 border-t border-slate-100">
              <button
                type="button"
                onClick={closeWorkspace}
                disabled={isSavingDraft || isVerifying}
                className="px-3.5 py-2 rounded text-xs font-bold text-slate-500 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-all disabled:opacity-50 flex items-center justify-center gap-1.5"
              >
                <X size={13} />
                Tutup
              </button>
              <button
                type="button"
                onClick={handleSaveDraft}
                disabled={isSavingDraft || isVerifying}
                className="px-3.5 py-2 rounded text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition-all disabled:opacity-50 flex items-center justify-center gap-1.5"
              >
                {isSavingDraft ? <RefreshCw size={13} className="animate-spin" /> : <Save size={13} />}
                {isSavingDraft ? "Menyimpan Draft..." : "Simpan Draft"}
              </button>
              <button
                type="button"
                onClick={handleVerifySave}
                disabled={isSavingDraft || isVerifying}
                className="px-4 py-2 rounded text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-all shadow-xs flex items-center justify-center gap-1.5 disabled:opacity-50"
              >
                {isVerifying ? <RefreshCw size={13} className="animate-spin" /> : <CheckCircle size={13} />}
                {isVerifying ? "Menyimpan Verifikasi..." : "Selesaikan & Verifikasi Dokumen"}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Verified / Historical Receipts List */}
      <div className="mt-6">
        <div className="flex justify-between items-center mb-3">
          <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
            {activeTab === "pending" ? "Daftar Dokumen Masuk Menunggu Verifikasi" : "Daftar Kuitansi Valid Terverifikasi"}
          </span>
        </div>

        <div className="overflow-x-auto border border-slate-200 rounded">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50 text-slate-600 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                <th className="px-5 py-3">No Nota / Invoice</th>
                <th className="px-5 py-3">Nama Toko</th>
                <th className="px-5 py-3">Tanggal Belanja</th>
                <th className="px-5 py-3">Metode Pengadaan</th>
                <th className="px-5 py-3 text-center">Tarif Pajak PPN</th>
                <th className="px-5 py-3 text-right">Total Nilai</th>
                <th className="px-5 py-3 text-center">Status Verifikasi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {activeTab === "pending" ? (
                pendingDocuments.length > 0 ? (
                  pendingDocuments.map((doc) => (
                    <tr key={`doc-${doc.id}`} className="hover:bg-slate-50/50 transition-colors text-xs font-mono">
                      <td className="px-5 py-3 font-semibold text-slate-700">
                        {doc.summary?.invoiceNo || "-"}
                      </td>
                      <td className="px-5 py-3 font-bold text-slate-800 font-sans">
                        {doc.summary?.storeName || doc.original_filename}
                      </td>
                      <td className="px-5 py-3 text-slate-500 font-sans">
                        {doc.summary?.date || "-"}
                      </td>
                      <td className="px-5 py-3 font-semibold text-slate-600 font-sans">
                        {doc.summary?.method || "-"}
                      </td>
                      <td className="px-5 py-3 text-center font-bold text-indigo-600">
                        {doc.summary?.isTaxed ? `${doc.summary.taxRate}%` : "0% (Bebas)"}
                      </td>
                      <td className="px-5 py-3 text-right font-bold text-slate-800 font-sans">
                        {formatIDR(doc.summary?.total || 0)}
                      </td>
                      <td className="px-5 py-3 text-center font-sans">
                        <div className="flex items-center justify-center gap-1.5">
                          <button
                            onClick={() => {
                              if (isScanning || isSavingDraft || isVerifying) return;
                              setIsScanning(true);
                              setOcrStatus("processing");
                              pollDocumentStatus(doc.id).finally(() => setIsScanning(false));
                            }}
                            className="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold uppercase transition-colors flex items-center gap-1"
                          >
                            <FolderOpen size={12} />
                            Buka Draft
                          </button>
                          <button
                            onClick={() => handleDeleteDraft(doc.id)}
                            disabled={isScanning || isSavingDraft || isVerifying}
                            className="p-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded transition-colors disabled:opacity-50"
                            title="Hapus Draft"
                          >
                            <Trash2 size={14} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={7} className="text-center py-8 text-slate-400 text-xs font-medium font-sans">
                      Tidak ada dokumen menunggu verifikasi.
                    </td>
                  </tr>
                )
              ) : (
                receipts.filter((r) => r.isVerified).length > 0 ? (
                  receipts.filter((r) => r.isVerified).map((rc) => (
                    <tr key={rc.id} className="hover:bg-slate-50/50 transition-colors text-xs font-mono">
                      <td className="px-5 py-3 font-semibold text-slate-700">
                        {rc.invoiceNo}
                      </td>
                      <td className="px-5 py-3 font-bold text-slate-800 font-sans">
                        {rc.storeName}
                      </td>
                      <td className="px-5 py-3 text-slate-500 font-sans">
                        {rc.date}
                      </td>
                      <td className="px-5 py-3 font-semibold text-slate-600 font-sans">
                        {rc.method}
                      </td>
                      <td className="px-5 py-3 text-center font-bold text-indigo-600">
                        {rc.isTaxed ? `${rc.taxRate}%` : "0% (Bebas)"}
                      </td>
                      <td className="px-5 py-3 text-right font-bold text-slate-800 font-sans">
                        {formatIDR(rc.total)}
                      </td>
                      <td className="px-5 py-3 text-center font-sans">
                        <div className="flex flex-col items-center gap-1.5">
                          <span className="inline-flex items-center gap-1 text-[10px] px-2.5 py-0.5 rounded border font-bold bg-emerald-50 text-emerald-800 border-emerald-100">
                            Dokumen Valid
                          </span>
                          <button
                            onClick={() => handleUnverify(rc.id, rc.invoiceNo, rc.storeName)}
                            className="text-[9px] font-bold text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2 py-0.5 rounded border border-rose-100 transition-colors"
                          >
                            Batalkan
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={7} className="text-center py-8 text-slate-400 text-xs font-medium font-sans">
                      Tidak ada kuitansi valid.
                    </td>
                  </tr>
                )
              )}
</tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
