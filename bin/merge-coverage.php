<?php

declare(strict_types=1);

$coverageDirectory = dirname(__DIR__).'/var/coverage';
$methodPath = $coverageDirectory.'/line-summary.txt';
$branchPath = $coverageDirectory.'/summary.txt';

if (!is_file($methodPath) || !is_file($branchPath)) {
    fwrite(STDERR, "Coverage inputs are missing.\n");
    exit(1);
}

$methodText = (string) file_get_contents($methodPath);
$branchText = (string) file_get_contents($branchPath);

/**
 * @return array{covered: int, total: int}
 */
$metric = static function (string $coverageText, string $name): array {
    $coverageText = preg_replace('/\\x1B\\[[0-?]*[ -\\/]*[@-~]/', '', $coverageText) ?? $coverageText;
    if (1 !== preg_match('/^\\s*'.preg_quote($name, '/').':\\s+\\S+\\s+\\(\\s*(\\d+)\\s*\/\\s*(\\d+)\\s*\\)\\s*$/mi', $coverageText, $match)) {
        throw new RuntimeException(sprintf('Coverage input is missing the %s metric.', $name));
    }

    $covered = (int) $match[1];
    $total = (int) $match[2];
    if ($covered < 0 || $total < 0 || $covered > $total) {
        throw new RuntimeException(sprintf('Coverage input contains an invalid %s metric.', $name));
    }

    return ['covered' => $covered, 'total' => $total];
};

$percentage = static fn (array $value): float => 0 === $value['total']
    ? 100.0
    : 100.0 * $value['covered'] / $value['total'];

try {
    $methods = $metric($methodText, 'Methods');
    $methodLines = $metric($methodText, 'Lines');
    $branchMethods = $metric($branchText, 'Methods');
    $branchLines = $metric($branchText, 'Lines');
    $branches = $metric($branchText, 'Branches');

    if ($methods['total'] !== $branchMethods['total']) {
        throw new RuntimeException('Coverage runs used different method populations.');
    }
    if ($methodLines !== $branchLines) {
        throw new RuntimeException('Coverage runs produced different line populations or execution counts.');
    }

    $summary = sprintf(
        "Code Coverage Report:\n".
        "  Producer: PHPUnit standard method/line coverage + Xdebug path-instrumented branch coverage\n".
        "  Methods: %.2f%% (%d/%d)\n".
        "  Branches: %.2f%% (%d/%d)\n".
        "  Lines: %.2f%% (%d/%d)\n",
        $percentage($methods),
        $methods['covered'],
        $methods['total'],
        $percentage($branches),
        $branches['covered'],
        $branches['total'],
        $percentage($methodLines),
        $methodLines['covered'],
        $methodLines['total'],
    );

    if (false === file_put_contents($branchPath, $summary)) {
        throw new RuntimeException('Unable to write the canonical coverage summary.');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}

fwrite(STDOUT, $summary);
