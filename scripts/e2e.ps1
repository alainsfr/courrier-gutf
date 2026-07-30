#Requires -Version 5.1
<#
.SYNOPSIS
    Lance le parcours de test End-to-End (E2E) COURRIER GUTF.

.DESCRIPTION
    Exécute la suite PHPUnit "E2E" qui couvre le circuit métier complet :
    auth, dashboard, registre, création, pièces jointes, workflow multi-bureaux,
    kanban scoped, validation/rejet, archivage, messagerie, notifications,
    recherche, exports, profil, administration et contrôles d'accès.

.EXAMPLE
    .\scripts\e2e.ps1
    .\scripts\e2e.ps1 -Filter "messagerie"
    .\scripts\e2e.ps1 -VerboseOutput
#>
param(
    [string]$Filter = "",
    [switch]$VerboseOutput
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

Write-Host ""
Write-Host "══════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  COURRIER GUTF — Tests End-to-End (E2E)" -ForegroundColor Cyan
Write-Host "══════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  Répertoire : $root"
Write-Host ""

if (-not (Test-Path "vendor\bin\phpunit") -and -not (Test-Path "vendor\autoload.php")) {
    Write-Host "Dépendances manquantes. Exécutez : composer install" -ForegroundColor Red
    exit 1
}

$argsList = @("artisan", "test", "--testsuite=E2E")
if ($Filter -ne "") {
    $argsList += @("--filter", $Filter)
}
if ($VerboseOutput) {
    $argsList += "--testdox"
}

Write-Host ("Commande : php {0}" -f ($argsList -join " ")) -ForegroundColor DarkGray
Write-Host ""

& php @argsList
$code = $LASTEXITCODE

Write-Host ""
if ($code -eq 0) {
    Write-Host "E2E OK — parcours applicatif validé." -ForegroundColor Green
} else {
    Write-Host "E2E ÉCHEC (code $code)." -ForegroundColor Red
}
Write-Host ""

exit $code
