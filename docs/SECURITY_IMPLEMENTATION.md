# Implementación de seguridad — Fase 2

**Estado vigente: Fase 5 implementada y validada (CU12–CU19), con incidencias integradas en CU06/CU07/CU09.** Los cierres anteriores conservan su contexto histórico. [Implementación, pruebas y límites actuales](EQUIPMENT_LOANS_IMPLEMENTATION.md). Fase 6 no iniciada.

Catálogo derivado antes de seeders/Policies. Cada código representa exclusivamente una acción de CU03/CU04. El iniciador elegible es Administrador con asignación vigente y permiso aportado por ese mismo rol.

| Código | Nombre | Trazabilidad |
| --- | --- | --- |
| usuarios.ver | Consultar usuarios | RF2 / CU03: listar, buscar, filtrar y consultar |
| usuarios.crear | Registrar usuarios | RF2 / CU03: registrar cuenta |
| usuarios.editar | Editar usuarios | RF2 / CU03: actualizar datos y contraseña administrativa |
| usuarios.deshabilitar | Deshabilitar usuarios | RF2 / CU03: baja lógica |
| roles.ver | Consultar roles | RF3 / CU04: consultar roles y asignaciones |
| roles.crear | Registrar rol institucional | RF3 / CU04: alta dentro del catálogo institucional |
| roles.editar | Editar roles | RF3 / CU04: actualizar datos compatibles |
| permisos.ver | Consultar permisos | RF3 / CU04: consultar catálogo de permisos |
| roles.gestionar_permisos | Configurar permisos de rol | RF3 / CU04: asociar y retirar permisos |
| roles.asignar | Asignar roles | RF2–3 / CU03–04: asignar rol a usuario |
| roles.retirar | Retirar roles | RF3 / CU04: retirar conservando historia |

Matriz inicial: Administrador recibe los 11 permisos; Encargado de Laboratorio, Docente y Estudiante reciben ninguno de administración. Los cuatro pueden iniciar/cerrar su sesión y consultar Inicio sin permisos administrativos. No se crean permisos de módulos futuros. Los permisos efectivos se consultan en MySQL, no se conceden por constantes.

D-04 limita roles a cuatro identidades institucionales. CU04 permite crear una de ellas si falta, rechaza duplicados y nombres ajenos; una identidad con historia no se renombra a otra. Se edita su descripción. El seeding inicial completa las cuatro.

Integridad administrativa: operaciones de gestión se serializan mediante bloqueo de la fila Administrador. Siempre debe permanecer un usuario ACTIVO con asignación Administrador y el rol conserva los seis permisos de recuperación enumerados en PermissionCatalog::RECOVERY. Los otros permisos pueden retirarse y su ausencia deniega la acción. No hay bypass. El catálogo inicial solo asigna permisos al crear el rol; repetir seeders no repone permisos retirados.

Las asignaciones bloquean y reconsultan el usuario dentro de la misma transacción, rechazan duplicados activos y conservan historia. El retiro cierra todas las filas activas del par. CU30 y registros_actividad siguen pendientes.

## Esquema y relaciones implementadas

Seis migraciones incrementales ejecutan el CREATE TABLE del SQL oficial, normalizando únicamente `permisos.codigo` conforme D-01. Son 29 columnas, siete FK y dos CHECK; no hay timestamps Laravel agregados ni UNIQUE sobre la historia de asignaciones. InnoDB, charset, colación, índices, defaults y acciones de FK preservados. `migrations` es la única tabla técnica y no pertenece al modelo académico de 31 tablas.

Modelos: Usuario, Rol, Permiso, RolPermiso (Pivot), AsignacionRol y SesionAcceso, todos sin timestamps convencionales. Usuario–Rol y Rol–Permiso son M:N; las asignaciones conservan asignador/retirador y fechas; Usuario tiene muchas sesiones. Las relaciones de roles vigentes consultan fecha_retiro NULL. Password y token_hash quedan ocultos en serialización.

## Autenticación, sesiones y errores

