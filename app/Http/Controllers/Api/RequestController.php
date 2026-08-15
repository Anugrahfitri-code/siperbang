<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SafeBusinessException;
use App\Http\Controllers\Controller;
use App\Models\BonHeader;
use App\Models\BonStatusHistory;
use App\Models\Distribution;
use App\Models\HistoryLog;
use App\Models\ItemRequest;
use App\Models\Procurement;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemRequest::with(['distribution', 'procurements'])->orderBy('created_at', 'desc');

        if ($request->user() && ($request->user()->role === 'Ketua Tim' || $request->user()->role === 'Ketua Tim Kerja')) {
            $query->where('user_id', $request->user()->id);
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
            // barang_id boleh null/0 jika barang baru
            'items.*.barang_id' => 'nullable|integer',
            'items.*.nama_barang' => 'required_if:items.*.barang_id,null,0|string',
            'items.*.satuan' => 'nullable|string',
            'items.*.jumlah_diminta' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string',
        ]);

        $statusVal = ($validated['status'] === 'draft' || $validated['status'] === 'Draft') ? 'Draft' : 'Menunggu Verifikasi';

        DB::beginTransaction();
        try {
            $user = $request->user();
            $requester = $user->name;
            $section = $user->section ?? 'Tata Usaha';
            $targetUserId = $user->id;

            if (strtolower($user->role) === 'superadmin' && $request->filled('requester')) {
                $targetUser = User::where('username', $request->input('requester'))->first();
                if ($targetUser) {
                    if (strtolower($targetUser->status) !== 'aktif') {
                        throw new SafeBusinessException("User tujuan '{$request->input('requester')}' tidak aktif.");
                    }
                    $targetUserId = $targetUser->id;
                    $requester = $targetUser->name;
                    $section = $targetUser->section ?? 'Tata Usaha';
                } else {
                    throw new SafeBusinessException("User tujuan '{$request->input('requester')}' tidak ditemukan.");
                }
            }

            // Generate BON Number (Sequential per day)
            $datePrefix = now()->format('Y/m/d');
            $prefix = 'BON/'.$datePrefix.'/';

            $lastBon = BonHeader::where('bon_no', 'like', $prefix.'%')
                ->orderBy('bon_no', 'desc')
                ->first();

            $nextNum = 1;
            if ($lastBon) {
                $lastNumStr = substr($lastBon->bon_no, strrpos($lastBon->bon_no, '/') + 1);
                if (is_numeric($lastNumStr)) {
                    $nextNum = intval($lastNumStr) + 1;
                }
            }

            $bonNo = null;
            $attempts = 0;
            do {
                $tempNo = $prefix.str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                if (! BonHeader::where('bon_no', $tempNo)->exists()) {
                    $bonNo = $tempNo;
                } else {
                    $nextNum++;
                }
                $attempts++;
            } while ($bonNo === null && $attempts < 10);

            if ($bonNo === null) {
                throw new SafeBusinessException('Gagal membuat nomor BON unik. Coba lagi.');
            }

            $bonHeader = BonHeader::create([
                'bon_no' => $bonNo,
                'user_id' => $targetUserId,
                'section' => $section,
                'requester' => $requester,
                'date' => today(),
                'status' => $statusVal,
                'keperluan' => $validated['keperluan'],
                'catatan' => $validated['catatan'] ?? null,
                'last_updated' => today(),
            ]);

            BonStatusHistory::create([
                'bon_header_id' => $bonHeader->id,
                'status_before' => null,
                'status_after' => $statusVal,
                'changed_by' => $user->name,
                'notes' => $statusVal === 'Draft' ? 'Draft pengajuan dibuat.' : 'Pengajuan dikirim.',
            ]);

            // Create ItemRequests (Dukungan Barang Terdaftar & Barang Baru)
            foreach ($validated['items'] as $item) {
                $stockItemId = ! empty($item['barang_id']) && $item['barang_id'] > 0 ? $item['barang_id'] : null;
                $stockItem = $stockItemId ? StockItem::find($stockItemId) : null;

                $itemName = $stockItem ? $stockItem->name : ($item['nama_barang'] ?? 'Barang Baru');
                $unit = $stockItem ? $stockItem->unit : ($item['satuan'] ?? 'Buah');

                ItemRequest::create([
                    'bon_header_id' => $bonHeader->id,
                    'bon_no' => $bonNo,
                    'user_id' => $targetUserId,
                    'section' => $section,
                    'requester' => $requester,
                    'date' => today(),
                    'status' => $statusVal === 'Draft' ? 'Draft' : 'Diajukan',
                    'stock_item_id' => $stockItemId,
                    'item_name' => $itemName,
                    'qty_requested' => $item['jumlah_diminta'],
                    'unit' => $unit,
                    'notes' => $item['catatan'] ?? null,
                    'qty_available' => 0,
                    'qty_fulfilled' => 0,
                    'qty_to_procure' => 0,
                    'stock_allocated' => false,
                    'last_updated' => today(),
                ]);
            }

            $isOnBehalf = ($user->name !== $requester);

            $actionText = $statusVal === 'Draft' ? 'Buat Draft' : 'Ajukan Permintaan';
            $detailsText = $statusVal === 'Draft'
                ? "Menyimpan draft BON: {$bonNo}"
                : "Mengajukan permintaan barang (BON: {$bonNo}) ke petugas";

            if ($isOnBehalf) {
                $detailsText .= " atas nama {$requester} (Ketua Tim)";
            }

            HistoryLog::create([
                'user_id' => $targetUserId,
                'actor' => $user->name,
                'action' => $actionText,
                'details' => $detailsText,
            ]);

            DB::commit();

            return response()->json($bonHeader->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error RequestController', ['exception' => $e]);
            $msg = $e instanceof SafeBusinessException ? $e->getMessage() : 'Terjadi kesalahan saat memproses data.';

            return response()->json(['message' => 'Gagal menyimpan pengajuan: '.$msg], 422);
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
            'deductStock.id' => [
                'required_with:deductStock',
                'integer',
                'exists:stock_items,id',
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
                $stockItem = StockItem::find($validated['deductStock']['id']);
                if ($stockItem && ! $itemRequest->stock_allocated) {
                    $qtyToDeduct = $validated['deductStock']['qtyToDeduct'];
                    if ($stockItem->qty < $qtyToDeduct) {
                        throw new SafeBusinessException('Stok gudang tidak mencukupi untuk pemenuhan ini.');
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
                BonStatusHistory::create([
                    'bon_header_id' => $bonHeader->id,
                    'status_before' => $oldStatus,
                    'status_after' => $validated['status'],
                    'changed_by' => $request->user() ? $request->user()->name : 'Sistem',
                    'notes' => "Barang '{$itemRequest->item_name}' diperbarui ke status '{$validated['status']}'.".
                               (isset($validated['verifier_notes']) && $validated['verifier_notes'] !== '' ? " Catatan verifikator: {$validated['verifier_notes']}" : ''),
                ]);

                // Update parent BON header status based on items
                $bonHeader->update(['last_updated' => today()]);
                $this->syncBonHeaderStatus($bonHeader);
            }

            HistoryLog::create([
                'actor' => $request->user() ? $request->user()->name : 'Sistem',
                'action' => 'Update Status Pengajuan',
                'details' => "Memperbarui status permintaan {$itemRequest->item_name} menjadi '{$validated['status']}' (BON: {$itemRequest->bon_no})",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Status pengajuan berhasil diperbarui.',
                'data' => $itemRequest->fresh(['distribution', 'procurements']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error RequestController', ['exception' => $e]);
            $msg = $e instanceof SafeBusinessException ? $e->getMessage() : 'Terjadi kesalahan saat memproses data.';

            return response()->json(['message' => $msg], 422);
        }
    }

    public function distribute(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'stockItemId' => 'required|exists:stock_items,id',
            'qtyDistributed' => 'required|integer|min:1',
            'distributedBy' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $stockItem = StockItem::findOrFail($validated['stockItemId']);

            if (! $itemRequest->stock_allocated) {
                if ($stockItem->qty < $validated['qtyDistributed']) {
                    throw new SafeBusinessException('Stok gudang tidak mencukupi untuk distribusi.');
                }
                $stockItem->qty -= $validated['qtyDistributed'];
                $stockItem->last_updated = today();
                $stockItem->save();
                $itemRequest->stock_allocated = true;
            } else {
                // Jika stok sebelumnya dialokasikan, periksa apakah yang didistribusikan melebihi yang sebelumnya dialokasikan
                if ($validated['qtyDistributed'] > $itemRequest->qty_fulfilled) {
                    $extraNeeded = $validated['qtyDistributed'] - $itemRequest->qty_fulfilled;
                    if ($stockItem->qty < $extraNeeded) {
                        throw new SafeBusinessException('Stok gudang tidak mencukupi untuk tambahan distribusi.');
                    }
                    $stockItem->qty -= $extraNeeded;
                    $stockItem->last_updated = today();
                    $stockItem->save();
                }
            }

            // Update qty_fulfilled and qty_to_procure berdasarkan jumlah aktual yang didistribusikan
            if ($validated['qtyDistributed'] > $itemRequest->qty_fulfilled) {
                $itemRequest->qty_fulfilled = $validated['qtyDistributed'];
                $itemRequest->qty_to_procure = max(0, $itemRequest->qty_requested - $itemRequest->qty_fulfilled);
            }

            Distribution::create([
                'item_request_id' => $itemRequest->id,
                'stock_item_id' => $stockItem->id,
                'qty_distributed' => $validated['qtyDistributed'],
                'distributed_by' => $validated['distributedBy'],
                'distributed_at' => today(),
                'notes' => $validated['notes'] ?? null,
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

            HistoryLog::create([
                'actor' => $request->user() ? $request->user()->name : 'Sistem',
                'action' => 'Distribusi Barang',
                'details' => "Mendistribusikan {$validated['qtyDistributed']} unit {$stockItem->name} untuk BON {$itemRequest->bon_no}",
            ]);

            DB::commit();

            return response()->json($itemRequest->fresh(['distribution', 'procurements']));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error RequestController', ['exception' => $e]);
            $msg = $e instanceof SafeBusinessException ? $e->getMessage() : 'Terjadi kesalahan saat memproses data.';

            return response()->json(['message' => $msg], 422);
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
                'status' => 'Diproses',
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

            HistoryLog::create([
                'actor' => $request->user() ? $request->user()->name : 'Sistem',
                'action' => 'Proses Pengadaan',
                'details' => "Memproses pengadaan {$validated['qtyProcured']} unit {$itemRequest->item_name} untuk BON {$itemRequest->bon_no}",
            ]);

            DB::commit();

            return response()->json($itemRequest->fresh(['distribution', 'procurements']));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error RequestController', ['exception' => $e]);
            $msg = $e instanceof SafeBusinessException ? $e->getMessage() : 'Terjadi kesalahan saat memproses data.';

            return response()->json(['message' => $msg], 422);
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
                throw new SafeBusinessException('Pengadaan ini sudah selesai.');
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

            HistoryLog::create([
                'actor' => $request->user() ? $request->user()->name : 'Sistem',
                'action' => 'Pengadaan Selesai',
                'details' => "Menyelesaikan pengadaan {$procurement->qty_procured} unit {$itemRequest->item_name} (BON {$itemRequest->bon_no})",
            ]);

            DB::commit();

            return response()->json($itemRequest->fresh(['distribution', 'procurements']));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error RequestController', ['exception' => $e]);
            $msg = $e instanceof SafeBusinessException ? $e->getMessage() : 'Terjadi kesalahan saat memproses data.';

            return response()->json(['message' => $msg], 422);
        }
    }

    public function indexBons(Request $request)
    {
        $validated = $request->validate([
            'bon_no' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|max:50',
            'all' => 'nullable|in:true,false',
        ]);

        $query = BonHeader::with(['items' => function ($q) {
            // Sertakan stock_item_id agar frontend bisa pre-fill barang_id
            $q->select('id', 'bon_header_id', 'stock_item_id', 'item_name',
                'unit', 'qty_requested', 'qty_fulfilled', 'status', 'notes', 'verifier_notes');
        }])
            ->withCount('items')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->user() && ($request->user()->role === 'Ketua Tim' || $request->user()->role === 'Ketua Tim Kerja')) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->user()) {
            $query->where(function ($sub) use ($request) {
                $sub->where('status', '!=', 'Draft')
                    ->orWhere('user_id', $request->user()->id);
            });
        }

        if (! empty($validated['bon_no'])) {
            $query->where('bon_no', 'like', '%'.$validated['bon_no'].'%');
        }

        if (! empty($validated['start_date'])) {
            $query->whereDate('date', '>=', $validated['start_date']);
        }
        if (! empty($validated['end_date'])) {
            $query->whereDate('date', '<=', $validated['end_date']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['all']) && $validated['all'] === 'true') {
            return response()->json($query->get());
        }

        return response()->json($query->paginate(15));
    }

    public function showBon(Request $request, $id)
    {
        $bon = BonHeader::with(['items', 'statusHistories' => fn ($q) => $q->orderBy('created_at', 'asc')])->findOrFail($id);

        if ($request->user() && ($request->user()->role === 'Ketua Tim' || $request->user()->role === 'Ketua Tim Kerja')) {
            if ($bon->user_id !== $request->user()->id) {
                abort(403, 'Akses ditolak. Anda bukan pemilik pengajuan ini.');
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
            'items.*.barang_id' => 'nullable|integer',
            'items.*.nama_barang' => 'nullable|string',
            'items.*.satuan' => 'nullable|string',
            'items.*.jumlah_diminta' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string',
        ]);

        $statusVal = ($validated['status'] === 'draft' || $validated['status'] === 'Draft') ? 'Draft' : 'Menunggu Verifikasi';
        $bonHeader = BonHeader::findOrFail($id);
        $user = $request->user();

        if ($bonHeader->user_id !== $user->id && strtolower($user->role) !== 'superadmin') {
            abort(403, 'Anda bukan pemilik draft ini.');
        }

        if ($bonHeader->status !== 'Draft') {
            abort(422, 'Pengajuan yang sudah dikirim tidak dapat diedit.');
        }

        DB::beginTransaction();
        try {
            $oldStatus = $bonHeader->status;
            $requester = $bonHeader->requester;
            $section = $bonHeader->section;

            if (strtolower($user->role) === 'superadmin' && $request->filled('requester')) {
                $targetUser = User::where('username', $request->input('requester'))->first();
                if ($targetUser) {
                    if (strtolower($targetUser->status) !== 'aktif') {
                        throw new SafeBusinessException("User tujuan '{$request->input('requester')}' tidak aktif.");
                    }
                    $requester = $targetUser->name;
                    $section = $targetUser->section ?? 'Tata Usaha';
                } else {
                    throw new SafeBusinessException("User tujuan '{$request->input('requester')}' tidak ditemukan.");
                }
            }

            $bonHeader->update([
                'status' => $statusVal,
                'keperluan' => $validated['keperluan'],
                'catatan' => $validated['catatan'] ?? null,
                'requester' => $requester,
                'section' => $section,
                'last_updated' => today(),
            ]);

            BonStatusHistory::create([
                'bon_header_id' => $bonHeader->id,
                'status_before' => $oldStatus,
                'status_after' => $statusVal,
                'changed_by' => $user->name,
                'notes' => $statusVal === 'Draft' ? 'Draft diperbarui.' : 'Draft dikirim sebagai pengajuan.',
            ]);

            $bonHeader->items()->delete();

            foreach ($validated['items'] as $item) {
                $stockItemId = ! empty($item['barang_id']) && $item['barang_id'] > 0 ? $item['barang_id'] : null;
                $stockItem = $stockItemId ? StockItem::find($stockItemId) : null;

                $itemName = $stockItem ? $stockItem->name : ($item['nama_barang'] ?? 'Barang Baru');
                $unit = $stockItem ? $stockItem->unit : ($item['satuan'] ?? 'Buah');

                ItemRequest::create([
                    'bon_header_id' => $bonHeader->id,
                    'bon_no' => $bonHeader->bon_no,
                    'user_id' => $user->id,
                    'section' => $section,
                    'requester' => $requester,
                    'date' => $bonHeader->date,
                    'status' => $statusVal === 'Draft' ? 'Draft' : 'Diajukan',
                    'stock_item_id' => $stockItemId,
                    'item_name' => $itemName,
                    'qty_requested' => $item['jumlah_diminta'],
                    'unit' => $unit,
                    'notes' => $item['catatan'] ?? null,
                    'qty_available' => 0,
                    'qty_fulfilled' => 0,
                    'qty_to_procure' => 0,
                    'stock_allocated' => false,
                    'last_updated' => today(),
                ]);
            }

            HistoryLog::create([
                'actor' => $user->name,
                'action' => $statusVal === 'Draft' ? 'Update Draft' : 'Ajukan Draft',
                'details' => $statusVal === 'Draft'
                    ? "Memperbarui draft BON: {$bonHeader->bon_no}"
                    : "Mengirim draft BON {$bonHeader->bon_no} menjadi pengajuan ke petugas",
            ]);

            DB::commit();

            return response()->json($bonHeader->load('items'));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error RequestController', ['exception' => $e]);
            $msg = $e instanceof SafeBusinessException ? $e->getMessage() : 'Terjadi kesalahan saat memproses data.';

            return response()->json(['message' => 'Gagal memperbarui draft: '.$msg], 422);
        }
    }

    public function destroyDraft(Request $request, $id)
    {
        $bonHeader = BonHeader::findOrFail($id);

        $user = $request->user();
        if ($bonHeader->user_id !== $user->id && strtolower($user->role) !== 'superadmin') {
            abort(403, 'Anda bukan pemilik draft ini.');
        }

        if ($bonHeader->status !== 'Draft') {
            abort(422, 'Pengajuan yang sudah dikirim tidak dapat dihapus.');
        }

        DB::beginTransaction();
        try {
            $bonHeader->items()->delete();
            $bonHeader->delete();
            HistoryLog::create([
                'actor' => $request->user() ? $request->user()->name : 'Sistem',
                'action' => 'Hapus Draft',
                'details' => "Menghapus draft BON: {$bonHeader->bon_no}",
            ]);

            DB::commit();

            return response()->json(['message' => 'Draft berhasil dihapus.']);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error RequestController', ['exception' => $e]);
            $msg = $e instanceof SafeBusinessException ? $e->getMessage() : 'Terjadi kesalahan saat memproses data.';

            return response()->json(['message' => 'Gagal menghapus draft: '.$msg], 422);
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
    /**
     * Selesaikan pengajuan yang Terpenuhi Sebagian tanpa melakukan pengadaan.
     */
    public function completePartial(Request $request, ItemRequest $itemRequest)
    {
        if ($itemRequest->status !== 'Terpenuhi Sebagian') {
            return response()->json(['message' => 'Hanya pengajuan Terpenuhi Sebagian yang dapat diselesaikan langsung.'], 422);
        }

        DB::beginTransaction();
        try {
            $itemRequest->update([
                'qty_to_procure' => 0,
                'last_updated' => today(),
            ]);

            HistoryLog::create([
                'actor' => $request->user() ? $request->user()->name : 'Sistem',
                'action' => 'Selesai Sebagian',
                'details' => "Menandai permintaan {$itemRequest->item_name} (BON {$itemRequest->bon_no}) selesai tanpa pengadaan sisa barang.",
            ]);

            $this->syncBonHeaderStatus($itemRequest->bonHeader);
            DB::commit();

            return response()->json($itemRequest->fresh());
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal menyelesaikan pengajuan.'], 500);
        }
    }

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
                    .'sudah didistribusikan (tidak dikembalikan). '
                    ."Sisa {$itemRequest->qty_to_procure} {$itemRequest->unit} yang belum diadakan dibatalkan. "
                    ."Alasan: {$validated['alasan']}";
            } else {
                // Status lain: kembalikan stok jika sudah dialokasikan
                if ($itemRequest->stock_allocated && $itemRequest->qty_fulfilled > 0) {
                    $stockItem = $itemRequest->stock_item_id
                        ? StockItem::lockForUpdate()->find($itemRequest->stock_item_id)
                        : StockItem::lockForUpdate()
                            ->whereRaw('LOWER(name) = LOWER(?)', [$itemRequest->item_name])
                            ->first();

                    if ($stockItem) {
                        $stockItem->qty += $itemRequest->qty_fulfilled;
                        $stockItem->last_updated = today();
                        $stockItem->save();
                    }
                }
                $notes = "Alasan: {$validated['alasan']}";
            }

            $oldStatus = $itemRequest->status;

            $itemRequest->update([
                'status' => 'Ditolak',
                'verifier_notes' => $validated['alasan'],
                // Terpenuhi Sebagian: pertahankan qty_fulfilled (sudah didistribusikan)
                // Status lain: nol-kan semua
                'qty_fulfilled' => $isTerpenuhinSebagian ? $itemRequest->qty_fulfilled : 0,
                'qty_to_procure' => 0,
                'stock_allocated' => $isTerpenuhinSebagian ? $itemRequest->stock_allocated : false,
                'last_updated' => today(),
            ]);

            // Update parent BON header status
            $bonHeader = $itemRequest->bonHeader;
            if ($bonHeader) {
                BonStatusHistory::create([
                    'bon_header_id' => $bonHeader->id,
                    'status_before' => $oldStatus,
                    'status_after' => 'Ditolak',
                    'changed_by' => $request->user()?->name ?? 'Petugas',
                    'notes' => "Pengajuan '{$itemRequest->item_name}' dibatalkan. {$notes}",
                ]);
                $bonHeader->update(['last_updated' => today()]);
                $this->syncBonHeaderStatus($bonHeader);
            }

            HistoryLog::create([
                'actor' => $request->user() ? $request->user()->name : 'Sistem',
                'action' => 'Tolak Pengajuan',
                'details' => "Membatalkan permintaan {$itemRequest->item_name} (BON {$itemRequest->bon_no}). Alasan: {$validated['alasan']}",
            ]);

            DB::commit();

            return response()->json([
                'message' => $isTerpenuhinSebagian
                    ? 'Sisa pengajuan yang belum terpenuhi berhasil dibatalkan. Barang yang sudah didistribusikan tidak dikembalikan.'
                    : 'Pengajuan berhasil dibatalkan.',
                'data' => $itemRequest->fresh(['distribution', 'procurements']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error RequestController', ['exception' => $e]);
            $msg = $e instanceof SafeBusinessException ? $e->getMessage() : 'Terjadi kesalahan saat memproses data.';

            return response()->json(['message' => $msg], 422);
        }
    }

    private function syncBonHeaderStatus($bonHeader)
    {
        if (! $bonHeader) {
            return;
        }

        $items = $bonHeader->items;
        if ($items->isEmpty()) {
            return;
        }

        // If the header is draft, keep it draft unless changed.
        if ($bonHeader->status === 'Draft') {
            return;
        }

        $statuses = $items->pluck('status')->map(fn ($s) => strtoupper(trim($s)))->toArray();

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
        $hasInProgress = false;
        foreach ($items as $item) {
            $status = strtoupper(trim($item->status));
            if (in_array($status, ['PERLU PENGADAAN', 'DALAM PENGADAAN'])) {
                $hasInProgress = true;
                break;
            }
            if ($status === 'TERPENUHI SEBAGIAN' && $item->qty_to_procure > 0) {
                $hasInProgress = true;
                break;
            }
        }
        if ($hasInProgress) {
            $bonHeader->update(['status' => 'Diproses']);

            return;
        }

        // If all remaining items are either SELESAI, DITOLAK, TERPENUHI, SIAP DIDISTRIBUSIKAN, or TERPENUHI SEBAGIAN (with qty_to_procure == 0)
        // Check if any is TERPENUHI or SIAP DIDISTRIBUSIKAN -> Disetujui
        if (in_array('TERPENUHI', $statuses) || in_array('SIAP DIDISTRIBUSIKAN', $statuses)) {
            $bonHeader->update(['status' => 'Disetujui']);

            return;
        }

        // If all resolved items are either SELESAI, DITOLAK, or TERPENUHI SEBAGIAN (with qty_to_procure == 0)
        $allResolved = true;
        $hasPartialCompleted = false;
        foreach ($items as $item) {
            $status = strtoupper(trim($item->status));
            if ($status !== 'SELESAI' && $status !== 'DITOLAK') {
                if ($status === 'TERPENUHI SEBAGIAN' && $item->qty_to_procure == 0) {
                    $hasPartialCompleted = true;
                } else {
                    $allResolved = false;
                    break;
                }
            }
        }

        if ($allResolved) {
            if ($hasPartialCompleted) {
                $bonHeader->update(['status' => 'Selesai (Sebagian)']);
            } else {
                $bonHeader->update(['status' => 'Selesai']);
            }
        } else {
            // Fallback
            $bonHeader->update(['status' => 'Diproses']);
        }
    }
}
