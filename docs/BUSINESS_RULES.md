# Reglas de negocio y controles verificables

**Estado vigente — Fase 10 (2026-10-26).** RF21/CU26 implementados y verificados: 17 reglas, avisos propios, primera lectura y recuperación de 24 h. [Informe y evidencia](NOTIFICATIONS_IMPLEMENTATION.md). CU30 general y Fase 11 no iniciados. Sepolia real sigue pendiente de testETH. Los apartados de cierres anteriores conservan su contexto histórico.

**Estado histórico al cierre de Fase 9 implementada (2026-10-25/26).** RF19/CU24 y RF20/CU25, cuatro actividades críticas, dos tablas y tres permisos implementados conforme a D-12. [Implementación, pruebas offline, excepción temporal dev de Zod y Sepolia pendiente de testETH](BLOCKCHAIN_IMPLEMENTATION.md). CU30 general y Fase 10 permanecen pendientes. Las secciones de cierres anteriores conservan su contexto histórico.

**Estado al cierre funcional de Fase 8 (2026-10-24).** BR-14 y T-L01–T-L36 cuentan con implementación/verificación en Fase 8. Se conserva literalmente el contrato D-08 y su matriz. El encabezado «pruebas futuras» identifica el cierre histórico 7.5; el informe nuevo documenta la ejecución posterior. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

**Estado histórico: Fase 7 implementada conforme a D-10.** CU21/CU22 y evidencia informativa en CU06; dos tablas IoT y tres permisos nuevos. [Implementación, seguridad, pruebas y límites](IOT_IMPLEMENTATION.md). CU23 y Fase 8 no iniciados.

La Fase 6 conserva su aceptación y su límite de corroboración física descritos en [QR_IMPLEMENTATION.md](QR_IMPLEMENTATION.md). Los cierres documentales de Fase 6.5 y anteriores se conservan como evidencia histórica; sus criterios futuros se verifican ahora en Fase 7.

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