Laravel SessionGuard verifica correo/password_hash y ACTIVO con Hash Argon2id. FormRequest valida/normaliza; CSRF permanece activo. El mensaje de credenciales incorrectas es genérico, con RateLimiter por correo/IP y límite agregado por IP. Tras autenticarse se regenera el ID final y CSRF; la transacción crea sesiones_acceso con SHA-256 del ID final, titular, creación, vencimiento y revocación NULL. Nunca se almacena el ID crudo en esa tabla.

Antes de cada ruta protegida, EnsureAccessSession verifica usuario activo, titular, hash, vigencia y ausencia de revocación. Renueva únicamente una sesión vigente usando session.lifetime; no recrea registros ausentes ni revive expirados/revocados. El guard nativo conserva su almacenamiento file; los locks de sesión serializan solicitudes del mismo navegador y los bloqueos MySQL evitan carreras con revocación/inactividad. Sesiones diferentes tienen registros independientes.

Logout POST revoca la sesión actual, cierra el guard, invalida el almacenamiento técnico y regenera CSRF. La UI oculta inmediatamente su contenido y persiste solo un indicador booleano de cierre pendiente, sin tokens. Solo una confirmación exitosa elimina ese indicador y limpia el historial cifrado de Inertia. Si falla la revocación SQL, invalida autenticación y conserva exclusivamente en la sesión técnica anónima el hash pendiente para completar esa misma revocación al reintentar, sin enviarlo al navegador. Tras un fallo permite reintentar; recupera CSRF si la sesión técnica expiró. No afirma revocación remota durante una caída de red ni intenta borrar directamente cookies HttpOnly.

CheckedFileSessionHandler convierte escritura/eliminación fallida en excepción controlada. El renderer revoca el acceso cuando es posible, expira la cookie y devuelve 503. Fallar al registrar una sesión no deja acceso válido. Los errores SQL se presentan genéricos y registran únicamente SQLSTATE, nunca consultas/bindings/hashes. Respuestas privadas no-store, no-sniff, frame DENY y referrer same-origin. No hay recuperación pública de contraseña, recordar sesión ni notificaciones de correo.

## Autorización y administración

Rutas con `auth`, `access.session` y Gate de capacidad. AuthorizationService obtiene de MySQL los permisos del rol Administrador vigente del usuario activo; la condición de iniciador CU03/CU04 y el permiso deben coincidir en el mismo rol. No hay `Gate::before` que conceda acceso total. Un permiso asociado erróneamente a otro rol no habilita administración. Vue solo usa las capacidades para presentar acciones; el servidor vuelve a evaluarlas y las mutaciones reautorizan después del bloqueo transaccional.

Gates y servicios cubren las capacidades de este núcleo. No se crean Policies vacías: las políticas por recursos futuros se incorporarán al implementar sus entidades/ámbito. Las solicitudes validan campos y las actualizaciones usan lista permitida, sin aceptar estado, hash ni fechas desde el cliente.

Usuario: lista/búsqueda/filtros/paginación, detalle, alta, edición y baja lógica. Contraseña de alta mínimo 12 caracteres y confirmación; edición sin contraseña conserva el hash. Cambio administrativo explícito revoca todas las sesiones. Deshabilitar marca INACTIVO, revoca sesiones y conserva asignaciones/historia; no hay borrado físico ni endpoint de reactivación inventado.

Rol: consulta, descripción editable, alta de identidad oficial ausente, permisos del catálogo, búsqueda de candidatos, asignación y retiro histórico. No se inventan roles externos al catálogo D-04 ni permisos de módulos futuros. No se eliminan roles, permisos o asignaciones históricos.

## Pruebas y evidencia

MySQL testing real, base/cuenta/clave exclusivas y guarda antes de limpieza; detalles reproducibles en DEVELOPMENT_SETUP. Todas las pruebas automáticas, incluidas las de fundación, usan testing. Las comprobaciones web manuales usan el desarrollo local con cuentas ficticias.

