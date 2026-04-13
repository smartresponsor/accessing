<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

$directories = [
    __DIR__ . '/../bin',
    __DIR__ . '/../config',
    __DIR__ . '/../migrations',
    __DIR__ . '/../public',
    __DIR__ . '/../src',
    __DIR__ . '/../tests',
];

$rootFiles = [
    __DIR__ . '/../config/bootstrap.php',
];

$files = [];

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
            continue;
        }

        if ($fileInfo->getFilename() === 'reference.php') {
            continue;
        }

        $files[] = $fileInfo->getPathname();
    }
}

foreach ($rootFiles as $rootFile) {
    if (is_file($rootFile)) {
        $files[] = $rootFile;
    }
}

$files = array_values(array_unique($files));
sort($files);

foreach ($files as $file) {
    passthru(sprintf('php -l %s', escapeshellarg($file)), $exitCode);

    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

fwrite(STDOUT, sprintf("Linted %d PHP files successfully.\n", count($files)));
