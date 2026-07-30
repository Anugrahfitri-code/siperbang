<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\HistoryLog;
use App\Models\ItemRequest;
use App\Models\Procurement;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * StokPengadaanController
 *
 * JSON API controller (used by React frontend via /api/requests/*)
 * that wraps the full stock-check → distribute → procure lifecycle.
 *
 * All endpoints return JSON.  The blade-based stub that referenced
 * the non-existent PengajuanBarang / Distribusi models has been
 * replaced completely.
 */
class StokPengadaanController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    private function mapRequest(ItemRequest $r): array
    {
        return [
            'id'                => $r->id,
            'bonNo'             => $r->bon_no,
            'section'           => $r->section,
            'itemName'          => $r->item_name,
            'qtyRequested'      => $r->qty_requested,
            'qtyAvailable'      => $r->qty_available,
            'qtyFulfilled'      => $r->qty_fulfilled,
            'qtyToProcure'      => $r->qty_to_procure,
            'qtyUnfulfilled'    => $r->qty_unfulfilled,   // accessor
            'stockAllocated'    => $r->stock_allocated,
            'unit'              => $r->unit,
            'status'            => $r->status,
            'notes'             => $r->notes,
            'date'              => $r->date?->toDateString(),
            'requester'         => $r->requester,
            'lastUpdated'       => $r->last_updated?->toDateString(),
            'stockItemId'       => $r->stock_item_id,
            'procurementMethod' => $r->procurement_method,
            'vendorName'        => $r->vendor_name,
            'distribution'      => $r->distribution,
            'procurements'      => $r->procurements,
        ];
    }

    private function log(string $actor, string $action, string $details): void
    {
        HistoryLog::create([
            'actor'   => $actor,
            'action'  => $action,
            'details' => $details,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // INDEX — list all requests pending action
    // GET /stok-pengadaan
    // ──────────────────────────────────────────────────────────────

    public function index()
    {
        $requests = ItemRequest::with(['stockItem', 'distribution', 'procurements'])
            ->whereNotIn('status', ['Selesai', 'Ditolak', 'Draft'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests->map(fn ($r) => $this->mapRequest($r)));
    }

    // ──────────────────────────────────────────────────────────────
    // CEK STOK — check stock availability for one request
    // GET /stok-pengadaan/{id}
    // ──────────────────────────────────────────────────────────────

    public function cekStok($id)
    {
        $req = ItemRequest::with(['stockItem', 'distribution', 'procurements'])
            ->findOrFail($id);

        // Try to auto-match stock item by name if not already linked
        $stock = null;
        if ($req->stock_item_id) {
            $stock = StockItem::find($req->stock_item_id);
        } else {
            $stock = StockItem::whereRaw('LOWER(name) = ?', [strtolower($req->item_name)])
                ->first();
        }

        return response()->json([
            'request'   => $this->mapRequest($req),
            'stockItem' => $stock,
            'stockQty'  => $stock?->qty ?? 0,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // PROSES DISTRIBUSI — distribute already-allocated stock
    // POST /stok-pengadaan/{id}/distribusi
    // ──────────────────────────────────────────────────────────────

    public function prosesDistribusi(Request $request, $id)
    {
        $itemRequest = ItemRequest::with(['distribution', 'procurements'])->findOrFail($id);

        $validated = $request->validate([
            'stockItemId'    => 'required|integer|exists:stock_items,id',
            'qtyDistributed' => 'required|integer|min:1',
            'distributedBy'  => 'required|string|max:255',
            'notes'          => 'nullable|string|max:500',
        ]);

        // Guard: already distributed
        if ($itemRequest->distribution) {
            return response()->json(['message' => 'Distribusi untuk BON ini sudah pernah dilakukan.'], 422);
        }

        // Guard: status must allow distribution
        $allowedStatuses = ['Terpenuhi', 'Terpenuhi Sebagian', 'Siap Didistribusikan'];
        if (! in_array($itemRequest->status, $allowedStatuses)) {
            return response()->json([
                'message' => 'Distribusi tidak dapat dilakukan pada status saat ini: ' . $itemRequest->status,
            ], 422);
        }

        // Guard: cannot distribute more than fulfilled qty
        if ($validated['qtyDistributed'] > $itemRequest->qty_fulfilled) {
            return response()->json([
                'message' => "Jumlah distribusi ({$validated['qtyDistributed']}) melebihi jumlah yang dialokasikan ({$itemRequest->qty_fulfilled}).",
            ], 422);
        }

        DB::transaction(function () use ($validated, $itemRequest) {
            Distribution::create([
                'item_request_id' => $itemRequest->id,
                'stock_item_id'   => $validated['stockItemId'],
                'qty_distributed' => $validated['qtyDistributed'],
                'distributed_by'  => $validated['distributedBy'],
                'distributed_at'  => today(),
                'notes'           => $validated['notes'] ?? null,
            ]);

            $newStatus = $itemRequest->qty_to_procure > 0 ? 'Dalam Pengadaan' : 'Selesai';

            $itemRequest->update([
                'status'       => $newStatus,
                'last_updated' => today(),
            ]);
        });

        $fresh = $itemRequest->fresh(['distribution', 'procurements']);

        $this->log(
            $validated['distributedBy'],
            'Distribusi Barang',
            "BON {$itemRequest->bon_no}: didistribusikan {$validated['qtyDistributed']} {$itemRequest->unit}."
        );

        return response()->json($this->mapRequest($fresh));
    }

    // ──────────────────────────────────────────────────────────────
    // PROSES PENGADAAN — create procurement record
    // POST /stok-pengadaan/{id}/pengadaan
    // ──────────────────────────────────────────────────────────────

    public function prosesPengadaan(Request $request, $id)
    {
        $itemRequest = ItemRequest::with(['distribution', 'procurements'])->findOrFail($id);

        $validated = $request->validate([
            'method'      => 'required|string|in:Pengadaan Vendor,Pengadaan Sendiri (Toko)',
            'vendorName'  => 'required_if:method,Pengadaan Vendor|nullable|string|max:255',
            'storeName'   => 'required_if:method,Pengadaan Sendiri (Toko)|nullable|string|max:255',
            'qtyProcured' => 'required|integer|min:1',
            'unitPrice'   => 'required|numeric|min:0',
            'isTaxed'     => 'required|boolean',
            'taxRate'     => 'nullable|numeric|min:0|max:100',
            'invoiceNo'   => 'nullable|string|max:100',
            'bastName'    => 'nullable|string|max:255',
            'bastDate'    => 'nullable|date',
            'contractNo'  => 'nullable|string|max:100',
            'processedBy' => 'required|string|max:255',
        ]);

        // Guard: status
        $procurableStatuses = ['Perlu Pengadaan', 'Terpenuhi Sebagian', 'Dalam Pengadaan'];
        if (! in_array($itemRequest->status, $procurableStatuses)) {
            return response()->json([
                'message' => 'Pengadaan tidak dapat dilakukan pada status saat ini: ' . $itemRequest->status,
            ], 422);
        }

        // Guard: qty
        $maxProcure = $itemRequest->qty_to_procure > 0
            ? $itemRequest->qty_to_procure
            : $itemRequest->qty_requested;

        if ($validated['qtyProcured'] > $maxProcure) {
            return response()->json([
                'message' => "Jumlah pengadaan ({$validated['qtyProcured']}) melebihi kebutuhan ({$maxProcure}).",
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

            $itemRequest->update([
                'status'             => 'Dalam Pengadaan',
                'procurement_method' => $validated['method'],
                'vendor_name'        => $validated['vendorName'] ?? null,
                'last_updated'       => today(),
            ]);
        });

        $fresh = $itemRequest->fresh(['distribution', 'procurements']);

        $this->log(
            $validated['processedBy'],
            'Proses Pengadaan',
            "BON {$itemRequest->bon_no}: pengadaan {$validated['qtyProcured']} {$itemRequest->unit} via {$validated['method']}."
        );

        return response()->json($this->mapRequest($fresh));
    }
}
