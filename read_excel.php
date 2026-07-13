<?php
require 'vendor/autoload.php';

$files = glob('storage/app/private/private/uploads/*.xlsx');
$file = $files[0];
echo "Reading $file\n";

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

for ($r = 1; $r <= 5; $r++) {
    echo "Row $r:\n";
    foreach (range('A', 'I') as $col) {
        echo $col . $r . ': ' . $sheet->getCell($col.$r)->getValue() . " | ";
    }
    echo "\n";
}
