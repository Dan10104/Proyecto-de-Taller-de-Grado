# Reglas de negocio y controles verificables

Estas reglas sintetizan el informe. Los identificadores BR son internos para organizar pruebas; no sustituyen los RF/CU. Los estados exactos están en [DOMAIN_MODEL.md](DOMAIN_MODEL.md). Las decisiones D clasificadas y sus detalles diferidos están en [DECISIONS.md](DECISIONS.md).

| Regla | Tema | Fuente | Restricción | Prueba futura necesaria |
| --- | --- | --- | --- | --- |
| BR-01 | Acceso y revocación | RF1; CU01–02; RNF-10–15 | Cuenta activa, credenciales válidas, hash de contraseña y sesión vigente; invalidar sesión/token al salir. | Acceso válido; contraseña errónea; inactivo; expirado; petición posterior al logout denegada. |
| BR-02 | Autorización por objeto | RF2–3; CU03–04; RNF-11/15 | Permiso en servidor, propiedad de solicitud y ámbito del responsable; ocultar botones no basta. D-03 y RBAC de D-04 resueltos para Fase 2; ámbito fino diferido hasta el flujo por recurso, con denegación si no puede acreditarse. | Otro usuario no consulta ni modifica solicitudes ajenas; encargado no opera fuera de su ámbito. |
| BR-03 | Historia de roles | CU03–04; asignaciones_roles | Registrar asignador/retirador y fechas, conservar historia; desactivar cuenta sin borrar operaciones. | Retiro invalida acciones futuras; historia conserva responsables y fechas. |
| BR-04 | Disponibilidad del espacio | RF4–6; CU05–07; DB-17 | Combinar estado, horario, reservas y bloqueos/mantenimiento; reconsultar al confirmar; ocupación IoT es evidencia complementaria. | Dos peticiones concurrentes incompatibles no confirman ambas; mantenimiento impide nueva operación. |
| BR-05 | Resolución y cancelación | RF7; CU09–10 | Resolver solicitud autorizada una sola vez, verificar disponibilidad y registrar decisión/autor/fecha/motivo pertinente. Cancelar solo si estado, plazo y permiso lo permiten. D-05. | Doble resolución; cambio de recurso entre consulta/aprobación; cancelación fuera de plazo o ajena. |
| BR-06 | Disponibilidad de equipamiento | RF9–12; CU12–16; DB-18 | Considerar estado, periodos, préstamos e incidencias; no aprobar equipo prestado, no disponible, en baja o mantenimiento. | Conflictos concurrentes y equipo que cambió de estado después de consultarse. |
| BR-07 | Entrega | RF13; CU17; figura 23 | Préstamo aprobado, equipo disponible y receptor identificado; QR válido si se exige; fecha, responsable y receptor; equipo PRESTADO y préstamo ENTREGADO. D-11. | Préstamo no aprobado; receptor incorrecto; equipo distinto; QR inválido; doble entrega. |
| BR-08 | Devolución | RF14; CU18; enums | Comprobar identificación y condición; registrar responsable/fechas; condición adecuada permite disponibilidad; anomalía deriva a incidencia. NO_DETERMINABLE queda PENDIENTE_REVISION. D-02. | Equipo incorrecto; doble devolución; QR inválido; retorno con daño; condición desconocida sin cierre definitivo. |
| BR-09 | Incidencia y mantenimiento | RF15; CU19; DB-21 | Asociar recurso, motivo, responsable y periodo; conservar historia; restituir disponibilidad solo sin otras restricciones incompatibles. | Otra incidencia abierta impide restaurar disponibilidad al cerrar una anterior. |
| BR-10 | Objetivo QR | RF8/13/14; CU11/20; SQL | Cada código identifica exactamente un recurso, reserva o préstamo; token con hash, vigencia y bandera activo. | Cero/dos objetivos rechazados; expirado/revocado; token desconocido. |
| BR-11 | Validación QR contextual | CU11/20; figuras 111/121 | Verificar usuario, permisos, objetivo, recurso esperado, contexto, horario/estado y tipo de operación; inválido no confirma uso/entrega/devolución. D-09. | QR de otro equipo/reserva; usuario ajeno; horario incompatible; cámara denegada; reintento. |
| BR-12 | Recepción IoT | RF16–17; CU21–22; DB-23 | Aceptar evento solo de sensor registrado y habilitado; guardar fechas y espacio histórico; actualizar comunicación válida. No identificar personas. D-10. | Sensor desconocido/deshabilitado; payload inválido; reasignación conserva eventos previos. |
| BR-13 | Frescura y ocupación | RF17–18; CU22–23 | Ausencia o antigüedad de lecturas no demuestra desocupación; mostrar ocupación no determinable; mantener gestión principal ante caída IoT. | Sin eventos, eventos antiguos y caída de sensor no se muestran automáticamente como libre. |
| BR-14 | Evaluación de liberación | RF18; CU23; evaluaciones_liberacion | Evaluar reserva aprobada aplicable, tolerancia y evidencias. Resultado MANTENER_POR_USO, NO_DETERMINABLE, ALERTAR o LIBERAR. Solo modo AUTOMATICA y condiciones suficientes permiten liberar. D-08. | Evidencia de uso mantiene; insuficiente/contradictoria no libera; ALERTA no cambia reserva a LIBERADA. |
| BR-15 | Transacción principal | DB-15/17/20; R-PR-01 | Confirmar consistentemente estados, datos obligatorios y relaciones; fallos previos a confirmación no dejan operación parcial. | Fallo intermedio revierte todos los cambios relacionados; concurrencia no duplica confirmaciones. |
| BR-16 | Evidencia local y envío | RF19; CU24; figura 114; R-PR-03/04 | Partir de operación confirmada; seleccionar datos mínimos y validar privacidad antes de enviar; registrar hash/versión/estado local; caída externa conserva PENDIENTE sin revertir operación. D-12. | Proveedor caído conserva operación y evidencia; campos privados nunca salen; reintentos sin evidencia duplicada. |
| BR-17 | Verificación | RF20; CU25 | Recalcular con mismo algoritmo/versión canónica y comparar evidencia; distinguir VALIDO, INCONSISTENTE, PENDIENTE y NO_VERIFICABLE. | Alteración detectada; referencia faltante; proveedor caído; usuario no autorizado. |
| BR-18 | Notificación | RF21; CU26; R-PR-07 | Generar aviso interno después de confirmar el evento; vincular actividad/destinatario; listar solo propios y marcar leída según interacción. | Usuario ajeno no lee aviso; error al notificar conserva operación; lectura actualiza leida_en. |
| BR-19 | Indicadores y reportes | RF22–23; CU27–28 | Consultar datos reales permitidos y filtros válidos; diferenciar sin datos de error; PDF y Excel respetan alcance de consulta. | Exportación y pantalla con mismo filtro/permisos; vacío comprensible; descarga ajena denegada. |
| BR-20 | Chatbot de orientación | RF24; CU29; RD-15 | Backend usa contexto permitido y secreto privado; respuesta orienta sin ejecutar acciones protegidas; indisponibilidad no bloquea navegación. | Pedir aprobar/cancelar no modifica estado; otro usuario no obtiene datos; caída devuelve mensaje acotado. |
| BR-21 | Bitácora | RF25; CU30; DB-22 | Operación, fecha, resultado y responsable humano o proceso; contexto suficiente para seguimiento; consulta restringida y sin secretos. | Rechazo/error registrado sin contraseña/token; filtros autorizados; evento automático identifica proceso. |
| BR-22 | Configuración y privacidad | RNF-14/17; IS-14; RD-19/20/23/43/44 | Datos operativos completos en MySQL; secretos solo configuración segura; clientes y evidencia externa sin credenciales/datos sensibles. | Inspeccionar bundles, logs y payloads con valores sintéticos; verificar ausencia de secretos reales. |
| BR-23 | Rendimiento y continuidad | R-TR/R-CC/R-UR/R-DISP/R-ESC/R-PR | Cumplir límites cuantificados de trazabilidad con carga representativa, fallo de dependencias y recuperación real. | Medir extremos máximos, concurrencia, eventos aceptados, respaldo diario y restauración <=4 h. |
| BR-24 | Lenguaje y zona horaria | IU-01/04/05/08/10; RD-24–29 | Web responsiva en español, formatos de Bolivia y estados comprensibles; presentar condiciones derivadas sin inventar enums. | Pantallas pequeñas/escritorio; fechas límite locales; errores de formulario comprensibles. |


