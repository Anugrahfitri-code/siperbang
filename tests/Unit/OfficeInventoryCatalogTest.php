<?php

namespace Tests\Unit;

use App\Support\OfficeInventoryCatalog;
use Tests\TestCase;

class OfficeInventoryCatalogTest extends TestCase
{
    public function test_catalog_contains_only_official_1_01_03_subcategories(): void
    {
        $options = OfficeInventoryCatalog::categoryOptions();

        $this->assertCount(17, $options);
        $this->assertSame('1.01.03.01', $options[0]['code']);
        $this->assertSame('ALAT TULIS KANTOR', $options[0]['name']);
        $this->assertSame('1.01.03.99', $options[16]['code']);
        $this->assertSame(
            'ALAT/BAHAN UNTUK KEGIATAN KANTOR LAINNYA',
            $options[16]['name'],
        );
    }

    public function test_legacy_aliases_are_mapped_to_official_categories(): void
    {
        $this->assertSame(
            'ALAT TULIS KANTOR',
            OfficeInventoryCatalog::canonicalCategory(
                'Alat Tulis Kantor (ATK)',
            ),
        );

        $this->assertSame(
            'PERABOT KANTOR',
            OfficeInventoryCatalog::canonicalCategory(
                'Alat/Bahan Kebersihan',
            ),
        );

        $this->assertSame(
            'PERLENGKAPAN PENUNJANG KEGAITAN KANTOR',
            OfficeInventoryCatalog::canonicalCategory(
                'PERLENGKAPAN PENUNJANG KEGIATAN KANTOR',
            ),
        );
    }

    public function test_category_is_derived_from_inventory_code(): void
    {
        $this->assertSame(
            'KERTAS DAN COVER',
            OfficeInventoryCatalog::categoryForCode('1.01.03.02.001'),
        );

        $this->assertNull(
            OfficeInventoryCatalog::categoryForCode('1.01.01.01.001'),
        );
    }
}
