# Políticas de liberación de reservas — Fase 8

**Estado vigente — Fase 10.5 documental (2026-10-26).** RF22/CU27 y RF23/CU28: [contrato de dashboard y reportes cerrado](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11), **0 bloqueantes documentales**. Fase 11 NO iniciada; CU30 NO IMPLEMENTADO. Fase 10 funcional se conserva, sin cambios de código, datos, permisos o dependencias. Los cierres anteriores mantienen su contexto histórico.

**Estado histórico funcional — Fase 10 (2026-10-26).** RF21/CU26 implementados y verificados: 17 reglas, avisos propios, primera lectura y recuperación de 24 h. [Informe y evidencia](NOTIFICATIONS_IMPLEMENTATION.md). CU30 general y Fase 11 no iniciados. Sepolia real sigue pendiente de testETH. Los apartados de cierres anteriores conservan su contexto histórico.

Fecha: 2026-10-24. RF18 → CU23 implementado bajo el [contrato D-08 congelado](DECISIONS.md#contrato-de-liberación-de-reservas--cierre-d-08-de-fase-75), sin reinterpretar sus 24 filas × dos modos. Workspace único y remoto oficial conservados. La implementación parte de `08b6b08bcdba423ec1d2b1a519da3b9d6c4f16cd`; rama `feature/phase-8-release-policies`. Este informe actualiza el estado de ejecución de las fotografías documentales anteriores, que conservan su alcance histórico.

## Esquema y dominio

Se reutilizan `politicas_liberacion` y `espacios_academicos.politica_liberacion_id`, sin recrearlas ni alterar migraciones anteriores. Tres migraciones incrementales nuevas:

| Archivo en database/migrations | Tabla / columnas |
| --- | --- |
| `2026_10_24_000026_create_evaluaciones_liberacion_table.php` | `evaluaciones_liberacion`: id, reserva_id, politica_id, fecha_hora, resultado, motivo |
| `2026_10_24_000027_create_evaluaciones_eventos_ocupacion_table.php` | `evaluaciones_eventos_ocupacion`: evaluacion_id, evento_id |
| `2026_10_24_000028_create_evaluaciones_validaciones_qr_table.php` | `evaluaciones_validaciones_qr`: evaluacion_id, validacion_id |

Diez columnas, seis FK con ON UPDATE CASCADE, DELETE CASCADE desde pivote hacia evaluación y DELETE RESTRICT en las otras cuatro relaciones; índices, PK compuestas, enum y VARCHAR(500) nullable físicos fieles al diccionario/SQL. El servicio exige motivo seguro no vacío. Instalación acumulada: **28 tablas académicas, 179 columnas y 54 FK**, además de la tabla técnica migrations. Tres tablas académicas posteriores siguen pendientes. Desarrollo recibió solamente migraciones incrementales; fresh/rollback se probaron en `espacio_u_testing` real y protegido.

`ModoPoliticaLiberacion` contiene ALERTA/AUTOMATICA; `ResultadoEvaluacionLiberacion` contiene MANTENER_POR_USO/NO_DETERMINABLE/ALERTAR/LIBERAR. `PoliticaLiberacion` relaciona espacios/evaluaciones; `EvaluacionLiberacion` relaciona reserva/política/eventos/validaciones; Reserva, EventoOcupacion y ValidacionQr tienen relación inversa con evaluaciones. No hacen falta modelos de pivote. Las factories de política/evaluación son sintéticas; las relaciones generan los pivotes de prueba.

La aplicación rechaza modificar/borrar evaluaciones y no expone escritura de pivotes históricos. Las reglas/identidad de políticas son inmutables mediante el modelo y servicios. Estas guardas de aplicación no sustituyen los privilegios y controles de acceso directo a MySQL.

## Gestión y autorización

`ReleasePolicyRules` valida nombre no vacío de hasta 120 caracteres, versión entera positiva, tolerancia entera no negativa, límites de INT, modos/booleanos y al menos una fuente requerida. IoT requerido exige antigüedad entera no negativa; IoT no requerido exige NULL. Cero es válido. `ReleasePolicyManagementService` crea nuevas filas/versiones y traduce colisiones UNIQUE, incluida concurrencia y collation, a error de validación.

Crear nueva versión puede copiar datos visibles; no cambia la anterior ni sus asignaciones. Solo `activa` es mutable; desactivar conserva historia. No existe borrado de políticas. Asignación admite versión activa/válida o NULL, siempre bajo guarda de compromisos: cualquier APROBADA con fin>T bloquea NULL→ID, ID→otro e ID→NULL, incluidas reservas futuras. Mismo ID es operación sin cambio. Reactivar requiere configuración válida y ausencia de compromisos en todos sus espacios. No se inventa una incidencia de mantenimiento para diagnosticar políticas.

| Permiso nuevo exacto | Administrador | Encargado de Laboratorio | Docente | Estudiante |
| --- | --- | --- | --- | --- |
| liberacion.politicas.ver | Sí | Sí | No | No |
| liberacion.politicas.gestionar | Sí | Sí | No | No |
| liberacion.evaluaciones.ver_administrativas | Sí | Sí | No | No |
| liberacion.evaluaciones.ver_propias | No | No | Sí | Sí |

Catálogo acumulado: **43 permisos**. Se requiere elegibilidad y permiso vigente en el mismo rol; no hay bypass del Administrador. El seeder conserva revocaciones previas. Consultas propias aplican `reserva.solicitante_id = usuario autenticado` en servidor, incluidas URLs directas. Su DTO omite actores, identificadores e historia técnica IoT y toda evidencia ajena. Administración muestra actor QR autorizado y lecturas mínimas; nunca token/hash/firma/HMAC. Auth, sesión vigente, CSRF y reautorización de escrituras se conservan.

## CU23, ventanas y matriz

`ReleaseEvaluationService` usa una transacción nueva por reserva; rechaza llamadas anidadas. Solo APROBADA con política asignada, `inicio + tolerancia_min <= T < fin` y ventana útil. T proviene del servidor en America/La_Paz, precisión de segundos, después de adquirir todos los locks. La aritmética acota INT_MAX sin desbordar fechas. NULL omite; una política inactiva/inválida elegible produce como máximo un diagnóstico NO_DETERMINABLE sin consultar Q/E. Los errores de consulta/contexto revierten; no equivalen a evidencia ausente.

Q contiene todas las validaciones de la misma reserva, recurso esperado y USO_ESPACIO en [inicio,T]. VALIDA es uso positivo aun siendo QR opcional; INVALIDA se conserva como contexto y no demuestra no uso. Rotar/desactivar el código después no reescribe la validación histórica.

E contiene todos los eventos de todos los sensores HABILITADO actualmente asociados al espacio, sin filtrar por texto de tipo de sensor. Deben conservar ese espacio histórico, fecha_evento en [max(inicio,T−antigüedad),T] y fecha_recepcion<=T. IoT requerido usa los minutos de la política; la evidencia opcional usa 300 segundos fijos, separados de la frescura de CU22. No se toma solo la última lectura. No se consulta al dispositivo ni se espera evidencia en red.

PRESENCIA es positiva con cualquier valor admitido por D-10; OCUPACION >0 es positiva, 0 es negativa explícita, NULL/negativo son insuficientes. Negativa completa exige inventario no vacío, al menos un cero por sensor y todas las lecturas relevantes compatibles con cero. Sensor sin lectura/ambiguo impide unanimidad. Positivo+cero entre sensores/tiempos, o valores no equivalentes de mismo sensor/tipo/instante, son contradicción; QR válido+negativa completa también. La contradicción prevalece y conserva la reserva. Se consultan fuentes opcionales para respetar el veto de uso.

La [matriz normativa](DECISIONS.md#matriz-completa-de-decisión-de-d-08) se prueba con oráculo literal de **48 casos**, tres configuraciones válidas de fuentes, dos estados QR, cuatro clases IoT y dos modos. Resumen operativo:

| Condición | Resultado / efecto |
| --- | --- |
| Contradicción IoT, o QR válido + IoT negativo completo | NO_DETERMINABLE; APROBADA |
| Sin contradicción, QR válido o IoT positivo | MANTENER_POR_USO; APROBADA |
| Sin uso, IoT requerido insuficiente | NO_DETERMINABLE; APROBADA |
| Sin QR válido y negativo completo, o falta QR requerido sin veto con IoT no requerido | ALERTAR en ALERTA; LIBERAR en AUTOMATICA |

ALERTAR conserva APROBADA y muestra atención en UI; no genera notificación. LIBERAR confirma en una sola transacción evaluación, pivotes exactos Q/E y UPDATE condicional APROBADA→LIBERADA que debe afectar exactamente una fila. Solo cambia `reservas.estado`. No cambia estado del espacio, fechas/responsables de resolución/cancelación ni historia. Motivos comprensibles de hasta 500 caracteres, sin secretos ni payloads.

`SpaceAvailabilityService` existente ya excluye únicamente reservas bloqueantes según su contrato: LIBERADA deja de bloquear el intervalo. Bloqueos, mantenimiento, incidencias, otras reservas y estado operativo siguen aplicándose. No se agregó otra lógica de disponibilidad ni se modificó ese servicio.

## Idempotencia y concurrencia

La pareja reserva/política con cualquier MANTENER_POR_USO, ALERTAR o LIBERAR previo es terminal para CU23, incluso si llega nueva evidencia. Tras NO_DETERMINABLE solo se reevalúa cuando Q/E actual contiene un ID no enlazado en ninguna evaluación previa de la pareja y fecha QR>=última evaluación o fecha_evento/recepcion IoT>=última evaluación. Empates del mismo segundo cuentan; MAX(id) no determina orden de commit. La nueva evaluación enlaza todo Q/E considerado, no solo la novedad. Envejecer, cambiar inventario o retroceder reloj no produce por sí solo otra fila. Un evento antes futuro puede entrar cuando sea temporalmente válido; historia tardía fuera de W no cuenta. Los pivotes anteriores nunca se actualizan.

Orden CU23: reserva X NOWAIT → espacio X NOWAIT → política S NOWAIT → sensores habilitados S NOWAIT por id, con `idx_sensor_espacio`. Primera lectura consistente RR después de los locks. No toma coordinador global RBAC. Si cambió T, refresca corte/ventanas antes de persistir; si cambia durante escritura o cruza fin, rollback completo. Error 3572/1213/1205: hasta tres intentos completos, pausas de 20/40 ms fuera de transacción y, al agotarlos, diferimiento seguro. No SKIP LOCKED ni inventario parcial.

Los caminos humanos conservan RBAC y sus órdenes. Asignación toma espacio X y consulta compromisos actuales con lectura compartida antes de política S; reactivación toma espacios X por id, consulta compromisos y después política X. Así no adquieren una reserva después de retener política ni usan un snapshot anterior a la espera. Desactivación toma política X. CU23 retrocede ante cualquier inversión potencial con reservas/QR/IoT existentes, cuyos servicios/órdenes no se modificaron.

Pruebas con procesos/conexiones MySQL independientes cubren ambos órdenes de QR/IoT y cambios de inventario, asignación/aprobación, desactivación/evaluación y reactivación/aprobación. Cancelación mantiene D-05: solo antes de inicio. Cancelación legal que confirma primero impide evaluar; liberación primero impide cancelar; una cancelación que esperó hasta cruzar inicio también falla. Nunca se amplió la ventana para fabricar una carrera.

## Comando y scheduler

```powershell
. .\scripts\dev-environment.ps1
php artisan reservas:evaluar-liberacion
php artisan schedule:list
.\scripts\release-scheduler.ps1 -Action start
.\scripts\release-scheduler.ps1 -Action status
# Para detener solamente el scheduler de este proyecto:
.\scripts\release-scheduler.ps1 -Action stop
```

Laravel Scheduler invoca el comando cada minuto por defecto; `RELEASE_SCHEDULE` y `RELEASE_BATCH_SIZE` (100) son configuración técnica sin secretos. El lote se acota a 1–1000. Frecuencia no equivale a tolerancia. `ReleaseScanService` selecciona APROBADA, inicio<=corte<fin y espacio con política, paginando por clave; CU23 revalida cada candidato. Fallo/conflicto de una reserva no bloquea las demás. Contadores/logs son seguros; errores reales devuelven código 1 y diferidos no son éxito parcial. `withoutOverlapping` optimiza, pero la corrección depende de locks/idempotencia MySQL.

El script local ejecuta `schedule:work` oculto, verifica PID/executable/comando antes de detenerlo y guarda PID/logs solo en `.local/release-scheduler`, ignorado. Se verificaron ejecuciones reales por minuto. **El proceso local debe reiniciarse después de apagar el equipo o detenerlo**; no instala una tarea global ni servicio Windows. En despliegue futuro se necesitará invocación externa de `schedule:run` cada minuto, con supervisión. No requiere login ni usuario/rol Sistema; no depende de visitar CU22 ni de recibir un evento.

## Interfaces

Seis páginas Vue/Inertia: listado, creación de versión, detalle, asignaciones, listado y detalle de evaluaciones. Nueve rutas protegidas. Políticas/evaluaciones paginadas; filtros por versión/reserva/resultado; evidencia QR/IoT paginada separadamente. No hay botón/endpoint web de liberar manualmente ni formulario que sobrescriba reglas. Badges incluyen texto; tabla móvil con desplazamiento y foco; labels, errores y diálogos conservan componentes existentes.

## Verificación y trazabilidad de aceptación

Ejecución final completa: **537 pruebas aprobadas, 7400 aserciones, 520,27 s**; se añaden **166 casos** a la base de 371. Se corrigieron dos expectativas antiguas del catálogo acumulado de permisos detectadas por la primera regresión; la segunda regresión pasó completa. No se eliminó ni debilitó ningún caso anterior.

| Verificación | Resultado |
| --- | --- |
| `php artisan test --compact --log-junit work/phase8/tests.xml` | 537 passed / 7400 assertions / 520,27 s; MySQL real aislado |
| `php vendor/bin/pint --test` | Passed |
| `npm run build` | Correcto; 870 módulos, 378 ms en el último build |
| `composer audit` | Sin avisos de vulnerabilidad |
| `npm audit --audit-level=low` | 0 vulnerabilidades |
| Documentación | 266 enlaces locales válidos; 217 textos normativos, cuatro secciones de diccionario SQL y contrato/aceptación D-08 conservados |
| Migraciones / originales | 25 migraciones previas intactas; SHA-256 Word/PDF sin cambios |

Los 371 tests anteriores se conservan; se actualizaron únicamente expectativas acumuladas de permisos/FK/tablas. Las pruebas nuevas residen en diez suites `tests/Feature/Release*Test.php`, con `ReleaseTestCase`, `ReleaseConcurrencyTestCase` y worker sintético en `tests/Support/concurrent-release.php`. No se usa SQLite ni fresh en desarrollo. El EXPLAIN tradicional de consultas con 500 reservas históricas confirma índices oficiales; esto no constituye certificación de carga de producción.

| Criterios congelados | Evidencia ejecutable |
| --- | --- |
| T-L01 | ReleaseSchemaTest: columnas, FK/acciones, PK, índices, enum, longitud, M:N, rollback/reaplicación |
| T-L02–05 | ReleasePolicyTest; ReleasePolicyConcurrencyTest; ReleaseEvidenceTest: configuración, versiones, guardas y diagnóstico |
| T-L06–08 | ReleaseEvidenceTest: estados, límites, contexto QR, ausencia y fallos |
| T-L09–14 | ReleaseMatrixTest y ReleaseEvidenceTest: 48 resultados, veto, ventanas, sensores y contradicciones |
| T-L15–17 | ReleaseMatrixTest, ReleaseSchemaTest, ReleaseAtomicityTest: acciones/pivotes, historial y disponibilidad |
| T-L18–22 | ReleaseIdempotenceTest: terminales, novedad, empates, IDs, futuros, relojes y diagnósticos |
| T-L23–24 | ReleaseConcurrencyTest: dos primeras evaluaciones y dos reevaluaciones de misma novedad |
| T-L25–28 | ReleaseConcurrencyTest y ReleasePolicyConcurrencyTest: cancelación, QR, IoT/inventario, política/compromisos |
| T-L29–31 | ReleaseIdempotenceTest, ReleaseConcurrencyTest, ReleaseAtomicityTest: snapshot, relecturas, tiempo, NOWAIT/retry y rollback |
| T-L32 | ReleaseSchedulerTest: candidatos/lotes, aislamiento de fallos/locks, planificación, comando e índices |
| T-L33–34 | ReleaseHttpTest, ReleasePolicyTest y ReleaseConcurrencyTest: permisos, sesión/CSRF, revocación y scope propio |
| T-L35 | ReleaseSchedulerTest/ReleaseHttpTest y revisión de diff: ausencia de módulos/rutas/usuario Sistema anticipados |
| T-L36 | Revisión local descrita abajo |

## Revisión manual local

Datos sintéticos identificados Fase 8; nueve reservas creadas/aprobadas mediante servicios reales, validación QR por servicio real y eventos IoT sintéticos persistidos. El reloj de preparación solo afecta al helper; la aplicación/scheduler usan hora real. No se atribuye esta revisión a hardware físico. Las carreras se reproducen con barreras/procesos MySQL, no con clics simultáneos imprecisos.

| Puntos de la petición | Comprobación / resultado |
| --- | --- |
| 1–4 | UI creó versión QR ALERTA, asignó espacio; reserva aprobada y QR válido produjeron MANTENER_POR_USO |
| 5–7 | Ausencia QR produjo ALERTAR y, en versión AUTOMATICA, LIBERAR con estado LIBERADA visible |
| 8–12 | IoT positivo mantuvo; cero liberó; NULL produjo NO_DETERMINABLE; dos sensores en cero liberaron |
| 13–14 | QR válido+IoT cero conservó por contradicción; QR+IoT sin QR/cero produjo ALERTAR |
| 15–16 | Servicio central incluyó espacio tras liberar y lo excluyó con bloqueo/incidencia sintéticos; automatizados cubren también estado operativo |
| 17–18 | UI rechazó cambio con compromiso, creó nueva versión sin sobrescribir y rechazó reactivación; prueba MySQL cubre además compromiso futuro |
| 19–21 | Sesiones Docente/Estudiante mostraron únicamente cinco/cuatro evaluaciones propias; URL de evaluación ajena respondió 404; sin datos técnicos en DTO propio |
| 22–24 | Scheduler real por minuto y comando repetido sin duplicados; carreras reales de cancelación/liberación con guardas temporales |
| 25–27 | Móvil 390×844, tablet 768×1024, escritorio 1440×1000; sin desbordamiento de página; scroll interno de tabla con flecha, salto a main, diálogo/Escape/foco, labels y badges con texto. Consola sin errores de aplicación; 404 esperado al probar acceso ajeno |

Se conservan las nueve evaluaciones sintéticas y sus relaciones para inspección; al cerrar QA se deshabilitan usuarios/sensores/políticas de prueba, revocan sesiones y retiran credenciales temporales del `.env` local. No se borra historia. Servidor y scheduler local quedan disponibles. Datos de ejemplo no constituyen políticas institucionales listas para producción.

## Privacidad, publicación y límites

Escaneo de 389 archivos publicables y 106 archivos locales generados, además de diff/index/historial de la fase: **sin hallazgos pendientes**. Se contrastaron valores privados de `.env`/`.env.testing`, hashes QR/sesión/usuarios, IOT_MASTER_SECRET, credenciales derivadas y patrones sensibles, sin imprimirlos. Logs, fixtures, build y materiales QA se revisaron. El primer pase señaló exclusivamente el correo sintético dentro del helper privado; el helper y accesos temporales se retiraron y el segundo pase quedó limpio. `.env`/`.env.testing` siguen ignorados; `.env.example` no contiene secretos. Los informes/helper temporales de esta fase se retiran tras registrar los resultados aquí.

Publicación autorizada: commits funcional, de pruebas y documental en `feature/phase-8-release-policies`, push de la rama y posterior integración fast-forward a main, sin force push. Los hashes y comprobación final de igualdad remota se informan al completar esa operación; este texto no presume su éxito antes de ejecutarla.

No se modifican los originales Word/PDF, los 217 textos normativos, SQL congelado ni migraciones anteriores. Los servicios centrales de disponibilidad, reservas, QR e ingestión IoT y lockfiles se preservan. No hay dependencias nuevas. Git conserva el historial; sin repositorio paralelo, ZIP, copia ni force push.

Blockchain/CU24/CU25, canonicalización y hashing de operaciones críticas **no implementados**. Notificaciones funcionales y tabla notificaciones **no implementadas** (Fase 10). CU30/registros_actividad, chatbot, dashboard final, aplicación móvil y APROBADA→FINALIZADA por tiempo **no implementados**. Evaluaciones son historia funcional de CU23. No se promete observar actividad física sin evidencia confirmada antes del corte transaccional.

**Estado al cierre de Fase 8:** Fase 9 no iniciada y D-12 pendiente. La posterior Fase 8.5 cerró D-12 y excluyó LIBERADA expresamente, como se detalla a continuación; la implementación de Fase 9 sigue requiriendo nueva instrucción.

## Exclusión Blockchain cerrada en Fase 8.5

[D-12 y Readiness for Phase 9](DECISIONS.md#readiness-for-phase-9) quedaron cerrados documentalmente el 2026-10-25. **APROBADA→LIBERADA/CU23 no es una acción crítica Blockchain del alcance inicial.** CU24 extiende únicamente CU09/CU10/CU17/CU18; evaluaciones_liberacion, eventos IoT y validaciones QR mantienen la evidencia completa de CU23. No se añade actividad crítica, hash, envío ni hook Blockchain a la liberación.

La aprobación previa conserva su evidencia y sus campos históricos; CU25 podrá verificarla después de LIBERADA sin usar estado actual en el canónico. Incorporar liberación a la cadena exigiría una nueva decisión/versionado. D-08, políticas, scheduler, locks y criterios T-L se conservan; T-B10/T-B29 verificarán esta exclusión y la historia en Fase 9. Las cifras de pruebas de Fase 8 son históricas; no se repitieron ni se modificó código durante Fase 8.5.

## Integración RF21 prevista — cierre de Fase 9.5

La matriz, ventanas, evidencia, locks/NOWAIT e idempotencia D-08 de CU23 permanecen intactos. [N3/N4](DECISIONS.md#notification-contract--readiness-for-phase-10) fija dos productores futuros: evaluación nueva ALERTAR → RESERVA_ALERTADA_LIBERACION; evaluación nueva LIBERAR y transición real APROBADA→LIBERADA → RESERVA_LIBERADA. Ambas actividades EXITO/critica=FALSE, actor_id NULL, proceso_responsable scheduler:liberacion, contexto reserva/espacio/solicitante; misma transacción que evaluación y pivotes. Referencia humana segura de evaluación/política en descripcion, sin columna nueva ni snapshot de destinatarios.

Un ciclo terminal/reintento que omite evaluación no genera actividad ni aviso adicionales. MANTENER_POR_USO y NO_DETERMINABLE no notifican en el alcance inicial. ALERTAR no cambia APROBADA ni comunica liberación; LIBERADA sí informa del cambio confirmado. Destinatarios de ambas: titular y usuarios activos con liberacion.evaluaciones.ver_administrativas, por permiso efectivo. Tipo LIBERACION, no evidencia Blockchain crítica.

Las filas notificaciones se generan después del commit, sin locks CU23, por el servicio común e independientemente de Blockchain. Fallo de aviso no deshace evaluación/liberación. Texto histórico con fecha del evento; recuperación N6 de 24 h y sin backfill de evaluaciones previas a habilitación RF21. Enlace a evaluación/reserva exige permiso actual y no filtra evidencias QR/IoT. T-N36–T-N38 probarán integración/concurrencia; no se implementa ahora ningún hook ni se modifica el scheduler vigente.

## Extensión RF21 implementada — Fase 10

ALERTAR/LIBERAR producen actividad no crítica en la misma transacción de evaluación/transición/pivotes. MANTENER_POR_USO/NO_DETERMINABLE no producen avisos. Referencia CU23 estructurada en descripcion sin nueva columna/FK; matriz y D-08 intactas. [Implementación y evidencia](NOTIFICATIONS_IMPLEMENTATION.md).

## Consumo documental por CU27/CU28 — Fase 10.5

[DR4/DR7](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11) preserva evaluación, matriz y scheduler. Reservas liberadas del periodo = COUNT de reservas actualmente LIBERADA con EXISTS evaluación LIBERAR cuya fecha_hora esté en [A,B). No usar resuelta_en/cancelada_en ni eventos de notificación como sustitutos; no multiplicar por pivotes o reevaluaciones.

La primera entrega de dashboard tiene diez tarjetas y excluye la ampliación opcional de alertas. ALERTAR no significa pendiente ni libera; una extensión necesitaría definición explícita del periodo desde evaluaciones_liberacion. El resumen operacional puede agrupar RESERVA_ALERTADA_LIBERACION/RESERVA_LIBERADA ya registradas con permisos de reservas y evaluaciones administrativas; no reconstruye CU23 ni expone evidencia completa. Fase 10.5 no ejecuta evaluaciones ni implementa Fase 11.

## Integración con Fase 11

[Dashboard y reportes](DASHBOARD_REPORTING_IMPLEMENTATION.md) reutilizan las reglas, consultas y scopes de este dominio. No modifican sus estados, decisiones o productores. REPORTE_EXPORTADO no se añade a la whitelist de notificaciones ni a eventos críticos Blockchain. CU30 sigue NO IMPLEMENTADO; las limitaciones históricas de este informe conservan su alcance.
