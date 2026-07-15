<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\StockItem;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /**
     * Full stock list — Petugas Persediaan & Superadmin only.
     */
    public function index()
    {
        return response()->json(StockItem::orderBy('name')->get());
    }

    /**
     * Read-only paginated stock search.
     * Accessible by ALL authenticated roles (Ketua Tim, Petugas, Superadmin).
     *
     * Query params:
     *   q        – free-text search on name, code, or category
     *   category – exact category filter
     *   status   – 'tersedia' | 'terbatas' | 'kosong'
     *   per_page – items per page (default 20, max 100)
     *   page     – page number
     */
    public function search(Request $request)
    {
        $q        = trim($request->input('q', ''));
        $category = trim($request->input('category', ''));
        $status   = $request->input('status', '');
        $perPage  = min((int) $request->input('per_page', 20), 100);

        $query = Barang::where('is_active', true);

        // Free-text: code, name, or category — case-insensitive (PostgreSQL ILIKE)
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('code',     'ilike', "%{$q}%")
                    ->orWhere('name',     'ilike', "%{$q}%")
                    ->orWhere('category', 'ilike', "%{$q}%");
            });
        }

        // Exact category filter
        if ($category !== '') {
            $query->where('category', $category);
        }

        // Stock status filter
        if ($status === 'tersedia') {
            $query->where('qty', '>', 5);
        } elseif ($status === 'terbatas') {
            $query->whereBetween('qty', [1, 5]);
        } elseif ($status === 'kosong') {
            $query->where('qty', '<=', 0);
        }

        $paginated = $query->orderBy('name')->paginate($perPage);

        // Map to a clean read-only shape
        // NOTE: 'id' is included so the BON Digital form can populate barang_id
        $items = collect($paginated->items())->map(fn (Barang $item) => [
            'id'                => $item->id,
            'kode'              => $item->code,
            'nama'              => $item->name,
            'kategori'          => $item->category ?? '-',
            'satuan'            => $item->unit,
            'stok'              => $item->qty,
            'status_stok'       => $this->resolveStatus($item->qty),
            'update_terakhir'   => $item->last_updated?->toDateString()
                                    ?? $item->updated_at?->toDateString(),
        ]);

        // Distinct categories for filter dropdown (only categories in stock)
        $categories = Barang::where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'data'       => $items,
            'categories' => $categories,
            'meta'       => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ]);
    }

    private function resolveStatus(int $qty): string
    {
        if ($qty <= 0) return 'Tidak Tersedia';
        if ($qty <= 5) return 'Stok Terbatas';
        return 'Tersedia';
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
            $stock = StockItem::where('code', $stockData['code'])->where('name', $stockData['name'])->first();
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
