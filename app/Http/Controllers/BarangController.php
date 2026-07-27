<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\KodePersediaan;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    /**
     * Show master barang catalog (Petugas Persediaan only).
     */
    public function index(Request $request)
    {
        $this->authorizeRole('Petugas Persediaan');

        // Query HANYA dari stock_items — barang yang sudah masuk stok.
        // Kode persediaan master (yang belum pernah diupload) tidak ditampilkan
        // agar user tidak bingung melihat barang stok 0 yang tidak relevan.
        $query = Barang::where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('kategori_id')) {
            $query->whereHas('kategori', function ($q) use ($request) {
                $q->where('nama', $request->kategori_id);
            });
        }

        $perPage = $request->input('per_page', 10);
        $barangs   = $query->with('kategori')->orderBy('name')->paginate($perPage)->withQueryString();

        // Semua kategori dari database untuk dropdown filter
        $kategoris = KategoriBarang::orderBy('nama')->get();

        // Semua kode persediaan untuk dropdown (dengan relasi kategori)
        $kodePersediaans = KodePersediaan::with('kategoriBarang')->orderBy('kode')->get();

        return view('master-barang.index', compact('barangs', 'kategoris', 'kodePersediaans'));
    }

    /**
     * Search endpoint for dashboard Ketua Tim (read-only) and Petugas Persediaan.
     */
    public function search(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Allow Superadmin, Petugas Persediaan, and Ketua Tim Kerja
        $userRole = auth()->user()->role;
        $allowedRoles = ['Superadmin', 'Petugas Persediaan', 'Ketua Tim Kerja'];
        
        if (!in_array($userRole, $allowedRoles)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $queryStr = $request->input('query', '');

        $items = Barang::where('is_active', true)
            ->where(function($q) use ($queryStr) {
                $q->where('name',     'ilike', "%{$queryStr}%")
                  ->orWhere('code',     'ilike', "%{$queryStr}%")
                  ->orWhere('category', 'ilike', "%{$queryStr}%");
            })
            ->get();

        $mapped = $items->map(function($item) {
            $stok = $item->qty;
            
            // Resolve stock status
            if ($stok <= 0) {
                $status = 'Tidak Tersedia';
            } elseif ($stok <= 5) {
                $status = 'Stok Terbatas';
            } else {
                $status = 'Tersedia';
            }

            return [
                'kode_persediaan' => $item->code,
                'nama_barang' => $item->name,
                'kategori' => $item->category,
                'satuan' => $item->unit,
                'stok_tersedia' => $stok,
                'status_ketersediaan' => $status,
                'tanggal_update_terakhir' => $item->last_updated ? $item->last_updated->format('Y-m-d') : ($item->updated_at ? $item->updated_at->format('Y-m-d') : null),
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

        $request->validate([
            'name' => 'required|string|max:255',
            'kode_persediaan' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
        ]);

        $barang = Barang::findOrFail($id);

        // Cegah duplikasi nama (case-insensitive)
        $duplicate = Barang::whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => 'Nama barang sudah ada.'])->withInput()->with('edit_id', $id);
        }

        $barang->update([
            'name' => $request->name,
            'code' => $request->kode_persediaan ?? $barang->code,
            'unit' => $request->unit,
            'category' => $request->category ?? $barang->category,
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

    /**
     * Helper to enforce roles in controller.
     */
    protected function authorizeRole(string $role)
    {
        if (!auth()->check()) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        if (auth()->user()->role === 'Superadmin') {
            return;
        }

        if (auth()->user()->role !== $role) {
            abort(403, 'Akses ditolak. Halaman ini hanya boleh diakses oleh ' . $role);
        }
    }
}
