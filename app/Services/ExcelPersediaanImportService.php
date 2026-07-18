<?php

namespace App\Services;

use App\Models\KodePersediaan;
use App\Models\StokUpload;
use App\Models\StokUploadDetail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExcelPersediaanImportService
{
    protected $kodeService;

    public function __construct(KodePersediaanService $kodeService)
    {
        $this->kodeService = $kodeService;
    }

    /**
     * Parse the uploaded Excel file and save to temporary draft tables.
     */
    public function import(string $filePath, string $originalFileName, string $storedFileName): StokUpload
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File excel tidak ditemukan.");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheetNames = $spreadsheet->getSheetNames();
        
        // Create the StokUpload header
        $uploadBatch = StokUpload::create([
            'file_name_original' => $originalFileName,
            'file_name_stored' => $storedFileName,
            'user_id' => Auth::id() ?? 1, // Fallback to user ID 1
            'upload_date' => now(),
            'sheets_count' => count($sheetNames),
            'status' => 'Draft',
        ]);

        $allRows = [];
        $totalRowsCount = 0;
        $validRowsCount = 0;
        $errorRowsCount = 0;

        // Keep track of code counts for duplicate detection within this batch
        $codeCounts = [];

        foreach ($sheetNames as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            
            // ── 1. Extract supplier name ─────────────────────────────
            // Scan rows 1–5, any column A or B, for a cell containing "SUPPLIER"
            // Handles: A1=0 (numeric noise), multi-row headers, varied placement.
            $supplierName = 'Unknown Supplier';
            for ($i = 1; $i <= 5; $i++) {
                foreach (['A', 'B', 'C'] as $col) {
                    $cellVal = $sheet->getCell($col . $i)->getValue();
                    // Only check string cells — skip numeric values like 0
                    if (!is_string($cellVal) || $cellVal === '') {
                        continue;
                    }
                    if (stripos($cellVal, 'SUPPLIER') !== false) {
                        $parts = explode(':', $cellVal, 2);
                        $supplierName = isset($parts[1]) ? trim($parts[1]) : trim($cellVal);
                        break 2;
                    }
                }
            }

            // ── 2. Detect header row (rows 1–15) ────────────────────
            // Look for the row that contains recognisable column headers:
            // "Kode" (any col), "Nama" (any col), "Jumlah"/"Qty", "Satuan", "Harga"
            $headerRow       = 4; // safe fallback
            $headerColMap    = [];  // ['no'=>'A','kode'=>'B','nama'=>'C', ...]
            $maxScanRow      = min(15, $sheet->getHighestRow());

            for ($r = 1; $r <= $maxScanRow; $r++) {
                $found   = [];
                $highCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                    $sheet->getHighestColumn($r)
                );

                for ($c = 1; $c <= min($highCol, 12); $c++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $val       = $sheet->getCell($colLetter . $r)->getValue();
                    if (!is_string($val) || $val === '') {
                        continue;
                    }
                    $v = strtolower(trim($val));
                    if (str_contains($v, 'kode'))                     $found['kode']   = $colLetter;
                    elseif (str_contains($v, 'nama'))                 $found['nama']   = $colLetter;
                    elseif (in_array($v, ['no','no.','nomor','#']))   $found['no']     = $colLetter;
                    elseif (str_contains($v, 'jumlah') || $v==='qty') $found['qty']    = $colLetter;
                    elseif (str_contains($v, 'pajak') || str_contains($v, 'ppn')) $found['pajak'] = $colLetter;
                    elseif (str_contains($v, 'harga'))                $found['harga']  = $colLetter;
                    elseif (str_contains($v, 'satuan') || $v==='sat') $found['unit']   = $colLetter;
                    elseif (str_contains($v, 'total'))                $found['total']  = $colLetter;
                    elseif (str_contains($v, 'lokasi') || str_contains($v, 'location') || str_contains($v, 'rak') || str_contains($v, 'tempat')) $found['storage_location'] = $colLetter;
                }

                // Require at least "kode" + "nama" to confirm this is the header row
                if (isset($found['kode'], $found['nama'])) {
                    $headerRow    = $r;
                    $headerColMap = $found;
                    break;
                }
            }

            // ── 3. Resolve column letters from header map ────────────
            // Fall back to standard positions if dynamic detection missed a column.
            $colNo    = $headerColMap['no']    ?? 'A';
            $colKode  = $headerColMap['kode']  ?? 'B';
            $colNama  = $headerColMap['nama']  ?? 'C';
            $colQty   = $headerColMap['qty']   ?? 'D';
            $colUnit  = $headerColMap['unit']  ?? 'E';
            $colHarga = $headerColMap['harga'] ?? 'F';
            $colStorageLocation = $headerColMap['storage_location'] ?? null;

            // Detect taxed vs nett format
            // Taxed format has a "Harga Satuan + Pajak" column and/or a "Pajak" column
            $isTaxedFormat = isset($headerColMap['pajak']);

            // For taxed: G=harga+pajak, H=total, I=pajak rate
            // For nett:  G=total
            if ($isTaxedFormat) {
                // Find the "harga+pajak" column — it's the harga column or the one after harga
                $colHargaPajak = $headerColMap['pajak'] ?? null;
                // Re-scan to find exact "harga satuan + pajak" or "harga + pajak"
                $highCol2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                    $sheet->getHighestColumn($headerRow)
                );
                for ($c = 1; $c <= min($highCol2, 12); $c++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $val = strtolower(trim($sheet->getCell($colLetter . $headerRow)->getValue() ?? ''));
                    if (str_contains($val, 'harga') && (str_contains($val, 'pajak') || str_contains($val, 'ppn'))) {
                        $colHargaPajak = $colLetter;
                    }
                    if (str_contains($val, 'total')) {
                        $colTotal = $colLetter;
                    }
                    if ($val === 'pajak' || $val === 'ppn' || str_ends_with($val, 'pajak') || str_ends_with($val, 'ppn')) {
                        $colPajakRate = $colLetter;
                    }
                }
                $colHargaPajak  = $colHargaPajak  ?? 'G';
                $colTotal       = $colTotal       ?? 'H';
                $colPajakRate   = $colPajakRate   ?? 'I';
            } else {
                $colTotal = $headerColMap['total'] ?? 'G';
            }

            // ── 4. Process data rows ─────────────────────────────────
            $highestRow = $sheet->getHighestRow();

            // Helper: get cell value as string, fully trimmed (handles numeric, null, formula)
            $cellStr = function (string $colRow) use ($sheet): string {
                $cell = $sheet->getCell($colRow);
                $val  = $cell->getValue();
                // Resolve formula to calculated value
                if (is_string($val) && str_starts_with($val, '=')) {
                    $val = $cell->getCalculatedValue();
                }
                return trim((string) ($val ?? ''));
            };

            // Helper: clean numeric value — strips "Rp", spaces, dots (thousands), commas
            $cleanNum = function ($rawVal) use ($sheet): float {
                $val = $rawVal;
                if (is_string($val) && str_starts_with(trim($val), '=')) {
                    // Resolve formula
                    $val = 0; // fallback; caller should use getCalculatedValue separately
                }
                if (is_numeric($val)) return floatval($val);
                if (is_string($val)) {
                    // Strip "Rp", currency prefix, thousand separators, then parse
                    $clean = preg_replace('/[Rr][Pp]\.?\s*/u', '', $val);
                    // Remove thousand separators (dot before groups of 3) and replace decimal comma
                    $clean = preg_replace('/\.(?=\d{3})/', '', $clean); // remove thousand dot
                    $clean = str_replace(',', '.', $clean);             // decimal comma → dot
                    $clean = preg_replace('/[^\d.]/', '', $clean);      // keep only digits & dot
                    return is_numeric($clean) ? floatval($clean) : 0;
                }
                return 0;
            };

            // Helper: resolve a cell to its numeric value, handling formulas
            $cellNum = function (string $colRow) use ($sheet, $cleanNum): float {
                $cell = $sheet->getCell($colRow);
                $val  = $cell->getValue();
                if (is_string($val) && str_starts_with(trim($val), '=')) {
                    $val = $cell->getCalculatedValue();
                }
                return $cleanNum($val);
            };

            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {

                // ── Read & trim all data-column values ──────────────
                $noStr    = $cellStr($colNo    . $row);
                $kodeStr  = $cellStr($colKode  . $row);
                $namaStr  = $cellStr($colNama  . $row);
                $qtyStr   = $cellStr($colQty   . $row);
                $unitStr  = $cellStr($colUnit  . $row);
                $hargaStr = $cellStr($colHarga . $row);
                $totalStr = $cellStr(($isTaxedFormat ? ($colTotal ?? 'H') : ($colTotal ?? 'G')) . $row);
                $storageLocationStr = $colStorageLocation ? $cellStr($colStorageLocation . $row) : null;

                // ── Skip: all data columns empty after trim ──────────
                if ($noStr === '' && $kodeStr === '' && $namaStr === ''
                    && $qtyStr === '' && $unitStr === '' && $hargaStr === '') {
                    continue;
                }

                // ── Skip: total/subtotal row — kode+nama+qty empty but total present
                //    Also skip any row where kode looks like a label (contains letters
                //    that are not a valid code pattern) AND nama+qty+unit are all empty.
                if ($kodeStr === '' && $namaStr === '' && $qtyStr === '' && $unitStr === '' && $totalStr !== '') {
                    continue; // grand-total / sub-total row
                }

                // ── Skip: rows that are clearly section headers inside data
                //    (no numeric qty, no kode, but nama looks like a heading)
                $looksLikeHeader = ($kodeStr === '' && $qtyStr === '' && $unitStr === '' && $hargaStr === '');
                if ($looksLikeHeader) {
                    continue;
                }

                // ── This is a real data row ──────────────────────────
                $totalRowsCount++;

                $excelRowLabel = "baris {$row}"; // actual Excel row number for error messages

                $noUrut         = $noStr !== '' ? (int) $cleanNum($noStr) : null;
                $kodePersediaan = $kodeStr !== '' ? $kodeStr : null;
                $namaBarang     = $namaStr;
                $qty            = $qtyStr  !== '' ? (int) $cleanNum($qtyStr)  : 0;
                $unit           = $unitStr;
                $priceUnit      = $hargaStr !== '' ? $cellNum($colHarga . $row) : 0;

                // ── Calculate prices and totals ──────────────────────
                $priceUnitTaxed  = null;
                $totalExcel      = 0;
                $calculatedTotal = 0;
                $defaultTaxRate  = 1.11;

                if ($isTaxedFormat) {
                    // Read actual tax rate from pajak column
                    $taxRateRaw = $cellNum(($colPajakRate ?? 'I') . $row);
                    $taxRate    = ($taxRateRaw > 0) ? $taxRateRaw : $defaultTaxRate;

                    $priceUnitTaxed  = $cellNum(($colHargaPajak ?? 'G') . $row);
                    if ($priceUnitTaxed <= 0) {
                        $priceUnitTaxed = round($priceUnit * $taxRate, 2);
                    }

                    $calculatedTotal = round($qty * $priceUnitTaxed, 2);
                    $totalExcel      = $cellNum(($colTotal ?? 'H') . $row);
                    if ($totalExcel <= 0) $totalExcel = $calculatedTotal;

                } else {
                    $priceUnitTaxed  = $priceUnit;
                    $calculatedTotal = round($qty * $priceUnit, 2);
                    $totalExcel      = $cellNum(($colTotal ?? 'G') . $row);
                    if ($totalExcel <= 0) $totalExcel = $calculatedTotal;
                }

                // ── Validations ──────────────────────────────────────
                $errorDetails = []; // ['column' => 'B', 'message' => '...']

                if ($kodePersediaan === null || $kodePersediaan === '') {
                    $errorDetails[] = ['column' => $colKode,
                        'message' => "Baris Excel {$row}: Kode persediaan wajib diisi."];
                }
                if ($namaBarang === '') {
                    $errorDetails[] = ['column' => $colNama,
                        'message' => "Baris Excel {$row}: Nama barang wajib diisi."];
                }
                if ($qty <= 0) {
                    $errorDetails[] = ['column' => $colQty,
                        'message' => "Baris Excel {$row}: Jumlah harus berupa angka lebih dari 0."];
                }
                if ($unit === '') {
                    $errorDetails[] = ['column' => $colUnit,
                        'message' => "Baris Excel {$row}: Satuan wajib diisi."];
                } elseif (preg_match('/\d/', $unit)) {
                    $errorDetails[] = ['column' => $colUnit,
                        'message' => "Baris Excel {$row}: Satuan tidak boleh mengandung angka ('{$unit}')."];
                }
                if ($priceUnit <= 0) {
                    $errorDetails[] = ['column' => $colHarga,
                        'message' => "Baris Excel {$row}: Harga satuan harus berupa angka lebih dari 0."];
                }

                // Verify kode against master (only if kode provided)
                if ($kodePersediaan !== null && $kodePersediaan !== '') {
                    $codeExists = KodePersediaan::where('kode', $kodePersediaan)->exists();
                    if (! $codeExists) {
                        $errorDetails[] = ['column' => $colKode,
                            'message' => "Baris Excel {$row}: Kode persediaan '{$kodePersediaan}' tidak ditemukan di master kode."];
                    }
                    $codeCounts[$kodePersediaan] = ($codeCounts[$kodePersediaan] ?? 0) + 1;
                }

                // Check total discrepancy (only when both values are non-zero)
                if ($totalExcel > 0 && $calculatedTotal > 0
                    && abs(round($totalExcel) - round($calculatedTotal)) > 5) {
                    $colT = $isTaxedFormat ? ($colTotal ?? 'H') : ($colTotal ?? 'G');
                    $errorDetails[] = [
                        'column'  => $colT,
                        'message' => "Baris Excel {$row}: Total Excel ("
                            . number_format($totalExcel, 0, ',', '.')
                            . ") tidak sesuai hitungan sistem ("
                            . number_format($calculatedTotal, 0, ',', '.')
                            . ").",
                    ];
                }

                // Suggested code
                $categoryName  = $this->kodeService->getCategoryByCode($kodePersediaan ?? '');
                $suggestedCode = $this->kodeService->suggestCode($categoryName, $namaBarang);

                $hasError         = count($errorDetails) > 0;
                $statusValidation = $hasError ? 'Perlu Perbaikan' : 'Menunggu Verifikasi';

                if ($hasError) { $errorRowsCount++; } else { $validRowsCount++; }

                $errorMessages = array_map(fn ($e) => $e['message'], $errorDetails);
                $firstErrorCol = $hasError ? ($errorDetails[0]['column'] ?? null) : null;

                $allRows[] = [
                    'sheet_name'                => $sheetName,
                    'supplier'                  => $supplierName,
                    'no_urut'                   => $noUrut ?? $row,   // actual Excel row as fallback
                    'kode_persediaan_excel'     => $kodePersediaan,
                    'suggested_kode_persediaan' => $suggestedCode ?: null,
                    'nama_barang'               => $namaBarang,
                    'qty'                       => $qty,
                    'unit'                      => $unit,
                    'price_unit'                => $priceUnit,
                    'price_unit_taxed'          => $priceUnitTaxed ?? $priceUnit,
                    'total_excel'               => $totalExcel,
                    'total_calculated'          => $calculatedTotal,
                    'is_taxed'                  => $isTaxedFormat,
                    'status_validation'         => $statusValidation,
                    'status_verification'       => 'Pending',
                    // Never fall back to a hardcoded dummy code — leave null if missing
                    'verified_kode_persediaan'  => $kodePersediaan ?: null,
                    'notes_error'               => $hasError ? implode(' | ', $errorMessages) : null,
                    'error_column'              => $firstErrorCol,
                    'is_duplicate'              => false,
                    'storage_location'          => $storageLocationStr,
                    // Store actual Excel row number for display in UI
                    '_excel_row'                => $row,
                ];
            }
        }

        // ── Save to database or reject if there are errors ──────
        // If any row has a validation error the entire upload is rejected.
        // The user must fix the Excel file and re-upload.

        // Also reject if no data rows were found at all (empty / unrecognised file)
        if ($totalRowsCount === 0) {
            // Delete the batch header that was created at the start
            $uploadBatch->forceDelete();
            Storage::delete('private/uploads/' . $storedFileName);

            session()->flash('upload_errors', [[
                'sheet'    => '—',
                'no_urut'  => '—',
                'nama'     => '(tidak ada data)',
                'messages' => ['File Excel tidak mengandung baris data yang dapat dibaca. Pastikan format kolom sudah sesuai petunjuk.'],
            ]]);
            session()->flash('upload_error_count', 0);
            session()->flash('upload_total_count', 0);

            throw new \App\Exceptions\ExcelValidationException(
                "File ditolak: tidak ada baris data yang berhasil dibaca.",
                []
            );
        }

        if ($errorRowsCount > 0) {
            // Delete the batch header — nothing should remain on disk or DB
            $uploadBatch->forceDelete();
            Storage::delete('private/uploads/' . $storedFileName);

            // Build a structured error list for the caller to display
            $errorList = array_values(array_filter(
                array_map(fn ($r) => $r['notes_error'] ?? null, $allRows)
            ));

            // Attach sheet+row context to each error message
            $detailedErrors = [];
            foreach ($allRows as $r) {
                if (!empty($r['notes_error'])) {
                    $detailedErrors[] = [
                        'sheet'    => $r['sheet_name'],
                        'no_urut'  => $r['no_urut'],
                        'nama'     => $r['nama_barang'] ?: '(kosong)',
                        'messages' => explode(' | ', $r['notes_error']),
                    ];
                }
            }

            // Store in session so the upload form can display them
            session()->flash('upload_errors', $detailedErrors);
            session()->flash('upload_error_count', $errorRowsCount);
            session()->flash('upload_total_count', $totalRowsCount);

            throw new \App\Exceptions\ExcelValidationException(
                "File ditolak: ditemukan {$errorRowsCount} baris dengan data tidak valid. " .
                "Perbaiki file Excel Anda dan upload ulang.",
                $detailedErrors
            );
        }

        // All rows are valid — save to database
        DB::transaction(function () use ($uploadBatch, $allRows, $codeCounts, $totalRowsCount, $validRowsCount, $errorRowsCount) {
            foreach ($allRows as $rowData) {
                $code = $rowData['kode_persediaan_excel'] ?? null;
                if ($code && isset($codeCounts[$code]) && $codeCounts[$code] > 1) {
                    $rowData['is_duplicate'] = true;
                }

                // Remove internal helper key before saving
                unset($rowData['_excel_row']);

                $uploadBatch->details()->create($rowData);
            }

            // Update batch metrics — simplified statuses (no 'Sebagian Valid')
            $batchStatus = StokUpload::STATUS_MENUNGGU_VERIFIKASI;
            if ($errorRowsCount > 0) {
                $batchStatus = StokUpload::STATUS_PERLU_PERBAIKAN;
            }

            $uploadBatch->update([
                'rows_count'        => $totalRowsCount,
                'valid_rows_count'  => $validRowsCount,
                'error_rows_count'  => $errorRowsCount,
                'status'            => $batchStatus,
                'current_step'      => $errorRowsCount > 0 ? StokUpload::STEP_PEMERIKSAAN : StokUpload::STEP_VERIFIKASI,
            ]);
        });

        return $uploadBatch;
    }
}