## Intervalos y disponibilidad (D-05: convención de implementación)

Se adopta `[inicio, fin)`: inicio incluido, fin excluido, siempre `fin > inicio`. Dos intervalos A y B se solapan exactamente cuando `inicio_A < fin_B AND inicio_B < fin_A`. Aplicar la misma convención a reservas, bloqueos activos y los campos `inicio_previsto`/`fin_previsto` de préstamos. No añadir campos ni cambiar los CHECK del SQL.

| Intervalo A | Intervalo B | Resultado |
| --- | --- | --- |
| 08:00–09:00 | 09:00–10:00 | No se solapan |
| 08:00–09:00 | 08:30–09:30 | Se solapan |
| 08:00–10:00 | 08:30–09:00 | Se solapan por contención |
| 08:00–09:00 | 08:00–09:00 | Se solapan |
| 09:00–09:00 | Cualquier intervalo | Inválido: duración cero |
| 10:00–09:00 | Cualquier intervalo | Inválido: fin anterior |

| Elemento | Condición que compromete disponibilidad | Interpretación |
| --- | --- | --- |
| Reserva | APROBADA del mismo espacio que solapa | Es la reserva activa comprometida; no añadir ACTIVA como enum. |
| Solicitud de reserva | PENDIENTE | No concede exclusividad ni garantiza aprobación. Revalidar disponibilidad al registrar y resolver; solicitudes pendientes pueden competir por un intervalo aún no adjudicado. |
| Reserva terminal | RECHAZADA, CANCELADA, LIBERADA, FINALIZADA | No bloquea el periodo; no suprimir otras restricciones del espacio. |
| Bloqueo | activo=true y solapamiento | Impide disponibilidad del espacio durante el periodo aplicable. |
| Estado de espacio | BLOQUEADO o EN_MANTENIMIENTO | Impide operación incompatible aunque el calendario no tenga reservas. DISPONIBLE exige además comprobar reservas/bloqueos. |
| Préstamo | APROBADO o ENTREGADO con periodo previsto solapado | Compromete ese equipo; no aprobar dos préstamos incompatibles. |
| Solicitud de préstamo | PENDIENTE | No concede exclusividad. La resolución vuelve a validar disponibilidad y estado operativo. |
| Préstamo terminal | RECHAZADO o DEVUELTO | No bloquea por su periodo previsto; incidencias/mantenimiento pueden seguir bloqueando el equipo. |
| Estado/custodia de equipo | PRESTADO, EN_MANTENIMIENTO, BAJA o restricción incompatible | Impide aprobar/entregar según DB-18 y CU. Pasar fin_previsto no devuelve físicamente el equipo ni lo cambia a DISPONIBLE. |

