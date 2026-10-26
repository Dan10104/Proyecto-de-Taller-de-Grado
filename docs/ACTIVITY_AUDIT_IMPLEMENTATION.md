# Registro de actividad y bitácora — Fase 12.6

**Estado vigente — Fase 13 completada y validada (2026-10-26).** QA integral de CU01–CU30 y RF1–RF25 web; RF26 móvil diferido. [Informe de seguridad, rendimiento y pruebas](PHASE_13_SYSTEM_VALIDATION.md) y [matriz final por CU](FINAL_USE_CASE_VALIDATION.md). Los estados de fases anteriores que siguen son históricos. OpenAI/Sepolia pendientes independientes; excepción dev Hardhat/Zod explícita. Fase 14 NO iniciada.

Implementación de RF25/CU30 conforme al [contrato AC1–AC15](DECISIONS.md#activity-audit-contract--readiness-for-cu30). Autorización posterior a Fase 12.5; Fase 13 no iniciada. Validación local completada el 2026-10-26: 1.030 tests/11.467 assertions, build/Pint, auditorías, medición MySQL y revisión manual. Evidencia y límites debajo.

## Escritura central y catálogo

[ActivityAction](../app/Enums/ActivityAction.php) contiene exactamente 56 claves: 18 existentes y 38 administrativas. Se cotejan automáticamente con las tablas AC3/AC4. La columna SQL continúa VARCHAR. [ActivityRecorder](../app/Services/Activity/ActivityRecorder.php) exige transacción, campos permitidos, acción/resultado/criticidad compatibles, responsable y referencias positivas; límite Unicode de descripción 500. No endpoint de creación, observer genérico de negocio, tabla nueva ni backfill.

CriticalActivityRecorder, NotificationActivityRecorder y ReportAuditService delegan una sola inserción al recorder. Conservan firmas de llamada, contexto, fechas, NULL, cuatro críticas y callbacks. NotificationRuleRegistry mantiene 17 acciones; ActivityReportQuery mantiene 18. Las 38 nuevas son humanas/no críticas, sin notificación ni Blockchain.

### Dieciocho hitos conservados

| Acción existente | Hito/productor actual | Responsable y contexto | Crítica |
| --- | --- | --- | --- |
| RESERVA_SOLICITADA | ReservationService → NotificationActivityRecorder | Solicitante; U/R/V | No |
| RESERVA_APROBADA | ReservationService → CriticalActivityRecorder | Resolutor; U/R/V | Sí |
| RESERVA_RECHAZADA | ReservationService → NotificationActivityRecorder | Resolutor; U/R/V | No |
| RESERVA_CANCELADA | ReservationService → CriticalActivityRecorder | Cancelador; U/R/V | Sí |
| PRESTAMO_SOLICITADO | LoanService → NotificationActivityRecorder | Solicitante; U/R/P | No |
| PRESTAMO_APROBADO | LoanService → NotificationActivityRecorder | Resolutor; U/R/P | No |
| PRESTAMO_RECHAZADO | LoanService → NotificationActivityRecorder | Resolutor; U/R/P | No |
| PRESTAMO_ENTREGADO | LoanService → CriticalActivityRecorder; también camino QR | Entregador; U/R/P | Sí |
| DEVOLUCION_PENDIENTE_REVISION | LoanService → NotificationActivityRecorder | Receptor; U/R/P | No |
| PRESTAMO_DEVUELTO | LoanService → CriticalActivityRecorder; también revisión/QR | Confirmador; U/R/P | Sí |
| PRESTAMO_VENCIDO | OverdueLoanService → NotificationActivityRecorder | scheduler:vencimientos; U/R/P | No |
| INCIDENCIA_REGISTRADA | IncidentMaintenanceService o devolución → NotificationActivityRecorder | Reportante; R/I y U/P solo si deriva de devolución | No |
| INCIDENCIA_CERRADA | IncidentMaintenanceService → NotificationActivityRecorder | Cerrador; mismo contexto derivado | No |
| MANTENIMIENTO_REGISTRADO | IncidentMaintenanceService → NotificationActivityRecorder | Reportante; R/I y U/P solo si deriva de devolución | No |
| MANTENIMIENTO_CERRADO | IncidentMaintenanceService → NotificationActivityRecorder | Cerrador; mismo contexto derivado | No |
| RESERVA_ALERTADA_LIBERACION | ReleaseEvaluationService → NotificationActivityRecorder | scheduler:liberacion; U/R/V; evaluación/política en descripción | No |
| RESERVA_LIBERADA | ReleaseEvaluationService → NotificationActivityRecorder | scheduler:liberacion; U/R/V; evaluación/política en descripción | No |
| REPORTE_EXPORTADO | ReportGenerationService → ReportAuditService | Generador; sin FK de contexto | No |

### Treinta y ocho productores nuevos

La tabla siguiente transcribe los identificadores y asociaciones del contrato; sus productores están instrumentados en esta fase. El flujo de catálogo se trasladó del callback del controlador a SpaceManagementService.saveCatalog; el controlador conserva validación y respuesta.

| Acción nueva | Operación existente / disparador efectivo | Contexto mínimo |
| --- | --- | --- |
| USUARIO_CREADO | AdministrationService.createUser confirma alta | U=nuevo usuario; sin valores personales |
| USUARIO_EDITADO | updateUser cambia datos o contraseña administrativa | U=destinatario; nombres de campos y revocación de sesiones, nunca valores/hashes |
| USUARIO_DESHABILITADO | disableUser, ACTIVO→INACTIVO | U=destinatario; estados |
| ROL_CREADO | saveRole sin target, rol institucional faltante | Rol #id |
| ROL_EDITADO | saveRole cambia descripción, no identidad institucional | Rol #id; campo, no texto libre |
| PERMISOS_ROL_ACTUALIZADOS | setPermissions cambia conjunto | Rol #id y cantidades añadidas/retiradas |
| ROL_ASIGNADO | assign/assignLocked crea asignación activa | U=destinatario; rol #id y asignación #id |
| ROL_RETIRADO | withdraw retira asignaciones activas de un par | U=destinatario; rol #id y cantidad |
| ESPACIO_CREADO | SpaceManagementService.save sin target | R=espacio; estado inicial |
| ESPACIO_EDITADO | save cambia datos/características sin cambiar estado | R=espacio; campos/cantidad de asociaciones |
| ESPACIO_ESTADO_CAMBIADO | state o save cambia estado operativo | R=espacio; antes→después y campos adicionales |
| BLOQUEO_ESPACIO_CREADO | block confirma bloqueo | R=espacio; bloqueo #id, inicio/fin, sin motivo |
| BLOQUEO_ESPACIO_DESACTIVADO | deactivate, activo→false | R=espacio; bloqueo #id |
| PISO_CREADO | SpaceCatalogController.save, pisos, alta | Piso #id |
| PISO_EDITADO | Mismo flujo, edición efectiva | Piso #id; campos |
| TIPO_ESPACIO_CREADO | Catálogo tipos, alta | Tipo de espacio #id |
| TIPO_ESPACIO_EDITADO | Catálogo tipos, edición efectiva | Tipo de espacio #id; campos |
| CARACTERISTICA_ESPACIO_CREADA | Catálogo características, alta | Característica #id |
| CARACTERISTICA_ESPACIO_EDITADA | Catálogo características, edición efectiva | Característica #id; campos |
| TIPO_EQUIPAMIENTO_CREADO | EquipmentService.saveType sin target | Tipo de equipamiento #id |
| TIPO_EQUIPAMIENTO_EDITADO | saveType cambia datos | Tipo de equipamiento #id; campos |
| EQUIPAMIENTO_CREADO | EquipmentService.save sin target | R=equipo; estado inicial |
| EQUIPAMIENTO_EDITADO | save cambia datos, no estado | R=equipo; campos, sin serie/textos completos |
| EQUIPAMIENTO_ESTADO_CAMBIADO | state/save, transición administrativa | R=equipo; estados; no repetir entregas/devoluciones/incidencias |
| SENSOR_CREADO | IotManagementService.save sin target | R=espacio al crear; sensor #id, sin identificador/credencial |
| SENSOR_EDITADO | save cambia tipo y conserva espacio | R=espacio; sensor #id, campo tipo sin valor libre |
| SENSOR_REASIGNADO | save cambia espacio_actual_id | R=destino; sensor #id, espacios anterior→nuevo; campos adicionales |
| SENSOR_HABILITADO | state, DESHABILITADO→HABILITADO | R=espacio actual; sensor #id |
| SENSOR_DESHABILITADO | state, HABILITADO→DESHABILITADO | R=espacio actual; sensor #id |
| SENSOR_CREDENCIAL_CONSULTADA | provision autorizado prepara respuesta | R=espacio actual; sensor #id; sin clave/HMAC/hash, no es rotación |
| QR_GENERADO | QrManagementService.issue, objetivo sin historia QR | Contexto objetivo AC7; QR #id; sin token/hash/SVG |
| QR_ROTADO | issue, objetivo con historia QR activa o inactiva | Mismo contexto; anterior #id→nuevo #id; incluye desactivaciones internas |
| QR_DESACTIVADO | deactivate explícito, activo→false | Mismo contexto; QR #id; no repetir por rotación |
| POLITICA_LIBERACION_CREADA | ReleasePolicyManagementService.create nueva versión | Política #id y versión técnica; no objeto completo |
| POLITICA_LIBERACION_ACTIVADA | state, false→true | Política #id, estado |
| POLITICA_LIBERACION_DESACTIVADA | state, true→false | Política #id, estado |
| POLITICA_ASIGNADA_ESPACIO | assign, NULL→id o id→otro | R=espacio; política anterior o ninguna→nueva |
| POLITICA_RETIRADA_ESPACIO | assign, id→NULL | R=espacio; política anterior→ninguna |

## Atomicidad, fallos y concurrencia

[ActivityTransaction](../app/Services/Activity/ActivityTransaction.php) coordina las transacciones existentes, sin nuevo lock global. [ActivityAttempt](../app/Services/Activity/ActivityAttempt.php) se admite dentro del servicio tras reautorizar/resolver objetivo y datos. La inserción EXITO ocurre antes del commit: su fallo revierte negocio e hijos. Un alta con roles produce USUARIO_CREADO y una ROL_ASIGNADO por asignación real, en la misma transacción.

RECHAZO se limita a nueve acciones/guardas AC6: último administrador (deshabilitación/retiro), recuperación/elegibilidad de permisos, habilitación de espacio restringido, estado administrativo de equipo incompatible, asignación/retiro/reactivación de política con compromisos o versión no válida y consulta de credencial de sensor deshabilitado. Validaciones ordinarias, permisos, inexistencia, duplicados y no-op no generan ruido. Las decisiones RESERVA_RECHAZADA/PRESTAMO_RECHAZADO conservan EXITO.

ERROR solo para productor nuevo admitido que falla inesperadamente tras rollback confirmado. La excepción del callback debe ser la misma propagada por Laravel y el nivel transaccional debe haberse restablecido; se excluyen desconexiones inciertas y fallos de commit/afterCommit. Se registra el intento en una transacción posterior al rollback y fuera del callback reintentable. Fallo de ese registro no recursa: diagnóstico fijo sin payload. No se garantiza durabilidad durante caída total, ni se asegura rollback de un COMMIT incierto.

Conservados locks RBAC y de recursos/operaciones; reautorización después de espera. Retry transaccional descarta filas del intento revertido. QR mantiene tres intentos por colisión: solo agotamiento final puede producir ERROR, sin uno por retry. No UNIQUE global ni idempotency key añadidos. Requests independientes aceptadas (emisiones QR, consultas explícitas de credencial, exportaciones) pueden producir actividades independientes.

Guardados idénticos, conjuntos iguales, retiro/desactivación repetidos: sin EXITO adicional. Creación prevalece sobre edición/estado; cambio de estado prevalece sobre edición; reasignación de sensor prevalece sobre tipo; reemplazo de política produce una asignación; QR_ROTADO incluye desactivaciones internas y aplica aun si toda la historia previa está inactiva. Cambios físicos automáticos por préstamo/incidencia conservan su propio hito.

## Privacidad e historia

[ActivityDescription](../app/Services/Activity/ActivityDescription.php) compone plantillas cerradas: campos técnicos, estados oficiales, fechas, IDs y contadores. No nombres/correos/series/motivos/soluciones libres, identificadores de sensor, contraseñas/hashes, tokens/hash QR, HMAC, claves Blockchain/OpenAI, cuerpos HTTP, SQL, excepciones o conversación. Cambio de contraseña registra el nombre del campo y cantidad de sesiones revocadas; nunca valores. Provisionamiento IoT registra antes de devolver la credencial; si auditar falla, no se revela.

Contextos U/R/V/P/I conforme AC7. Actor no se copia automáticamente a afectado; roles/catálogos/políticas sin FK conservan ID técnico en plantilla. Sensor conserva recurso al evento; QR conserva contexto de su objetivo. La descripción no sirve para filtrar entidades, autorizar ni construir vínculos.

Historia desconocida se conserva sin modificar DB. DTO suprime acción/proceso arbitrarios y descripción que no corresponda a una plantilla permitida. Validación estructural de plantillas, no un reemplazo mediante regex de posibles secretos. Exportaciones históricas se muestran solo si su metadata cumple catálogo/tipos cerrados; textos truncados/ilegibles se omiten. Nombres de usuarios solo con usuarios.ver y expresamente actuales, no snapshot. Actor inactivo conserva historia; responsables históricos mixtos se muestran ambos, conforme CHECK OR.

## CU30: permiso, rutas y consulta

| Rol elegible | actividad.ver |
| --- | --- |
| Administrador | Sí, revocable y efectivo |
| Encargado de Laboratorio | No |
| Docente | No |
| Estudiante | No |

Catálogo total 52 permisos; actividad.ver no pertenece a RECOVERY. SecuritySeeder incremental conserva revocaciones y solo concede el código nuevo a rol elegible. Concesión errónea a otro rol no autoriza. Sesión D-07, cuenta ACTIVO y permiso se comprueban antes de consulta. No ownership artificial para esta auditoría institucional mínima.

GET /actividad y GET /actividad/{id}; [ActivityController](../app/Http/Controllers/ActivityController.php), [ActivityRequest](../app/Http/Requests/ActivityRequest.php), [ActivityFilters](../app/Services/Activity/ActivityFilters.php), [ActivityQueryService](../app/Services/Activity/ActivityQueryService.php). Respuestas privadas/no-store; sin POST de creación, PUT/PATCH/DELETE, exportación, purga o lectura automática Blockchain. No permiso de dominio concedido por consultar bitácora.

Listado: fecha/hora, acción, resultado textual, actor/proceso, referencias, criticidad; orden fijo fecha_hora DESC,id DESC; 25 por página, filtros conservados. Datos vivos: inserciones pueden desplazar páginas entre requests. Default hoy más 29 días anteriores; America/La_Paz. Fechas civiles [inicio del primer día, inicio del siguiente al último); sin 23:59:59. Rango explícito válido dentro de DATETIME, sin límite académico arbitrario; fin máximo 9999-12-30 para admitir siguiente día.

Filtros exactos combinados por AND: fecha_desde/fecha_hasta (ambas o ninguna), actor_id, usuario_afectado_id, accion (56), resultado (3), critica (0/1), tipo_entidad (8), reserva_id, prestamo_id, recurso_id, incidencia_id, responsable_tipo (todos/humano/proceso), proceso_responsable (scheduler:vencimientos/scheduler:liberacion). IDs positivos; sin coincidencias dan vacío. No SQL/order/tablas/columnas/descripcion libres. Parámetros desconocidos rechazados. Página positiva acotada al entero de implementación para evitar offsets inválidos; tamaño no configurable por cliente.

Tipos derivados: USUARIO, RECURSO, ESPACIO, EQUIPAMIENTO, RESERVA, PRESTAMO, INCIDENCIA, SIN_REFERENCIA. Predicados FK/EXISTS; actor no cuenta como referencia afectada. Una fila puede coincidir con varios tipos sin duplicarse. No categoría sensor/rol/política derivada de texto.

Detalle: proyección cerrada de los trece campos, referencias agrupadas, descripción saneada, sin modelos serializados completos ni nulos innecesarios. Enlaces solo a objeto existente y autorizado por permiso/Policy actual. Sin permiso: texto técnico. Crítica con evidencia y trazabilidad.evidencias.ver ofrece enlace a CU25; nunca verifica/reintenta RPC al consultar. No evidencia asociada es informativo, no conclusión de fraude.

## Interfaz y fronteras

[Listado](../resources/js/Pages/Activity/Index.vue) y [detalle](../resources/js/Pages/Activity/Show.vue), navegación Bitácora condicionada a actividad.ver. Filtros principales y bloque desplegable de responsables/referencias; IDs exactos evitan grandes selectores de usuarios. Botón limpiar, labels/errores, carga, foco/teclado, región de tabla con scroll y estado vacío. Resultado y criticidad siempre textuales. Vue no consulta/autorización de negocio; salida escapada sin v-html.

RF23 sigue agregado/exportable y con sus 18 acciones/scopes; CU30 es lectura individual institucional. No añadir PDF/XLSX/CSV CU30, acciones administrativas al resumen RF23 ni ruta del chatbot. CU29 conserva veinte destinos y privacidad; no registra preguntas/respuestas.

Exclusiones: lecturas IoT, validaciones QR, evaluación CU23 sin ALERTAR/LIBERAR, consulta de dashboard, preview, abrir/leer notificación, autenticación, consulta CU30 y reconciliación/reintento/verificación Blockchain. Mantienen sus historiales especializados. Las cuatro críticas y diecisiete reglas notificables son las mismas de fases previas.

## Inmutabilidad y esquema

RegistroActividad conserva mass assignment bloqueado y guardas update/delete; FK ON DELETE RESTRICT y CHECK actor OR proceso, no XOR. No modificación histórica ni backfill. Retención académica sin purga automática; backups conforme SECURITY_POLICY. No se promete protección frente a administrador SQL ni integridad criptográfica de toda actividad. El esquema de negocio conserva 31 tablas/211 columnas/63 FK. No librerías nuevas ni cambios de lockfiles; cero migraciones/índices adicionales, con evidencia de rendimiento debajo.

## Matriz de pruebas T-A01–T-A40

| Criterios | Evidencia de código/pruebas |
| --- | --- |
| T-A01/02 | ActivityProducerTest catálogo vs AC, delegadores y regresión Traceability*/NotificationBusinessTest |
| T-A03–10 | 38 escenarios de éxito por servicio real en ActivityTestCase/ActivityProducerTest; contexto/precedencia en ActivitySemanticsTest |
| T-A11 | ActivityIntegrityTest no-op/alta compuesta; ActivitySemanticsTest estado/QR/política/sensor |
| T-A12/13 | ActivityConcurrencyTest procesos MySQL; suites previas QR/Release/Notification concurrency y vencimientos |
| T-A14/17 | 38 fallos del recorder con snapshot íntegro de negocio y ERROR raíz; fallo de hijo/negocio después de EXITO |
| T-A15/19 | ActivityTransactionTest retry por error MySQL inyectado, agotamiento y afterCommit; ActivityIntegrityTest desconexión/recorder sin recursión |
| T-A16/18 | Nueve guardas reales + elegibilidad, validación ordinaria/no-op; regresión acceso/HTTP |
| T-A20/21 | Esquema OR existente; responsables mixtos e inactivos, QR U/R/V/P y sensor histórico |
| T-A22–24 | ActivityQueryTest cuatro roles, concesión errónea, revocación/seeder, datos mínimos y regresión D-07 |
| T-A25–29 | Límites locales, filtros válidos/invalidos, ocho tipos, AND, paginación/orden y detalle/404 |
| T-A30–33 | Enlaces revocados, evidencia sin verificación, historia desconocida, whitelist de descripción y metadata RF23 |
| T-A34–36 | Rutas solo GET, guardas de modelo/FK y regresión D-12/N/DR; ninguna nueva acción notifica |
| T-A37 | ActivityVolumeTest/ActivityQrVolumeTest (20 lecturas/validaciones), ChatbotFeatureTest, notificaciones y previews previos |
| T-A38 | Sonda privada MySQL con 10.000 filas, queries/tiempo/memoria y EXPLAIN; resultados debajo |
| T-A39 | QA visual responsiva, teclado/consola; resultados debajo |
| T-A40 | Suite completa conservada, auditorías, fuentes, esquema, diff y secret scan al cierre |

Las pruebas con writes se ejecutan únicamente en espacio_u_testing, MySQL real. No SQLite. Las pruebas de retry SIGNAL ejercitan clasificación/reintento real del driver; no se presentan como un interbloqueo espontáneo. Las carreras de estados/retiro/revocación sí usan procesos/conexiones independientes. No se eliminan tests previos; expectativas de catálogo 51→52 y ruta CU30 se actualizan al alcance autorizado.

## Mediciones y verificación final

### Rendimiento local y EXPLAIN

MySQL 9.7.4, PHP 8.5.12, espacio_u_testing; 10.000 actividades sintéticas repartidas en 60 días, dos actores, resultados mixtos y 10% críticas. Cinco ejecuciones por filtro; sin invalidar cachés del motor. Se mide ActivityQueryService.listing completo (autorización, COUNT, página de 25 y DTO), sin red/navegador. Las cifras no certifican carga concurrente ni producción.

| Consulta | Coincidencias | Media ms | Mín.–máx. ms | Consultas SQL por petición |
| --- | ---: | ---: | ---: | ---: |
| Inicial / últimos 30 días | 5,010 | 8.70 | 7.80–11.37 | 6 |
| Rango de 60 días | 10,000 | 10.24 | 9.96–10.39 | 6 |
| Fecha + actor | 2,505 | 12.49 | 12.28–12.72 | 6 |
| Fecha + acción + resultado | 1,503 | 7.71 | 7.39–8.15 | 6 |
| Fecha + criticidad | 501 | 5.98 | 5.75–6.21 | 6 |
| Fecha + actor + acción + resultado + criticidad | 1,670 | 12.73 | 12.63–12.81 | 6 |
| Fecha + usuario afectado | 501 | 6.12 | 6.01–6.24 | 6 |
| Fecha + incidencia | 334 | 5.37 | 5.33–5.41 | 6 |

Detalle crítico con contexto: 12.27 ms en una ejecución. Pico del proceso de la sonda: 40 MiB, incluyendo bootstrap, preparación del esquema/fixtures y mediciones; no es memoria aislada por request. Dataset sintético revertido; no filas de prueba en desarrollo. La primera sonda aislada se corrigió para inicializar roles antes de medir; no se usan resultados de ejecuciones fallidas.

EXPLAIN se ejecutó sobre las consultas SQL **realmente emitidas** por el servicio, tanto página con sus dos LEFT JOIN de cardinalidad única como COUNT. Planes y SQL parametrizado conservados en evidencia privada. Resultado:

- Listado por fecha y acción/resultado: rango inverso sobre idx_registro_fecha; joins por PRIMARY de usuarios, sin duplicación.
- Actor y combinación: idx_registro_actor; ordenación acotada para la página. Criticidad: idx_registro_critica y ordenación.
- Usuario afectado e incidencia: soporte físico existente fk_registro_usuario_afectado/fk_registro_incidencia, confirmado mediante SHOW INDEX; no índices inventados.
- COUNT de rangos amplios y acción/resultado puede elegir table scan de las 10.000 filas; no carga ese historial en PHP. Otros conteos usan índices de actor/criticidad/referencia. Un scan de alta selectividad o un sort aislado no justifican un índice nuevo: media máxima conjunta 12.73 ms, seis consultas constantes y página acotada.

**0 migraciones, 0 índices adicionales.** Sin cuello de botella medido que justifique modificar el SQL oficial. Si Fase 13 evalúa otra escala, repetir EXPLAIN y mediciones antes de proponer un índice.

### Revisión manual de Fase 12.6

Instancia temporal loopback 127.0.0.1:8002, vinculada exclusivamente a espacio_u_testing, con sesiones privadas y CSRF real. Cuatro cuentas y recursos sintéticos; proveedor chatbot fake y ejecución Blockchain externa bloqueada, sin modificar la configuración productiva. Se cerró el servidor QA después de guardar evidencia; el servidor de desarrollo y pestañas del usuario permanecieron intactos.

| Pasos solicitados | Recorrido y resultado observado |
| --- | --- |
| 1–3 | Admin entra a /actividad; Admin sin actividad.ver y Encargado/Docente/Estudiante reciben pantalla 403. Revocación simulada en el harness privado y comprobada contra Gates reales; pruebas automatizadas también revocan el permiso en DB. |
| 4–6 | Alta manual mediante AdministrationService con credencial aleatoria solo en memoria; cuenta consultada en UI. Edición de nombre y deshabilitación desde formularios, confirmaciones y estado persistido correctos. El alta HTTP también está cubierta por la regresión de administración; no se presenta la invocación de servicio como un alta tecleada en navegador. |
| 7–9 | Fixture sin rol Docente permite crearlo desde UI; guardado de un permiso elegible, asignación y retiro al usuario sintético. Confirmaciones e historial conservados. |
| 10–13 | Crear aula, editar capacidad, pasar a BLOQUEADO y registrar bloqueo futuro desde UI; actividades separadas correctas, sin motivo libre en descripción de auditoría. |
| 14–17 | Alta de equipo y sensor, emisión QR y cierre de visualización sin copiar token, creación de versión de política. Catálogos y variantes restantes cubiertos por los 38 escenarios automatizados. |
| 18–21 | Reserva aprobada de fixture cancelada desde UI; préstamo pendiente rechazado; incidencia cerrada; preview y descarga PDF de dos equipos. Una RESERVA_CANCELADA, PRESTAMO_RECHAZADO (resultado EXITO), INCIDENCIA_CERRADA y REPORTE_EXPORTADO. Evidencias locales para las dos críticas del recorrido. |
| 22–24 | Acción+resultado+criticidad combinados, solo críticas, proceso scheduler:liberacion, limpiar filtros, vacío por ERROR y página 2 (26–50 de 54) conservando rango. |
| 25–28 | Detalle #4, nombres actuales e IDs históricos, enlaces autorizados y evidencia. Al retirar permisos de destino desaparecen enlaces/nombres y quedan referencias técnicas; consulta no dispara CU25. |
| 29–30 | Consulta al chatbot fake y abrir/marcar aviso de liberación. Conteo antes/después: 54→54; ninguna actividad nueva por esas lecturas/conversación. |
| 31–34 | Escritorio 1440×900 y 1366×768; tablet 768×1024; móvil 390×844 y detalle móvil. Sin desbordamiento de página; tabla conserva scroll propio. Tab enfoca summary, Enter lo abre; Tab desde región de tabla llega a Ver y desplaza horizontalmente 648 px. Foco visible, labels, texto de resultado/criticidad y captions. Consola revisada: sin warnings/errors. |

Capturas y observaciones privadas: .local/qa/phase126/ui-1440.png, ui-1366.png, ui-768.png, ui-390.png, ui-detail-mobile.png, ui-keyboard.png; PDF sintético y conteos antes/después en el mismo directorio ignorado. No se versionan sesiones, credenciales, datos ni capturas. La cobertura de estos recorridos es local al módulo y sus integraciones; no declara iniciada la QA integral Fase 13.

### Regresión y controles finales

**Regresión completa aprobada:** php artisan test --compact --no-ansi, **1.030 tests / 11.467 assertions / 944.91 s**, exit 0. Baseline 877/10.217 conservado; **153 casos adicionales** y 1.250 assertions más. MySQL espacio_u_testing, sin SQLite ni pruebas en desarrollo. Ocho clases Activity*Test nuevas, soporte ActivityTestCase y regresión de todos los módulos previos.

La primera ejecución detectó una regresión al asignar política inexistente (404 en lugar de validación). Se restauró la respuesta anterior antes de admitir auditoría; 48 pruebas dirigidas/170 assertions pasaron y se añadió el caso de ID no resuelto sin actividad. La suite completa se repitió con la corrección y terminó con el resultado anterior. Ningún test previo se eliminó ni se relajó para ocultar el fallo.

Pint --test --dirty pasó sobre los archivos finales antes del commit de implementación. npm run build pasó (889 módulos; 409 ms en la última ejecución), sin bibliotecas nuevas. Secret scan de worktree/index/diff/historial y enlaces documentales comprobados antes de publicar, sin hallazgos. Verificación de conservación: 94 archivos de pruebas previos/480 métodos test_* (877 casos con datasets); 217 textos normativos, 30 CU, 17 decisiones D, AC1–AC15 y CH1–CH12 intactos; hashes del Word/PDF originales idénticos. Los logs/evidencias privadas, incluida la regresión final, se examinan en memoria contra valores sensibles locales y patrones de claves: cero hallazgos, sin imprimir valores. Word/PDF no modificados; ninguna dependencia, lockfile o migration nueva.


Auditorías de esta fase: Composer 0 advisories/0 abandonados; npm frontend 0 y --omit=dev 0; blockchain 5 altas exclusivamente dev Hardhat/Zod bajo excepción temporal autorizada y --omit=dev 0. Sin cambios de dependencias. No afirmar cero global. [Advisory, versiones y controles compensatorios](BLOCKCHAIN_IMPLEMENTATION.md).

Smokes reales OpenAI (clave/cuota) y Sepolia (testETH) continúan pendientes e independientes; no claves ni fondos reales utilizados. No se amplía CU29 ni D-12. Fase 13 no iniciada; siguiente trabajo recomendado, solo con instrucción nueva: QA integral y endurecimiento del sistema completo, usando esta suite como baseline y manteniendo los smokes externos separados.

## Operación local y estado funcional

SecuritySeeder incremental aplicado al entorno oficial: 51→52 permisos; actividad.ver asociado solo a Administrador. Conservó los usuarios y el conteo de registros_actividad existentes; sin pruebas ni fixtures en desarrollo, sin backfill o modificación de .env. El permiso sigue siendo revocable y no se restauran concesiones anteriores. Compilación frontend disponible en public/build ignorado por Git.

RF25 y CU30 cuentan con implementación; con esta entrega queda implementado el catálogo de **30 casos de uso web en su alcance acordado**. Esto no certifica automáticamente todos los requisitos no funcionales de despliegue/carga, el cliente móvil ni la validación física/externa pendiente de fases previas. Los límites de esas fases permanecen documentados. Fase 13 sigue reservada para QA integral, seguridad, rendimiento y endurecimiento del sistema completo.

Rama autorizada: feature/phase-12.6-activity-audit; base main/origin/main ec3a064542e85e742c68cd9fca8a042c1778756e. Implementación y pruebas en b2c3e76 (feat: implement transactional activity audit and CU30). La documentación reúne los resultados aprobados en un commit separado. Publicación de la rama e integración mediante fast-forward, sin force push; los hashes definitivos y la igualdad de main/origin/main se confirman en el informe de cierre después de ejecutar y verificar esas operaciones.

Documentación de cierre: este informe nuevo y 21 documentos actualizados (README, diez documentos vinculantes y diez informes funcionales). Trazabilidad RF25 → CU30 → registros_actividad → recorder/productores → listado/detalle → pruebas T-A01–T-A40. Cero bloqueantes de esta entrega; Fase 13 no iniciada.
