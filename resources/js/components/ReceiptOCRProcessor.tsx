import React, { useState, useRef, useEffect } from "react";
import { ReceiptData, ReceiptItem, ProcurementMethod, RequestStatus, ItemRequest, ParsedReceiptResult, OcrWarning, OcrField, ReceiptDocument, ReceiptManualDraft, InventoryCodeOption } from "../types";
import { apiFetch } from "../api";
import { FileDown, UploadCloud, FileText, CheckCircle, RefreshCw, Plus, Trash2, Edit3, Settings, Calculator, Percent, Sparkles, Receipt, AlertTriangle, ShieldCheck, ShieldAlert, Cpu, Save, FolderOpen, X, Download, TableProperties, Pencil } from "lucide-react";
import { ConfirmDialog } from "./ConfirmDialog";

interface ReceiptOCRProcessorProps {
  receipts: ReceiptData[];
  requests: ItemRequest[];
  onAddReceipt: (newReceipt: ReceiptData) => void;
  onVerifyReceipt: (
    id: string,
    updatedReceipt: ReceiptData,
    logMsg: string
  ) => void | Promise<void>;
  onUnverifyReceipt?: (
    id: string,
    logMsg: string
  ) => void | Promise<void>;
}

interface StockMasterOption {
  id: number;
  code: string;
  name: string;
  category: string;
  qty: number;
  unit: string;
}

const RECEIPT_UNIT_OPTIONS = [
  "PCS",
  "PAK",
  "RIM",
  "BKS",
  "BOX",
  "DUS",
  "RG",
  "KEPING",
  "BOTOL",
  "JERIGEN",
  "LEMBAR",
  "BUAH",
  "UNIT",
  "SET",
  "ROLL",
  "LUSIN",
  "SACHET",
  "KG",
  "GRAM",
  "LITER",
] as const;

const normalizeInventoryCode = (
  value: unknown
): string => (
  String(value ?? "")
    .replace(/\D/g, "")
    .slice(0, 10)
);

