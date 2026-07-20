<?php

namespace Tests\Unit;

use App\Services\InventoryCodeSuggestionService;
use PHPUnit\Framework\TestCase;

class InventoryCodeSuggestionServiceTest extends TestCase
{
    public function test_office_inventory_code_is_formatted_correctly(): void
    {
        $this->assertSame(
            '1.01.03.06.010',
            InventoryCodeSuggestionService::formatCode('1010306010'),
        );
    }

    public function test_non_ten_digit_value_is_not_changed(): void
    {
        $this->assertSame(
            'invalid',
            InventoryCodeSuggestionService::formatCode('invalid'),
        );
    }
}
