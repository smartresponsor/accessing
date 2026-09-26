$ErrorActionPreference = 'Stop'

& composer run-script schema:parity
$exitCode = $LASTEXITCODE

Write-Host "CMCP_SCHEMA_PARITY_EXIT=$exitCode"
exit $exitCode
