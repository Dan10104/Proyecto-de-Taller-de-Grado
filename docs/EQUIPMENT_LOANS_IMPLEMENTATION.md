# Equipamiento, préstamos, entrega, devolución e incidencias — Fase 5

Fecha: 2026-10-23. Núcleo de CU12–CU19 y RF9–RF15 implementado y validado; CU19 amplía CU06 y las revalidaciones de reservas. Se aplica el contrato congelado de Fase 4.5, D-02/D-11 y D-04/D-05, sin cambiar el SQL final. Las obligaciones de QR, Blockchain, notificaciones y bitácora general siguen pendientes de sus fases. **Fase 6 no iniciada.**

## Persistencia y dominio

Se añadieron únicamente seis migraciones incrementales `2026_10_23_000016` a `000021`. Las quince anteriores permanecen intactas. Son 54 columnas nuevas, sin timestamps Laravel: 21 tablas académicas / 135 columnas académicas / 35 FK en el esquema materializado, más la tabla técnica `migrations`. El modelo final de 31 tablas / 211 columnas / 63 FK no se modifica: faltan diez tablas académicas.

| Orden | Tabla | Modelo | Columnas y relaciones principales |
| --- | --- | --- | --- |
| 16 | tipos_equipamiento | TipoEquipamiento | 3; código UNIQUE; un tipo tiene muchos equipos |
| 17 | equipamientos_tecnologicos | EquipamientoTecnologico | 9; recurso_id PK+FK; tipo obligatorio, espacio_base nullable; serie nullable UNIQUE |
| 18 | prestamos | Prestamo | 12; equipo, solicitante y resolutor; código UNIQUE, periodo y estado |
| 19 | entregas_prestamo | EntregaPrestamo | 8; préstamo UNIQUE, responsable y receptor; condición texto y fechas |
| 20 | devoluciones_prestamo | DevolucionPrestamo | 10; préstamo UNIQUE; receptor físico, confirmador, condición y estado |
| 21 | incidencias_mantenimiento | IncidenciaMantenimiento | 12; recurso común, reportante, responsable del cierre y devolución opcional |

`EquipamientoTecnologico.recurso_id` es la misma identidad que `RecursoInstitucional.id`, sin id independiente. EquipmentService crea padre y subtipo en una transacción; un fallo revierte ambos. El espacio base es ubicación habitual, no custodia; el piso se obtiene por espacio base. Los vacíos de serie se normalizan a NULL y MySQL permite múltiples NULL. No existen acciones de eliminación física ni catálogos institucionales inventados.

Los seis modelos tienen relaciones Eloquent, casts enum/fechas y asignación masiva cerrada. Los actores se obtienen de la sesión y las fechas de decisión del servidor. Requests y servicios validan sus entradas; las Policies y Gates conservan RBAC sin bypass de Administrador.

| Enum PHP | Valores persistidos |
| --- | --- |
| EstadoEquipamiento | DISPONIBLE, PRESTADO, EN_MANTENIMIENTO, BAJA |
| EstadoPrestamo | PENDIENTE, APROBADO, RECHAZADO, ENTREGADO, DEVUELTO |
| CondicionDevolucion | ADECUADA, CON_INCIDENCIA, NO_DETERMINABLE |
| EstadoDevolucion | PENDIENTE_REVISION, CONFIRMADA |
| TipoIncidencia | INCIDENCIA, MANTENIMIENTO |
| EstadoIncidencia | ABIERTA, CERRADA |

VENCIDO es exclusivamente `estado == ENTREGADO && ahora > fin_previsto`; a igualdad no vence. No se guarda, no libera equipo y no produce transición automática. ACTIVO y FINALIZADO no se agregan al enum. El código inmutable `PRE-` + UUID v4 canónico ocupa 40 caracteres, se genera en servidor y tiene UNIQUE; solo la colisión de ese índice admite hasta tres intentos.

## Permisos y alcance

