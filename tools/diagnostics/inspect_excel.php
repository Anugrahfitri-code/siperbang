<?php

declare(strict_types=1);
use PhpOffice\PhpSpreadsheet\IOFactory;

$projectRoot = dirname(__DIR__, 2);
$autoload = $projectRoot.'/vendor/autoload.php';

if (! is_file($autoload)) {
    fwrite(STDERR, "Dependensi Composer belum terpasang. Jalankan composer install.\n");
    exit(1);
}

require $autoload;

$input = $argv[1] ?? null;

if ($input === null) {
    $uploadRoot = $projectRoot.'/storage/app/private';

    if (is_dir($uploadRoot)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadRoot, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'xlsx') {
                $input = $file->getPathname();
                break;
            }
        }
    }
}

if ($input === null || ! is_file($input)) {
    fwrite(STDERR, "Gunakan: php tools/diagnostics/inspect_excel.php <file.xlsx>\n");
    exit(1);
}

$spreadsheet = IOFactory::load($input);
$sheet = $spreadsheet->getActiveSheet();

fwrite(STDOUT, "File: {$input}\n");

for ($row = 1; $row <= 5; $row++) {
    fwrite(STDOUT, "Baris {$row}:\n");

    foreach (range('A', 'I') as $column) {
        $value = $sheet->getCell($column.$row)->getValue();
        fwrite(STDOUT, "{$column}{$row}: {$value} | ");
    }

    fwrite(STDOUT, PHP_EOL);
}
