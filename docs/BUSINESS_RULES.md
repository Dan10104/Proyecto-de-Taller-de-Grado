# Reglas de negocio y controles verificables

Estas reglas sintetizan el informe. Los identificadores BR son internos para organizar pruebas; no sustituyen los RF/CU. Los estados exactos están en [DOMAIN_MODEL.md](DOMAIN_MODEL.md). Las decisiones D clasificadas y sus detalles diferidos están en [DECISIONS.md](DECISIONS.md).

| Regla | Tema | Fuente | Restricción | Prueba futura necesaria |
| --- | --- | --- | --- | --- |
| BR-01 | Acceso y revocación | RF1; CU01–02; RNF-10–15 | Cuenta activa, credenciales válidas, hash de contraseña y sesión vigente; invalidar sesión/token al salir. | Acceso válido; contraseña errónea; inactivo; expirado; petición posterior al logout denegada. |
| BR-02 | Autorización por objeto | RF2–3; CU03–04; RNF-11/15 | Permiso en servidor, propiedad de solicitud y ámbito del responsable; ocultar botones no basta. D-03 y RBAC de D-04 resueltos; Fases 3/3.5 precisan CU05–CU10 por módulo/acción y propiedad, conservando diferido el ámbito fino por recurso. | Sin permiso administrativo no se consultan/modifican solicitudes ajenas; comprobar rol elegible y permiso del mismo rol. |
| BR-03 | Historia de roles | CU03–04; asignaciones_roles | Registrar asignador/retirador y fechas, conservar historia; desactivar cuenta sin borrar operaciones. | Retiro invalida acciones futuras; historia conserva responsables y fechas. |
| BR-04 | Disponibilidad del espacio | RF4–6; CU05–07; DB-17 | Combinar estado, horario, reservas y bloqueos/mantenimiento; reconsultar al confirmar; ocupación IoT es evidencia complementaria. | Dos aprobaciones solapadas no confirman ambas; dos PENDIENTE pueden coexistir. Mantenimiento impide solicitar/aprobar. |
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

La selección de estados que comprometen el periodo es una convención explícita de D-05, coherente con solicitud→resolución; no se presenta como frase literal del informe. Es obligatoria la revalidación transaccional y el control concurrente al adjudicar, entregar o liberar. Para reservas, el algoritmo queda fijado documentalmente en Fase 3.5: lock de la fila del espacio y revalidación actual dentro de la transacción, con orden compatible con la coordinación RBAC existente. Se implementará y probará en Fase 4; préstamos conservan su fase.

Cancelar una reserva solo desde PENDIENTE o APROBADA, con iniciador de CU10 y permiso propio más propiedad, o permiso administrativo de Administrador/Encargado. Fase 3.5 fija la guarda estricta `ahora < inicio` para ambos caminos; igualdad y posterioridad se deniegan. Motivo de cancelación opcional y nullable; motivo de rechazo opcional según la precisión de CU09. FINALIZADA automática queda fuera de Fase 4; no inferir un cambio persistido de la hora actual. [Contrato D-05](DECISIONS.md#contrato-de-reservas--cierre-d-05-de-fase-35).

## Tiempo, identificadores y estados (D-01, D-02, D-16)

Identificadores técnicos ASCII, sin tildes, snake_case cuando corresponda; `permisos.codigo` es el nombre resuelto. Textos humanos en español correcto. Los 19 ENUM y las condiciones derivadas están reconciliados en DOMAIN_MODEL; no añadir valores por palabras narrativas.

Convención temporal del alcance institucional único: `America/La_Paz` para captura, presentación y persistencia operativa en los DATETIME existentes. Un instante externo con zona explícita se convierte a esta referencia antes de persistir/comparar; no mezclar horas UTC con horas locales sin conversión. Configurar aplicación/conexión coherentemente en Fase 1 y probar conversiones antes de IoT. No añadir columnas ni fijar aquí el formato canónico Blockchain: su representación exacta y la normalización de fechas se cerrarán antes de Fase 9 (D-12).

## Contratos congelados de integraciones

**QR (D-09).** El código identifica recurso u operación, no autentica. El servidor comprueba usuario autenticado, permisos, código, recurso esperado, operación/contexto, estado y fecha/horario aplicable. Respetar objetivo único del código y exclusividad reserva/préstamo por tipo de validación; enlaces a entrega/devolución según CHECK/UNIQUE. Código inválido no confirma uso/entrega/devolución. Emisión, vigencia exacta y correlación de reintentos se especificarán antes de Fase 6; no inventar un nuevo CRUD ni suponer un token de un solo uso.

**IoT (D-10).** Detecta presencia/ocupación, nunca identidad. Sensor registrado, habilitado y asociado a espacio; preservar espacio histórico y fechas de evento/recepción. Falta de lectura reciente produce NO_DETERMINABLE calculado, sin agregar enum de espacio. El formato, unidades, transporte, autenticación y ventana concreta se cierran antes de Fase 7.

**Liberación (D-08).** Evaluar reserva y política aplicables, tolerancia, validaciones QR, eventos IoT y condiciones configuradas; registrar evaluación y enlaces existentes. Información ausente, insuficiente o contradictoria no libera automáticamente; solo una política e informe que permitan inequívocamente la decisión pueden habilitarla. Evidencia de uso mantiene; ALERTA no libera; AUTOMATICA requiere todas las condiciones aplicables. No interpretar silencio del sensor como ausencia de personas. Combinaciones exactas, disparador y conservación de versión de política se cierran antes de Fase 8.

**Blockchain (D-12).** Secuencia: operación crítica → registro principal confirmado en MySQL → selección de datos mínimos → representación canónica consistente → hash → evidencia local → envío externo → referencia externa. Fallo externo conserva la operación y evidencia PENDIENTE cuando corresponda; no rollback de la transacción principal. Nunca enviar nombres, correos, contraseñas, información personal completa ni datos operativos completos. La canonicalización deberá ser determinista, con orden de campos y formato de fechas estables, UTF-8, sin campos personales no permitidos y versionada por `version_canonica`. Su especificación exacta, algoritmo y proveedor se cierran antes de Fase 9; aquí no se elige silenciosamente JSON, una norma de canonicalización o un algoritmo hash concreto.

## Límites diferidos y pruebas futuras

D-04/D-07 cerradas en Fase 1.5: Laravel Session web y expiración desde configuración; RBAC y convención de permisos oficiales. El catálogo administrativo está implementado en Fase 2; Fases 3/3.5 precisan autorización por módulo/acción y propiedad para CU05–CU10, manteniendo diferido el alcance fino. Autenticación móvil/API diferida. Reservas: D-05 resuelta para Fase 4 mediante Fase 3.5; cancelación antes del inicio y finalización automática diferida a una fase posterior autorizada. Emisión/correlación QR: antes de Fase 6. Payload, transporte y frescura IoT: antes de Fase 7. Combinación de evidencias, disparador y versión de política: antes de Fase 8. Canonicalización exacta, proveedor y recuperación Blockchain: antes de Fase 9. Protocolos de medición/retención: fases de integración/operación según D-16. Las versiones de herramientas ya se seleccionaron y verificaron en Fase 1 (D-15).

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
