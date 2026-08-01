<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\KodePersediaan;
use App\Support\Inventory\OfficeInventoryCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangController extends Controller
{
    /**
     * Show master barang catalog (Petugas Persediaan only).
     */
    public function index(Request $request)
    {
        $this->authorizeRole('Petugas Persediaan');

        $query = Barang::query()->where('is_active', true);
        $this->applyOfficeCodeScope($query);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        $requestedCategory = trim((string) $request->input('kategori_id', ''));
        $selectedCategory = OfficeInventoryCatalog::canonicalCategory(
            $requestedCategory,
        );
        $selectedGroup = OfficeInventoryCatalog::groupForCategory(
            $selectedCategory,
        );

        if ($requestedCategory !== '' && $selectedGroup === null) {
            $query->whereRaw('1 = 0');
        } elseif ($selectedGroup !== null) {
            $this->applyCategoryCodeScope($query, $selectedGroup);
        }

        $perPage = max(1, min((int) $request->input('per_page', 10), 100));
        $barangs = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $barangs->getCollection()->each(function (Barang $barang): void {
            $barang->setAttribute(
                'canonical_category',
                OfficeInventoryCatalog::categoryForCode($barang->code)
                    ?? OfficeInventoryCatalog::canonicalCategory($barang->category)
                    ?? $barang->category,
            );
        });

        $categoryOptions = collect(OfficeInventoryCatalog::categoryOptions());

        $kodePersediaans = KodePersediaan::query()
            ->with('kategoriBarang')
            ->where('kode', 'like', OfficeInventoryCatalog::codePrefix().'%')
            ->orderBy('kode')
            ->get();

        return view('master-barang.index', compact(
            'barangs',
            'categoryOptions',
            'kodePersediaans',
            'selectedCategory',
        ));
    }

    /**
     * Search endpoint for dashboard Ketua Tim (read-only) and Petugas Persediaan.
     */
    public function search(Request $request)
    {
        if (! auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $allowedRoles = ['Superadmin', 'Petugas Persediaan', 'Ketua Tim Kerja'];

        if (! in_array(auth()->user()->role, $allowedRoles, true)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $queryString = trim((string) $request->input('query', ''));
        $query = Barang::query()->where('is_active', true);
        $this->applyOfficeCodeScope($query);

        if ($queryString !== '') {
            $query->where(function (Builder $builder) use ($queryString): void {
                $builder->where('name', 'ilike', "%{$queryString}%")
                    ->orWhere('code', 'ilike', "%{$queryString}%")
                    ->orWhere('category', 'ilike', "%{$queryString}%");
            });
        }

        $items = $query->orderBy('name')->get();

        $mapped = $items->map(function (Barang $item): array {
            $stock = $item->qty;

            if ($stock <= 0) {
                $status = 'Tidak Tersedia';
            } elseif ($stock <= 5) {
                $status = 'Stok Terbatas';
            } else {
                $status = 'Tersedia';
            }

            return [
                'kode_persediaan' => OfficeInventoryCatalog::normalizeCode($item->code),
                'nama_barang' => $item->name,
                'kategori' => OfficeInventoryCatalog::categoryForCode($item->code)
                    ?? OfficeInventoryCatalog::canonicalCategory($item->category)
                    ?? $item->category,
                'satuan' => $item->unit,
                'stok_tersedia' => $stock,
                'status_ketersediaan' => $status,
                'tanggal_update_terakhir' => $item->last_updated
                    ? $item->last_updated->format('Y-m-d')
                    : $item->updated_at?->format('Y-m-d'),
            ];
        });

        return response()->json($mapped);
    }

    /**
     * Update an existing barang.
     */
    public function update(Request $request, $id)
    {
        $this->authorizeRole('Petugas Persediaan');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'kode_persediaan' => 'required|string|max:20',
            'unit' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('edit_id', $id);
        }

        $validated = $validator->validated();

        $normalizedCode = OfficeInventoryCatalog::normalizeCode(
            $validated['kode_persediaan'],
        );

        if (! OfficeInventoryCatalog::isOfficeCode($normalizedCode)) {
            return back()
                ->withErrors([
                    'kode_persediaan' => 'Kode persediaan wajib berasal dari kelompok 1.01.03.',
                ])
                ->withInput()
                ->with('edit_id', $id);
        }

        $codeMaster = KodePersediaan::query()
            ->where('kode', $normalizedCode)
            ->where('kode', 'like', OfficeInventoryCatalog::codePrefix().'%')
            ->first();

        if ($codeMaster === null) {
            return back()
                ->withErrors([
                    'kode_persediaan' => 'Kode persediaan tidak ditemukan pada master resmi 1.01.03.',
                ])
                ->withInput()
                ->with('edit_id', $id);
        }

        $category = OfficeInventoryCatalog::categoryForCode($normalizedCode);

        if ($category === null) {
            return back()
                ->withErrors([
                    'kode_persediaan' => 'Subkategori kode persediaan tidak dikenali.',
                ])
                ->withInput()
                ->with('edit_id', $id);
        }

        $barang = Barang::findOrFail($id);

        $duplicate = Barang::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['name' => 'Nama barang sudah ada.'])
                ->withInput()
                ->with('edit_id', $id);
        }

        $barang->update([
            'name' => trim($validated['name']),
            'code' => $normalizedCode,
            'unit' => trim($validated['unit']),
            'category' => $category,
        ]);

        return back()->with('success', 'Barang berhasil diperbarui.');
    }

    /**
     * Delete a barang, only if it has no transaction history.
     */
    public function destroy($id)
    {
        $this->authorizeRole('Petugas Persediaan');

        $barang = Barang::findOrFail($id);
        $barang->update(['is_active' => false]);

        return back()->with('success', 'Barang berhasil dihapus.');
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

    /**
     * Helper to enforce roles in controller.
     */
    protected function authorizeRole(string $role): void
    {
        if (! auth()->check()) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        if (auth()->user()->role === 'Superadmin') {
            return;
        }

        if (auth()->user()->role !== $role) {
            abort(403, 'Akses ditolak. Halaman ini hanya boleh diakses oleh '.$role);
        }
    }
}
