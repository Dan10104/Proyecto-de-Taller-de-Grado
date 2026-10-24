# Reglas de negocio y controles verificables

**Actualización documental Fase 6.5: D-10 RESUELTA PARA FASE 7, cero bloqueantes documentales.** [Contrato IoT y puerta de Fase 7](DECISIONS.md#readiness-for-phase-7). CU21/CU22 no implementados. La evidencia funcional anterior sigue correspondiendo a Fase 6 y conserva sus límites.

**Estado vigente: Fase 6 implementada y aceptada; suite automatizada aprobada. La prueba física se acepta por confirmación del usuario, sin nueva evidencia válida corroborada independientemente.** CU11/CU20 y extensión opcional CU17/CU18 incorporados. [Arquitectura, 325 pruebas, trazabilidad y límites de aceptación](QR_IMPLEMENTATION.md). Los cierres anteriores se conservan como evidencia histórica; Fase 7 no iniciada.

Estas reglas sintetizan el informe. Los identificadores BR son internos para organizar pruebas; no sustituyen los RF/CU. Los estados exactos están en [DOMAIN_MODEL.md](DOMAIN_MODEL.md). Las decisiones D clasificadas y sus detalles diferidos están en [DECISIONS.md](DECISIONS.md).

| Regla | Tema | Fuente | Restricción | Prueba futura necesaria |
| --- | --- | --- | --- | --- |
| BR-01 | Acceso y revocación | RF1; CU01–02; RNF-10–15 | Cuenta activa, credenciales válidas, hash de contraseña y sesión vigente; invalidar sesión/token al salir. | Acceso válido; contraseña errónea; inactivo; expirado; petición posterior al logout denegada. |
| BR-02 | Autorización por objeto | RF2–3; CU03–04; RNF-11/15 | Permiso en servidor, propiedad de solicitud y ámbito del responsable; ocultar botones no basta. D-03 y RBAC de D-04 resueltos; Fases 3/3.5 precisan CU05–CU10 y Fase 4.5 aplica CU12–CU19 por módulo/acción y propiedad, conservando diferido el ámbito fino por recurso. | Sin permiso administrativo no se consultan/modifican solicitudes ajenas; comprobar rol elegible y permiso del mismo rol. |
| BR-03 | Historia de roles | CU03–04; asignaciones_roles | Registrar asignador/retirador y fechas, conservar historia; desactivar cuenta sin borrar operaciones. | Retiro invalida acciones futuras; historia conserva responsables y fechas. |
| BR-04 | Disponibilidad del espacio | RF4–6; CU05–07; DB-17 | Combinar estado, horario, reservas y bloqueos/mantenimiento; reconsultar al confirmar; ocupación IoT es evidencia complementaria. | Dos aprobaciones solapadas no confirman ambas; dos PENDIENTE pueden coexistir. Mantenimiento impide solicitar/aprobar. |
| BR-05 | Resolución y cancelación | RF7; CU09–10 | Resolver solicitud autorizada una sola vez, verificar disponibilidad y registrar decisión/autor/fecha/motivo pertinente. Cancelar solo si estado, plazo y permiso lo permiten. D-05. | Doble resolución; cambio de recurso entre consulta/aprobación; cancelación fuera de plazo o ajena. |
| BR-06 | Disponibilidad de equipamiento | RF9–12; CU12–16; DB-18 | Considerar estado, periodos, préstamos e incidencias; no aprobar equipo prestado, no disponible, en baja o mantenimiento. | Conflictos concurrentes y equipo que cambió de estado después de consultarse. |
| BR-07 | Entrega | RF13; CU17; figura 23 | Préstamo aprobado, equipo disponible y receptor identificado; QR válido si se exige; fecha, responsable y receptor; equipo PRESTADO y préstamo ENTREGADO. D-11. | Préstamo no aprobado; receptor incorrecto; equipo distinto; QR inválido; doble entrega. |
| BR-08 | Devolución | RF14; CU18; enums | Comprobar identificación y condición; registrar responsable/fechas; condición adecuada permite disponibilidad; anomalía deriva a incidencia. NO_DETERMINABLE queda PENDIENTE_REVISION. D-02. | Equipo incorrecto; doble devolución; QR inválido; retorno con daño; condición desconocida sin cierre definitivo. |
| BR-09 | Incidencia y mantenimiento | RF15; CU19; DB-21 | Asociar recurso, motivo, responsable y periodo; conservar historia; restituir disponibilidad solo sin otras restricciones incompatibles. | Otra incidencia abierta impide restaurar disponibilidad al cerrar una anterior. |
| BR-10 | Objetivo QR | RF8/13/14; CU11/20; SQL | Cada código identifica exactamente un recurso, reserva o préstamo; token con hash, vigencia y bandera activo. | Cero/dos objetivos rechazados; expirado/revocado; token desconocido. |
| BR-11 | Validación QR contextual | CU11/20; figuras 111/121 | Verificar usuario, permisos, objetivo, recurso esperado, contexto, horario/estado y tipo de operación; inválido no confirma uso/entrega/devolución. D-09. | QR de otro equipo/reserva; usuario ajeno; horario incompatible; cámara denegada; reintento. |
| BR-12 | Recepción IoT | RF16–17; CU21–22; DB-23 | Aceptar evento solo de sensor registrado y habilitado; guardar fechas y espacio histórico validado al aceptar; actualizar comunicación solo por evento nuevo aceptado, nunca duplicado/rechazado. No identificar personas. D-10. | Sensor desconocido/deshabilitado; payload inválido; reasignación conserva eventos previos. |
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

La selección de estados que comprometen el periodo es una convención explícita de D-05, coherente con solicitud→resolución; no se presenta como frase literal del informe. Es obligatoria la revalidación transaccional y el control concurrente al adjudicar, entregar o liberar. Para reservas, el algoritmo queda fijado documentalmente en Fase 3.5: lock de la fila del espacio y revalidación actual dentro de la transacción, con orden compatible con la coordinación RBAC existente. Implementado y probado en Fase 4; su aplicación documental a préstamos se cierra en Fase 4.5, con ejecución futura en Fase 5.

Cancelar una reserva solo desde PENDIENTE o APROBADA, con iniciador de CU10 y permiso propio más propiedad, o permiso administrativo de Administrador/Encargado. Fase 3.5 fija la guarda estricta `ahora < inicio` para ambos caminos; igualdad y posterioridad se deniegan. Motivo de cancelación opcional y nullable; motivo de rechazo opcional según la precisión de CU09. FINALIZADA automática queda fuera de Fase 4; no inferir un cambio persistido de la hora actual. [Contrato D-05](DECISIONS.md#contrato-de-reservas--cierre-d-05-de-fase-35).

## Tiempo, identificadores y estados (D-01, D-02, D-16)

Identificadores técnicos ASCII, sin tildes, snake_case cuando corresponda; `permisos.codigo` es el nombre resuelto. Textos humanos en español correcto. Los 19 ENUM y las condiciones derivadas están reconciliados en DOMAIN_MODEL; no añadir valores por palabras narrativas.

Convención temporal del alcance institucional único: `America/La_Paz` para captura, presentación y persistencia operativa en los DATETIME existentes. Un instante externo con zona explícita se convierte a esta referencia antes de persistir/comparar; no mezclar horas UTC con horas locales sin conversión. Configurar aplicación/conexión coherentemente en Fase 1 y probar conversiones antes de IoT. No añadir columnas ni fijar aquí el formato canónico Blockchain: su representación exacta y la normalización de fechas se cerrarán antes de Fase 9 (D-12).

## Contratos congelados de integraciones

**QR (D-09, RESUELTA PARA FASE 6).** El código identifica recurso u operación, no autentica ni autoriza por sí solo. El servidor comprueba usuario autenticado/ACTIVO, permisos, código, recurso esperado, operación/contexto, estado y fecha/horario. Objetivo único y exclusividad reserva/préstamo por tipo; enlaces a entrega/devolución según CHECK/UNIQUE. Código inválido no confirma uso/entrega/devolución. Emisión, vigencia y correlación quedan fijadas en el [contrato documental de Fase 5.5](DECISIONS.md#contrato-qr-y-validaciones--cierre-d-09-de-fase-55); no uso único ni obligación global. Fase 6 implementada y verificada automáticamente; aceptación física confirmada por el usuario en QR_IMPLEMENTATION.md.

**IoT (D-10).** Detecta presencia/ocupación, nunca identidad. Sensor registrado, habilitado y asociado a espacio; preservar espacio histórico y fechas de evento/recepción. Falta de lectura reciente produce NO_DETERMINABLE calculado, sin agregar enum de espacio. D-10 quedó RESUELTA PARA FASE 7 en 6.5: REST/HMAC, payload, ventana y deduplicación exactos; valor sin interpretación física universal. [Contrato normativo](DECISIONS.md#contrato-iot--cierre-d-10-de-fase-65).

**Liberación (D-08).** Evaluar reserva y política aplicables, tolerancia, validaciones QR, eventos IoT y condiciones configuradas; registrar evaluación y enlaces existentes. Información ausente, insuficiente o contradictoria no libera automáticamente; solo una política e informe que permitan inequívocamente la decisión pueden habilitarla. Evidencia de uso mantiene; ALERTA no libera; AUTOMATICA requiere todas las condiciones aplicables. No interpretar silencio del sensor como ausencia de personas. Combinaciones exactas, disparador y conservación de versión de política se cierran antes de Fase 8.

**Blockchain (D-12).** Secuencia: operación crítica → registro principal confirmado en MySQL → selección de datos mínimos → representación canónica consistente → hash → evidencia local → envío externo → referencia externa. Fallo externo conserva la operación y evidencia PENDIENTE cuando corresponda; no rollback de la transacción principal. Nunca enviar nombres, correos, contraseñas, información personal completa ni datos operativos completos. La canonicalización deberá ser determinista, con orden de campos y formato de fechas estables, UTF-8, sin campos personales no permitidos y versionada por `version_canonica`. Su especificación exacta, algoritmo y proveedor se cierran antes de Fase 9; aquí no se elige silenciosamente JSON, una norma de canonicalización o un algoritmo hash concreto.

## Límites diferidos y pruebas futuras

D-04/D-07 cerradas en Fase 1.5: Laravel Session web y expiración desde configuración; RBAC y convención de permisos oficiales. El catálogo administrativo está implementado en Fase 2; Fases 3/3.5 precisan autorización por módulo/acción y propiedad para CU05–CU10, manteniendo diferido el alcance fino. Autenticación móvil/API diferida. Reservas: D-05 resuelta para Fase 4 mediante Fase 3.5; cancelación antes del inicio y finalización automática diferida a una fase posterior autorizada. Emisión/correlación QR: antes de Fase 6. Payload, transporte y frescura IoT: cerrados en Fase 6.5; implementación pendiente de Fase 7. Combinación de evidencias, disparador y versión de política: antes de Fase 8. Canonicalización exacta, proveedor y recuperación Blockchain: antes de Fase 9. Protocolos de medición/retención: fases de integración/operación según D-16. Las versiones de herramientas ya se seleccionaron y verificaron en Fase 1 (D-15).

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

## Reglas verificadas de CU05/CU06 — Fase 3

RBAC por módulo/acción según petición §35, que precisa para esta fase la puerta histórica de ámbito fino. La elegibilidad y el permiso deben venir del mismo rol vigente; gestionar espacio no implica cambiar su estado ni bloquearlo. Alta/edición y M:N son transaccionales; responsable del bloqueo procede de la sesión. Código UNIQUE global a recursos, capacidad positiva y periodo válido se verifican también en MySQL.

Disponibilidad base: estado DISPONIBLE, todos los filtros (características AND) y ausencia de bloqueo activo con inicio < fin_consulta y fin > inicio_consulta. Intervalos [inicio, fin), Bolivia. Solapamientos entre bloqueos permitidos; desactivar conserva historia y no afecta otros bloqueos. Vencer no cambia activo automáticamente. Estado BLOQUEADO es independiente. Reservas Fase 4, IoT Fase 7 y CU30 siguen pendientes. [Pruebas y límites](SPACES_IMPLEMENTATION.md).

## Aceptación futura de reservas — Fase 4

Contrato congelado en Fase 3.5, **sin pruebas de reservas ejecutadas todavía**. Estos criterios complementan BR-02/04/05/15 y no alteran la evidencia histórica de Fases 2/3. Para CU07–CU10 aplica RBAC por módulo/acción y propiedad según el [cierre D-05 y precisión de D-04](DECISIONS.md#cu08-y-catálogo-mínimo-de-permisos-previsto); el ámbito fino por recurso continúa diferido.

| ID | Escenario obligatorio | Resultado que deberá verificarse |
| --- | --- | --- |
| T-R01 | Crear dos solicitudes del mismo espacio/periodo disponible, incluso concurrentes | Ambas PENDIENTE; ninguna bloquea exclusividad; responsables/fechas de decisión nulos |
| T-R02 | Aprobar la primera de esas solicitudes | Solo ella pasa a APROBADA; actor/fecha de resolución completos; motivo_rechazo NULL |
| T-R03 | Intentar aprobar la segunda tras confirmar la primera | Conflicto; segunda sigue PENDIENTE, sin decisión parcial ni rechazo automático |
| T-R04 | Dos procesos reales con conexiones/transacciones MySQL independientes intentan aprobar dos pendientes solapadas | En condiciones válidas, exactamente una APROBADA y una PENDIENTE; la segunda espera y observa el commit de la primera |
| T-R05 | Aprobar dos solicitudes contiguas 08:00–09:00 y 09:00–10:00 | Ambas APROBADAS; no falso conflicto en la frontera |
| T-R06 | Intervalos idénticos, contenidos, parciales, vacíos, invertidos y cruce de medianoche | Aplicar [inicio, fin); rechazar duración no positiva; CU06/CU07/CU09 coinciden |
| T-R07 | Bloqueo activo o mantenimiento aparece entre consulta y confirmación/decisión | CU07 no inserta; CU09 no aprueba y conserva PENDIENTE. Bloqueos inactivos/no solapados no excluyen |
| T-R08 | Solicitar aprobar/rechazar/cancelar desde cada estado y repetir una decisión | Solo alta PENDIENTE y cuatro transiciones de DOMAIN_MODEL; ningún selector/estado de cliente elude guardas |
| T-R09 | Cancelar antes, exactamente al inicio y después; incluir espera por lock que atraviesa inicio | Solo antes permite cancelar. Aplicar igual a titular y responsable; conservar resolución anterior y actor/fecha de cancelación |
| T-R10 | Cancelación con/sin motivo; rechazo con/sin motivo | Ambos motivos opcionales, máximo 500; vacío a NULL. Aprobar siempre deja motivo_rechazo NULL |
| T-R11 | Cuenta/sesión no válida, rol retirado, permiso retirado, Administrador sin permiso, combinación de roles incompatible | Denegación en servidor incluso con formulario previamente abierto; ningún bypass |
| T-R12 | Consulta/detalle/filtros/contadores propios con ids/códigos ajenos y filtro de solicitante manipulado | No expone terceros; el filtro administrativo no habilita acceso. Administrador/Encargado requieren permiso administrativo concreto |
| T-R13 | Aprobación contra cancelación; doble cancelación; rechazo contra aprobación | Estado final corresponde al orden serial permitido; no sobrescribir primera decisión ni responsable; cancelación posterior a aprobación vuelve a validar hora |
| T-R14 | Fallo inyectado antes del commit, después de preparar actor/fecha o estado | Rollback de toda la operación; nunca liberar exclusividad con cancelación sin persistir ni mostrar éxito sin commit |
| T-R15 | Colisión controlada de codigo y reintentos agotados | UNIQUE conserva identidad; regeneración acotada solo para ese conflicto; sin duplicados, códigos editables ni filas parciales |
| T-R16 | Reserva APROBADA con fin vencido; estados LIBERADA/FINALIZADA y reservas en otros espacios | No finalización/liberación automática; solo APROBADA solapada del mismo espacio bloquea; periodo futuro no afectado por uno expirado |
| T-R17 | Filtros de fecha/estado/espacio y solicitante autorizado, incluida medianoche | Conjunción dentro del alcance autorizado; día local [00:00, siguiente 00:00); código estable y estado persistido |

T-R04 requiere sincronización observable entre dos procesos, no llamadas secuenciales ni un mock de lockForUpdate. Coordinar el inicio/espera con barreras acotadas, verificar las dos conexiones y revisar filas/resultados después de ambos commits. Probar también la espera de la fila del espacio y la lectura de una APROBADA recién confirmada, sin dar por demostrada esa protección solo porque el lock RBAC global serializa la prueba. Esos ensayos deben usar la base MySQL de testing autorizada y nunca `espacio_u`. No alterar el aislamiento global para hacer pasar una carrera.

T-R07 debe cubrir cambios de CU05 con el mismo orden de locks. Un escritor que confirma después de CU07 puede volver inviable la solicitud pendiente; CU09 revalida y no la aprueba. No se inventa cancelación automática ni prioridad sobre cambios operativos futuros.

El ensayo de disponibilidad con 500 reservas y los límites de respuesta de los CU se ejecutarán cuando exista el esquema de Fase 4. La regresión de seguridad/espacios seguirá formando parte de esa validación. Esta fase documental solo verifica coherencia, trazabilidad, alcance del diff y ausencia de secretos.

## Aceptación ejecutada de reservas — Fase 4

La tabla T-R01–17 anterior era aceptación futura en el cierre 3.5; ahora cuenta con pruebas de esquema, HTTP, servicios y procesos MySQL independientes. Se conserva íntegro el contrato. [Correspondencia de cada T-R y resultados](RESERVATIONS_IMPLEMENTATION.md#evidencia-automática-y-rendimiento). Se comprobó la cancelación posterior a espera por lock y la aprobación contra un commit posterior al snapshot. El ensayo local con 500 reservas es evidencia del servicio/BD; la aceptación integral de rendimiento sigue en Fase 13.

## Aceptación futura de equipamiento y préstamos — Fase 5

**Contrato cerrado documentalmente en Fase 4.5; ninguna prueba de esta sección ha sido ejecutada.** [Decisiones, fuentes y permisos](DECISIONS.md#contrato-de-equipamiento-y-préstamos--fase-45). D-02/D-11 resueltas para Fase 5; D-04/D-05 se aplican sin reabrirlas. Las pruebas T-P son identificadores internos de aceptación, no requisitos nuevos.

| ID | Reglas / CU | Comprobación obligatoria al implementar |
| --- | --- | --- |
| T-P01 | BR-06; CU12; esquema | Padre y subtipo comparten recurso_id PK/FK; exactamente nueve columnas de equipo; tipo obligatorio, base nullable y serie nullable UNIQUE. Dos equipos sin serie válidos; serie/código duplicado rechazado. Fallo al crear subtipo revierte padre. Metadatos MySQL idénticos a SQL. |
| T-P02 | BR-02; CU12–CU19; D-04 | Probar los doce permisos y cuatro roles según matriz; rol activo elegible debe aportar permiso. A/E sin D/S no solicitan; D/S solo ven lo propio. Denegar cuenta/sesión/rol inválidos, permiso retirado, ID ajeno y payload que falsifica solicitante/responsables. No asociación encargado–recurso inventada. |
| T-P03 | BR-06; CU13/14/16 | Servicio único: estado DISPONIBLE, filtros, APROBADO/ENTREGADO solapados e incidencias ABIERTA. Contención, igualdad y solapamiento parcial bloquean; adyacencia no. PENDIENTE/RECHAZADO/DEVUELTO no bloquean. PRESTADO/EN_MANTENIMIENTO/BAJA excluyen aunque la fecha prevista haya pasado. |
| T-P04 | BR-06/15; CU14 | Dos solicitudes pendientes compatibles operativamente para mismo equipo/periodo pueden coexistir. Validar motivo hasta 500, fin>inicio, revalidación tras lock, código PRE-UUIDv4 único/inmutable; forzar colisión y fallo de persistencia sin filas parciales ni datos de resolución. |
| T-P05 | BR-06/15; CU16 | Dos procesos/conexiones MySQL aprueban solicitudes distintas e incompatibles: exactamente una APROBADO, otra PENDIENTE; perdedora sin resolvedor/fecha nuevos. Espera real sobre mismo equipo; lectura actual posterior al lock, incluso si existía snapshot previo. No prueba secuencial que simula concurrencia. |
| T-P06 | BR-06; CU16; RF12 | Solo PENDIENTE se resuelve; aprobación registra actor/fecha y motivo NULL; rechazo exige texto no vacío hasta 500, aun con equipo indisponible. Doble resolución/aprobar contra rechazar conserva una decisión. No afectar regla opcional de rechazo de reservas. |
| T-P07 | BR-07/15; CU17 | Dos responsables entregan el mismo APROBADO simultáneamente: una fila entrega, un ENTREGADO y equipo PRESTADO; ninguna escritura parcial del perdedor. Probar equipo/receptor incorrectos, incidencia y estado cambiado durante espera. |
| T-P08 | BR-07; CU17; D-11 | receptor_id=solicitante_id desde servidor; entregado_por_id/fecha_hora confiables. condicion_entrega texto requerido <=255, observacion opcional <=500; recepcion_confirmada_en NULL. Entrega funciona sin QR ni confirmación digital posterior. |
| T-P09 | BR-08/15; CU18 | Dos procesos devuelven el mismo ENTREGADO: una sola devolución; ganador registra responsables/fechas y resultado, perdedor no modifica el hito. Forzar fallos después del hito o del estado y verificar rollback total. |
| T-P10 | BR-08; CU18 | ADECUADA→CONFIRMADA/DEVUELTO/DISPONIBLE si apto; CON_INCIDENCIA→CONFIRMADA/DEVUELTO/EN_MANTENIMIENTO con reporte/vínculo obligatorio mismo recurso y permiso de incidencias; conservar BAJA y otras restricciones. Fallo de incidencia revierte devolución. |
| T-P11 | BR-08; CU18 | NO_DETERMINABLE→PENDIENTE_REVISION, préstamo ENTREGADO, equipo EN_MANTENIMIENTO preventivo (BAJA se conserva), confirmador/fecha NULL. Prohibido solicitar, aprobar o habilitar por edición genérica/cierre de otra incidencia. |
| T-P12 | BR-08/09; CU18/19 | Confirmación posterior autorizada actualiza misma devolución: solo condición final ADECUADA/CON_INCIDENCIA, conserva recepción, completa confirmador/fecha y pasa préstamo a DEVUELTO. Doble confirmación concurrente solo produce una transición; no segunda fila, cierre de incidente implícito ni retroceso desde CONFIRMADA. |
| T-P13 | BR-09; CU19 | Incidencia/mantenimiento sobre ambos subtipos, con tipo/estado exactos, descripción, responsables y fechas. fin_previsto informado exige >inicio en aplicación, sin cambiar CHECK SQL >=; cierre no anterior a inicio. Vínculo devolución nullable y del mismo equipo, sin sobrescribir otra devolución. No borrado histórico. |
| T-P14 | BR-09/15; CU19 | Apertura y cierre/restauración concurrentes en MySQL: si se confirma una ABIERTA, resultado nunca DISPONIBLE. Cerrar una entre varias no restaura; pendiente de revisión mantiene mantenimiento; ENTREGADO mantiene PRESTADO; BAJA se conserva. Probar ambos órdenes de adquisición y doble cierre. |
| T-P15 | BR-04/09; CU05/06/07/09/19 | Incidencia ABIERTA excluye espacios en el servicio existente y revalidaciones. Cierre no borra bloqueos/APROBADA ni causa administrativa; habilitación explícita requiere espacios.cambiar_estado y ausencia de bloqueo vigente. Bloqueos futuros siguen excluyendo periodos propios; preservar fronteras y permisos de Fase 4. |
| T-P16 | BR-06/09; CU12/19 | BAJA impide solicitud/aprobación/entrega y solo se retira explícitamente con cambiar_estado. Retorno permitido conserva BAJA; cerrar incidente no la levanta. Sin préstamo no se permite PRESTADO manual. EN_MANTENIMIENTO administrativo no se elimina sin confirmar aptitud y permiso. |
| T-P17 | BR-24; CU15 | Vencido calculado solo ENTREGADO y ahora>fin_previsto; igualdad no vence; DEVUELTO no vence; revisión pendiente se muestra además. No enum ni escritura por reloj. Consulta propia/administrativa, filtros, vacío y 1.000 registros <=3 s, máximo 100 por página. CU13 debe medir también catálogo equivalente. |
| T-P18 | BR-15–18/21; límites | CU17/CU18 sin QR; ninguna llamada externa dentro de locks ni evidencia/aviso/bitácora ficticios. Comprobar transiciones imposibles rechazadas, fechas/actor confiables, rollback e integridad FK/UNIQUE; verificar que no se crean tablas o enums ajenos al contrato. |

La estrategia requiere transacciones con locks reales sobre el recurso antes de consultar ausencia de conflictos; UNIQUE por préstamo complementa la aplicación, no sustituye las guardas. [Orden único y atomicidad](DECISIONS.md#transacciones-y-orden-único-de-locks). Fin previsto de incidencia no cierra ABIERTA; no hay disponibilidad automática por cron. Todos los registros abiertos de CU19 se tratan como incompatibles hasta cierre explícito, incluyendo mantenimiento previsto a futuro: convención conservadora visible, sin bandera adicional.

Motivo de rechazo de préstamo obligatorio según RF12 y precisión autorizada en Fase 4.5; el de reservas sigue opcional. El alcance funcional de Fase 5 comprende el núcleo sin QR, sin Blockchain, RF21 ni CU30: sus pruebas de integración pertenecen a sus fases, no se marcan aprobadas aquí.

## Evidencia de aceptación de Fase 5

T-P01–18 dejaron de ser pruebas futuras al ejecutar el núcleo autorizado: [matriz de criterios y pruebas](EQUIPMENT_LOANS_IMPLEMENTATION.md#aceptación-t-p0118). Suite completa 262 aprobadas / 3318 aserciones, 14 escenarios concurrentes nuevos sobre MySQL real y 28 pasos manuales. Se preservan los criterios normativos anteriores y sus obligaciones de integraciones diferidas; esta ejecución no acredita QR, Blockchain, RF21 ni CU30. Rendimiento con 1.000 registros: catálogo 0,0075 s (4 consultas), préstamos 0,0059 s; medición local de servicios, no certificación integral de carga.

## Aceptación futura de QR — Fase 6

Fase 5.5 especificó estos criterios sin ejecutarlos entonces. Fase 6 los vincula ahora con pruebas y evidencia en [QR_IMPLEMENTATION](QR_IMPLEMENTATION.md#pruebas-y-aceptación-t-q0124); T-Q23 conserva la aceptación física por confirmación del usuario (sin corroboración independiente nueva). Aplicar [D-09 y Readiness](DECISIONS.md#readiness-for-phase-6) conservando T-R/T-P y la regresión previa. El QR nunca sustituye identidad, permisos, estado ni comprobación física. Las referencias T-Q son criterios internos, no requisitos nuevos ni estados de BD.

| Criterio | Fuente / regla | Prueba obligatoria y resultado esperado |
| --- | --- | --- |
| T-Q01 | SQL / D-17 | Verificar ambas tablas completas en MySQL: 8+13 columnas, tipos/defaults/nullable/índices, 10 FK y acciones referenciales; CHECK de objetivo, vigencia, contexto y hitos; UNIQUE de hash y enlaces. Sin timestamps/columnas/estados extra. |
| T-Q02 | Petición 5.5 §§3–5,38–39 / BR-10 | Generación CSPRNG de 32 bytes, base64url 43 ASCII canónicos; igualdad de hash en emisión y lectura; mayúsculas distintas, espacios, padding, URI, Unicode, escapes y bits no canónicos no se normalizan a un token válido. No tokens reales ni reutilizables en fixtures. |
| T-Q03 | BR-22 / privacidad | Solo hashes persistidos. Inspeccionar DB, sesión, old input, historial Inertia, almacenamiento/cache del navegador, logs/trazas, errores, Git y capturas: sin token/imagen activos. Respuesta de emisión no-store; cierre descarta buffers; no recuperación del original desde hash. |
| T-Q04 | chk_qr_objetivo / IH-03 | Cero/dos objetivos fallan; recurso espacio/equipo, reserva y préstamo correctos funcionan. Tipo derivado, sin tipo_qr; QR de recurso principal; emisión de operaciones solo explícita, sin crear uno por cada solicitud. |
| T-Q05 | chk_qr_vigencia / D-09 | activo falso, reloj previo a generada_en y ahora==vence_en son inválidos; inicio incluido; NULL exige activo. Igualdad generación/vencimiento SQL tiene ventana vacía; no fecha implícita por fin de préstamo, no cron ni consumo automático. |
| T-Q06 | BR-10/15 / gestión | Emitir/rotar desactiva todos los activos del objetivo exacto y crea uno nuevo; no afecta objetivos distintos del mismo recurso. Desactivación repetida es idempotente; anteriores no se reactivan, editan ni borran; misma etiqueta admite varias validaciones. |
| T-Q07 | D-04 / matriz QR | Tres códigos exactos con roles elegibles; gestión solo A/E y acceso al objetivo; D/S no emiten aunque sean titulares. Seeder idempotente conserva revocaciones. Retiro de permiso/cuenta/sesión durante espera deniega, sin bypass administrativo. |
| T-Q08 | RF8/CU11 / BR-02 | D/S: permiso QR + consulta propia + titular; A/E: permiso QR + consulta administrativa. IDs y query params ajenos no amplían alcance; datos protegidos no se filtran; todos los actores requieren sesión ACTIVO/CSRF. |
| T-Q09 | CU11/CU20 / CHECK | Matriz de correspondencia: QR del recurso esperado o de la operación exacta; rechazar operación distinta aunque tenga el mismo recurso. USO_ESPACIO nunca préstamo; ENTREGA/DEVOLUCION nunca reserva; recurso esperado/actor/tipo se derivan del servidor. |
| T-Q10 | CU11 / D-02/D-05 | Solo APROBADA en [inicio,fin), sin tolerancia; probar inicio/fin exactos y todos los otros estados. Validaciones repetidas conservan evidencia individual sin cambiar reserva ni espacio ni crear EN_USO/UTILIZADA/CONFIRMADA. |
| T-Q11 | CU11 / BR-04/09 | Revalidación central con intervalo de reserva y excepción de su propio ID evita autoconflicto. Bloqueos, otras APROBADAS, estado incompatible e incidencias siguen impidiendo uso. No cancelar/eliminar compromisos ni ejecutar liberación. |
| T-Q12 | CU20→CU17 / RF13 | APROBADO sin entrega, equipo disponible correcto, receptor=solicitante, confirmaciones físicas y permisos QR/entrega; incidencia o cambio de estado bloquea. VALIDA sola no entrega; servicio principal crea hito/ENTREGADO/PRESTADO; recepcion_confirmada_en sigue NULL. |
| T-Q13 | CU20→CU18 / RF14 | ENTREGADO con entrega válida y sin retorno; QR/contexto exactos. PRESTADO, EN_MANTENIMIENTO o BAJA no impiden por sí solos recibir físicamente; preservación de BAJA/restricciones y permisos de incidencia/recuperación de Fase 5. |
| T-Q14 | CU18 / D-02 | ADECUADA, CON_INCIDENCIA y NO_DETERMINABLE conservan semántica. Retorno pendiente puede enlazarse a VALIDA y sigue ENTREGADO; revisión posterior conserva la misma devolución, receptor original y enlace, sin exigir ni enlazar segunda validación. |
| T-Q15 | codigo_qr_id/hash_leido | Token desconocido canónico y string no canónico admitido → INVALIDA, codigo_qr_id NULL, hash, actor/contexto/hora/motivo. Código conocido inactivo/vencido/ajeno conserva su FK, sin revelar el objetivo ajeno ni generar código ficticio. |
| T-Q16 | Admisión / BR-01/02 | Sin sesión, ACTIVO, CSRF, permiso o contexto existente/accesible: 401/403/404/419 según la guarda, sin fila forzada ni acceso ajeno. Payload ausente/no string/>1024 bytes: 422/413 previo; no truncamiento. Cámara sin lectura no genera registro ficticio. |
| T-Q17 | BR-11/15 | INVALIDA admitida confirma únicamente su propia fila, motivo seguro <=500 y enlaces NULL; no cambia ninguna entidad operativa. VALIDA tiene motivo NULL. Intentos inválidos anteriores no se borran ni se convierten en válidos. Error al guardar resultado no permite continuar. |
| T-Q18 | Correlación / UNIQUE | Confirmación con token/datos en un request; rechazo de validacion_id antigua, de otro actor/sesión/tipo/recurso/préstamo. La misma fila nueva enlaza hito creado en esa transacción; no segunda validación ni intervalo arbitrario de minutos. |
| T-Q19 | Atomicidad / R-PR-01 | Fallos tras insertar VALIDA, crear hito o actualizar enlace revierten validación/hito/estados juntos; inválidos ya confirmados sobreviven. Fallo/collision de emisión revierte desactivación anterior; solo colisión de hash reintenta, máximo tres. Respuesta perdida/reintento no duplica operación. |
| T-Q20 | Concurrencia real MySQL | Dos rotaciones y escaneo contra desactivación/rotación en ambos órdenes, con barreras observables y conexiones distintas. Máximo un activo por objetivo; si revocación confirma primero, lectura actual rechaza; si validación confirma primero, conserva evidencia histórica. Probar snapshot previo y vencimiento entre captura/espera/decisión. |
| T-Q21 | Concurrencia CU17/CU18 | Dos confirmaciones con QR para mismo hito → uno creado y máximo una VALIDA enlazada; perdedor no modifica el hito/vínculo. Cambio de préstamo/permiso/incidencia antes de lock/confirmación impide continuación; dentro de transacción común no hay intercalación. Probar entrega, devolución y retorno pendiente. |
| T-Q22 | Extensión condicional / límites | Invocar QR sin token o con INVALIDA nunca cae automáticamente al flujo sin QR. Núcleo autorizado sin QR sigue separado; no requiere_qr nuevo ni uso de politicas_liberacion. No Blockchain, IoT, liberación, RF21 o CU30 anticipados. |
| T-Q23 | Cámara / IH-02/03 / IU-08 | Navegador compatible/contexto seguro, permiso concedido/denegado, cámara ausente e ilegibilidad; captura local, sin frames subidos/guardados, sin campo manual. Antes de confirmar solo indica leído. Navegación/cancelación detienen tracks y limpian token; labels/foco/teclado/responsive y consola sin secretos. |
| T-Q24 | R-TR-04 / R-CC-04 / RD-08 | Medir <=3 s desde lectura correcta hasta resultado mostrado (incluye red/backend/UI; captura y confirmaciones humanas ocurren antes de la lectura final) y >=30 validaciones/min con resultados coherentes. Verificar ninguna transacción abierta durante captura; librerías mínimas/mantenidas/locales, build/auditorías y regresión completa. |

Las carreras requieren dos procesos reales, barreras y comprobación de estado confirmado, no mocks ni solo secuencias. Usar únicamente MySQL testing aislado con guarda. La coordinación RBAC global es una limitación existente: incluir espera del recurso/QR y lecturas actuales para no atribuir toda la garantía a esa serialización. Estos ensayos pertenecen a Fase 6; su documentación no constituye ejecución ni cumplimiento de la aceptación integral de Fase 13.

## Evidencia QR de Fase 6

T-Q01–24 mantienen su contenido normativo; la matriz ejecutada y sus límites están en QR_IMPLEMENTATION.md. Suite 325/4984, 63 casos nuevos, 16 escenarios concurrentes y revisión interactiva diferenciada de cámara física. No se convierten lecturas sintéticas ni la declaración del usuario sin fila concordante en prueba física corroborada. Se conserva la regresión T-R/T-P.

## Aceptación IoT prevista para Fase 7

[D-10 RESUELTA PARA FASE 7](DECISIONS.md#readiness-for-phase-7); BR-12/13/22 concretadas. **T-I01–T-I32 son obligaciones futuras, ninguna ejecutada en Fase 6.5.** Usar datos/secretos sintéticos de testing, MySQL aislado y procesos concurrentes reales donde se indica; no pruebas sobre desarrollo ni dispositivos reales sin su fase. La suite previa pertenece a Fase 6.

| ID | Escenario | Resultado exigido |
| --- | --- | --- |
| T-I01 | Alta/consulta CU21; duplicado de identificador, espacio inexistente, edición de identificador | UNIQUE y FK oficiales; inmutabilidad desde alta, no nuevas columnas; sin eliminación física. |
| T-I02 | Provisionar con/sin permiso, revocación concurrente y volver a provisionar | Solo sesión/CSRF/permiso de rol A/E; clave solicitada en memoria/no-store, no fuga; misma derivación no es rotación. |
| T-I03 | Maestro ausente/malformado, claves de dos sensores/entornos, cambio explícito | Rechazo cerrado; derivación exacta de bytes por sensor/entorno, clave individual no autentica otro; cambio exige reprovisionar. |
| T-I04 | Firma válida y alteración de método, path, sensor, timestamp, body/espacios/orden | Solo cadena exacta verifica; hash RAW antes de parseo; firma hex minúscula y comparación constant-time. |
| T-I05 | Firma inválida/desconocido; sensor válido deshabilitado | 401 uniforme sin enumeración; 403 solo autenticado/deshabilitado; cero eventos/cambios de comunicación. |
| T-I06 | Timestamp request en −301, −300, +300, +301 s; inválido, duplicado u overflow | Límites ±300 inclusivos, demás rechazo; cabeceras únicas y enteros seguros; ventana revalidada tras espera. |
| T-I07 | HMAC válido con JSON roto, faltantes, extra, PII, claves duplicadas, array | Parseo después de firma; 400/422 seguros, sin eco/log de cuerpo ni actualización. |
| T-I08 | Header sensor distinto del body, space_code de otro espacio o ID arbitrario | 422 por identidad contradictoria, 409 por contexto; FK siempre asociación BD validada. |
| T-I09 | Tipos PRESENCIA/OCUPACION y tipo desconocido | Dos enums exactos; sensores_iot.tipo no redefine tipo de evento; desconocido rechazado. |
| T-I10 | value omitido/null/cero, negativos, límites, booleano/string/exponente/exceso de escala | NULL-safe, rango/escala exactos sin redondear; sin interpretación universal ni identidad. |
| T-I11 | Z y offsets equivalentes; sin zona, fracción, calendario falso, -00:00, rango SQL | Conservar instante en America/La_Paz; perfil estricto; mismo instante → misma fecha_evento. |
| T-I12 | Evento futuro +300/+301 s y recepción cliente falsificada | Límite técnico inclusive, exceso rechazado; recepción solo DEFAULT servidor; futuro tolerado no actual hasta su instante. |
| T-I13 | Evento antiguo con request recién firmado | Histórico aceptado si contexto válido; recepción distinta, comunicación renovada solo si nuevo; lectura actual NO_DETERMINABLE. |
| T-I14 | Mismo evento retransmitido, nueva firma/serialización, 1/1.0/1.00 y null/omisión | 200 duplicate, mismo ID, una fila y sin renovar comunicación; NULL distinto de cero. |
| T-I15 | Dos eventos distintos del mismo sensor, dos procesos | Serialización breve por sensor; ambas filas completas, última comunicación servidor coherente. |
| T-I16 | Duplicado simultáneo, dos procesos sobre tabla inicialmente vacía y con historia | Una fila/una actualización; otro duplicate tras espera; snapshot no anterior al mutex. |
| T-I17 | Sensores diferentes, dos procesos, mismo y diferente espacio | Sin lock global/RBAC; lecturas compartidas compatibles; ambos avanzan y persisten. |
| T-I18 | Deshabilitar mientras llega evento, ambos órdenes de commit | Gestión primero rechaza; ingestión primero conserva evento histórico válido; sin actualizaciones por rechazado. |
| T-I19 | Reasignar A→B mientras llega evento, ambos órdenes | Estado BD al obtener lock decide; A histórico intacto, nuevos B, antiguo payload A rechazado. |
| T-I20 | Cambiar asociación entre firma/persistencia; cambiar código CU05 concurrentemente | Revalidar después de espera; no FK arbitraria ni inversión de locks; el código actual decide. |
| T-I21 | Fallo entre INSERT y ultima_comunicacion; deadlock/reintento; respuesta perdida | Rollback completo o commit único; hasta tres intentos; reenvío idempotente si contexto válido. |
| T-I22 | Frescura: edades de evento/recepción 0/300/301 s y futuras | Ambas dentro de 0..300; antigua recibida hoy no actual, futuro tampoco hasta su instante. |
| T-I23 | Llegadas desordenadas, tipos múltiples, valores contradictorios, múltiples sensores | Orden por medición; tipos separados; contradicción/semántica insuficiente → NO_DETERMINABLE, sin sumar ocupantes. |
| T-I24 | Sin eventos, sensor caído/deshabilitado/reasignado o ultima_comunicacion NULL | NO_DETERMINABLE; historia conservada, comunicación calculada sin enums ONLINE/OFFLINE/ERROR. |
| T-I25 | Permisos A/E retirados, D/S, acceso directo a sensores/eventos/provisión | Gates/Policies centrales, elegibilidad y permiso del mismo rol; sin bypass o historial público. |
| T-I26 | Consulta con filtros sensor/espacio/tipo/rango y paginación | Fechas normalizadas, intervalo [desde,hasta), 15/página, orden estable; eventos no editables. |
| T-I27 | 120/121 requests por sensor/min, dos workers, identificador falsificado, limitador caído | Cuota atómica y separada; 429/Retry-After, firma inválida no agota víctima, 503 si limitador no seguro. |
| T-I28 | Cuerpo 4096/4097 bytes, streaming sin longitud, content type/encoding inesperados | Máximo inclusive, 413/415, memoria acotada; no cuerpo grande ni descompresión previa. |
| T-I29 | Logs, APM, proxy, errores, UI/historial, Git/env.example | Ningún maestro, clave, firma completa, cabecera secreta o PII; solo campos técnicos permitidos. |
| T-I30 | CU06, reserva/QR y núcleo ante lectura sin presencia, silencio o caída IoT | Solo información mínima; no cambio de estados ni disponibilidad base; sin ejecución CU23. |
| T-I31 | Cambiar ventana UI y política de Fase 8; dispositivo atrasado/NTP | Configuraciones distintas, reloj servidor; no sustituir max_antiguedad_lectura_min ni inventar política. |
| T-I32 | >=120 eventos válidos/min agregados, historial y consulta bajo carga | Sin pérdida aceptada, reflejo <=5 s desde recepción servidor; documentar entorno, cuotas/carga y límites del ensayo. |

La retención piloto conserva eventos, sin purga automática. Probar que todos los escritores comparten el lock del sensor y que la consulta de deduplicación establece su snapshot después de esperarlo. No basta simular carreras secuencialmente. No instalar MQTT para acreditar REST; cualquier adaptación futura deberá pasar las mismas reglas de aplicación.
