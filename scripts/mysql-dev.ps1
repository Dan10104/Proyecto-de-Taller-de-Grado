param([ValidateSet('Init', 'Start', 'Stop', 'Status')][string]$Action = 'Status')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$mysqlBase = Join-Path $projectRoot '.tools\mysql\mysql-9.7.4-winx64'
$server = Join-Path $mysqlBase 'bin\mysqld.exe'
$client = Join-Path $mysqlBase 'bin\mysql.exe'
$localRoot = Join-Path $projectRoot '.local\mysql'
$dataDirectory = Join-Path $localRoot 'data'
$configPath = Join-Path $localRoot 'my.ini'
$envPath = Join-Path $projectRoot '.env'
$php = Join-Path $projectRoot '.tools\php\php.exe'
$port = 3308
if (-not (Test-Path -LiteralPath $server) -or -not (Test-Path -LiteralPath $php)) {
    throw 'Preparar los binarios oficiales indicados en docs/DEVELOPMENT_SETUP.md.'
}
function Invoke-BootstrapSql([string]$Sql) {
    $startInfo = [Diagnostics.ProcessStartInfo]::new()
    $startInfo.FileName = $client
    $startInfo.UseShellExecute = $false
    $startInfo.CreateNoWindow = $true
    $startInfo.RedirectStandardInput = $true
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    foreach ($argument in @('--no-defaults','--protocol=MEMORY','--shared-memory-base-name=ESPACIO_U_DEV_BOOTSTRAP','--user=root','--skip-password','--batch','--silent','--connect-timeout=2')) {
        $startInfo.ArgumentList.Add($argument)
    }
    $process = [Diagnostics.Process]::Start($startInfo)
    $process.StandardInput.WriteLine($Sql)
    $process.StandardInput.Close()
    $null = $process.StandardOutput.ReadToEnd()
    $null = $process.StandardError.ReadToEnd()
    $process.WaitForExit()
    return $process.ExitCode
}
if ($Action -eq 'Init') {
    if (Test-Path -LiteralPath $dataDirectory) { throw 'Ya existe un directorio de datos; no se reinicializa ni elimina.' }
    if (-not (Test-Path -LiteralPath $envPath)) { throw 'Crear primero .env desde .env.example.' }
    if (Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue) { throw 'El puerto de desarrollo esta ocupado.' }
    New-Item -ItemType Directory -Path $localRoot -Force | Out-Null
    $currentSid = [Security.Principal.WindowsIdentity]::GetCurrent().User.Value
    & icacls $localRoot /inheritance:r /grant:r "*$($currentSid):(OI)(CI)F" '*S-1-5-18:(OI)(CI)F' | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo restringir el directorio de datos local.' }
    $envText = [IO.File]::ReadAllText($envPath)
    foreach ($key in @('DB_USERNAME','DB_PASSWORD','MYSQL_DEV_ROOT_PASSWORD')) {
        if ($envText -notmatch "(?m)^$key=\s*$") { throw 'La inicializacion requiere campos de credenciales vacios; no se sobrescriben secretos existentes.' }
    }
    $appUser = 'local_' + [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(8)).ToLowerInvariant()
    $appPassword = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
    $adminPassword = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
    $updates = @{DB_CONNECTION='mysql';DB_HOST='127.0.0.1';DB_PORT=[string]$port;DB_DATABASE='espacio_u';DB_USERNAME=$appUser;DB_PASSWORD=$appPassword;MYSQL_DEV_ROOT_PASSWORD=$adminPassword}
    foreach ($entry in $updates.GetEnumerator()) {
        $envText = [regex]::Replace($envText, '(?m)^'+[regex]::Escape($entry.Key)+'=.*$', $entry.Key+'='+$entry.Value)
    }
    [IO.File]::WriteAllText($envPath, $envText, [Text.UTF8Encoding]::new($false))
    & icacls $envPath /inheritance:r /grant:r "*$($currentSid):F" '*S-1-5-18:F' | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'No se pudieron restringir permisos de .env.' }
    $configuration = @"
