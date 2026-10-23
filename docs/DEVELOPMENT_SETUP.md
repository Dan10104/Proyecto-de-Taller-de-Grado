# Entorno de desarrollo — Fase 1

Fecha de verificación: 2026-10-23. Único workspace: `C:\Users\BlackMaster69\Desktop\ESPACIO-U`, raíz de Visual Studio Code. La aplicación es una sola instalación convencional Laravel con Vue/Inertia; no existen proyectos backend/frontend separados.

## Versiones comprobadas

| Componente | Versión instalada | Uso / criterio |
| --- | --- | --- |
| PHP | 8.5.12 NTS x64 | Compatible con Laravel 13; extensiones MySQL y requisitos Composer verificados |
| Laravel Framework | 13.38.0 | Skeleton oficial laravel/laravel v13.11.0; framework fijado por composer.lock |
| Composer | 2.10.3 | PHAR local, ejecutado con el PHP del proyecto |
| Node.js | 24.22.1 LTS | Rama LTS compatible con Vite 8 |
| npm | 11.19.1 | Incluido con ese Node; se conserva este conjunto compatible, sin actualizar el npm global |
| Vue | 3.5.44 | Única página técnica |
| inertiajs/inertia-laravel | 3.5.2 | Adaptador servidor |
| @inertiajs/vue3 / @inertiajs/vite | 3.9.1 / 3.9.1 | Adaptador Vue y resolución de páginas |
| Vite | 8.3.5 | Compilación de desarrollo y producción |
| laravel-vite-plugin | 3.2.0 | Entrada de assets Laravel |
| @vitejs/plugin-vue | 6.0.10 | Compilación de componentes Vue |
| Tailwind CSS / @tailwindcss/vite | 4.3.3 / 4.3.3 | Pipeline CSS mínimo |
| MySQL | 9.7.4 LTS | Binarios ZIP oficiales, instancia exclusiva del proyecto |
| PHPUnit / Pint / Collision | 12.5.40 / 1.32.1 / 8.9.5 | Pruebas, formato PHP y salida de pruebas |
| PowerShell / Git | 7.6.5 / 2.54.0.windows.1 | Entorno Windows utilizado |

Las versiones resueltas están en `composer.lock` y `package-lock.json`; conservar ambos. Usar `composer install` y `npm ci` para reproducirlas. Las actualizaciones futuras deben ser deliberadas y volver a validarse. No se instalaron starter kits, autenticación/API, Tinker, Faker, Mockery, Redis, librerías de integraciones ni componentes de negocio.

El informe contempla Tailwind y Bootstrap como herramientas de presentación. En Fase 1 se utiliza solamente Tailwind para evitar dos sistemas de estilos en una página mínima. No se ha elegido un dashboard ni se ha convertido el prototipo en interfaces funcionales.

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
- La base de aplicación contiene **cero tablas al cierre de Fase 1**. No existen migraciones ni usuarios de la aplicación; los usuarios técnicos de MySQL no son el módulo funcional de usuarios.

```powershell
.\scripts\mysql-dev.ps1 -Action Start
.\scripts\mysql-dev.ps1 -Action Status
.\scripts\mysql-dev.ps1 -Action Stop
```

Los comandos validan puerto y directorio de datos antes de operar. `Stop` ejecuta un apagado SQL de la instancia del proyecto. Si `Status` informa indisponibilidad tras `Stop`, iniciar de nuevo con `Start`; no intentar acceder a las otras instancias. No ejecutar `Init` en este equipo ya preparado.

Sesiones anónimas y caché técnicas usan archivos; cola `sync` y correo `log` son configuración base, sin jobs ni envío de mensajes. En Fase 1 no requirieron tablas ni implementaron autenticación. La posterior Fase 1.5 resolvió documentalmente D-07 para la web; ese contrato aún no se ha programado. Laravel usa español y `America/La_Paz`; las conexiones MySQL fijan `-04:00`. La conversión de instantes externos sigue diferida.

## Ejecutar y detener la aplicación

