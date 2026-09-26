$ErrorActionPreference = 'Stop'

& composer run-script test:coverage
$exitCode = $LASTEXITCODE

Write-Host "CMCP_COVERAGE_EXIT=$exitCode"
exit $exitCode