[mysqld]
basedir=$($mysqlBase.Replace('\','/'))
datadir=$($dataDirectory.Replace('\','/'))
port=$port
bind-address=127.0.0.1
mysqlx=OFF
skip-name-resolve
skip-log-bin
general-log=OFF
slow-query-log=OFF
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
default-time-zone=-04:00
innodb-buffer-pool-size=128M
max-connections=30
log-error=$($localRoot.Replace('\','/'))/mysql-error.log
pid-file=$($localRoot.Replace('\','/'))/mysql.pid
"@
    [IO.File]::WriteAllText($configPath, $configuration, [Text.UTF8Encoding]::new($false))
    & $server "--defaults-file=$configPath" --initialize-insecure
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo inicializar el directorio MySQL local.' }
    $bootstrap = Start-Process -FilePath $server -ArgumentList @("--defaults-file=`"$configPath`"",'--skip-networking','--shared-memory','--shared-memory-base-name=ESPACIO_U_DEV_BOOTSTRAP') -WindowStyle Hidden -PassThru
    try {
        $ready = $false
        for ($attempt=0; $attempt -lt 40; $attempt++) {
            if ($bootstrap.HasExited) { throw 'MySQL de bootstrap se detuvo.' }
            if ((Invoke-BootstrapSql 'SELECT 1;') -eq 0) { $ready=$true; break }
            Start-Sleep -Milliseconds 250
        }
        if (-not $ready) { throw 'MySQL de bootstrap no estuvo disponible a tiempo.' }
        # Secretos solo en memoria/stdin y .env; nunca en argumentos, SQL guardado o salida.
        $sql = "CREATE DATABASE espacio_u CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER '$appUser'@'127.0.0.1' IDENTIFIED BY '$appPassword'; GRANT ALL PRIVILEGES ON espacio_u.* TO '$appUser'@'127.0.0.1'; RENAME USER 'root'@'localhost' TO 'root'@'127.0.0.1'; ALTER USER 'root'@'127.0.0.1' IDENTIFIED BY '$adminPassword'; SHUTDOWN;"
        if ((Invoke-BootstrapSql $sql) -ne 0) { throw 'No se completo la provision local; no se muestran sentencias ni credenciales.' }
        if (-not $bootstrap.WaitForExit(15000)) { throw 'MySQL no termino el bootstrap a tiempo.' }
    } finally {
        if (-not $bootstrap.HasExited) { $bootstrap.Kill(); $bootstrap.WaitForExit() }
        $sql=$null; $appUser=$null; $appPassword=$null; $adminPassword=$null; $updates=$null; $envText=$null
    }
    Write-Output 'MySQL aislado inicializado; credenciales guardadas solo en .env.'
    $Action='Start'
}
if ($Action -eq 'Start') {
    if (-not (Test-Path -LiteralPath $configPath)) { throw 'Inicializar primero con -Action Init.' }
    if (Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue) {
        & $php (Join-Path $PSScriptRoot 'mysql-dev.php') status
        if ($LASTEXITCODE -ne 0) { throw 'Puerto ocupado por una instancia no validada del proyecto.' }
        return
    }
    $started = Start-Process -FilePath $server -ArgumentList "--defaults-file=`"$configPath`"" -WindowStyle Hidden -PassThru
    for ($attempt=0; $attempt -lt 40; $attempt++) {
        if ($started.HasExited) { throw 'La instancia MySQL del proyecto se detuvo al arrancar.' }
        if (Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue) { break }
        Start-Sleep -Milliseconds 250
    }
    & $php (Join-Path $PSScriptRoot 'mysql-dev.php') status
    if ($LASTEXITCODE -ne 0) { throw 'Fallo al validar la instancia MySQL del proyecto.' }
} elseif ($Action -in @('Stop','Status')) {
    & $php (Join-Path $PSScriptRoot 'mysql-dev.php') $Action.ToLowerInvariant()
    if ($LASTEXITCODE -ne 0) { throw 'La operacion MySQL local no se completo.' }
}