A = Administrador; E = Encargado de Laboratorio; D = Docente; S = Estudiante. Cada permiso exige una asignación activa de un rol elegible que aporte ese permiso. Los doce códigos se incorporan al catálogo existente de 21: **33 permisos**. Matriz completa inicial: A 30, E 19, D 7, S 7.

| Código nuevo | A | E | D | S |
| --- | --- | --- | --- | --- |
| equipamientos.ver | Sí | Sí | Sí | Sí |
| equipamientos.gestionar | Sí | Sí | — | — |
| equipamientos.cambiar_estado | Sí | Sí | — | — |
| prestamos.ver_propios | — | — | Sí | Sí |
| prestamos.ver_administrativos | Sí | Sí | — | — |
| prestamos.solicitar | — | — | Sí | Sí |
| prestamos.resolver | Sí | Sí | — | — |
| prestamos.registrar_entrega | Sí | Sí | — | — |
| prestamos.registrar_devolucion | Sí | Sí | — | — |
| incidencias.ver | Sí | Sí | — | — |
| incidencias.gestionar | Sí | Sí | — | — |
| incidencias.cerrar | Sí | Sí | — | — |

SecuritySeeder incorpora los códigos nuevos de forma idempotente y conserva revocaciones anteriores. Las seis factories nuevas generan datos sintéticos relacionados; entregas/devoluciones mantienen sus precondiciones. No se agregan cuentas institucionales ni seeds masivos de inventario a desarrollo.

La consulta propia se restringe antes de filtros, paginación, opciones y contadores. Manipular solicitante o pedir un detalle ajeno no amplía el alcance. DTO de seguimiento con nombres necesarios, sin correos, hashes, tokens, sesiones ni modelos completos de usuario. Las mutaciones conservan CSRF y reautorizan después de esperar coordinación RBAC. La devolución CON_INCIDENCIA exige además `incidencias.gestionar`; la habilitación explícita exige el permiso de cambio de estado correspondiente.

## Disponibilidad y transiciones

EquipmentAvailabilityService concentra CU13, CU14, CU16 y entrega. Intervalos `[inicio, fin)` con fin estrictamente posterior al inicio: existe solapamiento cuando `existente.inicio < solicitado.fin && existente.fin > solicitado.inicio`. La adyacencia se permite. APROBADO y ENTREGADO bloquean su periodo; PENDIENTE, RECHAZADO y DEVUELTO no reservan exclusividad. Dos pendientes incompatibles pueden coexistir.

La disponibilidad requiere estado físico DISPONIBLE, ausencia de incidencias/mantenimiento ABIERTOS y de conflictos. PRESTADO, EN_MANTENIMIENTO y BAJA excluyen cualquier periodo futuro; el fin previsto no recupera la custodia. La comprobación defensiva de un ENTREGADO o de una revisión pendiente también excluye aunque el rótulo físico estuviera desactualizado. Toda incidencia abierta es restrictiva inmediatamente, incluso mantenimiento de inicio futuro; fin_previsto no la cierra. La consulta usa SQL NOT EXISTS, filtros por tipo/estado/ubicación/marca/modelo y paginación de 15. No carga todos los préstamos para filtrar catálogos en PHP.

