# Modelo del dominio

Fuentes: figura 19 (p. 109 / PDF 110), figuras 108–109 (p. 248–249 / PDF 249–250), mapeo, volúmenes y SQL del cap. VII. El diagrama conceptual simplifica relaciones; las diferencias con el diseño de clases y datos están registradas en D-06. Estado: especificación, sin clases implementadas.

## Conceptos y responsabilidades

| Concepto del diseño | Tabla de correspondencia | Responsabilidad / invariante |
| --- | --- | --- |
| Usuario | `usuarios` | Correo único; contraseña como hash; desactivar conservando historia. |
| Rol | `roles` | Nombre único; no confundir rol con permiso ni con el ámbito de recursos. |
| Permiso | `permisos` | Código único pretendido; discrepancia código/codigo bloquea su migración (D-01). |
| Asociación Rol–Permiso | `roles_permisos` | PK compuesta evita repetir la misma asociación rol–permiso. |
| AsignacionRol | `asignaciones_roles` | Conservar usuario, rol, asignador, fechas y retirador opcional; aclarar duplicados activos. |
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
| Reserva | `reservas` | Código único; solicitante/espacio obligatorios; fin>inicio; impedir solapamiento en servidor y transacción. |
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
| Docente y Estudiante | Solicitudes, seguimiento propio, validación y asistencia habilitada | Propiedad y permisos; RF/CU difieren en actores adicionales: D-03 |
| Dispositivo IoT | Envía eventos de presencia/ocupación | No identifica personas ni decide autorizaciones |
| Servicio Blockchain | Registra/consulta evidencia mínima | No es la base operativa ni un aprobador de reservas |
| Proveedor de chatbot | Proporciona orientación a través del servidor | No ejecuta acciones protegidas |

No incorporar “Facultad” como entidad para múltiples instituciones. El contexto único es la FICCT.

## Valores persistidos exactos

Los siguientes valores proceden de `ENUM` del SQL y se contrastan con la figura 108. Las etiquetas visibles se traducen a español comprensible sin cambiar el valor almacenado.

| Tabla | Columna | Valores permitidos |
| --- | --- | --- |
| `usuarios` | `estado` | `ACTIVO, INACTIVO` |
| `politicas_liberacion` | `modo` | `ALERTA, AUTOMATICA` |
| `espacios_academicos` | `estado` | `DISPONIBLE, BLOQUEADO, EN_MANTENIMIENTO` |
| `equipamientos_tecnologicos` | `estado` | `DISPONIBLE, PRESTADO, EN_MANTENIMIENTO, BAJA` |
| `reservas` | `estado` | `PENDIENTE, APROBADA, RECHAZADA, CANCELADA, LIBERADA, FINALIZADA` |
| `prestamos` | `estado` | `PENDIENTE, APROBADO, RECHAZADO, ENTREGADO, DEVUELTO` |
| `devoluciones_prestamo` | `condicion` | `ADECUADA, CON_INCIDENCIA, NO_DETERMINABLE` |
| `devoluciones_prestamo` | `estado` | `PENDIENTE_REVISION, CONFIRMADA` |
| `incidencias_mantenimiento` | `tipo` | `INCIDENCIA, MANTENIMIENTO` |
| `incidencias_mantenimiento` | `estado` | `ABIERTA, CERRADA` |
| `validaciones_qr` | `resultado` | `VALIDA, INVALIDA` |
| `validaciones_qr` | `tipo_operacion` | `USO_ESPACIO, ENTREGA, DEVOLUCION` |
| `sensores_iot` | `estado` | `HABILITADO, DESHABILITADO` |
| `eventos_ocupacion` | `tipo` | `PRESENCIA, OCUPACION` |
| `evaluaciones_liberacion` | `resultado` | `MANTENER_POR_USO, NO_DETERMINABLE, ALERTAR, LIBERAR` |
| `registros_actividad` | `resultado` | `EXITO, RECHAZO, ERROR` |
| `evidencias_trazabilidad` | `estado` | `PENDIENTE, REGISTRADA` |
| `evidencias_trazabilidad` | `resultado_verificacion` | `VALIDO, INCONSISTENTE, PENDIENTE, NO_VERIFICABLE` |
| `notificaciones` | `tipo` | `SOLICITUD, DECISION, CANCELACION, ENTREGA, DEVOLUCION, VENCIMIENTO, INCIDENCIA, LIBERACION` |

Políticas: `modo` tiene los valores indicados; `activa`, `requiere_qr` y `usa_iot` son booleanos, no enums de estado. `tolerancia_min` y `max_antiguedad_lectura_min` regulan la evaluación; el segundo admite NULL en el SQL. QR: `activo` es booleano y la vigencia se deriva de sus fechas. Notificación leída/no leída se deriva de `leida_en`; sesión vigente se deriva de expiración/revocación.

“Ocupación no determinable” es un resultado conceptual de monitoreo y un valor de evaluación; no agregarlo al enum de `espacios_academicos.estado`. “Ocupado” y “reservado” describen disponibilidad derivada, no estados persistidos adicionales del espacio. “Vencido” puede ser una condición temporal derivada del préstamo; no existe en `prestamos.estado`. CU17/CU18 mencionan “activo”, “finalizado” y “cancelado” en prosa, pero esos valores no están en ese enum (D-02).

## Ciclos de vida y reglas de transición

| Objeto | Comportamiento respaldado | Guarda / precisión pendiente |
| --- | --- | --- |
| Reserva | Registro PENDIENTE; resolución APROBADA o RECHAZADA; cancelación CANCELADA; política puede producir LIBERADA; existe FINALIZADA | Resolver D-05: estados cancelables, límites y mecanismo exacto de finalización. No confundir activa con un enum |
| Préstamo | Solicitud PENDIENTE; resolución APROBADO o RECHAZADO; entrega ENTREGADO; devolución confirmada DEVUELTO | Receptor, identificación y recurso disponible; no agregar CANCELADO/ACTIVO/FINALIZADO |
| Equipamiento | Entrega cambia a PRESTADO; retorno adecuado puede restituir DISPONIBLE; mantenimiento/baja impiden operaciones | Condición desconocida o incidencia no debe devolver disponibilidad automáticamente |
| Devolución | PENDIENTE_REVISION o CONFIRMADA, con condición separada | No cerrar definitivamente una devolución cuya condición sea NO_DETERMINABLE |
| Incidencia/mantenimiento | ABIERTA; cierre CERRADA con solución/responsable | Recurso recupera disponibilidad solo si no hay otra restricción incompatible |
| Sensor | HABILITADO / DESHABILITADO | Falta de comunicación no crea un tercer valor; se muestra salud/frescura como información derivada |
| Evidencia | PENDIENTE / REGISTRADA | El resultado de verificación tiene su enum independiente; no mezclar ambos |

La tabla describe comportamientos observados, no completa una máquina de estados que el informe no define. Las transiciones de frontera deben resolverse antes de sus pruebas de implementación. Véanse [BUSINESS_RULES.md](BUSINESS_RULES.md) y [DECISIONS.md](DECISIONS.md).