const normalizeStockItemId = (
  value: unknown
): number | null => {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const id = Number(value);

  return Number.isInteger(id) && id > 0
    ? id
    : null;
};


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
  const [dialogConfirm, setDialogConfirm] = useState<{
    title: string;
    message: string;
    variant?: "danger" | "warning" | "info" | "success";
    confirmText?: string;
    cancelText?: string;
    onConfirm?: () => void | Promise<void>;
  } | null>(null);
  const [dialogAlert, setDialogAlert] = useState<{
    title: string;
    message: string;
    variant?: "danger" | "warning" | "info" | "success";
  } | null>(null);
  const [dialogLoading, setDialogLoading] = useState(false);
  const [exportingExcelKey, setExportingExcelKey] = useState<string | null>(null);
  const pollTimerRef = useRef<number | NodeJS.Timeout | null>(null);
  const documentRequestTokenRef = useRef(0);

  const cancelDocumentRequest = () => {
    if (pollTimerRef.current) {
      clearTimeout(
        pollTimerRef.current as number
      );

      pollTimerRef.current = null;
    }

    documentRequestTokenRef.current += 1;
  };

  const beginDocumentRequest = (): number => {
    cancelDocumentRequest();

    return documentRequestTokenRef.current;
  };

  const isCurrentDocumentRequest = (
    requestToken: number
  ): boolean => (
    requestToken
    === documentRequestTokenRef.current
  );

  useEffect(() => {
    return () => {
      if (pollTimerRef.current) {
        clearTimeout(
          pollTimerRef.current as number
        );
      }

      documentRequestTokenRef.current += 1;
    };
  }, []);

  useEffect(() => {
    return () => {
      if (
        selectedImage
        && selectedImage.startsWith("blob:")
      ) {
        URL.revokeObjectURL(
          selectedImage
        );
      }
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
  const [
    inventoryCodes,
    setInventoryCodes,
  ] = useState<InventoryCodeOption[]>([]);
  const [
    inventoryCodesLoading,
    setInventoryCodesLoading,
  ] = useState(false);
  const [
    stockMasterItems,
    setStockMasterItems,
  ] = useState<StockMasterOption[]>([]);
  const [
    stockMasterLoading,
    setStockMasterLoading,
  ] = useState(false);
  const [
    openStockDropdownId,
    setOpenStockDropdownId,
  ] = useState<string | null>(null);
  const [bastName, setBastName] = useState("");
  const [bastDate, setBastDate] = useState("");

  // State for editing inventory codes on already-verified receipts
  const [editingReceipt, setEditingReceipt] = useState<ReceiptData | null>(null);
  const [editItemCodes, setEditItemCodes] = useState<Record<string, { inventory_code: string; unit: string }>>({}); // keyed by item.id
  const [isSavingEdit, setIsSavingEdit] = useState(false);

  useEffect(() => {
    let active = true;

    const loadInventoryCodes = async () => {
      setInventoryCodesLoading(true);

      try {
        const response = await apiFetch(
          "/api/inventory-codes"
        );

        if (!response.ok) {
          throw new Error(
            `HTTP ${response.status} ${response.statusText}`
          );
        }

        const payload = await response.json();
        const options = Array.isArray(
          payload?.data
        )
          ? payload.data
          : [];

        if (active) {
          setInventoryCodes(
            options.map(
              (
                option: any
              ): InventoryCodeOption => ({
                code: normalizeInventoryCode(
                  option.code
                ),
                formatted_code:
                  String(
                    option.formatted_code
                    ?? option.code
                    ?? ""
                  ),
                description:
                  String(
                    option.description
                    ?? ""
                  ),
                category:
                  option.category
                    ? String(option.category)
                    : null,
              })
            )
          );
        }
      } catch (error) {
        console.error(
          "Gagal memuat kode persediaan 1.01.03:",
          error
        );

        if (active) {
          setInventoryCodes([]);
        }
      } finally {
        if (active) {
          setInventoryCodesLoading(false);
        }
      }
    };

    void loadInventoryCodes();

    return () => {
      active = false;
    };
  }, []);

  const loadStockMasterItems = async () => {
    setStockMasterLoading(true);

    try {
      const response = await apiFetch(
        "/api/stocks"
      );

      if (!response.ok) {
        throw new Error(
          `HTTP ${response.status} ${response.statusText}`
        );
      }

      const payload = await response.json();
      const rows = Array.isArray(payload)
        ? payload
        : [];

      setStockMasterItems(
        rows
          .filter(
            (row: any) =>
              row?.is_active !== false
          )
          .map(
            (row: any): StockMasterOption => ({
              id: Number(row.id),
              code: normalizeInventoryCode(
                row.code
              ),
              name: String(row.name ?? ""),
              category: String(
                row.category ?? ""
              ),
              qty: Number(row.qty ?? 0),
              unit: String(row.unit ?? "")
                .trim()
                .toUpperCase(),
            })
          )
          .filter(
            (row: StockMasterOption) =>
              Number.isInteger(row.id)
              && row.id > 0
              && row.name.length > 0
              && row.code.startsWith("10103")
          )
          .sort(
            (
              left: StockMasterOption,
              right: StockMasterOption
            ) => left.name.localeCompare(
              right.name,
              "id"
            )
          )
      );
    } catch (error) {
      console.error(
        "Gagal memuat master barang:",
        error
      );

      setStockMasterItems([]);
    } finally {
      setStockMasterLoading(false);
    }
  };

  useEffect(() => {
    void loadStockMasterItems();
  }, []);

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
    setDialogConfirm({
      title: "Hapus Draft Kuitansi",
      message: "Yakin ingin menghapus draft kuitansi ini? Aksi ini tidak dapat dibatalkan.",
      variant: "danger",
      onConfirm: async () => {
        setDialogLoading(true);
        try {
          const res = await apiFetch(`/api/receipt-documents/${id}`, {
            method: "DELETE",
          });
          if (!res.ok) {
            throw new Error(await readApiError(res));
          }
          setDialogConfirm(null);
          setDialogLoading(false);
          setDialogAlert({ title: "Berhasil", message: "Draft kuitansi berhasil dihapus.", variant: "success" });
          await loadPendingDocuments();
        } catch (e: any) {
          setDialogConfirm(null);
          setDialogLoading(false);
          setDialogAlert({ title: "Gagal", message: "Gagal menghapus draft: " + (e.message || "Kesalahan server"), variant: "danger" });
        }
      },
    });
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
    startedAt = Date.now(),
    requestToken = documentRequestTokenRef.current
  ) => {
    if (
      !isCurrentDocumentRequest(
        requestToken
      )
    ) {
      return;
    }

    const elapsedMs = Date.now() - startedAt;
    const SOFT_LIMIT_MS = 20_000;
    const HARD_LIMIT_MS = 120_000;

    if (elapsedMs > HARD_LIMIT_MS) {
      setIsScanning(false);
      setOcrStatus("timeout");
      setActiveDocumentId(id);

      setDialogAlert({
        title: "Waktu Habis",
        message: "Tampilan berhenti menunggu setelah 2 menit. Dokumen tetap tersimpan. Periksa status dokumen dan terminal queue untuk mengetahui hasil akhirnya.",
        variant: "warning",
      });

      return;
    }

    try {
      const res = await apiFetch(`/api/receipt-documents/${id}`);

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }

      const data = await res.json();

      if (
        !isCurrentDocumentRequest(
          requestToken
        )
      ) {
        return;
      }

      if (
          data.status === "needs_review"
          || data.status === "draft"
          || data.status === "verified"
        ) {
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
                name: String(
                  extractValue(it.name, "")
                  ?? ""
                ),
                qty,
                unit: String(
                  extractValue(it.unit, "")
                  ?? ""
                ),
                inventoryCode:
                  normalizeInventoryCode(
                    extractValue(
                      it.inventory_code,
                      ""
                    )
                  ),
                inventoryCodeDescription:
                  typeof it.inventory_code_description
                    === "string"
                      ? it.inventory_code_description
                      : null,
                stockItemId:
                  normalizeStockItemId(
                    it.stock_item_id
                  ),
                codeConfidence:
                  Number.isFinite(
                    Number(
                      it.inventory_code
                        ?.confidence
                    )
                  )
                    ? Number(
                        it.inventory_code
                          ?.confidence
                      )
                    : null,
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
                name: String(
                  extractValue(it.name, "")
                  ?? ""
                ),
                qty: 1,
                unit: String(
                  extractValue(it.unit, "")
                  ?? ""
                ),
                inventoryCode:
                  normalizeInventoryCode(
                    extractValue(
                      it.inventory_code,
                      ""
                    )
                  ),
                inventoryCodeDescription:
                  typeof it.inventory_code_description
                    === "string"
                      ? it.inventory_code_description
                      : null,
                stockItemId:
                  normalizeStockItemId(
                    it.stock_item_id
                  ),
                codeConfidence:
                  Number.isFinite(
                    Number(
                      it.inventory_code
                        ?.confidence
                    )
                  )
                    ? Number(
                        it.inventory_code
                          ?.confidence
                      )
                    : null,
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
              name: String(
                extractValue(it.name, "")
                ?? ""
              ),
              qty: 1,
              unit: String(
                extractValue(it.unit, "")
                ?? ""
              ),
              inventoryCode:
                normalizeInventoryCode(
                  extractValue(
                    it.inventory_code,
                    ""
                  )
                ),
              inventoryCodeDescription:
                typeof it.inventory_code_description
                  === "string"
                    ? it.inventory_code_description
                    : null,
              stockItemId:
                normalizeStockItemId(
                  it.stock_item_id
                ),
              codeConfidence:
                Number.isFinite(
                  Number(
                    it.inventory_code
                      ?.confidence
                  )
                )
                  ? Number(
                      it.inventory_code
                        ?.confidence
                    )
                  : null,
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
          finalItems = m.items.map((item: any, index: number) => {
            const suggestedItem = safeItems[index];
            const manualCode = normalizeInventoryCode(
              item.inventoryCode
              ?? item.inventory_code
              ?? ""
            );

            return {
              id: `it-draft-manual-${index}`,
              name: item.name || "",
              qty: Number(item.qty) || 1,
              unit: String(
                item.unit
                ?? suggestedItem?.unit
                ?? ""
              ),
              inventoryCode:
                manualCode
                || suggestedItem?.inventoryCode
                || "",
              inventoryCodeDescription:
                suggestedItem
                  ?.inventoryCodeDescription
                ?? null,
              stockItemId:
                normalizeStockItemId(
                  item.stockItemId
                  ?? item.stock_item_id
                )
                ?? suggestedItem?.stockItemId
                ?? null,
              codeConfidence:
                suggestedItem?.codeConfidence
                ?? null,
              price: Number(item.price) || 0,
              subtotal: (Number(item.qty) || 1) * (Number(item.price) || 0),
            };
          });
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
        void loadPendingDocuments();
        setStoreName(newDraft.storeName);
        setInvoiceNo(newDraft.invoiceNo);
        setDate(newDraft.date);
        setIsTaxed(newDraft.isTaxed);
        setTaxRate(newDraft.taxRate);
        setItems(newDraft.items);
        setBastName(newDraft.bastName || "");
        setBastDate(newDraft.bastDate || "");

        /*
         * Preview menggunakan URL yang langsung terikat
         * pada ID dokumen. Tidak ada lagi fetch Blob
         * terpisah yang dapat selesai terlambat lalu
         * menimpa preview draft yang baru dibuka.
         */
        if (
          !isCurrentDocumentRequest(
            requestToken
          )
        ) {
          return;
        }

        const contentType = (
          typeof data.mime_type === "string"
          && data.mime_type.length > 0
        )
          ? data.mime_type
          : "application/pdf";

        setSelectedMimeType(
          contentType
        );

        setSelectedImage(
          `/api/receipt-documents/${id}/file`
          + `?draft=${id}`
          + `&request=${requestToken}`
          + `&t=${Date.now()}`
        );

        setIsScanning(false);

        return;
      }

      if (data.status === "failed") {
        if (
          !isCurrentDocumentRequest(
            requestToken
          )
        ) {
          return;
        }

        setIsScanning(false);
        setActiveDocumentId(null);
        setOcrStatus("failed");
        setDialogAlert({ title: "OCR Gagal", message: "Proses OCR gagal: " + (data.error_message || "Kesalahan tidak diketahui"), variant: "danger" });
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
      if (
        !isCurrentDocumentRequest(
          requestToken
        )
      ) {
        return;
      }

      pollTimerRef.current = setTimeout(
        () => {
          void pollDocumentStatus(
            id,
            startedAt,
            requestToken
          );
        },
        1000
      );
    } catch (e) {
      if (
        !isCurrentDocumentRequest(
          requestToken
        )
      ) {
        return;
      }

      console.error(e);
      setIsScanning(false);
      setOcrStatus("error");
      setDialogAlert({ title: "Kesalahan", message: "Terjadi kesalahan saat mengecek status OCR.", variant: "danger" });
    }
  };

  const handleFileUpload = async (file: File) => {
    if (isScanning) return;

    const requestToken =
      beginDocumentRequest();

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
      void pollDocumentStatus(
        docId,
        Date.now(),
        requestToken
      );
    } catch (e: any) {
      console.error(e);
      setIsScanning(false);
      setOcrStatus("error");
      setDialogAlert({ title: "Gagal Unggah", message: "Gagal mengunggah dokumen OCR: " + (e.message || "Kesalahan tidak diketahui"), variant: "danger" });
    }
  };

  const handleAddItem = () => {
    const newItem: ReceiptItem = {
      id: "it-draft-" + Date.now(),
      name: "",
      qty: 1,
      unit: "",
      inventoryCode: "",
      inventoryCodeDescription: null,
      stockItemId: null,
      codeConfidence: null,
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

  const normaliseMasterSearch = (
    value: string
  ): string => (
    value
      .trim()
      .toLocaleLowerCase("id-ID")
  );

  const getStockMasterMatches = (
    query: string
  ): StockMasterOption[] => {
    const needle = normaliseMasterSearch(
      query
    );

    const matches = needle === ""
      ? stockMasterItems
      : stockMasterItems.filter(
          (stockItem) => {
            const searchable = [
              stockItem.name,
              stockItem.code,
              stockItem.category,
              stockItem.unit,
            ]
              .join(" " )
              .toLocaleLowerCase("id-ID");

            return searchable.includes(
              needle
            );
          }
        );

    return matches.slice(0, 8);
  };

  const getSelectedStockMaster = (
    item: ReceiptItem
  ): StockMasterOption | null => (
    item.stockItemId
      ? (
          stockMasterItems.find(
            (stockItem) =>
              stockItem.id
              === item.stockItemId
          ) ?? null
        )
      : null
  );

  const handleItemNameChange = (
    id: string,
    value: string
  ) => {
    setItems(
      (previous) => previous.map(
        (item) =>
          item.id === id
            ? {
                ...item,
                name: value,
                /*
                 * Begitu nama diketik manual, hubungan ke barang
                 * master lama dilepas. Pengguna dapat memilih lagi
                 * dari dropdown atau membiarkannya sebagai barang baru.
                 */
                stockItemId: null,
              }
            : item
      )
    );

    setOpenStockDropdownId(id);
  };

  const handleSelectStockMaster = (
    id: string,
    stockItem: StockMasterOption
  ) => {
    const inventoryCode =
      inventoryCodes.find(
        (option) =>
          option.code === stockItem.code
      );

    setItems(
      (previous) => previous.map(
        (item) =>
          item.id === id
            ? {
                ...item,
                name: stockItem.name,
                unit: stockItem.unit,
                inventoryCode:
                  stockItem.code,
                inventoryCodeDescription:
                  inventoryCode?.description
                  ?? stockItem.name,
                stockItemId:
                  stockItem.id,
              }
            : item
      )
    );

    setOpenStockDropdownId(null);
  };

  const handleUnitChange = (
    id: string,
    unit: string
  ) => {
    setItems(
      (previous) => previous.map(
        (item) => {
          if (item.id !== id) {
            return item;
          }

          const selectedMaster =
            getSelectedStockMaster(item);

          return {
            ...item,
            unit,
            stockItemId:
              selectedMaster
              && selectedMaster.unit === unit
                ? item.stockItemId
                : null,
          };
        }
      )
    );
  };

  const handleInventoryCodeChange = (
    id: string,
    codeValue: string
  ) => {
    const code = normalizeInventoryCode(
      codeValue
    );

    const selected = inventoryCodes.find(
      (option) => option.code === code
    );

    setItems(
      (previous) => previous.map(
        (item) => {
          if (item.id !== id) {
            return item;
          }

          const selectedMaster =
            getSelectedStockMaster(item);

          return {
            ...item,
            inventoryCode: code,
            inventoryCodeDescription:
              selected?.description
              ?? null,
            stockItemId:
              selectedMaster
              && selectedMaster.code === code
                ? item.stockItemId
                : null,
          };
        }
      )
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
      unit: item.unit.trim(),
      inventoryCode:
        normalizeInventoryCode(
          item.inventoryCode
        ),
      stockItemId:
        item.stockItemId ?? null,
      price: Number(item.price),
    })),
    method,
    bastName: bastName.trim(),
    bastDate: bastDate || null,
  });

  const closeWorkspace = () => {
    cancelDocumentRequest();
    setIsScanning(false);
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
      setDialogAlert({ title: "Berhasil", message: "Draft verifikasi berhasil disimpan. Dokumen dapat dibuka kembali dari daftar Menunggu Verifikasi.", variant: "success" });
    } catch (error: any) {
      console.error(error);
      setDialogAlert({ title: "Gagal", message: "Gagal menyimpan draft:\n" + (error?.message || "Kesalahan tidak diketahui"), variant: "danger" });
    } finally {
      setIsSavingDraft(false);
    }
  };

  const getVerificationValidationMessage = (): string | null => {
    if (!storeName.trim()) {
      return "Nama toko/penyedia wajib diisi sebelum verifikasi.";
    }

    if (!date) {
      return "Tanggal kuitansi wajib diisi sebelum verifikasi.";
    }

    const invalidItemIndex = items.findIndex(
      (item) =>
        !item.name.trim()
        || !item.unit.trim()
        || !/^10103\d{5}$/.test(
          normalizeInventoryCode(
            item.inventoryCode
          )
        )
        || !Number.isInteger(Number(item.qty))
        || Number(item.qty) < 1
        || !Number.isFinite(Number(item.price))
        || Number(item.price) <= 0
    );

    if (items.length === 0) {
      return "Minimal satu barang wajib diisi.";
    }

    if (invalidItemIndex >= 0) {
      return `Periksa barang ke-${invalidItemIndex + 1}. Nama, kode persediaan kategori 1.01.03, satuan, jumlah, dan harga wajib valid.`;
    }

    return null;
  };

  const performVerification = async () => {
    if (
      !activeDraft
      || !activeDocumentId
      || isSavingDraft
      || isVerifying
    ) {
      return {
        success: false as const,
        message: "Dokumen tidak siap untuk diverifikasi.",
      };
    }

    setIsVerifying(true);

    try {
      const response = await apiFetch(
        `/api/receipt-documents/${activeDocumentId}/verify`,
        {
          method: "PUT",
          body: JSON.stringify(buildManualPayload()),
        }
      );

      if (!response.ok) {
        throw new Error(
          await readApiError(response)
        );
      }

      const responsePayload = await response.json();
      const receipt = responsePayload?.data?.receipt;

      if (!receipt) {
        throw new Error(
          "Server tidak mengembalikan data kuitansi yang sudah diverifikasi."
        );
      }

      const serverItems = Array.isArray(receipt.items)
        ? receipt.items.map((item: any) => ({
            id: String(item.id),
            name: String(item.name),
            qty: Number(item.qty),
            unit: String(item.unit ?? ""),
            inventoryCode:
              normalizeInventoryCode(
                item.inventory_code
                ?? ""
              ),
            inventoryCodeDescription:
              item.inventory_code_master
                ?.nama_barang
                ?? null,
            stockItemId:
              normalizeStockItemId(
                item.stock_item_id
              ),
            codeConfidence: null,
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
        method:
          (receipt.method as ProcurementMethod)
          || method,
        bastName:
          receipt.bast_name
          || bastName,
        bastDate:
          receipt.bast_date
            ? String(receipt.bast_date).slice(0, 10)
            : bastDate,
      };

      const displayInvoice =
        finalReceipt.invoiceNo
        || "tanpa nomor";

      const logMsg =
        `Verifikasi Kuitansi: Petugas memverifikasi kuitansi nomor ${displayInvoice} dari ${finalReceipt.storeName}. Total belanja ${formatIDR(finalReceipt.total)}.`;

      await Promise.resolve(
        onVerifyReceipt(
          activeDraft.id,
          finalReceipt,
          logMsg
        )
      );

      await loadStockMasterItems();

      setPendingDocuments(
        (previous) => previous.filter(
          (document) =>
            document.id !== activeDocumentId
        )
      );

      closeWorkspace();

      return {
        success: true as const,
        message:
          (responsePayload.message
            || "Dokumen berhasil diverifikasi.")
          + " Kode, satuan, dan jumlah stok master telah tersimpan di database.",
      };
    } catch (error: any) {
      console.error(error);

      return {
        success: false as const,
        message:
          "Gagal menyimpan verifikasi:\n"
          + (
            error?.message
            || "Kesalahan tidak diketahui"
          ),
      };
    } finally {
      setIsVerifying(false);
    }
  };

  const handleVerifySave = () => {
    if (
      !activeDraft
      || !activeDocumentId
      || isSavingDraft
      || isVerifying
    ) {
      return;
    }

    const validationMessage =
      getVerificationValidationMessage();

    if (validationMessage) {
      setDialogAlert({
        title: "Validasi Gagal",
        message: validationMessage,
        variant: "warning",
      });

      return;
    }

    setDialogConfirm({
      title: "Konfirmasi Verifikasi Kuitansi",
      message:
        "Apakah Anda yakin seluruh data sudah benar? Setelah diverifikasi, data kuitansi, kode persediaan, dan satuan akan disimpan ke database dan digunakan pada ekspor Excel.",
      variant: "warning",
      confirmText: "Ya, Verifikasi",
      cancelText: "Periksa Kembali",
      onConfirm: async () => {
        setDialogLoading(true);

        const result =
          await performVerification();

        setDialogLoading(false);
        setDialogConfirm(null);

        setDialogAlert({
          title: result.success
            ? "Berhasil"
            : "Gagal",
          message: result.message,
          variant: result.success
            ? "success"
            : "danger",
        });
      },
    });
  };

  const openEditInventoryCodes = (rc: ReceiptData) => {
    setEditingReceipt(rc);
    const initial: Record<string, { inventory_code: string; unit: string }> = {};
    rc.items.forEach((it) => {
      initial[it.id] = {
        inventory_code: it.inventoryCode || "",
        unit: it.unit || "",
      };
    });
    setEditItemCodes(initial);
  };

  const handleSaveInventoryCodes = async () => {
    if (!editingReceipt) return;

    // validate all items have a code
    const allFilled = editingReceipt.items.every((it) => {
      const code = normalizeInventoryCode(editItemCodes[it.id]?.inventory_code ?? "");
      return /^10103\d{5}$/.test(code);
    });
    if (!allFilled) {
      setDialogAlert({
        title: "Validasi Gagal",
        message: "Semua barang harus memiliki kode persediaan kategori 1.01.03 yang valid.",
        variant: "warning",
      });
      return;
    }

    setIsSavingEdit(true);
    try {
      const payload = {
        items: editingReceipt.items.map((it) => ({
          id: Number(it.id),
          inventory_code: normalizeInventoryCode(editItemCodes[it.id]?.inventory_code ?? ""),
          unit: (editItemCodes[it.id]?.unit ?? it.unit ?? "").trim(),
        })),
      };

      const response = await apiFetch(`/api/receipts/${editingReceipt.id}/items`, {
        method: "PUT",
        body: JSON.stringify(payload),
      });

      if (!response.ok) throw new Error(await readApiError(response));

      const json = await response.json();
      // update receipts in parent
      const updatedReceipt: ReceiptData = {
        ...editingReceipt,
        items: (json.data?.items ?? editingReceipt.items).map((it: any) => ({
          id: String(it.id),
          name: String(it.name ?? ""),
          qty: Number(it.qty ?? 0),
          unit: String(it.unit ?? ""),
          inventoryCode: String(it.inventory_code ?? "").replace(/\D/g, ""),
          inventoryCodeDescription: it.inventory_code_master?.nama_barang ?? null,
          stockItemId:
            normalizeStockItemId(
              it.stock_item_id
            ),
          codeConfidence: null,
          price: Number(it.price ?? 0),
          subtotal: Number(it.subtotal ?? 0),
        })),
      };
      if (onVerifyReceipt) {
        await Promise.resolve(
          onVerifyReceipt(
            editingReceipt.id,
            updatedReceipt,
            `Kode persediaan kuitansi ${editingReceipt.invoiceNo} dari ${editingReceipt.storeName} diperbarui.`
          )
        );
      }

      await loadStockMasterItems();
      setEditingReceipt(null);
      setDialogAlert({ title: "Berhasil", message: "Kode persediaan berhasil diperbarui.", variant: "success" });
    } catch (error: any) {
      setDialogAlert({ title: "Gagal", message: "Gagal memperbarui: " + (error?.message ?? "Kesalahan tidak diketahui"), variant: "danger" });
    } finally {
      setIsSavingEdit(false);
    }
  };

  const handleUnverify = async (id: string, invoiceNo: string, storeName: string) => {
    setDialogConfirm({
      title: "Batalkan Validasi",
      message: "Apakah Anda yakin ingin membatalkan validasi dokumen ini? Dokumen akan kembali ke status draft/menunggu verifikasi.",
      variant: "danger",
      onConfirm: async () => {
        setDialogLoading(true);
        try {
          const response = await apiFetch(`/api/receipts/${id}/unverify`, {
            method: "PUT",
          });

          if (!response.ok) throw new Error(await readApiError(response));
          
          const logMsg = `Pembatalan Verifikasi: Petugas membatalkan kuitansi nomor ${invoiceNo || "tanpa nomor"} dari ${storeName}.`;
          if (onUnverifyReceipt) {
            await Promise.resolve(
              onUnverifyReceipt(
                id,
                logMsg
              )
            );
          }

          await loadStockMasterItems();
          await loadPendingDocuments();
          setDialogConfirm(null);
          setDialogLoading(false);
          setDialogAlert({ title: "Berhasil", message: "Validasi kuitansi berhasil dibatalkan.", variant: "success" });
        } catch (error: any) {
          setDialogConfirm(null);
          setDialogLoading(false);
          console.error(error);
          setDialogAlert({ title: "Gagal", message: "Gagal membatalkan verifikasi:\n" + (error?.message || "Kesalahan tidak diketahui"), variant: "danger" });
        }
      },
    });
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
  // ── Ekspor Excel ──────────────────────────────────────────────────────────
  const exportToExcel = async (
    data: ReceiptData[]
  ) => {
    if (exportingExcelKey !== null) {
      return;
    }

    const receiptIds = data
      .map(
        (receipt) =>
          Number(receipt.id)
      )
      .filter(
        (id) =>
          Number.isInteger(id)
          && id > 0
      );

    if (receiptIds.length === 0) {
      setDialogAlert({
        title: "Tidak Dapat Mengekspor",
        message:
          "Tidak ada kuitansi valid "
          + "yang dapat diekspor.",
        variant: "warning",
      });

      return;
    }

    const exportKey = data.length === 1
      ? `receipt:${data[0].id}`
      : "all";

    setExportingExcelKey(exportKey);

    try {
      const response = await apiFetch(

        "/api/receipts/export-excel",
        {
          method: "POST",

          headers: {
            Accept:
              "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
          },

          body: JSON.stringify({
            receipt_ids: receiptIds,
          }),
        }
      );

      if (!response.ok) {
        throw new Error(
          await readApiError(response)
        );
      }

      const blob =
        await response.blob();

      const disposition =
        response.headers.get(
          "Content-Disposition"
        ) ?? "";

      /*
       * Laravel dapat mengirim filename biasa
       * atau format UTF-8 filename*.
       */
      const utf8FileName =
        disposition.match(
          /filename\*=UTF-8''([^;]+)/i
        );

      const normalFileName =
        disposition.match(
          /filename="?([^";]+)"?/i
        );

      let fileName =
        data.length === 1
          ? `${
              data[0].storeName
              || "Kuitansi"
            }.xlsx`
          : "Belanja Persediaan.xlsx";

      if (utf8FileName?.[1]) {
        fileName = decodeURIComponent(
          utf8FileName[1]
            .trim()
            .replace(
              /^['"]|['"]$/g,
              ""
            )
        );
      } else if (
        normalFileName?.[1]
      ) {
        fileName =
          normalFileName[1].trim();
      }

      const url =
        URL.createObjectURL(blob);

      const anchor =
        document.createElement("a");

      anchor.href = url;
      anchor.download = fileName;

      document.body.appendChild(
        anchor
      );

      anchor.click();

      document.body.removeChild(
        anchor
      );

      URL.revokeObjectURL(url);
    } catch (error: any) {
      console.error(
        "Gagal mengekspor kuitansi ke Excel:",
        error
      );

      setDialogAlert({
        title: "Gagal Ekspor Excel",

        message:
          error?.message
          || "Terjadi kesalahan saat "
          + "membuat file Excel.",

        variant: "danger",
      });
    } finally {
      setExportingExcelKey(null);
    }
  };

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
        <div className="flex items-center gap-4">
          <div className="flex size-14 shrink-0 items-center justify-center rounded-xl border bg-indigo-50 text-indigo-600 border-indigo-100">
            <FileText size={24} />
          </div>
          <div>
            <h2 className="text-lg font-semibold leading-7 text-slate-900">Pembacaan Kuitansi Otomatis (OCR)</h2>
            <p className="text-sm font-normal leading-5 text-slate-500 mt-0.5">
              Unggah struk belanja, baca otomatis dengan AI, verifikasi manual, sesuaikan pajak toko
            </p>
          </div>
        </div>

        {/* View Tabs */}
        <div className="flex bg-slate-50 border border-slate-200 rounded-md overflow-hidden self-start md:self-auto">
          <button
            onClick={() => setActiveTab("pending")}
            className={`px-6 py-2.5 text-xs font-bold transition-all border-b-2 ${
              activeTab === "pending"
                ? "bg-white text-blue-600 border-blue-600 shadow-sm"
                : "text-slate-500 hover:text-slate-700 border-transparent hover:bg-slate-100"
            }`}
          >
            Menunggu Verifikasi ({pendingDocuments.length})
          </button>
          <button
            onClick={() => setActiveTab("valid")}
            className={`px-6 py-2.5 text-xs font-bold transition-all border-b-2 ${
              activeTab === "valid"
                ? "bg-white text-blue-600 border-blue-600 shadow-sm"
                : "text-slate-500 hover:text-slate-700 border-transparent hover:bg-slate-100"
            }`}
          >
            Kuitansi Valid ({receipts.filter((r) => r.isVerified).length})
          </button>
        </div>
      </div>

      {/* File Drag Drop fallback */}
      <label className="block border-2 border-dashed border-indigo-200 bg-indigo-50/20 rounded-xl py-12 px-6 text-center hover:bg-indigo-50/50 cursor-pointer mb-8 transition-all">
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
        <div className="mx-auto w-12 h-12 bg-white rounded-full border border-indigo-100 flex items-center justify-center shadow-xs mb-3">
          <UploadCloud size={24} className="text-blue-500" strokeWidth={2} />
        </div>
        <h4 className="text-base font-extrabold text-slate-800 mb-1">Unggah kuitansi atau foto struk</h4>
        <p className="text-xs text-slate-500 mb-5">Dukung format JPG, PNG, PDF. Sistem akan membaca data kuitansi secara otomatis.</p>
        <div className="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-lg font-bold text-xs hover:bg-blue-700 shadow-sm transition-colors">
          <UploadCloud size={14} />
          <span>Pilih File</span>
        </div>
        <p className="text-xs text-slate-400 mt-3 font-medium">atau seret dan lepas file di sini</p>
      </label>

      {/* OCR Scanner Loading Animation */}
      {isScanning && (
        <div className="flex flex-col items-center py-10 bg-slate-50 rounded border border-slate-200 mb-6">
          <RefreshCw className="animate-spin text-indigo-600 mb-3" size={24} />
          <h3 className="text-xs font-bold text-slate-800">
            {ocrStatus === "uploading" ? "Mengunggah Dokumen..." : "Mesin OCR Sedang Membaca Dokumen..."}
          </h3>
          <p className="text-xs text-slate-400 max-w-sm text-center mt-1 leading-relaxed">
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
                <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Dokumen Asli:</span>
                {selectedMimeType === "application/pdf" ? (
                  <iframe
                    key={`pdf-${activeDocumentId}-${selectedImage}`}
                    src={selectedImage}
                    className="w-full h-64 border border-slate-200 rounded"
                    title="PDF Preview"
                  />
                ) : (
                  <img
                    key={`image-${activeDocumentId}-${selectedImage}`}
                    src={selectedImage}
                    alt="Struk"
                    className="w-full max-h-48 object-contain rounded border border-slate-200 shadow-sm"
                  />
                )}
              </div>
            )}
            
            <div className="bg-white border border-slate-200 rounded p-4 shadow-xs font-mono text-xs text-slate-700 space-y-4">
              <div className="text-center border-b border-dashed border-slate-200 pb-4">
                <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">{storeName || "NAMA TOKO"}</h4>
                <p className="text-xs text-slate-400 mt-0.5">Makassar, Sulawesi Selatan</p>
              </div>

              <div className="space-y-1">
                {ocrWarnings.length > 0 && (
                  <div className="bg-amber-50 border border-amber-200 rounded p-2 mb-3">
                    <div className="flex items-center gap-1.5 mb-1 text-amber-800">
                      <AlertTriangle size={12} />
                      <span className="text-xs font-bold uppercase">Peringatan OCR</span>
                    </div>
                    <ul className="list-disc pl-4 text-2xs text-amber-700 space-y-0.5">
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
                      <p className="text-xs text-slate-450">
                        {it.qty} {it.unit || "(satuan?)"} x {formatIDR(it.price)}
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

              <div className="text-center bg-indigo-50 text-indigo-700 p-2 rounded border border-indigo-150 font-sans text-xs font-bold uppercase tracking-wider">
                DRAFT PEMBACAAN OCR
              </div>
            </div>

            {rawText && (
              <div className="mt-4 p-3 bg-slate-800 text-emerald-400 font-mono text-2xs rounded whitespace-pre-wrap max-h-60 overflow-auto">
                <div className="text-slate-400 mb-2 uppercase tracking-wider font-bold">DEBUG: Raw OCR Text</div>
                {rawText}
              </div>
            )}
          </div>

          {/* RIGHT: Manual Override Form (Double Check) */}
          <div className="xl:col-span-7 bg-white border border-slate-200 rounded p-5 shadow-xs space-y-5">
            <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
              <div className="flex size-14 shrink-0 items-center justify-center rounded-xl border bg-indigo-50 text-indigo-600 border-indigo-100">
                <Calculator size={24} />
              </div>
              <h3 className="text-xs font-bold text-slate-700 uppercase tracking-wider">Workspace Verifikasi Manual & Penyesuaian Pajak</h3>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {/* Nama Toko */}
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
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
                <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
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
                <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
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
                <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
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
                <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Item Barang Belanja</span>
                <button
                  type="button"
                  onClick={handleAddItem}
                  className="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 bg-indigo-50 px-2.5 py-1 rounded border border-indigo-150"
                >
                  <Plus size={11} />
                  Tambah Barang
                </button>
              </div>

              <p className="mb-2 text-xs leading-relaxed text-slate-500">
                Kode dibatasi pada kategori resmi
                <strong className="mx-1 text-slate-700">
                  1.01.03 - Alat/Bahan untuk Kegiatan Kantor
                </strong>
                dan tetap wajib dikonfirmasi petugas.
                Pilih nama dari master barang untuk menambah stok barang
                yang sudah ada, atau ketik nama sendiri untuk membuat
                barang master baru saat kuitansi diverifikasi.
              </p>

              <div className="overflow-auto border border-slate-200 rounded max-h-[290px]">
                <table className="min-w-[1260px] w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-slate-50 text-slate-600 text-2xs font-bold uppercase tracking-wider border-b border-slate-200">
                      <th className="px-3 py-2 min-w-[300px]">Nama Barang</th>
                      <th className="px-3 py-2 min-w-[330px]">Kode Persediaan</th>
                      <th className="px-3 py-2 w-24 text-center">Jumlah</th>
                      <th className="px-3 py-2 min-w-[120px]">Satuan</th>
                      <th className="px-3 py-2 min-w-[150px]">Harga Satuan (Rp)</th>
                      <th className="px-3 py-2 min-w-[130px] text-right">Subtotal</th>
                      <th className="px-3 py-2 w-10 text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {items.map((it) => (
                      <tr key={it.id} className="hover:bg-slate-50/50 transition-colors">
                        <td className="px-3 py-1.5 align-top">
                          <div className="relative">
                            <input
                              type="text"
                              value={it.name}
                              onFocus={() =>
                                setOpenStockDropdownId(
                                  it.id
                                )
                              }
                              onBlur={() => {
                                window.setTimeout(
                                  () => {
                                    setOpenStockDropdownId(
                                      (current) =>
                                        current === it.id
                                          ? null
                                          : current
                                    );
                                  },
                                  150
                                );
                              }}
                              onChange={(event) =>
                                handleItemNameChange(
                                  it.id,
                                  event.target.value
                                )
                              }
                              placeholder={
                                stockMasterLoading
                                  ? "Memuat master barang..."
                                  : "Pilih atau ketik nama barang..."
                              }
                              autoComplete="off"
                              className="w-full bg-white border border-slate-200 rounded px-2 py-1 text-xs text-slate-800 font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />

                            {openStockDropdownId === it.id && (
                              <div className="absolute left-0 right-0 top-full z-40 mt-1 max-h-56 overflow-y-auto rounded-md border border-slate-200 bg-white shadow-xl">
                                {stockMasterLoading ? (
                                  <div className="flex items-center gap-2 px-3 py-2 text-xs text-slate-500">
                                    <RefreshCw
                                      size={12}
                                      className="animate-spin"
                                    />
                                    Memuat master barang...
                                  </div>
                                ) : getStockMasterMatches(
                                    it.name
                                  ).length > 0 ? (
                                  getStockMasterMatches(
                                    it.name
                                  ).map(
                                    (stockItem) => (
                                      <button
                                        key={stockItem.id}
                                        type="button"
                                        onMouseDown={(
                                          event
                                        ) =>
                                          event.preventDefault()
                                        }
                                        onClick={() =>
                                          handleSelectStockMaster(
                                            it.id,
                                            stockItem
                                          )
                                        }
                                        className="block w-full border-b border-slate-100 px-3 py-2 text-left last:border-b-0 hover:bg-indigo-50"
                                      >
                                        <span className="block text-xs font-bold text-slate-800">
                                          {stockItem.name}
                                        </span>
                                        <span className="mt-0.5 block text-2xs text-slate-500">
                                          {stockItem.code}
                                          {" • "}
                                          {stockItem.unit}
                                          {" • Stok "}
                                          {stockItem.qty}
                                          {stockItem.category
                                            ? ` • ${stockItem.category}`
                                            : ""}
                                        </span>
                                      </button>
                                    )
                                  )
                                ) : (
                                  <div className="px-3 py-2 text-xs text-amber-700">
                                    Tidak ada kecocokan. Nama ini akan
                                    dibuat sebagai barang baru setelah
                                    verifikasi.
                                  </div>
                                )}
                              </div>
                            )}
                          </div>

                          {(() => {
                            const selectedMaster =
                              getSelectedStockMaster(
                                it
                              );

                            return selectedMaster ? (
                              <p className="mt-1 text-2xs font-semibold text-emerald-700">
                                Terhubung ke master: {selectedMaster.code}
                                {" • Stok "}
                                {selectedMaster.qty}
                                {" "}
                                {selectedMaster.unit}
                              </p>
                            ) : it.name.trim() ? (
                              <p className="mt-1 text-2xs font-semibold text-amber-700">
                                Barang baru — akan dibuat di master
                                saat verifikasi.
                              </p>
                            ) : null;
                          })()}
                        </td>
                        <td className="px-3 py-1.5">
                          <select
                            value={it.inventoryCode}
                            onChange={(e) =>
                              handleInventoryCodeChange(
                                it.id,
                                e.target.value
                              )
                            }
                            title={
                              it.inventoryCodeDescription
                              ?? "Pilih kode persediaan resmi kategori 1.01.03"
                            }
                            className={`w-full bg-white border rounded px-2 py-1 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 ${
                              it.inventoryCode
                                ? "border-slate-200"
                                : "border-amber-300 bg-amber-50"
                            }`}
                          >
                            <option value="">
                              {inventoryCodesLoading
                                ? "Memuat kode..."
                                : "Pilih kode 1.01.03"}
                            </option>
                            {inventoryCodes.map(
                              (option) => (
                                <option
                                  key={option.code}
                                  value={option.code}
                                >
                                  {option.formatted_code}
                                  {" - "}
                                  {option.description}
                                </option>
                              )
                            )}
                          </select>
                          {it.inventoryCodeDescription && (
                            <p className="mt-1 max-w-[310px] truncate text-2xs text-slate-500">
                              {it.inventoryCodeDescription}
                            </p>
                          )}
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
                          <select
                            value={it.unit}
                            onChange={(e) =>
                              handleUnitChange(
                                it.id,
                                e.target.value
                              )
                            }
                            className={`w-full bg-white border rounded px-2 py-1 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 ${
                              it.unit
                                ? "border-slate-200"
                                : "border-amber-300 bg-amber-50"
                            }`}
                          >
                            <option value="">
                              Pilih satuan
                            </option>
                            {RECEIPT_UNIT_OPTIONS.map(
                              (unit) => (
                                <option
                                  key={unit}
                                  value={unit}
                                >
                                  {unit}
                                </option>
                              )
                            )}
                          </select>
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
                            aria-label={`Hapus ${it.name || "barang"}`}
                            title="Hapus barang"
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

      {/* Verified / Historical Receipts Table List */}
      <div className="mt-4 mb-1 flex items-center justify-between gap-3">
        <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-wider">
          {activeTab === "pending"
            ? "DAFTAR DOKUMEN MASUK MENUNGGU VERIFIKASI"
            : "DAFTAR KUITANSI VALID (TERVERIFIKASI)"}
        </h3>
        {activeTab === "valid" && receipts.filter((r) => r.isVerified).length > 0 && (
          <button
            onClick={() =>
              exportToExcel(
                receipts.filter(
                  (receipt) => receipt.isVerified
                )
              )
            }
            disabled={exportingExcelKey !== null}
            className="group flex-shrink-0 flex items-center gap-2 px-4 py-1.5 rounded-lg text-xs font-bold bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white shadow hover:shadow-md transition-all duration-200 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
            title="Ekspor seluruh kuitansi valid ke Excel (.xlsx)"
          >
            {exportingExcelKey === "all" ? (
              <RefreshCw
                size={13}
                className="animate-spin"
              />
            ) : (
              <TableProperties
                size={13}
                className="group-hover:scale-110 transition-transform duration-150"
              />
            )}
            {exportingExcelKey === "all"
              ? "Menyiapkan Excel..."
              : "Ekspor Excel"}
            {exportingExcelKey !== "all" && (
              <Download
                size={12}
                className="opacity-80"
              />
            )}
          </button>
        )}
      </div>

        <div className="overflow-x-auto border border-slate-200 rounded">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50 text-slate-600 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                <th className="px-5 py-3">No Nota / Invoice</th>
                <th className="px-5 py-3">Nama Toko</th>
                <th className="px-5 py-3">Tanggal Belanja</th>
                <th className="px-5 py-3">Metode Pengadaan</th>
                <th className="px-5 py-3 text-center">Tarif Pajak PPN</th>
                <th className="px-5 py-3 text-right">Total Nilai</th>
                <th className="px-5 py-3 text-center">Status Verifikasi</th>
                <th className="px-5 py-3 text-center">Aksi</th>
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
                        <span className="inline-flex items-center gap-1.5 text-xs px-2.5 py-0.5 rounded-md border font-semibold bg-amber-50 text-amber-700 border-amber-200 cursor-default select-none">
                          <span className="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block flex-shrink-0"></span>
                          Menunggu Verifikasi
                        </span>
                      </td>
                      <td className="px-5 py-3 text-center font-sans">
                        <div className="flex items-center justify-center gap-2 whitespace-nowrap">
                          <button
                            onClick={() => {
                              if (
                                isScanning
                                || isSavingDraft
                                || isVerifying
                              ) {
                                return;
                              }

                              const requestToken =
                                beginDocumentRequest();

                              setIsScanning(true);
                              setOcrStatus("processing");

                              void pollDocumentStatus(
                                doc.id,
                                Date.now(),
                                requestToken
                              );
                            }}
                            className="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-xs font-bold uppercase transition-colors flex items-center gap-1"
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
                    <td colSpan={8} className="py-12">
                      <div className="flex flex-col items-center justify-center text-center">
                        <FolderOpen size={40} className="text-slate-300 mb-3" strokeWidth={1} />
                        <h4 className="text-sm font-extrabold text-slate-800 mb-1">Belum ada dokumen menunggu verifikasi</h4>
                        <p className="text-xs text-slate-500">Unggah kuitansi atau foto struk untuk memulai proses OCR dan verifikasi.</p>
                      </div>
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
                      <td className="px-5 py-3 text-center font-sans align-middle">
                        <span className="inline-flex items-center gap-1.5 text-xs px-2.5 py-0.5 rounded-md border font-semibold bg-emerald-50 text-emerald-700 border-emerald-200 cursor-default select-none">
                          <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block flex-shrink-0"></span>
                          Dokumen Valid
                        </span>
                      </td>
                      <td className="px-5 py-3 text-center font-sans align-middle">
                        <div className="flex items-center justify-center gap-2 whitespace-nowrap">
                          <button
                            onClick={() => openEditInventoryCodes(rc)}
                            className="inline-flex items-center gap-1 text-2xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2 py-0.5 rounded border border-indigo-100 transition-colors"
                            title="Lihat Detail Kode Persediaan"
                          >
                            <Pencil size={9} />
                            Lihat Detail
                          </button>
                          <button
                            onClick={() => handleUnverify(rc.id, rc.invoiceNo, rc.storeName)}
                            className="text-2xs font-semibold text-rose-600 bg-white hover:bg-rose-50 px-2.5 py-1 rounded-md border border-rose-300 transition-colors cursor-pointer"
                          >
                            Batalkan
                          </button>
                          <button
                            onClick={() => exportToExcel([rc])}
                            disabled={exportingExcelKey !== null}
                            className="group inline-flex items-center gap-1 text-2xs font-bold text-emerald-700 hover:text-white bg-emerald-50 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-600 border border-emerald-200 hover:border-emerald-500 px-2 py-0.5 rounded transition-all duration-200 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
                            title="Ekspor kuitansi ini ke Excel"
                          >
                            {exportingExcelKey === `receipt:${rc.id}` ? (
                              <RefreshCw
                                size={10}
                                className="animate-spin flex-shrink-0"
                              />
                            ) : (
                              <Download
                                size={10}
                                className="flex-shrink-0"
                              />
                            )}
                            {exportingExcelKey === `receipt:${rc.id}`
                              ? "Menyiapkan..."
                              : "Ekspor Excel"}
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={8} className="py-12">
                      <div className="flex flex-col items-center justify-center text-center">
                        <CheckCircle size={40} className="text-slate-300 mb-3" strokeWidth={1} />
                        <h4 className="text-sm font-extrabold text-slate-800 mb-1">Belum ada kuitansi valid</h4>
                        <p className="text-xs text-slate-500">Verifikasi dokumen yang masuk untuk menyimpannya di sini.</p>
                      </div>
                    </td>
                  </tr>
                )
              )}
            </tbody>
          </table>
        </div>
      {/* Modal Edit Kode Persediaan -> Detail Kuitansi */}
      {editingReceipt && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 md:p-6 overflow-y-auto">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-4xl flex flex-col my-auto border border-slate-200 overflow-hidden">
            {/* Header */}
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                  <Receipt size={20} />
                </div>
                <div>
                  <h2 className="text-base font-extrabold text-slate-800">
                    Detail Dokumen Valid
                  </h2>
                  <p className="text-xs text-slate-500 mt-0.5 font-medium">Rincian lengkap dari dokumen yang telah diverifikasi</p>
                </div>
              </div>
              <button 
                onClick={() => setEditingReceipt(null)} 
                className="p-2 rounded-xl text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-colors"
              >
                <X size={18} />
              </button>
            </div>
            
            {/* Body */}
            <div className="overflow-y-auto max-h-[70vh] p-6 bg-slate-50/50">
              
              {/* Receipt Summary Grid */}
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
                  <div className="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">No Nota / Invoice</div>
                  <div className="text-sm font-semibold text-slate-800 break-all">{editingReceipt.invoiceNo || "-"}</div>
                </div>
                <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
                  <div className="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Toko</div>
                  <div className="text-sm font-semibold text-slate-800 break-words">{editingReceipt.storeName || "-"}</div>
                </div>
                <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
                  <div className="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Belanja</div>
                  <div className="text-sm font-semibold text-slate-800">{editingReceipt.date || "-"}</div>
                </div>
                <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
                  <div className="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Metode Pengadaan</div>
                  <div className="text-sm font-semibold text-slate-800">{editingReceipt.method || "-"}</div>
                </div>
              </div>
              
              {/* Items Table */}
              <div className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-6">
                <div className="px-5 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center gap-2">
                  <TableProperties size={14} className="text-slate-500" />
                  <h3 className="text-xs font-bold text-slate-700">Daftar Item & Kode Persediaan</h3>
                </div>
                <div className="overflow-x-auto">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="bg-slate-50/50 text-slate-500 text-2xs font-bold uppercase tracking-wider border-b border-slate-100">
                        <th className="px-4 py-3 w-10 text-center">No</th>
                        <th className="px-4 py-3">Nama Item</th>
                        <th className="px-4 py-3 text-center">Satuan</th>
                        <th className="px-4 py-3 min-w-[200px]">Kode Persediaan</th>
                        <th className="px-4 py-3 text-right">Harga Satuan</th>
                        <th className="px-4 py-3 text-right">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {editingReceipt.items.map((it, idx) => {
                        const normalCode = normalizeInventoryCode(it.inventoryCode ?? "");
                        const isValid = /^10103\d{5}$/.test(normalCode);
                        const selectedOption = inventoryCodes.find(o => o.code === normalCode);
                        
                        return (
                          <tr key={it.id} className="hover:bg-slate-50/30 transition-colors">
                            <td className="px-4 py-3 text-xs font-medium text-slate-400 text-center">{idx + 1}</td>
                            <td className="px-4 py-3 text-xs font-bold text-slate-700">{it.name}</td>
                            <td className="px-4 py-3 text-xs text-slate-600 text-center font-medium">{it.unit || "-"}</td>
                            <td className="px-4 py-3">
                              <div className="flex flex-col gap-0.5">
                                <span className={`text-xs font-mono font-bold ${isValid ? 'text-indigo-600' : 'text-rose-500'}`}>
                                  {normalCode || "Belum diatur"}
                                </span>
                                {selectedOption && (
                                  <span className="text-2xs text-emerald-600 font-medium line-clamp-1">{selectedOption.description}</span>
                                )}
                              </div>
                            </td>
                            <td className="px-4 py-3 text-xs font-medium text-slate-600 text-right">{formatIDR(it.price)}</td>
                            <td className="px-4 py-3 text-xs font-bold text-slate-800 text-right">{formatIDR(it.qty * it.price)}</td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
              
              {/* Totals Section */}
              <div className="flex justify-end">
                <div className="w-full md:w-1/3 bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                  <div className="p-4 space-y-3">
                    <div className="flex justify-between items-center text-xs">
                      <span className="font-semibold text-slate-500">Subtotal</span>
                      <span className="font-bold text-slate-700">{formatIDR(editingReceipt.subtotal)}</span>
                    </div>
                    <div className="flex justify-between items-center text-xs">
                      <span className="font-semibold text-slate-500">
                        Pajak PPN {editingReceipt.isTaxed ? `(${editingReceipt.taxRate}%)` : "(0%)"}
                      </span>
                      <span className="font-bold text-slate-700">{formatIDR(editingReceipt.taxAmount)}</span>
                    </div>
                    <div className="pt-3 border-t border-slate-100 flex justify-between items-center">
                      <span className="text-sm font-extrabold text-slate-800">Total Akhir</span>
                      <span className="text-sm font-extrabold text-indigo-700">{formatIDR(editingReceipt.total)}</span>
                    </div>
                  </div>
                </div>
              </div>
              
            </div>
            
            {/* Footer */}
            <div className="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-white">
              <button
                onClick={() => setEditingReceipt(null)}
                className="px-6 py-2.5 text-xs font-bold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all active:scale-95 flex items-center gap-2"
              >
                Tutup Detail
              </button>
            </div>
          </div>
        </div>
      )}
      {exportingExcelKey && (
        <div className="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm">
          <div className="flex w-full max-w-sm flex-col items-center rounded-2xl border border-slate-200 bg-white px-7 py-8 text-center shadow-2xl">
            <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
              <RefreshCw
                size={25}
                className="animate-spin"
              />
            </div>
            <h3 className="text-base font-extrabold text-slate-900">
              Menyiapkan File Excel
            </h3>
            <p className="mt-2 text-sm leading-relaxed text-slate-500">
              {exportingExcelKey === "all"
                ? "Sistem sedang menggabungkan seluruh kuitansi valid ke dalam workbook Belanja Persediaan."
                : "Sistem sedang menyusun data kuitansi ke dalam format Belanja Persediaan."}
            </p>
            <p className="mt-3 text-xs font-semibold text-emerald-700">
              Mohon tunggu dan jangan menutup halaman.
            </p>
          </div>
        </div>
      )}
      {dialogConfirm && (
        <ConfirmDialog
          open
          title={dialogConfirm.title}
          message={dialogConfirm.message}
          variant={dialogConfirm.variant || "warning"}
          confirmText={
            dialogLoading
              ? "Memproses..."
              : (
                  dialogConfirm.confirmText
                  ?? "Konfirmasi"
                )
          }
          cancelText={
            dialogConfirm.cancelText
            ?? "Batal"
          }
          loading={dialogLoading}
          onConfirm={async () => {
            if (dialogConfirm.onConfirm) await dialogConfirm.onConfirm();
          }}
          onClose={() => { if (!dialogLoading) { setDialogConfirm(null); } }}
        />
      )}
      {dialogAlert && (
        <ConfirmDialog
          open
          title={dialogAlert.title}
          message={dialogAlert.message}
          variant={dialogAlert.variant || "info"}
          confirmText="OK"
          showCancel={false}
          onConfirm={() => setDialogAlert(null)}
          onClose={() => setDialogAlert(null)}
        />
      )}
    </div>
  );
};
