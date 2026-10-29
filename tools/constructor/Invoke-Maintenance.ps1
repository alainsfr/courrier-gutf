[CmdletBinding()]
param(
    [Parameter(Mandatory=$true)][ValidatePattern('^gutf-[a-f0-9]{12}@[0-9.]+$')][string]$Agence,
    [Parameter(Mandatory=$true)][ValidateSet('status','diagnostic','request','import','backup','restart')][string]$Action,
    [ValidatePattern('^[a-z_][a-z0-9_-]*@[a-zA-Z0-9][a-zA-Z0-9.-]*$')][string]$Relais,
    [string]$CleSsh,
    [string]$Fichier,
    [string]$Sortie
)
$ErrorActionPreference='Stop'
if($Action -eq 'import' -and -not $Fichier){throw 'Fichier signe requis.'}
if($Action -eq 'request' -and -not $Sortie){throw 'Chemin de sortie requis pour la demande.'}
if($Sortie -and (Test-Path -LiteralPath $Sortie)){throw 'La sortie existe deja.'}
$arguments=@()
if($Relais){$arguments+=@('-J',$Relais)}
if($CleSsh){$arguments+=@('-i',(Resolve-Path -LiteralPath $CleSsh).Path)}
$arguments+=@($Agence,$Action)
$start=New-Object Diagnostics.ProcessStartInfo
$start.FileName=(Get-Command ssh -ErrorAction Stop).Source
$start.UseShellExecute=$false
$start.RedirectStandardInput=$true
$start.RedirectStandardOutput=$true
# Arguments contain only validated destinations, fixed verbs and an existing Windows path.
$start.Arguments=($arguments|ForEach-Object {'"'+$_.Replace('"','\"')+'"'}) -join ' '
$process=New-Object Diagnostics.Process
$process.StartInfo=$start
$destination=$null
try{
    if($Fichier){
        $bytes=[IO.File]::ReadAllBytes((Resolve-Path -LiteralPath $Fichier).Path)
        if($bytes.Length -gt 16384 -or $bytes.Length -eq 0){throw 'Taille de fichier invalide.'}
    }
    if($Sortie){$destination=[IO.File]::Open([IO.Path]::GetFullPath($Sortie),[IO.FileMode]::CreateNew,[IO.FileAccess]::Write)}
    else{$destination=[Console]::OpenStandardOutput()}
    [void]$process.Start()
    if($Action -eq 'import'){$process.StandardInput.BaseStream.Write($bytes,0,$bytes.Length)}
    $process.StandardInput.Close()
    $process.StandardOutput.BaseStream.CopyTo($destination)
    $destination.Flush()
    $process.WaitForExit()
    if($process.ExitCode -ne 0){throw "Maintenance interrompue ($($process.ExitCode))."}
}finally{
    if($Sortie -and $destination){$destination.Dispose()}
    $process.Dispose()
}
