<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InventoryCodeSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryCodeController extends Controller
{
    public function index(
        Request $request,
        InventoryCodeSuggestionService $service,
    ): JsonResponse {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
        ]);

        $query = mb_strtolower(trim((string) ($validated['q'] ?? '')));

        $items = $service->officeCodes()
            ->filter(function ($item) use ($query): bool {
                if ($query === '') {
                    return true;
                }

                return str_contains(mb_strtolower($item->kode), $query)
                    || str_contains(mb_strtolower($item->nama_barang), $query)
                    || str_contains(
                        mb_strtolower((string) $item->kategoriBarang?->nama),
                        $query,
                    );
            })
            ->values()
            ->map(fn ($item): array => [
                'code' => $item->kode,
                'formatted_code' => InventoryCodeSuggestionService::formatCode($item->kode),
                'description' => $item->nama_barang,
                'category' => $item->kategoriBarang?->nama,
            ]);

        return response()->json([
            'data' => $items,
        ]);
    }
}
