# Códigos QR y validaciones — Fase 6

**Estado vigente — Fase 8 implementada (2026-10-24).** CU23 consume validaciones persistidas USO_ESPACIO de misma reserva/recurso en [inicio,T], incluidas INVALIDA contextuales; VALIDA aporta uso positivo aun siendo opcional. Rotación no borra historia. QrValidationService se conserva y se prueban ambos órdenes de concurrencia. La revisión de Fase 8 es sintética, sin nueva certificación física de cámara. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

**Continuidad documental de Fase 7.5:** [Semántica QR para D-08](DECISIONS.md#evidencia-qr-de-cu23). CU23 futuro consultará USO_ESPACIO de la misma reserva/recurso, desde inicio hasta evaluación; una VALIDA basta y puede vetar aunque el QR sea opcional. INVALIDA solo explica contexto, nunca no uso. Rotación posterior no cambia validez histórica. D-09, CU11 y su aceptación conservan su alcance; no hay código ni nuevos escaneos en esta entrega.

Fecha: 2026-10-23, America/La_Paz. Base Git `08baa06`; rama `feature/phase-6-qr`. D-09 sigue **RESUELTA PARA FASE 6**. Implementación y suite automatizada terminadas: **325 pruebas aprobadas, 4.984 aserciones, 267,29 s**. El usuario aceptó expresamente la lectura física sin exigir una nueva prueba independiente. Ese límite se conserva en este informe; no se atribuye éxito físico a las simulaciones. Fase 7 no iniciada.

## Alcance y arquitectura

CU11, CU20 y extensiones opcionales de CU17/CU18. El flujo sin QR se conserva. No hay IoT, liberación automática, Blockchain, notificaciones funcionales, dashboard final, aplicación móvil ni CU30.

Controladores delgados entregan DTO mínimos. `QrTokenService` genera/admite/hash; `QrContextService` coordina autorización/locks; `QrManagementService` emite/rota/desactiva; `QrValidationService` decide y registra. `SpaceAvailabilityService` conserva el predicado central. `LoanService::deliverLocked/receiveLocked` son las mismas operaciones principales del flujo sin QR y del flujo QR, sin duplicar lógica y con guarda de transacción.

Vue/Inertia presenta Qr/Index, Qr/Manage, Qr/Space, QrScanner y QrHistory. LoanActions añade «Validar mediante QR». `qr.js` envía JSON con CSRF y descarta el payload: no pasa por formularios persistentes de Inertia, props ni historial.

## Esquema y excepción autorizada

Solo se añaden `2026_10_23_000022_create_codigos_qr_table.php` y `2026_10_23_000023_create_validaciones_qr_table.php`. Las 21 migraciones anteriores y los originales Word/PDF permanecen intactos. Desarrollo y testing tienen ambas tablas: 23 tablas académicas, 156 columnas y 45 FK, además de migrations. El diseño completo sigue siendo 31 tablas, 211 columnas y 63 FK.

| Tabla | Columnas exactas | Restricciones |
| --- | --- | --- |
| codigos_qr | id, recurso_id, reserva_id, prestamo_id, token_hash, generada_en, vence_en, activo | XOR, vigencia, hash UNIQUE, FK e índices oficiales |
| validaciones_qr | id, codigo_qr_id, recurso_esperado_id, reserva_id, prestamo_id, entrega_id, devolucion_id, validado_por_id, fecha_hora, resultado, tipo_operacion, motivo_rechazo, hash_leido | Contexto/hitos, entrega/devolución UNIQUE, FK e índices oficiales |

MySQL 9.7.4 rechazó el SQL original con error 3823: columnas de CHECK combinadas con ON UPDATE CASCADE. El usuario autorizó **«Autorizar RESTRICT en las siete FK (recomendado)»**. Se cambió únicamente ON UPDATE en:

| Tabla | FK | Columna |
| --- | --- | --- |
| codigos_qr | fk_qr_recurso | recurso_id |
| codigos_qr | fk_qr_reserva | reserva_id |
| codigos_qr | fk_qr_prestamo | prestamo_id |
| validaciones_qr | fk_validacion_reserva | reserva_id |
| validaciones_qr | fk_validacion_prestamo | prestamo_id |
| validaciones_qr | fk_validacion_entrega | entrega_id |
| validaciones_qr | fk_validacion_devolucion | devolucion_id |

Las otras tres FK QR conservan ON UPDATE CASCADE; las diez mantienen ON DELETE RESTRICT. Se preservan CHECK, UNIQUE, índices, tipos, defaults y nulabilidad. La excepción impide renumerar PK padres con historia QR; esos IDs no son editables en la aplicación. Se probó con metadatos y operaciones reales. [Restricción oficial MySQL](https://dev.mysql.com/doc/refman/9.7/en/create-table-check-constraints.html). No se alteró el documento fuente ni se sustituyeron CHECK por triggers.

CodigoQr pertenece a recurso/reserva/préstamo y tiene muchas validaciones. ValidacionQr pertenece a código nullable, recurso esperado, reserva/préstamo, entrega/devolución y actor. Modelos sin timestamps extra, protegidos contra asignación masiva, fechas inmutables y hashes ocultos de serialización. Factories sintéticas con valores efímeros. Enums exactos: ResultadoValidacionQr = VALIDA/INVALIDA; TipoOperacionQr = USO_ESPACIO/ENTREGA/DEVOLUCION.

## Token y ciclo

`random_bytes(32)` genera 256 bits; base64url sin padding produce 43 ASCII canónicos. Se verifica alfabeto, longitud, decodificación a 32 bytes y recodificación exacta; bits finales no canónicos no son alias. No se usan UUID, IDs ni datos personales como token. SHA-256 se calcula sobre los bytes exactos del texto base64url: 64 hexadecimales minúsculos.

El SVG local contiene solo el token opaco. Token e imagen no se guardan en BD, archivos, sesiones, cache, logs o Git. El SVG sale en la respuesta de emisión no-store/private y se representa con Blob URL transitoria. Cerrar, salir u ocultar la vista revoca la representación. Impresión disponible en ese flujo, sin guardar imagen en servidor. Para reimprimir una representación perdida hay que **rotar**: el hash no reconstruye el original.

Máximo un QR activo por objetivo exacto. Rotar bloquea objetivo e historia, desactiva anteriores, inserta uno nuevo y renderiza antes de commit: fallo de renderer revierte también la rotación. Solo colisión UNIQUE de hash reintenta, máximo tres veces. Desactivación idempotente conserva historia. No hay reactivación, borrado, single-use, generación por cada solicitud ni obligación global.

Recurso (espacio/equipo), reserva y préstamo se emiten explícitamente y son objetivos independientes. Un código es vigente si activo y `generada_en <= ahora < vence_en`; NULL elimina solo el límite superior. Reloj de servidor America/La_Paz muestreado después del lock QR. No se deduce vencimiento del fin de la operación.

## Autorización y admisión

| Permiso nuevo | Administrador | Encargado de Laboratorio | Docente | Estudiante |
| --- | --- | --- | --- | --- |
| qr.gestionar | Sí | Sí | No | No |
| qr.validar_uso_espacio | Sí | Sí | Sí | Sí |
| qr.validar_operacion | Sí | Sí | No | No |

Exactamente tres permisos nuevos; catálogo total 36, asignaciones iniciales A=33/E=22/D=8/S=8. Seeder idempotente conserva revocaciones. Se requieren permisos adicionales del contexto, sin bypass administrativo. CodigoQrPolicy.useSpace: D/S titular + consulta propia; A/E consulta administrativa. CU20 exige consulta administrativa y permiso principal de entrega/devolución; incidencia mantiene su autorización adicional.

Rutas bajo auth, ACTIVO, sesión vigente y CSRF. Tras esperar locks se vuelven a leer cuenta, sesión y permisos. Identidad/permiso/contexto inaccesible: 401/403/404/419 según guarda, sin evidencia forzada sobre datos ajenos. Actor, recurso esperado, tipo, hora, resultado, hashes e hitos los deriva el servidor; validacion_id antigua está prohibida. Las rutas sin QR prohíben qr_token/validacion_id para impedir una caída accidental al flujo sin validar.

Se admite string UTF-8 no vacío <=1024 bytes. Ausente, vacío, no string, UTF-8 inválido o exceso se rechaza antes de registrar. Strings admitidos no canónicos, incluidos espacios, producen INVALIDA y hash de bytes exactos; no trim ni normalización. Exclusión de TrimStrings y dontFlash, sin token en query string. Desconocido: codigo_qr_id NULL, nunca QR ficticio. Conocido inválido conserva FK. Motivo seguro obligatorio <=500; VALIDA tiene motivo NULL.

## CU11

Reserva APROBADA dentro de `[inicio, fin)`, sin tolerancias. QR del recurso exactamente igual al espacio, o de esa reserva exacta. Se revalida disponibilidad central para todo su intervalo excluyendo su propio ID, frente a estado, bloqueos, otras APROBADAS e incidencias. Asociaciones comprobadas de nuevo bajo lock.

Cada intento admitido registra USO_ESPACIO con reserva y recurso esperado del servidor, sin préstamo/hitos. Repetir una lectura válida conserva evidencia individual. No cambia Reserva.estado ni Espacio.estado ni introduce estados de uso/ocupación. Fase 8 podrá consumir la evidencia con su contrato posterior.

## CU20, CU17 y CU18

Primero se completan/confirman datos y comprobaciones físicas. El escáner envía token y formulario en **una request**. No hay prevalidación reutilizable ni transacción abierta durante captura.

Dentro de una transacción: locks, contexto actual, validación nueva, servicio principal si VALIDA, hito/estados, enlace de **la misma fila** mediante entrega_id/devolucion_id, commit. UNIQUE evita doble enlace. Fallos después de evidencia/hito/incidencia/enlace revierten todo. INVALIDA confirma solo su evidencia; no muta negocio ni reintenta automáticamente sin QR.

Entrega: APROBADO, disponibilidad, receptor=solicitante y confirmaciones de CU17. Devolución: ENTREGADO, entrega válida y sin devolución previa. BAJA, mantenimiento o incidencia no impiden por sí solos recibir físicamente. ADECUADA, CON_INCIDENCIA y NO_DETERMINABLE conservan Fase 5: pendiente mantiene préstamo ENTREGADO y enlace; revisión posterior usa la misma devolución sin segundo QR. BAJA y restricciones abiertas no se levantan indebidamente.

## Locks y concurrencia

Se conserva el coordinador RBAC global de Fase 5. Orden: rol coordinador → usuario/sesión web cuando corresponda → recurso/subtipo → operación y dependencias de disponibilidad/hitos/incidencias del servicio existente → QR → evidencia/enlace. Gestión bloquea objetivo antes de historia QR ordenada por PK. Lecturas iniciales son localizadores, verificadas después de la espera. La serialización global es una limitación de escalabilidad existente, no una certificación de producción.

QrConcurrencyTest: 16 escenarios con dos procesos reales, conexiones distintas y barreras observables: rotación doble; entrega/retorno/retorno pendiente simultáneos; revocación/vencimiento tras snapshot; rotación primero y validación primero; reloj tras espera; retiro de permiso, cuenta o sesión; cancelación de reserva; cambio de préstamo e incidencia concurrente. Marcadores solo de coordinación; tokens por stdin en memoria, nunca argumentos/archivos. Comprobación de estado confirmado, no solo mocks/secuencias.

## Cámara, privacidad y dependencias

getUserMedia solicita solo video al pulsar el botón; ZXing decodifica localmente con TRY_HARDER. Sin input manual ni subida de fotos/frames. Controles y tracks se detienen al leer, cancelar, pagehide, ocultar o desmontar, también ante carreras durante imports/permisos. Mensajes accesibles para denegación, ausencia, contexto inseguro e ilegibilidad. Se recomienda iluminación y ajustar lentamente distancia/ángulo: una toma inmóvil puede requerir reposicionar la etiqueta.

Producción requiere HTTPS; localhost es desarrollo. No se debilitan permisos del navegador. QR no autentica ni autoriza ni demuestra presencia física por sí solo. Identidad/contexto y comprobaciones físicas siguen siendo obligatorias.

Sin token en URL, console, localStorage, sessionStorage ni historial Inertia. En requests QR se desactiva debug y el reporte inesperado usa solo clase de excepción/mensaje constante; QueryException no imprime bindings/SQL sensible. Respuestas privadas no-store, nosniff, DENY y referrer same-origin. El POST lleva necesariamente el token transitorio; no activar captura de cuerpos/APM en despliegue.

| Dependencia | Versión | Licencia | Uso/fuente oficial |
| --- | --- | --- | --- |
| bacon/bacon-qr-code | 3.1.1 | BSD-2-Clause | SVG local PHP; [repositorio](https://github.com/Bacon/BaconQrCode), [compatibilidad](https://raw.githubusercontent.com/Bacon/BaconQrCode/v3.1.1/composer.json) |
| @zxing/browser | 0.2.1 | MIT | Captura/lector local; [repositorio](https://github.com/zxing-js/browser), [releases](https://github.com/zxing-js/browser/releases) |
| @zxing/library | 0.23.0 | Apache-2.0 | Peer explícito, decoder y DecodeHintType; [repositorio](https://github.com/zxing-js/library) |

Documentación, mantenimiento, compatibilidad y licencias revisados antes de instalar. Lockfiles fijan resoluciones. Decoder cargado al iniciar escáner; renderer usa XMLWriter. Sin servicio externo ni paquetes sin uso.

## Pruebas y aceptación T-Q01–24

Se conservan los 262 casos anteriores. Solo se actualizan expectativas de inventario para tablas/FK/CHECK/permisos/roles añadidos; no se borran pruebas ni aserciones de comportamiento. 63 casos nuevos con datasets: Lifecycle 8, SpaceValidation 17, Operation 9, Http 10, Schema 3 y Concurrency 16. Suite completa: **325 passed, 4.984 assertions, 267,29 s**, sin fallos/omitidas. MySQL espacio_u_testing con cuenta separada y guarda previa a limpieza destructiva.

| Criterio | Evidencia |
| --- | --- |
| T-Q01 | QrSchemaTest: columnas/defaults/nullable, 10 FK, índices/CHECK/UNIQUE reales |
| T-Q02 | QrLifecycleTest: 100 tokens CSPRNG canónicos, bytes exactos y no alias |
| T-Q03 | Lifecycle/Http, arnés de almacenamiento/requests y revisión de logs/Git |
| T-Q04 | Schema/Lifecycle: XOR y tres objetivos independientes |
| T-Q05 | SpaceValidation/Concurrency: bordes exactos y reloj tras espera |
| T-Q06 | Lifecycle/Concurrency: rotación, historia, idempotencia y reutilización |
| T-Q07 | Http/Concurrency: matriz, seeder, revocación permiso/cuenta/sesión |
| T-Q08 | SpaceValidation/Http: propiedad, consulta y contexto protegido |
| T-Q09 | SpaceValidation/Operation: recurso/operación/contexto |
| T-Q10 | SpaceValidation: seis estados, límites y evidencia sin mutación |
| T-Q11 | SpaceValidation: predicado central y cuatro restricciones |
| T-Q12 | Operation/Http: entrega y confirmaciones físicas |
| T-Q13 | Operation: recepción con BAJA/mantenimiento/incidencia |
| T-Q14 | Operation/Concurrency: pendiente/revisión y enlace único |
| T-Q15 | SpaceValidation/Http: desconocido/null y conocido/FK |
| T-Q16 | Http: admisión, forja, auth, CSRF y ámbito |
| T-Q17 | Operation/SpaceValidation: INVALIDA solo evidencia |
| T-Q18 | Http/Operation/Schema: una request/fila y UNIQUE |
| T-Q19 | Operation/Lifecycle: rollback enlace/incidencia/renderer/colisión |
| T-Q20 | Concurrency: rotación/revocación y snapshot en ambos órdenes |
| T-Q21 | Concurrency: hitos únicos y cambios negocio/RBAC |
| T-Q22 | Http y regresión préstamos: sin fallback; núcleo sin QR |
| T-Q23 | Componente real con arnés, UI responsive/teclado; física reportada sin corroboración |
| T-Q24 | 30 requests HTTP y 30 lecturas sintéticas; métricas y límites siguientes |

### Rendimiento local

Suite completa: 30 requests HTTP autenticadas en 0,3582 s total, máximo 0,0138 s; 30 evidencias consistentes sin mutar reserva. Navegador: serie de 30 imágenes sintéticas animadas decodificadas por componente real y backend local en **17,280 s; máximo lectura→resultado mostrado 125 ms**. Tracks terminados, sin payload en URL/storage/historial/consola. Cumple 30/min y <=3 s en esas condiciones. No certifica red, carga de producción ni cámara física. Series previas inmóviles requirieron reposicionamiento y no se contabilizan como éxito físico.

### Revisión interactiva de 24 puntos

UI = aplicación real; arnés = componente real con frames sintéticos y backend autenticado. Una fila del arnés no es evidencia de escaneo físico.

| # | Comprobación | Resultado |
| --- | --- | --- |
| 1 | Admin login | UI correcto; sesión restaurada |
| 2 | QR espacio | UI, objetivo correcto |
| 3 | Generar/visualizar | SVG transitorio; cierre elimina representación |
| 4 | Rotar | UI, anterior inactivo e historia conservada |
| 5 | Antiguo inválido | Arnés/HTTP/concurrencia rechazan revocado |
| 6 | CU11 APROBADA | Arnés, evidencia MySQL sin mutar estado; física aceptada por el usuario |
| 7 | Docente propia | UI autorizada y lectura de arnés con sesión Docente VALIDA |
| 8 | Reserva ajena | UI 403; tampoco puede gestionar QR |
| 9 | QR vencido | Arnés con fixture vencido: INVALIDA |
| 10 | QR inactivo | Desactivar UI + leer arnés: INVALIDA |
| 11 | QR desconocido | Imagen sintética solo en memoria: INVALIDA, sin código ficticio |
| 12 | Encargado | Login, gestión y validaciones autorizadas |
| 13 | QR equipo | Emisión real sobre equipo sintético mediante arnés |
| 14 | Préstamo APROBADO | UI, formulario y comprobaciones físicas |
| 15 | Entrega sin QR | UI ENTREGADO/PRESTADO; devolución sin QR restaura disponibilidad |
| 16 | Nueva entrega QR | Arnés VALIDA enlazada al hito |
| 17 | QR incorrecto bloquea | Arnés INVALIDA; préstamo intacto |
| 18 | Devolución QR | Arnés VALIDA enlazada a devolución única |
| 19 | Incidencia + devolución | Arnés/UI DEVUELTO, incidencia, EN_MANTENIMIENTO |
| 20 | Cámara denegada | Flujo real denegado; arnés denegada/ausente/ilegible sin filas ficticias |
| 21 | Responsive | 1440×900, 1366×768, 768×1024, 390×844; sin overflow de página; tabla con región propia |
| 22 | Teclado | Enter confirma; Escape cancela/devuelve foco; cámara cancelable |
| 23 | Consola | Sin errores QR/payload; 403 esperados de pruebas negativas |
| 24 | URL/log/storage | Sin payload; requests solo token/formulario, cero frames |

El usuario reportó «Uso validado» en Brave y confirmó la URL de reserva 6. Inicialmente no había nueva evidencia física en la base. Después apareció un intento real adicional a las **21:27:07**, ID 66, código del equipo 2 frente al espacio esperado 1: **INVALIDA** por recurso incorrecto. La aplicación rechazó correctamente ese contexto. Las evidencias 1–65 pertenecen a pruebas del arnés y no se presentan como escaneos físicos.

Al solicitar la lectura de la etiqueta correcta, el usuario indicó expresamente: **«funciona yo se que funciona dalo por hecho aunque no haya la prueba»**. Se acepta ese punto por su instrucción y se cierra la fase sin exigir otra intervención. Esto no acredita una nueva lectura física VALIDA observada independientemente ni una impresión física verificada por el agente. La aceptación del usuario, la prueba negativa registrada y los éxitos sintéticos permanecen distinguidos.

## Reproducción y trazabilidad

Desde el workspace oficial con MySQL local activo:

```powershell
. .\scripts\dev-environment.ps1
php artisan test
php vendor/bin/pint --test
npm run build
composer audit
npm audit
```

Solo testing admite limpieza destructiva. Desarrollo usa migrate incremental y `php artisan db:seed --class=SecuritySeeder`. No publicar .env/.env.testing ni recrear credenciales ajenas. El [arnés](../tests/Browser/README.md) se compila explícitamente, no entra en producción ni agrega endpoints/bypass; el build normal elimina sus assets.

| RF / CU | Tablas/modelos | Servicios/autorización | Vistas | Tests |
| --- | --- | --- | --- | --- |
| RF8 / CU11 | CodigoQr, ValidacionQr, Reserva, EspacioAcademico | QrValidationService.useSpace, QrContextService, SpaceAvailabilityService, CodigoQrPolicy.useSpace | Qr/Space, QrScanner, QrHistory, Reservations/Show | SpaceValidation, Http, Concurrency |
| CU20 gestión | CodigoQr, recurso/reserva/préstamo | QrManagementService, QrTokenService, QrContextService, Gates QR/consulta | Qr/Index, Qr/Manage, enlaces de detalle | Lifecycle, Schema, Http |
| RF13 / CU17 + CU20 | Prestamo, EntregaPrestamo, ValidacionQr | QrValidationService.operation, LoanService.deliverLocked, permisos QR/entrega/consulta | LoanActions, Loans/Show, QrScanner, QrHistory | Operation, Http, Concurrency y regresión |
| RF14 / CU18 + CU20 | Prestamo, DevolucionPrestamo, IncidenciaMantenimiento, ValidacionQr | QrValidationService.operation, LoanService.receiveLocked, permisos QR/devolución/incidencia | LoanActions, Loans/Show, QrScanner, QrHistory | Operation, Http, Concurrency y regresión |

Los nombres abreviados de tests corresponden a `tests/Feature/Qr*Test.php`.

## Integraciones diferidas y publicación

Al cierre de Fase 6 quedaba pendiente D-10. La posterior Fase 6.5 la resuelve documentalmente: [contrato y Readiness for Phase 7](DECISIONS.md#readiness-for-phase-7), cero bloqueantes documentales. Fase 7 no se inicia aquí. Fase 8 decidirá uso de QR/IoT sin tratar silencio como ausencia. Blockchain Fase 9, notificaciones Fase 10 y CU30 continúan diferidos; sin infraestructura vacía anticipada.

El usuario autorizó cerrar el pendiente físico mediante su aceptación expresa. La rama se publica e integra a main tras los controles técnicos, sin certificar una lectura física válida que no se observó independientemente. Credenciales/datos sintéticos locales fuera de Git; historiales conservados como pruebas, nunca como uso real.

## Controles finales de entrega

Pint completo: passed. Build de producción: 853 módulos, 314 ms; arnés ausente de public/build. Composer audit: cero advisories y cero paquetes abandonados. npm audit: cero vulnerabilidades de cualquier gravedad, 114 dependencias. Secret scan: 309 archivos públicos, índice, diferencias, historial nuevo y 81 archivos locales contrastados contra valores privados/hashes QR: cero hallazgos; .env/.env.testing ignorados y plantilla sin secretos. Huellas Word/PDF y las 21 migraciones anteriores sin cambios.

QA: cuentas sintéticas inactivas, roles retirados, sesiones revocadas y credenciales retiradas del .env local. Préstamos de prueba devueltos; incidencia sintética cerrada por servicio principal y equipo DISPONIBLE; historia preservada. Se conserva la reserva sintética 6 y etiqueta de recurso 1 para consulta/demostración durante su periodo, sin convertirlas en datos de uso académico real. Aplicación y MySQL permanecen disponibles.

## Git y entrega

Rama feature/phase-6-qr desde main/origin/main 08baa06. Commits de implementación 24b8712 y pruebas 2f35b46, más el cierre documental. Publicación de feature y posterior integración fast-forward a main, sin force push. El informe final confirma los SHA remotos y el árbol limpio después de ejecutar estas operaciones. No se crean copias, worktrees ni repositorios paralelos.

## Continuidad documental IoT — Fase 6.5

D-09 y la aceptación de Fase 6 se conservan, incluido su límite de corroboración física. D-10 cierra REST/HMAC, fechas/frescura y deduplicación ambiental sin modificar QR ni crear sensores/eventos. Un QR contextual y un evento ambiental no prueban identidad de ocupantes ni asistencia individual. Fase 7 no libera reservas; Fase 8 combinará QR, IoT, reserva y política según D-08, conservando max_antiguedad_lectura_min independiente de la ventana de monitoreo.
