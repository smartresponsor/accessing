$ErrorActionPreference = 'Stop'

& npm run test:e2e
$exitCode = $LASTEXITCODE

Write-Host "CMCP_PLAYWRIGHT_EXIT=$exitCode"
exit $exitCode
