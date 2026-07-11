<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemRequest;
use App\Models\StockItem;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function index()
    {
        return response()->json(ItemRequest::orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section' => 'required|string',
            'itemName' => 'required|string',
            'qtyRequested' => 'required|integer',
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
            'status' => 'DIAJUKAN',
            'notes' => $validated['notes'],
            'date' => $validated['date'],
            'requester' => $validated['requester'],
            'last_updated' => today(),
        ]);

        return response()->json($itemRequest, 201);
    }

    public function updateStatus(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'qtyAvailable' => 'required|integer',
            'qtyFulfilled' => 'required|integer',
            'deductStock' => 'nullable|array',
            'deductStock.code' => 'required_with:deductStock|string',
            'deductStock.qtyToDeduct' => 'required_with:deductStock|integer',
        ]);

        $itemRequest->update([
            'status' => $validated['status'],
            'qty_available' => $validated['qtyAvailable'],
            'qty_fulfilled' => $validated['qtyFulfilled'],
            'last_updated' => today(),
        ]);

        if (isset($validated['deductStock'])) {
            $stock = StockItem::where('code', $validated['deductStock']['code'])->first();
            if ($stock) {
                $stock->update([
                    'qty' => max(0, $stock->qty - $validated['deductStock']['qtyToDeduct']),
                    'last_updated' => today(),
                ]);
            }
        }

        return response()->json($itemRequest);
    }
}