| Acción | Guarda y resultado atómico |
| --- | --- |
| Solicitar | Docente/Estudiante autorizado; equipo/periodo/motivo válidos; revalidación bajo lock → PENDIENTE, código/solicitante/fecha del servidor |
| Aprobar | Solo PENDIENTE y disponibilidad vigente → APROBADO, responsable y fecha; otra aprobación incompatible deja al perdedor PENDIENTE sin resolución |
| Rechazar | Solo PENDIENTE; texto no vacío hasta 500 → RECHAZADO con responsable/fecha; no necesita que el equipo esté disponible; rechazo de reservas sigue opcional |
| Entregar | Solo APROBADO, equipo disponible, sin restricciones; identificación física de equipo y receptor; condición obligatoria <=255, observación opcional <=500 → una entrega, ENTREGADO y PRESTADO |
| Devolver ADECUADA | Solo ENTREGADO con entrega válida → una devolución CONFIRMADA y préstamo DEVUELTO; equipo disponible solo si no quedan restricciones |
| Devolver CON_INCIDENCIA | CONFIRMADA y DEVUELTO, incidencia del mismo equipo vinculada por devolucion_id, equipo EN_MANTENIMIENTO; descripción obligatoria, todo o nada |
| Devolver NO_DETERMINABLE | PENDIENTE_REVISION, préstamo sigue ENTREGADO, equipo EN_MANTENIMIENTO; confirmador/fecha NULL; descripción requerida |
| Confirmar revisión | Actualiza la misma devolución a ADECUADA o CON_INCIDENCIA; conserva recibido_por_id/registrada_en, atribuye confirmador/fecha actuales; pasa a DEVUELTO; no cierra incidencias previas |

Receptor de entrega = solicitante, sin delegación ni selección de terceros. Responsable y fecha se fijan en servidor; `recepcion_confirmada_en` permanece NULL. Los checks de identificación del formulario no crean columnas nuevas. UNIQUE por préstamo y locks impiden duplicar entregas/devoluciones. No hay QR ni firma digital simulada.

BAJA es exclusivamente administrativa, impide solicitar/aprobar/entregar y se conserva al devolver o cerrar incidencias. Para retirarla se necesita cambio explícito autorizado y validación. PRESTADO no puede asignarse por edición genérica. EN_MANTENIMIENTO administrativo requiere confirmación explícita de aptitud y permiso para recuperarse: una devolución adecuada puede quedar confirmada sin liberar ese estado. Si persisten incidencias o revisión, se conserva EN_MANTENIMIENTO; si hay otro ENTREGADO, PRESTADO.

## CU19 y espacios

IncidentMaintenanceService opera sobre `recurso_id` común a espacios y equipos. Abrir registra ABIERTA, actor de sesión, descripción, tipo y periodo. El formulario identifica el subtipo y no permite vincular arbitrariamente una devolución. El vínculo se produce dentro del retorno del equipo. Aplicación: fin_previsto informado > inicio; SQL original conserva CHECK >=. Cerrar requiere ABIERTA, solución y reloj no anterior al inicio; guarda responsable/fecha sin borrar el registro.

Cerrar una entre varias no habilita el recurso. La opción de recuperar disponibilidad es explícita y autorizada; un cierre puede completarse conservando restricciones. BAJA no se retira. Una devolución pendiente mantiene mantenimiento y un ENTREGADO mantiene custodia. El cierre de espacio conserva bloqueos vigentes y otras incidencias; tampoco elimina reservas ni bloqueos futuros. Los compromisos afectados se muestran, sin cancelación automática.

SpaceAvailabilityService es el único algoritmo de espacios: estado + bloqueos activos del periodo + reservas APROBADA solapadas + incidencias ABIERTA. CU06 y revalidaciones de CU07/CU09 lo reutilizan. CU05 tampoco permite habilitar un espacio con incidencia o bloqueo vigente. IoT continúa diferido.

## Coordinación transaccional

ResourceOperationService coordina todas las escrituras nuevas. Orden: fila RBAC existente de Administrador → subtipo de recurso por recurso_id → préstamo objetivo cuando existe → otros préstamos pertinentes por id → entregas por id → devoluciones por id → incidencias por id. Para espacios conserva el orden previo: espacio, bloqueos y reservas, luego incidencias. Se revalida autorización tras adquirir el lock RBAC; las lecturas relacionadas usan FOR UPDATE y son actuales incluso después de una espera con snapshot anterior.

Los estados se actualizan condicionados al estado esperado; hitos y cambio físico se confirman juntos. Hasta tres intentos ante deadlock; agotamiento/timeout se traduce a conflicto comprensible, sin SQL sensible. Los UNIQUE de hitos complementan las guardas. No existen llamadas a servicios externos dentro de las transacciones.

