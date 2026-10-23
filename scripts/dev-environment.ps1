$projectRoot = Split-Path -Parent $PSScriptRoot
$phpDirectory = Join-Path $projectRoot '.tools\php'
$nodeDirectory = Join-Path $projectRoot '.tools\node\node-v24.22.1-win-x64'
if (-not (Test-Path -LiteralPath (Join-Path $phpDirectory 'php.exe')) -or -not (Test-Path -LiteralPath (Join-Path $nodeDirectory 'node.exe'))) {
    throw 'Preparar los runtimes oficiales de docs/DEVELOPMENT_SETUP.md o usar instalaciones globales compatibles.'
}
$env:Path = $phpDirectory + ';' + $nodeDirectory + ';' + (Join-Path $projectRoot '.tools') + ';' + $env:Path
Write-Output 'PHP y Node del proyecto activos en esta terminal; sin modificar el PATH del sistema.'
