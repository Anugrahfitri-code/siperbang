<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index()
    {
        return response()->json(Receipt::with('items')->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoiceNo' => 'required|string',
            'storeName' => 'required|string',
            'date' => 'required|date',
            'isTaxed' => 'required|boolean',
            'taxRate' => 'required|numeric',
            'subtotal' => 'required|numeric',
            'taxAmount' => 'required|numeric',
            'total' => 'required|numeric',
            'isVerified' => 'required|boolean',
            'status' => 'required|string',
            'method' => 'nullable|string',
            'bastName' => 'nullable|string',
            'bastDate' => 'nullable|date',
            'items' => 'required|array',
        ]);

        $receipt = Receipt::create([
            'invoice_no' => $validated['invoiceNo'],
            'store_name' => $validated['storeName'],
            'date' => $validated['date'],
            'is_taxed' => $validated['isTaxed'],
            'tax_rate' => $validated['taxRate'],
            'subtotal' => $validated['subtotal'],
            'tax_amount' => $validated['taxAmount'],
            'total' => $validated['total'],
            'is_verified' => $validated['isVerified'],
            'status' => $validated['status'],
            'method' => $validated['method'] ?? null,
            'bast_name' => $validated['bastName'] ?? null,
            'bast_date' => $validated['bastDate'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $receipt->items()->create([
                'name' => $item['name'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        return response()->json($receipt->load('items'), 201);
    }
}
