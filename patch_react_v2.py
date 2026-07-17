import os
import re

def patch():
    path = r"d:\Project\siperbang\resources\js\components\ReceiptOCRProcessor.tsx"
    with open(path, "r", encoding="utf-8") as f:
        content = f.read()

    # 1. Update imports
    old_imports = """import {
  ReceiptData,
  ReceiptItem,
  ProcurementMethod,
  RequestStatus,
  ItemRequest,
  ParsedReceiptResult,
  OcrWarning,
  OcrField,
  ReceiptDocument,
} from "../types";"""
    new_imports = """import {
  ReceiptData,
  ReceiptItem,
  ProcurementMethod,
  RequestStatus,
  ItemRequest,
  ParsedReceiptResult,
  OcrWarning,
  OcrField,
  ReceiptDocument,
  ReceiptManualDraft,
} from "../types";"""
    content = content.replace(old_imports, new_imports)

    old_icons = """import {
  FileDown,
  UploadCloud,
  FileText,
  CheckCircle,
  RefreshCw,
  Plus,
  Trash2,
  Edit3,
  Settings,
  Calculator,
  Percent,
  Sparkles,
  Receipt,
  AlertTriangle,
  ShieldCheck,
  ShieldAlert,
  Cpu,
} from "lucide-react";"""
    new_icons = """import {
  FileDown,
  UploadCloud,
  FileText,
  CheckCircle,
  RefreshCw,
  Plus,
  Trash2,
  Edit3,
  Settings,
  Calculator,
  Percent,
  Sparkles,
  Receipt,
  AlertTriangle,
  ShieldCheck,
  ShieldAlert,
  Cpu,
  Save,
  FolderOpen,
  X,
} from "lucide-react";"""
    content = content.replace(old_icons, new_icons)

    # 2. Add State Variables & Hooks
    state_to_add = """  const [
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
  ] = useState(false);"""
    
    if "isSavingDraft" not in content:
        content = content.replace(
            "const [rawText, setRawText] = useState<string>(\"\");",
            "const [rawText, setRawText] = useState<string>(\"\");\n\n" + state_to_add
        )

    read_api_error = """  const readApiError = async (
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
        return messages.join("\\n");
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
"""
    if "readApiError = async" not in content:
        content = content.replace(
            "const normalizeWarnings =",
            read_api_error + "\n  const normalizeWarnings ="
        )

    # Allow draft in polling
    old_poll_cond = """if (data.status === "needs_review" || data.status === "verified") {"""
    new_poll_cond = """if (
          data.status === "needs_review"
          || data.status === "draft"
          || data.status === "verified"
        ) {"""
    if old_poll_cond in content:
        content = content.replace(old_poll_cond, new_poll_cond)
    else:
        content = re.sub(
            r'if\s*\(\s*data\.status\s*===\s*"needs_review"\s*\|\|\s*data\.status\s*===\s*"verified"\s*\)\s*\{',
            new_poll_cond,
            content
        )

    if "setActiveDraft(newDraft);" in content:
        content = content.replace(
            "setActiveDraft(newDraft);",
            "setActiveDraft(newDraft);\n        await loadPendingDocuments();"
        )

    # 3. Replace handleVerifySave entirely
    start_handle = content.find("  const handleVerifySave = async () => {")
    end_handle = content.find("  const formatIDR = (", start_handle)
    
    new_handle = """  const buildManualPayload = () => ({
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
      alert("Gagal menyimpan draft:\\n" + (error?.message || "Kesalahan tidak diketahui"));
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
      alert("Gagal menyimpan verifikasi:\\n" + (error?.message || "Kesalahan tidak diketahui"));
    } finally {
      setIsVerifying(false);
    }
  };

"""
    if start_handle != -1 and end_handle != -1:
        content = content[:start_handle] + new_handle + content[end_handle:]

    # 4. Replace action buttons in form
    old_buttons = """<div className="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setActiveDraft(null)}
                className="px-3.5 py-2 rounded text-xs font-bold text-slate-500 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-all"
              >
                Tolak Draft
              </button>
              <button
                type="button"
                onClick={handleVerifySave}
                className="px-4 py-2 rounded text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-all shadow-xs flex items-center gap-1.5"
              >
                <CheckCircle size={13} />
                Selesaikan & Verifikasi Dokumen
              </button>
            </div>"""
    
    new_buttons = """<div className="flex flex-col sm:flex-row sm:justify-end gap-2.5 pt-3 border-t border-slate-100">
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
            </div>"""
            
    content = content.replace(old_buttons, new_buttons)

    # 5. Replace the "Menunggu Verifikasi" table body ONLY
    # It starts with "Daftar Dokumen Masuk Menunggu Verifikasi", we will search for the first <tbody> after that!
    
    table_header = 'activeTab === "pending" ? "Daftar Dokumen Masuk Menunggu Verifikasi"'
    header_idx = content.find(table_header)
    
    tbody_start = content.find('<tbody className="divide-y divide-slate-100">', header_idx)
    tbody_end = content.find("</tbody>", tbody_start)
    
    new_tbody = """<tbody className="divide-y divide-slate-100">
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
                        <button
                          onClick={() => {
                            if (isScanning || isSavingDraft || isVerifying) return;
                            setIsScanning(true);
                            setOcrStatus("processing");
                            pollDocumentStatus(doc.id).finally(() => setIsScanning(false));
                          }}
                          className="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold uppercase transition-colors flex items-center justify-center gap-1 mx-auto"
                        >
                          <FolderOpen size={12} />
                          Buka Draft
                        </button>
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
                        <span className="inline-flex items-center gap-1 text-[10px] px-2.5 py-0.5 rounded border font-bold bg-emerald-50 text-emerald-800 border-emerald-100">
                          Dokumen Valid
                        </span>
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
"""
    if tbody_start != -1 and tbody_end != -1:
        content = content[:tbody_start] + new_tbody + content[tbody_end:]

    with open(path, "w", encoding="utf-8") as f:
        f.write(content)

if __name__ == "__main__":
    patch()
    print("Patch V2 applied successfully.")
