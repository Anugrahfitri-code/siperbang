<?php

namespace App\Support\Export;

class SpreadsheetSecurity
{
    /**
     * Neutralize potential spreadsheet formula injection.
     * Prevents cells starting with =, +, -, @, \t, \r from being executed as formulas
     * when opened in Excel, LibreOffice, or Google Sheets.
     */
    public static function escapeFormula(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Remove leading whitespace for checking
        $trimmed = ltrim($value);

        if ($trimmed === '') {
            return $value;
        }

        // Check if starts with dangerous character
        $dangerousCharacters = ['=', '+', '-', '@', "\t", "\r"];
        $firstChar = mb_substr($trimmed, 0, 1);

        if (in_array($firstChar, $dangerousCharacters, true)) {
            // Prefix with a single quote to force text interpretation
            return "'".$value;
        }

        return $value;
    }
}
