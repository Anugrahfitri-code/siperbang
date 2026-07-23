<?php

namespace App\Services;

use App\Models\Receipt;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class ReceiptExcelExportService
{
    /*
     * Sheet contoh tanpa pajak.
     *
     * Sheet ini mempunyai enam baris barang:
     * baris 5 sampai 10, kemudian total pada baris 11.
     */
    private const NON_TAX_TEMPLATE_SHEET = '130126 SS';

    /*
     * Sheet contoh dengan pajak.
     *
     * Sheet ini mempunyai lima belas baris barang:
     * baris 5 sampai 19, kemudian total pada baris 20.
     */
    private const TAX_TEMPLATE_SHEET = '170626 NA';

    private const ITEM_START_ROW = 5;

    private const NON_TAX_TEMPLATE_ITEM_COUNT = 6;

    private const TAX_TEMPLATE_ITEM_COUNT = 15;

    /**
     * @param Collection<int, Receipt> $receipts
     *
     * @return array{
     *     path: string,
     *     filename: string
     * }
     */
    public function create(Collection $receipts): array
    {
        // Tingkatkan batas memori untuk mencegah Out of Memory (OOM) saat export
        ini_set('memory_limit', '512M');

        if ($receipts->isEmpty()) {
            throw new RuntimeException(
                'Tidak ada kuitansi yang dapat diekspor.'
            );
        }

        $templatePath = public_path(
            'templates/Belanja Persediaan 2026.xlsx'
        );

        if (! is_file($templatePath)) {
            throw new RuntimeException(
                'Template Belanja Persediaan 2026.xlsx tidak ditemukan.'
            );
        }

        /*
         * Strategi: load template sebagai workbook ekspor, kemudian
         * gunakan duplicateWorksheetByTitle() untuk menduplikasi sheet
         * contoh per kuitansi. duplicateWorksheetByTitle() adalah
         * satu-satunya API PhpSpreadsheet yang menduplikasi sheet
         * beserta style-index-nya dalam workbook yang sama.
         *
         * Setelah semua kuitansi diproses, sheet-sheet asli template
         * dihapus sehingga hanya sheet hasil duplikasi yang tersisa.
         */
        $workbook = IOFactory::load($templatePath);

        $workbook
            ->getProperties()
            ->setCreator('SIPERBANG')
            ->setLastModifiedBy('SIPERBANG')
            ->setTitle('Belanja Persediaan')
            ->setSubject(
                'Ekspor kuitansi belanja persediaan'
            );

        /*
         * Bersihkan sheet yang tidak akan dipakai sebagai template
         * sedini mungkin untuk menghemat memori.
         */
        $keepTemplates = [
            self::TAX_TEMPLATE_SHEET,
            self::NON_TAX_TEMPLATE_SHEET,
        ];

        foreach (
            array_reverse($workbook->getSheetNames())
            as $name
        ) {
            if (!in_array($name, $keepTemplates, true)) {
                $sheet = $workbook->getSheetByName($name);

                if ($sheet instanceof Worksheet) {
                    $idx = $workbook->getIndex($sheet);
                    $workbook->removeSheetByIndex($idx);
                }
            }
        }

        /*
         * Catat nama sheet asli yang tersisa sebelum kita
         * menambahkan duplikasi baru.
         */
        $originalSheetNames = $workbook->getSheetNames();

        $usedSheetTitles = [];

        foreach (
            $receipts->values()
            as $receipt
        ) {
            $receipt->loadMissing('items');

            if ($receipt->items->isEmpty()) {
                throw new RuntimeException(
                    "Kuitansi {$receipt->id} "
                    . 'tidak mempunyai barang.'
                );
            }

            $isTaxed = (
                (bool) $receipt->is_taxed
                && (float) $receipt->tax_rate > 0
            );

            $templateSheetName = $isTaxed
                ? self::TAX_TEMPLATE_SHEET
                : self::NON_TAX_TEMPLATE_SHEET;

            /*
             * duplicateWorksheetByTitle() menduplikasi sheet
             * beserta seluruh style-nya dalam workbook yang sama
             * dan menambahkannya di akhir.
             *
             * Judul yang dikembalikan ditambahi suffix otomatis
             * (misalnya "130126 SS 1"), jadi kita rename setelah
             * duplikasi.
             */
            $newSheet = $workbook->duplicateWorksheetByTitle(
                $templateSheetName
            );

            if (! $newSheet instanceof Worksheet) {
                throw new RuntimeException(
                    "Gagal menduplikasi sheet template "
                    . "{$templateSheetName}."
                );
            }

            $sheetTitle =
                $this->makeUniqueSheetTitle(
                    $receipt,
                    $usedSheetTitles,
                );

            $newSheet->setTitle($sheetTitle, false);

            $this->populateSheet(
                $newSheet,
                $receipt,
                $isTaxed,
            );
        }

        /*
         * Hapus semua sheet asli template (iterasi mundur supaya
         * index tidak bergeser saat removeSheetByIndex dipanggil).
         * Sheet-sheet hasil duplikasi berada di akhir daftar,
         * sehingga tidak ikut terhapus.
         */
        foreach (
            array_reverse($originalSheetNames)
            as $name
        ) {
            $sheet = $workbook->getSheetByName($name);

            if ($sheet instanceof Worksheet) {
                $idx = $workbook->getIndex($sheet);
                $workbook->removeSheetByIndex($idx);
            }
        }

        $workbook->setActiveSheetIndex(0);

        $temporaryBase = tempnam(
            sys_get_temp_dir(),
            'siperbang_receipt_'
        );

        if ($temporaryBase === false) {
            throw new RuntimeException(
                'Gagal membuat file sementara '
                . 'untuk ekspor Excel.'
            );
        }

        /*
         * tempnam membuat file kosong tanpa ekstensi.
         * File tersebut dihapus, lalu writer membuat
         * file .xlsx pada lokasi yang sama.
         */
        @unlink($temporaryBase);

        $temporaryPath = $temporaryBase . '.xlsx';

        $writer = new Xlsx($workbook);

        /*
         * Formula total dan harga langsung mempunyai
         * cached result saat workbook dibuka.
         */
        $writer->setPreCalculateFormulas(true);

        $writer->save($temporaryPath);

        $filename = $this->makeFilename($receipts);

        $workbook->disconnectWorksheets();

        return [
            'path'     => $temporaryPath,
            'filename' => $filename,
        ];
    }

    private function populateSheet(
        Worksheet $sheet,
        Receipt $receipt,
        bool $isTaxed,
    ): void {
        $items = $receipt->items->values();

        $itemCount = $items->count();

        $lastColumn = $isTaxed ? 'I' : 'G';

        $totalColumn = $isTaxed ? 'H' : 'G';

        $templateItemCount = $isTaxed
            ? self::TAX_TEMPLATE_ITEM_COUNT
            : self::NON_TAX_TEMPLATE_ITEM_COUNT;

        $templateTotalRow =
            self::ITEM_START_ROW + $templateItemCount;

        /*
         * Sesuaikan jumlah baris template dengan jumlah
         * barang pada kuitansi.
         */
        if ($itemCount < $templateItemCount) {
            $sheet->removeRow(
                self::ITEM_START_ROW + $itemCount,
                $templateItemCount - $itemCount,
            );
        } elseif ($itemCount > $templateItemCount) {
            $sheet->insertNewRowBefore(
                $templateTotalRow,
                $itemCount - $templateItemCount,
            );
        }

        $itemEndRow =
            self::ITEM_START_ROW + $itemCount - 1;

        $totalRow = $itemEndRow + 1;

        /*
         * Style disalin per kolom dari baris pertama template
         * supaya font Arial, alignment, format Rupiah, dan
         * ukuran baris tetap dipertahankan.
         */
        $itemRowHeight = $sheet
            ->getRowDimension(self::ITEM_START_ROW)
            ->getRowHeight();

        for (
            $column = 'A';
            $column <= $lastColumn;
            $column++
        ) {
            $columnStyle = $sheet->getStyle(
                $column . self::ITEM_START_ROW
            );

            $sheet->duplicateStyle(
                $columnStyle,
                $column
                    . self::ITEM_START_ROW
                    . ':'
                    . $column
                    . $itemEndRow,
            );
        }

        if ($itemRowHeight > 0) {
            for (
                $row = self::ITEM_START_ROW;
                $row <= $itemEndRow;
                $row++
            ) {
                $sheet
                    ->getRowDimension($row)
                    ->setRowHeight($itemRowHeight);
            }
        }

        /*
         * Contoh:
         * "Toko redzky plastik"  =>  "SUPPLIER : REDZKY PLASTIK"
         */
        $sheet->setCellValue(
            'A2',
            'SUPPLIER : '
            . $this->formatSupplierName(
                (string) $receipt->store_name
            ),
        );

        foreach ($items as $index => $item) {
            $row = self::ITEM_START_ROW + $index;

            $sheet->setCellValue('A' . $row, $index + 1);

            /*
             * Kode persediaan disimpan sebagai teks agar Excel
             * tidak mengubahnya menjadi scientific notation.
             */
            $sheet->setCellValueExplicit(
                'B' . $row,
                $this->normaliseInventoryCode(
                    $item->inventory_code
                ),
                DataType::TYPE_STRING,
            );

            $sheet->setCellValue(
                'C' . $row,
                trim((string) $item->name),
            );

            $sheet->setCellValue(
                'D' . $row,
                (int) $item->qty,
            );

            $sheet->setCellValue(
                'E' . $row,
                $this->formatUnit($item->unit),
            );

            $sheet->setCellValue(
                'F' . $row,
                round((float) $item->price, 2),
            );

            if ($isTaxed) {
                /*
                 * G = harga satuan setelah pajak.
                 * H = jumlah × harga setelah pajak.
                 */
                $sheet->setCellValue(
                    'G' . $row,
                    "=F{$row}*\$I\$5",
                );

                $sheet->setCellValue(
                    'H' . $row,
                    "=D{$row}*G{$row}",
                );

                $sheet->setCellValue(
                    'I' . $row,
                    null,
                );
            } else {
                /*
                 * G = jumlah × harga satuan.
                 */
                $sheet->setCellValue(
                    'G' . $row,
                    "=D{$row}*F{$row}",
                );
            }
        }

        if ($isTaxed) {
            $taxRate = $this->formulaNumber(
                (float) $receipt->tax_rate
            );

            /*
             * Contoh tax rate 11:
             * =(100+11)/100  =>  faktor 1,11
             */
            $sheet->setCellValue(
                'I5',
                "=(100+{$taxRate})/100",
            );
        }

        /*
         * Bersihkan isi contoh pada baris total.
         * Style abu-abu dan format Rupiah tetap dipertahankan.
         */
        for (
            $column = 'A';
            $column <= $lastColumn;
            $column++
        ) {
            $sheet->setCellValue(
                $column . $totalRow,
                null,
            );
        }

        $sheet->setCellValue(
            $totalColumn . $totalRow,
            "=SUM({$totalColumn}"
                . self::ITEM_START_ROW
                . ":{$totalColumn}{$itemEndRow})",
        );

        $sheet
            ->getPageSetup()
            ->setPrintArea(
                "A1:{$lastColumn}{$totalRow}"
            );

        $sheet->setSelectedCell('A1');
    }

    /**
     * @param array<string, true> $usedTitles
     */
    private function makeUniqueSheetTitle(
        Receipt $receipt,
        array &$usedTitles,
    ): string {
        $datePart =
            $receipt->date?->format('dmy')
            ?? now()->format('dmy');

        $initials =
            $this->supplierInitials(
                (string) $receipt->store_name
            );

        $baseTitle = trim($datePart . ' ' . $initials);

        /*
         * Microsoft Excel membatasi nama worksheet
         * maksimal 31 karakter.
         */
        $candidate = Str::limit($baseTitle, 31, '');

        $counter = 2;

        while (
            isset($usedTitles[Str::lower($candidate)])
        ) {
            $suffix    = '-' . $counter;
            $candidate =
                Str::limit(
                    $baseTitle,
                    31 - strlen($suffix),
                    '',
                ) . $suffix;

            $counter++;
        }

        $usedTitles[Str::lower($candidate)] = true;

        return $candidate;
    }

    private function formatSupplierName(
        string $supplier,
    ): string {
        $supplierName = Str::upper(trim($supplier));

        /*
         * Workbook referensi menampilkan "REDZKY PLASTIK",
         * bukan "TOKO REDZKY PLASTIK".
         * Prefix CV dan PT tidak dihapus.
         */
        return preg_replace(
            '/^TOKO\s+/u',
            '',
            $supplierName,
        ) ?: $supplierName;
    }

    private function supplierInitials(
        string $supplier,
    ): string {
        $normalised = Str::upper(Str::ascii($supplier));

        $normalised = preg_replace(
            '/[^A-Z0-9]+/',
            ' ',
            $normalised,
        ) ?? '';

        /*
         * Kata-kata berikut tidak digunakan untuk inisial.
         *
         * Toko Redzky Plastik  =>  RP
         * CV Nirmana Aqsha     =>  NA
         */
        $ignoredPrefixes = [
            'TOKO', 'CV', 'PT',
            'UD', 'PD', 'TB',
        ];

        $words = collect(
            preg_split('/\s+/', trim($normalised)) ?: []
        )
            ->filter()
            ->reject(
                fn (string $word): bool =>
                    in_array($word, $ignoredPrefixes, true)
            )
            ->values();

        if ($words->isEmpty()) {
            return 'SP';
        }

        if ($words->count() === 1) {
            return substr($words->first(), 0, 2);
        }

        return substr($words->get(0), 0, 1)
            . substr($words->get(1), 0, 1);
    }

    private function normaliseInventoryCode(
        mixed $value,
    ): string {
        return substr(
            preg_replace('/\D/', '', (string) $value) ?? '',
            0,
            10,
        );
    }

    private function formatUnit(mixed $value): string
    {
        $unit = Str::upper(trim((string) $value));

        if ($unit === '') {
            return '';
        }

        $keepUppercase = [
            'PCS', 'KG', 'GR',
            'ML', 'CM', 'MM',
        ];

        if (in_array($unit, $keepUppercase, true)) {
            return $unit;
        }

        return Str::title(Str::lower($unit));
    }

    private function formulaNumber(float $value): string
    {
        return rtrim(
            rtrim(
                number_format($value, 2, '.', ''),
                '0',
            ),
            '.',
        );
    }

    /**
     * @param Collection<int, Receipt> $receipts
     */
    private function makeFilename(
        Collection $receipts,
    ): string {
        if ($receipts->count() === 1) {
            /** @var Receipt $receipt */
            $receipt = $receipts->first();

            $storeName = preg_replace(
                '~[\\\\/:*?"<>|]+~u',
                '_',
                trim((string) $receipt->store_name),
            ) ?: 'Kuitansi';

            $receiptDate =
                $receipt->date?->format('Ymd')
                ?? now()->format('Ymd');

            return "{$storeName}_{$receiptDate}.xlsx";
        }

        $years = $receipts
            ->map(
                fn (Receipt $receipt): ?string =>
                    $receipt->date?->format('Y')
            )
            ->filter()
            ->unique()
            ->values();

        $yearLabel =
            $years->count() === 1
                ? ' ' . $years->first()
                : '';

        return "Belanja Persediaan{$yearLabel}.xlsx";
    }
}
