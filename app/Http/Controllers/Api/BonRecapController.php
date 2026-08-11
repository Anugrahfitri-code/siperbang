<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemRequest;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BonRecapController extends Controller
{
    /**
     * Preview semua barang yang masih harus dibeli oleh Petugas Persediaan.
     *
     * Barang yang sama + satuan yang sama digabung agar menjadi
     * daftar pengadaan yang ringkas.
     */
    public function procurementPreview(): JsonResponse
    {
        $items = ItemRequest::query()
            ->forTeamLeaders()
            ->needsProcurement()
            ->with('user:id,name')
            ->orderBy('item_name')
            ->get([
                'id',
                'user_id',
                'bon_no',
                'requester',
                'item_name',
                'unit',
                'qty_to_procure',
            ]);

        $rows = $items
            ->groupBy(function (ItemRequest $item) {
                return strtolower(trim($item->item_name))
                    .'|'
                    .strtolower(trim($item->unit));
            })
            ->map(function ($group) {
                /** @var ItemRequest $first */
                $first = $group->first();

                return [
                    'item_name' => $first->item_name,
                    'unit' => $first->unit,

                    'qty_to_procure' => (int) $group->sum(
                        'qty_to_procure'
                    ),

                    'bon_count' => $group
                        ->pluck('bon_no')
                        ->filter()
                        ->unique()
                        ->count(),

                    'bon_numbers' => $group
                        ->pluck('bon_no')
                        ->filter()
                        ->unique()
                        ->values(),

                    'requesters' => $group
                        ->map(
                            fn (ItemRequest $item) => $item->user?->name
                                ?: $item->requester
                        )
                        ->filter()
                        ->unique()
                        ->values(),
                ];
            })
            ->sortBy(
                fn (array $row) => strtolower($row['item_name'])
            )
            ->values();

        return response()->json([
            'data' => $rows,

            'total_item_types' => $rows->count(),

            'total_qty_to_procure' => (int) $rows->sum(
                'qty_to_procure'
            ),
        ]);
    }

    /**
     * Ekspor Rekap BON Pengajuan per Ketua Tim.
     *
     * Hanya Superadmin yang mempunyai route menuju method ini.
     */
    public function exportPdf(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);

        /*
         * Pastikan user yang dipilih benar-benar Ketua Tim.
         * Jangan hanya mempercayai user_id dari frontend.
         */
        $leader = User::query()
            ->whereKey($validated['user_id'])
            ->whereIn(
                'role',
                ['Ketua Tim', 'Ketua Tim Kerja']
            )
            ->firstOrFail();

        /*
         * Ambil item Ketua Tim terpilih yang sudah disetujui /
         * masuk proses pemenuhan.
         */
        $items = ItemRequest::query()
            ->forTeamLeaders()
            ->approvedForBonRecap()
            ->where('user_id', $leader->id)
            ->with('user:id,name')
            ->orderBy('date')
            ->orderBy('bon_no')
            ->orderBy('id')
            ->get([
                'id',
                'user_id',
                'bon_no',
                'date',
                'requester',
                'item_name',
                'unit',
                'qty_requested',
                'qty_fulfilled',
                'qty_to_procure',
                'status',
            ])
            ->filter(
                fn (ItemRequest $item) => $item->approved_recap_qty > 0
            )
            ->values();

        $options = new Options;

        /*
         * DejaVu Sans sudah mendukung karakter yang lebih aman
         * daripada font standar PDF.
         */
        $options->set(
            'defaultFont',
            'DejaVu Sans'
        );

        /*
         * Tidak membutuhkan resource internet.
         */
        $options->set(
            'isRemoteEnabled',
            false
        );

        $dompdf = new Dompdf($options);

        $html = view(
            'pdf.bon-recap',
            [
                'leader' => $leader,
                'items' => $items,
            ]
        )->render();

        $dompdf->loadHtml($html);

        /*
         * Landscape supaya 7 kolom tetap nyaman dibaca.
         */
        $dompdf->setPaper(
            'A4',
            'landscape'
        );

        $dompdf->render();

        $safeName = Str::slug(
            $leader->name
        ) ?: 'ketua-tim';

        $fileName =
            'rekap-bon-pengajuan-'
            .$safeName
            .'.pdf';

        return response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' => 'attachment; filename="'
                    .$fileName
                    .'"',

                'Cache-Control' => 'private, no-store, max-age=0',
            ]
        );
    }
}
