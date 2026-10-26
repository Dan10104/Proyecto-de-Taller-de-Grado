# Modelo del dominio

**Fase 12.6 — RF25/CU30 implementados y validados localmente (2026-10-26).** [Registro de actividad y bitácora](ACTIVITY_AUDIT_IMPLEMENTATION.md): 56 acciones, recorder transaccional, permiso exclusivo de Administrador y consulta individual. Sin nuevas tablas/dependencias. Fase 13 NO iniciada; smokes OpenAI/Sepolia pendientes independientes. Los cierres siguientes conservan su alcance histórico.

**Estado histórico — Fase 12.5 documental cerrada (2026-10-26).** [Activity Audit Contract / Readiness for CU30](DECISIONS.md#activity-audit-contract--readiness-for-cu30), **0 bloqueantes documentales**. RF25 con productores parciales; CU30 **NO IMPLEMENTADO**. Implementación solo con nueva instrucción. Sin cambios de código, datos, permisos, migraciones o dependencias; OpenAI/Sepolia pendientes independientes.

**Base funcional conservada — Fase 12 implementada y validada offline (2026-10-26).** RF24/CU29: [implementación del asistente](CHATBOT_IMPLEMENTATION.md), conforme a CH1–CH12. Orientación por permisos, OpenAI Responses y contexto mínimo sin datos operativos ni historial persistido. CU30 **NO IMPLEMENTADO**; aplicación móvil diferida; ninguna fase posterior iniciada. Los cierres siguientes conservan su contexto histórico.

**Estado histórico — Fase 11.5 documental cerrada (2026-10-26).** RF24/CU29: [Chatbot Assistance Contract / Readiness for Phase 12](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12), **0 bloqueantes documentales**. Fase 12 NO iniciada; CU30 NO IMPLEMENTADO. No cambios de código, dependencias, configuración o datos.

**Base funcional conservada — Fase 11 implementada y verificada (2026-10-26).** RF22/CU27 y RF23/CU28: 747 tests / 9111 assertions; QA y auditorías productivas aprobadas. [Implementación, scopes, formatos y evidencia](DASHBOARD_REPORTING_IMPLEMENTATION.md). Sin nuevas tablas ni índices; CU30 NO IMPLEMENTADO y Fase 12 NO iniciada. Los cierres anteriores son históricos.

**Estado histórico — Fase 10.5 documental (2026-10-26).** RF22/CU27 y RF23/CU28: [contrato de dashboard y reportes cerrado](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11), **0 bloqueantes documentales**. Fase 11 NO iniciada; CU30 NO IMPLEMENTADO. Fase 10 funcional se conserva, sin cambios de código, datos, permisos o dependencias. Los cierres anteriores mantienen su contexto histórico.

**Estado histórico funcional — Fase 10 (2026-10-26).** RF21/CU26 implementados y verificados: 17 reglas, avisos propios, primera lectura y recuperación de 24 h. [Informe y evidencia](NOTIFICATIONS_IMPLEMENTATION.md). CU30 general y Fase 11 no iniciados. Sepolia real sigue pendiente de testETH. Los apartados de cierres anteriores conservan su contexto histórico.

**Estado histórico al cierre de Fase 9 implementada (2026-10-25/26).** RF19/CU24 y RF20/CU25, cuatro actividades críticas, dos tablas y tres permisos implementados conforme a D-12. [Implementación, pruebas offline, excepción temporal dev de Zod y Sepolia pendiente de testETH](BLOCKCHAIN_IMPLEMENTATION.md). CU30 general y Fase 10 permanecen pendientes. Las secciones de cierres anteriores conservan su contexto histórico.

**Estado al cierre funcional de Fase 8 (2026-10-24).** Los enums de modo/resultado y EvaluacionLiberacion materializan el dominio congelado. Una evaluación relaciona reserva/política y Q/E mediante M:N histórica. Solo LIBERAR cambia APROBADA→LIBERADA; no cambia estado del espacio ni crea FINALIZADA. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

**Estado histórico: Fase 7 implementada conforme a D-10.** CU21/CU22 y evidencia informativa en CU06; dos tablas IoT y tres permisos nuevos. [Implementación, seguridad, pruebas y límites](IOT_IMPLEMENTATION.md). CU23 y Fase 8 no iniciados.

La Fase 6 conserva su aceptación y su límite de corroboración física descritos en [QR_IMPLEMENTATION.md](QR_IMPLEMENTATION.md). Los cierres documentales de Fase 6.5 y anteriores se conservan como evidencia histórica; sus criterios futuros se verifican ahora en Fase 7.

Fuentes: figura 19 (p. 109 / PDF 110), figuras 108–109 (p. 248–249 / PDF 249–250), mapeo, volúmenes y SQL del cap. VII. El diagrama conceptual simplifica relaciones; las diferencias con el diseño de clases y datos están registradas en D-06. Estado: contrato congelado en Fase 0.5; materialización incremental hasta Fase 4 descrita al final. Las entidades de fases posteriores siguen siendo documentales.

## Conceptos y responsabilidades

| Concepto del diseño | Tabla de correspondencia | Responsabilidad / invariante |
| --- | --- | --- |
| Usuario | `usuarios` | Correo único; contraseña como hash; desactivar conservando historia. |
| Rol | `roles` | Nombre único; no confundir rol con permiso ni con el ámbito de recursos. |
| Permiso | `permisos` | Identificador técnico `codigo`, único; corrección ASCII resuelta en D-01. |
| Asociación Rol–Permiso | `roles_permisos` | PK compuesta evita repetir la misma asociación rol–permiso. |
| AsignacionRol | `asignaciones_roles` | Conservar usuario, rol, asignador, fechas y retirador opcional; evaluar roles/permisos como conjuntos, sin agregar UNIQUE a la historia (D-17). |
| SesionAcceso | `sesiones_acceso` | Token hash único; expiración posterior a creación; revocación conserva historia. |
| Piso | `pisos` | Número único; cada espacio apunta a un piso. |
| RecursoInstitucional (abstracto) | `recursos_institucionales` | Código único compartido por espacios y equipamiento; superclase abstracta del diseño. |
| TipoEspacio | `tipos_espacio` | Código único; catálogo, sin crear una entidad general Aula. |
| CaracteristicaEspacio | `caracteristicas_espacio` | Nombre único; características M:N con los espacios. |
| PoliticaLiberacion | `politicas_liberacion` | Nombre/version únicos; reglas inmutables por versión; tolerancia y antigüedad no negativas según D-08 de Fase 7.5. |
| EspacioAcademico | `espacios_academicos` | PK compartida con recurso; capacidad positiva; piso y tipo obligatorios; política opcional. |
| Asociación Espacio–Característica | `espacios_caracteristicas` | Una fila por par espacio/característica; no duplicar valores. |
| BloqueoEspacio | `bloqueos_espacio` | Intervalo estricto fin>inicio; responsable y motivo; considerar solo bloqueos aplicables. |
| TipoEquipamiento | `tipos_equipamiento` | Código único; tipos se incorporan como registros. |
| EquipamientoTecnologico | `equipamientos_tecnologicos` | PK compartida; serie opcional única; ubicación base opcional; estado operativo según enum. |
| Reserva | `reservas` | Código único e inmutable; solicitante/espacio obligatorios; fin>inicio; solo APROBADA excluye otro periodo solapado, mediante servidor/transacción. PENDIENTE admite coexistencia. |
| Prestamo | `prestamos` | Código único; equipo/solicitante obligatorios; fin_previsto>inicio_previsto; aprobar solo si disponible. |
| EntregaPrestamo | `entregas_prestamo` | Una entrega por préstamo; entregador y receptor obligatorios; comprobación física y estado aprobado. |
| DevolucionPrestamo | `devoluciones_prestamo` | Una devolución por préstamo; recibido_por obligatorio, confirmado_por opcional; condición incierta conserva revisión. |
| IncidenciaMantenimiento | `incidencias_mantenimiento` | Recurso y reportante obligatorios; devolución y cierre opcionales; fechas coherentes; bloquear uso incompatible. |
| CodigoQR | `codigos_qr` | Exactamente un objetivo: recurso, reserva o préstamo; hash único; vigencia válida. |
| ValidacionQR | `validaciones_qr` | Recurso esperado y validador obligatorios; contexto reserva/préstamo y operación coherentes; enlace único a entrega/devolución. |
| SensorIoT | `sensores_iot` | Identificador único; espacio actual obligatorio; solo HABILITADO admite eventos válidos. |
| EventoOcupacion | `eventos_ocupacion` | Sensor y espacio histórico obligatorios; conservar fecha de evento y recepción; no reescribir al reasignar sensor. |
| EvaluacionLiberacion | `evaluaciones_liberacion` | Reserva/política obligatorias; resultado exacto y evidencias enlazadas; no liberar con datos insuficientes. |
| Asociación Evaluación–Evento | `evaluaciones_eventos_ocupacion` | PK compuesta; conserva los eventos usados en cada evaluación. |
| Asociación Evaluación–Validación | `evaluaciones_validaciones_qr` | PK compuesta; conserva las validaciones usadas en cada evaluación. |
| RegistroActividad | `registros_actividad` | Actor o proceso responsable obligatorio; operación, fecha, resultado e indicador crítica; preservar contexto histórico. |
| EvidenciaTrazabilidad | `evidencias_trazabilidad` | Una evidencia por registro de actividad; hash único; algoritmo/versión y referencia; caída externa deja PENDIENTE. |
| Notificacion | `notificaciones` | Destinatario y actividad obligatorios; leida_en nullable; lectura exclusivamente autorizada al destinatario. |

Los cuatro pares de asociación se materializan como tablas de unión, no como nuevos recursos de negocio. `RecursoInstitucional` concentra identidad y nombre, y sus subtipos `EspacioAcademico` y `EquipamientoTecnologico` comparten su PK. `AsignacionRol` conserva historia y responsables; no sustituirla por una relación usuario/rol sin esas fechas. `SesionAcceso` conserva hash y vigencia. Los nombres de entidades ilustrativas en diagramas de análisis no autorizan tablas ajenas al diseño final.

## Actores y ámbito

| Actor | Responsabilidades documentadas | Límite |
| --- | --- | --- |
| Administrador | Administración, decisiones autorizadas, supervisión y bitácora | Verificar permisos en servidor; no inferir excepciones a reglas de consistencia |
| Encargado de Laboratorio | Gestión de recursos asignados, resolución, entrega, devolución y supervisión autorizada | El modelo no define cómo persistir la asignación de recursos: D-04 |
| Docente y Estudiante | Solicitudes, seguimiento propio, validación y asistencia habilitada | Propiedad y permisos; el iniciador y flujo del CU delimitan la acción (D-03 resuelta) |
| Dispositivo IoT | Envía eventos de presencia/ocupación | No identifica personas ni decide autorizaciones |
| Servicio Blockchain | Registra/consulta evidencia mínima | No es la base operativa ni un aprobador de reservas |
| Proveedor de chatbot | Proporciona orientación a través del servidor | No ejecuta acciones protegidas |

No incorporar “Facultad” como entidad para múltiples instituciones. El contexto único es la FICCT.

## Valores persistidos y reconciliación narrativa

Contrato D-02 resuelto: los 19 campos ENUM del SQL final se conservan completos y en su orden original. Se auditaron términos de RF, CU, narrativa y diagramas frente a clases y SQL. Las etiquetas humanas pueden llevar tildes; los valores persistidos no se traducen ni amplían. Las equivalencias se aplican en su contexto, no como sustituciones universales de palabras.

| Entidad / campo | Estados persistidos oficiales | Términos narrativos equivalentes |
| --- | --- | --- |
| `usuarios.estado` | `ACTIVO`, `INACTIVO` | Cuenta habilitada = ACTIVO; deshabilitada/inactiva = INACTIVO. No hay un tercer estado de cuenta. |
| `politicas_liberacion.modo` | `ALERTA`, `AUTOMATICA` | Advertir = ALERTA; liberar automáticamente bajo condiciones suficientes = AUTOMATICA. Activa/inactiva corresponde al booleano activa. |
| `espacios_academicos.estado` | `DISPONIBLE`, `BLOQUEADO`, `EN_MANTENIMIENTO` | Estado operativo. Libre, ocupado y reservado son disponibilidad/ocupación calculadas; DISPONIBLE no garantiza que el intervalo esté libre. |
| `equipamientos_tecnologicos.estado` | `DISPONIBLE`, `PRESTADO`, `EN_MANTENIMIENTO`, `BAJA` | Entregado/en préstamo = PRESTADO. Dañado, perdido, bloqueado o no disponible no crean valores ni se convierten automáticamente en BAJA; CU19 y las restricciones concretas determinan el tratamiento. |
| `reservas.estado` | `PENDIENTE`, `APROBADA`, `RECHAZADA`, `CANCELADA`, `LIBERADA`, `FINALIZADA` | Solicitada = PENDIENTE; autorizada = APROBADA. Activa/en uso describe una APROBADA en su periodo/contexto; no añade ACTIVA. Cancelación, liberación y finalización conservan valores distintos. |
| `prestamos.estado` | `PENDIENTE`, `APROBADO`, `RECHAZADO`, `ENTREGADO`, `DEVUELTO` | Pendiente de entrega = APROBADO; entregado/activo/pendiente de devolución = ENTREGADO; finalizado por devolución confirmada = DEVUELTO. Cancelado no tiene equivalente automático ni CU de cancelación. Vencido = ENTREGADO y ahora_servidor > fin_previsto, condición derivada; una devolución PENDIENTE_REVISION aún no finaliza el préstamo. |
| `devoluciones_prestamo.condicion` | `ADECUADA`, `CON_INCIDENCIA`, `NO_DETERMINABLE` | Retorno en buenas condiciones = ADECUADA; anomalía = CON_INCIDENCIA; condición desconocida = NO_DETERMINABLE. No confundir condición con estado de devolución. |
| `devoluciones_prestamo.estado` | `PENDIENTE_REVISION`, `CONFIRMADA` | En revisión = PENDIENTE_REVISION; confirmación definitiva = CONFIRMADA. NO_DETERMINABLE conserva revisión, sin cerrar préstamo ni liberar equipo automáticamente. |
| `incidencias_mantenimiento.tipo` | `INCIDENCIA`, `MANTENIMIENTO` | Distingue reporte de incidencia y mantenimiento; tipo no equivale a estado abierto/cerrado. |
| `incidencias_mantenimiento.estado` | `ABIERTA`, `CERRADA` | Abierta/en atención = ABIERTA; resuelta/cerrada = CERRADA. Cerrar no restaura disponibilidad si persiste otra restricción. |
| `validaciones_qr.resultado` | `VALIDA`, `INVALIDA` | Aceptada = VALIDA; rechazada = INVALIDA. Una lectura válida no concede autorización por sí misma. |
| `validaciones_qr.tipo_operacion` | `USO_ESPACIO`, `ENTREGA`, `DEVOLUCION` | Validación de espacio = USO_ESPACIO; validación de entrega o devolución usa su valor correspondiente y contexto exclusivo. |
| `sensores_iot.estado` | `HABILITADO`, `DESHABILITADO` | Autorizado para recibir eventos = HABILITADO; deshabilitado = DESHABILITADO. Sin conexión/sin lectura reciente es salud calculada, no cambio automático de este enum. |
| `eventos_ocupacion.tipo` | `PRESENCIA`, `OCUPACION` | Presencia y ocupación describen el evento recibido; no identifican a una persona ni prueban por sí solas que una reserva deba liberarse. |
| `evaluaciones_liberacion.resultado` | `MANTENER_POR_USO`, `NO_DETERMINABLE`, `ALERTAR`, `LIBERAR` | Uso comprobado mantiene; datos insuficientes = NO_DETERMINABLE; advertencia = ALERTAR; decisión de liberación = LIBERAR. ALERTAR no cambia reserva a LIBERADA. |
| `registros_actividad.resultado` | `EXITO`, `RECHAZO`, `ERROR` | Operación exitosa = EXITO; denegada = RECHAZO; fallo = ERROR. Es resultado de actividad, no estado del recurso. |
| `evidencias_trazabilidad.estado` | `PENDIENTE`, `REGISTRADA` | Evidencia en espera/sin registro externo confirmado = PENDIENTE; registro externo confirmado = REGISTRADA. Caída externa no revierte la operación principal. |
| `evidencias_trazabilidad.resultado_verificacion` | `VALIDO`, `INCONSISTENTE`, `PENDIENTE`, `NO_VERIFICABLE` | Coincidencia = VALIDO; diferencia = INCONSISTENTE; comprobación pendiente = PENDIENTE; imposibilidad de comprobar = NO_VERIFICABLE. Independiente del estado de envío. |
| `notificaciones.tipo` | `SOLICITUD`, `DECISION`, `CANCELACION`, `ENTREGA`, `DEVOLUCION`, `VENCIMIENTO`, `INCIDENCIA`, `LIBERACION` | Clase del aviso; leído/no leído se deriva de leida_en. VENCIMIENTO no añade VENCIDO al préstamo. |

### Entidades sin enum y condiciones calculadas

| Entidad / campo | Representación oficial | Interpretación congelada |
| --- | --- | --- |
| CodigoQR | activo booleano; generacion/expiración según fechas del modelo | Revocado/inactivo se representa con activo=false; vigente exige comprobar fechas. Caducado no crea un estado ni implica cambiar la bandera automáticamente. |
| PoliticaLiberacion | activa, requiere_qr, usa_iot booleanos; modo separado | Antigüedad obligatoria no negativa si usa_iot, NULL en otro caso; al menos una fuente obligatoria. D-08 resuelta en 7.5. |
| BloqueoEspacio | activo y periodo | Un bloqueo aplicable debe estar activo y solaparse con la consulta; no crear enum de bloqueo. |
| SesionAcceso | fechas de creación, expiración y revocación | Vigente exige revocada_en IS NULL y ahora < expira_en, junto a sesión Laravel y cuenta ACTIVO. Control por hash, sin enum adicional (D-07/Fase 1.5). |
| AsignacionRol | fechas de asignación/retiro | Activa = fecha_retiro IS NULL; retirada = fecha_retiro informada. Conservar historia y responsables, sin enum adicional (D-04/Fase 1.5). |
| EntregaPrestamo | fila única por préstamo y recepcion_confirmada_en | Entrega/recepción confirmada usa datos existentes; no crear enum propio. |
| Notificacion | leida_en nullable | NULL = no leída; fecha = leída. El tipo del aviso es independiente. |
| RegistroActividad | critica booleano | Crítica/no crítica es clasificación independiente del resultado. |
| Ocupación y disponibilidad | cálculo de servicio | Sin lectura reciente: NO_DETERMINABLE conceptual; jamás asumir LIBRE ni añadir estados a espacios/eventos. |
| Piso, Rol, Permiso, RecursoInstitucional, tipos y características, tablas de asociación | sin columna de estado | No añadir estados activo/inactivo a catálogos o pivotes por analogía. |

## Ciclos de vida y reglas de transición

| Objeto | Comportamiento respaldado | Guarda / precisión pendiente |
| --- | --- | --- |
| Reserva | Registro PENDIENTE; resolución APROBADA o RECHAZADA; cancelación CANCELADA; política puede producir LIBERADA; existe FINALIZADA | D-05 resuelta para Fase 4 en Fase 3.5: PENDIENTE/APROBADA cancelables con permiso y ahora<inicio; solo APROBADA bloquea. FINALIZADA automática diferida fuera de Fase 4 |
| Préstamo | Solicitud PENDIENTE; resolución APROBADO o RECHAZADO; entrega ENTREGADO; devolución confirmada DEVUELTO | Receptor, identificación y recurso disponible; no agregar CANCELADO/ACTIVO/FINALIZADO |
| Equipamiento | Entrega cambia a PRESTADO; retorno adecuado puede restituir DISPONIBLE; mantenimiento/baja impiden operaciones | Condición desconocida o incidencia no debe devolver disponibilidad automáticamente |
| Devolución | PENDIENTE_REVISION o CONFIRMADA, con condición separada | No cerrar definitivamente una devolución cuya condición sea NO_DETERMINABLE |
| Incidencia/mantenimiento | ABIERTA; cierre CERRADA con solución/responsable | Recurso recupera disponibilidad solo si no hay otra restricción incompatible |
| Sensor | HABILITADO / DESHABILITADO | Falta de comunicación no crea un tercer valor; se muestra salud/frescura como información derivada |
| Evidencia | PENDIENTE / REGISTRADA | El resultado de verificación tiene su enum independiente; no mezclar ambos |

La tabla sintetiza comportamientos del informe; los contratos posteriores de reservas y préstamos de este documento concretan sus transiciones autorizadas. Las guardas fijadas en Fase 0.5 se aplican desde su fase; solo los detalles enumerados como diferidos en DECISIONS deben cerrarse antes de implementar su punto dependiente. Véanse [BUSINESS_RULES.md](BUSINESS_RULES.md) y [DECISIONS.md](DECISIONS.md).

## Interpretación de seguridad cerrada en Fase 1.5

Usuario puede conservar varias asignaciones históricas y varios roles vigentes. Roles y permisos se evalúan como conjuntos, con un rol elegible para el CU que aporte el permiso; retirar un rol cierra sus asignaciones activas sin borrar historia. Los cuatro roles oficiales no incluyen a los actores técnicos externos. D-04 permite implementar capacidades/módulos en Fase 2; el vínculo fino Encargado–recurso se resolverá en su fase funcional sin inferir una entidad nueva ni autorización global.

SesionAcceso corresponde al control de una sesión web autenticada: hash SHA-256 del ID final, titular, creada_en, expira_en y revocada_en. No es una credencial reutilizable ni RegistroActividad. Varias sesiones por usuario son independientes; revocación/expiración y cuenta INACTIVO impiden acceso. La renovación por inactividad usa la misma duración configurada de Laravel y nunca reactiva una fila revocada/expirada. Las seis tablas de seguridad son el único núcleo funcional autorizado para la próxima fase; los demás conceptos permanecen documentales.

## Materialización de espacios — Fase 3

Piso, TipoEspacio y CaracteristicaEspacio conservan su estructura oficial. RecursoInstitucional representa el padre persistente y EspacioAcademico su subclase por PK/FK compartida recurso_id. EstadoEspacio materializa DISPONIBLE/BLOQUEADO/EN_MANTENIMIENTO. BloqueoEspacio conserva periodo, responsable Usuario y activo; no sustituye al estado general. Política es exclusivamente dependencia estructural, sin conducta de CU23. Características se asocian por belongsToMany/PK compuesta, sin nueva entidad física.

Los modelos, relaciones y límites vigentes están en [SPACES_IMPLEMENTATION.md](SPACES_IMPLEMENTATION.md). No se materializó Reserva, IoT ni los restantes componentes futuros.

## Transiciones de reserva — contrato de Fase 3.5

D-05 **RESUELTA PARA FASE 4**, sin modelo Reserva ni ciclo funcional creados en esta entrega. Conservar exactamente PENDIENTE, APROBADA, RECHAZADA, CANCELADA, LIBERADA y FINALIZADA. CU07 crea PENDIENTE; no hay aprobación implícita. Los únicos cambios de estado previstos para CU09/CU10 son:

| Estado origen | Acción | Estado destino | Actor autorizado | Guardas adicionales |
| --- | --- | --- | --- | --- |
| PENDIENTE | Aprobar, CU09 | APROBADA | Administrador/Encargado con reservas.resolver | Disponibilidad completa vigente y comprobación transaccional con lock del espacio |
| PENDIENTE | Rechazar, CU09 | RECHAZADA | Administrador/Encargado con reservas.resolver | Estado aún pendiente; motivo opcional; no requiere espacio disponible |
| PENDIENTE | Cancelar, CU10 | CANCELADA | Titular con reservas.cancelar_propias o Administrador/Encargado con reservas.cancelar_administrativas | Hora del servidor < inicio; propiedad cuando es cancelación propia |
| APROBADA | Cancelar, CU10 | CANCELADA | Titular con reservas.cancelar_propias o Administrador/Encargado con reservas.cancelar_administrativas | Hora del servidor < inicio; preservar responsable/fecha de resolución |

Toda acción requiere cuenta/sesión válida y permiso del rol elegible vigente, con revalidación bajo locks. No hay transición desde RECHAZADA, CANCELADA, LIBERADA o FINALIZADA en Fase 4, ni APROBADA→RECHAZADA, regreso a PENDIENTE, reapertura o edición arbitraria de estados. La guarda temporal también obliga al actor administrativo. Un conflicto al aprobar mantiene PENDIENTE y no es un rechazo.

Solo APROBADA compromete el intervalo `[inicio, fin)`. Varias PENDIENTE pueden compartir espacio/horario y competir por aprobación; no representan posesión provisional. Cancelar retira ese compromiso cuando existía, sin garantizar disponibilidad si persisten otros impedimentos. LIBERADA se reserva a CU23/Fase 8; FINALIZADA automática se difiere a una fase posterior autorizada. No se producen por reloj, cron ni elección manual en Fase 4.

La [definición completa](DECISIONS.md#contrato-de-reservas--cierre-d-05-de-fase-35) establece locks, consulta, código y matriz de permisos. CU08 distingue propiedad y consulta administrativa por módulo/acción; el ámbito fino de D-04 permanece diferido sin nuevas entidades de asignación. Ningún diagrama conceptual autoriza entidades adicionales en este cierre.

## Materialización de reservas — Fase 4

`Reserva`, `EstadoReserva`, sus cuatro relaciones y `EspacioAcademico.reservas` materializan el contrato anterior. El código inmutable se genera en el modelo; solicitud y transiciones específicas se ejecutan mediante `ReservationService`. Las cuatro transiciones congeladas están implementadas y verificadas; LIBERADA/FINALIZADA no tienen acción actual. [Evidencia](RESERVATIONS_IMPLEMENTATION.md).

## Ciclo de equipamiento y préstamos — contrato de Fase 4.5

**D-02/D-11 resueltas para Fase 5; implementación pendiente.** [Contrato completo](DECISIONS.md#contrato-de-equipamiento-y-préstamos--fase-45). Se mantienen los 19 ENUM oficiales y las entidades del diseño, sin clases/tablas implementadas en esta revisión.

EquipamientoTecnologico conserva la identidad del RecursoInstitucional mediante recurso_id PK+FK, tipo obligatorio y espacio_base 0..1. El equipo puede tener ubicación base sin estar permanentemente contenido allí: equipo→espacio→piso; no hay módulo/edificio/facultad. Serie opcional y única cuando existe. Crear padre y especialización es una sola operación atómica.

Las únicas transiciones de Prestamo en Fase 5 serán PENDIENTE→APROBADO, PENDIENTE→RECHAZADO, APROBADO→ENTREGADO y ENTREGADO→DEVUELTO. Solicitud pendiente no asigna exclusividad. APROBADO/ENTREGADO bloquean intervalos previstos solapados; equipo PRESTADO, EN_MANTENIMIENTO o BAJA impide nueva disponibilidad física. Vencido se calcula con `estado = ENTREGADO AND ahora > fin_previsto`, sin persistir VENCIDO ni terminar el préstamo. «Activo» se interpreta ENTREGADO y «finalizado» DEVUELTO; no se admite cancelación de préstamo por una palabra narrativa.

| Hito de dominio | Resultado congelado |
| --- | --- |
| Solicitud y resolución | Código servidor PRE-UUIDv4 único/inmutable; motivo de solicitud requerido; aprobar deja motivo_rechazo NULL; rechazar exige motivo según RF12. Conflicto de aprobación conserva PENDIENTE. |
| Entrega | Máximo una por préstamo APROBADO; receptor=solicitante, responsable de sesión, condición textual obligatoria. Préstamo ENTREGADO/equipo PRESTADO atómicos. recepcion_confirmada_en permanece NULL: campo/método de clase no autoriza un flujo digital posterior no descrito. |
| Retorno ADECUADA | Devolución CONFIRMADA, préstamo DEVUELTO, equipo DISPONIBLE solo sin otras restricciones. |
| Retorno CON_INCIDENCIA | Devolución CONFIRMADA, préstamo DEVUELTO y reporte/vínculo a IncidenciaMantenimiento; equipo EN_MANTENIMIENTO, preservando BAJA. |
| Retorno NO_DETERMINABLE | Devolución PENDIENTE_REVISION, préstamo ENTREGADO y mantenimiento preventivo, preservando BAJA; sin confirmador/fecha final. |
| Continuación de revisión | Administrador/Encargado autorizado actualiza la misma devolución a condición ADECUADA/CON_INCIDENCIA y CONFIRMADA con actor/fecha; no segundo retorno ni CU nuevo. |
| Incidencia/mantenimiento | Recurso común; tipos INCIDENCIA/MANTENIMIENTO y ABIERTA/CERRADA. Todas las ABIERTA son incompatibles en Fase 5; fin previsto no las cierra. |
| Recuperación del equipo | BAJA prevalece; luego otra ABIERTA o revisión pendiente→EN_MANTENIMIENTO; luego préstamo ENTREGADO→PRESTADO; libre de esas causas puede DISPONIBLE tras confirmar aptitud. No habilitación automática al cerrar. |
| Recuperación del espacio | Considerar otras ABIERTA, bloqueos activos por periodo y estado administrativo. Cierre no reconstruye causa previa: habilitación explícita con permiso y validación; mantener restricción si falta esa confirmación. |

D-04 aplica permiso por módulo/acción y propiedad del solicitante; no relación fina responsable–recurso inventada. D-05 conserva [inicio, fin), fin>inicio y lecturas actuales tras lock; la prioridad de revisión sobre PRESTADO distingue custodia pendiente de cierre y condición física recibida. BAJA es decisión administrativa explícita, no efecto automático del daño. Los permisos específicos y el protocolo único se encuentran en DECISIONS. QR/Blockchain/notificaciones/bitácora mantienen sus fases posteriores.

## Materialización de Fase 5

Los seis modelos y enums de equipamiento/préstamos/hitos/incidencias están implementados conforme al contrato anterior. Equipamiento comparte recurso_id con RecursoInstitucional y deriva piso por espacio base. Receptor de entrega es el solicitante; recepción física y confirmación de devolución permanecen separadas sobre una sola fila. NO_DETERMINABLE conserva ENTREGADO y revisión pendiente; VENCIDO solo se calcula. BAJA no se retira al devolver o cerrar incidencias. [Estados, relaciones y transiciones implementadas](EQUIPMENT_LOANS_IMPLEMENTATION.md#persistencia-y-dominio). No se materializan conceptos de Fase 6.

## Contrato QR cerrado en Fase 5.5

D-09 está [RESUELTA PARA FASE 6](DECISIONS.md#readiness-for-phase-6), sin modelos ni enums PHP implementados. CodigoQR identifica exactamente un recurso (espacio/equipo), reserva o préstamo; no hay tipo_qr. Activo y ventana [generada_en,vence_en), con NULL permitido, determinan vigencia; máximo un activo por objetivo exacto por regla transaccional, sin consumo al escanear. Revocar conserva historia y rotar crea otra identidad/token.

ValidacionQR registra VALIDA/INVALIDA y USO_ESPACIO/ENTREGA/DEVOLUCION, hash leído, actor/reloj/contexto y QR reconocido opcional. El recurso esperado se deriva de reserva/préstamo. CU11 exige APROBADA en [inicio,fin), propia o administrativa autorizada, y no cambia estados. CU20 amplía opcionalmente CU17/CU18; retorno con restricción es posible y NO_DETERMINABLE conserva su semántica. La validación nueva se enlaza al hito de la misma petición/transacción; una revisión posterior conserva ese enlace. Invalidar no modifica entidades operativas. [Ciclo, correspondencia y correlación exactos](DECISIONS.md#contextos-y-correspondencia-exactos).

## Materialización QR de Fase 6

CodigoQr/ValidacionQr, relaciones belongsTo/hasMany y enums ResultadoValidacionQr/TipoOperacionQr implementados. Sin estados extra ni timestamps automáticos. Hashes ocultos, fechas inmutables, factories sintéticas. CU11 solo evidencia; CU20 conserva estados y servicios CU17/CU18. [Ciclo, relaciones, pruebas y límite de aceptación](QR_IMPLEMENTATION.md). Las declaraciones de ausencia de modelos en Fases 5/5.5 son históricas.

## Contrato de dominio IoT — Fase 6.5

[D-10 cerrada, implementación pendiente](DECISIONS.md#readiness-for-phase-7). SensorIoT conserva identificador UNIQUE, inmutable desde alta, tipo descriptivo, espacio_actual obligatorio y HABILITADO/DESHABILITADO. El dispositivo usa HMAC, nunca Usuario/Rol/SesionAcceso. Reasociación cambia solo el presente; no eliminación física.

EventoOcupacion describe ambiente, nunca identidad/asistencia: PRESENCIA/OCUPACION, valor DECIMAL(10,2) nullable sin interpretación universal, fecha_evento declarada normalizada y recepción servidor. Espacio histórico se toma de asociación validada al aceptar. Histórico inmutable, sin edición UI; tupla sensor/espacio/tipo/valor NULL-safe/fecha_evento identifica duplicados exactos.

Ultima_comunicacion cambia solo con evento nuevo aceptado; una retransmisión no renueva este campo. Ambas edades de evento/recepción deben cumplir ventana de monitoreo, sensor habilitado y contexto espacial vigente para lectura actual. Datos insuficientes/antiguos/contradictorios → NO_DETERMINABLE; no equivale a LIBRE. Conectividad y lectura reciente son cálculos; nunca ONLINE/OFFLINE/ERROR persistidos. CU23 y su evaluación siguen en Fase 8, sin alterar reserva/espacio por eventos en Fase 7.

## Contrato documental de liberación — Fase 7.5

[D-08 resuelta para Fase 8](DECISIONS.md#readiness-for-phase-8). PoliticaLiberacion contiene modo; EvaluacionLiberacion contiene resultado y referencia a la versión exacta. Reserva no recibe FK de política ni snapshot. Espacio conserva asociación opcional; cambiarla se prohíbe con APROBADA fin>T. Cada evaluación tiene una reserva y una política obligatorias, y cero o más eventos/validaciones mediante los dos pivotes oficiales.

MANTENER_POR_USO, NO_DETERMINABLE y ALERTAR conservan APROBADA; solo LIBERAR en AUTOMATICA permite APROBADA→LIBERADA, sin mutar estado operativo. Terminal de evaluación no significa estado terminal de reserva. Versiones y evidencia evaluada permanecen inmutables; desactivar afecta decisiones futuras. CU23 interpreta PRESENCIA/OCUPACION exclusivamente bajo D-08, sin cambiar CU22 genérico ni crear identidades de ocupantes. No hay nuevas clases implementadas en 7.5.


## Actualización de Fase 7

SensorIot y EventoOcupacion materializan las relaciones de asociación actual e historia al aceptar. Estados persistidos HABILITADO/DESHABILITADO y tipos PRESENCIA/OCUPACION exactos. NO_DETERMINABLE y comunicación reciente son resultados de consulta, no nuevos estados persistidos. No inferir personas, desocupación ni liberación. [Implementación y verificación](IOT_IMPLEMENTATION.md).

## Registro crítico y evidencia complementaria — Fase 8.5

[D-12](DECISIONS.md#contrato-blockchain--cierre-d-12-de-fase-85) delimita RegistroActividad a cuatro escrituras críticas en Fase 9: RESERVA_APROBADA/CU09, RESERVA_CANCELADA/CU10, PRESTAMO_ENTREGADO/CU17 y PRESTAMO_DEVUELTO/CU18 confirmado. resultado EXITO y critica TRUE; actividad atómica con la transición/hito. No se añade entidad ni se implementa CU30 general por anticipado.

EvidenciaTrazabilidad se relaciona obligatoriamente con una actividad y esta tiene 0..1 evidencia por UNIQUE; el reconciliador completa la evidencia faltante tras commit. Algoritmo/versión/hash identifican la representación histórica, no el estado actual de la reserva o préstamo. Aprobación verificable tras CANCELADA/LIBERADA; entrega verificable tras devolución; una revisión pendiente no produce aún devolución crítica.

Estados persistidos siguen exactamente PENDIENTE y REGISTRADA; resultado de verificación sigue VALIDO, INCONSISTENTE, PENDIENTE y NO_VERIFICABLE. Las acciones críticas son valores exactos de accion VARCHAR, no un enum SQL nuevo. Hash/ref no son credenciales ni confieren autorización. Registry es un componente externo mínimo, no otra entidad MySQL.

LIBERADA/CU23, QR individual, IoT, rechazo, aprobación de préstamo e incidencias/mantenimiento quedan fuera del conjunto externo inicial. Sus historias y estados vigentes se mantienen. Los esquemas por acción y tipos se centralizan en D-12 para evitar variantes; campos de los hitos usados por esos esquemas quedan inmutables tras su confirmación. No se crea snapshot JSON.

## Subconjunto de trazabilidad implementado en Fase 9

RegistroActividad se usa únicamente para RESERVA_APROBADA, RESERVA_CANCELADA, PRESTAMO_ENTREGADO y PRESTAMO_DEVUELTO exitosos/humanos. Cada actividad tiene 0..1 EvidenciaTrazabilidad; los hitos históricos reconstruyen el hash sin depender del estado actual. Pendiente/registrada describe registro externo; válido/inconsistente/pendiente/no verificable describe la última comprobación, conceptos separados. [Schemas y exclusiones](BLOCKCHAIN_IMPLEMENTATION.md#canónico-y-privacidad). No se declara completado el dominio de CU30 general ni notificaciones.

## Contrato de Notificacion — cierre documental de Fase 9.5

[RF21/CU26, N1–N9](DECISIONS.md#notification-contract--readiness-for-phase-10) define Notificacion como mensaje histórico interno: pertenece a un Usuario destinatario y obligatoriamente a un RegistroActividad. Una actividad puede originar cero o varias notificaciones y, si es una de las cuatro críticas D-12, su evidencia independiente. La notificación no cambia el evento, concede permisos ni sustituye el objeto de negocio.

Tipos exactos: SOLICITUD, DECISION, CANCELACION, ENTREGA, DEVOLUCION, VENCIMIENTO, INCIDENCIA y LIBERACION. Las 17 acciones N3 distinguen hechos del dominio de tipos de presentación: MANTENIMIENTO_* se presenta como INCIDENCIA; ALERTAR y LIBERADA como LIBERACION; recepción pendiente de revisión y devolución confirmada como DEVOLUCION, con semánticas distintas. VENCIDO sigue siendo condición calculada de ENTREGADO/fin_previsto, nunca estado adicional. Un rechazo confirmado tiene resultado de actividad EXITO.

La identidad de destinatarios directos deriva del evento (solicitante/receptor/afectado por devolución). Los grupos administrativos son una regla de permisos efectivos, no una entidad nueva ni un snapshot de usuarios. El cierre del conjunto y la ventana de recuperación están definidos en N6. leida_en NULL/con fecha expresa no leída/leída; título y mensaje son snapshot humano inmutable. No añadir estados de entrega/procesamiento, preferencia de canal, conversación, inbox global o usuario Sistema. CU30 general y toda implementación de Fase 10 siguen diferidos.

## Notificaciones materializadas — Fase 10

Notificacion y TipoNotificacion implementan snapshot de ocho tipos y primera lectura. PRESTAMO_VENCIDO es actividad, no nuevo estado; MANTENIMIENTO_* usa tipo INCIDENCIA. Estados y custodia previos conservados. [Implementación y evidencia](NOTIFICATIONS_IMPLEMENTATION.md).

## Contrato de indicadores y reportes — Fase 10.5

[Contrato DR1–DR12](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11). Dashboard, indicador, filtro y definición de reporte son conceptos de consulta/presentación, no entidades persistidas nuevas. Su fuente autoritativa sigue siendo el modelo operacional. No Data Warehouse, Data Mart, tablas agregadas, snapshots diarios o motor BI paralelo.

Las diez tarjetas/seis gráficos administrativos usan permisos del dominio; las siete tarjetas Docente/Estudiante usan solicitante_id/destinatario_id propios. DashboardService y ReportDefinition son responsabilidades futuras de aplicación, no tablas ni código creado ahora. La distribución de estados de EspacioAcademico no equivale a disponibilidad de un intervalo; esta se consulta mediante SpaceAvailabilityService. APROBADA activa exige inicio<=t<fin; vencido es ENTREGADO y t>fin_previsto. Incidencia abierta sigue siendo ABIERTA; fin_previsto no acredita cierre. Ningún enum cambia.

Monitoreo reciente no vuelve universal el valor de EventoOcupacion: NO_DETERMINABLE es resultado de consulta, no estado persistido. La liberación del periodo se acredita por EvaluacionLiberacion LIBERAR/fecha_hora y reserva actual LIBERADA. No se usa la fecha de resolución para inferir liberación. CU26 aporta el contador propio existente sin marcar lectura.

Siete reportes: reservas, disponibilidad/espacios (dos vistas), equipamiento, préstamos/vencimientos, incidencias/mantenimiento, IoT histórico y resumen operacional. Preview y PDF/XLSX comparten filtros, scopes, columnas y orden. Reporte operacional agrupa actividades autorizadas; no concede CU30. REPORTE_EXPORTADO será una actividad humana no crítica, sin entidad de reporte, evidencia Blockchain ni notificación. **Fase 11 no iniciada; CU30 NO IMPLEMENTADO.**

## Lecturas operacionales de Fase 11

CU27/CU28 consultan las entidades existentes con scopes/permisos, sin entidad persistida Dashboard/Reporte/Métrica. ReportDefinition y ReportPeriod son objetos de consulta. Vencido, disponibilidad y NO_DETERMINABLE continúan derivados. REPORTE_EXPORTADO agrega un valor de acción al catálogo de productores de RegistroActividad (columna textual existente), no un estado de reserva/préstamo ni evidencia Blockchain. [Implementación](DASHBOARD_REPORTING_IMPLEMENTATION.md).


## Contrato de orientación RF24/CU29 — Fase 11.5

[CH1–CH12](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12) no añade entidades persistentes ni estados de reserva/préstamo/IoT. ChatbotProvider, ChatbotKnowledgeBase, ChatbotRouteRegistry y ChatbotService son responsabilidades técnicas futuras. ANSWER/OUT_OF_SCOPE/RESTRICTED pertenecen a un DTO efímero de asistencia, no a enums del dominio ni a tablas nuevas. El catálogo local tiene entradas procedimentales con topic/text/required_permissions/allowed_route_keys, id/versión/fuente; no registros de usuarios o recursos.

El usuario ACTIVO con sesión vigente y chatbot.consultar puede solicitar orientación. Capabilities deriva de permisos efectivos D-04; rol no concede bypass. El modelo no obtiene identidad, propiedad ni filas de operaciones. «Mis reservas» significa orientar a la lista autorizada, no establecer una relación consulta-chat→Reserva. Se conservan conocimiento de estados y reglas de módulos existentes, sin evaluar disponibilidad, vencimientos, políticas o Blockchain desde el asistente. D-14 prohíbe conversación persistida; el historial visual es memoria de la página. CU30 sigue NO IMPLEMENTADO.

## Orientación implementada — Fase 12

ChatbotContext es efímero y no relaciona consultas con registros operativos. ChatbotService/Provider/KnowledgeBase/RouteRegistry implementados sin tablas ni enums nuevos. ANSWER/OUT_OF_SCOPE/RESTRICTED solo describen respuesta de asistencia; no modifican negocio. 32 fragmentos y 20 destinos autorizados orientan sobre los módulos existentes. [Implementación RF24/CU29](CHATBOT_IMPLEMENTATION.md). CU30 NO IMPLEMENTADO; D-14 conserva ausencia de conversación persistida.

## RegistroActividad — cierre documental Fase 12.5

[Activity Audit Contract / Readiness for CU30](DECISIONS.md#activity-audit-contract--readiness-for-cu30): RegistroActividad sigue siendo historia mínima inmutable, responsable humano o proceso (CHECK OR, no XOR), resultado EXITO/RECHAZO/ERROR y contexto por FK. Actor no equivale a usuario afectado; se admiten varias relaciones simultáneas. Actor inactivo conserva historia. Los 18 hitos actuales permanecen; 38 acciones previstas cubren administración/configuración realmente disponibles. No nuevas entidades ni historial de chat; no rehabilitación de usuarios inexistente.

Para roles/catálogos/sensores/QR/bloqueos/políticas sin FK propia se usa referencia técnica mínima en plantilla, sin parsearla como vínculo o autorización. tipo_entidad será derivado de las FK, no persistido. Ver AC3/AC4/AC7 para responsabilidad, disparador y asociaciones exactas; AC6 limita fallos. CU30 NO IMPLEMENTADO; no cambia ningún estado operativo ni D-12.


## Implementación RF25/CU30 — Fase 12.6

RegistroActividad conserva trece campos, responsable OR, contexto FK e inmutabilidad. 56 acciones en ActivityAction; 38 nuevas no críticas, sin nuevas operaciones de dominio. Noop/transición efectiva y precedencia AC4/AC12; nombres actuales no snapshot. CU30 consulta individual; RF23 conserva agregado y CU29 no obtiene actividad. [Implementación, evidencia y límites](ACTIVITY_AUDIT_IMPLEMENTATION.md).
