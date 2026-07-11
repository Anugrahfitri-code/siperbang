<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockItem;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        return response()->json(StockItem::orderBy('name')->get());
    }

    public function bulkStore(Request $request)
    {
        $stocks = $request->validate([
            '*.code' => 'required|string',
            '*.category' => 'required|string',
            '*.name' => 'required|string',
            '*.qty' => 'required|integer',
            '*.unit' => 'required|string',
            '*.lastUpdated' => 'nullable|date',
        ]);

        foreach ($stocks as $stockData) {
            $stock = StockItem::where('code', $stockData['code'])->first();
            if ($stock) {
                $stock->update([
                    'qty' => $stock->qty + $stockData['qty'],
                    'last_updated' => $stockData['lastUpdated'] ?? now(),
                ]);
            } else {
                StockItem::create([
                    'code' => $stockData['code'],
                    'category' => $stockData['category'],
                    'name' => $stockData['name'],
                    'qty' => $stockData['qty'],
                    'unit' => $stockData['unit'],
                    'last_updated' => $stockData['lastUpdated'] ?? now(),
                ]);
            }
        }

        return response()->json(['message' => 'Stocks uploaded successfully']);
    }
}
