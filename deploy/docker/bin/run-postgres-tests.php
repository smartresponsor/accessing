<?php

declare(strict_types=1);

/**
 * Runs the Accessing PHPUnit suite against the Docker PostgreSQL runtime.
 *
 * PostgreSQL-backed testing is intentionally separated from the default
 * `composer test` contour. The runner prepares a deterministic DATABASE_URL
 * and executes test files one by one so a hanging test is reported precisely.
 */

$projectDir = dirname(__DIR__, 3);
$insideDocker = in_array('--inside-docker', $argv, true);

$env = load_env($projectDir . '/deploy/docker/.env');

$dbName = $env['ACCESSING_POSTGRES_DB'] ?? 'accessing_test';
$dbUser = $env['ACCESSING_POSTGRES_USER'] ?? 'app';
$dbPassword = $env['ACCESSING_POSTGRES_PASSWORD'] ?? 'app';
$dbHost = $insideDocker ? 'postgres' : '127.0.0.1';
$dbPort = $insideDocker ? '5432' : ($env['ACCESSING_POSTGRES_PORT'] ?? '54329');

$databaseUrl = sprintf(
    'pgsql://%s:%s@%s:%s/%s?serverVersion=17&charset=utf8',
    rawurlencode($dbUser),
    rawurlencode($dbPassword),
    $dbHost,
    $dbPort,
    rawurlencode($dbName),
);

$processEnv = norm_env(array_merge($_ENV, $_SERVER, [
    'APP_ENV' => 'test',
    'APP_DEBUG' => '1',
    'KERNEL_CLASS' => 'App\Accessing\\Kernel',
    'DATABASE_URL' => $databaseUrl,
    'MAILER_DSN' => 'null://null',
    'ACCESSING_PHONE_VERIFICATION_PROVIDER' => 'fake',
    'ACCESSING_PHONE_VERIFICATION_DSN' => '',
]));

wait_pg($databaseUrl, 30);

$phpunit = $projectDir . '/vendor/symfony/phpunit-bridge/bin/simple-phpunit';
if (!is_file($phpunit)) {
    fwrite(STDERR, "Symfony PHPUnit bridge binary was not found at $phpunit.\n");
    exit(1);
}

$testFiles = test_files($projectDir, [
    'tests/Unit',
    'tests/Integration',
    'tests/Functional',
]);

if ($testFiles === []) {
    fwrite(STDERR, "No PostgreSQL test files were found.\n");
    exit(1);
}

foreach ($testFiles as $testFile) {
    $relative = rel_path($projectDir, $testFile);
    fwrite(STDOUT, "[postgres-test] $relative\n");

    $exitCode = run_proc([
        PHP_BINARY,
        $phpunit,
        '--colors=never',
        '--stop-on-error',
        '--stop-on-failure',
        $testFile,
    ], $projectDir, $processEnv, 90, $relative);

    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

fwrite(STDOUT, "PostgreSQL PHPUnit files completed successfully.\n");
exit(0);

/**
 * @return array<string, string>
 */
function load_env(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $values;
}

/**
 * @param array<array-key, mixed> $env
 *
 * @return array<string, string>
 */
function norm_env(array $env): array
{
    $normalized = [];

    foreach ($env as $key => $value) {
        if (!is_string($key) || $key === '' || is_array($value) || is_object($value) || is_resource($value)) {
            continue;
        }

        if ($value === null) {
            continue;
        }

        if (is_bool($value)) {
            $normalized[$key] = $value ? '1' : '0';
            continue;
        }

        if (is_scalar($value)) {
            $normalized[$key] = (string) $value;
        }
    }

    return $normalized;
}

function wait_pg(string $databaseUrl, int $timeoutSeconds): void
{
    if (!extension_loaded('pdo_pgsql')) {
        fwrite(STDERR, "pdo_pgsql extension is required for composer test:postgres.\n");
        exit(1);
    }

    $deadline = time() + $timeoutSeconds;
    $lastMessage = 'unknown connection error';

    do {
        try {
            $parts = parse_url($databaseUrl);
            if ($parts === false) {
                throw new RuntimeException('Invalid DATABASE_URL.');
            }

            $host = $parts['host'] ?? '127.0.0.1';
            $port = (string) ($parts['port'] ?? 5432);
            $db = isset($parts['path']) ? ltrim($parts['path'], '/') : 'accessing_test';
            $user = isset($parts['user']) ? rawurldecode($parts['user']) : 'app';
            $password = isset($parts['pass']) ? rawurldecode($parts['pass']) : 'app';

            $pdo = new PDO(sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db), $user, $password);
            $pdo->query('SELECT 1');

            return;
        } catch (Throwable $exception) {
            $lastMessage = $exception->getMessage();
            usleep(500_000);
        }
    } while (time() < $deadline);

    fwrite(STDERR, 'PostgreSQL test runtime is not ready: ' . $lastMessage . "\n");
    fwrite(STDERR, "Start it with: docker compose -f deploy/docker/compose.yaml --env-file deploy/docker/.env up -d postgres\n");
    exit(1);
}

