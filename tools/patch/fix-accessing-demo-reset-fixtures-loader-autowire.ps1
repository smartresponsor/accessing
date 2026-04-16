# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
# Add explicit Symfony service binding for Doctrine fixtures loader in AccessingDemoResetCommand.
# Run from the Accessing repository root.

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = (Get-Location).Path
$path = Join-Path $root 'src/Command/AccessingDemoResetCommand.php'

if (-not (Test-Path $path)) {
    throw "File not found: $path"
}

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupRoot = Join-Path $root "var/patch-backup/accessing-demo-reset-fixtures-loader-autowire-$timestamp"
New-Item -ItemType Directory -Force -Path (Join-Path $backupRoot 'src/Command') | Out-Null
Copy-Item -Path $path -Destination (Join-Path $backupRoot 'src/Command/AccessingDemoResetCommand.php') -Force

$content = Get-Content $path -Raw

if ($content -notmatch 'Symfony\\Component\\DependencyInjection\\Attribute\\Autowire') {
    if ($content -match 'use Symfony\\Component\\Console\\Attribute\\AsCommand;\r?\n') {
        $content = $content -replace '(use Symfony\\Component\\Console\\Attribute\\AsCommand;\r?\n)', '$1use Symfony\Component\DependencyInjection\Attribute\Autowire;' + "`r`n"
    } else {
        throw 'Could not find AsCommand import anchor.'
    }
}

if ($content -notmatch "Autowire\(service:\s*'doctrine\.fixtures\.loader'\)") {
    $content = $content -replace '(\s*)private readonly SymfonyFixturesLoader \$fixturesLoader,', '$1#[Autowire(service: ''doctrine.fixtures.loader'')]' + "`r`n" + '$1private readonly SymfonyFixturesLoader $fixturesLoader,'
}

Set-Content -Path $path -Value $content -NoNewline

php -l $path

Write-Host 'AccessingDemoResetCommand fixtures loader autowire patch completed.'
Write-Host "Backup: $backupRoot"