La selección de estados que comprometen el periodo es una convención explícita de D-05, coherente con solicitud→resolución; no se presenta como frase literal del informe. Es obligatoria la revalidación transaccional y el control concurrente al adjudicar, entregar o liberar. El algoritmo de serialización se seleccionará y probará en su fase.

Cancelar una reserva solo desde PENDIENTE o APROBADA, con iniciador de CU10, permiso/propiedad y plazo permitido. El motivo se exige cuando el flujo lo requiera; conservar la nulabilidad física. Los límites exactos de cancelación y el disparador autorizado de FINALIZADA se cerrarán antes de Fase 4; no inferirlos de la hora actual ni implementar cierre automático ahora.

## Tiempo, identificadores y estados (D-01, D-02, D-16)

Identificadores técnicos ASCII, sin tildes, snake_case cuando corresponda; `permisos.codigo` es el nombre resuelto. Textos humanos en español correcto. Los 19 ENUM y las condiciones derivadas están reconciliados en DOMAIN_MODEL; no añadir valores por palabras narrativas.

Convención temporal del alcance institucional único: `America/La_Paz` para captura, presentación y persistencia operativa en los DATETIME existentes. Un instante externo con zona explícita se convierte a esta referencia antes de persistir/comparar; no mezclar horas UTC con horas locales sin conversión. Configurar aplicación/conexión coherentemente en Fase 1 y probar conversiones antes de IoT. No añadir columnas ni fijar aquí el formato canónico Blockchain: su representación exacta y la normalización de fechas se cerrarán antes de Fase 9 (D-12).

## Contratos congelados de integraciones

**QR (D-09).** El código identifica recurso u operación, no autentica. El servidor comprueba usuario autenticado, permisos, código, recurso esperado, operación/contexto, estado y fecha/horario aplicable. Respetar objetivo único del código y exclusividad reserva/préstamo por tipo de validación; enlaces a entrega/devolución según CHECK/UNIQUE. Código inválido no confirma uso/entrega/devolución. Emisión, vigencia exacta y correlación de reintentos se especificarán antes de Fase 6; no inventar un nuevo CRUD ni suponer un token de un solo uso.

**IoT (D-10).** Detecta presencia/ocupación, nunca identidad. Sensor registrado, habilitado y asociado a espacio; preservar espacio histórico y fechas de evento/recepción. Falta de lectura reciente produce NO_DETERMINABLE calculado, sin agregar enum de espacio. El formato, unidades, transporte, autenticación y ventana concreta se cierran antes de Fase 7.

**Liberación (D-08).** Evaluar reserva y política aplicables, tolerancia, validaciones QR, eventos IoT y condiciones configuradas; registrar evaluación y enlaces existentes. Información ausente, insuficiente o contradictoria no libera automáticamente; solo una política e informe que permitan inequívocamente la decisión pueden habilitarla. Evidencia de uso mantiene; ALERTA no libera; AUTOMATICA requiere todas las condiciones aplicables. No interpretar silencio del sensor como ausencia de personas. Combinaciones exactas, disparador y conservación de versión de política se cierran antes de Fase 8.

