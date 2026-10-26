# Entorno de desarrollo — Fases 1 y 2

**Estado vigente: Fase 5 implementada y validada (CU12–CU19), con incidencias integradas en CU06/CU07/CU09.** Los cierres anteriores conservan su contexto histórico. [Implementación, pruebas y límites actuales](EQUIPMENT_LOANS_IMPLEMENTATION.md). Fase 6 no iniciada.

Fecha de verificación: 2026-10-23. Único workspace: `C:\Users\BlackMaster69\Desktop\ESPACIO-U`, raíz de Visual Studio Code. La aplicación es una sola instalación convencional Laravel con Vue/Inertia; no existen proyectos backend/frontend separados.

## Versiones comprobadas

| Componente | Versión instalada | Uso / criterio |
| --- | --- | --- |
| PHP | 8.5.12 NTS x64 | Compatible con Laravel 13; extensiones MySQL y requisitos Composer verificados |
| Laravel Framework | 13.38.0 | Skeleton oficial laravel/laravel v13.11.0; framework fijado por composer.lock |
| Composer | 2.10.3 | PHAR local, ejecutado con el PHP del proyecto |
| Node.js | 24.22.1 LTS | Rama LTS compatible con Vite 8 |
| npm | 11.19.1 | Incluido con ese Node; se conserva este conjunto compatible, sin actualizar el npm global |
| Vue | 3.5.44 | Interfaces institucionales de seguridad |
| inertiajs/inertia-laravel | 3.5.2 | Adaptador servidor |
| @inertiajs/vue3 / @inertiajs/vite | 3.9.1 / 3.9.1 | Adaptador Vue y resolución de páginas |
| Vite | 8.3.5 | Compilación de desarrollo y producción |
| laravel-vite-plugin | 3.2.0 | Entrada de assets Laravel |
| @vitejs/plugin-vue | 6.0.10 | Compilación de componentes Vue |
| Tailwind CSS / @tailwindcss/vite | 4.3.3 / 4.3.3 | Pipeline CSS mínimo |
| MySQL | 9.7.4 LTS | Binarios ZIP oficiales, instancia exclusiva del proyecto |
| PHPUnit / Pint / Collision | 12.5.40 / 1.32.1 / 8.9.5 | Pruebas, formato PHP y salida de pruebas |
| PowerShell / Git | 7.6.5 / 2.54.0.windows.1 | Entorno Windows utilizado |

Las versiones resueltas están en `composer.lock` y `package-lock.json`; conservar ambos. Usar `composer install` y `npm ci` para reproducirlas. Las actualizaciones futuras deben ser deliberadas y volver a validarse. No se instalaron starter kits, paquetes de RBAC/API, Tinker, Redis ni integraciones. Fase 2 añade únicamente Faker 1.24.1 para factories y Mockery 1.6.15 para simular fallos de persistencia (Hamcrest 3.0.0 es transitiva). No se añadieron dependencias npm.

El informe contempla Tailwind y Bootstrap como herramientas de presentación. En Fase 1 se utiliza solamente Tailwind para evitar dos sistemas de estilos en una página mínima. Fase 2 conserva esta base de estilos e implementa el lenguaje visual institucional azul, navegación y formularios funcionales, sin plantilla de dashboard externa.

## Herramientas aisladas en este equipo

El PHP global de XAMPP 8.2.12 no cumple el mínimo de Laravel 13. Se prepararon herramientas oficiales dentro del workspace, sin cambiar instalaciones globales ni PATH persistente:

```text
.tools/
├── php/php.exe                 # PHP 8.5.12 y php.ini local
├── composer.phar               # Composer 2.10.3
├── composer.bat                # Lanza el PHAR con .tools/php/php.exe
├── node/node-v24.22.1-win-x64/
└── mysql/mysql-9.7.4-winx64/
.local/mysql/
├── my.ini
└── data/                       # Incluye sistemas internos de MySQL
```

