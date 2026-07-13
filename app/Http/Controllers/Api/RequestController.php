<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemRequest;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function index()
    {
        $requests = ItemRequest::orderBy('created_at', 'desc')->get();

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

        $countToday = ItemRequest::whereDate(
            'created_at',
            today()
        )->count() + 1;

        $bonNo = 'BON/'
            . date('Y/m/')
            . str_pad($countToday, 3, '0', STR_PAD_LEFT);

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
            'last_updated' => today(),
        ]);

        return response()->json([
            'message' => 'Pengajuan BON berhasil dibuat.',
            'data' => $itemRequest,
        ], 201);
    }

    public function updateStatus(
        Request $request,
        ItemRequest $itemRequest
    ) {
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

        $itemRequest->update([
            'status' => $validated['status'],
            'qty_available' => $validated['qtyAvailable'],
            'qty_fulfilled' => $validated['qtyFulfilled'],
            'last_updated' => today(),
        ]);

        return response()->json([
            'message' => 'Status pengajuan berhasil diperbarui.',
            'data' => $itemRequest->fresh(),
        ]);
    }
}