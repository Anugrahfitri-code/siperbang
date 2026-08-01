<?php

namespace App\Services\Receipt;

use App\Models\KodePersediaan;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\StockHistory;
use App\Models\StockItem;
use App\Support\Inventory\OfficeInventoryCatalog;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReceiptStockSyncService
{
    /**
     * Ganti seluruh item kuitansi dan sinkronkan kontribusi stoknya.
     *
     * Method ini harus dipanggil di dalam transaksi database.
     *
     * @param array<int, array{
     *     name: string,
     *     qty: int,
     *     unit: string,
     *     inventory_code: string,
     *     stock_item_id: int|null,
     *     price: float
     * }> $items
     */
    public function replaceReceiptItems(
        Receipt $receipt,
        array $items,
    ): void {
        $receipt->loadMissing('items');

        /*
         * Jika kuitansi lama diperbarui, kembalikan dahulu stok
         * yang pernah ditambahkan oleh item lama. Setelah itu baru
         * terapkan item terbaru. Dengan cara ini verifikasi ulang
         * tidak menambah stok dua kali.
         */
        $this->reverseItems(
            $receipt,
            $receipt->items,
            'Penyesuaian ulang item kuitansi',
        );

        $receipt->items()->delete();

        foreach ($items as $index => $item) {
            $stockItem = $this->resolveStockItem(
                $item,
                $index,
            );

            $receiptItem = $receipt->items()->create([
                'name' => $item['name'],
                'qty' => $item['qty'],
                'unit' => $item['unit'],
                'inventory_code' => $item['inventory_code'],
                'stock_item_id' => $stockItem->id,
                'price' => $item['price'],
                'subtotal' => round(
                    $item['qty'] * $item['price'],
                    2,
                ),
            ]);

            $this->increaseStock(
                $receipt,
                $receiptItem,
                $stockItem,
            );
        }
    }

    /**
     * Batalkan kontribusi stok seluruh item pada satu kuitansi.
     *
     * Method ini harus dipanggil di dalam transaksi database.
     */
    public function reverseReceipt(
        Receipt $receipt,
        string $reason,
    ): void {
        $receipt->loadMissing('items');

        $this->reverseItems(
            $receipt,
            $receipt->items,
            $reason,
        );
    }

    /**
     * @param array{
     *     name: string,
     *     qty: int,
     *     unit: string,
     *     inventory_code: string,
     *     stock_item_id: int|null,
     *     price: float
     * } $item
     */
    private function resolveStockItem(
        array $item,
        int $index,
    ): StockItem {
        $selectedId = $item['stock_item_id'] ?? null;

        if ($selectedId !== null) {
            $selected = StockItem::query()
                ->lockForUpdate()
                ->find($selectedId);

            if ($selected === null || ! $selected->is_active) {
                throw ValidationException::withMessages([
                    "items.{$index}.stockItemId" => [
                        'Barang master yang dipilih sudah tidak tersedia. '
                        .'Pilih kembali barang dari daftar master.',
                    ],
                ]);
            }

            // Validasi dihapus: Jika pengguna secara manual memilih stock_item_id dari dropdown,
            // kita asumsikan mereka tahu apa yang mereka lakukan meskipun satuan/nama berbeda.
            return $selected;
        }

        /*
         * Pengguna boleh mengetik barang baru.
         *
         * Baris kode resmi dikunci lebih dahulu. Ini membuat dua proses
         * verifikasi yang datang bersamaan untuk kode yang sama berjalan
         * berurutan, sehingga tidak membuat master barang ganda.
         */
        $normalisedCode = $this->normaliseCode(
            $item['inventory_code'],
        );

        $normalisedUnit = $this->normaliseUnit(
            $item['unit'],
        );

        $normalisedName = $this->normaliseName(
            $item['name'],
        );

        $codeMaster = KodePersediaan::query()
            ->with('kategoriBarang:id,nama')
            ->where('kode', $normalisedCode)
            ->where('kode', 'like', OfficeInventoryCatalog::codePrefix().'%')
            ->lockForUpdate()
            ->first();

        if ($codeMaster === null) {
            throw ValidationException::withMessages([
                "items.{$index}.inventoryCode" => [
                    'Kode persediaan tidak ditemukan pada master resmi kelompok 1.01.03.',
                ],
            ]);
        }

        /*
         * Satu kode persediaan dapat dipakai beberapa nama barang.
         * Karena itu pencocokan dilakukan dengan kombinasi kode,
         * nama yang dinormalisasi, dan satuan.
         */
        $existing = StockItem::query()
            ->where('code', $normalisedCode)
            ->whereRaw(
                'UPPER(TRIM(unit)) = ?',
                [$normalisedUnit],
            )
            ->lockForUpdate()
            ->get()
            ->first(
                fn (StockItem $candidate): bool => $this->normaliseName($candidate->name)
                    === $normalisedName
            );

        if ($existing !== null) {
            return $existing;
        }

        return StockItem::create([
            'category' => OfficeInventoryCatalog::categoryForCode(
                $normalisedCode,
            ) ?? $codeMaster->kategoriBarang?->nama
                ?? OfficeInventoryCatalog::groups()['99'],
            'code' => $normalisedCode,
            'name' => trim($item['name']),
            'qty' => 0,
            'unit' => $normalisedUnit,
            'last_updated' => now()->toDateString(),
            'is_active' => true,
        ]);
    }

    private function increaseStock(
        Receipt $receipt,
        ReceiptItem $receiptItem,
        StockItem $stockItem,
    ): void {
        $qtyBefore = (int) $stockItem->qty;
        $qtyChange = (int) $receiptItem->qty;
        $qtyAfter = $qtyBefore + $qtyChange;

        $stockItem->update([
            'qty' => $qtyAfter,
            'last_updated' => now()->toDateString(),
            'is_active' => true,
        ]);

        StockHistory::create([
            'stock_item_id' => $stockItem->id,
            'stok_upload_id' => null,
            'qty_change' => $qtyChange,
            'qty_before' => $qtyBefore,
            'qty_after' => $qtyAfter,
            'type' => 'Verifikasi Kuitansi',
            'notes' => sprintf(
                'Penambahan stok dari kuitansi #%d (%s) item #%d.',
                $receipt->id,
                $receipt->invoice_no ?: 'tanpa nomor',
                $receiptItem->id,
            ),
        ]);
    }

    /**
     * @param  Collection<int, ReceiptItem>  $items
     */
    private function reverseItems(
        Receipt $receipt,
        Collection $items,
        string $reason,
    ): void {
        foreach ($items as $receiptItem) {
            if ($receiptItem->stock_item_id === null) {
                continue;
            }

            $stockItem = StockItem::query()
                ->lockForUpdate()
                ->find($receiptItem->stock_item_id);

            if ($stockItem === null) {
                continue;
            }

            $qtyBefore = (int) $stockItem->qty;
            $requestedReduction = (int) $receiptItem->qty;
            $actualReduction = min(
                $qtyBefore,
                $requestedReduction,
            );
            $qtyAfter = $qtyBefore - $actualReduction;
            $shortfall = $requestedReduction - $actualReduction;

            $stockItem->update([
                'qty' => $qtyAfter,
                'last_updated' => now()->toDateString(),
            ]);

            $notes = sprintf(
                '%s untuk kuitansi #%d (%s), item #%d.',
                $reason,
                $receipt->id,
                $receipt->invoice_no ?: 'tanpa nomor',
                $receiptItem->id,
            );

            if ($shortfall > 0) {
                $notes .= sprintf(
                    ' Stok sudah terpakai sebanyak %d sebelum pembalikan; '
                    .'stok disetel menjadi 0.',
                    $shortfall,
                );
            }

            StockHistory::create([
                'stock_item_id' => $stockItem->id,
                'stok_upload_id' => null,
                'qty_change' => -$actualReduction,
                'qty_before' => $qtyBefore,
                'qty_after' => $qtyAfter,
                'type' => 'Pembatalan Kuitansi',
                'notes' => $notes,
            ]);
        }
    }

    private function normaliseCode(string $code): string
    {
        return preg_replace('/\D/', '', $code) ?? '';
    }

    private function normaliseUnit(string $unit): string
    {
        return mb_strtoupper(trim($unit));
    }

    private function normaliseName(string $name): string
    {
        $value = mb_strtolower(trim($name));

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private function nameSimilarity(
        string $left,
        string $right,
    ): float {
        $leftNormalised = $this->normaliseName($left);
        $rightNormalised = $this->normaliseName($right);

        if ($leftNormalised === $rightNormalised) {
            return 1.0;
        }

        if ($leftNormalised === '' || $rightNormalised === '') {
            return 0.0;
        }

        similar_text(
            $leftNormalised,
            $rightNormalised,
            $percentage,
        );

        return $percentage / 100;
    }
}
