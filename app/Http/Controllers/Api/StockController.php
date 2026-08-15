<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\KodePersediaan;
use App\Models\StockItem;
use App\Support\Inventory\OfficeInventoryCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    /**
     * Full stock list - Petugas Persediaan & Superadmin only.
     */
    public function index()
    {
        $query = StockItem::query();
        $this->applyOfficeCodeScope($query);

        $items = $query->orderBy('name')->get();

        $items->each(function (StockItem $item): void {
            $item->code = OfficeInventoryCatalog::normalizeCode($item->code);
            $item->category = $item->category
                ?? OfficeInventoryCatalog::categoryForCode($item->code)
                ?? OfficeInventoryCatalog::canonicalCategory($item->category);
        });

        return response()->json($items);
    }

    /**
     * Read-only paginated stock search.
     * Accessible by all authenticated roles.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $requestedCategory = trim((string) ($validated['category'] ?? ''));
        $status = (string) ($validated['status'] ?? '');
        $perPage = (int) ($validated['per_page'] ?? 20);

        $query = Barang::query()->where('is_active', true);
        $this->applyOfficeCodeScope($query);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('code', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%")
                    ->orWhere('category', 'ilike', "%{$search}%");
            });
        }

        if ($requestedCategory !== '') {
            $group = OfficeInventoryCatalog::groupForCategory($requestedCategory);

            if ($group === null) {
                $query->whereRaw('1 = 0');
            } else {
                $this->applyCategoryCodeScope($query, $group);
            }
        }

        if ($status === 'tersedia') {
            $query->where('qty', '>', 5);
        } elseif ($status === 'terbatas') {
            $query->whereBetween('qty', [1, 5]);
        } elseif ($status === 'kosong') {
            $query->where('qty', '<=', 0);
        }

        $paginated = $query->orderBy('name')->paginate($perPage);

        $items = collect($paginated->items())->map(fn (Barang $item): array => [
            'id' => $item->id,
            'kode' => OfficeInventoryCatalog::normalizeCode($item->code),
            'nama' => $item->name,
            'kategori' => $item->category
                ?? OfficeInventoryCatalog::categoryForCode($item->code)
                ?? OfficeInventoryCatalog::canonicalCategory($item->category)
                ?? '-',
            'satuan' => $item->unit,
            'stok' => $item->qty,
            'status_stok' => $this->resolveStatus($item->qty),
            'update_terakhir' => $item->last_updated?->toDateString()
                ?? $item->updated_at?->toDateString(),
        ]);

        return response()->json([
            'data' => $items,
            'categories' => OfficeInventoryCatalog::categoryNames(),
            'category_options' => OfficeInventoryCatalog::categoryOptions(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function bulkStore(Request $request)
    {
        $stocks = $request->validate([
            '*.code' => 'required|string|max:20',
            '*.category' => 'nullable|string|max:255',
            '*.name' => 'required|string|max:255',
            '*.qty' => 'required|integer',
            '*.unit' => 'required|string|max:50',
            '*.lastUpdated' => 'nullable|date',
        ]);

        foreach ($stocks as $index => $stockData) {
            $code = OfficeInventoryCatalog::normalizeCode($stockData['code']);
            $category = OfficeInventoryCatalog::categoryForCode($code);

            $isOfficialCode = $category !== null
                && KodePersediaan::query()->where('kode', $code)->exists();

            if (! $isOfficialCode) {
                throw ValidationException::withMessages([
                    "{$index}.code" => 'Kode wajib berasal dari master resmi kelompok 1.01.03.',
                ]);
            }

            $stock = StockItem::query()
                ->where('code', $code)
                ->where('name', trim($stockData['name']))
                ->first();

            if ($stock) {
                $stock->update([
                    'category' => $category,
                    'qty' => $stock->qty + $stockData['qty'],
                    'unit' => trim($stockData['unit']),
                    'last_updated' => $stockData['lastUpdated'] ?? now(),
                ]);
            } else {
                StockItem::create([
                    'code' => $code,
                    'category' => $category,
                    'name' => trim($stockData['name']),
                    'qty' => $stockData['qty'],
                    'unit' => trim($stockData['unit']),
                    'last_updated' => $stockData['lastUpdated'] ?? now(),
                ]);
            }
        }

        return response()->json(['message' => 'Stocks uploaded successfully']);
    }

    private function resolveStatus(int $qty): string
    {
        if ($qty <= 0) {
            return 'Tidak Tersedia';
        }

        if ($qty <= 5) {
            return 'Stok Terbatas';
        }

        return 'Tersedia';
    }

    private function applyOfficeCodeScope(Builder $query): void
    {
        $query->where(function (Builder $builder): void {
            $builder->where(
                'code',
                'like',
                OfficeInventoryCatalog::codePrefix().'%',
            )->orWhere('code', 'like', '1.01.03.%');
        });
    }

    private function applyCategoryCodeScope(Builder $query, string $group): void
    {
        $query->where(function (Builder $builder) use ($group): void {
            $builder->where(
                'code',
                'like',
                OfficeInventoryCatalog::codePrefix().$group.'%',
            )->orWhere('code', 'like', '1.01.03.'.$group.'%');
        });
    }
}