```powershell
. .\scripts\dev-environment.ps1
.\scripts\mysql-dev.ps1 -Action Start
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Abrir http://127.0.0.1:8000. `/` renderiza `Foundation.vue`; `/up` comprueba la salud básica de Laravel, **no la disponibilidad de MySQL**. La conexión se comprueba mediante `Status` y las pruebas.

Para editar con recarga de Vite, en una segunda terminal activar el entorno y ejecutar `npm run dev`. Vite escucha solo en `127.0.0.1:5173` y falla si el puerto está ocupado. No observará `.tools/`, `.local/` ni material temporal. Solo compila CSS de las vistas y páginas reales.

Detener Laravel y Vite con Ctrl+C en sus terminales y ejecutar `mysql-dev.ps1 -Action Stop`. Si un cierre forzado dejó `public/hot` después de detener Vite, borrar únicamente ese archivo y ejecutar `npm run build` antes de usar los assets compilados. Al cierre quedaron detenidos Laravel, Vite y la instancia MySQL del proyecto; los puertos 8000, 5173 y 3308 quedaron libres. Los servicios MySQL80/MySQL97 continuaron activos.

## Integración y verificaciones

`routes/web.php` devuelve una respuesta Inertia con un mensaje técnico; `HandleInertiaRequests` forma parte del grupo web. `resources/views/app.blade.php` contiene exclusivamente el punto de montaje y assets. `resources/js/app.js` inicia Inertia y resuelve `Pages/`; Vue recibe y muestra el mensaje del servidor. SSR y DevTools están deshabilitados. No hay API funcional, rutas de login, modelos de dominio ni migraciones automáticas.

```powershell
php artisan --version
php artisan about
php artisan route:list
php artisan test
composer check-platform-reqs
composer audit
npm run build
npm audit
php vendor/bin/pint --test app bootstrap/app.php bootstrap/providers.php config public routes scripts tests
```

Resultado al cierre: 3 pruebas / 17 aserciones, HTTP 200 e Inertia `Foundation`, `/up`, conexión MySQL real mediante `SELECT 1`, zona MySQL `-04:00` y Laravel en español / `America/La_Paz`. La prueba de BD es de solo lectura y usa `.env` local: no hay SQLite, mocks de conexión ni `RefreshDatabase`. Los tests futuros con escritura deberán aplicar el aislamiento definido en Fase 1.5 antes de ejecutarse.

Build de producción correcto. Vue muestra el mensaje recibido de Laravel tanto con build como con Vite en desarrollo; conexión de Vite comprobada y consola sin errores/advertencias. Composer y npm: cero avisos de vulnerabilidad en la consulta del 2026-10-23. No equivale a una garantía de seguridad permanente ni a validación de CU o rendimiento.

Incidencias resueltas: PHP global insuficiente, credenciales desconocidas de los MySQL existentes y motor Docker inactivo; se resolvieron con runtimes y BD locales autorizados. El puerto 3308 evita colisiones. El wrapper `.bat` de Composer evita que subprocesos detecten el Composer global. Un arranque inicial del servidor PHP desde el directorio incorrecto se corrigió usando `artisan serve`; no hubo cambio del framework. `composer validate` conserva únicamente una advertencia de metadatos por ausencia de licencia del proyecto: no se atribuyó una licencia de distribución sin decisión del titular; `--strict` trata esa advertencia como salida no exitosa. No se encontraron vulnerabilidades pendientes.

## Fuentes técnicas consultadas

- [Laravel 13: versiones y compatibilidad PHP](https://laravel.com/docs/13.x/releases).
- [PHP: versiones soportadas](https://www.php.net/supported-versions.php).
- [Node: ramas y ciclo de soporte](https://nodejs.org/en/about/previous-releases).
- [Vue: integración con herramientas](https://vuejs.org/guide/quick-start.html).
- [Inertia 3: instalación servidor](https://inertiajs.com/docs/v3/installation/server-side-setup) y [cliente](https://inertiajs.com/docs/v3/installation/client-side-setup).
- [Vite: requisitos y compilación](https://vite.dev/guide/).
- [Tailwind: integración Vite](https://tailwindcss.com/docs/installation/using-vite).
- [MySQL 9.7: notas de versión](https://dev.mysql.com/doc/relnotes/mysql/9.7/en/) e [inicialización](https://dev.mysql.com/doc/refman/9.7/en/data-directory-initialization.html).

No publicar `.env`, `.tools/`, `.local/`, dependencias, logs ni datos privados. La plantilla conserva vacíos todos los secretos y los lockfiles fijan dependencias públicas. Aplicar [SECURITY_POLICY.md](SECURITY_POLICY.md) antes de cada commit/push. D-04/D-07 están resueltas documentalmente para Fase 2; implementarla requiere nueva autorización.

## Aislamiento de pruebas para Fase 2

**Contrato conceptual de Fase 1.5; todavía no provisionado.** Las tres pruebas actuales de fundación solo leen MySQL de desarrollo. Antes de introducir migraciones destructivas, RefreshDatabase, truncados, seeders o cualquier escritura de tests, Fase 2 deberá:

1. Crear la base **MySQL espacio_u_testing** en la instancia aislada del proyecto, con charset/colación/motor compatibles. Puede compartir el servidor local 127.0.0.1:3308, pero no el esquema, los datos ni la cuenta de espacio_u. No usar SQLite ni las otras dos instancias del equipo.
2. Crear una cuenta distinta limitada a espacio_u_testing.* y sin permisos sobre espacio_u ni privilegios globales. La aplicación y las pruebas no usarán la cuenta administrativa. Guardar las credenciales y una APP_KEY exclusiva únicamente en .env.testing local; no copiar secretos de .env, no publicarlos ni pasarlos por argumentos.
3. Configurar APP_ENV=testing y DB_CONNECTION=mysql con esa base explícita. Adaptar phpunit.xml/configuración antes de usar tests con escritura: su estado de Fase 1 todavía no separa base. Un DB_URL, variable heredada o caché de configuración nunca debe permitir redirigir silenciosamente la conexión a desarrollo.
4. Incorporar una guarda previa a toda migración/limpieza que confirme entorno testing, driver mysql y destino efectivo: host/puerto locales autorizados y SELECT DATABASE() exactamente espacio_u_testing. Ante ausencia o discrepancia, abortar **antes de escribir**. No basta con comprobar que el nombre contiene la palabra test o confiar solo en un archivo de entorno.
5. Aislar caché, sesiones, archivos temporales y colas de prueba respecto del desarrollo; usar datos ficticios y los mecanismos de prueba del framework. Las pruebas que necesiten persistencia técnica entre solicitudes deberán usar un almacén exclusivo de testing. Ninguna prueba enviará correo ni llamará a integraciones reales.
6. Migrar únicamente el núcleo autorizado a esa BD y probar login/logout, RBAC, cambios de permisos, expiración y fallos/concurrencia. Las pruebas de rechazo del destino erróneo no ejecutarán comandos destructivos contra espacio_u. Paralelismo con bases adicionales queda deshabilitado hasta definir nombres permitidos y cuentas aisladas; no conceder permisos globales para facilitarlo.

Esta entrega no modifica .env, .env.example, phpunit.xml, config ni scripts; no crea .env.testing ni inicia servidores. El trabajo posterior configurará el entorno de pruebas antes del primer test con escritura. Procedimiento/credenciales permanecen separados: aquí se documentan reglas, nunca valores privados.

## Parámetros web previstos, todavía sin activar

En Fase 2, configurar el guard session y proveedor Usuario respetando correo/password_hash; Hash usará Argon2id, disponible en el PHP local comprobado. Conservar file para sesiones técnicas locales y configurar cookies según D-07. SESSION_LIFETIME seguirá siendo la única fuente de duración a través de config('session.lifetime'); no duplicar su default en lógica de aplicación. Los límites de intentos se parametrizarán y probarán antes de exponer login. No instalar paquetes de RBAC/API que reemplacen el esquema.

No se provisionarán en Fase 2 tablas de otros módulos: el núcleo permitido son las seis tablas de seguridad. Registros de actividad y CU30 requieren autorización de alcance posterior; los logs técnicos no equivalen a esa bitácora. [Contratos y readiness](DECISIONS.md#readiness-for-phase-2).