| Área | Evidencia |
| --- | --- |
| Fundación | Redirección, Inertia y conexión real/zona MySQL |
| Login/sesión | Regeneración, CSRF, Argon2id, error genérico, límite, expiración, titular incorrecto, ausencia, revocación, inactividad, varias sesiones y logout repetido |
| Usuarios | Validación, unicidad/case-insensitive, alta transaccional, edición, cambio de contraseña, baja, historial, paginación, campos prohibidos y 404 |
| Roles/RBAC | CRUD limitado al catálogo, permisos inválidos, denegación por cada rol, ausencia de bypass, efecto inmediato, retirar/reasignar, duplicados históricos y seed idempotente |
| Invariantes | Último administrador y permisos mínimos de recuperación; dos procesos reales simultáneos para asignar y deshabilitar |
| Fallos | Registro de sesión fallido, escritura/eliminación técnica fallida y respuesta protegida que revoca/expira cookie |
| Aislamiento | Configuración de desarrollo rechazada antes de consulta; cuenta testing denegada sobre desarrollo; metadatos de esquema y CHECK/FK |
| Concurrencia de sesión | Renovación y revocación simultáneas sin borrar revocación ni crear otra sesión |

Resultado automatizado 2026-10-23: **65 tests / 493 aserciones, sin omitidos, 67,84 s**. Pint y build aprobados. Composer audit y npm audit: cero vulnerabilidades reportadas, incluidas dependencias de desarrollo. Solo Faker/Mockery y transitiva Hamcrest añadidos en Composer; package.json/package-lock.json sin cambios.

## Revisión manual completada

**Validación manual completada el 2026-10-23 sobre la compilación publicada.** Al reanudar con una pestaña nueva, el navegador volvió a aceptar acciones. La incidencia anterior no dejó operaciones pendientes: se repitieron login, alta/edición de cuenta ficticia, asignación/retiro, baja lógica, cambios de permisos y logout. No fue necesario modificar código durante este cierre.

| Pasos solicitados | Resultado observado |
| --- | --- |
| 1–4: entorno, login, credenciales inválidas y administrador | Entorno aislado iniciado; error genérico y acceso correcto con credenciales privadas |
| 5–8: Inicio, Usuarios, alta ficticia y edición | Completados; edición sin cambiar contraseña y filtros por nombre, estado y rol verificados |
| 9: deshabilitar mediante interfaz | Cancelar conservó ACTIVO; Confirmar produjo INACTIVO, desapareció del filtro de activos y apareció en inactivos |
| 10–11: Roles y permisos, asignar rol | Catálogo real consultado; asignación adicional visible e incorporada al historial |
| 12: retirar rol | Escape canceló sin cambios; Confirmar retiró Estudiante y conservó la fila con fecha y responsable |
| 13: verificar permisos modificados | Retirar usuarios.editar al Administrador produjo 403 al abrir la URL de edición; después se restauraron los once permisos |
| 14–15: logout y acceso posterior a URL protegida | Logout desde escritorio y móvil redirigió al login; acceder a la URL protegida volvió a requerir autenticación |

Se comprobó además el rechazo de la cuenta INACTIVA con su contraseña correcta, con el mismo mensaje genérico. La descripción de Administrador se editó y se restauró desde CU04. El alta de rol muestra el estado correcto y deshabilita guardar cuando las cuatro identidades ya existen; no se borraron roles para probarla en desarrollo.

ConfirmAction usa `<dialog>`: el foco inicial está en Cancelar; Cancelar y Escape no ejecutan la operación; Confirmar aplica la acción. El menú móvil abre con Enter, cierra con Escape y al seleccionar navegación; al estar cerrado, el recorrido de teclado omite sus enlaces. En login, Tab pasa de correo a contraseña y el foco tiene contorno visible.

| Tamaño solicitado | Comprobación visual |
| --- | --- |
| 1440×900 | Login y roles; alineación, contraste, controles y mensajes |
| 1366×768 | Inicio y navegación de escritorio |
| 768×1024 | Formulario de usuario, etiquetas, columnas y acciones |
| 390×844 | Login, roles y menú; listas, detalle, formularios de usuario/rol, configuración de permisos y catálogo |