Las 14 pruebas de LoanConcurrencyTest usan dos procesos PHP/conexiones MySQL reales, barreras observables y tiempos acotados. Cubren pendientes coexistentes, aprobaciones incompatibles/adyacentes, aprobar contra rechazar, doble entrega/devolución/revisión/cierre, apertura contra cierre en ambos órdenes, lectura actual después de esperar recurso e incidencia y revocación durante espera RBAC. El perdedor no deja responsables/fechas/hitos parciales.

Límite: la coordinación RBAC global serializa escrituras protegidas, como en fases previas; no es una certificación de escalabilidad. Los ensayos de espera directa del recurso evitan atribuir toda la seguridad de concurrencia solo a esa serialización. No se modificó el aislamiento global de MySQL.

## Interfaces

Equipment/{Index,Form,Show,Types}: inventario, filtros, disponibilidad, alta/edición, detalle y estado. Loans/{Index,Create,Show}: consulta propia/administrativa, selección conservando periodo, motivo, decisiones, entrega, devolución, revisión y seguimiento. Incidents/{Index,Create,Show}: filtros, recurso/subtipo, alta, cierre y compromisos afectados. AppLayout expone navegación según permisos; Spaces/Show enlaza incidencias cuando se autoriza.

OperationForm usa diálogo nativo con foco y confirmación de acciones sensibles; LoanActions presenta únicamente acciones permitidas. OperationalState diferencia los estados oficiales y vencimiento calculado. Fechas locales America/La_Paz, mensajes de conflicto comprensibles y tablas con scroll interno. Durante QA se corrigieron el formato de periodo seleccionado, fechas de compromisos y duplicación del aviso de conflicto.

## Trazabilidad de implementación

