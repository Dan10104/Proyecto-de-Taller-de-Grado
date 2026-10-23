# Modelo del dominio

Fuentes: figura 19 (p. 109 / PDF 110), figuras 108–109 (p. 248–249 / PDF 249–250), mapeo, volúmenes y SQL del cap. VII. El diagrama conceptual simplifica relaciones; las diferencias con el diseño de clases y datos están registradas en D-06. Estado: contrato congelado en Fase 0.5; materialización incremental hasta Fase 3 descrita al final. Las entidades de fases posteriores siguen siendo documentales.

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
| PoliticaLiberacion | `politicas_liberacion` | Nombre/version únicos; tolerancia y antigüedad no negativas; versión histórica pendiente de precisar. |
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
| `prestamos.estado` | `PENDIENTE`, `APROBADO`, `RECHAZADO`, `ENTREGADO`, `DEVUELTO` | Pendiente de entrega = APROBADO; entregado/activo/pendiente de devolución = ENTREGADO; finalizado por devolución confirmada = DEVUELTO. Cancelado no tiene equivalente automático ni CU de cancelación. Vencido es ENTREGADO sin devolución al superar fin_previsto, condición derivada. |
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
| PoliticaLiberacion | activa, requiere_qr, usa_iot booleanos; modo separado | Habilitación y selección de evidencias no son nuevos enums. La antigüedad máxima admite NULL; su semántica precisa es D-08. |
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

La tabla describe comportamientos observados, no completa una máquina de estados que el informe no define. Las guardas fijadas en Fase 0.5 se aplican desde su fase; solo los detalles enumerados como diferidos en DECISIONS deben cerrarse antes de implementar su punto dependiente. Véanse [BUSINESS_RULES.md](BUSINESS_RULES.md) y [DECISIONS.md](DECISIONS.md).

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
