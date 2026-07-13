<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    /**
     * Show master barang catalog (Petugas Persediaan only).
     */
    public function index()
    {
        $this->authorizeRole('Petugas Persediaan');
        $items = Barang::orderBy('name')->get();
        return view('master-barang.index', compact('items'));
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
                $q->where('name', 'like', "%{$queryStr}%")
                  ->orWhere('code', 'like', "%{$queryStr}%")
                  ->orWhere('category', 'like', "%{$queryStr}%");
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
     * Helper to enforce roles in controller.
     */
    protected function authorizeRole(string $role)
    {
        if (!auth()->check()) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        if (auth()->user()->role !== $role) {
            abort(403, 'Akses ditolak. Halaman ini hanya boleh diakses oleh ' . $role);
        }
    }
}
