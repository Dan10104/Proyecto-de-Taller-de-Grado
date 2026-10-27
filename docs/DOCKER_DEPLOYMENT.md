# Docker productivo y validación local

Fase 14 prepara contenedores y cloud; no agrega negocio. Workspace único: `C:\Users\BlackMaster69\Desktop\ESPACIO-U`. No cambia los servicios MySQL del equipo. Entorno de desarrollo original y PHPUnit `espacio_u_testing` conservan su configuración.

## Imagen

| Componente | Versión / función |
| --- | --- |
| PHP | 8.5.12, imagen oficial apache-bookworm, digest fijado en Dockerfile |
| Apache | 2.4.68 observado; servidor HTTP productivo, prefork limitado a 8 workers |
| Node | 24.22.1, binario oficial copiado sin npm; JCS y ethers requieren runtime |
| Composer | 2.10.3 solo builder; install --no-dev desde composer.lock |
| Frontend | npm ci + npm run build desde package-lock.json; assets locales |
| Blockchain | npm ci --omit=dev --ignore-scripts desde su lock; canonicalize/ethers/jsonc-parser |
| MySQL local | 8.4.11 oficial con digest; familia LTS elegida para Cloud SQL MYSQL_8_4 |

Etapas `extensions`, `node-base`, `frontend`, `blockchain-runtime`, `composer-binary`, `dependencies`, `runtime`. PDO MySQL, mbstring, zip y pcntl se compilan fuera del runtime; dom/xmlreader/fileinfo/openssl y extensiones base se verifican dentro. Se eliminan gcc/g++/make/autoconf y PHPIZE_DEPS del filesystem final. No Composer, npm, Hardhat, Zod dev, solc, factories, tests ni artefactos de compilación Blockchain en el runtime. intl, bcmath y gd no se añaden: los requisitos productivos y los flujos PDF/XLSX/QR actuales pasan sin ellas; revisarlas antes de introducir funciones que sí las necesiten. pcntl permite los límites/señales del worker CLI. Las capas base oficiales se conservan en el historial; el escaneo examina también esas capas.

Inertia 3 exige comprobar existencia de Pages: sus archivos Vue están fuera de public y se conservan en `resources/js/Pages`; el navegador recibe los assets Vite compilados. No SSR, Vite server ni artisan serve. PHP procesa reglas; Node no es servidor HTTP.

`.dockerignore` usa lista de inclusión y exclusiones finales: `.env*`, `.git`, `.local`, `.tools`, vendor/node_modules del host, caches Laravel, hot/build del host y material privado. Nunca build-arg secreto. Único argumento propio: GIT_SHA. Los tags de validación locales no son tags de publicación; el despliegue exige SHA Git y digest inmutable.

## Arranque y privilegios

Apache escucha `0.0.0.0:$PORT`, por defecto 8080, document root `/var/www/html/public`. Usuario `www-data` UID 33, sin privilegios root; Compose elimina capabilities y activa no-new-privileges. Solo storage, bootstrap/cache y directorios de ejecución Apache son escribibles. Umask 077; sin chmod 777. Código y public son de solo lectura para el proceso.

`entrypoint.sh` valida puerto/configuración, crea directorios y genera config/route/view caches **en runtime**, después de inyectar secretos. Nunca migra ni ejecuta seeders. `deployment:check` no abre DB ni llama a proveedores y solo devuelve nombres seguros de ajustes inválidos. APP_KEY/DB_password no se imprimen.

`web.sh` supervisa Apache y una limpieza local de temporales cada 30 s. SIGTERM se convierte en cierre graceful Apache; termina y recoge también el proceso de limpieza y su hijo. GracefulShutdownTimeout=8 y margen de parada 10 s. No queue:work perpetuo ni schedule:work en web.

`GET /up` prueba vida del proceso, independientemente de DB, OpenAI, IoT o Blockchain. Startup probe tolera hasta 120 s; liveness cada 30 s con tres fallos. Un fallo DB devuelve error controlado en operaciones, no reinicios masivos por liveness. El smoke autenticado sí verifica DB/sesión.

## Reproducir entorno aislado

Desde PowerShell en el workspace, con Docker Desktop Linux iniciado:

```powershell
. ./scripts/dev-environment.ps1
php scripts/docker/prepare-local.php
docker compose --env-file .env.docker -f compose.production-like.yml config --quiet
docker compose --env-file .env.docker -f compose.production-like.yml build
docker compose --env-file .env.docker -f compose.production-like.yml up -d db
docker compose --env-file .env.docker -f compose.production-like.yml run --rm migrate
docker compose --env-file .env.docker -f compose.production-like.yml run --rm seed-security
docker compose --env-file .env.docker -f compose.production-like.yml run --rm bootstrap-admin
docker compose --env-file .env.docker -f compose.production-like.yml up -d web-a web-b
```

