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
$schemaParity = in_array('--schema-parity', $argv, true);

$env = load_env($projectDir . '/deploy/docker/.env');

$dbName = $env['ACCESSING_POSTGRES_DB'] ?? 'accessing_test';
$dbUser = $env['ACCESSING_POSTGRES_USER'] ?? 'app';
$dbPassword = $env['ACCESSING_POSTGRES_PASSWORD'] ?? 'app';
$dbHost = $insideDocker ? 'postgres' : '127.0.0.1';
$dbPort = $insideDocker ? '5432' : ($env['ACCESSING_POSTGRES_PORT'] ?? '54329');

if ($schemaParity && !$insideDocker) {
    $hostConnection = resolve_host_database_connection($projectDir);
    $dbHost = $hostConnection['host'];
    $dbPort = $hostConnection['port'];
    $dbUser = $hostConnection['user'];
    $dbPassword = $hostConnection['password'];
}

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

if ($schemaParity) {
    $adminDatabaseUrl = sprintf(
        'pgsql://%s:%s@%s:%s/postgres?serverVersion=17&charset=utf8',
        rawurlencode($dbUser),
        rawurlencode($dbPassword),
        $dbHost,
        $dbPort,
    );
    wait_pg($adminDatabaseUrl, 30);
} else {
    wait_pg($databaseUrl, 30);
}

if ($schemaParity) {
    $console = $projectDir . '/bin/console';
    $commands = [
        ['doctrine:database:drop', '--if-exists', '--force', '--env=test'],
        ['doctrine:database:create', '--if-not-exists', '--env=test'],
        ['doctrine:migrations:migrate', '--no-interaction', '--env=test'],
        ['doctrine:schema:validate', '--env=test'],
        ['doctrine:migrations:up-to-date', '--env=test'],
    ];

    foreach ($commands as $arguments) {
        $label = implode(' ', $arguments);
        fwrite(STDOUT, "[schema-parity] $label\n");
        $exitCode = run_proc(
            array_merge([PHP_BINARY, $console], $arguments),
            $projectDir,
            $processEnv,
            120,
            $label,
        );

        if ($exitCode !== 0) {
            exit($exitCode);
        }
    }

    fwrite(STDOUT, "PostgreSQL schema parity completed successfully.\n");
    exit(0);
}

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

/**
 * @return array{host: string, port: string, user: string, password: string}
 */
function resolve_host_database_connection(string $projectDir): array
{
    $resolver = dirname($projectDir) . '/app/tools/resolve-database-url.php';
    if (!is_file($resolver)) {
        fwrite(STDERR, "Host PostgreSQL resolver was not found at $resolver.\n");
        exit(1);
    }

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open([PHP_BINARY, $resolver], $descriptorSpec, $pipes, dirname($resolver));
    if (!is_resource($process)) {
        fwrite(STDERR, "Unable to execute host PostgreSQL resolver.\n");
        exit(1);
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0 || $stdout === false) {
        fwrite(STDERR, "Host PostgreSQL resolver failed.\n");
        if (is_string($stderr) && trim($stderr) !== '') {
            fwrite(STDERR, trim($stderr) . "\n");
        }
        exit(1);
    }

    $values = [];
    foreach (preg_split('/\R/', trim($stdout)) ?: [] as $line) {
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $encoded] = explode('=', $line, 2);
        $decoded = base64_decode($encoded, true);
        if ($decoded !== false) {
            $values[$key] = $decoded;
        }
    }

    foreach (['host', 'port', 'user', 'password'] as $required) {
        if (!isset($values[$required])) {
            fwrite(STDERR, "Host PostgreSQL resolver did not return $required.\n");
            exit(1);
        }
    }

    return [
        'host' => $values['host'],
        'port' => $values['port'],
        'user' => $values['user'],
        'password' => $values['password'],
    ];
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
    fwrite(STDERR, "Ensure the local PostgreSQL service is running and that www/app DATABASE_URL resolves to a reachable host.\n");
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
    $processExitCode = null;

    while (true) {
        foreach ([1 => STDOUT, 2 => STDERR] as $index => $target) {
            $chunk = stream_get_contents($pipes[$index]);
            if ($chunk !== false && $chunk !== '') {
                fwrite($target, $chunk);
            }
        }

        $status = proc_get_status($process);
        if ($status['running'] !== true) {
            if (is_int($status['exitcode']) && $status['exitcode'] >= 0) {
                $processExitCode = $status['exitcode'];
            }
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

    $closeExitCode = proc_close($process);

    return $processExitCode ?? $closeExitCode;
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
