param([ValidateSet('start', 'status', 'stop')][string]$Action = 'status')
$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Split-Path -Parent $PSScriptRoot))
$phpPath = Join-Path $projectRoot '.tools\php\php.exe'
$artisanPath = Join-Path $projectRoot 'artisan'
$runtimePath = Join-Path $projectRoot '.local\release-scheduler'
$pidPath = Join-Path $runtimePath 'worker.pid'

function Get-ProjectScheduler {
    if (-not (Test-Path -LiteralPath $pidPath)) { return $null }
    $workerId = 0
    if (-not [int]::TryParse(([IO.File]::ReadAllText($pidPath)).Trim(), [ref]$workerId)) { return $null }
    $worker = Get-CimInstance Win32_Process -Filter "ProcessId = $workerId" -ErrorAction SilentlyContinue
    if ($worker -and $worker.ExecutablePath -eq $phpPath -and $worker.CommandLine.Contains($artisanPath) -and $worker.CommandLine.Contains('schedule:work')) { return $worker }
    return $null
}

$existing = Get-ProjectScheduler
if ($Action -eq 'status') {
    if ($existing) { Write-Output 'Scheduler local de ESPACIO-U activo.' }
    else { Write-Output 'Scheduler local de ESPACIO-U detenido.' }
    exit 0
}
if ($Action -eq 'stop') {
    if ($existing) { Stop-Process -Id $existing.ProcessId }
    if (Test-Path -LiteralPath $pidPath) { Remove-Item -LiteralPath $pidPath }
    Write-Output 'Scheduler local de ESPACIO-U detenido. Los servicios MySQL no se modificaron.'
    exit 0
}
if ($existing) { Write-Output 'El scheduler local ya está activo.'; exit 0 }
if (-not (Test-Path -LiteralPath $phpPath)) { throw 'Active el runtime local documentado en DEVELOPMENT_SETUP.md.' }
New-Item -ItemType Directory -Force -Path $runtimePath | Out-Null
$worker = Start-Process -FilePath $phpPath -ArgumentList @(('"' + $artisanPath + '"'), 'schedule:work') -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $runtimePath 'stdout.log') -RedirectStandardError (Join-Path $runtimePath 'stderr.log')
[IO.File]::WriteAllText($pidPath, [string]$worker.Id)
Write-Output 'Scheduler local iniciado; ejecuta la planificación Laravel mientras este proceso permanezca activo.'
