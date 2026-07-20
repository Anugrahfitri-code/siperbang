<?php

namespace App\Services;

use App\Models\KodePersediaan;
use App\Models\StockItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InventoryCodeSuggestionService
{
    private const OFFICE_PREFIX = '10103';

    /** @var Collection<int, KodePersediaan>|null */
    private ?Collection $officeCodesCache = null;

    /** @var Collection<int, StockItem>|null */
    private ?Collection $stockItemsCache = null;

    /**
     * Suggest one official inventory code for an OCR item.
     *
     * The code is not read directly by OCR. It is matched against:
     * 1. verified stock/master items originating from the Excel workflow;
     * 2. deterministic office-supply keyword rules;
     * 3. the official 1.01.03 code descriptions.
     *
     * A low-confidence match returns null so the officer must choose manually.
     *
     * @return array{
     *   code: string|null,
     *   description: string|null,
     *   category: string|null,
     *   unit: string|null,
     *   confidence: float|null,
     *   source: string|null,
     *   stock_item_id: int|null
     * }
     */
    public function suggest(
        string $itemName,
        ?string $ocrUnit = null,
    ): array {
        $normalisedName = $this->normaliseText($itemName);
        $normalisedUnit = $this->normaliseUnit($ocrUnit);

        if ($normalisedName === '') {
            return $this->emptySuggestion($normalisedUnit);
        }

        $stockMatch = $this->bestStockMatch($normalisedName);

        if (
            $stockMatch !== null
            && $stockMatch['confidence'] >= 0.70
        ) {
            return [
                'code' => $stockMatch['item']->code,
                'description' => $stockMatch['item']->name,
                'category' => $stockMatch['item']->category,
                'unit' => $normalisedUnit
                    ?: $this->normaliseUnit($stockMatch['item']->unit),
                'confidence' => round($stockMatch['confidence'], 4),
                'source' => 'stock_master_excel',
                'stock_item_id' => $stockMatch['item']->id,
            ];
        }

        $excelReferenceMatch = $this->bestExcelReferenceMatch(
            $normalisedName,
        );

        if (
            $excelReferenceMatch !== null
            && $excelReferenceMatch['confidence'] >= 0.70
        ) {
            $master = $this->officeCodes()
                ->firstWhere(
                    'kode',
                    $excelReferenceMatch['item']['code'],
                );

            if ($master !== null) {
                return [
                    'code' => $master->kode,
                    'description' => $master->nama_barang,
                    'category' => $master->kategoriBarang?->nama,
                    'unit' => $normalisedUnit
                        ?: $this->normaliseUnit(
                            $excelReferenceMatch['item']['unit'] ?? null,
                        ),
                    'confidence' => round(
                        $excelReferenceMatch['confidence'],
                        4,
                    ),
                    'source' => 'belanja_persediaan_2026_reference',
                    'stock_item_id' => null,
                ];
            }
        }

        $keywordCode = $this->keywordCode($normalisedName);

        if ($keywordCode !== null) {
            $master = $this->officeCodes()
                ->firstWhere('kode', $keywordCode);

            if ($master !== null) {
                return [
                    'code' => $master->kode,
                    'description' => $master->nama_barang,
                    'category' => $master->kategoriBarang?->nama,
                    'unit' => $normalisedUnit,
                    'confidence' => 0.92,
                    'source' => 'office_keyword_rule',
                    'stock_item_id' => null,
                ];
            }
        }

        $codeMatch = $this->bestOfficialCodeMatch($normalisedName);

        if (
            $codeMatch !== null
            && $codeMatch['confidence'] >= 0.78
        ) {
            return [
                'code' => $codeMatch['item']->kode,
                'description' => $codeMatch['item']->nama_barang,
                'category' => $codeMatch['item']->kategoriBarang?->nama,
                'unit' => $normalisedUnit,
                'confidence' => round($codeMatch['confidence'], 4),
                'source' => 'official_code_description',
                'stock_item_id' => null,
            ];
        }

        return $this->emptySuggestion($normalisedUnit);
    }

    /**
     * @return Collection<int, KodePersediaan>
     */
    public function officeCodes(): Collection
    {
        if ($this->officeCodesCache === null) {
            $this->officeCodesCache = KodePersediaan::query()
                ->with('kategoriBarang:id,nama')
                ->where('kode', 'like', self::OFFICE_PREFIX . '%')
                ->orderBy('kode')
                ->get();
        }

        return $this->officeCodesCache;
    }

    public function normaliseUnit(?string $unit): ?string
    {
        if ($unit === null) {
            return null;
        }

        $value = strtoupper(trim($unit));
        $value = preg_replace('/[^A-Z]/', '', $value) ?? '';

        if ($value === '') {
            return null;
        }

        return match ($value) {
            'PC', 'PCS', 'PIECE', 'PIECES' => 'PCS',
            'PACK', 'PAK', 'PK' => 'PAK',
            'RIM', 'REAM' => 'RIM',
            'BKS', 'BUNGKUS' => 'BKS',
            'BOX', 'KOTAK' => 'BOX',
            'DUS', 'KARTON', 'KRT' => 'DUS',
            'BTL', 'BOTOL' => 'BOTOL',
            'JRG', 'JERIGEN', 'JERRYCAN' => 'JERIGEN',
            'LBR', 'LEMBAR' => 'LEMBAR',
            'BH', 'BUAH' => 'BUAH',
            'UNIT' => 'UNIT',
            'SET' => 'SET',
            'ROLL', 'ROL' => 'ROLL',
            'LSN', 'LUSIN' => 'LUSIN',
            'SCT', 'SACHET' => 'SACHET',
            'KG' => 'KG',
            'GR', 'GRAM' => 'GRAM',
            'LTR', 'LITER' => 'LITER',
            default => mb_substr($value, 0, 30),
        };
    }

    public static function formatCode(string $code): string
    {
        $digits = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($digits) !== 10) {
            return $code;
        }

        return sprintf(
            '%s.%s.%s.%s.%s',
            substr($digits, 0, 1),
            substr($digits, 1, 2),
            substr($digits, 3, 2),
            substr($digits, 5, 2),
            substr($digits, 7, 3),
        );
    }

    /**
     * @return array{item: StockItem, confidence: float}|null
     */
    private function bestStockMatch(string $normalisedName): ?array
    {
        $best = null;

        foreach ($this->officeStockItems() as $item) {
            $score = $this->similarity(
                $normalisedName,
                $this->normaliseText($item->name),
            );

            if ($best === null || $score > $best['confidence']) {
                $best = [
                    'item' => $item,
                    'confidence' => $score,
                ];
            }
        }

        return $best;
    }

    /**
     * @return array{item: array{name: string, code: string, unit: string, source_sheet?: string}, confidence: float}|null
     */
    private function bestExcelReferenceMatch(string $normalisedName): ?array
    {
        $best = null;
        $items = config('office_inventory_aliases.items', []);

        if (! is_array($items)) {
            return null;
        }

        foreach ($items as $item) {
            if (
                ! is_array($item)
                || ! isset($item['name'], $item['code'], $item['unit'])
            ) {
                continue;
            }

            $code = preg_replace('/\D/', '', (string) $item['code']) ?? '';

            if (
                strlen($code) !== 10
                || ! str_starts_with($code, self::OFFICE_PREFIX)
            ) {
                continue;
            }

            $score = $this->similarity(
                $normalisedName,
                $this->normaliseText((string) $item['name']),
            );

            if ($best === null || $score > $best['confidence']) {
                $best = [
                    'item' => [
                        'name' => (string) $item['name'],
                        'code' => $code,
                        'unit' => (string) $item['unit'],
                        'source_sheet' => isset($item['source_sheet'])
                            ? (string) $item['source_sheet']
                            : null,
                    ],
                    'confidence' => $score,
                ];
            }
        }

        return $best;
    }

    /**
     * @return array{item: KodePersediaan, confidence: float}|null
     */
    private function bestOfficialCodeMatch(string $normalisedName): ?array
    {
        $best = null;

        foreach ($this->officeCodes() as $item) {
            $score = $this->similarity(
                $normalisedName,
                $this->normaliseText($item->nama_barang),
            );

            if ($best === null || $score > $best['confidence']) {
                $best = [
                    'item' => $item,
                    'confidence' => $score,
                ];
            }
        }

        return $best;
    }

    /**
     * @return Collection<int, StockItem>
     */
    private function officeStockItems(): Collection
    {
        if ($this->stockItemsCache === null) {
            $this->stockItemsCache = StockItem::query()
                ->where('code', 'like', self::OFFICE_PREFIX . '%')
                ->where(function ($query) {
                    $query->whereNull('is_active')
                        ->orWhere('is_active', true);
                })
                ->orderBy('name')
                ->get([
                    'id',
                    'code',
                    'name',
                    'category',
                    'unit',
                ]);
        }

        return $this->stockItemsCache;
    }

    private function normaliseText(string $value): string
    {
        $value = Str::ascii(Str::lower(trim($value)));
        $value = str_replace('&', ' dan ', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        $replacements = [
            'tissue' => 'tisu',
            'beterai' => 'baterai',
            'battery' => 'baterai',
            'flash disk' => 'flashdisk',
            'paperclip' => 'paper clip',
            'jerrycan' => 'jerigen',
            'jerry can' => 'jerigen',
        ];

        return trim(strtr($value, $replacements));
    }

    private function similarity(string $left, string $right): float
    {
        if ($left === '' || $right === '') {
            return 0.0;
        }

        if ($left === $right) {
            return 1.0;
        }

        if (str_contains($left, $right) || str_contains($right, $left)) {
            $ratio = min(strlen($left), strlen($right))
                / max(strlen($left), strlen($right));

            return min(0.96, 0.80 + ($ratio * 0.16));
        }

        $leftTokens = $this->tokens($left);
        $rightTokens = $this->tokens($right);

        if ($leftTokens === [] || $rightTokens === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($leftTokens, $rightTokens));
        $union = count(array_unique(array_merge($leftTokens, $rightTokens)));
        $minimum = min(count($leftTokens), count($rightTokens));

        $jaccard = $union > 0 ? $intersection / $union : 0.0;
        $containment = $minimum > 0 ? $intersection / $minimum : 0.0;

        similar_text($left, $right, $characterPercent);
        $characterScore = $characterPercent / 100;

        return min(
            1.0,
            ($containment * 0.50)
            + ($jaccard * 0.30)
            + ($characterScore * 0.20),
        );
    }

    /** @return list<string> */
    private function tokens(string $value): array
    {
        $stopWords = [
            'dan',
            'untuk',
            'dengan',
            'warna',
            'ukuran',
            'merk',
            'merek',
            'isi',
            'pro',
        ];

        $tokens = array_filter(
            explode(' ', $value),
            static fn (string $token): bool =>
                strlen($token) >= 2
                && ! in_array($token, $stopWords, true),
        );

        return array_values(array_unique($tokens));
    }

    private function keywordCode(string $name): ?string
    {
        $rules = [
            '1010305004' => [
                'kantong sampah',
                'tempat sampah',
            ],
            '1010305002' => [
                'tisu',
                'kanebo',
                'lap microfiber',
                'lap pembersih',
            ],
            '1010305008' => [
                'sunlight',
                'soklin',
                'porstex',
                'mr muscle',
                'pembersih lantai',
                'bubuk pembersih',
                'toilet ball',
                'kamper dahlia',
            ],
            '1010305009' => [
                'gelas kertas',
                'paper cup',
            ],
            '1010306010' => [
                'baterai',
                'alkaline',
            ],
            '1010301001' => [
                'pulpen',
                'ballpoint',
                'bolpoin',
            ],
            '1010301003' => [
                'penjepit kertas',
                'paper clip',
            ],
            '1010301005' => [
                'buku folio',
                'buku tulis',
            ],
            '1010301006' => [
                'map komdigi',
                'ordner',
                'map folder',
            ],
            '1010301008' => [
                'gunting',
                'cutter',
            ],
            '1010301010' => [
                'lakban',
                'masking tape',
                'selotip',
            ],
            '1010302001' => [
                'kertas a4',
                'kertas hvs',
                'paperone',
            ],
            '1010304006' => [
                'flashdisk',
                'usb flash',
            ],
            '1010304010' => [
                'mouse',
            ],
            '1010399999' => [
                'toples plastik',
                'pisau',
            ],
        ];

        foreach ($rules as $code => $phrases) {
            foreach ($phrases as $phrase) {
                if (str_contains($name, $phrase)) {
                    return $code;
                }
            }
        }

        return null;
    }

    /**
     * @return array{
     *   code: null,
     *   description: null,
     *   category: null,
     *   unit: string|null,
     *   confidence: null,
     *   source: null,
     *   stock_item_id: null
     * }
     */
    private function emptySuggestion(?string $unit): array
    {
        return [
            'code' => null,
            'description' => null,
            'category' => null,
            'unit' => $unit,
            'confidence' => null,
            'source' => null,
            'stock_item_id' => null,
        ];
    }
}
