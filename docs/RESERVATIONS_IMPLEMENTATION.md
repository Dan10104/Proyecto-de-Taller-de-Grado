# Reservas de espacios académicos — Fase 4

**Estado vigente — Fase 13 completada y validada (2026-10-26).** QA integral de CU01–CU30 y RF1–RF25 web; RF26 móvil diferido. [Informe de seguridad, rendimiento y pruebas](PHASE_13_SYSTEM_VALIDATION.md) y [matriz final por CU](FINAL_USE_CASE_VALIDATION.md). Los estados de fases anteriores que siguen son históricos. OpenAI/Sepolia pendientes independientes; excepción dev Hardhat/Zod explícita. Fase 14 NO iniciada.

**Estado vigente — Fase 10.5 documental (2026-10-26).** RF22/CU27 y RF23/CU28: [contrato de dashboard y reportes cerrado](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11), **0 bloqueantes documentales**. Fase 11 NO iniciada; CU30 NO IMPLEMENTADO. Fase 10 funcional se conserva, sin cambios de código, datos, permisos o dependencias. Los cierres anteriores mantienen su contexto histórico.

**Estado histórico funcional — Fase 10 (2026-10-26).** RF21/CU26 implementados y verificados: 17 reglas, avisos propios, primera lectura y recuperación de 24 h. [Informe y evidencia](NOTIFICATIONS_IMPLEMENTATION.md). CU30 general y Fase 11 no iniciados. Sepolia real sigue pendiente de testETH. Los apartados de cierres anteriores conservan su contexto histórico.

**Estado vigente — Fase 8 implementada (2026-10-24).** CU23 incorpora APROBADA→LIBERADA atómica con evaluación y pivotes. Reserva es lock coordinador; D-05 y ReservationService no cambian. Cancelación conserva inicio exclusivo y las carreras MySQL confirman una sola transición válida. FINALIZADA por tiempo permanece fuera. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