En las vistas revisadas no se observó desbordamiento horizontal de página. Las tablas anchas usan desplazamiento interno accesible. La inspección de formularios no encontró campos sin etiqueta. La consola consultada en las vistas y flujos revisados no reportó errores ni advertencias. El tamaño de prueba se restableció al terminar. Esto acredita los recorridos y tamaños revisados, no una auditoría exhaustiva de accesibilidad ni compatibilidad con todos los navegadores.

Lectura final en MySQL: un Administrador ACTIVO con once permisos y descripción original; dos cuentas sintéticas INACTIVAS; retiro histórico con responsable; cero sesiones de acceso vigentes sin revocar. La primera cuenta se limpió mediante AdministrationService durante la incidencia anterior; la segunda se deshabilitó en navegador. No se borraron usuarios ni asignaciones. La suite automática conserva 65 pruebas / 493 aserciones aprobadas; este cierre solo actualiza documentación después de validar la interfaz existente.

## Límites explícitos

No se implementó CU30 ni registros_actividad; sesiones e historial de roles no sustituyen auditoría general. Los CU03/CU04 tienen implementado su núcleo autorizado; aceptación de la parte de bitácora sigue pendiente. No se implementó Fase 3, recursos, reservas, préstamos, QR, IoT, Blockchain, reportes, chatbot, API/móvil ni despliegue. No se afirma capacidad de carga ni disponibilidad de producción.

## Extensión posterior de Fase 3

Las cifras y límites anteriores describen el cierre histórico de Fase 2. El catálogo actual tiene 15 permisos: los once de seguridad mantienen elegibilidad exclusiva de Administrador; cuatro de espacios habilitan consulta a todos y gestión/estado/bloqueos solo a Administrador y Encargado. La asignación inicial total es 15/4/1/1. AuthorizationService ahora evalúa elegibilidad por cada acción, sin bypass y sin combinar elegibilidad de un rol con el permiso de otro. Seeder incremental conserva revocaciones, y la UI de roles ofrece solo permisos elegibles.

Los 65 tests anteriores siguen pasando dentro de 127 tests / 1395 aserciones. Se conservan guardas de sesión, invariantes administrativas y aislamiento MySQL. D-04 se concreta por módulo/acción para espacios conforme §35; ámbito fino y CU30 siguen pendientes. [Evidencia de Fase 3](SPACES_IMPLEMENTATION.md).

## Extensión de permisos en Fase 4

El catálogo total ahora contiene 21 permisos. La [matriz de reservas](RESERVATIONS_IMPLEMENTATION.md#permisos-y-alcance) agrega seis sin cambiar los once de seguridad ni los cuatro de espacios. Administrador tiene 20 permisos elegibles, no 21: solicitar requiere Docente/Estudiante. El seeder conserva revocaciones. Las pruebas anteriores se conservan con expectativas actualizadas por rol; una prueba concurrente verifica revocación mientras se espera el lock RBAC. Las cuentas sintéticas de revisión se deshabilitaron y sus sesiones se revocaron; no se publican credenciales.

## Ampliación RBAC de Fase 5

El catálogo vigente tiene 33 códigos: los 21 existentes y los 12 documentados en [matriz exacta de equipamiento/préstamos/incidencias](EQUIPMENT_LOANS_IMPLEMENTATION.md#permisos-y-alcance). Concesiones iniciales completas: Administrador 30, Encargado de Laboratorio 19, Docente 7, Estudiante 7. SecuritySeeder añade únicamente permisos nuevos y conserva revocaciones; probado idempotente. Policies de EquipamientoTecnologico, Prestamo e IncidenciaMantenimiento aplican permisos y propiedad. Pruebas de 403, CSRF, manipulación de actores/alcances, sesión y revocación durante espera preservan el núcleo anterior; CU30 permanece diferido.

## Permiso de CU26 — Fase 10

Solo notificaciones.ver_propias nuevo para Administrador, Encargado de Laboratorio, Docente y Estudiante. Catálogo total 47; por rol 43/29/10/10. Sin bypass de propiedad ni acceso a avisos ajenos. [Implementación y evidencia](NOTIFICATIONS_IMPLEMENTATION.md).
