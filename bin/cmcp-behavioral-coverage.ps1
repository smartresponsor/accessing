$ErrorActionPreference = 'Stop'

& composer run-script test:behavioral-coverage
$exitCode = $LASTEXITCODE

Write-Host "CMCP_BEHAVIORAL_COVERAGE_EXIT=$exitCode"
exit $exitCode
