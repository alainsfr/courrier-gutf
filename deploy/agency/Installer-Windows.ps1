[CmdletBinding()]
param(
    [string]$ServeurLinux,
    [string]$Instance,
    [string]$Url,
    [ValidateRange(1024,65535)][int]$Port = 8088,
    [string]$AdresseEcoute = '127.0.0.1',
    [string]$EmailAdministrateur,
    [string]$CleSsh,
    [switch]$VerifierSeulement
)
$ErrorActionPreference='Stop'
function Quote-Shell([string]$Value) {
    $escapedQuote="'"+[char]34+"'"+[char]34+"'"
    return "'" + $Value.Replace("'", $escapedQuote) + "'"
}
function Check-Exit { if($LASTEXITCODE -ne 0){ throw "La commande précédente a échoué ($LASTEXITCODE)." } }
$checksums=Get-Content -LiteralPath (Join-Path $PSScriptRoot 'checksums.json') -Raw | ConvertFrom-Json
foreach($entry in $checksums.PSObject.Properties){
    $root=[IO.Path]::GetFullPath($PSScriptRoot)+[IO.Path]::DirectorySeparatorChar
    $path=[IO.Path]::GetFullPath((Join-Path $PSScriptRoot $entry.Name))
    if(-not $path.StartsWith($root,[StringComparison]::OrdinalIgnoreCase)){ throw 'Chemin de paquet invalide.' }
    if((Get-FileHash -LiteralPath $path -Algorithm SHA256).Hash.ToLowerInvariant() -ne $entry.Value){ throw "Fichier altéré : $($entry.Name)" }
}
if($VerifierSeulement){ Write-Output 'Intégrité du paquet vérifiée. Les images sont destinées à une VM Linux x86_64.'; return }
if(-not $ServeurLinux){ $ServeurLinux=Read-Host 'Connexion SSH à la VM Linux (utilisateur@adresse)' }
if(-not $Instance){ $Instance=Read-Host 'Identifiant agence (ex. agence-centre)' }
if(-not $Url){ $Url=Read-Host "URL complète de l'application" }
if(-not $EmailAdministrateur){ $EmailAdministrateur=Read-Host 'Adresse du premier administrateur' }
if($ServeurLinux -notmatch '^[a-z_][a-z0-9_-]*@[a-zA-Z0-9][a-zA-Z0-9.-]*$'){ throw 'Destination SSH invalide.' }
if($Instance -notmatch '^[a-z][a-z0-9-]{2,29}$'){ throw 'Identifiant agence invalide.' }
if($Url -notmatch '^https?://[a-zA-Z0-9.:-]+/?$'){ throw 'URL invalide.' }
if($EmailAdministrateur -notmatch '^[^\s@]+@[^\s@]+\.[^\s@]+$'){ throw 'Adresse administrateur invalide.' }
$parsedIp=$null
if(-not [Net.IPAddress]::TryParse($AdresseEcoute,[ref]$parsedIp) -or $parsedIp.AddressFamily -ne [Net.Sockets.AddressFamily]::InterNetwork){ throw 'Adresse IPv4 attendue.' }
Get-Command ssh,scp -ErrorAction Stop | Out-Null
$identity=@()
if($CleSsh){ $identity=@('-i',(Resolve-Path -LiteralPath $CleSsh).Path) }
$remote='/tmp/courrier-gutf-'+[Guid]::NewGuid().ToString('N')
& ssh @identity $ServeurLinux ('umask 077 && mkdir '+(Quote-Shell $remote)); Check-Exit
$transferPaths=@(Get-ChildItem -LiteralPath $PSScriptRoot -Force | ForEach-Object FullName)
& scp @identity -r @transferPaths ($ServeurLinux+':'+$remote+'/'); Check-Exit
$command='sudo bash '+(Quote-Shell ($remote+'/install-linux.sh'))+' --instance '+(Quote-Shell $Instance)+' --url '+(Quote-Shell $Url)+' --port '+$Port+' --bind '+(Quote-Shell $AdresseEcoute)+' --admin-email '+(Quote-Shell $EmailAdministrateur)
& ssh @identity -t $ServeurLinux $command; Check-Exit
Write-Output "Installation terminée dans la VM. Les fichiers transférés restent dans $remote ; ils peuvent être retirés après validation."
