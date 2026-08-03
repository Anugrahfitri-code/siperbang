<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoryLog;
use App\Models\Receipt;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = HistoryLog::where('action', 'not like', '%Login%');

        if ($user && ! in_array(strtolower($user->role), ['admin', 'superadmin', 'petugas persediaan'])) {
            $query->where(function ($q) use ($user) {
                // Return if user_id explicitly matches or actor contains user's name
                $q->where('user_id', $user->id)
                    ->orWhere('actor', 'like', "%{$user->name}%");
            });
        }

        return response()->json(
            $query->orderBy('created_at', 'desc')->get()
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'actor' => $l->actor,
                    'action' => $l->action,
                    'details' => $l->details,
                    'ip_address' => $l->ip_address,
                    'metadata' => $l->metadata,
                    'created_at' => $l->created_at,
                ])
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'max:120'],
            'details' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();
        $log = HistoryLog::create([
            ...$validated,
            'actor' => $user->name,
            'user_id' => $user->id,
        ]);

        return response()->json($log, 201);
    }

    /**
     * Fitur Ekspor Rekap Kuitansi Mentah (Aman dari kendala Composer/Vendor)
     */
    public function exportExcel(Request $request)
    {
        $year = $request->query('year', '2026');
        $month = $request->query('month', 'All');
        $search = $request->query('search', '');
        $isAnnual = $request->query('annual') === 'true';

        $query = Receipt::with('items.inventoryCodeMaster')->where('is_verified', true);

        if ($year !== 'All') {
            $query->whereYear('date', $year);
        }

        if (! $isAnnual && $month !== 'All') {
            $query->whereMonth('date', $month);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $receipts = $query->orderBy('date', 'desc')->get();

        $filename = $isAnnual ? "SIPERBANG_REKAP_TAHUNAN_{$year}.csv" : "SIPERBANG_REKAP_BULANAN_{$month}_{$year}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($receipts, $isAnnual) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'No Nota',
                'Tanggal',
                'Nama Toko',
                'Kode Persediaan',
                'Nama Barang',
                'Jumlah',
                'Satuan',
                'Harga Satuan',
                'Subtotal',
                'PPN',
                'Total',
                'Metode Pengadaan',
                'BAST Nama',
                'BAST Tanggal',
                'Tanggal Buku',
            ]);

            foreach ($receipts as $rc) {
                foreach ($rc->items as $it) {
                    $subtotal = $it->qty * $it->price;
                    $taxAmount = $rc->is_taxed ? round($subtotal * ($rc->tax_rate / 100)) : 0;
                    $total = $subtotal + $taxAmount;

                    fputcsv($file, [
                        $rc->invoice_no,
                        $rc->date,
                        $rc->store_name,
                        $it->inventory_code,
                        $it->name,
                        $it->qty,
                        $it->unit,
                        $it->price,
                        $subtotal,
                        $taxAmount,
                        $total,
                        $rc->method,
                        $isAnnual ? '' : ($rc->bast_name ?? '-'),
                        $isAnnual ? '' : ($rc->bast_date ?? '-'),
                        $isAnnual ? '' : $rc->date,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
