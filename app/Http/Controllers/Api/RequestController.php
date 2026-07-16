<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemRequest;
use App\Models\StockItem;
use App\Models\Distribution;
use App\Models\Procurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemRequest::with(['distribution', 'procurements'])->orderBy('created_at', 'desc');
        
        if ($request->user() && ($request->user()->role === 'Ketua Tim' || $request->user()->role === 'Ketua Tim Kerja')) {
            $section = $request->user()->section;
            if ($section === 'Tata Usaha' || $section === 'Subbagian Tata Usaha') {
                $query->whereIn('section', ['Tata Usaha', 'Subbagian Tata Usaha']);
            } else {
                $query->where('section', $section);
            }
        }

        // Exclude drafts unless owned by current user
        if ($request->user()) {
            $query->where(function ($sub) use ($request) {
                $sub->where('status', '!=', 'Draft')
                    ->orWhere('user_id', $request->user()->id);
            });
        }
        
        $requests = $query->get();
        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'keperluan' => 'required|string',
            'catatan' => 'nullable|string',
            'status' => 'required|string|in:draft,menunggu_verifikasi,Draft,Menunggu Verifikasi',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|integer|exists:stock_items,id',
            'items.*.jumlah_diminta' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string',
        ]);

        $statusVal = $validated['status'];
        if ($statusVal === 'draft' || $statusVal === 'Draft') {
            $statusVal = 'Draft';
        } else {
            $statusVal = 'Menunggu Verifikasi';
        }

        DB::beginTransaction();
        try {
            $user      = $request->user();
            $section   = $user->section ?? 'Tata Usaha';
            $requester = $user->name;

            // ── Generate unique bon_no dengan retry ──────────────────────
            // Format: BON/YYYY/MM/DD/NNN  — unik per hari, aman dari race condition
            $bonNo    = null;
            $attempts = 0;
            do {
                $prefix     = 'BON/' . date('Y/m/d/');
                $countToday = \App\Models\BonHeader::where('bon_no', 'like', $prefix . '%')->count();
                $candidate  = $prefix . str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

                // Cek apakah nomor sudah dipakai (handle race condition)
                $exists = \App\Models\BonHeader::where('bon_no', $candidate)->exists();
                if (!$exists) {
                    $bonNo = $candidate;
                }
                $attempts++;
            } while ($bonNo === null && $attempts < 10);

            if ($bonNo === null) {
                throw new \Exception('Gagal membuat nomor BON unik. Coba lagi.');
            }

            // Create BonHeader
            $bonHeader = \App\Models\BonHeader::create([
                'bon_no' => $bonNo,
                'user_id' => $user->id,
                'section' => $section,
                'requester' => $requester,
                'date' => today(),
                'status' => $statusVal,
                'keperluan' => $validated['keperluan'],
                'catatan' => $validated['catatan'] ?? null,
                'last_updated' => today(),
            ]);

            // Create status history
            \App\Models\BonStatusHistory::create([
                'bon_header_id' => $bonHeader->id,
                'status_before' => null,
                'status_after' => $statusVal,
                'changed_by' => $user->name,
                'notes' => $statusVal === 'Draft' ? 'Draft pengajuan dibuat.' : 'Pengajuan dikirim.',
            ]);

            // Create ItemRequests
            foreach ($validated['items'] as $item) {
                $stockItem = \App\Models\StockItem::findOrFail($item['barang_id']);

                ItemRequest::create([
                    'bon_header_id' => $bonHeader->id,
                    'bon_no' => $bonNo,
                    'user_id' => $user->id,
                    'section' => $section,
                    'requester' => $requester,
                    'date' => today(),
                    'status' => $statusVal === 'Draft' ? 'Draft' : 'Diajukan',
                    'stock_item_id' => $stockItem->id,
                    'item_name' => $stockItem->name,
                    'qty_requested' => $item['jumlah_diminta'],
                    'unit' => $stockItem->unit,
                    'notes' => $item['catatan'] ?? null,
                    'qty_available' => 0,
                    'qty_fulfilled' => 0,
                    'qty_to_procure' => 0,
                    'stock_allocated' => false,
                    'last_updated' => today(),
                ]);
            }

            DB::commit();
            return response()->json($bonHeader->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan pengajuan: ' . $e->getMessage()], 422);
        }
    }

    public function updateStatus(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'qtyAvailable' => 'required|integer|min:0',
            'qtyFulfilled' => 'required|integer|min:0',
            'verifier_notes' => 'nullable|string',
            'deductStock' => 'nullable|array',
            'deductStock.code' => [
                'required_with:deductStock',
                'string',
                'exists:stock_items,code',
            ],
            'deductStock.qtyToDeduct' => [
                'required_with:deductStock',
                'integer',
                'min:0',
            ],
        ]);

        DB::beginTransaction();
        try {
            $stockItem = null;
            if (isset($validated['deductStock']) && $validated['deductStock'] !== null) {
                $stockItem = StockItem::where('code', $validated['deductStock']['code'])->first();
                if ($stockItem && !$itemRequest->stock_allocated) {
                    $qtyToDeduct = $validated['deductStock']['qtyToDeduct'];
                    if ($stockItem->qty < $qtyToDeduct) {
                        throw new \Exception("Stok gudang tidak mencukupi untuk pemenuhan ini.");
                    }
                    $stockItem->qty -= $qtyToDeduct;
                    $stockItem->last_updated = today();
                    $stockItem->save();
                    
                    $itemRequest->stock_allocated = true;
                    $itemRequest->stock_item_id = $stockItem->id;
                }
            }

            $qtyToProcure = max(0, $itemRequest->qty_requested - $validated['qtyFulfilled']);
            $oldStatus = $itemRequest->status;

            $itemRequest->update([
                'status' => $validated['status'],
                'qty_available' => $validated['qtyAvailable'],
                'qty_fulfilled' => $validated['qtyFulfilled'],
                'qty_to_procure' => $qtyToProcure,
                'verifier_notes' => $validated['verifier_notes'] ?? null,
                'last_updated' => today(),
            ]);

            // Save status history to BonHeader
            $bonHeader = $itemRequest->bonHeader;
            if ($bonHeader) {
                \App\Models\BonStatusHistory::create([
                    'bon_header_id' => $bonHeader->id,
                    'status_before' => $oldStatus,
                    'status_after' => $validated['status'],
                    'changed_by' => $request->user() ? $request->user()->name : 'Sistem',
                    'notes' => "Barang '{$itemRequest->item_name}' diperbarui ke status '{$validated['status']}'." . 
                               (isset($validated['verifier_notes']) && $validated['verifier_notes'] !== '' ? " Catatan verifikator: {$validated['verifier_notes']}" : ""),
                ]);

                // Update parent BON header status based on items
                $bonHeader->update(['last_updated' => today()]);
                $this->syncBonHeaderStatus($bonHeader);
            }

            DB::commit();

            return response()->json([
                'message' => 'Status pengajuan berhasil diperbarui.',
                'data' => $itemRequest->fresh(['distribution', 'procurements']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function distribute(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'stockItemId' => 'required|exists:stock_items,id',
            'qtyDistributed' => 'required|integer|min:1',
            'distributedBy' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $stockItem = StockItem::findOrFail($validated['stockItemId']);
            
            if (!$itemRequest->stock_allocated) {
                if ($stockItem->qty < $validated['qtyDistributed']) {
                    throw new \Exception("Stok gudang tidak mencukupi untuk distribusi.");
                }
                $stockItem->qty -= $validated['qtyDistributed'];
                $stockItem->last_updated = today();
                $stockItem->save();
                $itemRequest->stock_allocated = true;
            }

            Distribution::create([
                'item_request_id' => $itemRequest->id,
                'stock_item_id' => $stockItem->id,
                'qty_distributed' => $validated['qtyDistributed'],
                'distributed_by' => $validated['distributedBy'],
                'distributed_at' => today(),
                'notes' => $validated['notes'] ?? null
            ]);

            if ($itemRequest->qty_to_procure > 0) {
                $itemRequest->status = 'Terpenuhi Sebagian';
            } else {
                $itemRequest->status = 'Selesai';
            }
            $itemRequest->last_updated = today();
            $itemRequest->save();

            $bonHeader = $itemRequest->bonHeader;
            if ($bonHeader) {
                $bonHeader->update(['last_updated' => today()]);
                $this->syncBonHeaderStatus($bonHeader);
            }

            DB::commit();

            return response()->json($itemRequest->fresh(['distribution', 'procurements']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function procure(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'method' => 'required|string',
            'vendorName' => 'nullable|string',
            'storeName' => 'nullable|string',
            'qtyProcured' => 'required|numeric|min:1',
            'unitPrice' => 'required|numeric|min:0',
            'isTaxed' => 'required|boolean',
            'taxRate' => 'required|numeric|min:0',
            'invoiceNo' => 'nullable|string',
            'bastName' => 'nullable|string',
            'bastDate' => 'nullable|date',
            'contractNo' => 'nullable|string',
            'processedBy' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $totalPrice = $validated['qtyProcured'] * $validated['unitPrice'];
            if ($validated['isTaxed'] && $validated['taxRate'] > 0) {
                $totalPrice += $totalPrice * ($validated['taxRate'] / 100);
            }

            Procurement::create([
                'item_request_id' => $itemRequest->id,
                'method' => $validated['method'],
                'vendor_name' => $validated['vendorName'] ?? null,
                'store_name' => $validated['storeName'] ?? null,
                'qty_procured' => $validated['qtyProcured'],
                'unit_price' => $validated['unitPrice'],
                'total_price' => $totalPrice,
                'is_taxed' => $validated['isTaxed'],
                'tax_rate' => $validated['taxRate'],
                'invoice_no' => $validated['invoiceNo'] ?? null,
                'bast_name' => $validated['bastName'] ?? null,
                'bast_date' => $validated['bastDate'] ?? null,
                'contract_no' => $validated['contractNo'] ?? null,
                'processed_by' => $validated['processedBy'],
                'procurement_date' => today(),
                'status' => 'Diproses'
            ]);

            $itemRequest->status = 'Dalam Pengadaan';
            $itemRequest->procurement_method = $validated['method'];
            $itemRequest->vendor_name = $validated['vendorName'] ?? null;
            $itemRequest->last_updated = today();
            $itemRequest->save();

            $bonHeader = $itemRequest->bonHeader;
            if ($bonHeader) {
                $bonHeader->update(['last_updated' => today()]);
                $this->syncBonHeaderStatus($bonHeader);
            }

            DB::commit();

            return response()->json($itemRequest->fresh(['distribution', 'procurements']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function completeProcurement(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'procurementId' => 'required|exists:procurements,id',
            'processedBy' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $procurement = Procurement::findOrFail($validated['procurementId']);
            if ($procurement->status === 'Diterima') {
                throw new \Exception("Pengadaan ini sudah selesai.");
            }
            
            $procurement->status = 'Diterima';
            $procurement->save();

            $stockItem = null;
            if ($itemRequest->stock_item_id) {
                $stockItem = StockItem::find($itemRequest->stock_item_id);
            } else {
                $stockItem = StockItem::where('name', $itemRequest->item_name)->first();
            }

            if ($stockItem) {
                $stockItem->qty += $procurement->qty_procured;
                $stockItem->last_updated = today();
                $stockItem->save();
                
                $itemRequest->stock_item_id = $stockItem->id;
            }

            $itemRequest->qty_fulfilled += $procurement->qty_procured;
            $itemRequest->qty_to_procure = max(0, $itemRequest->qty_requested - $itemRequest->qty_fulfilled);
            
            if ($itemRequest->qty_fulfilled >= $itemRequest->qty_requested) {
                $itemRequest->status = 'Siap Didistribusikan';
            }
            
            $itemRequest->last_updated = today();
            $itemRequest->save();

            $bonHeader = $itemRequest->bonHeader;
            if ($bonHeader) {
                $bonHeader->update(['last_updated' => today()]);
                $this->syncBonHeaderStatus($bonHeader);
            }

            DB::commit();

            return response()->json($itemRequest->fresh(['distribution', 'procurements']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function indexBons(Request $request)
    {
        $query = \App\Models\BonHeader::with(['items' => function ($q) {
                // Sertakan stock_item_id agar frontend bisa pre-fill barang_id
                $q->select('id', 'bon_header_id', 'stock_item_id', 'item_name',
                            'unit', 'qty_requested', 'qty_fulfilled', 'status', 'notes');
            }])
            ->withCount('items')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->user() && ($request->user()->role === 'Ketua Tim' || $request->user()->role === 'Ketua Tim Kerja')) {
            $section = $request->user()->section;
            if ($section === 'Tata Usaha' || $section === 'Subbagian Tata Usaha') {
                $query->whereIn('section', ['Tata Usaha', 'Subbagian Tata Usaha']);
            } else {
                $query->where('section', $section);
            }

            $query->where(function ($sub) use ($request) {
                $sub->where('status', '!=', 'Draft')
                    ->orWhere('user_id', $request->user()->id);
            });
        }

        if ($request->filled('bon_no')) {
            $query->where('bon_no', 'like', '%' . $request->input('bon_no') . '%');
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->input('end_date'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('all') === 'true') {
            return response()->json($query->get());
        }

        return response()->json($query->paginate(15));
    }

    public function showBon(Request $request, $id)
    {
        $bon = \App\Models\BonHeader::with(['items', 'statusHistories' => fn($q) => $q->orderBy('created_at', 'asc')])->findOrFail($id);

        if ($request->user() && ($request->user()->role === 'Ketua Tim' || $request->user()->role === 'Ketua Tim Kerja')) {
            $section = $request->user()->section;
            $allowedSections = ($section === 'Tata Usaha' || $section === 'Subbagian Tata Usaha') 
                ? ['Tata Usaha', 'Subbagian Tata Usaha'] 
                : [$section];

            if (!in_array($bon->section, $allowedSections)) {
                abort(403, 'Akses ditolak.');
            }

            if ($bon->status === 'Draft' && $bon->user_id !== $request->user()->id) {
                abort(403, 'Akses ditolak.');
            }
        }

        return response()->json($bon);
    }

    public function updateDraft(Request $request, $id)
    {
        $validated = $request->validate([
            'keperluan' => 'required|string',
            'catatan' => 'nullable|string',
            'status' => 'required|string|in:draft,menunggu_verifikasi,Draft,Menunggu Verifikasi',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|integer|exists:stock_items,id',
            'items.*.jumlah_diminta' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string',
        ]);

        $statusVal = $validated['status'];
        if ($statusVal === 'draft' || $statusVal === 'Draft') {
            $statusVal = 'Draft';
        } else {
            $statusVal = 'Menunggu Verifikasi';
        }

        $bonHeader = \App\Models\BonHeader::findOrFail($id);
        $user = $request->user();

        if ($bonHeader->user_id !== $user->id) {
            abort(403, 'Anda bukan pemilik draft ini.');
        }

        if ($bonHeader->status !== 'Draft') {
            abort(422, 'Pengajuan yang sudah dikirim tidak dapat diedit.');
        }

        DB::beginTransaction();
        try {
            $oldStatus = $bonHeader->status;

            // Update header
            $bonHeader->update([
                'status' => $statusVal,
                'keperluan' => $validated['keperluan'],
                'catatan' => $validated['catatan'] ?? null,
                'last_updated' => today(),
            ]);

            // Save history
            \App\Models\BonStatusHistory::create([
                'bon_header_id' => $bonHeader->id,
                'status_before' => $oldStatus,
                'status_after' => $statusVal,
                'changed_by' => $user->name,
                'notes' => $statusVal === 'Draft' ? 'Draft diperbarui.' : 'Draft dikirim sebagai pengajuan.',
            ]);

            // Rebuild item requests: Delete existing and recreate
            $bonHeader->items()->delete();

            foreach ($validated['items'] as $item) {
                $stockItem = \App\Models\StockItem::findOrFail($item['barang_id']);

                ItemRequest::create([
                    'bon_header_id' => $bonHeader->id,
                    'bon_no' => $bonHeader->bon_no,
                    'user_id' => $user->id,
                    'section' => $bonHeader->section,
                    'requester' => $bonHeader->requester,
                    'date' => $bonHeader->date,
                    'status' => $statusVal === 'Draft' ? 'Draft' : 'Diajukan',
                    'stock_item_id' => $stockItem->id,
                    'item_name' => $stockItem->name,
                    'qty_requested' => $item['jumlah_diminta'],
                    'unit' => $stockItem->unit,
                    'notes' => $item['catatan'] ?? null,
                    'qty_available' => 0,
                    'qty_fulfilled' => 0,
                    'qty_to_procure' => 0,
                    'stock_allocated' => false,
                    'last_updated' => today(),
                ]);
            }

            DB::commit();
            return response()->json($bonHeader->load('items'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui draft: ' . $e->getMessage()], 422);
        }
    }

    public function destroyDraft(Request $request, $id)
    {
        $bonHeader = \App\Models\BonHeader::findOrFail($id);

        if ($bonHeader->user_id !== $request->user()->id) {
            abort(403, 'Anda bukan pemilik draft ini.');
        }

        if ($bonHeader->status !== 'Draft') {
            abort(422, 'Pengajuan yang sudah dikirim tidak dapat dihapus.');
        }

        DB::beginTransaction();
        try {
            $bonHeader->items()->delete();
            $bonHeader->delete();
            DB::commit();
            return response()->json(['message' => 'Draft berhasil dihapus.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus draft: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Batalkan / tolak satu item request.
     *
     * Logika pengembalian stok:
     * - Jika status = Terpenuhi Sebagian:
     *     → Barang yang sudah didistribusikan (qty_fulfilled) TIDAK dikembalikan
     *       karena sudah di tangan penerima.
     *     → Hanya sisa yang belum terpenuhi (qty_to_procure) yang dibatalkan.
     * - Selain itu:
     *     → Jika stok sudah dialokasikan (stock_allocated = true), qty_fulfilled
     *       dikembalikan ke stok gudang.
     */
    public function rejectItem(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'alasan' => 'required|string|min:3|max:500',
        ]);

        if ($itemRequest->status === 'Ditolak') {
            return response()->json(['message' => 'Pengajuan ini sudah dibatalkan sebelumnya.'], 422);
        }

        if ($itemRequest->status === 'Selesai') {
            return response()->json(['message' => 'Pengajuan yang sudah selesai tidak dapat dibatalkan.'], 422);
        }

        DB::beginTransaction();
        try {
            $isTerpenuhinSebagian = $itemRequest->status === 'Terpenuhi Sebagian';

            if ($isTerpenuhinSebagian) {
                // Hanya batalkan porsi yang BELUM terpenuhi.
                // Stok yang sudah didistribusikan (qty_fulfilled) tidak dikembalikan.
                // Tidak ada pengembalian stok ke gudang.
                $notes = "Pembatalan sebagian: {$itemRequest->qty_fulfilled} {$itemRequest->unit} "
                    . "sudah didistribusikan (tidak dikembalikan). "
                    . "Sisa {$itemRequest->qty_to_procure} {$itemRequest->unit} yang belum diadakan dibatalkan. "
                    . "Alasan: {$validated['alasan']}";
            } else {
                // Status lain: kembalikan stok jika sudah dialokasikan
                if ($itemRequest->stock_allocated && $itemRequest->qty_fulfilled > 0) {
                    $stockItem = $itemRequest->stock_item_id
                        ? StockItem::lockForUpdate()->find($itemRequest->stock_item_id)
                        : StockItem::lockForUpdate()
                            ->whereRaw('LOWER(name) = LOWER(?)', [$itemRequest->item_name])
                            ->first();

                    if ($stockItem) {
                        $stockItem->qty         += $itemRequest->qty_fulfilled;
                        $stockItem->last_updated = today();
                        $stockItem->save();
                    }
                }
                $notes = "Alasan: {$validated['alasan']}";
            }

            $oldStatus = $itemRequest->status;

            $itemRequest->update([
                'status'          => 'Ditolak',
                'verifier_notes'  => $validated['alasan'],
                // Terpenuhi Sebagian: pertahankan qty_fulfilled (sudah didistribusikan)
                // Status lain: nol-kan semua
                'qty_fulfilled'   => $isTerpenuhinSebagian ? $itemRequest->qty_fulfilled : 0,
                'qty_to_procure'  => 0,
                'stock_allocated' => $isTerpenuhinSebagian ? $itemRequest->stock_allocated : false,
                'last_updated'    => today(),
            ]);

            // Update parent BON header status
            $bonHeader = $itemRequest->bonHeader;
            if ($bonHeader) {
                \App\Models\BonStatusHistory::create([
                    'bon_header_id' => $bonHeader->id,
                    'status_before' => $oldStatus,
                    'status_after'  => 'Ditolak',
                    'changed_by'    => $request->user()?->name ?? 'Petugas',
                    'notes'         => "Pengajuan '{$itemRequest->item_name}' dibatalkan. {$notes}",
                ]);
                $bonHeader->update(['last_updated' => today()]);
                $this->syncBonHeaderStatus($bonHeader);
            }

            DB::commit();

            return response()->json([
                'message' => $isTerpenuhinSebagian
                    ? 'Sisa pengajuan yang belum terpenuhi berhasil dibatalkan. Barang yang sudah didistribusikan tidak dikembalikan.'
                    : 'Pengajuan berhasil dibatalkan.',
                'data'    => $itemRequest->fresh(['distribution', 'procurements']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function syncBonHeaderStatus($bonHeader)
    {
        if (!$bonHeader) return;

        $items = $bonHeader->items;
        if ($items->isEmpty()) return;

        // If the header is draft, keep it draft unless changed.
        if ($bonHeader->status === 'Draft') return;

        $statuses = $items->pluck('status')->map(fn($s) => strtoupper(trim($s)))->toArray();

        // Check if any is draft
        if (in_array('DRAFT', $statuses)) {
            $bonHeader->update(['status' => 'Draft']);
            return;
        }

        // Check if all are DITOLAK
        $allRejected = true;
        foreach ($statuses as $status) {
            if ($status !== 'DITOLAK') {
                $allRejected = false;
                break;
            }
        }
        if ($allRejected) {
            $bonHeader->update(['status' => 'Ditolak']);
            return;
        }

        // If any is DIAJUKAN or DICEK
        if (in_array('DIAJUKAN', $statuses) || in_array('DICEK', $statuses)) {
            $bonHeader->update(['status' => 'Menunggu Verifikasi']);
            return;
        }

        // If any is in progress (TERPENUHI SEBAGIAN, PERLU PENGADAAN, DALAM PENGADAAN)
        if (in_array('TERPENUHI SEBAGIAN', $statuses) || 
            in_array('PERLU PENGADAAN', $statuses) || 
            in_array('DALAM PENGADAAN', $statuses)) {
            $bonHeader->update(['status' => 'Diproses']);
            return;
        }

        // If all remaining items are either SELESAI, DITOLAK, TERPENUHI, or SIAP DIDISTRIBUSIKAN
        // If any of them is TERPENUHI or SIAP DIDISTRIBUSIKAN, then header is Disetujui
        if (in_array('TERPENUHI', $statuses) || in_array('SIAP DIDISTRIBUSIKAN', $statuses)) {
            $bonHeader->update(['status' => 'Disetujui']);
            return;
        }

        // If all resolved and non-rejected items are SELESAI
        $allSelesaiOrRejected = true;
        foreach ($statuses as $status) {
            if ($status !== 'SELESAI' && $status !== 'DITOLAK') {
                $allSelesaiOrRejected = false;
                break;
            }
        }
        if ($allSelesaiOrRejected) {
            $bonHeader->update(['status' => 'Selesai']);
            return;
        }

        // Fallback
        $bonHeader->update(['status' => 'Diproses']);
    }
}