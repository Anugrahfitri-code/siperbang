<?php

declare(strict_types=1);

/** @return array{hash: string, files: array<int, string>} */
function calculateFrontendSourceHash(string $root): array
{
    $files = [
        'package.json',
        'package-lock.json',
        'vite.config.js',
        'tsconfig.json',
        'eslint.config.js',
        'scripts/build-source-hash.mjs',
        'scripts/build-artifact-hash.mjs',
        'scripts/write-build-metadata.mjs',
        'scripts/verify-build-metadata.mjs',
    ];

    foreach (['resources/js', 'resources/css'] as $sourceRoot) {
        $absoluteRoot = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $sourceRoot);
        if (! is_dir($absoluteRoot)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absoluteRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/');
            $files[] = $relative;
        }
    }

    $files = array_values(array_unique($files));
    sort($files, SORT_STRING);
    $context = hash_init('sha256');

    foreach ($files as $relative) {
        $absolute = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (! is_file($absolute)) {
            throw new RuntimeException("Sumber frontend {$relative} tidak ditemukan.");
        }

        hash_update($context, $relative."\0");
        $stream = fopen($absolute, 'rb');
        if ($stream === false) {
            throw new RuntimeException("Sumber frontend {$relative} tidak dapat dibaca.");
        }
        hash_update_stream($context, $stream);
        fclose($stream);
        hash_update($context, "\0");
    }

    return ['hash' => hash_final($context), 'files' => $files];
}

/** @return array{hash: string, files: array<int, string>} */
function calculateFrontendBuildHash(string $root): array
{
    $buildRoot = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build';
    if (! is_dir($buildRoot)) {
        throw new RuntimeException('Direktori public/build tidak ditemukan.');
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($buildRoot, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->isLink()) {
            continue;
        }

        $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($buildRoot))), '/');
        if ($relative !== 'build-meta.json') {
            $files[] = $relative;
        }
    }

    sort($files, SORT_STRING);
    $context = hash_init('sha256');

    foreach ($files as $relative) {
        $absolute = $buildRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        hash_update($context, $relative."\0");
        $stream = fopen($absolute, 'rb');
        if ($stream === false) {
            throw new RuntimeException("Build frontend {$relative} tidak dapat dibaca.");
        }
        hash_update_stream($context, $stream);
        fclose($stream);
        hash_update($context, "\0");
    }

    return ['hash' => hash_final($context), 'files' => $files];
}

function frontendBuildContainsSourceHash(string $root, string $sourceHash): bool
{
    $assetRoot = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build';
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($assetRoot, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || strtolower($file->getExtension()) !== 'js') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        if ($contents !== false && str_contains($contents, $sourceHash)) {
            return true;
        }
    }

    return false;
}

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Script ini hanya dapat dijalankan melalui CLI.\n");
    exit(1);
}

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "Ekstensi PHP zip diperlukan.\n");
    exit(1);
}

$root = realpath(dirname(__DIR__));
$outputArgument = $argv[1] ?? null;
$includeBuild = in_array('--include-build', $argv, true);

if ($root === false || $outputArgument === null || str_starts_with($outputArgument, '--')) {
    fwrite(STDERR, "Penggunaan: php scripts/package-release.php /path/output.zip [--include-build]\n");
    exit(1);
}

if (is_file($root.'/public/hot')) {
    fwrite(STDERR, "Gagal: public/hot ditemukan. Hapus file development Vite sebelum membuat rilis.\n");
    exit(1);
}

if ($includeBuild && ! is_file($root.'/public/build/manifest.json')) {
    fwrite(STDERR, "Gagal: --include-build memerlukan public/build/manifest.json dari build terbaru.\n");
    exit(1);
}

if ($includeBuild) {
    $metadataPath = $root.'/public/build/build-meta.json';
    $metadata = is_file($metadataPath)
        ? json_decode((string) file_get_contents($metadataPath), true)
        : null;

    try {
        $source = calculateFrontendSourceHash($root);
    } catch (RuntimeException $exception) {
        fwrite(STDERR, 'Gagal: '.$exception->getMessage()."\n");
        exit(1);
    }

    if (! is_array($metadata) || ($metadata['source_hash'] ?? null) !== $source['hash']) {
        fwrite(STDERR, "Gagal: build frontend tidak cocok dengan source saat ini. Jalankan npm run build.\n");
        exit(1);
    }

    if (($metadata['source_files'] ?? null) !== count($source['files'])) {
        fwrite(STDERR, "Gagal: metadata jumlah source frontend tidak valid. Jalankan npm run build.\n");
        exit(1);
    }

    if (! frontendBuildContainsSourceHash($root, $source['hash'])) {
        fwrite(STDERR, "Gagal: aset build tidak memuat fingerprint source saat ini. Jalankan npm run build.\n");
        exit(1);
    }

    try {
        $build = calculateFrontendBuildHash($root);
    } catch (RuntimeException $exception) {
        fwrite(STDERR, 'Gagal: '.$exception->getMessage()."\n");
        exit(1);
    }

    if (
        ($metadata['build_hash'] ?? null) !== $build['hash']
        || ($metadata['build_files'] ?? null) !== count($build['files'])
    ) {
        fwrite(STDERR, "Gagal: integritas file build frontend tidak cocok. Jalankan npm run build.\n");
        exit(1);
    }
}

