$ErrorActionPreference = "Stop"
$raiz = Split-Path -Parent $PSScriptRoot
$php = $null
$comando = Get-Command php -ErrorAction SilentlyContinue
if ($comando) {
    $php = $comando.Source
} elseif (Test-Path (Join-Path $raiz ".tools\php\php.exe")) {
    $php = Join-Path $raiz ".tools\php\php.exe"
}

if (-not $php) {
    Write-Output "No hay PHP en el PATH ni en .tools\php\php.exe."
    Write-Output "Instale PHP 8.1 o superior y ejecute, desde la raíz del proyecto:"
    Write-Output "php -S localhost:8080 -t public"
    exit 1
}

Write-Output "Sirviendo con $php"
Write-Output "Abra http://localhost:8080/"
& $php -S localhost:8080 -t (Join-Path $raiz "public")