**Liberación (D-08).** [Contrato cerrado en Fase 7.5](DECISIONS.md#contrato-de-liberación-de-reservas--cierre-d-08-de-fase-75). Solo APROBADA desde inicio+tolerancia y antes de fin, con política asignada activa/válida. Uso positivo veta; insuficiencia requerida o contradicción mantiene; inactividad suficientemente demostrada produce ALERTAR en ALERTA o LIBERAR en AUTOMATICA. La matriz exacta, ventanas, versiones, evidencia M:N, scheduler, idempotencia y locks están congelados. CU23 todavía no está implementado; silencio no equivale a ausencia.

**Blockchain (D-12).** [Contrato cerrado en Fase 8.5](DECISIONS.md#contrato-blockchain--cierre-d-12-de-fase-85): cuatro acciones exactas; actividad crítica en la transacción principal, evidencia local y red tras commit; SHA-256/JCS espacio-u-jcs-v1, subjects históricos, Sepolia/11155111, idempotencia/recuperación y CU25 con cuatro resultados. LIBERADA excluida. No se implementa aún CU24/CU25; T-B01–T-B40 especifican su aceptación.

## Límites diferidos y pruebas futuras

D-04/D-07 cerradas en Fase 1.5: Laravel Session web y expiración desde configuración; RBAC y convención de permisos oficiales. El catálogo administrativo está implementado en Fase 2; Fases 3/3.5 precisan autorización por módulo/acción y propiedad para CU05–CU10, manteniendo diferido el alcance fino. Autenticación móvil/API diferida. Reservas: D-05 resuelta para Fase 4 mediante Fase 3.5; cancelación antes del inicio y finalización automática diferida a una fase posterior autorizada. Emisión/correlación QR: antes de Fase 6. Payload, transporte y frescura IoT: cerrados en Fase 6.5; implementación pendiente de Fase 7. Combinación de evidencias, disparador y versión de política: antes de Fase 8. Canonicalización exacta, proveedor y recuperación Blockchain: cerrados documentalmente en Fase 8.5; implementación pendiente de Fase 9. Protocolos de medición/retención: fases de integración/operación según D-16. Las versiones de herramientas ya se seleccionaron y verificaron en Fase 1 (D-15).

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


## Actualización de Fase 7

Los escenarios T-I01–T-I32 previstos en 6.5 se ejecutan en las suites IoT de Fase 7. BR-12/13/22 se aplican: historial con espacio validado, doble frescura y no determinable, sin mutación de disponibilidad/reservas. El mapa y la evidencia detallada están en el informe. BR-14/CU23 no se ejecuta. [Implementación y verificación](IOT_IMPLEMENTATION.md).

## Liberación de reservas — contrato documental de Fase 7.5

BR-14 se concreta en la [matriz completa D-08](DECISIONS.md#matriz-completa-de-decisión-de-d-08). Las reglas siguientes son futuras de CU23; no cambian CU22 genérico ni la aplicación de Fase 7.

- Política solo QR: falta de VALIDA tras tolerancia satisface su criterio administrativo, salvo veto/contradicción. INVALIDA no prueba no uso. Política IoT exige ceros explícitos suficientes de todos los sensores habilitados actuales; una fuente opcional positiva también veta.
- PRESENCIA acredita uso por el tipo; OCUPACION>0 acredita uso, =0 permite evidencia negativa y NULL/negativo es insuficiente. Considerar toda la ventana; no borrar un positivo reciente mediante un cero posterior. QR válido contra IoT negativo completo o contradicción interna → NO_DETERMINABLE. QR válido con IoT insuficiente/positivo no contradictorio → MANTENER_POR_USO.
- Inactividad suficiente + ALERTA → ALERTAR, conserva APROBADA. Inactividad suficiente + AUTOMATICA → evaluación/pivotes/APROBADA→LIBERADA atómicos. Sin modificar estado operativo ni ignorar bloqueos/incidencias.
- Versiones inmutables; desactivar conserva historia; asignar/retirar política o reactivar sobre compromisos futuros/activos respeta las guardas D-08. Terminales de evaluación por reserva/política no se repiten; NO_DETERMINABLE exige evidencia nueva relevante.

## Aceptación de liberación prevista para Fase 8

Estos **36 criterios son especificaciones de pruebas futuras**, no tests ejecutados en 7.5. MySQL aislado real, procesos/conexiones independientes para carreras y regresión de las 371 pruebas previas. No sustituir por SQLite ni reconstruir desarrollo.

| ID | Escenario | Aceptación requerida |
| --- | --- | --- |
| T-L01 | SQL y dominio | Tres tablas, 10 columnas, seis FK/acciones, PK compuestas, índices, enum y motivo nullable físico exactos; sin cambiar políticas ni migraciones anteriores |
| T-L02 | Configuración | Nombre vacío/largo, version no positiva/no entera, límites INT, modo/booleanos inválidos, fuentes ambas falsas, antigüedad ausente/negativa o valor con usa_iot=false rechazados; cero válido |
| T-L03 | Versionado | Nombre/version UNIQUE con collation vigente; colisión concurrente controlada; reglas/identidad inmutables; nueva versión no cambia asignaciones ni historia; sin borrado |
| T-L04 | Asignación | NULL→ID, ID→otro y ID→NULL prohibidos con APROBADA fin>T; mismo ID sin cambio; vencidas/no aprobadas no impiden; carrera con aprobación revalida bajo espacio |
| T-L05 | activa y diagnóstico | Desactivar conserva historia; inactiva/inválida no libera y diagnóstico NO_DETERMINABLE controlado; reactivar rechaza compromisos vigentes; sin incidencias de mantenimiento inventadas |
| T-L06 | Elegibilidad y tolerancia | Seis estados, T antes/igual al umbral, T antes/igual a fin, tolerancia cero/igual o mayor al periodo y overflow; solo ventana útil APROBADA |
| T-L07 | Sin política y fallos | NULL omite sin error crítico; FK/contexto no resoluble o fallo de consulta aborta seguro; no convierte error en ausencia de QR/IoT |
| T-L08 | Contexto QR | Misma reserva/recurso/USO_ESPACIO, inicio inclusivo y T inclusivo; otros contextos/fechas fuera excluidos; INVALIDA solo contexto; rotar código no invalida historia VALIDA |
| T-L09 | QR y veto | Un QR VALIDA basta, requerido u opcional; ausencia opcional no aporta negativo; IoT positivo veta aunque falte QR requerido |
| T-L10 | Ventana IoT | max(inicio,T−minutos), ambas fronteras, recepción<=T, futuro excluido, antigüedad cero; independiente de configuración CU22 y ventana auxiliar fija de 300 s cuando no se exige IoT |
| T-L11 | Interpretación exclusiva | PRESENCIA con NULL/0/negativo sigue positiva por tipo; OCUPACION positivo/cero/NULL/negativo; CU22 conserva valor genérico y visualización sin reinterpretación |
| T-L12 | Varios sensores | Todos los habilitados asociados, sin selección por tipo; ninguno, uno sin lectura, solo presencia silenciosa, ambiguo y ceros completos; sin unanimidad fabricada |
| T-L13 | Contradicciones | QR válido/negativo completo, positivo/cero entre sensores y tiempos, valores distintos/NULL del mismo instante; NO_DETERMINABLE y evidencia completa |
| T-L14 | Matriz completa | Las 24 filas × 2 modos producen exactamente sus 48 resultados; fuentes ambas falsas rechazadas; ninguna combinación positiva/contradictoria libera |
| T-L15 | Acciones de resultado | Cuatro enums, motivo obligatorio seguro <=500; solo LIBERAR cambia APROBADA; ALERTAR no notifica ni libera; sin campos de cancelación/resolución falsos |
| T-L16 | Disponibilidad | LIBERADA deja de bloquear intervalo; mantenimiento, bloqueo, incidencia y estado incompatible siguen excluyendo; servicio central y demás reservas conservados |
| T-L17 | Historia exacta | Pivotes Q/E exactos, incluidos ambiguos/INVALIDA relevantes; sin historia ajena/futura/fuera de ventana; inmutabilidad; ningún JSON/copia de fuentes ni recálculo retrospectivo |
| T-L18 | Terminales | MANTENER_POR_USO/ALERTAR/LIBERAR de misma pareja omiten repeticiones aunque llegue evidencia; no reapertura por reloj o edición de modo |
| T-L19 | Reevaluar NO_DETERMINABLE | Solo Q/E nuevo relevante no enlazado antes y posterior según contrato; cambio de conectividad/inventario o envejecimiento sin novedad no crea filas |
| T-L20 | Empate DATETIME | Evento/QR distinto confirmado dentro del mismo segundo habilita una sola reevaluación por ID no enlazado; no asumir orden de commit por MAX(id) |
| T-L21 | Retransmisiones e historia tardía | Duplicado IoT no es novedad; recepción nueva fuera de W no reevalúa; futuro antes excluido solo participa al volverse temporalmente válido |
| T-L22 | Reloj y contexto | Reloj atrasado respecto de evaluación difiere; nueva versión no evade guarda de asignación; política inválida no produce un diagnóstico idéntico cada minuto |
| T-L23 | Dos primeras evaluaciones | Workers simultáneos misma reserva: una evaluación/una transición como máximo; segundo ve commit e idempotencia; rollback no consume evidencia |
| T-L24 | Dos reevaluaciones | Misma novedad tras NO_DETERMINABLE: una evaluación, pivotes confirmados impiden otra; novedad real posterior sí admite otra no terminal |
| T-L25 | Cancelación y frontera | Cancelación legal previa gana u operación CU23 gana dentro de su ventana; espera que cruza inicio invalida cancelación; D-05 intacta y nunca doble transición |
| T-L26 | QR concurrente | Ambos órdenes: VALIDA previa veta; CU23 que encuentra espacio retenido retrocede; LIBERADA previa impide VALIDA posterior; sin snapshot viejo |
| T-L27 | IoT concurrente | Ingestión, alta, habilitación/deshabilitación, reasignación entrante/saliente en ambos órdenes; conjunto completo, espacio histórico conservado y evento posterior no reescribe historia |
| T-L28 | Política concurrente | Asignación/aprobación, desactivación/evaluación y reactivación/compromiso: reglas revalidadas bajo locks, sin usar versiones inactivas por lectura obsoleta |
| T-L29 | Snapshot y consistencia | Prohibir transacción anidada/snapshot previo; primera lectura después de todos los locks incluye commits previos bajo RR; refrescar T antes de decidir/escribir |
| T-L30 | Atomicidad ante fallo | Fallos tras evaluación, durante cada pivot y antes/después de UPDATE revierten todo; actualización de cero filas nunca deja LIBERAR; fin vencido durante trabajo aborta |
| T-L31 | Locks y recuperación | NOWAIT no omite sensores, sin SKIP LOCKED; hasta tres intentos completos y pausa sin locks; sin coordinador RBAC humano ni llamadas externas; agotamiento difiere sin éxito parcial |
| T-L32 | Scheduler y carga | Candidatos/paginación por clave/lotes sin cargar todos; frecuencia independiente de tolerancia; errores por reserva aislados; dos workers seguros y EXPLAIN con índices oficiales |
| T-L33 | RBAC de políticas | Matriz de cuatro permisos, elegibilidad y permiso del mismo rol, revocación mientras espera, sesión/CSRF y URLs directas; sin bypass de Administrador |
| T-L34 | Evaluaciones propias | Titular solo sus reservas; proyección segura sin PII ajena, secretos QR, identificadores/historia técnica de sensores; administrativo con permiso vigente |
| T-L35 | Límites de fase | Sin notificaciones funcionales, Blockchain, CU30, usuario/rol Sistema, API de liberación manual ni scheduler de FINALIZADA |
| T-L36 | Revisión manual | Crear versión, asignar bajo guardas, desactivar, consultar uso/historia y cuatro resultados; móvil/tablet/escritorio, teclado, etiquetas, foco, errores y consola; sin botón Liberar ahora |

## Aceptación futura Blockchain — Fase 9

Contrato normativo [D-12](DECISIONS.md#contrato-blockchain--cierre-d-12-de-fase-85). Los criterios siguientes se definieron en Fase 8.5 y se implementan en Fase 9: [mapa de pruebas T-B01–T-B39; T-B40 Sepolia pendiente de testETH](BLOCKCHAIN_IMPLEMENTATION.md#trazabilidad-de-aceptación-t-b01t-b40). Cubren RF19/RF20, CU24/CU25, R-PR-03/04 y R-DISP-06. Suite normal offline, datos sintéticos y MySQL separado; pruebas live independientes. Concurrencia verificada con procesos/conexiones distintos y barreras controladas.

| Criterio | Área | Comprobación exigida |
| --- | --- | --- |
| T-B01 | JCS reproducible | Mismos datos y orden de propiedades distinto producen exactamente los mismos bytes JCS y SHA-256; usar vectores independientes de referencia, no un test que repita el algoritmo. |
| T-B02 | Unicode y validación | UTF-8 válido, caracteres no ASCII/escapes y orden UTF-16; rechazar Unicode inválido/claves duplicadas; no normalizar composiciones Unicode diferentes. |
| T-B03 | IDs y fechas | BIGINT mayores a 2^53 conservan strings exactos; UTC segundos Z desde America/La_Paz, cruce de fecha, sin float/fracciones/fecha de navegador. |
| T-B04 | Null y texto secundario | NULL JSON vs vacío con hash, acentos, espacios/case y saltos de línea exactos; claves presentes; obligatorio faltante no se sustituye por NULL válido. |
| T-B05 | Esquemas cerrados | Seis claves comunes y subjects de 7/6/9/11 claves; schema/versión/acciones exactos, cambio de cada campo relevante cambia el hash; sin atributos adicionales. |
| T-B06 | Aprobación y atomicidad | CU09 válido crea una sola actividad EXITO/critica en su transacción; fallo de actividad revierte aprobación; evidencia solo después de commit. |
| T-B07 | Cancelación | CU10 exitoso desde PENDIENTE o APROBADA crea una actividad/hito con actor y motivo hash correctos; ilegal/fallida no crea evidencia. |
| T-B08 | Entrega | CU17 exitoso crea entrega/actividad únicas y equipo PRESTADO; actor/receptor/contexto correctos; rollback local sin evidencia huérfana. |
| T-B09 | Devolución final | NO_DETERMINABLE/PENDIENTE_REVISION no crea actividad crítica; confirmar mismo hito conserva recepción inicial y produce solo una actividad al quedar DEVUELTO. |
| T-B10 | Exclusiones de reservas | Rechazo, solicitud y APROBADA→LIBERADA CU23 no generan actividad crítica ni evidencia; evaluaciones/QR/IoT conservan su historia. |
| T-B11 | Exclusiones restantes | Aprobación/rechazo préstamo, validación QR individual, IoT e incidencia/mantenimiento no envían evidencia; incidencia derivada de devolución no duplica. |
| T-B12 | Minimización canónica | Nombres, correos, CI, password, teléfonos, motivos/observaciones en claro ausentes; solo allowlist local y hashes secundarios. |
| T-B13 | Privacidad externa | Interceptar calldata y requests: un argumento bytes32 más metadatos técnicos; sin JSON, IDs internos, códigos, fechas de negocio, motivos/observaciones ni payload añadido. |
| T-B14 | Algoritmo y versión | SHA-256 hex minúsculo/64 y espacio-u-jcs-v1 persistidos; bytes32 externos equivalentes; no confundir Keccak de protocolo con SHA-256 de evidencia. |
| T-B15 | Contrato autorizado | Solo writer configurado registra; llamada ajena revierte; lookup público devuelve timestamp; no objetos de negocio ni envío de valor. |
| T-B16 | Contrato idempotente | Mismo hash dos veces conserva primer timestamp y un único evento/registro lógico; hashes distintos no se mezclan. |
| T-B17 | Red incorrecta | Config o eth_chainId distinto de 11155111 bloquea firma/envío, incluso cambio a Mainnet solo por variable; negocio confirmado y evidencia pendiente. |
| T-B18 | Configuración/contrato | Integración deshabilitada, RPC/clave ausente, contrato ausente o bytecode/writer ajeno no envían; actividad/evidencia locales sobreviven; diagnóstico sin secretos. |
| T-B19 | Confirmaciones | Tx_hash solo no registra; receipt exitoso, bloque canónico, contrato/hash/evento y N>=1 coherentes sí; fecha externa del bloque, nunca cliente. |
| T-B20 | Fallo externo | RPC timeout, HTTP error, red caída y contrato no consultable dejan negocio válido y pendiente; no locks ni rollback principal. |
| T-B21 | Transacción no final | Revert o transacción pendiente/confirmaciones insuficientes conservan PENDIENTE; no fecha externa fabricada y no reenvío mientras tx conocida pendiente. |
| T-B22 | Referencia | Formato eip155 estricto y longitud 125; normalizar hex; rechazar red/contrato/tx ajenos y URLs; cambio de proveedor mantiene referencia verificable. |
| T-B23 | Actividad sin evidencia | Interrupción tras commit y antes del callback se recupera desde actividad; falla de hash no genera placeholder; un error no impide otros casos. |
| T-B24 | Evidencia pendiente | Ejecuciones repetidas y reintento manual reutilizan actividad/evidencia/hash/version/fecha local; nunca segunda evidencia. |
| T-B25 | Registro ya externo | Contrato contiene hash: no broadcast adicional; logs indexados recuperan tx original y se valida confirmación antes de registrar localmente. |
| T-B26 | Timeout incierto | Simular aceptación de broadcast y pérdida de respuesta: no asumir fracaso; consultar hash/receipt; recuperar sin doble registro lógico. |
| T-B27 | Caídas intermedias | Interrumpir antes de envío, después de envío y antes de actualizar MySQL; ref candidata, ausencia comprobada y tx pendiente se distinguen; no inventar éxito. |
| T-B28 | Dos workers | Procesos reales sobre MySQL aislado compiten por una actividad y wallet; una evidencia local, un registro lógico externo, nonce coordinado y locks liberados tras fallo. |
| T-B29 | Historia estable | Verificar aprobación después de CANCELADA/LIBERADA y entrega después de devolución; no estado actual, no sobrescribir resolución o recepción inicial. |
| T-B30 | Inconsistencia local | Alterar campo histórico reconstruible cambia hash: INCONSISTENTE sin RPC; no sobrescribir hash ni enviar para ocultar diferencia. |
| T-B31 | Pendiente verificable | Hash local igual y estado PENDIENTE produce PENDIENTE, salvo reconciliación confirmada previa; metadatos iniciales no simulan una verificación. |
| T-B32 | Verificación válida | Hash local y referencia/receipt/evento/contrato/red coherentes producen VALIDO; persistir resultado y hora servidor. |
| T-B33 | No verificable | Historia obligatoria ausente, versión no soportada o RPC caído con REGISTRADA producen NO_VERIFICABLE; conservar estado/ref/fecha externa. |
| T-B34 | Inconsistencia externa | Prueba positiva de hash/red/contrato/tx incorrectos o ausencia confirmada produce INCONSISTENTE; reorg/no conclusión no se confunde con timeout ni reenvío automático. |
| T-B35 | RBAC y sesión | Solo Administrador con permisos efectivos; denegar Encargado/Docente/Estudiante, permiso retirado/cuenta inactiva/sesión revocada; verificar/reintentar requieren ver y CSRF. |
| T-B36 | UI y verificación concurrente | Solo metadatos permitidos en UI/props/logs; cuatro resultados; serializar CU25 por evidencia, actualizar última comprobación sin resultados obsoletos por carrera ni efectos de GET. |
| T-B37 | Latencia y locks | Medir generación local <=3 s tras commit con datos sintéticos; bloquear RPC artificialmente y demostrar por otra conexión que reserva/préstamo/equipo quedan libres. |
| T-B38 | Esquema y alcance | Dos tablas exactas/UNIQUE/índices/CHECK y única excepción fk_registro_actor; sin columnas JSON/outbox/CU30 general/backfill; default resultado_verificacion=PENDIENTE. |
| T-B39 | Pruebas offline y secretos | Suite normal fake/mock, MySQL aislado y EVM local controlado sin Internet; fixtures sin claves activas, RPC privado ni mnemonic; errores saneados. |
| T-B40 | Integración Sepolia separada | Solo cuando haya configuración segura y saldo testnet: registro, confirmación, recuperación y CU25 reales; guardar prueba sin claves/URLs privadas; no acreditarla con mocks. |

La garantía es una evidencia local y un registro lógico externo por actividad crítica, no exactamente un broadcast. La actividad fallida revierte negocio; un fallo posterior del hash/red no revierte un commit. Un reintento no cambia el hash original. La exclusión LIBERADA no reduce la evidencia propia de CU23.

## Contrato de notificaciones — Fase 9.5

RF21/CU26 queda cerrado documentalmente en [Notification Contract / Readiness for Phase 10](DECISIONS.md#notification-contract--readiness-for-phase-10), N1–N9. No se ejecuta Fase 10. Las reglas siguientes son obligaciones de implementación futura:

| Regla | Invariante y consecuencia |
| --- | --- |
| BR-N01 | Solo los 17 eventos EXITO de N3 generan los ocho tipos oficiales. Rechazo de negocio confirmado es EXITO; ERROR/RECHAZO técnico y acciones sin regla no notifican. |
| BR-N02 | Actividad durable y negocio se confirman juntos; avisos después del commit. Un fallo posterior de generación no invalida negocio. Cuatro actividades críticas reutilizadas; trece nuevas no críticas; consumidores Blockchain/avisos independientes. |
| BR-N03 | Destinatarios ACTIVO, permisos efectivos al corte N2; grupos administrativos sin actor redundante. Unión sin duplicados; S tiene prioridad de plantilla. Autocancelación: AR y nunca el propio titular. |
| BR-N04 | Idempotencia por actividad+destinatario+tipo bajo lock de actividad y lote atómico. Ninguna UNIQUE nueva ni mutex global; distintas actividades concurrentes. |
| BR-N05 | Recuperación N6: 24 h desde evento, solo posteriores a habilitación RF21, primer lote cierra grupo administrativo; después solo reparación directa reconstruible. Sin filas permite resolver actuales dentro de ventana; ausencia de snapshot impide recuperar integrantes administrativos perdidos de un lote ya confirmado. |
| BR-N06 | Vencimiento calculado ENTREGADO y ahora > fin_previsto, como máximo una actividad por préstamo; sin cambiar estado ni custodia. Revisión pendiente inicial notifica AD y no equivale a devolución confirmada. |
| BR-N07 | CU19 diferencia incidencia/mantenimiento en acción, ambos tipo INCIDENCIA; apertura/cierre a AI y afectado solo por devolución vinculada. No atribuir culpa ni prometer disponibilidad. |
| BR-N08 | CU23 ALERTAR y LIBERAR crean actividad en su evaluación/transición; avisos posteriores. MANTENER_POR_USO/NO_DETERMINABLE no notifican; idempotencia D-08 intacta. |
| BR-N09 | Mensaje histórico plano y determinista; título ≤160; sin motivos libres, PII innecesaria ni secretos. Cambios actuales no reescriben el mensaje; enlaces sujetos a permiso actual. |
| BR-N10 | CU26 solo propietario con notificaciones.ver_propias, incluidos administradores; contador propio. Primera lectura del servidor inmutable, listado/prefetch/GET no mutan. Apertura expresa del detalle solicita POST/PATCH autorizado y con CSRF. |

### Aceptación de notificaciones prevista para Fase 10

**T-N01–T-N40 son criterios futuros, no pruebas ejecutadas en Fase 9.5.** Cada caso debe probar resultado y ausencia de efectos indebidos. Las carreras requieren conexiones/procesos MySQL independientes y barreras observables, no solo invocaciones secuenciales. Datos exclusivamente sintéticos; no usar cuentas, tokens ni mensajes reales.

| ID | Escenario futuro y resultado exigido |
| --- | --- |
| T-N01 | Esquema oficial: exactamente 8 columnas, PK, 2 FK CASCADE/RESTRICT, 8 enums, índices, creada_en default y leida_en NULL; sin UNIQUE nueva ni timestamps del framework. Título de 160 aceptado/161 rechazado. |
| T-N02 | Dataset de las 17 filas N3: acción → tipo exacto, evento/actor/contexto/critica/EXITO correctos; solo cuatro acciones críticas con evidencia. |
| T-N03 | Por cada acción, conjunto exacto de receptores y no receptores: ACTIVO, INACTIVO, rol retirado/inactivo, permiso revocado, permiso parcial, elegibilidad D-04 y alcance. Rol por nombre sin permiso no basta. |
| T-N04 | Solicitud de reserva: solo AR; aprobación/rechazo: solo S; actor resolutor no recibe por ser administrador. Autocancelación: AR sin titular; cancelación ajena: S sin grupo adicional. |
| T-N05 | Solicitud de préstamo: AP; decisión/entrega/devolución final: S; receptor = solicitante; sin administrativos adicionales. |
| T-N06 | Multirol, coincidencia S/grupo y múltiples permisos: una fila por clave lógica, plantilla directa prevalece. Excluir actor solo de grupo; excepción explícita de autocancelación. |
| T-N07 | Fallo/rollback antes del commit de negocio, incluidas escrituras de actividad: ningún evento/aviso parcial. Reintento de transición ya confirmada no genera segunda actividad. |
| T-N08 | Fallo forzado de generación/despacho después de commit en reserva, préstamo, devolución, incidencia y liberación: operación y actividad permanecen; respuesta no afirma rollback de negocio. |
| T-N09 | Fallo del consumidor Blockchain no impide avisos; fallo de avisos no impide evidencia; no exigir testETH/RPC/REGISTRADA. Eventos técnicos Blockchain, QR o IoT aislados no notifican. |
| T-N10 | Dos generadores simultáneos sobre la misma actividad: exactamente una fila por destinatario/tipo; el segundo consulta filas confirmadas tras esperar. |
| T-N11 | Generación normal y notificaciones:reconciliar simultáneos: misma garantía T-N10 y sin modificar texto/creada_en/leida_en existentes. |
| T-N12 | Detener worker después de insertar parte del lote pero antes de commit: rollback total; recuperación crea el conjunto completo. No persistencia parcial por commits por destinatario. |
| T-N13 | Fallo inicial sin filas y recuperación dentro de ventana: destinatarios actuales válidos; una y diez ejecuciones mantienen conjunto bajo elegibilidad estable. |
| T-N14 | Fixture parcial persistido: administrativo ya existente y S faltante. Recuperar S activo dentro de ventana; conservar fila/lectura administrativas; no agregar un administrador recién autorizado. |
| T-N15 | Lote administrativo ya confirmado con supuesto integrante faltante: no inferirlo de permisos actuales ni ampliar el conjunto. Documentar la imposibilidad de reconstrucción histórica sin snapshot; no afirmar reparación administrativa completa. |
| T-N16 | Cero destinatarios: cero filas, sin receptor ficticio; reintento antes de límite usa actuales. Cambio de permisos antes del primer lote puede cambiar receptores; después de primer lote no agrega administrativos. |
| T-N17 | Ventana: evento+24 h incluido, después excluido; evento anterior a habilitación excluido, reloj anterior al evento difiere. Reiniciar no mueve habilitación ni vuelve a abrir historial. |
| T-N18 | Evento sin regla, ERROR, RECHAZO técnico y contexto incoherente: cero avisos; rechazo correcto con EXITO sí cumple N3; diagnóstico no copia datos sensibles. |
| T-N19 | Cuenta deshabilitada después de recibir: fila conservada; no nuevas filas mientras esté inactiva. Reactivación/permisos semanas después no produce backfill; pérdida de CU26 bloquea consulta, no borra historia. |
| T-N20 | Revocación/desactivación concurrente: commit anterior al corte de destinatarios excluye; posterior conserva el corte; revocación entre GET detalle y POST lectura impide marcar. No reutilizar permisos cacheados de petición anterior. |
| T-N21 | Actividades distintas procesan simultáneamente, incluso con receptores compartidos, sin mutex exclusivo global, locks de huecos sobre notificaciones vacías ni espera por locks de negocio. Primer snapshot de selección posterior al lock de actividad. Timeout/deadlock agota reintentos saneados sin revertir negocio. |
| T-N22 | Propietario con permiso lista/consulta; ID ajeno e inexistente dan 404 sin cuerpo revelador; incluso Administrador no accede al ajeno. Sin permiso de módulo, 403; sin sesión/inactivo, acceso denegado. |
| T-N23 | Solo leida_en NULL es no leída; POST/PATCH propio fija hora del servidor. Segunda llamada conserva primera fecha; fecha/destinatario manipulados se ignoran o rechazan. |
| T-N24 | Dos lecturas concurrentes conservan un único primer timestamp; el perdedor no lo reemplaza. CSRF ausente/incorrecto impide mutación. |
| T-N25 | Listado, contador, prefetch y GET detalle no marcan; apertura expresa de detalle renderizado envía la operación autorizada. Fallo de esa operación no muestra lectura confirmada; URL directa usa mismo flujo. |
| T-N26 | Contador cuenta todas las propias no leídas, independiente de filtros/página; no incluye ajenas. Orden creada_en DESC,id DESC, 15 por página; todas/no leídas/leídas/tipo y vacío correctos. |
| T-N27 | Plantillas N7 exactas: título ≤160, fecha del evento distinta de inserción recuperada; aprobación sigue aprobación tras cancelación. Texto libre/HTML del recurso, motivo o solución no se copia ni ejecuta. |
| T-N28 | Privacidad de respuestas, Inertia, mensajes y logs: sin CI/correo/terceros/secreto/QR/HMAC/RPC/private key; DTO no serializa RegistroActividad completo. Plantilla propia no filtra contenido administrativo. |
| T-N29 | Enlaces derivados y reautorizados: permiso perdido conserva aviso propio pero no acceso al objeto; afectado de incidencia sin incidencias.ver solo enlaza préstamo permitido. Acceso directo al destino también se controla. |
| T-N30 | Vencimiento: no ENTREGADO, antes del límite e igualdad no generan; después sí. Dos detectores simultáneos crean una actividad y una notificación por destinatario, sin estado VENCIDO. |
| T-N31 | Repetir barridos vencidos no repite actividad; devolución posterior impide nuevo evento. Carrera detector/devolución conserva resultado según primer commit; aviso histórico puede recuperarse sin afirmar estado actual. |
| T-N32 | CU18 NO_DETERMINABLE/PENDIENTE_REVISION crea una actividad no crítica y avisos AD, sin PRESTAMO_DEVUELTO ni aviso reiterado a S. Confirmación posterior produce una actividad crítica final y aviso normal; mismo hito. |
| T-N33 | Incidencia abierta/cerrada manual: acción y plantilla correctas, AI activos sin actor; sin S si no deriva de devolución. Cierre repetido no repite actividad y no promete recurso disponible. |
| T-N34 | Mantenimiento abierto/cerrado: acciones MANTENIMIENTO_*, tipo INCIDENCIA, mismos controles; no inventar enum, receptor afectado ni vínculo inexistente. |
| T-N35 | Incidencia derivada de devolución: S adicional por vínculo real, mensaje requiere revisión/sin culpa; cierre avisa S. Rollback de devolución revierte también incidencia/actividades. |
| T-N36 | CU23 ALERTAR produce actividad no crítica + S/AL; mensaje no dice liberada. LIBERAR produce actividad solo si transición APROBADA→LIBERADA y evaluación confirman juntas. |
| T-N37 | CU23 MANTENER_POR_USO/NO_DETERMINABLE: sin aviso ni nueva actividad RF21; política/ventanas/resultado/evidencias D-08 no cambian. |
| T-N38 | Dos evaluadores CU23, reintentos y barridos repetidos: evaluación terminal/actividad/aviso únicos; rollback elimina todo lo nuevo; cancelación/QR/IoT concurrentes conservan guardas previas. Referencia CU23 en descripcion apunta a evaluación correcta. |
| T-N39 | EXPLAIN y consultas con volumen sintético: filtro propietario antes de contar/paginar, índices oficiales aprovechables, lotes acotados, sin N+1 ni carga de historial completo; registrar mediciones sin inventar SLA nuevo. |
| T-N40 | Regresión de Fases 2–9 y alcance: estados/locks/RBAC/QR/IoT/D-12 intactos; sin email, servicios externos, realtime complejo, avisos técnicos, CU30 general, marcar todas, volver a no leída o borrar. Navegación responsive y accesible por teclado. |

## Evidencia posterior T-N01–T-N40 — Fase 10

Los criterios anteriores conservan su estado histórico en 9.5. Ahora están implementados y cubiertos por 86 casos nuevos dentro de 656 pruebas / 8721 aserciones. El informe contiene mapa a suites, carreras reales y revisión interactiva. No se amplía recuperación administrativa histórica. [Implementación y evidencia](NOTIFICATIONS_IMPLEMENTATION.md).