Estas carpetas se ignoran y no se distribuyen en Git. Los ZIP, instaladores y skeleton temporal se eliminan al terminar la preparación; los runtimes y datos necesarios se conservan. Requieren espacio local y los componentes de ejecución Windows exigidos por sus proveedores. Los scripts usan PowerShell 7.4 o posterior, no Windows PowerShell 5.1.

Para reconstruir las herramientas si faltan, descargar únicamente de los proveedores oficiales:

1. PHP: obtener `php-8.5.12-nts-Win32-vs17-x64.zip` de [PHP para Windows](https://windows.php.net/download/) / [manifiesto oficial de releases](https://downloads.php.net/~windows/releases/releases.json). Verificar SHA-256 contra el manifiesto antes de extraer en `.tools/php/`. En la instalación comprobada el SHA-256 fue `2ab242af38d76bdf417cde5ec048b1bcd76a5a40f39a3d15d5f046f38571c477`.
2. Copiar `php.ini-development` a `php.ini` en ese directorio; activar `extension_dir="ext"`, curl, fileinfo, intl, mbstring, openssl, pdo_mysql y zip; fijar `date.timezone=America/La_Paz`. Verificar `php --ini`, `php -m` y los requisitos Composer tras activar el entorno. No copiar configuración de XAMPP.
3. Node: obtener el ZIP x64 de [Node 24.22.1](https://nodejs.org/dist/v24.22.1/), contrastarlo con su `SHASUMS256.txt` y extraer conservando `node-v24.22.1-win-x64` bajo `.tools/node/`.
4. Composer: seguir la [instalación oficial](https://getcomposer.org/download/), verificando el instalador con el SHA-384 publicado en `https://composer.github.io/installer.sig`. Ejecutarlo con `.tools/php/php.exe`, `--version=2.10.3 --install-dir=.tools --filename=composer.phar`; borrar el instalador después. El wrapper local `.tools/composer.bat` debe contener las dos líneas siguientes:

```bat
@echo off
"%~dp0php\php.exe" "%~dp0composer.phar" %*
```

5. MySQL: obtener `mysql-9.7.4-winx64.zip` del [sitio oficial de MySQL 9.7 LTS](https://dev.mysql.com/downloads/mysql/9.7.html) o del [CDN oficial](https://cdn.mysql.com/Downloads/MySQL-9.7/mysql-9.7.4-winx64.zip). Extraer bajo `.tools/mysql/`; `Get-AuthenticodeSignature` sobre `bin/mysqld.exe` debe reportar firma válida de Oracle America Inc., como ocurrió en esta instalación. No instalar un servicio Windows ni ejecutar configuradores sobre las instancias existentes.

Activar las herramientas únicamente en la terminal actual:

```powershell
. .\scripts\dev-environment.ps1
php --version
composer --version
node --version
npm --version
```

No hace falta volver a descargar herramientas en este equipo ya preparado. Un entorno global compatible puede ejecutar Laravel, pero los scripts de MySQL aislado están diseñados expresamente para las rutas locales anteriores.

## Preparar una instalación sin dependencias

Desde la raíz, con herramientas preparadas:

```powershell
. .\scripts\dev-environment.ps1
composer install --no-interaction --prefer-dist
npm ci
if (-not (Test-Path -LiteralPath .env)) { Copy-Item -LiteralPath .env.example -Destination .env }
```

Solo en un `.env` nuevo con `APP_KEY` vacío, ejecutar `php artisan key:generate`. No regenerar la clave de un entorno existente ni sobrescribir su `.env`. No usar `key:generate --show` ni imprimir el archivo.

Con los campos de credenciales todavía vacíos y sin directorio de datos previo:

```powershell
.\scripts\mysql-dev.ps1 -Action Init
php artisan migrate --force
php artisan db:seed --force
php scripts/mysql-testing.php
php artisan migrate --env=testing --force
npm run build
php artisan test
```

`Init` genera credenciales criptográficamente aleatorias y las guarda en `.env`; inicializa MySQL sin red, provisiona por memoria compartida y pasa después al arranque normal con contraseña. El SQL sensible se envía por stdin en memoria, nunca mediante argumentos, archivos SQL o salida. Se restringen los permisos NTFS de `.env` y `.local/mysql` al usuario local y SYSTEM. Un directorio de datos existente, puerto ocupado o credenciales ya presentes impiden reinicializar/sobrescribir. Si la inicialización se interrumpe, conservar los archivos y revisar el estado antes de cualquier recuperación; no borrar datos ni vaciar secretos para forzar un reintento.

## MySQL exclusivo de desarrollo

- Base: `espacio_u`, `utf8mb4`, `utf8mb4_unicode_ci`, InnoDB. El nombre es el alias de desarrollo autorizado; no cambia el contrato físico documental `ficct_reservas_prestamos`.
- Dirección: `127.0.0.1:3308`; MySQL X desactivado. Sin servicio Windows, sin inicio automático y sin Docker.
- Datos: `.local/mysql/data`; configuración sin secretos en `.local/mysql/my.ini`.
- Cuenta de aplicación: generada localmente, limitada a `espacio_u.*` y host loopback. Laravel utiliza exclusivamente esta cuenta. La administración aislada usa una contraseña distinta; ambos secretos permanecen solamente en `.env` en texto legible.
- No se guardan contraseñas en logs, argumentos, plantilla, código ni documentación. MySQL conserva sus verificadores internos de autenticación en su propio directorio privado.
- Los servicios MySQL80 (3306) y MySQL97 (3307) permanecen intactos. No se modificaron configuración, cuentas, datos, puertos ni estado de esos servicios.
- Al cierre histórico de Fase 1 la base estaba vacía. En Fase 2 contiene las seis tablas de seguridad y `migrations`, más los cuatro roles, once permisos y el administrador creado por bootstrap local. Los usuarios técnicos de MySQL no son cuentas del módulo funcional.

```powershell
.\scripts\mysql-dev.ps1 -Action Start
.\scripts\mysql-dev.ps1 -Action Status
.\scripts\mysql-dev.ps1 -Action Stop
```

Los comandos validan puerto y directorio de datos antes de operar. `Stop` ejecuta un apagado SQL de la instancia del proyecto. Si `Status` informa indisponibilidad tras `Stop`, iniciar de nuevo con `Start`; no intentar acceder a las otras instancias. No ejecutar `Init` en este equipo ya preparado.

Sesiones anónimas y caché técnicas usan archivos; cola `sync` y correo `log` son configuración base, sin jobs ni envío de mensajes. En Fase 1 no requirieron tablas ni implementaron autenticación. Fase 1.5 resolvió D-07 y Fase 2 lo implementa con control obligatorio en `sesiones_acceso`. Laravel usa español y `America/La_Paz`; las conexiones MySQL fijan `-04:00`. La conversión de instantes externos sigue diferida.

## Ejecutar y detener la aplicación

```powershell
. .\scripts\dev-environment.ps1
.\scripts\mysql-dev.ps1 -Action Start
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Abrir http://127.0.0.1:8000. `/` redirige al login; tras autenticarse se muestra `/inicio`; `/up` comprueba la salud básica de Laravel, **no la disponibilidad de MySQL**. La conexión de desarrollo se comprueba mediante `Status`; las pruebas conectan exclusivamente a MySQL testing.

Para editar con recarga de Vite, en una segunda terminal activar el entorno y ejecutar `npm run dev`. Vite escucha solo en `127.0.0.1:5173` y falla si el puerto está ocupado. No observará `.tools/`, `.local/` ni material temporal. Solo compila CSS de las vistas y páginas reales.

Detener Laravel y Vite con Ctrl+C en sus terminales y ejecutar `mysql-dev.ps1 -Action Stop`. Si un cierre forzado dejó `public/hot` después de detener Vite, borrar únicamente ese archivo y ejecutar `npm run build` antes de usar los assets compilados. Al terminar una sesión de trabajo, detener únicamente los procesos del proyecto; no actuar sobre los servicios MySQL80/MySQL97. El estado de la revisión de Fase 2 se registra en SECURITY_IMPLEMENTATION.md.

## Integración y verificaciones

`routes/web.php` expone exclusivamente CU01–CU04 y la página de inicio. Inertia comparte capacidades calculadas en servidor; nunca contraseñas, IDs de sesión ni hashes. SSR y DevTools siguen deshabilitados. No hay API móvil ni módulos de recursos.

```powershell
php artisan route:list
php artisan test
composer check-platform-reqs
composer audit
npm run build
npm audit
php vendor/bin/pint --test app bootstrap/app.php bootstrap/providers.php config database lang routes scripts tests
```

Verificación de Fase 2: 65 pruebas, 493 aserciones, sin omitidas, con MySQL real; tres carreras con dos procesos/conexiones independientes. Build correcto, Pint aprobado y cero avisos de vulnerabilidad en Composer y npm (incluidas dependencias de desarrollo), consulta del 2026-10-23. La evidencia no certifica CU30, rendimiento de carga ni despliegue. Registro funcional y visual en [SECURITY_IMPLEMENTATION.md](SECURITY_IMPLEMENTATION.md).

Se conserva la advertencia de metadatos de Composer por licencia aún no definida; no se asignó una licencia sin decisión del titular. Los incidentes históricos de PHP global insuficiente, Docker inactivo y credenciales desconocidas de las otras instancias se resolvieron en Fase 1 mediante runtimes y MySQL aislados. No deben reinicializarse ahora.

## Fuentes técnicas consultadas

- [Laravel 13: versiones y compatibilidad PHP](https://laravel.com/docs/13.x/releases).
- [PHP: versiones soportadas](https://www.php.net/supported-versions.php).
- [Node: ramas y ciclo de soporte](https://nodejs.org/en/about/previous-releases).
- [Vue: integración con herramientas](https://vuejs.org/guide/quick-start.html).
- [Inertia 3: instalación servidor](https://inertiajs.com/docs/v3/installation/server-side-setup) y [cliente](https://inertiajs.com/docs/v3/installation/client-side-setup).
- [Vite: requisitos y compilación](https://vite.dev/guide/).
- [Tailwind: integración Vite](https://tailwindcss.com/docs/installation/using-vite).
- [MySQL 9.7: notas de versión](https://dev.mysql.com/doc/relnotes/mysql/9.7/en/) e [inicialización](https://dev.mysql.com/doc/refman/9.7/en/data-directory-initialization.html).

No publicar `.env`, `.tools/`, `.local/`, dependencias, logs ni datos privados. La plantilla conserva vacíos todos los secretos y los lockfiles fijan dependencias públicas. Aplicar [SECURITY_POLICY.md](SECURITY_POLICY.md) antes de cada commit/push. D-04/D-07 están implementadas para la web; otros módulos requieren su autorización posterior.

## Aislamiento de pruebas para Fase 2

**Provisionado y verificado en este equipo.** `espacio_u_testing` comparte únicamente el servidor aislado `127.0.0.1:3308`; tiene esquema, usuario y APP_KEY propios. La cuenta solo tiene privilegios sobre `espacio_u_testing.*`; una consulta a desarrollo fue denegada por MySQL con código 1142. No usa SQLite, servicios externos ni la cuenta administrativa.

En una instalación nueva, después de preparar `.env`, MySQL de desarrollo y dependencias, ejecutar una sola vez:

```powershell
php scripts/mysql-testing.php
php artisan migrate --env=testing --force
php artisan db:seed --env=testing --force
php artisan test
```

El script verifica el puerto/directorio de datos del servidor y rechaza sobrescribir `.env.testing` o una base existente. Genera credenciales y APP_KEY aleatorias, guardadas solo en `.env.testing` ignorado. Si se interrumpe, conservar el archivo y el estado de MySQL para diagnóstico; no borrar ni reiniciar a ciegas. El script restringe el archivo antes de escribir secretos: usuario actual y SYSTEM en Windows; modo 0600 en sistemas POSIX.

PHPUnit fuerza APP_ENV=testing, driver MySQL y una ubicación de caché de configuración exclusiva. `TestingDatabaseGuard` comprueba primero configuración estricta (sin URL/socket alternativos) y después base efectiva, puerto y directorio de datos antes de cualquier `RefreshDatabase` o limpieza. `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback` y `db:wipe` también exigen ese destino incluso con `--force`; en desarrollo se utiliza únicamente migración incremental. Una configuración incoherente aborta, no cambia a otra BD automáticamente.

Caché y sesión de tests usan `array`; rutas de archivos, locks y configuración, cuando hacen falta, van bajo `.local/testing`. Cola sync y correo array; datos ficticios. La suite se ejecuta en serie. Las pruebas de concurrencia lanzan dos procesos dentro del mismo esquema autorizado, con limpieza protegida antes y después. No usar `artisan test --parallel`, que intentaría crear esquemas no autorizados.

## Administrador inicial y acceso local

El catálogo se carga con `php artisan db:seed`; es idempotente y no restituye permisos retirados de un rol ya existente. No crea cuentas ni contraseñas por defecto.

En una base recién migrada y sin usuarios, ejecutar `php artisan security:bootstrap-admin`: solicita correo y contraseña mediante entrada oculta y confirmación. Alternativamente, completar **solo** `.env` privado con `SEED_ADMIN_EMAIL` y `SEED_ADMIN_PASSWORD` y ejecutar `php artisan security:bootstrap-admin --from-env`. La plantilla deja ambas vacías. Se valida la entrada y se almacena Argon2id; cualquier usuario existente impide reutilizar el bootstrap. La primera asignación se atribuye al propio administrador, única convención de arranque sin actor previo. Nunca pasar secretos por argumentos ni imprimir `.env`.

**Este equipo ya está preparado:** el administrador inicial y sus credenciales aleatorias locales existen. No repetir el comando ni regenerar APP_KEY. La comprobación manual utilizó únicamente cuentas sintéticas: ambas quedaron INACTIVAS y conservan su historia. La primera se deshabilitó mediante el servicio durante la incidencia del navegador; la segunda se creó, editó y deshabilitó desde la interfaz en la revisión final completada. No se almacenaron contraseñas de esas cuentas en archivos públicos ni documentación.

## Parámetros de sesión web

Guard `web`/proveedor `Usuario`, correo y password_hash; Laravel Hash Argon2id con parámetros del framework. Sesión técnica file y registro obligatorio en sesiones_acceso; nunca usar esa tabla como driver database. `config('session.lifetime')` deriva de SESSION_LIFETIME y es la única duración. Límite: cinco fallos por correo/IP y treinta intentos por IP en sesenta segundos, parametrizado en config/security.php. Cookies HttpOnly y SameSite=Lax; Secure obligatorio fuera de local/testing. Producción exige HTTPS y configuración propia.

CU30/registros_actividad, API/móvil y persistencia distribuida permanecen pendientes. No instalar paquetes que sustituyan el esquema académico. [Implementación y límites](SECURITY_IMPLEMENTATION.md).

## Actualización incremental a Fase 3

Con la rama actualizada y el entorno activado en la raíz oficial:

```powershell
. .\scripts\dev-environment.ps1
.\scripts\mysql-dev.ps1 -Action Start
php artisan migrate
php artisan db:seed
npm run build
php artisan test
.\vendor\bin\pint --test
composer audit
npm audit
```

migrate agrega ocho tablas respetando FK; nunca usar fresh/reset/wipe en desarrollo. db:seed extiende el catálogo a 15 permisos sin sembrar inventario ni políticas. Las revocaciones existentes se conservan al repetirlo. Crear pisos/tipos/características y espacios desde la administración autorizada; cualquier demo debe identificarse como sintética. No asignar pisos reales sin respaldo institucional.

Resultado integrado: 127 pruebas / 1395 aserciones (62 nuevas), sin fallos ni omitidas, sobre espacio_u_testing protegido; build y auditorías aprobados. Tres cuentas sintéticas adicionales de revisión se deshabilitaron y se revocaron sus sesiones; sus credenciales temporales se retiraron del .env privado. La demostración de espacio/catálogos y bloqueo desactivado conserva historia local. Los dos servicios MySQL existentes no se alteraron. La vista local y los procesos del proyecto se dejan activos para revisión; no iniciar otra copia del servidor si el puerto 8000 ya está atendido. [Evidencia y límites](SPACES_IMPLEMENTATION.md).

## Actualización incremental a Fase 4

Desde el workspace oficial con `dev-environment.ps1` activado y la instancia aislada disponible, ejecutar `php artisan migrate`, `php artisan db:seed` y `npm run build`. Esta fase añade una sola tabla: reservas; el seeder incorpora seis permisos y conserva revocaciones. No crea inventario ni reservas permanentes. Nunca usar fresh/reset/wipe en desarrollo ni modificar MySQL80/MySQL97.

Validar con `php artisan test`, `php vendor/bin/pint --test`, `composer audit` y `npm audit`. La suite usa exclusivamente espacio_u_testing, en serie; las pruebas de concurrencia abren procesos independientes dentro de ese esquema con guardas. No usar `--parallel`. [Resultado final y procedimiento de concurrencia](RESERVATIONS_IMPLEMENTATION.md).

Las tres cuentas temporales de revisión quedaron INACTIVAS y sus sesiones revocadas; las credenciales temporales se retiraron de .env. El historial sintético se conserva (cuatro reservas canceladas, una rechazada). No hay credenciales en la documentación, plantilla ni repositorio. La aplicación y los procesos locales permanecen activos, sin duplicar el servidor que atiende 127.0.0.1:8000.

## Aplicar y verificar Fase 5

Desde la raíz oficial, con el entorno activado y MySQL aislado iniciado:

```powershell
php artisan migrate --force
php artisan db:seed --class=SecuritySeeder --force
npm run build
php artisan test
php vendor/bin/pint --test
composer audit
npm audit
```

Desarrollo recibió solo seis migraciones incrementales, sin limpiar espacio_u. La suite usa exclusivamente espacio_u_testing en 3308 con credenciales privadas en .env.testing y TestingDatabaseGuard. No usar --parallel ni migrate:fresh sobre desarrollo; las pruebas de concurrencia ya coordinan sus propios procesos. No se cambian MySQL80/MySQL97. Resultado final: 262 pruebas / 3318 aserciones, 178,23 s, cero fallos/errores/omitidas; Pint/build/auditorías correctos. [Procedimiento, QA sintética y límites](EQUIPMENT_LOANS_IMPLEMENTATION.md). Las cuentas temporales de QA están inactivas y sus credenciales retiradas; se conservan los hitos sintéticos y servicios locales.


## Actualización de Fase 7

Fase 7 agrega dos migraciones incrementales y tres permisos al seed idempotente. Inicializar un maestro distinto por entorno con scripts/iot/initialize-secret.php, que escribe directamente sin mostrar secretos. Usar .env/.env.testing ignorados; no config:cache local. Servidor local en 8000 y MySQL aislado 3308 conservados; no cambios en los servicios 3306/3307. [Implementación y verificación](IOT_IMPLEMENTATION.md).

## Actualización de Fase 9

Dos migraciones incrementales y tres permisos nuevos de Administrador, sin recrear desarrollo. El scheduler existente ejecuta CU23 y trazabilidad:reconciliar. Instalar blockchain/ con npm ci --ignore-scripts para compilar/probar; runtime productivo usa --omit=dev, Node compatible y ABI generado. Suites Laravel/integración secuenciales en espacio_u_testing, nunca sobre desarrollo. [Configuración, scripts, wallet, auditorías, excepción dev temporal y límite Sepolia](BLOCKCHAIN_IMPLEMENTATION.md#configuración-y-operación-reproducible). No se implementa Docker ni Fase 10.
