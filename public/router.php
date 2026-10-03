<?php

declare(strict_types=1);

if ('cli-server' !== PHP_SAPI) {
    return false;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$publicFile = __DIR__.($path ?: '/');

if ('/' !== $path && is_file($publicFile)) {
    return false;
}

$interfacingPublicRoot = dirname(__DIR__).'/vendor/interfacing/interface/public';
$interfacingRelativePath = null;

if (is_string($path) && str_starts_with($path, '/bundles/interfacing/')) {
    $interfacingRelativePath = substr($path, strlen('/bundles/interfacing/'));
} elseif ('/mandala.svg' === $path) {
    $interfacingRelativePath = 'mandala.svg';
}

if (null !== $interfacingRelativePath) {
    $root = realpath($interfacingPublicRoot);
    $candidate = realpath($interfacingPublicRoot.'/'.$interfacingRelativePath);

    if (false !== $root && false !== $candidate && str_starts_with($candidate, $root.DIRECTORY_SEPARATOR) && is_file($candidate)) {
        $contentType = match (strtolower(pathinfo($candidate, PATHINFO_EXTENSION))) {
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'json' => 'application/json; charset=UTF-8',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        header('Content-Type: '.$contentType);
        header('Content-Length: '.(string) filesize($candidate));
        readfile($candidate);

        return true;
    }
}

$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/index.php';
require $_SERVER['SCRIPT_FILENAME'];

