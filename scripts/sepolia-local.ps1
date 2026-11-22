param([ValidateSet('Start', 'Stop', 'Status', 'Configure', 'Deploy', 'Reset')][string]$Action = 'Status')
# Red Ethereum local compatible con Sepolia para validación y demostración controlada.
# No es la testnet pública: solo escucha en 127.0.0.1 y no envía nada a Internet.
#   Start      inicia la red (reconstruye su cadena a partir del diario local)
#   Stop       la detiene
#   Status     indica si está en marcha, su último bloque y sus transacciones
#   Configure  apunta el entorno de presentación a la red local (solo archivos .env privados)
#   Deploy     despliega TraceabilityRegistry en la red local y guarda su dirección en .env
#   Reset      vacía la cadena local (con la red detenida)
$ErrorActionPreference = 'Stop'
# Nada sale de este equipo: tampoco la telemetria de Hardhat.
$env:HARDHAT_DISABLE_TELEMETRY = 'true'
$projectRoot = Split-Path -Parent $PSScriptRoot
$blockchain = Join-Path $projectRoot 'blockchain'
$node = Join-Path $projectRoot '.tools\node\node-v24.22.1-win-x64\node.exe'
$php = Join-Path $projectRoot '.tools\php\php.exe'
$simulator = Join-Path $blockchain 'scripts\sepolia-local.mjs'
if (-not (Test-Path -LiteralPath $node) -or -not (Test-Path -LiteralPath $php)) {
    throw 'Preparar los binarios oficiales indicados en docs/DEVELOPMENT_SETUP.md.'
}
if (-not (Test-Path -LiteralPath (Join-Path $blockchain 'node_modules\hardhat'))) {
    throw 'Instalar primero las dependencias del modulo blockchain (npm ci en blockchain/).'
}

function Invoke-Simulator([string[]]$Arguments, [switch]$Quiet) {
    Push-Location $blockchain
    try {
        # La salida va a la consola; la funcion devuelve unicamente el codigo de salida.
        if ($Quiet) { & $node $simulator @Arguments | Out-Null } else { & $node $simulator @Arguments | Out-Host }
        return $LASTEXITCODE
    } finally { Pop-Location }
}
function Clear-ApplicationConfiguration {
    Push-Location $projectRoot
    try { & $php artisan config:clear | Out-Null } finally { Pop-Location }
}

switch ($Action) {
    'Start' {
        if ((Invoke-Simulator @('status') -Quiet) -ne 0) {
            $data = Join-Path $blockchain 'runtime-data\sepolia-local'
            New-Item -ItemType Directory -Path $data -Force | Out-Null
            $started = Start-Process -FilePath $node -ArgumentList @("`"$simulator`"", 'serve') -WorkingDirectory $blockchain -WindowStyle Hidden -PassThru `
                -RedirectStandardOutput (Join-Path $data 'simulator.out.log') -RedirectStandardError (Join-Path $data 'simulator.err.log')
            for ($attempt = 0; $attempt -lt 120; $attempt++) {
                if ($started.HasExited) { throw 'La red local se detuvo al arrancar. Revise blockchain/runtime-data/sepolia-local/simulator.err.log.' }
                if ((Invoke-Simulator @('status') -Quiet) -eq 0) { break }
                Start-Sleep -Milliseconds 250
            }
        }
        if ((Invoke-Simulator @('status')) -ne 0) { throw 'La red local no respondio.' }
    }
    'Stop' {
        # Tambien detiene una red que aun esta reconstruyendo su cadena; si no hay ninguna, no es un error.
        if ((Invoke-Simulator @('stop')) -ne 0) { throw 'No se pudo detener la red local.' }
    }
    'Status' { Invoke-Simulator @('status') | Out-Null }
    'Configure' {
        if ((Invoke-Simulator @('configure')) -ne 0) { throw 'No se configuro el entorno de simulacion local.' }
        Clear-ApplicationConfiguration
    }
    'Deploy' {
        if ((Invoke-Simulator @('status') -Quiet) -ne 0) { throw 'Inicie primero la red local (-Action Start).' }
        Push-Location $blockchain
        try {
            # Mismo contrato y mismo script de despliegue que usara la testnet publica.
            if (-not (Test-Path -LiteralPath 'artifacts\contracts\TraceabilityRegistry.sol\TraceabilityRegistry.json')) {
                & $node 'node_modules\hardhat\dist\src\cli.js' compile
                if ($LASTEXITCODE -ne 0) { throw 'El contrato no compilo.' }
            }
            # El script se niega a firmar si la configuracion no apunta al entorno de simulacion local.
            & $node 'scripts\deploy.mjs' 'local-simulation'
            if ($LASTEXITCODE -ne 0) { throw 'El contrato no se desplego en la red local.' }
        } finally { Pop-Location }
        Clear-ApplicationConfiguration
    }
    'Reset' {
        if ((Invoke-Simulator @('reset')) -ne 0) { throw 'No se vacio la cadena local.' }
    }
}
