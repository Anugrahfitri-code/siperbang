<?php

namespace App\Services;

use App\Models\KodePersediaan;
use App\Models\StokUpload;
use App\Models\StokUploadDetail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
            
            // 1. Extract supplier from Row 1 to 3 Column A
            $supplierName = 'Unknown Supplier';
            for ($i = 1; $i <= 3; $i++) {
                $supplierRaw = $sheet->getCell('A' . $i)->getValue();
                if ($supplierRaw && str_contains(strtoupper($supplierRaw), 'SUPPLIER')) {
                    $parts = explode(':', $supplierRaw);
                    $supplierName = isset($parts[1]) ? trim($parts[1]) : trim($supplierRaw);
                    break;
                }
            }

            // 2. Identify layout type from headers dynamically
            $headerRow = 4;
            for ($r = 1; $r <= 10; $r++) {
                $valB = $sheet->getCell('B' . $r)->getValue();
                $valC = $sheet->getCell('C' . $r)->getValue();
                if ((is_string($valB) && stripos($valB, 'kode') !== false) || (is_string($valC) && stripos($valC, 'nama') !== false)) {
                    $headerRow = $r;
                    break;
                }
            }
            
            $colGHeader = trim($sheet->getCell('G' . $headerRow)->getValue() ?? '');
            $colIHeader = trim($sheet->getCell('I' . $headerRow)->getValue() ?? '');
            
            // If Column G is "Harga Satuan + Pajak" or Col I is "Pajak", it is taxed format
            $isTaxedFormat = (str_contains(strtolower($colGHeader), 'pajak') || str_contains(strtolower($colIHeader), 'pajak') || !empty($colIHeader));

            $highestRow = $sheet->getHighestRow();

            // 3. Process rows starting from headerRow + 1
            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                $noVal = $sheet->getCell('A' . $row)->getValue();
                $kodeVal = $sheet->getCell('B' . $row)->getValue();
                $namaVal = $sheet->getCell('C' . $row)->getValue();
                $qtyVal = $sheet->getCell('D' . $row)->getValue();
                $unitVal = $sheet->getCell('E' . $row)->getValue();
                $priceVal = $sheet->getCell('F' . $row)->getValue();

                // Skip completely empty rows
                if (empty($noVal) && empty($kodeVal) && empty($namaVal) && empty($qtyVal) && empty($unitVal) && empty($priceVal)) {
                    continue;
                }

                // Identify total row (A, B, C empty, but Total column has value)
                $totalCol = $isTaxedFormat ? 'H' : 'G';
                $totalValExcelRaw = $sheet->getCell($totalCol . $row)->getValue();
                if (empty($noVal) && empty($kodeVal) && empty($namaVal) && !empty($totalValExcelRaw)) {
                    // This is the total row of the sheet, skip processing as a data row
                    continue;
                }

                // If it's a data row
                $totalRowsCount++;
                $errors = [];

                // Helper to clean "Rp", commas, and spaces
                $cleanNum = function($val) {
                    if (is_numeric($val)) return floatval($val);
                    if (is_string($val)) {
                        $clean = preg_replace('/[Rr]p|\s|,/', '', $val);
                        return is_numeric($clean) ? floatval($clean) : 0;
                    }
                    return 0;
                };

                // Standardize values
                $noUrut = $noVal ? $cleanNum($noVal) : null;
                $kodePersediaan = $kodeVal ? trim(strval($kodeVal)) : null;
                $namaBarang = $namaVal ? trim(strval($namaVal)) : '';
                $qty = $qtyVal ? $cleanNum($qtyVal) : 0;
                $unit = $unitVal ? trim(strval($unitVal)) : '';
                
                // Get calculated values for price and totals to resolve Excel formulas
                $priceUnit = $priceVal ? $cleanNum($priceVal) : 0;
                if ($priceVal && str_starts_with(strval($priceVal), '=')) {
                    $priceUnit = $cleanNum($sheet->getCell('F' . $row)->getCalculatedValue());
                }

                $priceUnitTaxed = null;
                $totalExcel = 0;
                $taxRate = 1.11; // 11% Tax multiplier

                if ($isTaxedFormat) {
                    $taxVal = $sheet->getCell('I' . $row)->getValue();
                    if ($taxVal && str_starts_with(strval($taxVal), '=')) {
                        // resolve formula e.g. =111/100
                        $resolvedTax = floatval($sheet->getCell('I' . $row)->getCalculatedValue());
                        if ($resolvedTax > 0) {
                            $taxRate = $resolvedTax;
                        }
                    } elseif (is_numeric($taxVal)) {
                        $taxRate = floatval($taxVal);
                    }

                    // Get calculated taxed unit price
                    $priceUnitTaxedVal = $sheet->getCell('G' . $row)->getValue();
                    if ($priceUnitTaxedVal && str_starts_with(strval($priceUnitTaxedVal), '=')) {
                        $calculated = $cleanNum($sheet->getCell('G' . $row)->getCalculatedValue());
                        $priceUnitTaxed = $calculated > 0 ? $calculated : ($priceUnit * $taxRate);
                    } else {
                        $priceUnitTaxed = is_numeric($priceUnitTaxedVal) ? floatval($priceUnitTaxedVal) : ($priceUnit * $taxRate);
                    }

                    // System calculation
                    $calculatedTotal = $qty * ($priceUnit * $taxRate);

                    // Excel total
                    $totalExcelVal = $sheet->getCell('H' . $row)->getValue();
                    if ($totalExcelVal && str_starts_with(strval($totalExcelVal), '=')) {
                        $calculated = $cleanNum($sheet->getCell('H' . $row)->getCalculatedValue());
                        $totalExcel = $calculated > 0 ? $calculated : $calculatedTotal;
                    } else {
                        $totalExcel = $totalExcelVal ? $cleanNum($totalExcelVal) : 0;
                    }
                } else {
                    // System calculation
                    $calculatedTotal = $qty * $priceUnit;

                    // Excel total
                    $totalExcelVal = $sheet->getCell('G' . $row)->getValue();
                    if ($totalExcelVal && str_starts_with(strval($totalExcelVal), '=')) {
                        $calculated = $cleanNum($sheet->getCell('G' . $row)->getCalculatedValue());
                        $totalExcel = $calculated > 0 ? $calculated : $calculatedTotal;
                    } else {
                        $totalExcel = $totalExcelVal ? $cleanNum($totalExcelVal) : 0;
                    }
                }

                // --- VALIDATIONS ---
                if (empty($kodePersediaan)) {
                    $errors[] = "Kode persediaan wajib ada.";
                }
                if (empty($namaBarang)) {
                    $errors[] = "Nama barang wajib ada.";
                }
                if ($qty <= 0) {
                    $errors[] = "Jumlah wajib angka dan lebih dari 0.";
                }
                if (empty($unit)) {
                    $errors[] = "Satuan wajib ada.";
                }
                if ($priceUnit <= 0) {
                    $errors[] = "Harga satuan wajib angka.";
                }

                // Verify code match
                if ($kodePersediaan) {
                    // Check if code exists in the master list
                    $codeExists = KodePersediaan::where('kode', $kodePersediaan)->exists();
                    if (!$codeExists) {
                        $errors[] = "Kode persediaan tidak cocok dengan aturan kategori.";
                    }
                    
                    // We no longer track duplicates as errors because a code can be used for multiple items.
                    if (!isset($codeCounts[$kodePersediaan])) {
                        $codeCounts[$kodePersediaan] = 0;
                    }
                    $codeCounts[$kodePersediaan]++;
                }

                // Check total discrepancies
                if (abs(round($totalExcel) - round($calculatedTotal)) > 5) {
                    $errors[] = "Total dari Excel (" . number_format($totalExcel, 2) . ") tidak sesuai dengan hitungan sistem (" . number_format($calculatedTotal, 2) . ").";
                }

                // Determine suggested code
                $categoryName = $this->kodeService->getCategoryByCode($kodePersediaan ?? '');
                $suggestedCode = $this->kodeService->suggestCode($categoryName, $namaBarang);

                $statusValidation = count($errors) > 0 ? 'Perlu Perbaikan' : 'Menunggu Verifikasi';
                if ($statusValidation === 'Menunggu Verifikasi') {
                    $validRowsCount++;
                } else {
                    $errorRowsCount++;
                }

                $allRows[] = [
                    'sheet_name' => $sheetName,
                    'supplier' => $supplierName,
                    'no_urut' => $noUrut,
                    'kode_persediaan_excel' => $kodePersediaan,
                    'suggested_kode_persediaan' => $suggestedCode,
                    'nama_barang' => $namaBarang,
                    'qty' => $qty,
                    'unit' => $unit,
                    'price_unit' => $priceUnit,
                    'price_unit_taxed' => $priceUnitTaxed,
                    'total_excel' => $totalExcel,
                    'total_calculated' => $calculatedTotal,
                    'is_taxed' => $isTaxedFormat,
                    'status_validation' => $statusValidation,
                    'status_verification' => 'Pending',
                    'verified_kode_persediaan' => $kodePersediaan ?: $suggestedCode,
                    'notes_error' => count($errors) > 0 ? implode(' | ', $errors) : null,
                    'is_duplicate' => false, // Will resolve after compiling counts
                ];
            }
        }

        // 4. Resolve duplicates and save to database
        DB::transaction(function () use ($uploadBatch, $allRows, $codeCounts, $totalRowsCount, $validRowsCount, $errorRowsCount) {
            foreach ($allRows as $rowData) {
                $code = $rowData['kode_persediaan_excel'] ?? null;
                if ($code && isset($codeCounts[$code]) && $codeCounts[$code] > 1) {
                    $rowData['is_duplicate'] = true;
                    // Do not mark as error or change status for duplicate codes
                }

                $uploadBatch->details()->create($rowData);
            }

            // Update batch metrics
            $batchStatus = 'Menunggu Verifikasi';
            if ($errorRowsCount > 0) {
                $batchStatus = ($validRowsCount > 0) ? 'Sebagian Valid' : 'Perlu Perbaikan';
            }

            $uploadBatch->update([
                'rows_count' => $totalRowsCount,
                'valid_rows_count' => $validRowsCount,
                'error_rows_count' => $errorRowsCount,
                'status' => $batchStatus,
            ]);
        });

        return $uploadBatch;
    }
}
