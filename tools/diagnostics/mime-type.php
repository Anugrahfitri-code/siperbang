<?php

declare(strict_types=1);

$file = $argv[1] ?? null;

if ($file === null || ! is_file($file)) {
    fwrite(STDERR, "Gunakan: php tools/diagnostics/mime-type.php <path-file>\n");
    exit(1);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);

if ($finfo === false) {
    fwrite(STDERR, "Ekstensi fileinfo tidak tersedia.\n");
    exit(1);
}

$mimeType = finfo_file($finfo, $file);
finfo_close($finfo);

fwrite(STDOUT, ($mimeType ?: 'tidak diketahui').PHP_EOL);