| RF / CU | Tablas / modelos | Servicios / Policy | Vistas | Pruebas principales |
| --- | --- | --- | --- | --- |
| RF9 / CU12 | tipos_equipamiento / TipoEquipamiento; equipamientos_tecnologicos / EquipamientoTecnologico; RecursoInstitucional, EspacioAcademico | EquipmentService / EquipamientoTecnologicoPolicy | Equipment/* | EquipmentManagementTest, EquipmentLoanSchemaTest |
| RF10 / CU13 | EquipamientoTecnologico, Prestamo, IncidenciaMantenimiento, DevolucionPrestamo | EquipmentAvailabilityService / EquipamientoTecnologicoPolicy | Equipment/Index | EquipmentAvailabilityTest |
| RF11 / CU14 | prestamos / Prestamo | LoanService, EquipmentAvailabilityService / PrestamoPolicy | Loans/Create | LoanLifecycleTest, LoanHttpTest, LoanConcurrencyTest |
| RF11–14 / CU15 | Prestamo, EntregaPrestamo, DevolucionPrestamo | LoanQueryService / PrestamoPolicy | Loans/Index, Show | LoanHttpTest, EquipmentAvailabilityTest, LoanLifecycleTest |
| RF12 / CU16 | Prestamo, EquipamientoTecnologico | LoanService, ResourceOperationService / PrestamoPolicy | Loans/Index, Show; LoanActions | LoanLifecycleTest, LoanConcurrencyTest |
| RF13 / CU17 | entregas_prestamo / EntregaPrestamo; Prestamo, EquipamientoTecnologico | LoanService / PrestamoPolicy | Loans/Show; LoanActions | LoanLifecycleTest, LoanHttpTest, LoanConcurrencyTest |
| RF14 / CU18 | devoluciones_prestamo / DevolucionPrestamo; Prestamo, IncidenciaMantenimiento | LoanService, ResourceOperationService / PrestamoPolicy | Loans/Show; LoanActions | LoanLifecycleTest, LoanConcurrencyTest |
| RF15 / CU19 | incidencias_mantenimiento / IncidenciaMantenimiento; recurso y ambos subtipos | IncidentMaintenanceService / IncidenciaMantenimientoPolicy | Incidents/* | IncidentMaintenanceTest, LoanConcurrencyTest |
| RF5–7 + RF15 / CU06, CU07, CU09 | EspacioAcademico, BloqueoEspacio, Reserva, IncidenciaMantenimiento | SpaceAvailabilityService, SpaceManagementService / Policies existentes | Spaces/Availability, Show; Reservations/* | IncidentMaintenanceTest y regresión de espacios/reservas |

## Aceptación T-P01–18

| Criterio congelado | Evidencia ejecutada |
| --- | --- |
| T-P01 | EquipmentLoanSchemaTest: metadatos completos, PK/FK/índices/CHECK/UNIQUE y herencia; EquipmentManagementTest: rollback sin huérfano, serie NULL |
| T-P02 | LoanHttpTest/EquipmentManagementTest: matriz, revocaciones, propiedad, parámetros y campos protegidos; schema: enums exactos |
| T-P03 | EquipmentAvailabilityTest: siete relaciones de intervalos, filtros, estados físicos, incidencias futuras y periodo incompleto |
| T-P04 | LoanLifecycleTest/LoanConcurrencyTest: coexistencia pendiente, código UUID e identidad del solicitante |
| T-P05 | LoanConcurrencyTest: exactamente una aprobación incompatible, perdedor pendiente; adyacencia; lectura actual tras espera |
| T-P06 | LoanLifecycleTest/LoanHttpTest: estados, rechazo requerido y longitud; carrera aprobar/rechazar |
| T-P07 | LoanConcurrencyTest: una entrega y estado atómico; incidencia confirmada mientras espera recurso |
| T-P08 | LoanLifecycleTest/LoanHttpTest: responsables/receptor del servidor, condición y confirmación NULL, sin QR |
| T-P09 | LoanConcurrencyTest: una devolución; LoanLifecycleTest: fallo después de hito/estado y rollback |
| T-P10 | LoanLifecycleTest: adecuada/incidencia vinculada, permisos combinados, BAJA y rollback de incidencia |
| T-P11 | LoanLifecycleTest/IncidentMaintenanceTest: desconocida, NULL de confirmación, custodia/restricción conservadas |
| T-P12 | LoanLifecycleTest/LoanConcurrencyTest: revisión de misma fila, recepción preservada, responsable nuevo, doble confirmación |
| T-P13 | IncidentMaintenanceTest: ambos subtipos, validación temporal, cierre manual, autores y vínculo |
| T-P14 | IncidentMaintenanceTest/LoanConcurrencyTest: varias incidencias, ambos órdenes apertura/cierre, doble cierre y restricciones restantes |
| T-P15 | IncidentMaintenanceTest y regresión previa: CU06/CU07/CU09, permisos, bloques vigentes/futuros, reservas conservadas |
| T-P16 | EquipmentManagementTest/LoanLifecycleTest/IncidentMaintenanceTest: BAJA explícita, PRESTADO no manual, mantenimiento y aptitud |
| T-P17 | LoanLifecycleTest/LoanHttpTest/EquipmentAvailabilityTest: vencimiento estricto calculado, revisión, alcance y consultas con 1.000 filas |
| T-P18 | EquipmentLoanSchemaTest/LoanHttpTest/LoanLifecycleTest: esquema limitado, CSRF, actores/fechas, estados imposibles y atomicidad |

## Resultados reproducibles

Se conservaron las 197 pruebas previas y se añadieron **65** en siete clases: EquipmentManagementTest (5), EquipmentAvailabilityTest (11 con datasets), EquipmentLoanSchemaTest (3), LoanLifecycleTest (16), LoanHttpTest (8), IncidentMaintenanceTest (8), LoanConcurrencyTest (14). LoanTestCase y Support/concurrent-loans.php aportan preparación y procesos independientes. Solo se actualizaron expectativas anteriores afectadas por tablas/FK/permisos nuevos; no se retiró cobertura previa.

| Comprobación final | Resultado |
| --- | --- |
| php artisan test | **262 aprobadas; 3318 aserciones; 178,23 s; 0 fallos, 0 errores, 0 omitidas** |
| Motor | MySQL real aislado, espacio_u_testing; ejecución en serie; guarda previa a cualquier limpieza |
| Pint --test | Correcto |
| npm run build | Correcto; compilación final 286 ms |
| composer audit | 0 avisos y 0 dependencias abandonadas reportadas |
| npm audit | 0 vulnerabilidades reportadas |
| Rendimiento local con 1.000 registros | Catálogo 0,0075 s / 4 consultas; consulta de préstamos 0,0059 s; ambos <3 s |

El tiempo de rendimiento mide consultas del servicio en este equipo/base, no latencia de red, render de navegador ni concurrencia sostenida. No certifica Fase 13. Los informes JUnit/logs y capturas están en `.local/verification/phase5/`, ignorados; no se publican credenciales ni imágenes de usuarios reales.

## Verificación manual y accesibilidad

Navegador integrado sobre la aplicación real en 127.0.0.1:8000, con MySQL de desarrollo 3308 y datos exclusivamente sintéticos. Se completaron los 28 pasos solicitados:

| Pasos | Resultado observado |
| --- | --- |
| 1–5 | Administrador autenticado; tipo/equipo creados, edición persistida y disponibilidad por periodo |
| 6–12 | Docente y Estudiante crearon pendientes incompatibles; Encargado aprobó una; la otra dio conflicto sin resolución y después fue rechazada con motivo |
| 13–16 | Entrega identificada → ENTREGADO/PRESTADO; devolución ADECUADA → DEVUELTO/DISPONIBLE |
| 17–19 | Segundo ciclo CON_INCIDENCIA → vínculo real y mantenimiento; cierre autorizado con aptitud recuperó disponibilidad |
| 20–21 | Tercer ciclo NO_DETERMINABLE → revisión pendiente y confirmador NULL; otro responsable (Administrador) confirmó ADECUADA conservando la recepción del Encargado |
| 22 | Mantenimiento manual de equipo y cierre explícito, sin cierre por fin previsto |
| 23–24 | Incidencia de espacio lo excluyó de CU06; cierre autorizado recuperó la disponibilidad |
| 25 | Estudiante obtuvo 403 al pedir detalle de préstamo ajeno |
| 26 | 1440×900, 1366×768, 768×1024 y 390×844; 4 pantallas por tamaño, 16 mediciones sin overflow global |
| 27 | Enter abre diálogo y enfoca Cancelar; Tab alcanza Confirmar; Escape cierra y devuelve foco; confirmación por teclado; tabla móvil y navegación operables |
| 28 | Consola final sin errores ni advertencias |

Comprobación adicional: BAJA excluyó el equipo del catálogo; reactivación administrativa con aptitud volvió a habilitarlo. Se inspeccionaron labels (ningún control sin etiqueta en las 16 mediciones), foco visible, contraste visual heredado y disposición sin superposiciones. No equivale a auditoría WCAG completa.

Estado final sintético conservado para seguimiento: un tipo y un equipo demo; cuatro préstamos (tres DEVUELTO, uno RECHAZADO), tres entregas y tres devoluciones CONFIRMADA; tres incidencias CERRADA. Equipo y espacio demo DISPONIBLE. Las tres cuentas de QA fueron deshabilitadas y sus sesiones revocadas; sus credenciales temporales fueron retiradas de .env. La cuenta administrativa local y los servicios existentes se conservan. Capturas locales: final-1440.png, devolucion-1440.png, revision-pendiente-1440.png, cu06.png, prestamos-390.png y los cuatro tamaños del formulario de incidencia.

## Aplicación local y límites

Desde la raíz oficial y con el runtime activado: `php artisan migrate --force`, `php artisan db:seed --class=SecuritySeeder --force` y `npm run build`. En esta entrega se aplicaron únicamente las seis migraciones incrementales a desarrollo. No ejecutar migrate:fresh ni restauraciones sobre espacio_u; la limpieza de pruebas pertenece exclusivamente a espacio_u_testing con su guarda y credenciales separadas en .env.testing. Se preservan las instancias MySQL ajenas al proyecto.

Se revisan los archivos públicos, índice, diffs e historial antes de publicar y se contrastan con valores privados de ambos entornos y bases sin imprimirlos. .env/.env.testing, runtime, datos MySQL y evidencias permanecen ignorados; .env.example mantiene secretos vacíos. Word/PDF originales no se modifican. No se añadieron dependencias ni se alteraron lockfiles.

Revisión previa a publicación: 272 archivos públicos contrastados sin hallazgos; 145 enlaces locales con archivos y anchors válidos; git diff --check correcto. SHA-256 de Word/PDF coincide con el valor previo a esta fase. Rama de trabajo: feature/phase-5-equipment-loans, creada desde main/origin/main en 828bd58. Implementación y pruebas reunidas en 1548ea1; la documentación de este informe se registra en un commit separado. Publicación e integración se realizan sin force push y con verificación de igualdad de las referencias locales/remotas.

Permanecen diferidos QR/codigos_qr/validaciones_qr y CU11/CU20, IoT, liberación automática, Blockchain, notificaciones funcionales/RF21, registros_actividad/CU30, chatbot, dashboard final, app móvil y ámbito fino por recurso. Los responsables/fechas e hitos existentes no sustituyen CU30.

Recomendación para la próxima instrucción: **Fase 6 — Códigos QR y validaciones**. La recomendación original de cerrar D-09 fue atendida documentalmente en Fase 5.5, conservando SQL/UNIQUE/CHECK; la siguiente instrucción podrá implementar CU11/CU20 y las extensiones QR de CU17/CU18, validar objetivo/contexto/permisos/estado en servidor y probar códigos falsos, vencidos, ajenos, reintentos y cámara ausente/denegada. Mantener las transacciones de entrega/devolución y comprobar <=3 s y >=30 validaciones/min según el plan. Esa fase requiere una nueva instrucción y no está ejecutada aquí.

## Extensión documental CU20 — Fase 5.5

La revisión previa de D-09 recomendada en el cierre anterior ya está atendida: [RESUELTA PARA FASE 6](DECISIONS.md#readiness-for-phase-6). Fase 6 todavía no iniciada. CU20 será extensión explícita, sin exigir QR globalmente ni usar politicas_liberacion.requiere_qr. ENTREGA exige APROBADO y las guardas actuales; DEVOLUCION exige ENTREGADO y entrega válida, permitiendo recepción física con incidencia/mantenimiento/BAJA y conservando las tres condiciones.

El contrato fija token CSPRNG/base64url, hash SHA-256, gestión/rotación histórica y una petición/transacción común para validación nueva + servicio principal + enlace al hito. No se acepta una validación antigua como autorización ni se crea otra solo para asociar. NO_DETERMINABLE puede quedar vinculada a VALIDA mientras sigue pendiente de revisión; su confirmación posterior conserva el mismo hito/enlace. No cambia recepcion_confirmada_en ni se introduce firma digital. [Permisos, locking y pruebas T-Q](DECISIONS.md#contrato-qr-y-validaciones--cierre-d-09-de-fase-55). La implementación y las 262 pruebas de Fase 5 permanecen intactas; no son evidencia de QR.

## Integración posterior QR — Fase 6

La Fase 6 posterior implementa CU11/CU20 y las extensiones opcionales de CU17/CU18; preserva estados, disponibilidad central, núcleo sin QR y la historia descrita en este cierre. [Implementación, pruebas actuales y aceptación física por confirmación del usuario (sin corroboración independiente nueva)](QR_IMPLEMENTATION.md). Las referencias a QR pendiente en el cierre histórico de este documento no describen el avance actual. IoT, liberación, Blockchain, notificaciones y CU30 siguen diferidos.