$output = str_starts_with($outputArgument, DIRECTORY_SEPARATOR)
    ? $outputArgument
    : getcwd().DIRECTORY_SEPARATOR.$outputArgument;
$outputDirectory = dirname($output);

if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0775, true) && ! is_dir($outputDirectory)) {
    fwrite(STDERR, "Gagal membuat direktori output.\n");
    exit(1);
}

$excludedPrefixes = [
    '.git/', '.idea/', '.vscode/', '.cursor/', '.codex/', '.pytest_cache/',
    'vendor/', 'node_modules/', 'archive/', 'scratch/', 'desain_temp/', 'ocr-test/',
    'database/database.sqlite', 'public/hot', 'public/storage', 'public/fonts-manifest.dev.json',
    'bootstrap/cache/', 'storage/app/', 'storage/logs/', 'storage/framework/',
    'ocr-service/.venv/', 'ocr-service/.pytest_cache/', 'ocr-service/debug-output/',
    'opencode.json', 'RELEASE_MANIFEST.sha256',
];

if (! $includeBuild) {
    $excludedPrefixes[] = 'public/build/';
}

$excludedBasenames = [
    '.DS_Store', 'Thumbs.db', '.phpunit.result.cache',
];

$shouldExclude = static function (string $relative) use ($excludedPrefixes, $excludedBasenames, $output, $root): bool {
    $relative = str_replace('\\', '/', $relative);

    if (in_array(basename($relative), $excludedBasenames, true)) {
        return true;
    }

    $basename = basename($relative);
    if ($basename === '.env') {
        return true;
    }

    if (str_starts_with($basename, '.env.') && $basename !== '.env.example') {
        return true;
    }

    if (preg_match('/\.(zip|pyc|pyo)$/i', $relative)) {
        return true;
    }

    if (str_contains($relative, '/__pycache__/') || str_starts_with($relative, '__pycache__/')) {
        return true;
    }

    if (in_array($relative, [
        'bootstrap/cache/.gitignore',
        'storage/app/.gitignore',
        'storage/app/private/.gitignore',
        'storage/app/public/.gitignore',
        'storage/framework/.gitignore',
        'storage/framework/cache/.gitignore',
        'storage/framework/cache/data/.gitignore',
        'storage/framework/sessions/.gitignore',
        'storage/framework/testing/.gitignore',
        'storage/framework/views/.gitignore',
        'storage/logs/.gitignore',
    ], true)) {
        return false;
    }

    foreach ($excludedPrefixes as $prefix) {
        if ($relative === rtrim($prefix, '/') || str_starts_with($relative, $prefix)) {
            return true;
        }
    }

    $absolute = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

    return realpath($absolute) === realpath($output);
};

$zip = new ZipArchive;
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Gagal membuka output ZIP.\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY,
);

$added = 0;
$manifest = [];

foreach ($iterator as $file) {
    if (! $file->isFile() || $file->isLink()) {
        continue;
    }

    $absolute = $file->getPathname();
    $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($root))), '/');

    if ($shouldExclude($relative)) {
        continue;
    }

    $entry = 'siperbang/'.$relative;
    if (! $zip->addFile($absolute, $entry)) {
        $zip->close();
        @unlink($output);
        fwrite(STDERR, "Gagal menambahkan {$relative}.\n");
        exit(1);
    }

    $manifest[] = hash_file('sha256', $absolute).'  '.$relative;
    $added++;
}

sort($manifest);
$zip->addFromString(
    'siperbang/RELEASE_MANIFEST.sha256',
    implode("\n", $manifest)."\n",
);
$zip->close();

fwrite(STDOUT, "Paket dibuat: {$output}\n");
fwrite(STDOUT, "Berkas source: {$added}\n");
fwrite(STDOUT, 'Build frontend: '.($includeBuild ? 'disertakan' : 'tidak disertakan')."\n");
