# Matriz CU01–CU30

**Estado vigente — Fase 8.5 documental cerrada (2026-10-25).** D-12 RESUELTA PARA FASE 9; [Readiness for Phase 9](DECISIONS.md#readiness-for-phase-9), **0 bloqueantes documentales**. La implementación sigue en Fase 8; Fase 9 no iniciada. El contrato nuevo se distingue de las fotografías históricas que se conservan debajo.

**Estado al cierre funcional de Fase 8 (2026-10-24).** CU23 implementado como proceso interno programado, extensión temporal de CU22 sobre evidencia persistida; no requiere actor humano ni evento entrante. Versiones, ventanas, pivotes, matriz, idempotencia y concurrencia comprobados. RegistroActividad de la ficha original y notificaciones continúan pendientes de sus fases. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

**Cierre histórico de Fase 7.5:** [D-08 cerrada, 0 bloqueantes para Fase 8](DECISIONS.md#readiness-for-phase-8). CU23 permanece sin implementación; su contrato futuro incluye matriz completa, fuentes opcionales con veto, scheduler, idempotencia, versiones y concurrencia. No declara cumplidas las notificaciones ni CU30 del flujo completo.

Cobertura: 30/30 casos de uso del cap. V, tablas 38–67; prioridad según tabla 37. Cada ficha reúne iniciador, actores, precondición, flujo, alternativas, excepciones, resultado, RF, entidades/tablas, paquete/fase y pruebas. La asignación RF→CU→tabla→fase es trazabilidad analítica basada en los flujos y diagramas; no crea nuevas funciones.

**Alcance web:** las menciones a aplicación móvil en el texto fuente describen el sistema global. En estas fases se implementará la variante web y el soporte de servidor aplicable, sin Flutter/Android. “Activo”, “finalizado”, “vencido” o “cancelado” en la prosa del préstamo no se traducen automáticamente a un enum; aplicar la reconciliación D-02 resuelta en DOMAIN_MODEL. Las fichas conservan criterios del diseño completo; los estados y evidencias de implementación se precisan por fase. Fase 1.5 cierra D-04/D-07 documentalmente y delimita el próximo núcleo de seguridad; véase el contrato al final.

## Índice

| CU | Nombre | Prioridad | RF principal/relacionado | Fase principal |
| --- | --- | --- | --- | --- |
| CU01 | Iniciar sesión | Alta | RF1 | 2 |
| CU02 | Cerrar sesión | Alta | RF1 | 2 |
| CU03 | Gestionar usuarios | Alta | RF2 | 2 |
| CU04 | Gestionar roles y permisos | Alta | RF3 | 2 |
| CU05 | Gestionar espacios académicos | Alta | RF4 | 3 |
| CU06 | Consultar disponibilidad de espacios académicos | Alta | RF5 | 3 |
| CU07 | Registrar solicitud de reserva | Alta | RF6 | 4 |
| CU08 | Consultar estado de reservas | Media | RF6, RF7 | 4 |
| CU09 | Resolver solicitud de reserva | Alta | RF7 | 4 |
| CU10 | Cancelar reserva | Alta | RF7 | 4 |
| CU11 | Validar uso de espacio mediante código QR | Alta | RF8 | 6 |
| CU12 | Gestionar equipamiento tecnológico | Alta | RF9 | 5 |
| CU13 | Consultar disponibilidad de equipamiento tecnológico | Alta | RF10 | 5 |
| CU14 | Registrar solicitud de préstamo | Alta | RF11 | 5 |
| CU15 | Consultar estado de préstamos | Media | RF11, RF12, RF13, RF14 | 5 |
| CU16 | Resolver solicitud de préstamo | Alta | RF12 | 5 |
| CU17 | Registrar entrega de equipamiento tecnológico | Alta | RF13 | 5 |
| CU18 | Registrar devolución de equipamiento tecnológico | Alta | RF14 | 5 |
| CU19 | Gestionar incidencias y mantenimiento | Media | RF15 | 5 |
| CU20 | Validar operación de equipamiento mediante código QR | Alta | RF13, RF14 | 6 |
| CU21 | Gestionar sensores IoT | Alta | RF16 | 7 |
| CU22 | Monitorear ocupación de espacios académicos | Alta | RF17 | 7 |
| CU23 | Aplicar política de liberación de reserva | Media | RF18 | 8 |
| CU24 | Generar evidencia de trazabilidad mediante Blockchain | Alta | RF19 | 9 |
| CU25 | Verificar evidencia de trazabilidad | Alta | RF20 | 9 |
| CU26 | Gestionar notificaciones | Media | RF21 | 10 |
| CU27 | Visualizar dashboard e indicadores | Media | RF22 | 11 |
| CU28 | Generar y exportar reportes | Media | RF23 | 11 |
| CU29 | Consultar chatbot de asistencia | Media | RF24 | 12 |
| CU30 | Consultar bitácora de actividad | Media | RF25 | Pendiente de autorización de bitácora; alcance ajustado en Fase 1.5 |

## Relaciones de los diagramas

CU07 y CU09 incluyen CU06; CU14 y CU16 incluyen CU13. CU20 extiende CU17/CU18 cuando la política exige QR; CU19 extiende CU18 al detectar anomalías; CU23 extiende CU22; CU24 extiende condicionalmente CU09, CU10, CU17 y CU18 cuando la operación es crítica. Un include representa comportamiento reutilizado; un extend es condicional y no exige ejecutarlo en todos los casos. RF19 enuncia además un conjunto configurable de operaciones críticas: D-12 debe fijar ese conjunto antes de ampliarlo.

Las fichas citan la página inicial impresa; el índice PDF incluye la portada, por lo que es una página mayor. Las excepciones y pre/postcondiciones conservan el sentido de las tablas oficiales, incluidas las ambigüedades abiertas. Ningún estado de tabla se modifica para adaptar una frase del CU.

## CU01 — Iniciar sesión

Fuente: tabla 38; p. 175 (PDF 176); figura 29; análisis figura 75.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Usuario registrado. |
| Actores | Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Precondiciones | El usuario debe contar con una cuenta registrada y activa. El sistema y el servicio de autenticación deben encontrarse disponibles. |
| Flujo principal (síntesis) | Ingresar credenciales; validar campos, identidad y cuenta; obtener roles/permisos; crear sesión; presentar opciones autorizadas. |
| Alternativas / extensiones | Credenciales incorrectas o cuenta inactiva: interrumpir sin revelar información sensible. |
| Excepciones documentadas | Credenciales incorrectas: el sistema rechaza el acceso y permite un nuevo intento. Cuenta inexistente, inactiva o deshabilitada: no se crea la sesión. Servicio no disponible: se informa la indisponibilidad temporal sin crear una sesión parcial. Token o sesión inválida: el usuario debe autenticarse nuevamente. |
| Postcondición documentada | El usuario queda autenticado con una sesión válida y acceso limitado por sus roles y permisos. |
| RF relacionado | RF1 |
| Entidades / clases principales | Usuario, Rol, Permiso, Asociación Rol–Permiso, AsignacionRol, SesionAcceso |
| Tablas directamente relacionadas | `usuarios`, `roles`, `permisos`, `roles_permisos`, `asignaciones_roles`, `sesiones_acceso` |
| Módulo futuro y fase | Seguridad y Administración; Fase 2 |
| Pruebas necesarias | T-CU01: Cuenta activa accede; inactiva/contraseña errónea no; sesión expirada rechazada; ninguna credencial en respuesta/log. |

## CU02 — Cerrar sesión

Fuente: tabla 39; p. 176 (PDF 177); figura 30; análisis figura 76.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Usuario autenticado. |
| Actores | Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Precondiciones | El usuario debe tener una sesión o token de acceso activo. |
| Flujo principal (síntesis) | Identificar la sesión activa; invalidarla en servidor; eliminar información temporal de autenticación del cliente; volver al acceso; exigir nueva autenticación. |
| Alternativas / extensiones | Sesión ya expirada: volver al acceso. Si falla comunicación, limpiar credenciales locales y exigir autenticación futura; revisar garantía de revocación (D-07). |
| Excepciones documentadas | Sesión expirada previamente: se redirige al acceso sin intentar reutilizarla. Fallo de comunicación durante el cierre: la aplicación elimina las credenciales locales y solicita una nueva autenticación para futuros accesos. |
| Postcondición documentada | La sesión queda finalizada y el usuario ya no puede acceder a funciones protegidas con la sesión anterior. |
| RF relacionado | RF1 |
| Entidades / clases principales | Usuario, SesionAcceso |
| Tablas directamente relacionadas | `usuarios`, `sesiones_acceso` |
| Módulo futuro y fase | Seguridad y Administración; Fase 2 |
| Pruebas necesarias | T-CU02: Token anterior rechazado tras cierre confirmado; sesión expirada; caída de red y revocación según D-07. |

## CU03 — Gestionar usuarios

Fuente: tabla 40; p. 177 (PDF 178); figura 31; análisis figura 77.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador. |
| Actores | Administrador. |
| Precondiciones | El Administrador debe encontrarse autenticado. El Administrador debe contar con permisos de gestión de usuarios. |
| Flujo principal (síntesis) | Consultar usuarios; registrar o editar datos; comprobar campos/duplicidad y permisos; asignar lo autorizado o cambiar estado; guardar y registrar actividad. |
| Alternativas / extensiones | Datos incompletos/duplicados: corregir. Desactivar usuario conserva operaciones históricas. |
| Excepciones documentadas | Identificador o correo duplicado: no se registra la nueva cuenta. Datos obligatorios incompletos o inválidos: se solicita su corrección. Usuario inexistente al intentar modificarlo: se cancela la operación. Permisos insuficientes: el sistema bloquea la gestión. |
| Postcondición documentada | La cuenta queda registrada, modificada o deshabilitada de acuerdo con la operación realizada. |
| RF relacionado | RF2 |
| Entidades / clases principales | Usuario, Rol, AsignacionRol, RegistroActividad |
| Tablas directamente relacionadas | `usuarios`, `roles`, `asignaciones_roles`, `registros_actividad` |
| Módulo futuro y fase | Seguridad y Administración; Fase 2 |
| Pruebas necesarias | T-CU03: Alta sintética válida; correo duplicado; campos inválidos; edición ajena; baja conserva historial. |

## CU04 — Gestionar roles y permisos

Fuente: tabla 41; p. 179 (PDF 180); figura 32; análisis figura 78.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador. |
| Actores | Administrador. |
| Precondiciones | El Administrador debe encontrarse autenticado y autorizado para administrar roles y permisos. Deben existir usuarios registrados cuando se realicen asignaciones. |
| Flujo principal (síntesis) | Consultar roles/permisos; seleccionar alta, modificación o asignación; validar coherencia y autorización; persistir relación y registrar actividad. |
| Alternativas / extensiones | Asignaciones o datos incompatibles: rechazar sin afectar relaciones vigentes; conservar historia de asignación/retiro. |
| Excepciones documentadas | Rol duplicado o con nombre inválido: no se guarda. Permiso inexistente o configuración incompleta: se solicita corregir la selección. Usuario inexistente: no se realiza la asignación. Permisos insuficientes del Administrador: la operación es rechazada. |
| Postcondición documentada | Los roles, permisos y asignaciones quedan actualizados y disponibles para el control de acceso. |
| RF relacionado | RF3 |
| Entidades / clases principales | Usuario, Rol, Permiso, Asociación Rol–Permiso, AsignacionRol, RegistroActividad |
| Tablas directamente relacionadas | `usuarios`, `roles`, `permisos`, `roles_permisos`, `asignaciones_roles`, `registros_actividad` |
| Módulo futuro y fase | Seguridad y Administración; Fase 2 |
| Pruebas necesarias | T-CU04: Asignar/retirar rol; cambio de permiso efectivo; acceso denegado; no escalada propia sin autorización; historia íntegra. |

## CU05 — Gestionar espacios académicos

**Estado Fase 3: núcleo funcional implementado y probado; integración de bitácora CU30 diferida.** RBAC por módulo/acción según petición §35; ámbito fino por recurso pendiente. [Código y evidencia](SPACES_IMPLEMENTATION.md).

Fuente: tabla 42; p. 180 (PDF 181); figura 33; análisis figura 79.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador o Encargado de Laboratorio autorizado. |
| Actores | Administrador y Encargado de Laboratorio. |
| Precondiciones | El actor debe encontrarse autenticado. El actor debe contar con permisos sobre los espacios académicos que desea gestionar. |
| Flujo principal (síntesis) | Consultar catálogo; crear o modificar espacio, piso, tipo, capacidad, características y restricciones; validar coherencia; guardar y actualizar disponibilidad. |
| Alternativas / extensiones | Restricción o modificación incompatible con operaciones existentes: informar y mantener consistencia; consultar D-05. |
| Excepciones documentadas | Identificador duplicado: se rechaza el registro. Datos obligatorios incompletos: se solicita corregirlos. Espacio inexistente al intentar modificarlo. Actor sin permisos sobre el espacio seleccionado. |
| Postcondición documentada | El espacio queda registrado o actualizado y su información queda disponible para consultas y procesos de reserva. |
| RF relacionado | RF4 |
| Entidades / clases principales | Piso, RecursoInstitucional (abstracto), TipoEspacio, CaracteristicaEspacio, PoliticaLiberacion, EspacioAcademico, Asociación Espacio–Característica, BloqueoEspacio, RegistroActividad |
| Tablas directamente relacionadas | `pisos`, `recursos_institucionales`, `tipos_espacio`, `caracteristicas_espacio`, `politicas_liberacion`, `espacios_academicos`, `espacios_caracteristicas`, `bloqueos_espacio`, `registros_actividad` |
| Módulo futuro y fase | Gestión de Espacios y Reservas; Fase 3 |
| Pruebas necesarias | T-CU05: Piso/tipo válidos; capacidad positiva; código duplicado; características únicas; bloqueo con periodo válido. |

## CU06 — Consultar disponibilidad de espacios académicos

**Estado: FUNCIONALMENTE COMPLETO PARA DISPONIBILIDAD OPERATIVA; ENRIQUECIMIENTO IoT DIFERIDO A FASE 7.** Filtros, estado, bloqueos y reservas APROBADAS integrados. [Código, autorización y pruebas](RESERVATIONS_IMPLEMENTATION.md#trazabilidad-de-implementación).

Fuente: tabla 43; p. 182 (PDF 183); figura 34; análisis figura 80.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Usuario autenticado. |
| Actores | Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Precondiciones | Deben existir espacios académicos registrados. El actor debe contar con permiso para consultar la disponibilidad. La fecha u horario consultado debe poder expresarse dentro del formato admitido por el sistema. |
| Flujo principal (síntesis) | Ingresar periodo y filtros; validar fechas; consultar espacios, reservas y restricciones; calcular disponibilidad y mostrar alternativas permitidas. |
| Alternativas / extensiones | Sin alternativas: mostrar ausencia de disponibilidad; ajustar filtros sin crear reservas. |
| Excepciones documentadas | Sin resultados: se informa que no existen espacios disponibles bajo los criterios seleccionados. Datos insuficientes o desactualizados: se informa la limitación de la consulta. Falla de lectura IoT: la consulta continúa con la información operativa disponible y se indica que no existe una lectura reciente. Fallo de conexión: no se completa la consulta. |
| Postcondición documentada | El actor obtiene una vista actualizada de la disponibilidad de espacios según los criterios seleccionados. |
| RF relacionado | RF5 |
| Entidades / clases principales | Piso, TipoEspacio, CaracteristicaEspacio, EspacioAcademico, Asociación Espacio–Característica, BloqueoEspacio, Reserva, IncidenciaMantenimiento |
| Tablas directamente relacionadas | `pisos`, `tipos_espacio`, `caracteristicas_espacio`, `espacios_academicos`, `espacios_caracteristicas`, `bloqueos_espacio`, `reservas`, `incidencias_mantenimiento` |
| Módulo futuro y fase | Gestión de Espacios y Reservas; Fase 3 |
| Pruebas necesarias | T-CU06: Periodo inválido; bloqueo/mantenimiento; reservas conflictivas; filtro sin datos; <=3 s con 500 reservas. |

## CU07 — Registrar solicitud de reserva

**Estado: núcleo autorizado implementado y probado en Fase 4.** Las integraciones futuras de la ficha siguen diferidas. [Implementación y pruebas](RESERVATIONS_IMPLEMENTATION.md).

Fuente: tabla 44; p. 183 (PDF 184); figura 35; análisis figura 81.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Docente o Estudiante autorizado. |
| Actores | Docente y Estudiante. |
| Precondiciones | El actor debe encontrarse autenticado. El actor debe estar habilitado para registrar solicitudes de reserva. Debe existir al menos un espacio potencialmente disponible para el periodo requerido. |
| Flujo principal (síntesis) | Consultar disponibilidad mediante CU06; elegir espacio/periodo/motivo; validar datos y permisos; revalidar disponibilidad; confirmar y persistir solicitud pendiente. |
| Alternativas / extensiones | 6.A datos inválidos: corregir; 7.A recurso ya no disponible: no registrar y seleccionar otro. |
| Excepciones documentadas | El espacio deja de estar disponible antes de la confirmación. Datos incompletos, fecha inválida o rango horario inconsistente. El actor no cuenta con permisos para solicitar ese tipo de espacio. Fallo de conexión antes de confirmar: la solicitud no debe quedar registrada parcialmente. |
| Postcondición documentada | La solicitud de reserva queda registrada y disponible para seguimiento y resolución. |
| RF relacionado | RF6 |
| Entidades / clases principales | EspacioAcademico, BloqueoEspacio, Reserva, RegistroActividad |
| Tablas directamente relacionadas | `espacios_academicos`, `bloqueos_espacio`, `reservas`, `registros_actividad` |
| Módulo futuro y fase | Gestión de Espacios y Reservas; Fase 4 |
| Pruebas necesarias | T-CU07: Dos PENDIENTE del mismo espacio/periodo coexisten; revalidación final bajo locks frente a bloqueo/APROBADA; permiso; rollback; <=3 s. T-R01/06/07/11/14/15. |

## CU08 — Consultar estado de reservas

**Estado: núcleo autorizado implementado y probado en Fase 4.** Las integraciones futuras de la ficha siguen diferidas. [Implementación y pruebas](RESERVATIONS_IMPLEMENTATION.md).

Fuente: tabla 45; p. 185 (PDF 186); figura 36; análisis figura 82.

| Campo | Especificación |
| --- | --- |
| Prioridad | Media |
| Actor iniciador | Usuario autenticado. |
| Actores | Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Precondiciones | El actor debe encontrarse autenticado. El sistema debe permitirle acceder al módulo de reservas según sus permisos. |
| Flujo principal (síntesis) | Identificar usuario y alcance; consultar reservas propias o autorizadas con filtros; mostrar estado, datos y seguimiento disponibles. |
| Alternativas / extensiones | Sin resultados: mostrar estado vacío; detalle únicamente si pertenece al ámbito permitido. |
| Excepciones documentadas | No existen reservas para los filtros seleccionados. La reserva seleccionada dejó de existir o no es accesible para el actor. Fallo de conexión durante la consulta. |
| Postcondición documentada | El actor visualiza información actualizada de las reservas autorizadas para su rol. |
| RF relacionado | RF6, RF7 |
| Entidades / clases principales | Reserva |
| Tablas directamente relacionadas | `reservas` |
| Módulo futuro y fase | Gestión de Espacios y Reservas; Fase 4 |
| Pruebas necesarias | T-CU08: Consulta propia sin terceros; gestión solo con permiso de Administrador/Encargado; filtros/paginación no amplían alcance; estados persistidos. Ámbito fino diferido. T-R12/16/17. |

## CU09 — Resolver solicitud de reserva

**Estado: núcleo autorizado implementado y probado en Fase 4.** Las integraciones futuras de la ficha siguen diferidas. [Implementación y pruebas](RESERVATIONS_IMPLEMENTATION.md).

Fuente: tabla 46; p. 186 (PDF 187); figura 37; análisis figura 83.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador o Encargado de Laboratorio autorizado. |
| Actores | Administrador y Encargado de Laboratorio. |
| Precondiciones | Debe existir una solicitud de reserva en estado pendiente. El actor debe contar con permisos para resolver la solicitud correspondiente. |
| Flujo principal (síntesis) | Consultar solicitud pendiente; comprobar permisos y disponibilidad mediante CU06; aprobar o rechazar; registrar decisión, responsable, fecha y motivo aplicable; actualizar seguimiento. |
| Alternativas / extensiones | Conflicto al aprobar mantiene PENDIENTE; rechazo explícito solo de PENDIENTE, motivo opcional según cierre D-05 de Fase 3.5; CU24 condicional, diferido a Fase 9. |
| Excepciones documentadas | La solicitud ya fue resuelta o cambió de estado. El espacio dejó de estar disponible al intentar aprobar. El actor no posee permisos para resolver esa solicitud. Fallo de actualización: el sistema debe evitar dejar una decisión parcialmente registrada. |
| Postcondición documentada | La solicitud queda aprobada o rechazada y el resultado queda disponible para seguimiento. |
| RF relacionado | RF7 |
| Entidades / clases principales | EspacioAcademico, BloqueoEspacio, Reserva, RegistroActividad |
| Tablas directamente relacionadas | `espacios_academicos`, `bloqueos_espacio`, `reservas`, `registros_actividad` |
| Módulo futuro y fase | Gestión de Espacios y Reservas; Fase 4 |
| Pruebas necesarias | T-CU09: Dos procesos reales, una sola aprobación solapada; perdedora PENDIENTE; contiguas aprobables; doble resolución; permiso; rechazo; <=3 s. T-R02–08/10/11/13/14. |

## CU10 — Cancelar reserva

**Estado: núcleo autorizado implementado y probado en Fase 4.** Las integraciones futuras de la ficha siguen diferidas. [Implementación y pruebas](RESERVATIONS_IMPLEMENTATION.md).

Fuente: tabla 47; p. 188 (PDF 189); figura 38; análisis figura 84.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Usuario autorizado para cancelar la reserva. |
| Actores | Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Precondiciones | Debe existir una reserva en un estado que permita cancelación. El actor debe ser titular de la reserva o contar con permiso administrativo para cancelarla. |
| Flujo principal (síntesis) | Seleccionar reserva; comprobar titularidad/permiso, estado y plazo; confirmar cancelación y motivo pertinente; registrar cambio y actualizar disponibilidad. |
| Alternativas / extensiones | Operación fuera de condiciones: no cancelar. CU24 condicional si la cancelación está definida como crítica. |
| Excepciones documentadas | Reserva ya cancelada, finalizada o fuera del periodo permitido para cancelar. Actor sin autorización para cancelar la reserva. La reserva cambió de estado antes de la confirmación. Error al actualizar el estado: no debe liberarse el espacio sin registrar correctamente la cancelación. |
| Postcondición documentada | La reserva queda cancelada y el espacio vuelve a estar disponible según la fecha, horario y demás restricciones existentes. |
| RF relacionado | RF7 |
| Entidades / clases principales | EspacioAcademico, Reserva, RegistroActividad |
| Tablas directamente relacionadas | `espacios_academicos`, `reservas`, `registros_actividad` |
| Módulo futuro y fase | Gestión de Espacios y Reservas; Fase 4 |
| Pruebas necesarias | T-CU10: Propia y administrativa con permisos distintos; denegar ajena no autorizada, ahora>=inicio y estados no cancelables; motivo opcional; preservar resolución; concurrencia/rollback; <=3 s. T-R08–14. |

## CU11 — Validar uso de espacio mediante código QR

Fuente: tabla 48; p. 190 (PDF 191); figura 39; análisis figura 85.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Usuario autorizado que realiza el escaneo. |
| Actores | Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Precondiciones | El actor debe encontrarse autenticado. El dispositivo debe disponer de una cámara funcional y permiso para utilizarla. El código QR debe encontrarse asociado previamente a un espacio u operación válida. |
| Flujo principal (síntesis) | Leer QR; enviar token/contexto al servidor; validar identidad, vigencia, espacio, reserva y autorización; registrar resultado y mostrar confirmación de uso válida. |
| Alternativas / extensiones | Código no vigente, inválido o ajeno al contexto: resultado inválido y explicación; no confirmar uso. |
| Excepciones documentadas | Código QR inválido, inexistente o vencido. Usuario no autorizado para el espacio o reserva. Reserva inexistente, cancelada o fuera del periodo permitido. Cámara no disponible o permiso de cámara denegado. |
| Postcondición documentada | La validación queda registrada y asociada al uso del espacio o reserva correspondiente. |
| RF relacionado | RF8 |
| Entidades / clases principales | RecursoInstitucional (abstracto), EspacioAcademico, Reserva, CodigoQR, ValidacionQR |
| Tablas directamente relacionadas | `recursos_institucionales`, `espacios_academicos`, `reservas`, `codigos_qr`, `validaciones_qr` |
| Módulo futuro y fase | Validación QR transversal; Fase 6 |
| Pruebas necesarias | T-CU11: Cámara denegada/código ilegible; expirado; espacio/usuario/contexto incorrecto; resultado <=3 s tras lectura. |

## CU12 — Gestionar equipamiento tecnológico

Fuente: tabla 49; p. 191 (PDF 192); figura 40; análisis figura 86.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador o Encargado de Laboratorio autorizado. |
| Actores | Administrador y Encargado de Laboratorio. |
| Precondiciones | El actor debe encontrarse autenticado. El actor debe contar con permisos para administrar el equipamiento seleccionado. |
| Flujo principal (síntesis) | Consultar inventario; registrar o modificar código/nombre, tipo, características, ubicación y estado; validar identidad y datos; persistir y conservar historial. |
| Alternativas / extensiones | Duplicado o cambio incompatible con préstamo/incidencia: impedir modificación incoherente. |
| Excepciones documentadas | Identificador de equipamiento duplicado. Datos obligatorios incompletos o inválidos. Equipo inexistente al intentar modificarlo. Actor sin permisos suficientes. |
| Postcondición documentada | El equipamiento queda registrado o actualizado con la información y estado vigentes. |
| RF relacionado | RF9 |
| Entidades / clases principales | RecursoInstitucional (abstracto), TipoEquipamiento, EquipamientoTecnologico, RegistroActividad |
| Tablas directamente relacionadas | `recursos_institucionales`, `tipos_equipamiento`, `equipamientos_tecnologicos`, `registros_actividad` |
| Módulo futuro y fase | Gestión de Equipamiento y Préstamos; Fase 5 |
| Estado y precisión de implementación | Contrato Fase 4.5: PK compartida, nueve campos, serie/base opcionales, guardas de estado y BAJA; T-P01/02/16. Implementación pendiente. |
| Pruebas necesarias | T-CU12: Código/serie duplicados; tipo/base inexistentes; estado permitido; cambio incompatible con préstamo activo. |

## CU13 — Consultar disponibilidad de equipamiento tecnológico

Fuente: tabla 50; p. 192 (PDF 193); figura 41; análisis figura 87.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Usuario autenticado. |
| Actores | Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Precondiciones | Debe existir equipamiento tecnológico registrado. El actor debe contar con permiso de consulta. |
| Flujo principal (síntesis) | Solicitar periodo/filtros; validar; consultar estado, préstamos e incidencias del equipamiento; mostrar disponibilidad autorizada. |
| Alternativas / extensiones | Equipo no disponible o sin coincidencias: informar y permitir otro filtro/periodo. |
| Excepciones documentadas | Filtros inválidos o incompletos. No existen equipos disponibles bajo los criterios seleccionados. Información de disponibilidad no actualizada. Fallo de conexión con el servidor. |
| Postcondición documentada | El actor obtiene la disponibilidad actual del equipamiento tecnológico consultado. |
| RF relacionado | RF10 |
| Entidades / clases principales | TipoEquipamiento, EquipamientoTecnologico, Prestamo, IncidenciaMantenimiento |
| Tablas directamente relacionadas | `tipos_equipamiento`, `equipamientos_tecnologicos`, `prestamos`, `incidencias_mantenimiento` |
| Módulo futuro y fase | Gestión de Equipamiento y Préstamos; Fase 5 |
| Estado y precisión de implementación | Contrato Fase 4.5: EquipmentAvailabilityService único, APROBADO/ENTREGADO solapados más estado físico e incidencias ABIERTA; T-P03/17. Implementación pendiente. |
| Pruebas necesarias | T-CU13: Equipo prestado, mantenimiento o baja excluido; solapamientos; 1.000 registros <=3 s; paginación <=100. |

## CU14 — Registrar solicitud de préstamo

Fuente: tabla 51; p. 194 (PDF 195); figura 42; análisis figura 88.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Docente o Estudiante autorizado. |
| Actores | Docente y Estudiante. |
| Precondiciones | El actor debe encontrarse autenticado. El actor debe estar habilitado para solicitar préstamos. Debe existir equipamiento que pueda estar disponible para el periodo requerido. |
| Flujo principal (síntesis) | Consultar disponibilidad mediante CU13; seleccionar equipo y periodo/motivo; validar permisos y datos; revalidar disponibilidad; confirmar solicitud pendiente. |
| Alternativas / extensiones | Información inválida: corregir. Equipo deja de estar disponible: no confirmar solicitud. |
| Excepciones documentadas | El equipamiento deja de estar disponible antes de la confirmación. Periodo inválido o datos incompletos. El actor no cuenta con permisos para solicitar el recurso. Fallo de conexión antes de confirmar: no debe generarse una solicitud parcial. |
| Postcondición documentada | La solicitud de préstamo queda registrada y disponible para seguimiento y resolución. |
| RF relacionado | RF11 |
| Entidades / clases principales | EquipamientoTecnologico, Prestamo, RegistroActividad |
| Tablas directamente relacionadas | `equipamientos_tecnologicos`, `prestamos`, `registros_actividad` |
| Módulo futuro y fase | Gestión de Equipamiento y Préstamos; Fase 5 |
| Estado y precisión de implementación | Contrato Fase 4.5: D/S con permiso, solicitante de sesión, PENDIENTE sin exclusividad, PRE-UUIDv4, revalidación transaccional; T-P02/04. Implementación pendiente. |
| Pruebas necesarias | T-CU14: Dos solicitudes incompatibles concurrentes; datos inválidos; equipo deja de estar disponible; atomicidad. |

## CU15 — Consultar estado de préstamos

Fuente: tabla 52; p. 195 (PDF 196); figura 43; análisis figura 89.

| Campo | Especificación |
| --- | --- |
| Prioridad | Media |
| Actor iniciador | Usuario autenticado. |
| Actores | Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Precondiciones | El actor debe encontrarse autenticado. El sistema debe permitirle acceder al módulo de préstamos según su rol. |
| Flujo principal (síntesis) | Identificar usuario y permisos; filtrar préstamos propios o supervisables; mostrar estado y datos de entrega/devolución pertinentes. |
| Alternativas / extensiones | Sin préstamos: vacío comprensible; limitar supervisión al ámbito permitido. |
| Excepciones documentadas | No existen registros para los filtros seleccionados. El préstamo no existe o no es accesible para el actor. Fallo de conexión durante la consulta. |
| Postcondición documentada | El actor visualiza el estado actualizado de los préstamos autorizados para su rol. |
| RF relacionado | RF11, RF12, RF13, RF14 |
| Entidades / clases principales | Prestamo |
| Tablas directamente relacionadas | `prestamos` |
| Módulo futuro y fase | Gestión de Equipamiento y Préstamos; Fase 5 |
| Estado y precisión de implementación | Contrato Fase 4.5: propios D/S, administrativos A/E con permiso; vencido calculado ENTREGADO y ahora>fin_previsto; T-P02/17. Implementación pendiente. |
| Pruebas necesarias | T-CU15: Consulta propia frente a ajena; estados y fechas; vacío; 1.000 registros <=3 s; máximo 100 por página. |

## CU16 — Resolver solicitud de préstamo

Fuente: tabla 53; p. 196 (PDF 197); figura 44; análisis figura 90.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador o Encargado de Laboratorio autorizado. |
| Actores | Administrador y Encargado de Laboratorio. |
| Precondiciones | Debe existir una solicitud de préstamo en estado pendiente. El actor debe contar con permisos para resolverla. |
| Flujo principal (síntesis) | Revisar solicitud pendiente; validar responsable y disponibilidad mediante CU13; aprobar o rechazar (motivo obligatorio al rechazar en Fase 5, precisión RF12/Fase 4.5); registrar decisión y seguimiento. |
| Alternativas / extensiones | Disponibilidad modificada: conservar PENDIENTE, sin auto-rechazo; rechazo posterior explícito exige motivo. |
| Excepciones documentadas | La solicitud ya fue resuelta. El equipamiento dejó de estar disponible o presenta una incidencia incompatible. Actor sin permisos para resolver la solicitud. Fallo de actualización: la decisión no debe quedar parcialmente registrada. |
| Postcondición documentada | La solicitud queda aprobada o rechazada y el resultado queda registrado para seguimiento. |
| RF relacionado | RF12 |
| Entidades / clases principales | EquipamientoTecnologico, Prestamo, RegistroActividad |
| Tablas directamente relacionadas | `equipamientos_tecnologicos`, `prestamos`, `registros_actividad` |
| Módulo futuro y fase | Gestión de Equipamiento y Préstamos; Fase 5 |
| Estado y precisión de implementación | Contrato Fase 4.5: solo PENDIENTE; rechazo con motivo obligatorio según RF12; conflicto mantiene PENDIENTE; T-P05/06. Implementación pendiente. |
| Pruebas necesarias | T-CU16: Doble decisión; estado no pendiente; recurso no disponible; motivo de rechazo; responsable/fecha persistidos; CU30 diferido. |

## CU17 — Registrar entrega de equipamiento tecnológico

Fuente: tabla 54; p. 198 (PDF 199); figura 45; análisis figura 91.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador o Encargado de Laboratorio autorizado. |
| Actores | Administrador y Encargado de Laboratorio. |
| Precondiciones | Debe existir un préstamo aprobado pendiente de entrega. El equipamiento debe encontrarse disponible y sin una incidencia que impida su entrega. El usuario receptor debe estar identificado. |
| Flujo principal (síntesis) | Revisar préstamo aprobado, equipo y receptor; verificar físicamente identidad/condición; validar QR si aplica; confirmar; registrar entrega y actualizar equipo/préstamo. |
| Alternativas / extensiones | CU20 extiende si se requiere QR: inválido impide entrega. CU24 puede extender si operación crítica. |
| Excepciones documentadas | Préstamo no aprobado, cancelado o ya entregado. Equipamiento distinto al autorizado. Equipamiento no disponible o con incidencia previa. Receptor no identificado o no autorizado. Validación QR inválida cuando la política exige su uso. |
| Postcondición documentada | El préstamo queda registrado como entregado y el equipamiento queda asociado temporalmente al receptor con estado prestado. |
| RF relacionado | RF13 |
| Entidades / clases principales | EquipamientoTecnologico, Prestamo, EntregaPrestamo, IncidenciaMantenimiento, ValidacionQR, RegistroActividad |
| Tablas directamente relacionadas | `equipamientos_tecnologicos`, `prestamos`, `entregas_prestamo`, `incidencias_mantenimiento`, `validaciones_qr`, `registros_actividad` |
| Módulo futuro y fase | Gestión de Equipamiento y Préstamos; Fase 5 |
| Estado y precisión de implementación | Contrato Fase 4.5: receptor=solicitante; recepcion_confirmada_en NULL; entrega única/atómica, sin QR en Fase 5; T-P07/08. Núcleo implementado en Fase 5; extensión QR implementada en Fase 6, con suite aprobada y aceptación física por confirmación del usuario (sin corroboración independiente nueva). |
| Pruebas necesarias | T-CU17: Receptor/equipo incorrectos; QR exigido inválido; una única entrega; estados ENTREGADO/PRESTADO atómicos. |

## CU18 — Registrar devolución de equipamiento tecnológico

Fuente: tabla 55; p. 199 (PDF 200); figura 46; análisis figura 92.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador o Encargado de Laboratorio autorizado. |
| Actores | Administrador y Encargado de Laboratorio. |
| Precondiciones | Debe existir un préstamo entregado y activo asociado al equipamiento. El equipamiento a devolver debe poder identificarse dentro del sistema. |
| Flujo principal (síntesis) | Identificar préstamo y equipo recibido; comprobar condición física y QR si aplica; registrar responsable/fecha; determinar disponibilidad y cierre o revisión; mostrar resultado. |
| Alternativas / extensiones | QR inválido impide confirmar; retorno adecuado permite disponibilidad; anomalía extiende CU19; condición desconocida queda pendiente de revisión; CU24 condicional. |
| Excepciones documentadas | Préstamo inexistente, cancelado o no entregado. El equipamiento recibido no corresponde al préstamo. Validación QR inválida cuando sea requerida. No es posible determinar la condición física: la operación debe quedar pendiente de revisión. |
| Postcondición documentada | El préstamo queda finalizado y el equipamiento queda disponible o marcado con un estado de incidencia/mantenimiento según su condición. |
| RF relacionado | RF14 |
| Entidades / clases principales | EquipamientoTecnologico, Prestamo, EntregaPrestamo, DevolucionPrestamo, IncidenciaMantenimiento, ValidacionQR, RegistroActividad |
| Tablas directamente relacionadas | `equipamientos_tecnologicos`, `prestamos`, `entregas_prestamo`, `devoluciones_prestamo`, `incidencias_mantenimiento`, `validaciones_qr`, `registros_actividad` |
| Módulo futuro y fase | Gestión de Equipamiento y Préstamos; Fase 5 |
| Estado y precisión de implementación | Contrato Fase 4.5: devolución única; ADECUADA/CON_INCIDENCIA confirmadas, NO_DETERMINABLE pendiente; continuar misma fila con actor/fecha; T-P09–12. Núcleo implementado en Fase 5; extensión QR implementada en Fase 6, con suite aprobada y aceptación física por confirmación del usuario (sin corroboración independiente nueva). |
| Pruebas necesarias | T-CU18: Retorno adecuado; daño e incidencia; NO_DETERMINABLE/PENDIENTE_REVISION; préstamo no entregado; devolución única. |

## CU19 — Gestionar incidencias y mantenimiento

Fuente: tabla 56; p. 201 (PDF 202); figura 47; análisis figura 93.

| Campo | Especificación |
| --- | --- |
| Prioridad | Media |
| Actor iniciador | Administrador o Encargado de Laboratorio autorizado; también puede activarse como extensión de CU18 cuando se detecta una anomalía durante la devolución. |
| Actores | Administrador y Encargado de Laboratorio. |
| Precondiciones | El recurso afectado debe estar registrado en el sistema. El actor debe contar con permisos para gestionar incidencias o modificar su estado. |
| Flujo principal (síntesis) | Identificar recurso/incidencia; registrar tipo, descripción, responsables y periodo; actualizar restricción del recurso; seguir atención y cerrar con solución cuando proceda. |
| Alternativas / extensiones | Atención posterior/cierre exige condición compatible; se activa desde CU18 cuando existe anomalía. |
| Excepciones documentadas | Recurso inexistente o no accesible para el actor. Datos obligatorios de la incidencia incompletos. Intento de habilitar un recurso mientras existe una condición activa incompatible. Actor sin permisos para modificar el estado del recurso. |
| Postcondición documentada | La incidencia o mantenimiento queda registrado y el recurso refleja su condición real para futuras operaciones. |
| RF relacionado | RF15 |
| Entidades / clases principales | RecursoInstitucional (abstracto), EspacioAcademico, EquipamientoTecnologico, DevolucionPrestamo, IncidenciaMantenimiento, RegistroActividad |
| Tablas directamente relacionadas | `recursos_institucionales`, `espacios_academicos`, `equipamientos_tecnologicos`, `devoluciones_prestamo`, `incidencias_mantenimiento`, `registros_actividad` |
| Módulo futuro y fase | Gestión de Equipamiento y Préstamos; Fase 5 |
| Estado y precisión de implementación | Contrato Fase 4.5: ambos subtipos, ABIERTA incompatible, cierre sin habilitación automática, integración futura con SpaceAvailabilityService; T-P13–16. Implementación pendiente. |
| Pruebas necesarias | T-CU19: Apertura/cierre autorizado; fechas inválidas; otra incidencia impide disponibilidad; historia preservada. |

## CU20 — Validar operación de equipamiento mediante código QR

Fuente: tabla 57; p. 203 (PDF 204); figura 48; análisis figura 94.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador o Encargado de Laboratorio durante CU17 Registrar entrega o CU18 Registrar devolución. |
| Actores | Administrador y Encargado de Laboratorio. |
| Precondiciones | Debe existir una entrega o devolución en curso. El dispositivo utilizado debe disponer de cámara habilitada. El código QR debe encontrarse asociado al equipamiento u operación correspondiente. |
| Flujo principal (síntesis) | Leer QR durante entrega/devolución; recuperar código, objetivo y contexto; validar usuario, equipo y préstamo; registrar resultado y permitir continuidad solo si corresponde. |
| Alternativas / extensiones | QR inválido impide confirmar la operación que extendió; validación correcta permite continuar CU17 o CU18. |
| Excepciones documentadas | Código QR inválido, inexistente o no registrado. El código corresponde a un equipamiento diferente al del préstamo. Cámara no disponible o permiso denegado. La entrega o devolución dejó de estar activa antes de completar la validación. |
| Postcondición documentada | La operación principal dispone de un resultado de validación QR registrado y verificable. |
| RF relacionado | RF13, RF14 |
| Entidades / clases principales | RecursoInstitucional (abstracto), EquipamientoTecnologico, Prestamo, EntregaPrestamo, DevolucionPrestamo, CodigoQR, ValidacionQR |
| Tablas directamente relacionadas | `recursos_institucionales`, `equipamientos_tecnologicos`, `prestamos`, `entregas_prestamo`, `devoluciones_prestamo`, `codigos_qr`, `validaciones_qr` |
| Módulo futuro y fase | Validación QR transversal; Fase 6 |
| Pruebas necesarias | T-CU20: Token de otro equipo/operación; permisos; contexto obligatorio; reintentos; enlace único entrega/devolución; <=3 s. |

## CU21 — Gestionar sensores IoT

Fuente: tabla 58; p. 204 (PDF 205); figura 49; análisis figura 95.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador o Encargado de Laboratorio autorizado. |
| Actores | Administrador y Encargado de Laboratorio. |
| Precondiciones | El actor debe encontrarse autenticado y autorizado. El espacio académico que será monitoreado debe estar registrado. El dispositivo IoT debe disponer de un identificador válido. |
| Flujo principal (síntesis) | Registrar/consultar sensor; fijar identificador inmutable al alta, editar tipo/asociación/estado; validar unicidad/coherencia; habilitar/deshabilitar conservando historia y provisionar explícitamente con autorización. |
| Alternativas / extensiones | Reasignar o deshabilitar sensor no borra eventos históricos; identificador duplicado impide alta. |
| Excepciones documentadas | Identificador de sensor duplicado. Espacio académico inexistente. Datos obligatorios incompletos. Actor sin permisos para gestionar sensores. |
| Postcondición documentada | El sensor queda registrado y asociado a un espacio, con un estado que determina si sus eventos serán aceptados. |
| RF relacionado | RF16 |
| Entidades / clases principales | SensorIoT, RegistroActividad |
| Tablas directamente relacionadas | `sensores_iot`, `registros_actividad` |
| Módulo futuro y fase | Monitoreo IoT y Disponibilidad implementado en Fase 7 según D-10; [evidencia](IOT_IMPLEMENTATION.md). Bitácora general/CU30 diferida. |
| Pruebas necesarias | T-I01–T-I32 y T-CU21: Identificador duplicado; espacio inexistente; habilitar/deshabilitar; reasignación no altera historia. |

## CU22 — Monitorear ocupación de espacios académicos

Fuente: tabla 59; p. 206 (PDF 207); figura 50; análisis figura 96.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Dispositivo IoT registrado y habilitado. |
| Actores | Administrador, Encargado de Laboratorio y Dispositivo IoT. |
| Precondiciones | El dispositivo debe estar registrado, habilitado y asociado a un espacio académico. Debe existir conectividad y el evento debe utilizar el formato de comunicación definido. |
| Flujo principal (síntesis) | Autenticar HMAC RAW antes de JSON; validar sensor/contexto/payload/tiempos; deduplicar bajo lock del sensor; persistir nuevo evento/última comunicación atómicos; mostrar evidencia reciente o NO_DETERMINABLE a usuarios autorizados. |
| Alternativas / extensiones | Sensor/estructura inválidos: rechazar evento; datos recientes insuficientes: no determinable; CU23 exclusivamente en Fase 8, sin liberación en Fase 7. |
| Excepciones documentadas | Sensor no registrado o deshabilitado: se rechaza el evento. Evento con formato inválido o datos obligatorios faltantes. Pérdida de conectividad del dispositivo. Lectura desactualizada o ausencia de eventos recientes: se informa que la ocupación no puede determinarse con información IoT actual. |
| Postcondición documentada | El evento IoT válido queda registrado y la información de monitoreo del espacio queda actualizada. |
| RF relacionado | RF17 |
| Entidades / clases principales | EspacioAcademico, SensorIoT, EventoOcupacion |
| Tablas directamente relacionadas | `espacios_academicos`, `sensores_iot`, `eventos_ocupacion` |
| Módulo futuro y fase | Monitoreo IoT y Disponibilidad implementado en Fase 7 según D-10; [evidencia](IOT_IMPLEMENTATION.md). Bitácora general/CU30 diferida. |
| Pruebas necesarias | T-I01–T-I32 y T-CU22: Sensor inválido/deshabilitado; dato malformado rechazado/antiguo válido histórico; no determinable; >=120 eventos/min; reflejo <=5 s. |

## CU23 — Aplicar política de liberación de reserva

Fuente: tabla 60; p. 207 (PDF 208); figura 51; análisis figura 97.

| Campo | Especificación |
| --- | --- |
| Prioridad | Media |
| Actor iniciador | CU22 Monitorear ocupación de espacios académicos, cuando se cumplen las condiciones temporales y operativas definidas por la política. |
| Actores | No posee un actor humano directo. Este caso de uso se ejecuta como una extensión condicional de CU22 Monitorear ocupación de espacios académicos. |
| Precondiciones | Debe existir una reserva aprobada y activa. Debe existir una política de liberación aplicable al espacio académico asociado a la reserva. Si la política utiliza IoT, el espacio debe contar con un sensor habilitado y con información suficiente. |
| Flujo principal (síntesis) | Desde CU22, recuperar reserva aprobada/activa y política; revisar periodo, QR y eventos recientes cuando apliquen; evaluar; guardar resultado/evidencias y actuar según modo. |
| Alternativas / extensiones | 5.A evidencia de uso mantiene reserva; 5.B insuficiente conserva y registra no determinable; 5.C libera o alerta según política; no liberar por falta de lecturas. |
| Excepciones documentadas | Información IoT insuficiente o sensor sin lecturas recientes: no se libera automáticamente el espacio cuando la política requiera esos datos. Reserva ya modificada, cancelada o finalizada antes de la evaluación. La política aplicable no se encuentra configurada correctamente: se conserva la reserva y se registra la incidencia de configuración. |
| Postcondición documentada | La reserva y la disponibilidad del espacio quedan actualizadas únicamente cuando se cumplen las condiciones establecidas por la política. |
| RF relacionado | RF18 |
| Entidades / clases principales | PoliticaLiberacion, EspacioAcademico, Reserva, ValidacionQR, SensorIoT, EventoOcupacion, EvaluacionLiberacion, Asociación Evaluación–Evento, Asociación Evaluación–Validación, RegistroActividad |
| Tablas directamente relacionadas | `politicas_liberacion`, `espacios_academicos`, `reservas`, `validaciones_qr`, `sensores_iot`, `eventos_ocupacion`, `evaluaciones_liberacion`, `evaluaciones_eventos_ocupacion`, `evaluaciones_validaciones_qr`, `registros_actividad` |
| Módulo y estado actual | Monitoreo IoT y Disponibilidad; núcleo CU23 implementado en Fase 8. CU30/notificaciones diferidos. |
| Pruebas necesarias | T-CU23 concretado en T-L01–T-L36; ejecutados en Fase 8, con evidencia en RELEASE_POLICY_IMPLEMENTATION. En 7.5 eran especificaciones futuras. |

## CU24 — Generar evidencia de trazabilidad mediante Blockchain

Fuente: tabla 61; p. 209 (PDF 210); figura 52; análisis figura 98.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Caso de uso base CU09, CU10, CU17 o CU18 al finalizar correctamente una operación configurada como crítica. |
| Actores | Servicio Blockchain. |
| Precondiciones | La operación principal debe haberse registrado correctamente en MySQL. La operación debe pertenecer al conjunto de acciones configuradas para generar evidencia. Debe existir una configuración válida para el mecanismo de trazabilidad. |
| Flujo principal (síntesis) | Detectar operación crítica confirmada; seleccionar datos mínimos permitidos; generar representación/hash; persistir evidencia local; solicitar registro externo; conservar referencia o pendiente. |
| Alternativas / extensiones | Servicio externo no disponible: evidencia PENDIENTE e intento posterior; operación principal permanece confirmada. |
| Excepciones documentadas | Servicio Blockchain temporalmente no disponible: la evidencia queda pendiente. Error al generar el hash o preparar la evidencia: se registra el fallo para revisión. Operación principal inexistente o incompleta. Se detecta información no permitida en los datos destinados al registro externo: el envío se cancela. |
| Postcondición documentada | La operación crítica queda asociada a una evidencia local y, cuando el servicio está disponible, a una referencia registrada mediante Blockchain. |
| RF relacionado | RF19 |
| Entidades / clases principales | RegistroActividad, EvidenciaTrazabilidad |
| Tablas directamente relacionadas | `registros_actividad`, `evidencias_trazabilidad` |
| Módulo futuro y fase | Trazabilidad Blockchain; Fase 9 |
| Pruebas necesarias | T-CU24: Privacidad previa al envío; operación no confirmada; local <=3 s; proveedor caído; recuperación sin duplicados. |

## CU25 — Verificar evidencia de trazabilidad

Fuente: tabla 62; p. 210 (PDF 211); figura 53; análisis figura 99.

| Campo | Especificación |
| --- | --- |
| Prioridad | Alta |
| Actor iniciador | Administrador. |
| Actores | Administrador y Servicio Blockchain. |
| Precondiciones | Debe existir una operación crítica con evidencia local asociada. El Administrador debe contar con permiso para consultar la evidencia. Para una comprobación externa debe existir una referencia Blockchain o disponibilidad del servicio. |
| Flujo principal (síntesis) | Seleccionar evidencia autorizada; recuperar datos mínimos, algoritmo y versión; recalcular hash; consultar referencia cuando corresponda; comparar y presentar resultado. |
| Alternativas / extensiones | Coincide: VALIDO; difiere: INCONSISTENTE; aún pendiente: PENDIENTE; imposibilidad de verificación: NO_VERIFICABLE según causa. |
| Excepciones documentadas | Evidencia inexistente o todavía pendiente de registro. Servicio Blockchain no disponible para una verificación externa. Datos de la operación incompletos o no recuperables. Administrador sin permisos suficientes para consultar la evidencia. |
| Postcondición documentada | El Administrador obtiene un resultado de verificación que permite determinar la correspondencia entre la operación y su evidencia. |
| RF relacionado | RF20 |
| Entidades / clases principales | RegistroActividad, EvidenciaTrazabilidad |
| Tablas directamente relacionadas | `registros_actividad`, `evidencias_trazabilidad` |
| Módulo futuro y fase | Trazabilidad Blockchain; Fase 9 |
| Pruebas necesarias | T-CU25: Hash alterado; versión canónica incorrecta; referencia ausente; servicio caído; cuatro resultados; permisos. |

## CU26 — Gestionar notificaciones

Fuente: tabla 63; p. 212 (PDF 213); figura 54; análisis figura 100.

| Campo | Especificación |
| --- | --- |
| Prioridad | Media |
| Actor iniciador | Administrador, Encargado de Laboratorio, Docente o Estudiante autenticado. |
| Actores | Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Precondiciones | Debe existir un evento sujeto a notificación. Debe existir al menos un destinatario válido identificado por las reglas del sistema. |
| Flujo principal (síntesis) | Identificar usuario; recuperar únicamente sus avisos; listar estado; abrir detalle del evento; marcar lectura cuando corresponda y continuar consulta. |
| Alternativas / extensiones | Sin avisos: informar. La generación automática de avisos se especifica en RF21 y complementa este flujo de consulta. |
| Excepciones documentadas | No existe un destinatario válido: no se genera un aviso sin receptor. El evento no posee una regla de notificación configurada. Fallo temporal al generar el aviso: la operación principal debe mantenerse registrada. El usuario intenta consultar una notificación que no le pertenece: el acceso es rechazado. |
| Postcondición documentada | La notificación queda registrada y disponible para el destinatario con su estado de lectura correspondiente. |
| RF relacionado | RF21 |
| Entidades / clases principales | Usuario, Notificacion, RegistroActividad |
| Tablas directamente relacionadas | `usuarios`, `notificaciones`, `registros_actividad` |
| Módulo futuro y fase | Servicios de Notificación y Asistencia; Fase 10 |
| Pruebas necesarias | T-CU26: Aviso propio/ajeno; lectura persistida; sin avisos; fallo de generación no revierte operación confirmada. |

## CU27 — Visualizar dashboard e indicadores

Fuente: tabla 64; p. 213 (PDF 214); figura 55; análisis figura 101.

| Campo | Especificación |
| --- | --- |
| Prioridad | Media |
| Actor iniciador | Usuario autenticado. |
| Actores | Administrador, Encargado de Laboratorio, Docente y Estudiante. |
| Precondiciones | El actor debe encontrarse autenticado. El actor debe contar con permiso para visualizar el dashboard o los indicadores correspondientes. |
| Flujo principal (síntesis) | Identificar rol/permisos; consultar información consolidada; calcular indicadores autorizados; mostrar panel y actualizar filtros/vista. |
| Alternativas / extensiones | Sin datos para un filtro: presentar vacío/limitación sin inventar indicadores; falla parcial no expone información indebida. |
| Excepciones documentadas | No existen datos suficientes para calcular un indicador. Fallo al consultar o consolidar la información. El actor intenta visualizar un indicador no autorizado. Parámetros de filtro inválidos: se solicita su corrección. |
| Postcondición documentada | El actor visualiza indicadores actualizados y limitados a los datos autorizados para su rol. |
| RF relacionado | RF22 |
| Entidades / clases principales | RecursoInstitucional, EspacioAcademico, Reserva, EquipamientoTecnologico, Prestamo, EntregaPrestamo, DevolucionPrestamo, IncidenciaMantenimiento, EventoOcupacion, EvaluacionLiberacion |
| Tablas directamente relacionadas | `recursos_institucionales`, `espacios_academicos`, `reservas`, `equipamientos_tecnologicos`, `prestamos`, `entregas_prestamo`, `devoluciones_prestamo`, `incidencias_mantenimiento`, `eventos_ocupacion`, `evaluaciones_liberacion` |
| Módulo futuro y fase | Reportes e Indicadores; Fase 11 |
| Pruebas necesarias | T-CU27: Indicadores por rol y filtros consistentes; sin datos; cambios de permisos; <=5 s con carga piloto. |

## CU28 — Generar y exportar reportes

Fuente: tabla 65; p. 214 (PDF 215); figura 56; análisis figura 102.

| Campo | Especificación |
| --- | --- |
| Prioridad | Media |
| Actor iniciador | Administrador o Encargado de Laboratorio autorizado. |
| Actores | Administrador y Encargado de Laboratorio. |
| Precondiciones | El actor debe encontrarse autenticado y autorizado para el tipo de reporte solicitado. Debe existir información disponible para los criterios seleccionados. |
| Flujo principal (síntesis) | Elegir reporte y filtros autorizados; validar parámetros; consultar datos; mostrar resultados; generar PDF o Excel; autorizar descarga y registrar actividad si aplica. |
| Alternativas / extensiones | Sin datos: informar; cambiar filtros; exportar exclusivamente formatos/alcance habilitados. |
| Excepciones documentadas | No existen datos para los filtros seleccionados. Parámetros de consulta inválidos. Error durante la generación del archivo. Actor sin permisos para acceder al reporte solicitado. |
| Postcondición documentada | El reporte queda generado con los filtros solicitados y disponible para consulta o descarga autorizada. |
| RF relacionado | RF23 |
| Entidades / clases principales | Usuario, RecursoInstitucional, EspacioAcademico, Reserva, EquipamientoTecnologico, Prestamo, EntregaPrestamo, DevolucionPrestamo, IncidenciaMantenimiento, EventoOcupacion, RegistroActividad |
| Tablas directamente relacionadas | `usuarios`, `recursos_institucionales`, `espacios_academicos`, `reservas`, `equipamientos_tecnologicos`, `prestamos`, `entregas_prestamo`, `devoluciones_prestamo`, `incidencias_mantenimiento`, `eventos_ocupacion`, `registros_actividad` |
| Módulo futuro y fase | Reportes e Indicadores; Fase 11 |
| Pruebas necesarias | T-CU28: PDF/Excel coherentes con filtros/permisos; 10.000 filas <=30 s; reporte vacío; fallo de exportación sin bloquear a otros. |

## CU29 — Consultar chatbot de asistencia

Fuente: tabla 66; p. 216 (PDF 217); figura 57; análisis figura 103.

| Campo | Especificación |
| --- | --- |
| Prioridad | Media |
| Actor iniciador | Usuario autenticado. |
| Actores | Administrador, Encargado de Laboratorio, Docente, Estudiante y Proveedor del chatbot. |
| Precondiciones | El usuario debe encontrarse autenticado. El servidor debe disponer de la configuración necesaria para comunicarse con el Proveedor del chatbot. |
| Flujo principal (síntesis) | Recibir consulta de usuario autenticado; validar contexto permitido; enviar al proveedor desde backend; presentar orientación o indisponibilidad sin ejecutar operaciones protegidas. |
| Alternativas / extensiones | Consulta fuera de alcance o respuesta no disponible: orientar/informar; nunca ejecutar una acción administrativa como alternativa. |
| Excepciones documentadas | Proveedor externo no disponible o cuota de uso agotada. Consulta fuera del ámbito de asistencia definido. Intento de obtener información no autorizada para el rol. Error de comunicación con el proveedor: las demás funciones del sistema permanecen disponibles. |
| Postcondición documentada | El usuario recibe orientación relacionada con el uso de la plataforma o un mensaje claro de indisponibilidad cuando el servicio no pueda responder. |
| RF relacionado | RF24 |
| Entidades / clases principales | Usuario, Rol, Permiso, Asociación Rol–Permiso, AsignacionRol |
| Tablas directamente relacionadas | `usuarios`, `roles`, `permisos`, `roles_permisos`, `asignaciones_roles` |
| Persistencia de conversación | No definida en el modelo; no crear tabla de chat. Las tablas listadas sustentan identidad/permisos; el contexto adicional solo se consulta si se autoriza. |
| Módulo futuro y fase | Servicios de Notificación y Asistencia; Fase 12 |
| Pruebas necesarias | T-CU29: Petición de aprobar/cancelar no modifica nada; contexto ajeno excluido; proveedor caído; respuesta/aviso objetivo <=15 s. |

## CU30 — Consultar bitácora de actividad

Fuente: tabla 67; p. 217 (PDF 218); figura 58; análisis figura 104.

| Campo | Especificación |
| --- | --- |
| Prioridad | Media |
| Actor iniciador | Administrador. |
| Actores | Administrador. |
| Precondiciones | El Administrador debe encontrarse autenticado y autorizado para consultar la bitácora. Deben existir registros de actividad almacenados para la consulta solicitada. |
| Flujo principal (síntesis) | Comprobar autorización; filtrar actividad por criterios disponibles; consultar registros; mostrar operación, responsable, fecha, resultado y contexto permitido. |
| Alternativas / extensiones | Sin coincidencias: vacío comprensible; permisos insuficientes: no devolver registros. |
| Excepciones documentadas | No existen registros para los filtros seleccionados. Filtros inválidos o rango de fechas inconsistente. El Administrador intenta consultar información fuera de sus permisos. Fallo al recuperar la información de bitácora. |
| Postcondición documentada | El Administrador obtiene la información histórica necesaria para seguimiento y auditoría interna. |
| RF relacionado | RF25 |
| Entidades / clases principales | Usuario, RegistroActividad |
| Tablas directamente relacionadas | `usuarios`, `registros_actividad` |
| Módulo futuro y fase | Seguridad y Administración; implementación de bitácora pendiente de autorización según Fase 1.5 §15; verificación integral en Fase 13 |
| Pruebas necesarias | T-CU30: Filtros y paginación; usuario no autorizado; evento humano/automático; secreto ausente; conservación de historia. |


## Dependencias transversales de la matriz

Todos los CU humanos requieren las verificaciones de identidad/permiso que indiquen sus precondiciones; Usuario/Rol/Permiso/SesionAcceso no se repiten como escritura directa en cada ficha. CU27/CU28 consultan las fuentes operativas pertinentes; no necesitan tablas de indicadores inventadas. Notificaciones y evidencia se vinculan por RegistroActividad, no mediante FK nuevas en cada reserva/préstamo. Las pruebas negativas deben comprobar tanto el resultado devuelto como que no se produjo una mutación no autorizada.

D-03 resuelve las diferencias entre actores RF y CU mediante la tabla siguiente; D-04 difiere el mecanismo de alcance del encargado sin autorizar una nueva entidad de asignación. CU23 no tiene actor humano directo; CU24 se inicia por una operación confirmada. CU26 describe consulta/lectura, mientras RF21 también prescribe generación automática posterior al evento. Son responsabilidades complementarias.

## Reconciliación de actores RF/CU (D-03 resuelta)

Fuentes contrastadas: tabla 36 (RF) y tablas 38–67 (detalle CU). La lista Usuarios del RF expresa también participación, recepción de información o involucramiento. Para ejecutar una acción se exige el iniciador y flujo del CU, permisos efectivos, RBAC, propiedad/ámbito y guardas del proceso, verificados en servidor. Esta tabla resuelve diferencias sin inventar nuevos permisos ni un catálogo de códigos: el catálogo se deriva en Fase 2 según D-04; el alcance fino por recurso se acredita antes de habilitar su flujo en la fase funcional correspondiente. Ante duda se deniega lo no demostrado. Un usuario con varios roles puede operar por un rol admitido y su permiso; poseer además otro rol no lo excluye.

| CU / RF contrastados | Usuarios del RF (texto fuente) | Iniciador del CU (texto fuente) | Interpretación de ejecución |
| --- | --- | --- | --- |
| CU01 / RF1 | RF1: Administrador, Encargado de laboratorio, Docente y Estudiante. | Usuario registrado. | Cuenta registrada activa y credenciales válidas; solo se habilitan sus permisos efectivos. |
| CU02 / RF1 | RF1: Administrador, Encargado de laboratorio, Docente y Estudiante. | Usuario autenticado. | Usuario autenticado sobre su propia sesión. Cierre confirmado invalida sesión/token; caída de red no prueba revocación remota (D-07). |
| CU03 / RF2 | RF2: Administrador. | Administrador. | Administrador con permiso de gestión de usuarios; no trasladar esa facultad al usuario gestionado. |
| CU04 / RF3 | RF3: Administrador. | Administrador. | Administrador con permiso de roles/permisos; no autoasignación por participar como usuario. |
| CU05 / RF4 | RF4: Administrador y Encargado de laboratorio. | Administrador o Encargado de Laboratorio autorizado. | Administrador o Encargado de Laboratorio autorizado; este último dentro de su ámbito verificable (D-04). |
| CU06 / RF5 | RF5: Administrador, Encargado de laboratorio, Docente y Estudiante. | Usuario autenticado. | Los cuatro roles humanos pueden consultar con permiso y alcance; consultar no concede crear o resolver. |
| CU07 / RF6 | RF6: Docente, Estudiante, Administrador y Encargado de laboratorio, según permisos. | Docente o Estudiante autorizado. | Docente o Estudiante con permiso y cuenta activa. Administrador/Encargado mencionados en RF6 no reciben por ese solo rol facultad de solicitar en CU07. |
| CU08 / RF6, RF7 | RF6: Docente, Estudiante, Administrador y Encargado de laboratorio, según permisos.; RF7: Administrador, Encargado de laboratorio, Docente y Estudiante, según la operación permitida. | Usuario autenticado. | Consulta propia del solicitante o supervisión autorizada del Administrador/Encargado; filtrar por propiedad/ámbito. |
| CU09 / RF7 | RF7: Administrador, Encargado de laboratorio, Docente y Estudiante, según la operación permitida. | Administrador o Encargado de Laboratorio autorizado. | Administrador o Encargado autorizado resuelve; Docente/Estudiante de RF7 participan como solicitantes, no aprobadores. |
| CU10 / RF7 | RF7: Administrador, Encargado de laboratorio, Docente y Estudiante, según la operación permitida. | Usuario autorizado para cancelar la reserva. | Titular con permiso propio, o Administrador/Encargado con permiso administrativo según precisión de Fase 3.5; comprobar estado y ahora<inicio. Docente/Estudiante no cancelan ajenas; no conceder facultades por la lista general de RF7. |
| CU11 / RF8 | RF8: Docente, Estudiante, Administrador y Encargado de laboratorio. | Usuario autorizado que realiza el escaneo. | Los cuatro roles solo con autorización para el recurso/reserva/contexto y horario; escanear no autentica ni concede permisos. |
| CU12 / RF9 | RF9: Administrador y Encargado de laboratorio. | Administrador o Encargado de Laboratorio autorizado. | Administrador o Encargado autorizado gestiona inventario dentro de su ámbito; no ampliar desde las consultas. |
| CU13 / RF10 | RF10: Administrador, Encargado de laboratorio, Docente y Estudiante. | Usuario autenticado. | Los cuatro roles consultan con permiso y alcance. La disponibilidad mostrada no autoriza resolución ni entrega. |
| CU14 / RF11 | RF11: Docente, Estudiante, Administrador y Encargado de laboratorio, según permisos. | Docente o Estudiante autorizado. | Docente o Estudiante inicia con permiso. Administrador/Encargado mencionados por RF11 no obtienen por ese solo rol iniciación de CU14. |
| CU15 / RF11, RF12, RF13, RF14 | RF11: Docente, Estudiante, Administrador y Encargado de laboratorio, según permisos.; RF12: Administrador, Encargado de laboratorio, Docente y Estudiante como solicitantes.; RF13: Encargado de laboratorio, Administrador y usuario receptor del préstamo.; RF14: Encargado de laboratorio, Administrador y usuario que devuelve el equipamiento. | Usuario autenticado. | Solicitante consulta lo propio; Administrador/Encargado supervisa lo autorizado. RF12–14 no otorgan aprobación/entrega/devolución a quien solo consulta. |
| CU16 / RF12 | RF12: Administrador, Encargado de laboratorio, Docente y Estudiante como solicitantes. | Administrador o Encargado de Laboratorio autorizado. | Administrador o Encargado autorizado resuelve. Docente/Estudiante de RF12 es solicitante/participante, no resolvedor. |
| CU17 / RF13 | RF13: Encargado de laboratorio, Administrador y usuario receptor del préstamo. | Administrador o Encargado de Laboratorio autorizado. | Administrador o Encargado autorizado registra entrega; receptor participa en identificación/recepción, sin obtener el permiso de ejecutar CU17. |
| CU18 / RF14 | RF14: Encargado de laboratorio, Administrador y usuario que devuelve el equipamiento. | Administrador o Encargado de Laboratorio autorizado. | Administrador o Encargado autorizado registra devolución; quien devuelve participa, sin obtener permiso de confirmación administrativa. |
| CU19 / RF15 | RF15: Administrador y Encargado de laboratorio. | Administrador o Encargado de Laboratorio autorizado; también puede activarse como extensión de CU18 cuando se detecta una anomalía durante la devolución. | Administrador o Encargado autorizado; también invocación condicional desde CU18. No conceder a todos los receptores capacidad de cerrar incidencias. |
| CU20 / RF13, RF14 | RF13: Encargado de laboratorio, Administrador y usuario receptor del préstamo.; RF14: Encargado de laboratorio, Administrador y usuario que devuelve el equipamiento. | Administrador o Encargado de Laboratorio durante CU17 Registrar entrega o CU18 Registrar devolución. | Administrador o Encargado autorizado en CU17/CU18; el poseedor del QR no sustituye al responsable. Mantener guarda de operación. |
| CU21 / RF16 | RF16: Administrador y Encargado de laboratorio autorizado. | Administrador o Encargado de Laboratorio autorizado. | Administrador o Encargado autorizado registra/configura sensores; un dispositivo no administra su propio alta/habilitación. |
| CU22 / RF17 | RF17: Sistema, Administrador y Encargado de laboratorio. | Dispositivo IoT registrado y habilitado. | Dispositivo IoT inicia el envío autenticado; Administrador/Encargado observa según permisos. La lista humana de RF17 no habilita suplantar eventos. |
| CU23 / RF18 | RF18: Sistema, Administrador, Encargado de laboratorio y usuario titular de la reserva. | CU22 Monitorear ocupación de espacios académicos, cuando se cumplen las condiciones temporales y operativas definidas por la política. | Proceso del sistema como extensión condicional de CU22; sin actor humano directo. El titular involucrado por RF18 no ejecuta liberación manual inferida. |
| CU24 / RF19 | RF19: Sistema y Administrador. | Caso de uso base CU09, CU10, CU17 o CU18 al finalizar correctamente una operación configurada como crítica. | Proceso posterior a operación crítica confirmada de los CU base documentados (CU09, CU10, CU17, CU18); Blockchain colabora. Administrador en RF19 no crea evidencias arbitrarias. |
| CU25 / RF20 | RF20: Administrador y usuario autorizado para auditoría o supervisión. | Administrador. | Administrador autorizado solicita verificación; Blockchain responde. Otros usuarios relacionados por RF20 no obtienen consulta de auditoría global. |
| CU26 / RF21 | RF21: Administrador, Encargado de laboratorio, Docente y Estudiante. | Administrador, Encargado de Laboratorio, Docente o Estudiante autenticado. | Los cuatro roles consultan/marcan sus propios avisos. RF21 agrega generación automática posterior a eventos; no autoriza enviar mensajes arbitrarios. |
| CU27 / RF22 | RF22: Administrador, Encargado de laboratorio, Docente y Estudiante, según permisos. | Usuario autenticado. | Los cuatro roles acceden únicamente a indicadores permitidos por sus roles y ámbito; no ven todos los datos por compartir el CU. |
| CU28 / RF23 | RF23: Administrador y Encargado de laboratorio; otros usuarios únicamente para reportes expresamente habilitados. | Administrador o Encargado de Laboratorio autorizado. | Administrador o Encargado autorizado genera/exporta; Docente/Estudiante de RF23 no recibe ese permiso por la lista de participantes. |
| CU29 / RF24 | RF24: Administrador, Encargado de laboratorio, Docente y Estudiante. | Usuario autenticado. | Usuario autenticado consulta orientación; proveedor responde bajo contexto permitido. Ninguno ejecuta acciones protegidas a través del chatbot. |
| CU30 / RF25 | RF25: Sistema y Administrador; otros usuarios autorizados podrán consultar únicamente los registros permitidos. | Administrador. | Administrador autorizado consulta bitácora. RF25 describe generación transversal por el sistema y usuarios involucrados, no acceso global de todos a la bitácora. |

## CU17: contrato de entrega congelado (D-11)

Prevalecen la tabla 54 (p. 198 / PDF 199), su precondición/flujo textual y los estados físicos sobre la conexión contradictoria de la figura 23. No seguir la flecha que permitiría confirmar con identificación inválida. La secuencia conceptual completa es la siguiente; en Fase 5 sus extensiones QR y Blockchain siguen diferidas según el contrato de Fase 4.5:

1. Existe préstamo APROBADO pendiente de entrega.
2. El responsable autorizado accede al préstamo dentro de su ámbito.
3. El sistema muestra equipamiento y receptor esperado.
4. El responsable verifica físicamente el equipo y la identidad del receptor.
5. Si la política exige QR, se ejecuta CU20 en contexto ENTREGA.
6. Un QR inválido bloquea la confirmación; tampoco se confirma si falla identidad, recurso o permiso.
7. El responsable confirma el equipamiento, revalidando estado y disponibilidad en servidor.
8. Se registra la entrega con fecha/hora, receptor y responsable; unicidad por préstamo.
9. El equipamiento cambia a PRESTADO.
10. El préstamo cambia a ENTREGADO, equivalente persistido de “entregado o activo”. Los pasos 8–10 son una sola operación atómica.
11. Tras confirmar la operación principal, puede generarse evidencia si la operación es crítica según CU24; fallo externo no revierte la entrega.
12. Se muestra confirmación coherente con el resultado persistido.

Pruebas futuras: préstamo no aprobado, equipo/receptor incorrecto, QR obligatorio inválido, doble entrega y concurrencia deben dejar sin confirmación parcial. La rama que requiere QR no se declara aceptada hasta integrar Fase 6; el núcleo de Fase 5 debe funcionar sin QR. No crear validaciones/códigos ni bloquear entrega/devolución por esa integración pendiente.

## Postcondiciones narrativas de préstamos (D-02)

| CU | Redacción documental conservada en la ficha | Contrato de implementación |
| --- | --- | --- |
| CU17 | Entregado/activo | Prestamo ENTREGADO y equipamiento PRESTADO tras entrega confirmada. |
| CU18 | Finalizado/cancelado en la prosa de cierre | Prestamo DEVUELTO solo tras devolución confirmada. No crear CANCELADO, FINALIZADO ni ACTIVO; no inventar un proceso de cancelación de préstamo. |
| CU15 | Vencido | Calculado: estado ENTREGADO y ahora_servidor > fin_previsto; no persistir ni liberar equipo. |
| CU18, condición incierta | Condición no determinable y revisión | Devolución NO_DETERMINABLE y PENDIENTE_REVISION; sin cierre definitivo del préstamo ni disponibilidad automática. |

Las fichas conservan la fuente y sus excepciones para trazabilidad; esta interpretación determina valores persistidos y guardas. No se ejecutaron CU ni pruebas funcionales en esta fase.

## Aplicación de D-04/D-07 tras Fase 1.5

Las fichas conservan actores, flujo, excepciones y tablas del informe. La columna de fase es planificación de implementación, no una modificación del CU. D-04/D-07 están resueltas para el núcleo web de seis tablas; no se ha programado ninguno de estos casos.

- CU01: Laravel Session por correo/contraseña y cuenta ACTIVO, hash de contraseña Argon2id, regeneración final, CSRF y registro obligatorio con hash de ID en sesiones_acceso. Error genérico; fallo del registro no deja acceso autenticado. El catálogo de permisos se deriva de RF/CU antes de implementarse.
- CU02: revocar registro de la sesión actual, cerrar guard, invalidar sesión técnica y regenerar CSRF. Expiración derivada de session.lifetime; ninguna sesión expirada/revocada se renueva. El fallo de red de la ficha se interpreta con D-07: limpieza de estado accesible de la UI no equivale a borrar cookie HttpOnly o confirmar revocación remota.
- CU03/CU04: Administrador necesita permiso explícito y guardas; no bypass por rol. Conservar usuarios, roles, permisos y las dos relaciones M:N; fecha_retiro NULL define asignación activa. Retiro/reasignación preserva historia, y los cambios afectan solicitudes posteriores sin confiar en una copia de permisos en Vue.
- CU05–CU29: se conserva íntegra la matriz de iniciadores. Fase 2 solo prepara capacidades, no implementa recursos/operaciones futuras. El alcance fino del Encargado se resuelve antes de exponer el recurso correspondiente; una lista de actores nunca autoriza por sí sola.
- CU30 y el registro general de actividad exigido por otros CU permanecen pendientes: la nueva limitación de tablas no incluye registros_actividad. No usar logs técnicos, asignaciones_roles o sesiones_acceso para afirmar que se implementó la bitácora; la parte de auditoría general de las postcondiciones no se declarará satisfecha hasta autorizar e integrar ese componente.

Pruebas negativas y de fallos previstas en [BUSINESS_RULES.md](BUSINESS_RULES.md); aislamiento MySQL en [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md#aislamiento-de-pruebas-para-fase-2).

## Evidencia de implementación posterior — Fase 2

CU01/CU02: autenticación web y sesiones implementadas y probadas. CU03/CU04: núcleo de usuarios, roles, permisos e historia implementado; bitácora general aún pendiente. CU30 no implementado. El catálogo de once permisos y la matriz vigente se documentan en [SECURITY_IMPLEMENTATION.md](SECURITY_IMPLEMENTATION.md). Las fichas normativas y sus dependencias se conservan; no se declara satisfacción de las partes excluidas. 65 pruebas/493 aserciones del núcleo y la fundación sobre MySQL testing, sin SQLite ni datos reales.

## Precisión autorizada para CU05/CU06 — Fase 3

La petición §35 sustituye en estos dos flujos la puerta histórica de ámbito fino: se habilita RBAC por módulo/acción sin inventar tablas. CU05 permite gestión a Administrador/Encargado con permiso; CU06 permite consulta a los cuatro roles con espacios.ver. La matriz de iniciadores de otros CU no cambia. CU05 conserva pendiente la bitácora; CU06 conserva reservas e IoT pendientes. No hay CU07–CU11 ni CU23 funcional.

## Contrato congelado de CU07–CU10 — Fase 3.5

**D-05 resuelta para Fase 4; CU07–CU10 NO IMPLEMENTADOS.** Las fichas conservan las fuentes completas, incluidas relaciones con RegistroActividad y extensiones futuras; esas referencias no autorizan crear bitácora, notificaciones, QR o Blockchain en Fase 4.

| CU | Contrato de núcleo para Fase 4 | Dependencias y límites |
| --- | --- | --- |
| CU06 | Mismo predicado central de disponibilidad utilizado por CU07 y aprobación de CU09 | Base implementada en Fase 3; integración de APROBADAS en Fase 4; IoT complementario en Fase 7 |
| CU07 | Docente/Estudiante con reservas.solicitar; espacio operativo, propósito y periodo válidos; transacción con revalidación; alta PENDIENTE | Pendientes pueden coexistir; no crear si hay APROBADA solapada o bloqueo activo; no aprobación implícita ni elección de solicitante ajeno |
| CU08 | Propiedad obligatoria en consulta propia; gestión para Administrador/Encargado con reservas.ver_administrativas | Filtros fecha/estado/espacio; solicitante solo con consulta administrativa. D-04 mantiene ámbito fino diferido, sin tabla nueva |
| CU09 | Administrador/Encargado con reservas.resolver; solo PENDIENTE; aprobar con lock y disponibilidad vigente, o rechazar explícitamente | Conflicto deja PENDIENTE; responsable/fecha atómicos. Rechazo opcionalmente motivado; aprobación deja motivo_rechazo NULL |
| CU10 | PENDIENTE/APROBADA y ahora<inicio; titular con reservas.cancelar_propias o responsable con reservas.cancelar_administrativas | Motivo opcional; actor/fecha de cancelación; conservar resolución previa; no liberar automáticamente ni forzar estado del espacio |

La [matriz de seis permisos](DECISIONS.md#cu08-y-catálogo-mínimo-de-permisos-previsto), [tabla de transiciones](DOMAIN_MODEL.md#transiciones-de-reserva--contrato-de-fase-35) y [aceptación T-R01–17](BUSINESS_RULES.md#aceptación-futura-de-reservas--fase-4) son contratos, no nuevas filas en permisos ni pruebas ejecutadas. No hay bypass administrativo. La precisión de consulta/gestión por módulo y propiedad sustituye para este núcleo la puerta histórica de acreditar asignaciones finas de recursos; no habilita esas asignaciones por inferencia.

## Contrato vigente de CU12–CU19 — Fase 4.5

Las fichas anteriores conservan los actores y flujos globales del informe. Sus tablas de integraciones y postcondiciones de bitácora/evidencia/notificación no implican implementación anticipada. [Contrato central y evidencia de fuentes](DECISIONS.md#contrato-de-equipamiento-y-préstamos--fase-45), [permisos previstos](DECISIONS.md#d-04-aplicada-y-catálogo-previsto) y [aceptación futura T-P01–18](BUSINESS_RULES.md#aceptación-futura-de-equipamiento-y-préstamos--fase-5).

CU14/CU16 incluyen CU13 con una sola regla de disponibilidad y distinta garantía de lectura al confirmar. CU17 deriva receptor del solicitante sin delegación; conserva recepcion_confirmada_en NULL sin crear un CU posterior. CU18 confirma el retorno físico adecuado o con incidencia; la revisión indeterminable deja préstamo ENTREGADO y equipo EN_MANTENIMIENTO preventivo (BAJA se conserva). Confirmación posterior de la misma fila es continuación CU18/CU19 con permiso de devolución y, ante anomalía, de incidencias. No añade caso de uso, devolución duplicada ni historial alternativo.

CU19 abarca espacios y equipos. Identifica compromisos afectados sin cancelarlos; toda ABIERTA impide nueva operación incompatible, y el cierre valida otras causas antes de una habilitación explícita. SpaceAvailabilityService se ampliará únicamente durante Fase 5. D-04 conserva módulo/acción y propiedad, ámbito fino diferido. QR CU20 pertenece a Fase 6 y Blockchain CU24 a Fase 9 después del commit; RF21/Fase 10 y CU30 continúan pendientes. No se ejecutaron CU12–CU19 ni pruebas funcionales en Fase 4.5.

## Estado vigente de CU12–CU19 y CU06 — Fase 5

El núcleo autorizado de CU12–CU19 está implementado: inventario, disponibilidad, solicitud, consulta propia/administrativa, resolución, entrega, devolución/revisión e incidencias comunes. CU06 integra incidencias ABIERTA; CU07/CU09 revalidan ese mismo predicado. [CU → tablas/modelos → servicios/Policies → vistas → tests](EQUIPMENT_LOANS_IMPLEMENTATION.md#trazabilidad-de-implementación). La verificación manual de 28 pasos y las 262 pruebas cubren ese alcance. Las extensiones CU11/CU20/QR, evidencia Blockchain, RF21 y bitácora CU30 de las fichas normativas mantienen su aceptación y límite físico documentado; no se declara cumplimiento de sus postcondiciones diferidas.

## Contrato vigente CU11/CU20 y extensiones — Fase 5.5

**D-09 resuelta documentalmente; Fase 6 NO IMPLEMENTADA.** Las fichas originales conservan su origen; [este contrato](DECISIONS.md#contrato-qr-y-validaciones--cierre-d-09-de-fase-55) fija sus precisiones implementables sin ampliar estados ni inventar obligatoriedad universal.

| CU | Contrato de Fase 6 | Evidencia futura |
| --- | --- | --- |
| CU11 / RF8 | Desde reserva autorizada, cámara local y token; APROBADA en [inicio,fin), QR del espacio o de esa reserva; D/S propios, A/E permiso administrativo además del QR; guardas operativas centrales excluyen la propia reserva del conflicto; registra resultado sin transición | T-Q08–11/15–17/23–24 |
| CU17 + CU20 / RF13 | Extensión explícita; permisos QR+entrega, APROBADO/equipo disponible/receptor solicitante/comprobación física; una petición confirma validación, entrega y enlace atómicamente; recepcion_confirmada_en sigue NULL | T-Q12/18–22 |
| CU18 + CU20 / RF14 | Extensión explícita; permisos QR+devolución, ENTREGADO/entrega válida/sin retorno; incidencia/BAJA no impiden identificar retorno físico; mantiene tres condiciones; revisión conserva misma devolución y enlace | T-Q13–14/18–22 |
| CU20 transversal | QR del mismo equipo o préstamo exacto; INVALIDA detiene la rama QR sin fallback automático; servicio QR no ejecuta transiciones; múltiples intentos se conservan y los hitos tienen asociación máxima única | T-Q09/15–22 |

La pantalla completa datos/verificación e intención «escanear y confirmar» antes de la lectura final y envía inmediatamente al decodificar; servidor valida antes de persistir el hito. No existe un resultado frontend que autorice ni una validación antigua transferible. No entrada manual, no locks durante captura, sin frames a terceros. Cámara ausente/denegada no crea VALIDA. Las obligaciones Blockchain, política de liberación, notificaciones y CU30 se conservan para sus fases.

## Estado vigente CU11/CU20 y extensiones CU17/CU18 — Fase 6

Implementados CU11 y CU20 con identidad, RBAC/contexto actual, token opaco/hash y evidencia. CU11 no cambia reserva/espacio. CU20 extiende opcionalmente los servicios principales de entrega/devolución en una misma request/transacción y enlaza la misma validación al hito. INVÁLIDA no muta negocio; los flujos sin QR siguen funcionando. Las postcondiciones Blockchain/notificaciones/CU30 mantienen su diferimiento.

[Trazabilidad completa y aceptación](QR_IMPLEMENTATION.md): suite 325/4984 aprobada, dos procesos MySQL, cámaras simuladas diferenciadas de física. La lectura física se acepta por confirmación expresa del usuario; no se presenta como corroboración técnica independiente. Las frases de implementación futura en contratos Fase 5/5.5 son históricas, no anulan este avance.

## Preparación CU21/CU22 y límites CU06/CU23 — Fase 6.5

[D-10 RESUELTA PARA FASE 7](DECISIONS.md#readiness-for-phase-7), cero bloqueantes documentales; casos todavía no funcionales. CU21 incluye alta/consulta/asociación/reasociación/habilitación/deshabilitación/última comunicación/provisión segura, sin eliminación. CU22 usa REST/HMAC separado de identidad humana, histórico inmutable, filtros paginados y lectura reciente/NO_DETERMINABLE. No sustituir bitácora prevista en RF16 con logs técnicos; registros_actividad/CU30 conservan su fase diferida.

A/E con permisos específicos iot.sensores.ver, iot.sensores.gestionar e iot.monitoreo.ver; D/S sin módulo administrativo/historial. CU06 podrá mostrar solo evidencia mínima autorizada, sin alterar SpaceAvailabilityService. CU23 mantiene ficha original como obligación futura de Fase 8; no se activa por eventos en Fase 7. [Matriz de aceptación T-I01–T-I32](BUSINESS_RULES.md#aceptación-iot-prevista-para-fase-7), no ejecutada en este cierre.


## Evidencia funcional CU21/CU22 — Fase 7

CU21 implementa gestión/provisión y CU22 recepción/historia/monitoreo; [trazabilidad T-I01–T-I32](IOT_IMPLEMENTATION.md#verificación-y-trazabilidad-de-aceptación). CU06 añade evidencia ambiental mínima sin alterar filtros de disponibilidad. CU23 permanece pendiente de Fase 8 y la bitácora general de RF16 permanece pendiente de CU30.

## Contrato cerrado de CU24 y CU25 — Fase 8.5

Las tablas fuente de CU24/CU25 conservan sus actores y flujos; [D-12](DECISIONS.md#contrato-blockchain--cierre-d-12-de-fase-85) completa sus vacíos. **Contrato resuelto, comportamiento todavía no implementado.**

| Caso | Contrato futuro aplicable | Permisos / aceptación |
| --- | --- | --- |
| CU24 | Solo CU09 aprobado, CU10 cancelado, CU17 entregado y CU18 finalmente devuelto. Actividad crítica EXITO en la transacción base; evidencia JCS/SHA-256 después del commit y envío automático Sepolia. Caída externa conserva negocio y pendiente; recuperación durable e idempotente. | Proceso interno; T-B01–T-B28, T-B37–T-B40 |
| CU25 | Reconstruir historia y comparar primero; después comprobar referencia/contrato/red si REGISTRADA. Persistir última verificación; cuatro resultados exactos. Caída RPC con REGISTRADA produce NO_VERIFICABLE sin retroceso. | Administrador con trazabilidad.evidencias.ver + trazabilidad.evidencias.verificar; T-B29–T-B36 |
| Recuperación manual | Solo evidencia PENDIENTE; mismo servicio, sin nueva evidencia/hash; no sustituye scheduler normal | Administrador con trazabilidad.evidencias.ver + trazabilidad.reintentar; T-B23–T-B28/35 |

CU25 no asigna Encargado por la referencia genérica de RF20 a supervisión. Administrador tampoco evita permisos efectivos, sesión/CSRF y guardas. Docente/Estudiante no acceden a administración de evidencia. No se crea actividad recursiva por una verificación; CU30 y su consulta general siguen diferidos.

CU23 no extiende CU24 en este alcance: APROBADA→LIBERADA excluida explícitamente. Validaciones QR/IoT e incidencias tampoco generan evidencia individual. Fuente RF/CU y textos originales de los requisitos permanecen sin alteración; [matriz de pruebas futuras](BUSINESS_RULES.md#aceptación-futura-blockchain--fase-9).
