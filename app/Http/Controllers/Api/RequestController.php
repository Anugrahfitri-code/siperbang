<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\HistoryLog;
use App\Models\ItemRequest;
use App\Models\Procurement;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    private function mapRequest(ItemRequest $r): array
    {
        return [
            'id'                 => $r->id,
            'bonNo'              => $r->bon_no,
            'section'            => $r->section,
            'itemName'           => $r->item_name,
            'qtyRequested'       => $r->qty_requested,
            'qtyAvailable'       => $r->qty_available,
            'qtyFulfilled'       => $r->qty_fulfilled,
            'qtyToProcure'       => $r->qty_to_procure,
            'stockAllocated'     => $r->stock_allocated,
            'unit'               => $r->unit,
            'status'             => $r->status,
            'notes'              => $r->notes,
            'date'               => $r->date?->toDateString(),
            'requester'          => $r->requester,
            'lastUpdated'        => $r->last_updated?->toDateString(),
            'stockItemId'        => $r->stock_item_id,
            'procurementMethod'  => $r->procurement_method,
            'vendorName'         => $r->vendor_name,
            'distribution'       => $r->distribution,
            'procurements'       => $r->procurements,
        ];
    }

    private function logHistory(string $actor, string $action, string $details): void
    {
        HistoryLog::create([
            'actor'   => $actor,
            'action'  => $action,
            'details' => $details,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // LIST
    // ──────────────────────────────────────────────────────────────

    public function index()
    {
        $requests = ItemRequest::with(['distribution', 'procurements'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests->map(fn ($r) => $this->mapRequest($r)));
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE NEW BON REQUEST
    // ──────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section'      => 'required|string|max:255',
            'itemName'     => 'required|string|max:255',
            'qtyRequested' => 'required|integer|min:1',
            'unit'         => 'required|string|max:50',
            'notes'        => 'nullable|string|max:1000',
            'date'         => 'required|date',
            'requester'    => 'required|string|max:255',
        ]);

        $countToday = ItemRequest::whereDate('created_at', today())->count() + 1;
        $bonNo      = 'BON/' . date('Y/m/') . str_pad($countToday, 3, '0', STR_PAD_LEFT);

        $itemRequest = ItemRequest::create([
            'bon_no'        => $bonNo,
            'section'       => $validated['section'],
            'item_name'     => $validated['itemName'],
            'qty_requested' => $validated['qtyRequested'],
            'unit'          => $validated['unit'],
            'status'        => 'Diajukan',
            'notes'         => $validated['notes'] ?? null,
            'date'          => $validated['date'],
            'requester'     => $validated['requester'],
            'last_updated'  => today(),
        ]);

        $this->logHistory(
            $validated['requester'],
            'Ajukan BON',
            "BON {$bonNo} diajukan: {$validated['itemName']} ({$validated['qtyRequested']} {$validated['unit']}) oleh {$validated['section']}."
        );

        return response()->json($this->mapRequest($itemRequest->fresh(['distribution', 'procurements'])), 201);
    }

    // ──────────────────────────────────────────────────────────────
    // STOCK CHECK — sets qty_available, qty_fulfilled, status
    // Prevents double-deduction via stock_allocated flag.
    // ──────────────────────────────────────────────────────────────

    public function updateStatus(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'status'              => 'required|string|in:Diajukan,Dicek,Terpenuhi,Terpenuhi Sebagian,Siap Didistribusikan,Perlu Pengadaan,Dalam Pengadaan,Ditolak,Selesai',
            'qtyAvailable'        => 'required|integer|min:0',
            'qtyFulfilled'        => 'required|integer|min:0',
            'deductStock'         => 'nullable|array',
            'deductStock.code'    => 'required_with:deductStock|string',
            'deductStock.qtyToDeduct' => 'required_with:deductStock|integer|min:0',
        ]);

        // Guard: cannot update a request that is already fully finalised
        if ($itemRequest->status === 'Selesai') {
            return response()->json(['message' => 'Request sudah selesai dan tidak dapat diubah.'], 422);
        }

        // Guard: qty_fulfilled cannot exceed qty_requested
        if ($validated['qtyFulfilled'] > $itemRequest->qty_requested) {
            return response()->json([
                'message' => 'Jumlah terpenuhi tidak boleh melebihi jumlah yang diminta.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $itemRequest) {
            $qtyFulfilled  = $validated['qtyFulfilled'];
            $qtyRequested  = $itemRequest->qty_requested;
            $qtyToProcure  = max(0, $qtyRequested - $qtyFulfilled);

            // Stock deduction — only once, guarded by stock_allocated flag
            if (
                isset($validated['deductStock']) &&
                ! $itemRequest->stock_allocated   // ← prevents double-deduct
            ) {
                $deduct    = $validated['deductStock'];
                $stockItem = StockItem::where('code', $deduct['code'])
                    ->lockForUpdate()            // row-level lock
                    ->firstOrFail();

                $toDeduct = min($deduct['qtyToDeduct'], $stockItem->qty); // never go negative

                if ($toDeduct > 0) {
                    $stockItem->decrement('qty', $toDeduct);
                    $stockItem->update(['last_updated' => today()]);
                }
            }

            $itemRequest->update([
                'status'          => $validated['status'],
                'qty_available'   => $validated['qtyAvailable'],
                'qty_fulfilled'   => $qtyFulfilled,
                'qty_to_procure'  => $qtyToProcure,
                'stock_allocated' => $itemRequest->stock_allocated || isset($validated['deductStock']),
                'last_updated'    => today(),
            ]);
        });

        $fresh = $itemRequest->fresh(['distribution', 'procurements']);

        $this->logHistory(
            auth()->user()?->name ?? 'System',
            'Cek Stok',
            "BON {$itemRequest->bon_no}: status → {$validated['status']}, terpenuhi {$validated['qtyFulfilled']}/{$itemRequest->qty_requested} {$itemRequest->unit}."
        );

        return response()->json($this->mapRequest($fresh));
    }

    // ──────────────────────────────────────────────────────────────
    // DISTRIBUTE — physically distribute from stock to requester
    // Only allowed when status is 'Terpenuhi' or 'Terpenuhi Sebagian'
    // (stock has already been allocated in updateStatus).
    // ──────────────────────────────────────────────────────────────

    public function distribute(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'stockItemId'    => 'required|integer|exists:stock_items,id',
            'qtyDistributed' => 'required|integer|min:1',
            'distributedBy'  => 'required|string|max:255',
            'notes'          => 'nullable|string|max:500',
        ]);

        // Guard: cannot distribute if already distributed
        if ($itemRequest->distribution) {
            return response()->json(['message' => 'Distribusi untuk BON ini sudah pernah dilakukan.'], 422);
        }

        // Guard: only allow distribution if stock has been checked
        $allowedStatuses = ['Terpenuhi', 'Terpenuhi Sebagian', 'Siap Didistribusikan'];
        if (! in_array($itemRequest->status, $allowedStatuses)) {
            return response()->json([
                'message' => "Distribusi hanya dapat dilakukan pada status: " . implode(', ', $allowedStatuses),
            ], 422);
        }

        // Guard: cannot distribute more than what was fulfilled from stock
        if ($validated['qtyDistributed'] > $itemRequest->qty_fulfilled) {
            return response()->json([
                'message' => "Jumlah distribusi ({$validated['qtyDistributed']}) melebihi jumlah yang dialokasikan dari stok ({$itemRequest->qty_fulfilled}).",
            ], 422);
        }

        DB::transaction(function () use ($validated, $itemRequest) {
            Distribution::create([
                'item_request_id'  => $itemRequest->id,
                'stock_item_id'    => $validated['stockItemId'],
                'qty_distributed'  => $validated['qtyDistributed'],
                'distributed_by'   => $validated['distributedBy'],
                'distributed_at'   => today(),
                'notes'            => $validated['notes'] ?? null,
            ]);

            // Determine new status after distribution
            $qtyToProcure = $itemRequest->qty_to_procure;
            $newStatus    = $qtyToProcure > 0 ? 'Dalam Pengadaan' : 'Selesai';

            $itemRequest->update([
                'status'       => $newStatus,
                'last_updated' => today(),
            ]);
        });

        $fresh = $itemRequest->fresh(['distribution', 'procurements']);

        $this->logHistory(
            $validated['distributedBy'],
            'Distribusi Barang',
            "BON {$itemRequest->bon_no}: didistribusikan {$validated['qtyDistributed']} {$itemRequest->unit} dari stok gudang."
        );

        return response()->json($this->mapRequest($fresh));
    }

    // ──────────────────────────────────────────────────────────────
    // PROCURE — create procurement record for unfulfilled portion
    // ──────────────────────────────────────────────────────────────

    public function procure(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'method'       => 'required|string|in:Pengadaan Vendor,Pengadaan Sendiri (Toko)',
            'vendorName'   => 'required_if:method,Pengadaan Vendor|nullable|string|max:255',
            'storeName'    => 'required_if:method,Pengadaan Sendiri (Toko)|nullable|string|max:255',
            'qtyProcured'  => 'required|integer|min:1',
            'unitPrice'    => 'required|numeric|min:0',
            'isTaxed'      => 'required|boolean',
            'taxRate'      => 'required_if:isTaxed,true|numeric|min:0|max:100',
            'invoiceNo'    => 'nullable|string|max:100',
            'bastName'     => 'nullable|string|max:255',
            'bastDate'     => 'nullable|date',
            'contractNo'   => 'nullable|string|max:100',
            'processedBy'  => 'required|string|max:255',
        ]);

        // Guard: cannot procure more than qty_to_procure
        $maxProcure = $itemRequest->qty_to_procure > 0
            ? $itemRequest->qty_to_procure
            : $itemRequest->qty_requested;

        if ($validated['qtyProcured'] > $maxProcure) {
            return response()->json([
                'message' => "Jumlah pengadaan ({$validated['qtyProcured']}) melebihi sisa kebutuhan yang perlu diadakan ({$maxProcure}).",
            ], 422);
        }

        // Guard: must be in a procurable status
        $procurableStatuses = ['Perlu Pengadaan', 'Terpenuhi Sebagian', 'Dalam Pengadaan'];
        if (! in_array($itemRequest->status, $procurableStatuses)) {
            return response()->json([
                'message' => "Pengadaan hanya dapat dilakukan pada status: " . implode(', ', $procurableStatuses),
            ], 422);
        }

        $taxRate   = $validated['isTaxed'] ? ($validated['taxRate'] ?? 11) : 0;
        $taxFactor = 1 + ($taxRate / 100);
        $total     = round($validated['unitPrice'] * $taxFactor * $validated['qtyProcured'], 2);

        DB::transaction(function () use ($validated, $itemRequest, $taxRate, $total) {
            Procurement::create([
                'item_request_id'  => $itemRequest->id,
                'method'           => $validated['method'],
                'vendor_name'      => $validated['vendorName'] ?? null,
                'store_name'       => $validated['storeName'] ?? null,
                'qty_procured'     => $validated['qtyProcured'],
                'unit_price'       => $validated['unitPrice'],
                'total_price'      => $total,
                'is_taxed'         => $validated['isTaxed'],
                'tax_rate'         => $taxRate,
                'status'           => 'Diproses',
                'invoice_no'       => $validated['invoiceNo'] ?? null,
                'bast_name'        => $validated['bastName'] ?? null,
                'bast_date'        => $validated['bastDate'] ?? null,
                'contract_no'      => $validated['contractNo'] ?? null,
                'processed_by'     => $validated['processedBy'],
                'procurement_date' => today(),
            ]);

            // Update request procurement metadata
            $itemRequest->update([
                'status'             => 'Dalam Pengadaan',
                'procurement_method' => $validated['method'],
                'vendor_name'        => $validated['vendorName'] ?? null,
                'last_updated'       => today(),
            ]);
        });

        $fresh = $itemRequest->fresh(['distribution', 'procurements']);

        $this->logHistory(
            $validated['processedBy'],
            'Proses Pengadaan',
            "BON {$itemRequest->bon_no}: pengadaan {$validated['qtyProcured']} {$itemRequest->unit} via {$validated['method']}."
        );

        return response()->json($this->mapRequest($fresh));
    }

    // ──────────────────────────────────────────────────────────────
    // COMPLETE PROCUREMENT — mark procurement as received & finalise
    // ──────────────────────────────────────────────────────────────

    public function completeProcurement(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'procurementId' => 'required|integer|exists:procurements,id',
            'processedBy'   => 'required|string|max:255',
        ]);

        $procurement = Procurement::where('id', $validated['procurementId'])
            ->where('item_request_id', $itemRequest->id)
            ->firstOrFail();

        if ($procurement->status === 'Diterima') {
            return response()->json(['message' => 'Pengadaan ini sudah diterima.'], 422);
        }

        DB::transaction(function () use ($procurement, $itemRequest, $validated) {
            $procurement->update(['status' => 'Diterima']);

            // Add procured qty to fulfilled
            $newFulfilled = min(
                $itemRequest->qty_fulfilled + $procurement->qty_procured,
                $itemRequest->qty_requested
            );
            $newToProcure = max(0, $itemRequest->qty_requested - $newFulfilled);

            $newStatus = $newToProcure === 0 ? 'Selesai' : 'Dalam Pengadaan';

            // Restock: add procured item back to stock
            if ($itemRequest->stock_item_id) {
                $stock = StockItem::lockForUpdate()->find($itemRequest->stock_item_id);
                if ($stock) {
                    $stock->increment('qty', $procurement->qty_procured);
                    $stock->update(['last_updated' => today()]);
                }
            }

            $itemRequest->update([
                'qty_fulfilled'  => $newFulfilled,
                'qty_to_procure' => $newToProcure,
                'status'         => $newStatus,
                'last_updated'   => today(),
            ]);
        });

        $fresh = $itemRequest->fresh(['distribution', 'procurements']);

        $this->logHistory(
            $validated['processedBy'],
            'Terima Pengadaan',
            "BON {$itemRequest->bon_no}: pengadaan #{$procurement->id} diterima, status → {$fresh->status}."
        );

        return response()->json($this->mapRequest($fresh));
    }
}
