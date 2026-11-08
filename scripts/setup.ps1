param(
    [string]$DbUser = "root",
    [string]$DbPassword = "",
    [string]$AdminUsername = "admin",
    [string]$AdminEmail = "admin@example.test"
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$php = "C:\xampp\php\php.exe"
$mysql = "C:\xampp\mysql\bin\mysql.exe"

if (!(Test-Path $php) -or !(Test-Path $mysql)) {
    throw "XAMPP PHP/MySQL binaries were not found under C:\xampp."
}

$env:MYSQL_PWD = $DbPassword
try {
    Get-Content -LiteralPath "$root\database\schema.sql" | & $mysql --protocol=tcp --host=127.0.0.1 --user=$DbUser
    if ($LASTEXITCODE -ne 0) { throw "Database schema import failed." }
}
finally {
    Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue
}

$adminPassword = Read-Host "Administrator password (minimum 12 characters)" -AsSecureString
$bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($adminPassword)
try {
    $plainPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
    if ($plainPassword.Length -lt 12) { throw "Administrator password must be at least 12 characters." }
    $env:NETWATCH_DB_DSN = "mysql:host=127.0.0.1;dbname=netwatch;charset=utf8mb4"
    $env:NETWATCH_DB_USER = $DbUser
    $env:NETWATCH_DB_PASSWORD = $DbPassword
    & $php "$root\database\create_admin.php" $AdminUsername $AdminEmail $plainPassword
    if ($LASTEXITCODE -ne 0) { throw "Administrator creation failed." }
}
finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
}

Write-Host "NetWatch setup completed."