**Continuidad documental de Fase 7.5:** [D-08 cerrada para Fase 8](DECISIONS.md#readiness-for-phase-8). CU23 futuro solo evaluará APROBADA desde inicio+tolerancia y antes de fin. LIBERAR confirmará evaluación/pivotes/transición en un commit. D-05 conserva cancelación estrictamente antes de inicio y la finalización natural diferida; los órdenes de lock actuales se conservan mediante el protocolo CU23 con NOWAIT. Sin cambios funcionales ahora.

Fecha: 2026-10-23. Implementación del núcleo autorizado de CU07–CU10 y ampliación de CU06. D-05 permanece **RESUELTA PARA FASE 4**; no se reinterpreta. Este informe conserva la evidencia histórica de Fase 4; [Fase 5 ya implementada](EQUIPMENT_LOANS_IMPLEMENTATION.md).

CU06: **FUNCIONALMENTE COMPLETO PARA DISPONIBILIDAD OPERATIVA; ENRIQUECIMIENTO IoT DIFERIDO A FASE 7.**

## Fuentes contrastadas

Word oficial y `Fase2-grupo21.pdf`: RF5–7 (PDF 117, 119, 121), CU06–CU10 (183–190), SQL de reservas (280–282), diagramas de análisis (231–234), modelo de clases (249) y secuencia de solicitud (301). El SQL Word/PDF coincide tras normalizar espacios y encabezados. Se conservaron las huellas de [DECISIONS.md](DECISIONS.md). Los originales no se publican. El contrato de Fase 3.5 fija los aspectos de implementación que el informe no precisa.

## Esquema y dominio

Una sola migración nueva: `2026_10_23_000015_create_reservas_table.php`. Ninguna migración anterior cambia. Materializa exactamente las 15 columnas oficiales, PK, `uq_reservas_codigo`, cuatro FK con UPDATE CASCADE / DELETE RESTRICT, `chk_reserva_fechas` y los tres índices oficiales. No agrega timestamps Laravel, historial auxiliar ni tabla de locks. El despliegue local fue incremental con `migrate`; `migrate:fresh` se ejecuta exclusivamente en testing protegido.

`Reserva` usa `timestamps=false`, asignación masiva cerrada y fechas inmutables; relaciones `espacio`, `solicitante`, `resueltaPor`, `canceladaPor` y `EspacioAcademico.reservas`. El enum PHP y MySQL contienen exactamente PENDIENTE, APROBADA, RECHAZADA, CANCELADA, LIBERADA y FINALIZADA.

El evento de creación del modelo es el único generador de `RES-` + UUID v4: 40 caracteres ASCII, independiente del autoincremental, protegido por UNIQUE. El evento de actualización impide cambiarlo. Los requests prohíben código, estado, actores y fechas de decisión aportados por cliente. El servicio selecciona campos expresamente y atribuye actor/hora en servidor. Una colisión del índice de código provoca hasta tres intentos; cualquier otro error conserva su tratamiento, sin regeneraciones indiscriminadas.

## Permisos y alcance

| Permiso | Administrador | Encargado de Laboratorio | Docente | Estudiante |
| --- | --- | --- | --- | --- |
| reservas.ver_propias | Sí | Sí | Sí | Sí |
| reservas.ver_administrativas | Sí | Sí | No | No |
| reservas.solicitar | No | No | Sí | Sí |
| reservas.resolver | Sí | Sí | No | No |
| reservas.cancelar_propias | Sí | Sí | Sí | Sí |
| reservas.cancelar_administrativas | Sí | Sí | No | No |

Estos seis permisos amplían el catálogo total a 21; cada rol recibe respectivamente 20, 9, 4 y 4 permisos incluyendo seguridad y espacios. La asignación debe estar vigente y el permiso debe provenir de ese mismo rol elegible. Un Administrador no adquiere solicitud por privilegio implícito; podría obtenerla mediante un rol solicitante vigente. `SecuritySeeder` es idempotente, incorpora los nuevos códigos y conserva revocaciones posteriores. No siembra reservas ni cuentas.

`ReservaPolicy` gobierna lectura, solicitud, resolución y cancelación. El scope de `ReservationQueryService` se impone antes de filtros, opciones, conteos y paginación. El usuario con consulta propia no obtiene solicitantes ajenos mediante query params. Administrador/Encargado usan el permiso administrativo; el alcance fino de D-04 sigue diferido, sin inventar asignaciones de recursos.

## Flujos implementados

- CU07: disponibilidad → espacio/periodo conservados → propósito obligatorio, normalizado, hasta 500 caracteres → revalidación dentro de transacción → PENDIENTE → confirmación. Dos pendientes pueden coexistir. Fallo o conflicto no deja fila parcial.
- CU08: listas propias/administrativas, bandeja de pendientes, fecha local, estado, espacio y solicitante solo autorizado; 15 filas por página. Relaciones precargadas; DTO sin correos, hashes, sesiones ni usuarios completos. El detalle muestra propósito, periodo y seguimiento derivado de los campos oficiales de la reserva.
- CU09: solo PENDIENTE permite aprobar/rechazar. Aprobar revalida disponibilidad, registra responsable/fecha y deja motivo_rechazo NULL. Conflicto conserva PENDIENTE. Rechazo explícito admite motivo opcional hasta 500 caracteres.
- CU10: titular con permiso propio o gestor con permiso administrativo, estado PENDIENTE/APROBADA y hora confiable estrictamente anterior al inicio. Motivo opcional hasta 500; vacío a NULL. Conserva resolución anterior. No elimina bloqueos ni fuerza el estado del espacio.

Transiciones únicas: PENDIENTE→APROBADA, PENDIENTE→RECHAZADA, PENDIENTE→CANCELADA y APROBADA→CANCELADA. No existe edición libre de enum, endpoint de liberación/finalización ni scheduler.

Las rutas web están protegidas por sesión y autorización. `ReservationRequest`, `ReservationActionRequest` y `ReservationFilterRequest` validan entrada; `ReservationService` repite las guardas críticas dentro de la transacción. Un conflicto web vuelve al formulario con error accesible; una petición JSON autenticada recibe 409. Permisos insuficientes producen 403 seguro; datos SQL no se exponen. Los únicos avisos actuales son mensajes de la request confirmada.

## Disponibilidad y concurrencia

Se extiende **SpaceAvailabilityService**. CU06, CU07 y aprobación de CU09 comparten los predicados de estado operativo, filtros, bloqueo activo y APROBADA solapada. No hay servicio paralelo ni decisiones de disponibilidad en controladores o Vue. El intervalo es `[inicio, fin)`, `fin > inicio`, zona America/La_Paz; solapa si `existente.inicio < solicitado.fin AND existente.fin > solicitado.inicio`. Horarios contiguos son válidos.

Solo APROBADA bloquea. PENDIENTE/RECHAZADA/CANCELADA/LIBERADA/FINALIZADA no excluyen. Una APROBADA pasada conserva su estado y no impide periodos futuros no solapados. CU06 consulta con NOT EXISTS en BD; la revalidación de CU07/CU09 usa lecturas actuales bajo lock. Un FOR UPDATE exterior no se considera suficiente para hacer actuales las subconsultas NOT EXISTS.

Orden de coordinación de todos los cambios: **fila Administrador de RBAC existente → espacio por PK → reserva por PK**, cuando ya existe. No se añade un lock global nuevo. La fila RBAC existente serializa también acciones entre espacios distintos: es un límite conocido de concurrencia del diseño actual, conservado deliberadamente por D-05. El espacio sigue siendo la fila coordinadora explícita de exclusividad.

Dentro de la transacción: reautorizar actor vigente, adquirir locks, releer reserva, comprobar transición y consultar estado/bloqueos/APROBADA mediante lecturas FOR UPDATE actuales. Actualizar estado y campos asociados con condición sobre estado anterior; exigir una fila modificada. La hora de cancelación se lee después de esperar locks y en cada reintento. No hay llamadas externas dentro de los locks. Laravel permite tres intentos ante deadlock; agotamiento o espera vencida se convierten en conflicto controlado, sin éxito parcial.

Los nueve tests de `ReservationConcurrencyTest` arrancan procesos PHP independientes con conexión MySQL real, barreras de archivos privadas y plazos acotados. Verifican conexiones distintas y nivel transaccional cero al terminar. Prueban creación simultánea, aprobaciones solapadas/adyacentes, doble cancelación, aprobación/cancelación, aprobación/rechazo, espera del espacio con lectura reciente, reloj después de espera y revocación de permiso mientras espera coordinación RBAC.

La prueba específica del espacio retiene **solo** esa fila en la conexión padre; el hijo alcanza el intento de FOR UPDATE después de establecer su snapshot RR por la lectura ordinaria del actor. El padre confirma otra APROBADA, libera el espacio y el hijo debe detectar el nuevo conflicto y conservar PENDIENTE. Así la evidencia no depende únicamente de la serialización del lock RBAC. El avance del reloj en la prueba temporal es instrumentación del worker de tests, no una opción de producción ni tiempo enviado por navegador.

## Evidencia automática y rendimiento

`php artisan test`: **197 pruebas aprobadas, 2321 aserciones, 121,38 segundos, 0 fallos, 0 errores y 0 omitidas**. Ejecución completa en serie sobre MySQL aislado `espacio_u_testing`, después de las correcciones y con la regresión anterior conservada.

Se conservan las 127 pruebas de Fase 3; se añaden 70 casos ejecutados. Las expectativas previas que afirmaban ausencia de reservas o un catálogo anterior se actualizan al alcance nuevo; ninguna prueba fue eliminada. Los tests de seguridad ahora distinguen catálogo total de catálogo elegible para Administrador, que no incluye solicitar reservas.

| Archivo de prueba | Evidencia |
| --- | --- |
| ReservationSchemaTest | 15 columnas, tipos, NULL/default, enum, índices, FK y RESTRICT, CHECK/UNIQUE, guards, código y relaciones |
| ReservationRequestTest | Roles, solicitudes coexistentes, entradas manipuladas, scopes, filtros, detalle seguro, seeder, 15 filas/página y consultas constantes |
| ReservationLifecycleTest | Aprobación/rechazo/cancelación, revalidación ante cambios CU05, estados inválidos, frontera temporal, motivos, historia, locks, rollback de insert/update y colisiones acotadas |
| ReservationAvailabilityTest | Seis estados, solapamientos/adyacencia, medianoche, consulta por día local, periodos pasados/otro espacio, NOT EXISTS e índice oficial |
| ReservationConcurrencyTest + Support/concurrent-reservations.php | Nueve escenarios multiproceso reales sobre espacio_u_testing |

Correspondencia de T-R01–17: T-R01 Request/Concurrency; T-R02–03 Lifecycle; T-R04–05 Concurrency; T-R06 Availability/Request/Schema; T-R07 Lifecycle + regresión CU05; T-R08–10 Lifecycle/Concurrency; T-R11 Request/Lifecycle/Concurrency + regresión de seguridad; T-R12 Request; T-R13 Concurrency; T-R14 Lifecycle; T-R15 Lifecycle; T-R16 Availability; T-R17 Request/Availability.

La prueba de 501 filas confirma que EXPLAIN FORMAT=TRADITIONAL elige `idx_reserva_espacio_periodo` para el conflicto por espacio/periodo. El cálculo de disponibilidad no hidrata reservas: se resuelve con NOT EXISTS. Listar 1 o 15 reservas mantiene el mismo número de consultas (máximo 7 en la prueba del servicio con autorización y relaciones).

Ensayo local adicional, una transacción de testing revertida al terminar: 500 solicitudes dentro de un mismo periodo, diez consultas por escenario. Con todas PENDIENTE: 0,361–2,669 ms; con una convertida a APROBADA: 0,364–0,521 ms y el espacio queda excluido. Son tiempos del servicio/BD local, no una certificación de latencia extremo a extremo ni de carga institucional; las pruebas integrales de Fase 13 siguen previstas.

Pint: aprobado. `npm run build`: 594 módulos, sin errores. `composer audit`: sin avisos de vulnerabilidad; `npm audit`: 0 vulnerabilidades. No se instalaron nuevas dependencias. Los procesos del proyecto y MySQL aislado permanecen activos; MySQL80/MySQL97 no se modificaron.

## Revisión manual de interfaz

Revisión real en el navegador integrado, 2026-10-23. Cinco reservas sintéticas sobre el espacio de demostración existente, 18/02/2027 08:00–09:00. Tres cuentas temporales (Docente, Estudiante y Encargado), con credenciales generadas únicamente en .env local; al terminar quedan INACTIVAS, sesiones revocadas y credenciales temporales retiradas. Se conservan cinco reservas históricas: cuatro CANCELADA y una RECHAZADA, ninguna ocupación pendiente/aprobada de la prueba.

| Pasos solicitados | Resultado observado |
| --- | --- |
| 1–5: Docente, disponibilidad, solicitar, ver propia, cancelar | Periodo y espacio conservados; confirmación PENDIENTE; cancelación sin motivo aceptada |
| 6–7: Estudiante, repetir | Consulta propia vacía frente a las del Docente; solicitud y cancelación con motivo correctas |
| 8–12: pendientes solapadas, Administrador, aprobar y provocar conflicto | Primera APROBADA; segunda muestra indisponibilidad y sigue PENDIENTE |
| 13–16: rechazo, disponibilidad, cancelar APROBADA futura, restauración | Rechazo explícito con motivo; 0 espacios mientras APROBADA y 1 después de cancelar; resolución previa conservada |
| 17–19: Encargado, permisos y acceso indebido | Consulta administrativa, aprobación y cancelación correctas; solicitar como Encargado y detalle ajeno como Estudiante devuelven 403 |
| 20–22: responsive, teclado y consola | 1440×1000, 768×1024 y 390×844; filtros/listado/formulario/detalle/diálogos; Escape, Tab, Enter, foco inicial en Volver y retorno al título; consola sin errores/advertencias |

Se corrigió el desborde móvil de texto accesible posicionado en la tabla, estableciendo su contenedor relativo. La tabla conserva desplazamiento horizontal interno y foco de teclado; no hay desborde global. Se usan labels, errores anunciados y dialog nativo, sin window.confirm. La captura de cierre queda local e ignorada en `.local/verification/phase4/reservas.png`; no publica credenciales ni fuentes del informe. La pestaña local queda abierta.

## Trazabilidad de implementación

| Requisito / CU | Código y tablas | Autorización / pruebas |
| --- | --- | --- |
| RF5 / CU06 | SpaceAvailabilityService, SpaceFilterRequest, Spaces/Availability.vue; espacios_academicos, bloqueos_espacio, reservas y catálogos existentes | espacios.ver; SpaceAvailabilityTest + ReservationAvailabilityTest |
| RF6 / CU07 | ReservationController.store, ReservationRequest, ReservationService.create, Reserva/EstadoReserva y Reservations/Create.vue; reservas | ReservaPolicy.create; ReservationRequestTest + Lifecycle + Concurrency |
| RF6–7 / CU08 | ReservationFilterRequest, ReservationQueryService, Controller.index/show, Reservations/Index/Show.vue; reservas y relaciones oficiales | ReservaPolicy.view y scopes; ReservationRequestTest |
| RF7 / CU09 | ReservationActionRequest, ReservationService.approve/reject y ReservationActions.vue; reservas | ReservaPolicy.resolve; Lifecycle + Concurrency |
| RF7 / CU10 | ReservationActionRequest, ReservationService.cancel y ReservationActions.vue; reservas | ReservaPolicy.cancel + guardas tras lock; Lifecycle + Concurrency |

## Integraciones diferidas y continuidad

- Fase 6/CU11: QR, sin código ni tabla anticipados.
- Fase 7: enriquecimiento IoT, sin sensores ni lectura funcional actual.
- Fase 8/CU23: liberación y políticas funcionales; LIBERADA solo pertenece al enum oficial.
- FINALIZADA: mecanismo pendiente de autorización posterior; sin cron ni transición manual.
- Fase 9/CU24: punto conceptual después del commit de aprobación/cancelación crítica; sin hashes, evidencia falsa ni llamadas externas. No se añade CU07 como operación crítica.
- Fase 10: notificaciones después del commit de solicitudes/resoluciones/cancelaciones; sin tabla, envío ni outbox actuales. Los mensajes de la request no satisfacen RF21.
- CU30: actividad/auditoría general pendiente de autorización; seguimiento derivado no sustituye registros_actividad.
- D-04: asignación fina por recurso continúa diferida. Sin cambios de contrato móvil/API, chatbot, dashboard o reportes.

Siguiente fase recomendada, únicamente tras nueva instrucción: **Fase 5 — Equipamiento, préstamos, entrega, devolución e incidencias (CU12–CU19, RF9–15)**. Antes de escribir, revisar D-02/D-05/D-11 y cerrar el ámbito aplicable de D-04. Conservar enums oficiales, exclusividad y entrega/devolución atómicas; definir los puntos condicionales de QR para Fase 6 sin implementarlos anticipadamente. No liberar equipamiento por mero vencimiento del periodo. Fase 5 no comenzó en esta entrega.

## Integración posterior de Fase 5

El contenido anterior es el cierre histórico de Fase 4. Fase 5 ya implementó CU12–CU19; SpaceAvailabilityService agrega incidencias abiertas a CU06 y a las revalidaciones de CU07/CU09. No se modifica el ciclo de reservas, el carácter opcional de su rechazo, sus migraciones ni sus periodos. Se preservan las reservas y bloqueos al cerrar incidencias. La suite vigente suma 262 pruebas / 3318 aserciones, incluida la regresión de Fase 4. [Evidencia y siguiente fase pendiente](EQUIPMENT_LOANS_IMPLEMENTATION.md).

## Extensión documental CU11 — Fase 5.5

D-09 está resuelta para Fase 6; [contrato CU11](DECISIONS.md#cu11-y-autorización-contextual). Solo reserva APROBADA dentro de [inicio,fin), titular D/S autorizado o A/E con permiso administrativo además del QR. El recurso esperado deriva de la reserva y el QR debe identificar ese espacio o esa reserva exacta. Se reutilizará la guarda de disponibilidad central con excepción de la propia reserva, sin ignorar bloqueos/incidencias. Registrar validación no cambia reserva/espacio ni crea un estado de uso. Tolerancias/liberación siguen Fase 8. No hubo cambio funcional, migración ni ejecución QR en Fase 5.5; la evidencia de pruebas anterior mantiene su fecha y alcance.

## Integración posterior QR — Fase 6

La Fase 6 posterior implementa CU11/CU20 y las extensiones opcionales de CU17/CU18; preserva estados, disponibilidad central, núcleo sin QR y la historia descrita en este cierre. [Implementación, pruebas actuales y aceptación física por confirmación del usuario (sin corroboración independiente nueva)](QR_IMPLEMENTATION.md). Las referencias a QR pendiente en el cierre histórico de este documento no describen el avance actual. IoT, liberación, Blockchain, notificaciones y CU30 siguen diferidos.

## Punto de integración cerrado en Fase 8.5

El núcleo funcional descrito arriba no cambia. [D-12](DECISIONS.md#contrato-blockchain--cierre-d-12-de-fase-85) queda RESUELTA PARA FASE 9: aprobación CU09 y cancelación CU10 exitosas crearán una actividad EXITO/critica dentro de su misma transacción; solo después del commit se generará la evidencia local y se intentará registro externo. Fallo de actividad obligatoria revierte la operación; fallo externo posterior no.

RESERVA_APROBADA usa reserva_id, reserva_codigo, espacio_id, inicio, fin, resuelta_por_id y resuelta_en; excluye estado actual. RESERVA_CANCELADA usa reserva_id, reserva_codigo, espacio_id, cancelada_por_id, cancelada_en y motivo_cancelacion_hash. Conservar resolución/intervalo/código tras aprobación permite verificarla después de cancelación o liberación; cancelar una pendiente también es una operación crítica válida si cumple CU10. Contexto común y tipos exactos se centralizan en D-12.

Solicitud CU07, rechazo y LIBERADA quedan excluidos. No se añade transición FINALIZADA ni se modifica disponibilidad. No se incorporaron ahora actividad, hashing, callback, migración o RPC. Los tests de reservas previos siguen siendo evidencia histórica; T-B06/07/10/29 especifican la futura integración y regresión.

## Integración RF21 prevista — cierre de Fase 9.5

El núcleo funcional de reservas y las actividades críticas ya implementadas en Fase 9 se conservan. [Contrato N2–N8](DECISIONS.md#notification-contract--readiness-for-phase-10) fija RESERVA_SOLICITADA (SOLICITUD → AR), RESERVA_APROBADA/RECHAZADA (DECISION → solicitante) y RESERVA_CANCELADA (CANCELACION). AR exige reservas.resolver + reservas.ver_administrativas y cuenta activa. Cancelación ajena avisa al solicitante; autocancelación avisa a AR excluyendo al titular/actor, nunca a sí mismo.

Solo solicitud/rechazo necesitan nuevas actividades no críticas dentro de su transacción. Aprobación/cancelación reutilizan exactamente la actividad crítica de Fase 9; notificación posterior al commit e independiente de evidencia Blockchain. No modificar D-05, guardas temporales, disponibilidad, permisos, orden de locks ni hitos. Una operación revertida no origina aviso; un fallo posterior del aviso no revierte una reserva confirmada.

CU08 aporta enlace contextual sujeto a autorización actual; el mensaje es snapshot y no cambia al cambiar estado de la reserva. CU23 ALERTAR/LIBERADA tiene contrato específico en N3/N4 y RELEASE_POLICY_IMPLEMENTATION. Recuperación acotada por N6. T-N04, T-N07–T-N11, T-N27–T-N29 y T-N36–T-N38 serán pruebas futuras. Sin hooks, UI ni nuevas escrituras en Fase 9.5.

## Extensión RF21 implementada — Fase 10

Solicitud/rechazo producen actividad no crítica dentro de negocio; aprobación/cancelación reutilizan la crítica existente. Postcommit AR/S según N3. Estados, exclusividad, disponibilidad y locks previos conservados. [Implementación y evidencia](NOTIFICATIONS_IMPLEMENTATION.md).

## Consumo documental por CU27/CU28 — Fase 10.5

[DR2/DR4–DR6](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11) conserva el servicio y permisos de Fase 4. Aprobada activa = APROBADA e inicio<=t<fin; próxima propia = APROBADA e inicio>=t. Pendientes son stock actual, no historia filtrada. Liberadas del periodo se acreditan por EXISTS evaluación LIBERAR/fecha_hora y estado actual LIBERADA; no inferir fecha ni duplicar por pivotes. No finalizar reservas al consultar.

Reporte de reservas filtra solapamiento inicio<B y fin>A, orden inicio/id; tendencias y estados del periodo usan solicitada_en. Explicar esa diferencia. Las nueve columnas excluyen propósito/motivos libres; solicitante solo bajo consulta administrativa autorizada. Espacio DISPONIBLE no prueba disponibilidad: SpaceAvailabilityService sigue siendo único para el intervalo con reservas, bloqueos e incidencias. Dashboard y reportes no cambian ni escriben el ciclo de reserva. Fase 11 no iniciada.

## Integración con Fase 11

[Dashboard y reportes](DASHBOARD_REPORTING_IMPLEMENTATION.md) reutilizan las reglas, consultas y scopes de este dominio. No modifican sus estados, decisiones o productores. REPORTE_EXPORTADO no se añade a la whitelist de notificaciones ni a eventos críticos Blockchain. CU30 sigue NO IMPLEMENTADO; las limitaciones históricas de este informe conservan su alcance.


## Límite de asistencia previsto — Fase 11.5

Solo explicar solicitud/seguimiento/resolución/cancelación según permisos y reglas vigentes. No consultar ni transmitir reservas propias/ajenas, aprobar o cancelar desde el chat; enlaces a listas con alcance fijo y autorización actual. [Contrato CH1–CH12](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12). Cambio exclusivamente documental; Fase 12 NO iniciada.

## Continuidad documental de RF25/CU30 — Fase 12.5

El [contrato AC1–AC15](DECISIONS.md#activity-audit-contract--readiness-for-cu30) conserva RESERVA_SOLICITADA/APROBADA/RECHAZADA/CANCELADA y ALERTADA_LIBERACION/LIBERADA con sus actores/procesos/contextos. No duplicar las cuatro actividades humanas ni sus consumidores al centralizar el recorder. Rechazo de solicitud confirmado sigue EXITO; las fallas de estos productores no adquieren nueva política de registro en AC6. CU30 consultará historia institucional mínima autorizada, sin conceder lectura de reserva por enlace.

Cambio exclusivamente documental. CU30 **NO IMPLEMENTADO**; implementación requiere nueva instrucción. La evidencia funcional anterior conserva su fecha y no se presenta como verificación nueva.


## Integración de auditoría — Fase 12.6

Los seis hitos de reserva conservan identificador, actor/proceso y asociaciones U/R/V. Los recorders heredados delegan una única inserción al ActivityRecorder; aprobación/cancelación mantienen evidencia D-12 y callbacks después del commit, mientras rechazo de solicitud confirmado sigue EXITO. No nuevos RECHAZO/ERROR para estos productores heredados ni actividad por consulta. CU30 reautoriza enlaces a reservas; actividad.ver no concede lectura administrativa de ellas.

[Implementación RF25/CU30, pruebas y resultados de integración](ACTIVITY_AUDIT_IMPLEMENTATION.md). Los cierres de fases anteriores conservan su alcance histórico. Fase 13 no iniciada.
