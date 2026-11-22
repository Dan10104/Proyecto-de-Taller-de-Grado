# Base de datos de presentación

Fecha: 2026-11-20, America/La_Paz. Actualizado: 2026-11-21.

## Propósito

La base `espacio_u` conserva los registros de QA y pruebas de las fases de desarrollo (recursos con sufijo «· Demo», pisos y políticas «sintéticos», cuentas `@example.test`). Se mantiene intacta como respaldo histórico.

Para presentar el sistema se usa una base separada, `espacio_u_presentacion`, creada con las mismas migraciones y el mismo catálogo de roles y permisos. Desde el 2026-11-21 contiene un conjunto controlado de **datos de demostración**, suficiente para recorrer todos los módulos con los cuatro roles sin mostrar el sistema vacío.

**Estos datos no son inventario ni registros oficiales de la FICCT.** Sirven únicamente para demostrar el funcionamiento del sistema.

## Clasificación de los datos

| Clase | Qué incluye |
| --- | --- |
| Real o verificado | El catálogo del sistema: 4 roles institucionales, 52 permisos y su asignación a roles (`SecuritySeeder`). |
| Provisional | La distribución del edificio descrita por el responsable del proyecto y pendiente de verificación en sitio: cuatro pisos, aulas en los pisos 1 a 3, laboratorios y auditorio en el piso 4, la numeración de los ambientes y el equipamiento habitual de un aula (proyector y aire acondicionado). Están cargados los 31 ambientes de esa distribución (aulas 11 a 17 en el piso 1, 18 a 25 en el piso 2 y 26 a 32 en el piso 3; laboratorios 41 a 48 y el auditorio en el piso 4), con códigos `AULA-<número>`, `LAB-<número>` y `AUD-01`, y cada espacio lo declara en su referencia de ubicación: «ubicación provisional por verificar en sitio». Los baños, la escalera, el ascensor y la circulación no son recursos académicos y no se cargan. Sigue siendo una distribución provisional: no es el plano definitivo ni inventario institucional. |
| Solo de presentación | Todo lo demás: capacidades de los ambientes, personas y cuentas, equipamiento, reservas, préstamos, incidencias, sensores y lecturas, códigos QR, políticas, evaluaciones, evidencias, notificaciones y bitácora. |

La distribución provisional no se convierte en inventario definitivo por estar cargada: sigue siendo la misma maqueta visual que declara `resources/js/building.js`. El equipamiento es un conjunto pequeño y genérico (proyectores, computadoras portátiles, kits de robótica y periféricos de laboratorio), sin marcas, modelos ni números de serie.

Las personas de las cuentas de presentación son ficticias. Ningún registro usa nombres como «Demo», «Test», «QA», «Sintético» o «Ejemplo».

## Módulos cubiertos

| Módulo | Situaciones que permite mostrar |
| --- | --- |
| Usuarios y roles | Cuentas de Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Espacios y disponibilidad | Espacios disponibles y en mantenimiento, características, bloqueos activos e inactivos. |
| Reservas | Pendientes, aprobadas (pasadas y próximas), rechazadas, canceladas por el titular y por la administración, y liberadas. |
| Equipamiento y préstamos | Equipos disponibles, prestados y en mantenimiento; préstamos pendientes, aprobados, rechazados, en curso, vencido y devueltos (en buen estado, con incidencia y con revisión posterior). |
| Incidencias y mantenimiento | Incidencias y mantenimientos abiertos y cerrados, de espacios y de equipos. |
| IoT | Sensores habilitados y deshabilitados, espacios monitoreados, historial de lecturas y lecturas del momento. |
| Códigos QR | Códigos de espacios, equipos y préstamos; rotación; validaciones válidas e inválidas de uso de espacio, entrega y devolución. |
| Liberación | Políticas activas e inactivas asignadas a espacios; evaluaciones con los cuatro resultados posibles. |
| Trazabilidad | Evidencia local con su huella digital por cada operación crítica; evidencias registradas en la red Ethereum local, verificadas y pendientes; reconciliación tras una caída de la red. |
| Notificaciones y bitácora | Avisos leídos y sin leer; bitácora con las operaciones de todos los módulos. |
| Dashboard y reportes | Indicadores y gráficos con contenido en los periodos de hoy, 7 y 30 días; vista previa y exportación de reportes. |

## Cómo se cargaron

Los registros se crearon con los servicios de la propia aplicación (los mismos que usan las pantallas), con el actor y los permisos que corresponden a cada operación y con el reloj situado en la fecha de cada hecho. Por eso la bitácora, las notificaciones, las evaluaciones de liberación y las evidencias quedaron generadas por el sistema y fechadas de forma coherente. No se copió ningún registro de `espacio_u` ni se modificó código, migraciones, permisos o reglas de negocio.

Las fechas son relativas al día de la carga: el pasado corresponde a días hábiles anteriores y el futuro a días hábiles posteriores.

El procedimiento de carga, respaldo y restauración es local y no se versiona. Las credenciales de las cuentas de presentación tampoco: se guardan solo en un archivo local ignorado por Git.

### Cuenta técnica inicial

La cuenta creada por `security:bootstrap-admin` se conserva como cuenta de recuperación. Es una cuenta técnica de acceso, no un dato institucional. Para que su registro sea anterior a la actividad cargada, se volvió a dar de alta con ese mismo comando al comienzo de la historia.

## Límites que la demostración respeta

- **Reservas «Finalizada»:** ninguna operación del sistema asigna ese estado. Una reserva aprobada cuyo horario ya terminó permanece como «Aprobada»; no se alteró su estado para aparentar otra cosa.
- **Registro externo de evidencias:** la base de presentación trabaja con el entorno Ethereum local compatible con Sepolia descrito en [BLOCKCHAIN_IMPLEMENTATION.md](BLOCKCHAIN_IMPLEMENTATION.md#entorno-ethereum-local-compatible-con-sepolia--validación-y-demostración-2026-11-21). Las evidencias pasan a «Registrada» únicamente cuando esa red local confirma su transacción; ninguna se marca a mano. Son registros de la red local, no de la testnet pública de Sepolia, y la interfaz lo indica en el detalle técnico. Algunas evidencias quedan «Pendiente» a propósito, para demostrar la reconciliación.
- **Lecturas IoT recientes:** el sistema solo considera reciente una lectura durante la ventana de frescura configurada (5 minutos por defecto). Para mostrarlas hay que enviar lecturas nuevas poco antes.

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

`php artisan db:show` indica la base activa. Las pruebas automatizadas no dependen de este cambio: usan la cuenta de `.env.testing` y su propia base desechable, `espacio_u_testing_efimera`. No tocan `espacio_u`, `espacio_u_presentacion` ni `espacio_u_testing`.

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

## Sustitución por datos reales

Cuando existan datos reales y verificados de la FICCT, los pisos, tipos de espacio, espacios académicos, tipos de equipamiento, equipamiento, sensores IoT, políticas de liberación y usuarios se registran desde la propia aplicación en una base nueva y vacía, creada con el procedimiento anterior. Los datos de demostración no deben reutilizarse como inventario institucional.
