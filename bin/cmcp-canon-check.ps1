param()

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$gatingRoot = Join-Path $repoRoot 'vendor\gating\gate'
$gatingBin = Join-Path $gatingRoot 'bin\gating'
$policyRoot = Join-Path $gatingRoot '.gating'
$severityConfig = Join-Path $policyRoot 'config\severity.yaml'
$varRoot = Join-Path $repoRoot 'var\cmcp'
$ruleSetPath = Join-Path $varRoot 'canon-rule-set.yaml'
$reportPath = Join-Path $varRoot ('canon-report-{0}.json' -f ([guid]::NewGuid().ToString('N')))

if (-not (Test-Path -LiteralPath $gatingBin -PathType Leaf)) {
    throw "Installed Gating CLI is missing: $gatingBin"
}

if (-not (Test-Path -LiteralPath $severityConfig -PathType Leaf)) {
    throw "Installed Gating severity config is missing: $severityConfig"
}

New-Item -ItemType Directory -Force -Path $varRoot | Out-Null

$catalogText = (& php $gatingBin list-rules --json | Out-String)
if ($LASTEXITCODE -ne 0) {
    throw 'Unable to read the installed Gating rule catalog.'
}

$catalog = $catalogText | ConvertFrom-Json
$canonRuleIds = @(
    $catalog.rules |
        Where-Object { [string]$_.id -like 'canon.*' } |
        ForEach-Object { [string]$_.id }
)

if ($canonRuleIds.Count -eq 0) {
    throw 'Installed Gating exposes no canon.* rules.'
}

$ruleSetLines = @(
    'rule_set:',
    '  name: cmcp-canon-check',
    '  rules:'
) + @($canonRuleIds | ForEach-Object { "    - $_" })

$ruleSetLines | Set-Content -LiteralPath $ruleSetPath -Encoding UTF8

$arguments = @(
    $gatingBin,
    'check',
    "--target=$repoRoot",
    "--rule-set=$ruleSetPath",
    "--policy-root=$policyRoot",
    "--severity-config=$severityConfig",
    "--report-file=$reportPath",
    '--format=json'
)

& php @arguments | Out-Null
$gatingExitCode = $LASTEXITCODE
if ($gatingExitCode -ne 0) {
    throw "Gating canon check failed with exit code $gatingExitCode."
}

if (-not (Test-Path -LiteralPath $reportPath -PathType Leaf)) {
    throw 'Gating did not produce a canon report.'
}

$report = Get-Content -LiteralPath $reportPath -Raw | ConvertFrom-Json
$canonResults = @($report.results | Where-Object { [string]$_.rule -like 'canon.*' })
$failed = @($canonResults | Where-Object { [string]$_.status -eq 'failed' })

foreach ($result in $canonResults) {
    Write-Output ("[{0}] {1} - {2}" -f ([string]$result.status).ToUpperInvariant(), [string]$result.rule, [string]$result.message)
}

if ($failed.Count -gt 0) {
    Write-Output ("CMCP canon check: FAIL ({0} failed canon rule(s))." -f $failed.Count)
    exit 1
}

Write-Output ("CMCP canon check: PASS ({0} canon rule(s), no failures)." -f $canonResults.Count)
exit 0
