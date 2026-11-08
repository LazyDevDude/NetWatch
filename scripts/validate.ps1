$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$php = "C:\xampp\php\php.exe"

Get-ChildItem -Path $root -Recurse -Filter "*.php" | ForEach-Object {
    & $php -l $_.FullName
    if ($LASTEXITCODE -ne 0) { throw "PHP lint failed: $($_.FullName)" }
}
python -m py_compile "$root\monitoring-agent\agent.py"
$cache = "$root\monitoring-agent\__pycache__"
if (Test-Path $cache) { Remove-Item -LiteralPath $cache -Recurse -Force }
Write-Host "Static validation passed."
