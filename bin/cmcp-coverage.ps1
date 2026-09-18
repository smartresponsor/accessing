$ErrorActionPreference = 'Stop'
$env:XDEBUG_MODE = 'coverage'

if (-not (Test-Path 'var/coverage')) {
    New-Item -ItemType Directory -Path 'var/coverage' | Out-Null
}

& php vendor/bin/phpunit --testsuite Accessing --coverage-text=var/coverage/line-summary.txt
$exitCode = $LASTEXITCODE

Write-Host "CMCP_COVERAGE_EXIT=$exitCode"
exit $exitCode
