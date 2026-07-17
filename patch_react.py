import os

def patch():
    path = r"d:\Project\siperbang\resources\js\components\ReceiptOCRProcessor.tsx"
    with open(path, "r", encoding="utf-8") as f:
        content = f.read()

    # 1. Replace handleVerifySave
    start = content.find("  const handleVerifySave = async () => {")
    end = content.find("  const formatIDR =", start)

    new_fns = """  const buildManualPayload = () => ({
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

      const finalReceipt = {
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
        method: (receipt.method) || method,
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
    if start != -1 and end != -1:
        content = content[:start] + new_fns + content[end:]
    else:
        print("Failed to find handleVerifySave block!")

    # 2. Replace buttons
    btn_start = content.find("            {/* Form Actions */}")
    btn_end = content.find("          </div>\n        </div>\n      )}", btn_start)

    new_buttons = """            {/* Form Actions */}
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
"""
    
    if btn_start != -1 and btn_end != -1:
        content = content[:btn_start] + new_buttons + content[btn_end:]
    else:
        print("Failed to find action buttons block!")

    # 3. Use new document lists in "Menunggu Verifikasi" table
    # Specifically: The user's list has pendingDocuments from API now!
    # Wait, the instruction said to map `pendingDocuments` for pending? No, the instruction was:
    # "Daftar “Menunggu Verifikasi” mengambil data dari tabel `receipts`, padahal dokumen yang belum diverifikasi berada di `receipt_documents`."
    # Let's replace the receipts.filter logic.
    table_body_start = content.find("<tbody className=\"divide-y divide-slate-100\">")
    table_body_end = content.find("</tbody>", table_body_start)
    
    new_table_body = """<tbody className="divide-y divide-slate-100">
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
                          className="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold uppercase transition-colors"
                        >
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
    if table_body_start != -1 and table_body_end != -1:
        content = content[:table_body_start] + new_table_body + content[table_body_end:]
    else:
        print("Failed to find table body block!")

    with open(path, "w", encoding="utf-8") as f:
        f.write(content)

if __name__ == "__main__":
    patch()
    print("Patch applied successfully.")
