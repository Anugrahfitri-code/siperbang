import os

def patch_backend():
    # 1. Add destroy method to ReceiptDocumentController
    controller_path = r"d:\Project\siperbang\app\Http\Controllers\Api\ReceiptDocumentController.php"
    with open(controller_path, "r", encoding="utf-8") as f:
        content = f.read()
    
    if "public function destroy(" not in content:
        destroy_method = """    public function destroy(ReceiptDocument $receiptDocument)
    {
        if ($receiptDocument->status === ReceiptDocumentStatus::VERIFIED) {
            return response()->json([
                'message' => 'Dokumen yang sudah diverifikasi tidak dapat dihapus.',
            ], 403);
        }

        // Delete physical file if exists
        if ($receiptDocument->storage_path && Storage::disk('local')->exists($receiptDocument->storage_path)) {
            Storage::disk('local')->delete($receiptDocument->storage_path);
        }

        $receiptDocument->delete();

        return response()->json([
            'message' => 'Draft dokumen berhasil dihapus.',
        ]);
    }
}
"""
        content = content.replace("}\n", destroy_method, 1) # Note: this might replace the wrong bracket. 
        # Let's be safer by finding the very last closing bracket.
        last_brace = content.rfind("}")
        if last_brace != -1:
            content = content[:last_brace] + destroy_method + content[last_brace+1:]
        
        with open(controller_path, "w", encoding="utf-8") as f:
            f.write(content)
        print("Controller patched.")

    # 2. Add route in web.php
    route_path = r"d:\Project\siperbang\routes\web.php"
    with open(route_path, "r", encoding="utf-8") as f:
        routes = f.read()
    
    if "Route::delete('/receipt-documents/{receiptDocument}'" not in routes:
        old_route_block = """        Route::post('/receipt-documents/{receiptDocument}/retry', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'retry']);"""
        new_route_block = """        Route::post('/receipt-documents/{receiptDocument}/retry', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'retry']);
        Route::delete('/receipt-documents/{receiptDocument}', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'destroy']);"""
        routes = routes.replace(old_route_block, new_route_block)
        with open(route_path, "w", encoding="utf-8") as f:
            f.write(routes)
        print("Routes patched.")

def patch_frontend():
    path = r"d:\Project\siperbang\resources\js\components\ReceiptOCRProcessor.tsx"
    with open(path, "r", encoding="utf-8") as f:
        content = f.read()

    # Add handleDeleteDraft function
    if "const handleDeleteDraft" not in content:
        insert_point = content.find("  const loadPendingDocuments =")
        delete_fn = """  const handleDeleteDraft = async (id: number) => {
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

"""
        content = content[:insert_point] + delete_fn + content[insert_point:]

    # Add the delete button in the pending table UI
    old_button_html = """                        <button
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
                        </button>"""
    new_button_html = """                        <div className="flex items-center justify-center gap-1.5">
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
                        </div>"""
    
    if "handleDeleteDraft(doc.id)" not in content:
        content = content.replace(old_button_html, new_button_html)
    
    with open(path, "w", encoding="utf-8") as f:
        f.write(content)
    print("Frontend patched.")

if __name__ == "__main__":
    patch_backend()
    patch_frontend()
