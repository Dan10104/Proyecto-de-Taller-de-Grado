# Base de datos de presentación

Fecha: 2026-11-20, America/La_Paz.

## Propósito

La base `espacio_u` conserva los registros de QA y pruebas de las fases de desarrollo (recursos con sufijo «· Demo», pisos y políticas «sintéticos», cuentas `@example.test`). Se mantiene intacta como respaldo histórico.

Para presentar el sistema se usa una base separada y vacía, creada con las mismas migraciones y el mismo catálogo de roles y permisos. No contiene aulas, laboratorios, equipos, sensores ni usuarios institucionales: mientras no se carguen datos reales y verificados de la FICCT, cada módulo muestra su estado vacío.

## Qué contiene la base de presentación

| Contenido | Origen | Filas |
| --- | --- | --- |
| Estructura (31 tablas académicas y tablas de infraestructura del framework) | `php artisan migrate` | — |
| Roles institucionales | `SecuritySeeder` | 4 |
| Permisos y su asignación a roles | `SecuritySeeder` | 52 y 105 |
| Administrador inicial y su asignación de rol | `security:bootstrap-admin --from-env` | 1 y 1 |

No se copió ningún registro de `espacio_u`.

### Registro técnico mínimo

El único registro que no es catálogo es el administrador inicial. Es una cuenta técnica de acceso, no un dato institucional:

- el comando de alta le asigna el nombre «Administración Local»;
- su correo y su contraseña provienen de `SEED_ADMIN_EMAIL` y `SEED_ADMIN_PASSWORD` de la configuración local privada;
- el nombre y el correo pueden editarse desde el módulo Usuarios cuando se defina la cuenta definitiva.

## Aislamiento

`scripts/mysql-presentation.php <nombre_de_base>` crea el esquema en la instancia MySQL local del proyecto (`127.0.0.1:3308`) con un usuario propio que solo tiene privilegios sobre ese esquema, y comprueba que no puede leer las demás bases. Las credenciales se escriben únicamente en `.env.<nombre_de_base>`, ignorado por Git. El script no sobrescribe archivos ni bases existentes y no imprime credenciales.

El nombre de la base no está fijado en el código: la aplicación usa `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` de `.env`.

## Cambiar de base

Los archivos `.env*` son locales y privados; nunca se versionan.

| Archivo | Contenido |
| --- | --- |
| `.env` | Configuración activa |
| `.env.qa` | Copia de la configuración que apunta a `espacio_u` |
| `.env.espacio_u_presentacion` | Configuración que apunta a la base de presentación |

Con el servidor de Laravel detenido:

```powershell
# Presentación
Copy-Item .env.espacio_u_presentacion .env -Force
php artisan config:clear

# QA histórico
Copy-Item .env.qa .env -Force
php artisan config:clear
```

`php artisan db:show` indica la base activa. Las pruebas automatizadas no dependen de este cambio: usan `.env.testing` y `espacio_u_testing`.

## Crear otra base vacía

```powershell
. .\scripts\dev-environment.ps1
.\scripts\mysql-dev.ps1 -Action Start
php scripts/mysql-presentation.php <nombre_de_base>
Copy-Item .env.<nombre_de_base> .env -Force
php artisan config:clear
php artisan migrate --force
php artisan db:seed --class=SecuritySeeder --force
php artisan security:bootstrap-admin --from-env
```

No ejecutar `migrate:fresh`, `migrate:refresh`, `migrate:reset` ni `db:wipe` en ninguna base con datos.

## Carga posterior de datos reales

Los pisos, tipos de espacio, espacios académicos, tipos de equipamiento, equipamiento, sensores IoT, políticas de liberación y usuarios se registran desde la propia aplicación con los datos reales de la FICCT. Hasta entonces el sistema no muestra información institucional.