/**
 * @param list<string> $directories
 *
 * @return list<string>
 */
function test_files(string $projectDir, array $directories): array
{
    $files = [];

    foreach ($directories as $directory) {
        $absoluteDirectory = $projectDir . '/' . $directory;
        if (!is_dir($absoluteDirectory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absoluteDirectory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $path = $fileInfo->getPathname();
            if (str_ends_with($path, 'Test.php')) {
                $files[] = $path;
            }
        }
    }

    sort($files);

    return array_values($files);
}

/**
 * @param list<string> $command
 * @param array<string, string> $env
 */
function run_proc(array $command, string $cwd, array $env, int $timeoutSeconds, string $label): int
{
    $descriptorSpec = [
        0 => STDIN,
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $cwd, $env);
    if (!is_resource($process)) {
        fwrite(STDERR, "Unable to start PHPUnit process for $label.\n");
        return 1;
    }

    foreach ([1, 2] as $index) {
        stream_set_blocking($pipes[$index], false);
    }

    $deadline = microtime(true) + $timeoutSeconds;

    while (true) {
        foreach ([1 => STDOUT, 2 => STDERR] as $index => $target) {
            $chunk = stream_get_contents($pipes[$index]);
            if ($chunk !== false && $chunk !== '') {
                fwrite($target, $chunk);
            }
        }

        $status = proc_get_status($process);
        if ($status['running'] !== true) {
            break;
        }

        if (microtime(true) >= $deadline) {
            fwrite(STDERR, "PostgreSQL PHPUnit file exceeded $timeoutSeconds seconds and was terminated: $label\n");
            proc_terminate($process);
            usleep(500_000);
            $status = proc_get_status($process);
            if ($status['running'] === true) {
                proc_terminate($process, 9);
            }

            foreach ([1 => STDOUT, 2 => STDERR] as $index => $target) {
                $chunk = stream_get_contents($pipes[$index]);
                if ($chunk !== false && $chunk !== '') {
                    fwrite($target, $chunk);
                }
            }

            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($process);

            return 124;
        }

        usleep(100_000);
    }

    foreach ([1 => STDOUT, 2 => STDERR] as $index => $target) {
        $chunk = stream_get_contents($pipes[$index]);
        if ($chunk !== false && $chunk !== '') {
            fwrite($target, $chunk);
        }
    }

    foreach ($pipes as $pipe) {
        fclose($pipe);
    }

    return proc_close($process);
}

function rel_path(string $projectDir, string $path): string
{
    $normalizedProjectDir = str_replace('\\', '/', rtrim($projectDir, '/\\'));
    $normalizedPath = str_replace('\\', '/', $path);

    if (str_starts_with($normalizedPath, $normalizedProjectDir . '/')) {
        return substr($normalizedPath, strlen($normalizedProjectDir) + 1);
    }

    return $normalizedPath;
}
