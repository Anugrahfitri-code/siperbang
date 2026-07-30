<?php

declare(strict_types=1);

$limits = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
];

fwrite(STDOUT, json_encode($limits, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
