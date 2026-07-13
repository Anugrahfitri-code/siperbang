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
    public function index()
    {
        $requests = ItemRequest::with(['distribution', 'procurements'])->orderBy('created_at', 'desc')->get();
        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section' => 'required|string',
            'itemName' => 'required|string',
            'qtyRequested' => 'required|integer|min:1',
            'unit' => 'required|string',
            'notes' => 'nullable|string',
            'date' => 'required|date',
            'requester' => 'required|string',
        ]);

        $countToday = ItemRequest::whereDate('created_at', today())->count() + 1;
        $bonNo = 'BON/' . date('Y/m/') . str_pad($countToday, 3, '0', STR_PAD_LEFT);

        $itemRequest = ItemRequest::create([
            'bon_no' => $bonNo,
            'section' => $validated['section'],
            'item_name' => $validated['itemName'],
            'qty_requested' => $validated['qtyRequested'],
            'unit' => $validated['unit'],
            'status' => 'Diajukan',
            'notes' => $validated['notes'] ?? null,
            'date' => $validated['date'],
            'requester' => $validated['requester'],
            'qty_available' => 0,
            'qty_fulfilled' => 0,
            'qty_to_procure' => 0,
            'stock_allocated' => false,
            'last_updated' => today(),
        ]);

        return response()->json($itemRequest, 201);
    }

    public function updateStatus(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'qtyAvailable' => 'required|integer|min:0',
            'qtyFulfilled' => 'required|integer|min:0',
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

            $itemRequest->update([
                'status' => $validated['status'],
                'qty_available' => $validated['qtyAvailable'],
                'qty_fulfilled' => $validated['qtyFulfilled'],
                'qty_to_procure' => $qtyToProcure,
                'last_updated' => today(),
            ]);

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

            DB::commit();

            return response()->json($itemRequest->fresh(['distribution', 'procurements']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}