El generador crea `.env.docker` una sola vez y se niega a sobrescribirlo. Credenciales aleatorias fuertes solo en ese archivo privado ignorado; no reutiliza `.env`. Restringir ACL al usuario local en Windows; en POSIX crea modo 0600. `.env.docker.example` tiene secretos vacíos. No ejecutar `compose config` sin `--quiet`, no pegar inspect Config.Env de contenedores: contienen valores inyectados.

RF21 requiere `NOTIFICATIONS_EVENTS_FROM`, fijado por el generador al inicio local; no cambiarlo en cada despliegue. Cloud exige una fecha explícita y estable. El bootstrap falla si ya existen usuarios; el seeder solo incorpora catálogo RBAC y no restaura revocaciones. Las credenciales de administrador solo entran al contenedor de bootstrap; nunca a web/queue/scheduler.

Web A: `http://127.0.0.1:8088`; Web B: `http://127.0.0.1:8089`. La excepción HTTP se limita a `DEPLOYMENT_TARGET=local-container`, URL loopback y DB `espacio_u_container` host db; cookies HTTP locales no alteran la política cloud. IoT exige HTTPS incluso aquí: el smoke usa el kernel HTTP real con señal TLS; HTTP directo debe devolver 403. No se relajó su contrato.

MySQL no publica puerto al host. Red y volumen `espacio-u-production-like_*` exclusivos. Inicialización crea cuenta DML web y cuenta DDL de migración restringidas a esa base; root solo administra este contenedor local. No importación de desarrollo. Conservar el volumen para repetir arranques; `down` sin `-v` detiene entorno sin borrar datos. No usar `down -v` salvo decisión explícita de descartar estos datos sintéticos.

## Sesiones, colas y scheduler

`SESSION_DRIVER=database`, `SESSION_BLOCK_STORE=database`, `CACHE_STORE=database`, `IOT_RATE_STORE=database`. Tablas técnicas `sessions`, `cache`, `cache_locks`, `jobs`; `sesiones_acceso` conserva la revocación e historia del dominio. Payload compartido no sustituye validar cuenta/roles/expiración en cada request. Sin afinidad de sesión ni filesystem compartido obligatorio.

Los tres productores de efectos (notificaciones, registro crítico, reintento de trazabilidad) toman `QUEUE_EFFECTS_CONNECTION`; local tradicional mantiene deferred, contenedor usa database. Dispatch después de commit; error no revierte negocio y reconciliadores recuperan desde registros/evidencias. Jobs contienen IDs, no prompts o secretos. `QUEUE_FAILED_DRIVER=null`: no persistir excepciones/payloads completos en failed_jobs; los dos handlers conocidos registran avisos seguros y tienen recuperación durable. Un futuro tipo de job requiere revisar esta decisión.

```powershell
docker compose --env-file .env.docker -f compose.production-like.yml run --rm queue
docker compose --env-file .env.docker -f compose.production-like.yml run --rm scheduler
```

Cola: stop-when-empty, max-time 50 s, max-jobs 100, timeout por job 60 s, retry_after 120 s, tries 5, memoria worker 128 MiB. max-time se verifica entre jobs, por eso Cloud Run Job permite 180 s. Frecuencia piloto un minuto; latencia habitual hasta un minuto más arranque/carga, sin promesa de tiempo real. Scheduler ejecuta schedule:run una sola vez y conserva withoutOverlapping. Reservas/notificaciones usan locks transaccionales e idempotencia; wallet/evidencia usa GET_LOCK MySQL en conexión separada.

Cuota IoT database usa fila de caché con SELECT FOR UPDATE y actualización transaccional, sin lease que pueda expirar mientras un proceso queda suspendido. Conserva ventana/límite/respuesta 429; fallo del store cierra con 503. Un corte de conexión revierte la transacción. Pruebas entre procesos confirman que no se pierden incrementos.

## Smoke reproducible

Herramientas QA no se copian a imagen. Python estándar salvo validación documental local con pypdf/openpyxl. Tras primer bootstrap, crear fixture sintética una vez:

```powershell
$OutputEncoding = [System.Text.UTF8Encoding]::new($false)
Get-Content scripts/docker/qa.php -Raw | docker compose --env-file .env.docker -f compose.production-like.yml exec -T web-a php /dev/stdin setup
docker compose --env-file .env.docker -f compose.production-like.yml run --rm queue
python scripts/docker/smoke.py
python scripts/docker/concurrency.py
python scripts/docker/resilience.py
python scripts/docker/recovery-smoke.py
python scripts/docker/image-scan.py
```

Setup rechaza fixture existente; no reinicia DB. Usar la instalación Python local con pypdf/openpyxl disponible o un entorno QA separado; esas librerías nunca son dependencias del contenedor. Los scripts exigen target/base aislados; no son herramientas de producción. Resultados privados en `.local/qa/phase14`, sin cuerpos QR, contraseñas ni cookies. [Resultados concretos y límites](PHASE_14_DEPLOYMENT_PREPARATION.md).
