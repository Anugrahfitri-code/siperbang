import os

def patch_frontend():
    path = r"d:\Project\siperbang\resources\js\components\ReceiptOCRProcessor.tsx"
    with open(path, "r", encoding="utf-8") as f:
        content = f.read()

    # Find the newDraft construction
    start_newDraft = content.find("        const newDraft: ReceiptData = {")
    end_newDraft = content.find("        setActiveDraft(newDraft);", start_newDraft)
    
    # We also need to extract `m = data.manual_draft` before newDraft
    new_code = """        const m = data.manual_draft;
        
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

"""
    if start_newDraft != -1 and end_newDraft != -1:
        content = content[:start_newDraft] + new_code + content[end_newDraft:]
    else:
        print("Could not find newDraft block")

    with open(path, "w", encoding="utf-8") as f:
        f.write(content)
    print("Frontend drafted code patched.")

if __name__ == "__main__":
    patch_frontend()