**Blockchain (D-12).** Secuencia: operación crítica → registro principal confirmado en MySQL → selección de datos mínimos → representación canónica consistente → hash → evidencia local → envío externo → referencia externa. Fallo externo conserva la operación y evidencia PENDIENTE cuando corresponda; no rollback de la transacción principal. Nunca enviar nombres, correos, contraseñas, información personal completa ni datos operativos completos. La canonicalización deberá ser determinista, con orden de campos y formato de fechas estables, UTF-8, sin campos personales no permitidos y versionada por `version_canonica`. Su especificación exacta, algoritmo y proveedor se cierran antes de Fase 9; aquí no se elige silenciosamente JSON, una norma de canonicalización o un algoritmo hash concreto.

## Límites diferidos y pruebas futuras

D-04/D-07 cerradas en Fase 1.5: Laravel Session web y expiración desde configuración; RBAC y convención de permisos oficiales. El catálogo concreto se deriva en Fase 2; el alcance fino se acredita antes del flujo funcional por recurso. Autenticación móvil/API diferida. Plazos de cancelación y finalización: antes de Fase 4. Emisión/correlación QR: antes de Fase 6. Payload, transporte y frescura IoT: antes de Fase 7. Combinación de evidencias, disparador y versión de política: antes de Fase 8. Canonicalización exacta, proveedor y recuperación Blockchain: antes de Fase 9. Protocolos de medición/retención: fases de integración/operación según D-16. Las versiones de herramientas ya se seleccionaron y verificaron en Fase 1 (D-15).

Estos detalles no bloquean inicializar la base técnica, pero sí el punto de implementación que depende de ellos. Las pruebas de este documento son obligaciones futuras, no resultados ejecutados. El cierre de Fase 0.5 valida contratos documentales y no certifica comportamiento de software.

## Criterios de aceptación de seguridad — Fase 1.5

Son contratos de prueba para Fase 2, no tests ejecutados ni cambios del modelo. Aplican D-04/D-07 y mantienen las reglas BR-01–03. El alcance de seis tablas no satisface aún la bitácora general BR-21/CU30.

| Área | Comprobación requerida antes de aceptar su implementación |
| --- | --- |
| Identidad | Correo/contraseña válidos y cuenta ACTIVO permiten login completo. Cuenta inexistente, INACTIVO o contraseña errónea no revela cuál falló ni crea una sesión autenticada parcial. |
| Contraseñas | Hash/Argon2id en password_hash; nunca texto plano, SHA-256 simple ni valores sensibles en props/logs. |
| Fijación y registro | ID final diferente al previo; hash de ese ID en sesiones_acceso, no del intermedio. Fallo del registro/almacenamiento impide confirmar login. |
| Vigencia | Aceptar antes del límite; rechazar exactamente en expira_en o después, antes de intentar renovar. Actividad válida renueva según session.lifetime; fallo o sesión caducada no revive acceso. |
| Revocación | Registro revocado, ausente, ajeno o cuenta deshabilitada bloquea la petición aunque exista cookie técnica. Reactivar cuenta no revive sesiones revocadas. |
| Logout y concurrencia | Marcar revocación, invalidar sesión y renovar CSRF. ID anterior no autentica; petición simultánea no borra revocación ni reconstruye autenticación cerrada. Sesión expirada/repetición no reactivan registros. |
| Fallos de red | Distinguir limpieza de UI y cierre remoto confirmado; no afirmar que Vue borró una cookie HttpOnly. Completar cierre al recuperar comunicación antes de reanudar el flujo. |
| CSRF y cookies | Rechazar escrituras/login/logout sin protección válida; cookie de autenticación HttpOnly/SameSite y Secure según entorno; no credenciales alternativas en almacenamiento JS. |
| RBAC y actores | Administrador sin el permiso requerido recibe denegación. Estudiante/Docente no aprueban por figurar como participantes; tampoco se combinan rol elegible y permiso de un rol incompatible. |
| Historia | fecha_retiro NULL define actividad; retiro lógico conserva todas las filas y responsables. Duplicados no multiplican privilegios; reasignación no reescribe historia. |
| Cambios de permisos | Retiro de rol/permiso surte efecto en la siguiente solicitud, sin depender de volver a iniciar sesión ni de las opciones visibles en Vue. |
| Ámbito y propiedad | Consultas propias no exponen terceros. No implementar acceso de Encargado a recursos hasta acreditar ámbito en su fase; no crear tabla de asignación por suposición. |
| Aislamiento | Una suite con escritura aborta si la conexión efectiva no es MySQL de testing autorizado; jamás migra/limpia espacio_u. |

Límites del control de intentos se tomarán de configuración y se probarán en sus fronteras, sin un número hardcodeado en varias capas. CU01–CU04 y estos controles siguen sin implementar.
