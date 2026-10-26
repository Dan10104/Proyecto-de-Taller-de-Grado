# Registro de decisiones y auditoría documental

**Estado vigente — Fase 10 (2026-10-26).** RF21/CU26 implementados y verificados: 17 reglas, avisos propios, primera lectura y recuperación de 24 h. [Informe y evidencia](NOTIFICATIONS_IMPLEMENTATION.md). CU30 general y Fase 11 no iniciados. Sepolia real sigue pendiente de testETH. Los apartados de cierres anteriores conservan su contexto histórico.

**Estado histórico al cierre de Fase 9 implementada (2026-10-25/26).** D-12 aplicada a RF19/CU24 y RF20/CU25. [Implementación y verificación offline, excepciones autorizadas y Sepolia pendiente de testETH](BLOCKCHAIN_IMPLEMENTATION.md). CU30 general y Fase 10 no iniciados. Los cierres documentales anteriores conservan su contexto histórico.

**Estado al cierre funcional de Fase 8 (2026-10-24).** D-08 continúa RESUELTA PARA FASE 8 y ahora aplicada; no se altera su contrato ni Readiness histórico. Las diferencias entre estado documental 7.5 y software actual se registran en RELEASE_POLICY_IMPLEMENTATION. D-12 se cerró posteriormente en Fase 8.5; ver el estado vigente superior. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

**Estado histórico: Fase 7.5 documental cerrada. D-08 RESUELTA PARA FASE 8; [Readiness for Phase 8](#readiness-for-phase-8), 0 bloqueantes documentales.** La Fase 7 permanece implementada; CU23 y Fase 8 no se han implementado. Las fotografías de cierres anteriores conservan su fecha y alcance.

Fecha: 2026-10-23. Fase 0.5: contratos de implementación congelados según la instrucción del usuario. Se conservan los 17 expedientes D-01–D-17 y su conflicto original; Git conserva la redacción inicial de Fase 0. No se cambian el Word/PDF, el alcance funcional ni el stack. Fase 1 técnica completada por instrucción posterior; D-15 y la configuración de D-16 tienen evidencia en [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md). Fase 1.5 cerró D-04/D-07; el núcleo web de Fase 2 y los espacios de Fase 3 están implementados. Fase 3.5 cerró D-05 para reservas; Fase 4 aplica ese contrato con implementación y pruebas. Fase 4.5 cierra documentalmente CU12–CU19: D-02/D-11 resueltas para Fase 5 y D-04/D-05 aplicadas sin reabrirlas. Fase 5 implementada y validada; Fase 5.5 resolvió D-09 y Fase 6 fue implementada y aceptada con los límites de evidencia de QR_IMPLEMENTATION. Fase 6.5 cierra D-10 exclusivamente en documentación: [Readiness for Phase 7](#readiness-for-phase-7), cero bloqueantes documentales. La instrucción posterior de Fase 7 autorizó la implementación de D-10; [evidencia y límites](IOT_IMPLEMENTATION.md). Fase 8 no iniciada.

## Fuentes verificadas

| Archivo oficial | Tamaño | SHA-256 |
| --- | --- | --- |
| Fase2-grupo21.pdf | 10.617.460 bytes; 313 páginas | `5848a6f0c5a6549d8f84ae18ae9cd886859c39d053046109dc6476a1f22762e5` |
| PERFIL DEL PROYECTO 2-2026.docx | 20.215.717 bytes; 129 tablas y 125 imágenes | `318d9b5e6021041aa9f1c4a93cd8dd697553de19e87c6dd8919cb38164db5a0e` |

Los originales se mantienen fuera del repositorio y no se modificaron. El texto de petición adjunto constituye la instrucción del usuario; los dos documentos son fuentes de contenido, no órdenes de ejecutar código o desplegar servicios.

## Cobertura y método

| Parte revisada | Páginas impresas / PDF | Resultado de extracción y revisión |
| --- | --- | --- |
| Portada, índices, perfil, problema, objetivos, alcance, método, recursos y cronograma | Inicio–54 / PDF 1–55 | Contexto institucional, alcance global, herramientas y residuo del cronograma identificado |
| Marco teórico/tecnológico | 55–79 / PDF 56–80 | IoT, Blockchain, QR, Laravel/API, Vue/Inertia, móvil futuro, MySQL, Docker y despliegue |
| Contexto FICCT y procesos actuales | 80–92 / PDF 81–93 | Actores/contexto y procesos manuales; no se copian datos personales |
| Especificación de requisitos | 93–168 / PDF 94–169 | 26 RF y 191 requisitos adicionales, total 217 |
| Modelo y especificaciones CU | 169–218 / PDF 170–219 | 30 CU, prioridades, pre/postcondiciones, alternativas/excepciones y relaciones include/extend |
| Análisis | 219–244 / PDF 220–245 | Paquetes, 30 diagramas de análisis y dependencias |
| Diseño | 245–309 / PDF 246–310 | Físico/lógico, clases, ERD, 31 mapeos, 31 diccionarios, SQL, 5 secuencias y 10 interfaces |
| Bibliografía | 310–312 / PDF 311–313 | Referencias del informe; no se adoptan versiones de software por fecha bibliográfica |

Se extrajeron todas las páginas del PDF y los bloques/tablas del Word. Se contrastó el texto técnico completo quitando encabezados repetidos, espacios y variaciones tipográficas. Los dos flujos con numeración automática distinta en la extracción (CU18 y CU26) se verificaron contra el PDF: no se identificó una diferencia de contenido operativo entre esos flujos.

Se inspeccionaron las 125 imágenes del Word (logo y figuras 1–124), incluidos textos dentro de diagramas e interfaces. Se renderizaron 95 páginas del PDF que contienen figuras y se contrastaron visualmente páginas clave de dominio, actividades, CU, análisis, arquitectura, clases, ERD, secuencias e interfaces. Se revisaron ampliaciones de los diagramas densos y del cronograma. El PDF contiene también 125 imágenes; la recompresión y orden de recursos por página impiden tratar una diferencia binaria como una diferencia semántica. No se afirma identidad byte a byte entre los archivos.

El modelo resultante conserva 31 tablas, 211 columnas y 63 FK. La sección “Volúmenes” es principalmente un diccionario de campos; la carga cuantitativa se obtiene de R-CC, no de tamaños estimados inventados. Las referencias a páginas de estas notas emplean paginación impresa; el PDF agrega una portada (índice PDF = página impresa +1 en el cuerpo).

## Decisiones establecidas por la petición y el informe

- Solo web por ahora; API preparada y requisitos móviles visibles como diferidos. No implementar ninguna funcionalidad durante Fases 0 y 0.5.
- Stack PHP/Laravel, Vue 3/Inertia/Vite, MySQL; integraciones externas y despliegue futuro definidos en la constitución.
- Workspace único y repositorio oficial confirmado por el usuario: `C:\Users\BlackMaster69\Desktop\ESPACIO-U`, `https://github.com/Dan10104/Proyecto-de-Taller-de-Grado.git`.
- Modelo de clases y estados se preservan; la inclusión de RecursoInstitucional, AsignacionRol y SesionAcceso procede del diseño, no de una ampliación de alcance.
- No se publican documentos originales, capturas personales ni secretos. `.env.example` es únicamente una plantilla segura.

## Jerarquía documental congelada

La instrucción de Fase 0.5 autoriza estas decisiones de implementación; los documentos oficiales siguen siendo la fuente de contenido. La jerarquía se aplica según el tipo de pregunta, no mediante una prioridad universal que invalide otro contrato.

| Tipo de problema | Prioridad, de mayor a menor |
| --- | --- |
| Persistencia | Script SQL final cap. VII → mapeo/volúmenes → diagrama lógico BD → diagrama de clases → narrativa |
| Comportamiento | Detalle CU01–CU30 → RF → reglas de negocio/requisitos DB → secuencias → análisis → ilustraciones anteriores |
| Estructura del dominio | Diagrama de clases final → modelo de dominio final → diseño lógico BD → SQL; cardinalidad interpretada junto con FK y nullable |
| Arquitectura | Diseño final cap. VII → RD → IS/IC → capítulo tecnológico → cronograma histórico, sin autoridad para redefinir ni ampliar |

SQL es contrato principal de tablas, columnas, tipos, PK/FK, nulabilidad, enums, índices y constraints. D-01 es una corrección lexical autorizada y explícita; no habilita otras alteraciones silenciosas. El detalle de CU17 prevalece sobre la flecha defectuosa. El cronograma no define implementación.

## Clasificación de las 17 decisiones originales (cierre de Fase 0.5)

Esta clasificación conserva la fotografía histórica de Fase 0.5, con **un único estado por expediente en ese cierre**. Los estados vigentes se actualizan en sus fichas y en Readiness for Phase 2/4/5/6/7/8/9; no se reescribe el recuento histórico. La clasificación cuenta expedientes de Fase 0, no cada parámetro interno. Una convención puede dejar parámetros posteriores expresamente enumerados; eso no los declara resueltos ni bloquea la fundación.

| Estado | Cantidad |
| --- | --- |
| RESUELTA | 5 |
| CONVENCIÓN DE IMPLEMENTACIÓN | 3 |
| NO AFECTA IMPLEMENTACIÓN | 2 |
| DIFERIDA A UNA FASE POSTERIOR | 7 |
| REALMENTE BLOQUEANTE | 0 |

| ID | Título original | Estado | Fase afectada |
| --- | --- | --- | --- |
| D-01 | Discrepancia confirmada: nombre de permiso | RESUELTA | 2; convención desde 1. |
| D-02 | Discrepancia confirmada: estados narrados | RESUELTA | 2–12 según entidad. |
| D-03 | Diferencias de alcance entre actores RF y CU | RESUELTA | 2–12; matriz desde 1. |
| D-04 | Vacío de diseño: recursos asignados al encargado | DIFERIDA A UNA FASE POSTERIOR | 2 antes de habilitar permisos de alcance; 3–5 antes de acciones por recurso. |
| D-05 | Vacíos y precisión temporal | CONVENCIÓN DE IMPLEMENTACIÓN en 0.5; RESUELTA PARA FASE 4 en 3.5 | Cierre documental de reservas; préstamos conservan su alcance de Fase 5. |
| D-06 | Diferencia entre modelo conceptual y diseño de datos | RESUELTA | 2–10 según tabla. |
| D-07 | Decisión técnica pendiente: autenticación y sesión | DIFERIDA A UNA FASE POSTERIOR | 2 antes de CU01–CU02; estudio de compatibilidad permitido en 1 cuando se autorice. |
| D-08 | Vacío de ejecución de liberación | DIFERIDA A UNA FASE POSTERIOR | 8; contrato con monitoreo en 7. |
| D-09 | Vacío de contrato QR y correlación | DIFERIDA A UNA FASE POSTERIOR | 6; prever contratos de entrega/devolución en 5. |
| D-10 | Contrato IoT | RESUELTA PARA FASE 7 | Cierre documental 6.5; implementación 7 pendiente; CU23 en 8. |
| D-11 | Defecto visual confirmado: entrega | RESUELTA | 5 y rama QR de 6. |
| D-12 | Vacío de contrato y secuencia Blockchain | DIFERIDA A UNA FASE POSTERIOR | 9, antes de generación/verificación funcional. |
| D-13 | Contenido residual del cronograma | NO AFECTA IMPLEMENTACIÓN | Todas; control de alcance desde 1. |
| D-14 | Bocetos/entidades de análisis sin respaldo persistente | NO AFECTA IMPLEMENTACIÓN | 2 y 12. |
| D-15 | Selección de versiones y entorno pendiente | DIFERIDA A UNA FASE POSTERIOR | 1 antes de instalar/lockfiles; Docker antes de ensayos de 14. |
| D-16 | Parámetros de tiempo/medición no fijados | CONVENCIÓN DE IMPLEMENTACIÓN | 1 para zona; 7/13/14 para parámetros de operación/medición. |
| D-17 | Invariantes no garantizadas solo por SQL | CONVENCIÓN DE IMPLEMENTACIÓN | 2–10; concurrencia en 3–5, validación integral en 13. |

## Registro de cierre por decisión

### D-01 — Discrepancia confirmada: nombre de permiso

| Campo | Registro |
| --- | --- |
| ID | D-01 |
| Título | Discrepancia confirmada: nombre de permiso |
| Fuente A | Script SQL cap. VII, CREATE TABLE permisos; p. 270 / PDF 271. |
| Fuente B | Mapeo/volumen tablas 70/101; ERD figura 109; instrucción Fase 0.5 §4. |
| Conflicto original de Fase 0 | La declaración usa `código`, mientras UNIQUE refiere `codigo`; el diccionario y ERD usan codigo. El SQL no puede trasladarse sin decidir el identificador exacto. |
| Tratamiento registrado en Fase 0 (histórico) | Propuesta: normalizar a `codigo` conforme ERD/diccionario y registrar corrección autorizada. No ejecutar la definición defectuosa. |
| Impacto | Identificador no ASCII y UNIQUE apuntando a otro nombre. |
| Decisión vigente de Fase 0.5 | El identificador implementable es `permisos.codigo`. Normalizar únicamente la grafía técnica `código` → `codigo`; mantener tipo, obligatoriedad, PK y UNIQUE. Los demás identificadores técnicos serán ASCII sin tildes y snake_case cuando corresponda; clases conservan PascalCase y convenciones del framework. UI con “Código”. |
| Justificación | Corrección explícitamente autorizada por el usuario; repara la contradicción lexical sin modificar significado ni estructura. El SQL sigue siendo contrato principal salvo esta normalización trazada. |
| Estado | RESUELTA |
| Fase afectada | 2; convención desde 1. |
| Detalle diferido / límite de la resolución | Ninguno sobre el nombre; no se ejecutó SQL ni se modificaron las fuentes. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-02 — Discrepancia confirmada: estados narrados

| Campo | Registro |
| --- | --- |
| ID | D-02 |
| Título | Discrepancia confirmada: estados narrados |
| Fuente A | Narrativa, IU-05, RF y CU01–CU30; en especial CU17/CU18. |
| Fuente B | 19 enums del SQL final y figura 108; instrucción Fase 0.5 §5. |
| Conflicto original de Fase 0 | La prosa menciona préstamo activo, finalizado, cancelado o vencido; su enum solo admite PENDIENTE, APROBADO, RECHAZADO, ENTREGADO y DEVUELTO. Ocupado/reservado tampoco son estados persistidos del espacio. |
| Tratamiento registrado en Fase 0 (histórico) | Conservar enums exactos. Distinguir etiquetas/condiciones derivadas; consultar significado de frases contradictorias antes de implementar transición. Devolución incierta conserva PENDIENTE_REVISION. |
| Impacto | Riesgo de persistir estados inexistentes o confundir estado con condición, resultado, tipo o vigencia. |
| Decisión vigente de Fase 0.5 | Congelar los 19 enums exactos y la tabla de equivalencias de DOMAIN_MODEL.md. Préstamo “entregado/activo” se persiste ENTREGADO; retorno confirmado “devuelto/finalizado” se persiste DEVUELTO. “Cancelado” no tiene equivalente de préstamo y no autoriza una función de cancelación. “Vencido” y ocupación son derivados; no crear enums. |
| Justificación | El contrato físico define los valores almacenados. La narrativa se interpreta según objeto y contexto, sin traducir una palabra a un estado distinto por comodidad. |
| Estado | RESUELTA PARA FASE 5 — reconfirmada y concretada en Fase 4.5 sin modificar los 19 enums. |
| Fase afectada | 2–12 según entidad. |
| Detalle diferido / límite de la resolución | Para CU12–CU19, transiciones, vencimiento calculado y devolución/revisión quedan cerrados en el contrato de Fase 4.5. No se añade cancelación de préstamos ni finalización por reloj. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-03 — Diferencias de alcance entre actores RF y CU

| Campo | Registro |
| --- | --- |
| ID | D-03 |
| Título | Diferencias de alcance entre actores RF y CU |
| Fuente A | Campo Usuarios de RF1–RF26. |
| Fuente B | Actor Iniciador, Actores, flujo y precondiciones de CU01–CU30; instrucción Fase 0.5 §6. |
| Conflicto original de Fase 0 | Los RF admiten ciertos usuarios adicionales por permisos; CU07/CU14 enumeran Docente/Estudiante, CU25/CU30 Administrador y CU28 Administrador/Encargado. |
| Tratamiento registrado en Fase 0 (histórico) | No ampliar ni restringir silenciosamente: fijar la matriz rol–permiso–operación a partir del informe. Conservar ambos alcances en la trazabilidad. |
| Impacto | Una lista amplia de participantes podría transformarse indebidamente en permisos de ejecución. |
| Decisión vigente de Fase 0.5 | Aplicar iniciador del CU → flujo → permisos → RBAC → guardas del proceso. Usuarios del RF no concede autorización. CU07/CU14: Docente o Estudiante; CU09/CU16–CU21 según cada CU: responsable Administrador/Encargado; CU25/CU30: Administrador; CU28: Administrador/Encargado. CU22/CU23/CU24 son disparos de dispositivo/proceso; sus observadores no obtienen permiso de ejecutarlos. Matriz completa en USE_CASE_MATRIX.md. |
| Justificación | La jerarquía funcional y el acceso más restrictivo que cumple el CU resuelven la diferencia. Un rol adicional no da permiso implícito; un usuario con varios roles debe actuar con uno admitido y sus permisos/ámbito válidos. |
| Estado | RESUELTA |
| Fase afectada | 2–12; matriz desde 1. |
| Detalle diferido / límite de la resolución | El mecanismo para acreditar ámbito de recursos se difiere en D-04; ante falta de ámbito demostrable, denegar la acción protegida. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-04 — Vacío de diseño: recursos asignados al encargado

| Campo | Registro |
| --- | --- |
| ID | D-04 |
| Título | Vacío de diseño: recursos asignados al encargado |
| Fuente A | Descripción del Encargado y alcance de acceso en RF/CU. |
| Fuente B | Figura 108 y SQL de reservas, prestamos, equipamientos_tecnologicos y asignaciones_roles; Fase 0.5 §10. |
| Conflicto original de Fase 0 | Se exige operar sobre recursos asignados, pero no se identifica una relación persistida encargado–recurso; asignaciones_roles solo asocia usuario y rol. |
| Tratamiento registrado en Fase 0 (histórico) | Precisar origen/representación del ámbito y autorización. No conceder todos los recursos ni añadir una tabla por suposición. |
| Impacto | Falta mecanismo explícito de alcance del responsable; no falta la asignación del recurso a una reserva/préstamo. |
| Decisión de Fase 0.5 (histórica) | Congelar Reserva→Espacio obligatorio; Préstamo→Equipamiento obligatorio; Equipamiento→espacio_base opcional. No agregar entidad de asignación. Implementar después autorización con permisos y ámbito acreditado; nunca inferir que el encargado puede operar todos los recursos. Si una estructura adicional resulta técnicamente imprescindible, documentarla antes de implementarla. |
| Justificación | El modelo ya define los vínculos operativos. La elección del mecanismo de autorización es posterior y no impide crear la base técnica. |
| Estado vigente | RESUELTA PARA FASE 2 |
| Parte diferida | DIFERIDA EN PARTE: alcance fino por recurso, antes de habilitar sus operaciones funcionales. |
| Fase afectada | 2: RBAC por capacidad/módulo; ámbito fino antes de cada flujo por recurso en sus fases funcionales. |
| Detalle diferido / límite de la resolución | En Fase 2 se deriva el catálogo desde RF/CU y se implementa RBAC sobre el esquema oficial. El vínculo fino Encargado–recurso se resolverá al implementar esas entidades; no se crea una tabla adicional ni se concede alcance global por ausencia del vínculo. |
| Bloquea iniciar Fase 1 | No |
| Actualización y autoridad | 2026-10-23; petición Fase 1.5 §§2–8, 15 y 18. Conserva el conflicto y cierre históricos. |
| Bloquea iniciar Fase 2 | No, para el núcleo de seguridad definido en esta petición. |

#### Contrato D-04 para Fase 2

La autorización de una acción protegida exige, en este orden lógico: identidad autenticada y cuenta ACTIVO; rol vigente; permiso explícito asociado; actor iniciador admitido; flujo del CU; restricciones de la operación; propiedad y ámbito del objeto cuando exista. Son condiciones acumulativas: aparecer en Actores/Usuarios de un RF/CU no otorga facultades. Denegar por defecto si falta alguna condición aplicable. CU01 valida identidad y CU02 permite abandonar la sesión: no inventar permisos de módulo para conceder privilegios al iniciar sesión ni para impedir cerrarla.

| Rol oficial | Capacidades admisibles, siempre con permiso y CU habilitante | Límite congelado |
| --- | --- | --- |
| Administrador | Usuarios, roles, permisos, configuración general, espacios, equipamiento, gestión/supervisión de reservas y préstamos, reportes, políticas y bitácora documentados. | Sin bypass universal ni `Gate::before` que autorice todo por nombre de rol. Los permisos amplios se asignan mediante RBAC; siguen vigentes actores, propiedad, estados y demás guardas del CU. |
| Encargado de Laboratorio | Operación autorizada de espacios, equipamiento, solicitudes, préstamos, entregas, devoluciones, incidencias y monitoreo IoT; otras consultas solo según su CU. | En Fase 2 se modelan capacidades; no se habilitan todavía estos módulos. La responsabilidad fina por recurso se acredita antes de sus operaciones posteriores, sin inventar una tabla de asignación. |
| Docente | Consultas autorizadas, solicitudes habilitadas, operaciones propias, validaciones permitidas, notificaciones propias y chatbot. | Sin gestión de usuarios/roles, aprobación inferida de una lista de actores ni información privada ajena. Toda excepción de acción exige CU/documentación explícita; la matriz vigente no le concede aprobar CU09/CU16. |
| Estudiante | Consultas y solicitudes habilitadas, seguimiento propio, QR autorizado, notificaciones propias y chatbot. | Sin administración de usuarios/roles, aprobación, modificación de recursos ni acceso privado de terceros. |

No crear roles nuevos. Los actores externos IoT/Blockchain/chatbot no son roles humanos. Esta tabla no concede automáticamente las capacidades citadas ni adelanta funciones. Se mantiene D-03 y la matriz completa CU01–CU30: por ejemplo, CU07/CU14 requieren Docente/Estudiante habilitado; ser Administrador por sí solo no transforma al usuario en ese iniciador.

**Permisos.** Adoptar `recurso.accion`, ASCII, minúsculas y snake_case dentro de cada segmento; patrón `^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$`, longitud máxima 100 conforme `permisos.codigo`. El catálogo concreto se deriva en Fase 2 antes de seeders o Policies: cada entrada debe trazar RF/CU, acción explícita, iniciador, roles elegibles, restricciones/propiedad/ámbito y prueba negativa. Descomponer CRUD solo donde el flujo lo exige; deshabilitar una cuenta no autoriza borrado físico. No fijar ahora códigos definitivos ni permisos comodín por conveniencia. El catálogo de seguridad se implementa primero; una capacidad futura documentada no habilita una ruta ni crea su módulo.

**Asignaciones históricas.** Usuario ↔ Rol permanece M:N mediante `asignaciones_roles`; Rol ↔ Permiso mediante `roles_permisos`. Asignación activa significa exactamente `fecha_retiro IS NULL`, sin enum nuevo. Los campos físicos de responsabilidad son `asignado_por_id` y `retirado_por_id` (las menciones conceptuales sin sufijo no los renombran). Asignar registra usuario, rol, asignador y fecha del servidor; retirar completa retirador/fecha coherentes, conserva la fila y no borra historia. Reasignar genera otra asignación. No añadir `usuarios.rol_id` ni sustituir pivotes mediante paquetes RBAC.

Un usuario puede tener varios roles activos. Evaluar identificadores y permisos como conjuntos; filas históricas o duplicados activos no multiplican privilegios. Para autorizar debe existir al menos un rol activo que sea elegible para la acción y aporte su permiso; no combinar el permiso de un rol no elegible con el nombre de otro para eludir D-03. El retiro lógico de un rol cierra todas las asignaciones activas de ese par usuario/rol, preservando cada fila. Cambios de roles/permisos deben surtir efecto desde la siguiente solicitud: resolverlos en servidor y no confiar en una copia congelada al login o enviada por Vue.

**Aplicación futura.** Middleware para identidad/vigencia y protección de rutas; Gates para capacidades sin objeto; Policies para acciones sobre modelos/objetos. La resolución RBAC y los criterios actor/permiso serán centrales y testeables, sin condiciones de rol dispersas. UI recibe solo capacidades de presentación; ocultar botones no sustituye la autorización HTTP. El ámbito fino no bloquea Fase 2 porque allí solo se implementa seguridad; tampoco habilita acceso irrestricto en fases posteriores. Antes de exponer un recurso, su Policy y consultas deberán acreditar su alcance o denegar.

### D-05 — Vacíos y precisión temporal

| Campo | Registro |
| --- | --- |
| ID | D-05 |
| Título | Vacíos y precisión temporal |
| Fuente A | CU06–CU10/CU13–CU18; RF5–7/10–14; DB-16/17/18. |
| Fuente B | CHECK de periodos, enums y FK del SQL; Fase 0.5 §9. |
| Conflicto original de Fase 0 | No se fija por completo qué estados bloquean intervalos, tratamiento de PENDIENTE, frontera entre periodos contiguos, plazos de cancelación, finalización automática ni motivo obligatorio vs cuando corresponda. SQL exige fin>inicio, más estricto que no anterior de DB-16. |
| Tratamiento registrado en Fase 0 (histórico) | Definir tabla de transiciones/guardas y colisión antes del algoritmo. Conservar la restricción temporal estricta documentada. Probar concurrencia y revalidación sin asumir una convención de intervalos como oficial. |
| Impacto | Fronteras temporales y sentido de activo no estaban fijados de forma uniforme. |
| Decisión vigente de Fase 0.5 | Intervalos `[inicio, fin)`, fin>inicio y solapamiento si inicio_A<fin_B e inicio_B<fin_A, sobre el mismo recurso. Reserva APROBADA compromete su periodo; PENDIENTE es solicitud aún sin exclusividad y exige revalidación al aprobar; estados terminales no bloquean el periodo. Préstamos APROBADO/ENTREGADO comprometen el periodo previsto; PENDIENTE no asigna exclusividad. Equipo PRESTADO o no disponible bloquea aprobación/entrega aunque haya terminado el periodo previsto. Cancelación de reserva solo desde PENDIENTE/APROBADA, con iniciador de CU10, permiso/propiedad y dentro de la política aplicable; no se fija un plazo arbitrario. |
| Justificación | Convención técnica explícita de intervalos y de solicitudes frente a compromisos, compatible con resolución posterior del CU. No cambia enums, tipos ni añade funciones. Pruebas concurrentes deben excluir la propia solicitud al comprobar conflictos. |
| Estado | RESUELTA PARA FASE 4 — cierre de Fase 3.5, aplicado y verificado en Fase 4 sin reinterpretación. |
| Fase afectada | 3–5; servicios de disponibilidad y transición. |
| Detalle diferido / límite de la resolución | Cancelación, motivos, estados y concurrencia de reservas cerrados en el contrato de Fase 3.5 al final de este documento. FINALIZADA no tiene disparador automático en Fase 4; se difiere a una fase posterior expresamente autorizada. Fase 4.5 documenta su aplicación a préstamos/CU16 antes de Fase 5, sin reabrir D-05. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-06 — Diferencia entre modelo conceptual y diseño de datos

| Campo | Registro |
| --- | --- |
| ID | D-06 |
| Título | Diferencia entre modelo conceptual y diseño de datos |
| Fuente A | Modelo conceptual figura 19; DB-06; asociaciones de figura 108. |
| Fuente B | Figura 109; mapeo/volumen; FK, NULL/NOT NULL y UNIQUE del SQL final; Fase 0.5 §7. |
| Conflicto original de Fase 0 | Figura 19 muestra 0..* evidencias por actividad y 0..1 actividad por notificación. SQL impone UNIQUE actividad para evidencia y actividad NOT NULL para notificación. DB-06 habla de sensores de espacios del piloto; el modelo permite espacios sin sensor. |
| Tratamiento registrado en Fase 0 (histórico) | Registrar la diferencia de nivel/detalle; confirmar cardinalidad final antes de migrar esas relaciones. No ampliar evidencia a varias por evento ni crear avisos sin actividad por inferencia. Fuera del piloto no exigir sensores a todos los espacios. |
| Impacto | Diferencias de multiplicidad y opcionalidad podían inducir cambios de estructura. |
| Decisión vigente de Fase 0.5 | Aplicar cardinalidades de persistencia documentadas en DATA_MODEL.md: cada evidencia tiene una actividad obligatoria y cada actividad 0..1 evidencia por UNIQUE; cada notificación tiene actividad y destinatario obligatorios. Un espacio admite 0..* sensores; cada sensor exige espacio actual. Conservar asociaciones M:N, herencia y las 63 FK sin añadir vínculos directos no documentados. |
| Justificación | Las clases expresan conceptos; nulabilidad, FK y unicidad concretan obligatoriedad y límites reales. La tabla comparativa registra las diferencias y la decisión sin editar los diagramas oficiales. |
| Estado | RESUELTA |
| Fase afectada | 2–10 según tabla. |
| Detalle diferido / límite de la resolución | No hay cardinalidad pendiente; contratos de uso de QR/liberación se difieren en D-08/D-09. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-07 — Decisión técnica pendiente: autenticación y sesión

| Campo | Registro |
| --- | --- |
| ID | D-07 |
| Título | Decisión técnica pendiente: autenticación y sesión |
| Fuente A | CU01–CU02; RNF-10/12/16; IC-04/05. |
| Fuente B | sesiones_acceso y contrato SQL; Fase 0.5 §19/20. |
| Conflicto original de Fase 0 | El informe exige sesión/token con hash, expiración y revocación, pero no selecciona mecanismo Laravel ni duración, almacenamiento de sesión o política de reintentos. CU02 con fallo de red no garantiza revocación remota inmediata. |
| Tratamiento registrado en Fase 0 (histórico) | Elegir integración web/API compatible con sesiones_acceso, sin añadir tablas del starter automáticamente. Definir cierre y recuperación ante red caída, duración y controles de acceso. |
| Impacto | Framework/mecanismo de sesión, duración y comportamiento de red no están seleccionados. |
| Decisión de Fase 0.5 (histórica) | Congelar sesión/token con hash, expiración/revocación y cuenta activa, y autenticación/autorización en servidor. Logout confirmado invalida la sesión remota; si falla la comunicación se eliminan credenciales locales sin afirmar revocación remota confirmada. No instalar starter de autenticación ni tablas alternativas en Fase 0.5. |
| Justificación | Es una selección técnica de la fase de acceso. La fundación puede inicializarse sin login implementado, respetando el contrato de sesiones existente. |
| Estado vigente | RESUELTA PARA FASE 2 |
| Parte diferida | Autenticación de API móvil y almacenamiento distribuido de producción; no condicionan la web local. |
| Fase afectada | 2: acceso web; cliente/API móvil cuando se autorice; entorno distribuido antes de Fase 14. |
| Detalle diferido / límite de la resolución | Web resuelta por el contrato inferior. La futura autenticación móvil/API se elegirá sin romper usuarios, RBAC ni sesiones_acceso; no seleccionar ahora Sanctum/JWT u otro mecanismo móvil. |
| Bloquea iniciar Fase 1 | No |
| Actualización y autoridad | 2026-10-23; petición Fase 1.5 §§9–16 y 19. Conserva el conflicto y cierre históricos. |
| Bloquea iniciar Fase 2 | No, para autenticación web y núcleo de seguridad. |

#### Contrato D-07 de autenticación web

Adoptar autenticación nativa Laravel con guard web de tipo `session`, proveedor compatible con el futuro modelo Usuario sobre `usuarios`, correo en `correo` y hash de contraseña en `password_hash`. No copiar las columnas/tablas `users`, `email`, `password` o `remember_token` de un starter. El mapeo del contrato Authenticatable debe respetar el esquema existente. Laravel/Inertia/Vue comparten el mismo origen y las rutas web. No JWT, OAuth, Google/Microsoft Login, LDAP ni SSO para este alcance; tampoco registro público, recordar sesión o recuperación añadidos desde bocetos.

Contraseñas: servicio Hash de Laravel con **Argon2id**, soportado por el PHP 8.5.12 local y Laravel 13.38.0 comprobados. Costes y rehash se toman de la configuración del framework, sin criptografía propia ni valores duplicados; el ajuste se realizará al implementar Fase 2. SHA-256 se reserva para identificar sesiones aleatorias, nunca para contraseñas. Cuenta INACTIVO, inexistente o contraseña incorrecta produce una respuesta genérica equivalente, sin informar existencia de correo. Ninguna contraseña, hash de contraseña, ID de sesión o cookie se comparte en props, logs o mensajes de validación.

**Dos responsabilidades.** Laravel Session mantiene la autenticación técnica; `sesiones_acceso` es el registro obligatorio de control de cada sesión autenticada, no una tabla de almacenamiento de payloads ni una mera bitácora informativa. Para desarrollo web se conserva el driver técnico `file` ya disponible; no se necesita una tabla Laravel `sessions`. El almacenamiento técnico privado que administra el framework no se duplica en el esquema académico. La selección distribuida de producción se revisará antes de despliegue.

En `sesiones_acceso.token_hash` se guardará SHA-256 hexadecimal en minúsculas (64 caracteres) del **identificador final** de sesión obtenido en servidor después de regenerarlo; cabe en el VARCHAR(255) existente y conserva su UNIQUE. No guardar el ID crudo, la cookie, la contraseña ni otro token en esta tabla; no se crea un bearer token para Vue ni se aceptará el hash como credencial. Cada fila pertenece a un usuario; varias sesiones del mismo usuario son independientes y salir cierra la actual, sin imponer una restricción de sesión única no documentada.

**Login exitoso, como una operación lógica:** validar entrada, credenciales y ACTIVO; autenticar con Laravel; regenerar/destruir el ID anterior y renovar CSRF; obtener el ID definitivo; calcular el hash; registrar usuario, creación, expiración y revocación nula; solo entonces confirmar el acceso. Laravel 13.38.0 ya regenera en `SessionGuard::updateSession`: la implementación debe registrar el ID después de todas las regeneraciones, evitando un hash intermedio en un listener demasiado temprano. Si falla la creación del registro o el almacenamiento técnico, deshacer autenticación/invalidate y no devolver éxito; una fila huérfana no autentica sin sesión técnica. No prometer atomicidad entre archivos y SQL: se requieren compensación y comprobación conjunta.

**Cada solicitud protegida:** exigir sesión técnica autenticada, usuario todavía ACTIVO y registro del mismo usuario localizado por el hash actual, con `revocada_en IS NULL` y `ahora < expira_en`. Registro inexistente, revocado, expirado, usuario distinto o indisponibilidad de su verificación impiden ejecutar la acción. No recrear automáticamente un registro perdido a partir de una cookie. Deshabilitar una cuenta revoca sus registros vigentes y la comprobación de ACTIVO bloquea todas sus sesiones; reactivarla no revive sesiones revocadas. Cualquier rotación autenticada posterior debe coordinar revocación del hash anterior y registro del nuevo; nunca dejar válido el anterior por omisión.

**Expiración coherente.** Única duración efectiva `config('session.lifetime')`, en minutos positivos, procedente de `SESSION_LIFETIME` y del único valor por defecto del framework. Hoy ese default es 120; no es un nuevo plazo del informe ni se copia a controladores, pruebas o tablas. Se adopta caducidad por inactividad, acorde al driver file: al login `expira_en = creada_en + duración`; tras una solicitud autenticada que supere el control de vigencia se renueva a `ahora + duración`. `creada_en` no cambia. Comprobar el límite anterior **antes** de renovar: en `ahora >= expira_en` se exige nuevo login. Los accesos anónimos o fallidos no reviven la sesión. La caducidad técnica o la del registro, cualquiera que ocurra primero, impide acceso. No depender del borrado periódico de archivos/filas para rechazar expirados.

Renovación, logout y rotaciones deben coordinarse para que una petición concurrente no borre `revocada_en`, renueve una fila caducada ni restaure una autenticación cerrada. Usar los bloqueos de sesión/actualizaciones condicionales disponibles y probar la carrera en Fase 2; las solicitudes ya admitidas antes del cierre no se presentan como canceladas retroactivamente. DATETIME y comparación usan la zona operativa `America/La_Paz` de D-16 y un mismo reloj de servidor. Cambiar la política de duración en un entorno con sesiones activas exige invalidarlas o una transición explícita; no aplicar dos duraciones implícitas.

**Logout confirmado:** petición de cambio de estado protegida por CSRF; localizar el registro actual, marcar `revocada_en` sin borrar la fila, cerrar autenticación con Laravel, invalidar la sesión técnica y regenerar CSRF. Conservar el registro histórico. La repetición del cierre o una sesión ya expirada debe terminar en acceso no autenticado, nunca reactivar el registro. Si falla persistencia/almacenamiento, intentar invalidar la sesión técnica y no afirmar cierre remoto completo sin comprobarlo; el control conjunto continúa denegando accesos no verificables.

**Red caída:** la UI limpia datos sensibles y estado temporal accesible, bloquea la continuación del flujo e informa que el cierre no fue confirmado. Vue no puede borrar una cookie HttpOnly ni revocar por sí sola el servidor. Al recuperar comunicación debe completar el cierre antes de reanudar acciones protegidas y solicitar nueva autenticación; no anunciar revocación remota estando offline. No crear credenciales alternativas en localStorage para simular esa garantía.

**Cookies y CSRF:** cookie de autenticación HttpOnly, SameSite=Lax, dominio limitado al host, ruta de la aplicación y Secure=true bajo HTTPS; Secure=false exclusivamente en desarrollo HTTP local. Conservar la protección CSRF estándar en login/logout y operaciones de escritura, sin exclusiones para Inertia. La cookie `XSRF-TOKEN` prevista por Laravel es distinta de la cookie HttpOnly de autenticación y sirve al encabezado CSRF; no es un bearer token de acceso. Expiración de cookie/almacenamiento se configura con el mismo lifetime; sin “remember me”.

La implementación de login incluirá RateLimiter de Laravel con claves de correo normalizado/IP y límites/ventana centralizados en configuración, respuestas genéricas y pruebas. Las cifras operativas se fijarán durante Fase 2 antes de habilitar el endpoint, sin atribuir al informe un número inexistente ni tratar este ajuste como una decisión de identidad pendiente. El mecanismo móvil/API queda DIFERIDO y no se instala paquete de autenticación API en Fase 1.5.

Sustento técnico: [autenticación Laravel 13](https://laravel.com/docs/13.x/authentication), [sesiones y concurrencia](https://laravel.com/docs/13.x/session), [hashing](https://laravel.com/docs/13.x/hashing), [CSRF](https://laravel.com/docs/13.x/csrf) y [Gates/Policies](https://laravel.com/docs/13.x/authorization), contrastados con el framework instalado. Estas elecciones son contratos de implementación autorizados por Fase 1.5; no se atribuyen como especificaciones literales del Word/PDF.

### D-08 — Vacío de ejecución de liberación

| Campo | Registro |
| --- | --- |
| ID | D-08 |
| Título | Vacío de ejecución de liberación |
| Fuente A | RF18 y CU23, extensión condicional de CU22. |
| Fuente B | politicas_liberacion, evaluaciones_liberacion y asociaciones a eventos/validaciones; Fase 0.5 §13. |
| Conflicto original de Fase 0 | RF18 parte del horario; CU23 se dispara desde monitoreo. La política puede tener usa_iot=false. Faltan disparador temporal sin eventos, tratamiento de antigüedad NULL, tabla de combinación QR/IoT/conflicto y conservación de versiones. |
| Tratamiento registrado en Fase 0 (histórico) | Definir evaluación temporal, tolerancias y evidencia suficiente con criterio conservador. No asumir ausencia como libre. Preservar política/version aplicada; no inventar columnas snapshot sin decisión. ALERTA no libera. |
| Impacto | Disparador temporal y matriz de evidencia no están completamente especificados. |
| Decisión de Fase 0.5 (histórica) | Congelar reserva aprobada aplicable, política, tolerancia y evidencias; uso confirmado mantiene; datos insuficientes/contradictorios o falta de IoT necesario no liberan automáticamente. Guardar evaluación con resultado exacto; ALERTAR no cambia reserva a LIBERADA; solo AUTOMATICA y condiciones inequívocas permiten LIBERAR. No mutar reglas históricas usadas sin preservar versión aplicable. |
| Justificación | El CU23 exige suficiente evidencia y la política limita la automatización; falla de sensor no prueba desocupación. No se añade una relación de persistencia. |
| Estado vigente | RESUELTA PARA FASE 8 |
| Fase afectada | 7.5 documental; implementación de CU23 en 8, todavía no iniciada. |
| Decisión vigente | Contrato de Fase 7.5 inferior: elegibilidad, versiones inmutables, matriz completa QR/IoT, veto, ventanas, scheduler, idempotencia, evidencia exacta, permisos y transacción compatible con los servicios existentes. |
| Detalle diferido / límite de la resolución | Ningún bloqueante documental para el alcance de Fase 8. Implementación y pruebas futuras; notificaciones Fase 10, Blockchain Fase 9, CU30 y finalización natural mantienen su alcance diferido. |
| Autoridad de actualización | Instrucción oficial Fase 7.5 §§1–84; cierre documental posterior a la implementación de Fase 7. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-09 — Vacío de contrato QR y correlación

| Campo | Registro |
| --- | --- |
| ID | D-09 |
| Título | Vacío de contrato QR y correlación |
| Fuente A | CU11/CU20 y extensiones de CU17/CU18; figuras 111/121. |
| Fuente B | codigos_qr, validaciones_qr y sus CHECK/UNIQUE; Fase 0.5 §11. |
| Conflicto original de Fase 0 | No hay CU independiente de emisión/renovación ni política completa de QR obligatorio. Recurso esperado/contexto son obligatorios; código reconocido puede ser NULL. Enlace a entrega/devolución es UNIQUE, aunque puede haber varios intentos. |
| Tratamiento registrado en Fase 0 (histórico) | Acordar emisión/vigencia, contexto y registro de rechazo, reintento/replay y enlace de validación confirmada. No crear CRUD extra ni relajar CHECK/UNIQUE por comodidad. |
| Impacto original | Emisión, vigencia concreta y correlación de intentos requerían detalle; el contrato de Fase 5.5 siguiente resuelve esos puntos conservando el propósito y la exclusividad. |
| Decisión de Fase 0.5 (base conservada) | QR identifica recurso/operación y no autentica ni autoriza. Servidor valida usuario, permiso, código, recurso esperado, operación, estado y tiempo aplicable. Código con exactamente un objetivo recurso/reserva/préstamo. USO_ESPACIO exige reserva y excluye préstamo/entrega/devolución; ENTREGA o DEVOLUCION exige préstamo y excluye reserva y el enlace del tipo contrario. Entrega/devolución enlazadas son opcionales y únicas; no relajar esos constraints. |
| Justificación | Aplicación directa de CU y SQL; tener una cámara o conocer el token nunca habilita una operación protegida. Un intento sin contexto válido no se fuerza a una fila que viole el modelo. |
| Decisión vigente de Fase 5.5 | Token CSPRNG de 256 bits/base64url, SHA-256, objetivo exclusivo, un activo por objetivo, revocación histórica, CU11 contextual y CU20 como extensión explícita con correlación atómica en el mismo request; [contrato completo](#contrato-qr-y-validaciones--cierre-d-09-de-fase-55). |
| Estado | RESUELTA PARA FASE 6 |
| Fase afectada | 5.5 cierre documental; 6 implementación futura CU11/CU20 y extensiones CU17/CU18. |
| Detalle diferido / límite de la resolución | Ningún bloqueante para la extensión explícita de Fase 6. Parametrización global de obligatoriedad, liberación, Blockchain y CU30 conservan alcance posterior; no se crea un CRUD genérico ni uso único. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; base Fase 0.5 y cierre autorizado de D-09 en Fase 5.5 |

### D-10 — Contrato IoT cerrado para Fase 7

| Campo | Registro |
| --- | --- |
| ID | D-10 |
| Fuentes | RF16–17; CU21–22; CU23 solo futuro; IC-06/07; RD-40; SQL sensores_iot, eventos_ocupacion y politicas_liberacion; instrucción Fase 6.5. |
| Conflicto original de Fase 0 | HTTP/MQTT sin selección; faltaban autenticación, payload, unidades, duplicados y reloj/frescura. |
| Tratamiento histórico de Fase 0.5 | Sensor registrado, habilitado y asociado; ambiente, nunca personas; historia y dos fechas; ausencia de lectura no demuestra libre. |
| Decisión vigente | REST con HMAC derivado por sensor, formato/tiempos, frescura, deduplicación transaccional y RBAC humano según [contrato D-10](#contrato-iot--cierre-d-10-de-fase-65). |
| Justificación | IC-06 admite HTTP; IC-07 exige dispositivo/espacio. Preservar SQL, historia, privacidad y autonomía de reservas/préstamos. Convenciones técnicas explícitas, no citas literales del informe. |
| Estado | RESUELTA PARA FASE 7 |
| Fase afectada | 6.5 documental; CU21/CU22 y CU06 informativo en 7; CU23 en 8. |
| Límite | Sin implementación, secretos ni permisos nuevos. Unidades del hardware se describirán al incorporarlo sin bloquear registro/visualización genéricos; MQTT opcional. D-08 y despliegue conservan su fase. |
| Bloqueantes documentales para Fase 7 | 0; no autoriza iniciar Fase 7. |
| Fecha de adopción | 2026-10-23, instrucción Fase 6.5. |

### D-11 — Defecto visual confirmado: entrega

| Campo | Registro |
| --- | --- |
| ID | D-11 |
| Título | Defecto visual confirmado: entrega |
| Fuente A | Figura 23, p. 127 / PDF 128: rama visual de identificación/entrega contradictoria. |
| Fuente B | Texto CU17, tabla 54, p. 198 / PDF 199; Fase 0.5 §8. |
| Conflicto original de Fase 0 | La rama rotulada [Si] de identificación válida conduce a informar inconsistencia; desde ese nodo también parte una conexión a recibir equipamiento. No representa de manera inequívoca el rechazo y la entrega. |
| Tratamiento registrado en Fase 0 (histórico) | Usar el texto RF13/CU17 para identificar la intención, consultar/corregir el diagrama antes de convertirlo a flujo ejecutable. Una identificación inválida nunca autoriza entrega. |
| Impacto | Copiar la flecha podría confirmar entrega tras una identificación inválida. |
| Decisión vigente de Fase 0.5 | Prevalece CU17: préstamo APROBADO pendiente de entrega; responsable verifica equipo/receptor; QR requerido inválido bloquea confirmación; registrar entrega, fecha/hora, receptor/responsable; equipo PRESTADO y préstamo ENTREGADO; evidencia solo si crítica, después de confirmar operación; mostrar confirmación. Flujo de 12 pasos en USE_CASE_MATRIX.md. |
| Justificación | La jerarquía funcional prioriza detalle textual del CU sobre figura ilustrativa defectuosa. No es necesario corregir el Word/PDF ni pedir otra aprobación para ignorar esa flecha. |
| Estado | RESUELTA PARA FASE 5 — cierre operativo documental de Fase 4.5. |
| Fase afectada | 5 y rama QR de 6. |
| Detalle diferido / límite de la resolución | Receptor igual al solicitante; recepcion_confirmada_en permanece NULL; entrega atómica y devolución/revisión definidas en el contrato de Fase 4.5. QR en Fase 6 y Blockchain en Fase 9; no hay funcionalidad de préstamos implementada. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-12 — Vacío de contrato y secuencia Blockchain

| Campo | Registro |
| --- | --- |
| ID | D-12 |
| Título | Vacío de contrato y secuencia Blockchain |
| Fuente A | RF19–20; CU24–CU25; figuras 26/114/123. |
| Fuente B | evidencias_trazabilidad; reglas Fase 0.5 §14/15. |
| Conflicto original de Fase 0 | RF19 permite operaciones críticas configuradas; CU24 enumera extensiones concretas. Proveedor/red, algoritmo, versión canónica, contenido mínimo y recuperación no están fijados. La prohibición de datos sensibles figura al final de un flujo textual; secuencia 114 la comprueba antes del envío. |
| Tratamiento registrado en Fase 0 (histórico) | Verificar privacidad antes de cualquier transmisión. Definir conjunto de operaciones, representación estable, recuperación/reintentos y comprobación externa. SHA-256 del boceto no se adopta automáticamente como única especificación. |
| Impacto | Falta especificación de representación canónica y proveedor, pero orden y privacidad están establecidos. |
| Decisión de Fase 0.5 (histórica) | Congelar MySQL confirmado → datos mínimos permitidos → representación consistente → hash → evidencia local → envío externo → referencia. Privacidad antes de transmitir; no nombres/correos/contraseñas/datos completos. Caída externa conserva operación y evidencia PENDIENTE. Representación determinista, orden estable, fechas estables, UTF-8 y version_canonica obligatorios; no fijar ahora algoritmo/serialización exactos. |
| Justificación | Separar atomicidad local de dependencia externa satisface CU24 y R-DISP-06; version_canonica permite verificación reproducible. No adoptar SHA-256 de un boceto como decisión automática. |
| Estado | RESUELTA PARA FASE 9 — cierre documental Fase 8.5, 2026-10-25. |
| Fase afectada | 9, antes de generación/verificación funcional. |
| Decisión vigente de Fase 8.5 | Cuatro acciones exactas; actividad en transacción, evidencia tras commit; SHA-256/JCS espacio-u-jcs-v1; Sepolia/11155111; Registry mínimo e idempotente; recuperación, CU25 y tres permisos solo Administrador. [Contrato completo](#contrato-blockchain--cierre-d-12-de-fase-85). |
| Detalle diferido / límite de la resolución | Ningún bloqueante documental para Fase 9. Implementación, compilador estable, despliegue y configuración segura se harán en esa fase cuando sea autorizada; CU30 general sigue diferido. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-13 — Contenido residual del cronograma

| Campo | Registro |
| --- | --- |
| ID | D-13 |
| Título | Contenido residual del cronograma |
| Fuente A | Cronograma figura 4, p. 54 / PDF 55. |
| Fuente B | Diseño final cap. VII, RD/IS/IC, CU/RF y Fase 0.5 §16. |
| Conflicto original de Fase 0 | El gráfico menciona aulas/activos, PWA, voz, predicción, Flutter y APK. Parte del vocabulario/funciones no pertenece al alcance vigente de RF o está diferida expresamente. |
| Tratamiento registrado en Fase 0 (histórico) | No recuperar entidades antiguas ni añadir PWA/voz/predicción. Mantener web y fases solicitadas; Flutter/Android quedan fuera hasta nueva autorización. |
| Impacto | Vocabulario y funciones históricas podrían contaminar el alcance actual. |
| Decisión vigente de Fase 0.5 | Código de estado: NO_AFECTA_IMPLEMENTACION. Tratar el cronograma como planificación histórica; ignorar sus términos contradictorios para dominio, datos, CU, arquitectura, funciones y tecnologías. No añadir PWA, voz o predicción ni reintroducir Activo/Aula general. Flutter sigue diferido por la instrucción de alcance web. |
| Justificación | La planificación histórica no redefine el contrato final. Se conserva el hallazgo para trazabilidad; no requiere corregir las fuentes. |
| Estado | NO AFECTA IMPLEMENTACIÓN |
| Fase afectada | Todas; control de alcance desde 1. |
| Detalle diferido / límite de la resolución | Ninguno; el cronograma no tiene autoridad para ampliar implementación. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-14 — Bocetos/entidades de análisis sin respaldo persistente

| Campo | Registro |
| --- | --- |
| ID | D-14 |
| Título | Bocetos/entidades de análisis sin respaldo persistente |
| Fuente A | Figuras de análisis/interfaz 103 y 115. |
| Fuente B | CU01/CU29, RF1/RF24 y modelo final SQL; jerarquía Fase 0.5. |
| Conflicto original de Fase 0 | El análisis de chatbot muestra conversación; la interfaz de login muestra recordar sesión/recuperación. No existe tabla de conversación ni especificación completa de recuperación en el modelo y CU finales. |
| Tratamiento registrado en Fase 0 (histórico) | No generar historial persistente, tabla adicional o función de recuperación solo por el boceto. Aclarar si debe incorporarse; preservar asistencia de orientación. |
| Impacto | Bocetos muestran conversación, recordar sesión y recuperación sin un contrato funcional completo. |
| Decisión vigente de Fase 0.5 | No generar tabla de conversación, historial persistente ni recuperación/recordar sesión como nuevas funcionalidades a partir del boceto. CU01 conserva su acceso definido y CU29 orientación. La interfaz debe adaptarse a ese alcance. |
| Justificación | Un elemento ilustrativo no amplía los CU ni crea una entidad persistida. Cualquier ampliación futura requeriría una instrucción de alcance independiente. |
| Estado | NO AFECTA IMPLEMENTACIÓN |
| Fase afectada | 2 y 12. |
| Detalle diferido / límite de la resolución | Ninguno para el alcance vigente; proveedor chatbot se decide en su fase. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-15 — Selección de versiones y entorno pendiente

| Campo | Registro |
| --- | --- |
| ID | D-15 |
| Título | Selección de versiones y entorno pendiente |
| Fuente A | Stack definido por cap. VII y RD-01–12/36–38. |
| Fuente B | Inventario local de Fase 0 y tareas de fundación Fase 1. |
| Conflicto original de Fase 0 | Informe fija tecnologías, no versiones precisas del backend/compilador. PHP es 8.2.12; existen MySQL 8.0.46 y 9.7.0; XAMPP incluye MariaDB; motor Docker no estaba disponible. |
| Tratamiento registrado en Fase 0 (histórico) | En Fase 1 contrastar soporte y compatibilidad con documentación oficial vigente, elegir/fijar versiones y una instancia MySQL. No sustituir MySQL por MariaDB ni instalar scaffold en Fase 0. |
| Impacto | Versiones, instancia y herramientas requieren selección compatible, sin contradicción de tecnología. |
| Decisión vigente de Fase 0.5 | Tecnologías congeladas: PHP/Laravel, Vue 3/Inertia/Vite, MySQL y despliegue previsto. Elegir y fijar versiones compatibles como primera tarea técnica de Fase 1 consultando fuentes oficiales vigentes; no sustituir MySQL por MariaDB. No instalar ni configurar nada en Fase 0.5. |
| Justificación | Seleccionar versiones es parte del trabajo autorizado de fundación cuando se solicite; no es una ambigüedad funcional ni exige cambiar el modelo. |
| Estado | DIFERIDA A UNA FASE POSTERIOR |
| Fase afectada | 1 antes de instalar/lockfiles; Docker antes de ensayos de 14. |
| Detalle diferido / límite de la resolución | Versiones/instancia web atendidas en Fase 1, según actualización inferior. Solo queda la operatividad de Docker antes de ensayos de Fase 14; por ese detalle el expediente conserva su estado DIFERIDA. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

**Actualización D-15 en Fase 1 (2026-10-23):** selección web completada antes de cada instalación con fuentes oficiales: PHP 8.5.12, Laravel 13.38.0, Composer 2.10.3, Node 24.22.1 LTS/npm 11.19.1, Vue 3.5.44, Inertia Laravel 3.5.2 y Vue/Vite 3.9.1, Vite 8.3.5, MySQL 9.7.4 LTS. Herramientas locales ignoradas y lockfiles públicos. Las dos instancias existentes requerían credenciales; el usuario autorizó expresamente una tercera instancia aislada en el workspace, base `espacio_u`, loopback 3308 y credenciales generadas solo en `.env`. Sin modificar servicios existentes ni activar Docker. Tailwind 4.3.3 se usa para el CSS mínimo previsto por el informe. [Procedimiento y pruebas](DEVELOPMENT_SETUP.md). El resto de decisiones diferidas no se resuelve anticipadamente.

### D-16 — Parámetros de tiempo/medición no fijados

| Campo | Registro |
| --- | --- |
| ID | D-16 |
| Título | Parámetros de tiempo/medición no fijados |
| Fuente A | RD-25, fechas DATETIME del SQL y RNF-01/R-TR. |
| Fuente B | Fase 0.5 solicita contratos interpretables sin alterar campos ni límites oficiales. |
| Conflicto original de Fase 0 | La UI debe presentar hora de Bolivia, pero no se fija almacenamiento/conversión. RNF-01 incluye login, sin umbral específico R-TR para esa operación. No hay duración/percentil de pruebas ni retención completa de eventos. |
| Tratamiento registrado en Fase 0 (histórico) | Documentar zona/normalización, método de medición y retención antes de implementación afectada; no inventar 3 s para login ni sustituir máximos por promedio. |
| Impacto | Riesgo de mezclar zonas horarias o prometer medidas no definidas. |
| Decisión vigente de Fase 0.5 | Zona operativa única America/La_Paz para captura, presentación y DATETIME de esta plataforma institucional; convertir instantes externos con zona explícita a esa zona antes de persistir/comparar, sin mezclar valores UTC sin conversión. Configuración efectiva se hará en Fase 1. Mantener máximos R-TR sin sustituirlos por promedio/percentil inventado; no asignar 3 s al login por analogía. |
| Justificación | Convención explícita coherente con alcance Bolivia y tipos DATETIME existentes; no cambia el modelo ni el algoritmo canónico. El informe fija máximos de operaciones específicas, no de todo endpoint. |
| Estado | CONVENCIÓN DE IMPLEMENTACIÓN |
| Fase afectada | 1 para zona; 7/13/14 para parámetros de operación/medición. |
| Detalle diferido / límite de la resolución | Antes de 13 fijar protocolo de medición y objetivo de login si se requiere; Fase 6.5 fija conservación del piloto sin purga automática; retención productiva antes de 14. Formato canónico de fechas antes de 9. Ninguno bloquea Fase 1. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-17 — Invariantes no garantizadas solo por SQL

| Campo | Registro |
| --- | --- |
| ID | D-17 |
| Título | Invariantes no garantizadas solo por SQL |
| Fuente A | DB-17/21, superclase RecursoInstitucional e historia de roles. |
| Fuente B | Índices, FK, cascadas y constraints del SQL final. |
| Conflicto original de Fase 0 | Índices no impiden solapamientos; cascadas no satisfacen por sí solas preservar historia; asignaciones activas duplicadas y semántica de subtipo no quedan completamente restringidas. |
| Tratamiento registrado en Fase 0 (histórico) | Mantener el esquema como referencia, diseñar guardas transaccionales/autorización y pruebas. Cualquier restricción adicional necesita decisión trazable, sin borrar historia ni alterar modelo silenciosamente. |
| Impacto | Confundir integridad declarativa con cumplimiento total de reglas podría borrar historia o permitir conflictos. |
| Decisión vigente de Fase 0.5 | Preservar todas las restricciones SQL; complementarlas con autorización y transacciones de servidor. Índice temporal no sustituye comprobación de solapamiento; cascada no autoriza borrar historia; consultas RBAC tratan roles/permisos como conjuntos aunque haya historia de asignaciones. Crear el recurso y su subtipo coherentemente, sin añadir discriminadores/FK/UNIQUE no documentados. |
| Justificación | Son mecanismos de cumplimiento del comportamiento definido, no un nuevo diseño de BD. La aplicación debe imponer las reglas que SQL no expresa, sin atribuirlas a constraints inexistentes. |
| Estado | CONVENCIÓN DE IMPLEMENTACIÓN |
| Fase afectada | 2–10; concurrencia en 3–5, validación integral en 13. |
| Detalle diferido / límite de la resolución | El detalle de bloqueo/transacción y pruebas se elige en su fase dentro de estos invariantes; no bloquea Fase 1. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

## Detalles posteriores y momento de cierre

Estos puntos no se implementaron ni se presentan como especificaciones completas. Deben cerrarse antes del comportamiento afectado, conservando los contratos anteriores y sin introducir funcionalidades nuevas.

| Expediente | Detalle pendiente | Momento límite |
| --- | --- | --- |
| D-04 | Alcance fino por recurso DIFERIDO EN PARTE; RBAC implementado, sin nueva tabla inferida | Fases 3/3.5 concretan CU05–CU10 y Fase 4.5 aplica el mismo alcance por módulo/acción y propiedad a CU12–CU19. Resolver asignaciones finas solo cuando se autorice ese alcance |
| D-05 | Reservas resueltas para Fase 4; aplicación a préstamos cerrada en Fase 4.5. Solo FINALIZADA automática de reservas sigue diferida | Finalización solo en una fase posterior autorizada. Sin bloqueo documental para CU12–CU19 |
| D-07 | Autenticación móvil/API y almacenamiento distribuido; web ya resuelta | Cuando se autorice el cliente/API; despliegue antes de Fase 14 |
| D-08 | Ejecución temporal de monitoreo/liberación, umbrales y combinación de evidencia, conservación de versiones aplicadas | Fase 8, con contrato de datos preparado en 7 |
| D-09 | Detalles resueltos documentalmente en Fase 5.5; [contrato y puerta de Fase 6](#readiness-for-phase-6) | Implementación en Fase 6; ninguna relajación de CHECK/UNIQUE |
| D-10 | RESUELTA PARA FASE 7; [contrato congelado](#readiness-for-phase-7) | Implementar/probar cuando se autorice Fase 7; MQTT no obligatorio |
| D-12 | RESUELTA PARA FASE 9 en Fase 8.5: contrato exacto, SQL compatible autorizado y estrategia de pruebas | [Readiness for Phase 9](#readiness-for-phase-9), cero bloqueantes documentales; implementación no iniciada |
| D-15 | Versiones/instancia local atendidas en Fase 1; solo operatividad Docker pendiente | Docker antes de ensayos de 14 |
| D-16 | Protocolo de medición, umbral específico de login si se define y retención operativa | Fases 7/13/14 según punto; formato canónico de fechas en 9 |
| D-17 | Mecanismo concreto de transacciones/bloqueos y pruebas de invariantes | Fase que implemente el flujo, sin alterar el contrato físico |

La selección de versiones es una actividad de Fase 1, no una contradicción que deba resolverse instalando software durante Fase 0.5. Las decisiones sobre integraciones no impiden inicializar el framework. “Modelo implementable” se refiere al contrato físico coherente; no afirma que todas las políticas operativas futuras estén parametrizadas.

## Readiness for Phase 1

- **Contradicciones bloqueantes: 0.** Ningún expediente está en estado REALMENTE BLOQUEANTE para iniciar la fundación técnica.
- **Estados persistidos definidos:** 19 campos ENUM exactos del SQL; booleans y estados derivados documentados en DOMAIN_MODEL.md, sin nuevos valores.
- **Nombres técnicos definidos:** ASCII sin tildes; snake_case cuando corresponde; `permisos.codigo` es la normalización autorizada; texto humano en español correcto.
- **Jerarquía documental definida:** persistencia, comportamiento, dominio y arquitectura tienen reglas explícitas de precedencia.
- **Actores/permisos interpretables:** CU iniciador y flujo, luego permiso/RBAC/guardas; participación RF no concede ejecución; denegar si no se acredita ámbito.
- **Cardinalidades implementables:** 63 FK, nullable, UNIQUE, PK compartidas y tablas M:N preservadas; diferencias resueltas en la matriz de DATA_MODEL.md.
- **Modelo de datos implementable:** 31 tablas/211 columnas; única normalización lexical D-01; sin FK, entidades o constraints nuevos ni ejecución SQL en esta fase.
- **Seguridad Git definida:** .gitignore y plantilla segura; revisión de índice/diferencias/secretos antes de commit/push; ningún dato real de desarrollo.
- **Cuestiones diferidas identificadas:** tabla anterior con fase límite; no se convierten en decisiones implícitas durante implementación.
- **Resultado histórico de Fase 0.5:** proyecto documentalmente listo para iniciar Fase 1. La nueva instrucción posterior autorizó y completó su fundación técnica; este registro preserva la distinción entre preparación e implementación.

## Verificación de esta revisión

Se reconsultaron ambos originales; las huellas SHA-256 siguen siendo las de Fuentes verificadas. Se revisaron los 17 expedientes, los 30 CU y 26 RF para interpretar actores; los 19 campos ENUM y sus términos narrativos; clases/dominio/ERD y restricciones SQL para cardinalidades. Las resoluciones conservan el conflicto original y su tratamiento histórico.

El cierre de Fase 0.5 es exclusivamente documental. No se instaló Laravel, no se creó software ni se configuró MySQL. Se comprobó que permanecen íntegros los 217 identificadores/cláusulas de requisitos, las 30 fichas CU y el diccionario de 31 tablas, 211 columnas y 63 FK. Tipos, defaults, nulabilidad, PK/FK, índices y constraints coinciden con Fase 0 salvo la grafía autorizada codigo. Los 19 ENUM coinciden con la extracción del SQL; se verificaron enlaces/tablas Markdown, conteos de decisiones, historial preservado y ejemplos de intervalos. La validación funcional de la aplicación y las mediciones de rendimiento siguen pendientes de sus fases.

## Cierre técnico posterior de Fase 1

Se implementó y verificó exclusivamente la fundación web; el cierre de Fase 0.5 anterior sigue siendo histórico. La configuración de D-16 ya usa español, `America/La_Paz` y sesión MySQL `-04:00`. No se diseñaron algoritmos de conversiones externas ni parámetros QR/IoT/Blockchain. Al cerrar Fase 1, D-04 y D-07 seguían pendientes; Fase 1.5 las cierra a continuación para el núcleo web. Fase 2 sigue sin iniciarse. El Word/PDF, las 31 tablas/211 columnas/63 FK documentales, los 19 ENUM y los 30 CU no se alteraron.


## Readiness for Phase 2

- **D-04: RESUELTA PARA FASE 2.** RBAC, cuatro roles oficiales, convención de permisos, asignaciones históricas y mecanismos centrales definidos. **DIFERIDA EN PARTE:** alcance fino por recurso antes de exponer su flujo funcional; no equivale a acceso global del Encargado.
- **D-07: RESUELTA PARA FASE 2.** Laravel Session web, Hash/Argon2id, CSRF, regeneración, control obligatorio por hash en sesiones_acceso, expiración por configuración y logout definidos. Autenticación móvil/API DIFERIDA.
- **Bloqueantes para Fase 2: 0**, para el núcleo web delimitado por la petición Fase 1.5. El catálogo trazado, las pruebas negativas y los parámetros operativos son tareas de implementación, no contratos de identidad abiertos.
- **Tablas permitidas:** usuarios, roles, permisos, roles_permisos, asignaciones_roles y sesiones_acceso; tablas técnicas del framework solo si se demuestra necesidad y se distinguen del modelo académico. El driver file actual evita exigir tablas técnicas adicionales. Sin tablas funcionales de otros módulos.
- **Límite de bitácora:** el plan anterior incluía CU30 básico. La nueva lista cerrada de seis tablas de Fase 1.5 §15 no incluye registros_actividad; su implementación y consulta funcional quedan pendientes de autorización de alcance. No borran RF25/CU30 del contrato ni se simulan como satisfechos con logs técnicos o sesiones_acceso. Esta limitación no bloquea CU01–CU04, pero impide declarar completa la bitácora o la parte de auditoría general de sus flujos.
- **Pruebas con escritura:** MySQL separado espacio_u_testing, cuenta exclusiva restringida a ese esquema y configuración privada local. Verificar entorno/driver/base efectiva antes de cualquier migración o limpieza; prohibido usar espacio_u. Estrategia detallada en [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md#aislamiento-de-pruebas-para-fase-2).
- **Estado de entrega:** solo contratos documentales. No se crearon migraciones, modelos, middleware, Policies, seeders, pantallas de login, cuentas ni bases de testing; no se instalaron paquetes. Fase 2 puede comenzar con nueva instrucción, pero **no se inicia en esta entrega**.

Revisión de fuentes de Fase 1.5: CU01/CU02 (tablas 38/39; pp. 175–177), descripción del Encargado y las seis tablas de seguridad reconsultadas en el Word; CU01/CU02 contrastados con el PDF. Huellas de ambos originales intactas. Se mantiene la normalización D-01 de codigo, los tipos, FK, índices y enums del modelo. La clasificación de Fase 0.5 se conserva como historia, sin reabrir el resto de decisiones.

## Concreción posterior de Fase 2 — sin reabrir contratos

D-04/D-07 implementadas para el núcleo web. Se derivaron once códigos administrativos trazados a CU03/CU04; solo el Administrador vigente y con permiso aportado por ese rol ejecuta esas acciones. Los otros tres roles conservan acceso a su propia sesión e Inicio. El alta de roles solo completa identidades oficiales ausentes; el nombre canónico no se renombra y su descripción es editable.

D-17 se concreta en bloqueo transaccional común sobre Administrador, reautorización tras adquirirlo, bloqueo de usuario al asignar y retiro de todas las filas activas del par, sin borrar historia ni alterar el SQL. Se conserva al menos un administrador activo y los seis permisos de recuperación de PermissionCatalog::RECOVERY; pruebas concurrentes verifican el invariante. Bootstrap atribuye su primera asignación al propio administrador, no crea un actor ficticio ni modifica FK. Estas son convenciones de implementación dentro de lo autorizado, no decisiones de dominio nuevas.

Testing MySQL está aprovisionado y aislado. D-04 ámbito por recurso, D-07 móvil/distribución y todos los detalles posteriores mantienen su estado diferido. CU30 no está implementado; ninguna conclusión de las secciones históricas equivale a implementarlo. [Implementación y comprobaciones](SECURITY_IMPLEMENTATION.md).

## Precisión e implementación autorizadas en Fase 3

D-04: la nueva petición §35 autoriza expresamente CU05 a nivel de módulo/acción y mantiene diferido el ámbito fino por recurso. Sustituye para esta fase el requisito histórico de acreditarlo antes de exponer espacios; no autoriza inventar encargado_espacio, usuarios_espacios ni responsabilidades_recursos. La matriz concede consulta a los cuatro roles y tres acciones de gestión a Administrador/Encargado, siempre con permiso del mismo rol vigente y sin bypass.

D-05: se aplica [inicio, fin), fin > inicio y America/La_Paz a bloqueos/consulta. Se admiten bloqueos solapados porque SQL no prohíbe esa situación; cualquiera activo excluye, desactivación explícita conserva historia. La expiración temporal no reescribe activo. Al cierre histórico de Fase 3, los parámetros del ciclo de reservas seguían diferidos; el contrato de Fase 3.5 siguiente resuelve los necesarios para CU07–CU10.

Las ocho estructuras SQL Word/PDF fueron contrastadas y las huellas originales no cambiaron. politicas_liberacion se adelanta únicamente para integridad FK, sin lógica de Fase 8. CU06 queda IMPLEMENTACIÓN PARCIAL — FASE 3: reservas Fase 4 e IoT Fase 7. CU30 y demás decisiones diferidas se conservan. [Implementación y evidencia](SPACES_IMPLEMENTATION.md).

## Contrato de reservas — cierre D-05 de Fase 3.5

Nota de continuidad: el texto siguiente conserva el contrato y estado histórico de la entrega 3.5. La implementación posterior está en [RESERVATIONS_IMPLEMENTATION.md](RESERVATIONS_IMPLEMENTATION.md).

**Estado: RESUELTA PARA FASE 4. Fecha: 2026-10-23. Entrega exclusivamente documental.** La instrucción de Fase 3.5 cierra las ambigüedades necesarias para CU07–CU10, sin iniciar su implementación ni reabrir las demás decisiones. Mantiene D-03, D-04, los seis estados y el esquema oficial. Las reglas de préstamos de D-05 no se amplían en esta entrega.

### Fuentes y naturaleza de las decisiones

Se reconsultaron el Word oficial `PERFIL DEL PROYECTO 2-2026.docx` y su PDF `Fase2-grupo21.pdf`: RF5, RF6 y RF7 (tablas 14–16; páginas impresas 116, 118 y 120 / PDF 117, 119 y 121); CU06–CU10 (tablas 43–47; impresas 182–189 / PDF 183–190); y `CREATE TABLE reservas` (impresas 279–281 / PDF 280–282). El SQL de reservas coincide entre ambos originales después de normalizar espacios y retirar encabezados de página. Sus huellas originales se conservan.

Las fuentes exigen revalidar disponibilidad, respetar permisos/propiedad, resolver solicitudes pendientes y evitar cambios parciales. CU09 condiciona el motivo de rechazo a cuando corresponda; CU10 condiciona el motivo a una política. RF7 menciona el motivo de rechazo de manera general: se adopta la precisión del flujo CU09 y la nulabilidad del SQL, sin deducir una obligación universal. El motivo opcional es una convención explícita para Fase 4; no se afirma que RF7 omita toda referencia al motivo.

La semántica de exclusividad, frontera temporal de cancelación, formato del código y mecanismo de serialización son convenciones de implementación adoptadas por esta instrucción. No se atribuyen al informe como normas institucionales literales. No se encontraron un plazo numérico, un formato institucional del código ni un disparador obligatorio de FINALIZADA que deban incorporarse a Fase 4.

### Estados, intervalos y disponibilidad compartida

Los únicos estados persistidos son `PENDIENTE`, `APROBADA`, `RECHAZADA`, `CANCELADA`, `LIBERADA` y `FINALIZADA`, con inicio en PENDIENTE. Solo APROBADA compromete el intervalo. PENDIENTE significa solicitud en revisión: varias pendientes del mismo espacio y periodo pueden coexistir; ninguna garantiza aprobación ni bloquea exclusividad. Los otros cuatro estados tampoco bloquean. No se añade ACTIVA como estado.

Todo intervalo es `[inicio, fin)`, con `fin > inicio`. Existe solapamiento si `existente.inicio < solicitado.fin AND existente.fin > solicitado.inicio`, siempre sobre el mismo espacio. Los periodos 08:00–09:00 y 09:00–10:00 son contiguos y compatibles. Fechas capturadas, persistidas y comparadas mantienen `America/La_Paz` y la conexión local ya configurada; el cliente no decide el reloj ni la validez temporal.

La disponibilidad completa exige conjuntamente: espacio existente con estado `DISPONIBLE`, todos los filtros solicitados satisfechos, ningún bloqueo activo solapado y ninguna reserva APROBADA solapada. BLOQUEADO y EN_MANTENIMIENTO excluyen aunque no haya reservas. Un filtro de estado no habilita reservar un espacio no operativo. IoT solo será complemento desde Fase 7.

CU06, CU07 y la aprobación de CU09 compartirán **SpaceAvailabilityService**, ampliando en Fase 4 el servicio existente. Los predicados de estado, filtros, bloqueos, APROBADA y solapamiento tendrán una sola definición. La consulta CU06 es informativa; el mismo servicio ofrecerá revalidación transaccional con lecturas actuales para CU07/CU09. Cambia la garantía de lectura, no la regla de disponibilidad. No se duplica el cálculo en controladores, Vue ni otro servicio independiente. El servicio actual permanece sin cambios y todavía no consulta reservas.

### CU07 — Solicitud y confirmación

El iniciador es Docente o Estudiante con cuenta/sesión válida y `reservas.solicitar`, conforme a D-03. Administrador/Encargado no obtienen esa facultad por su rol de gestión; si además poseen un rol solicitante vigente, este debe aportar el permiso correspondiente.

Validar espacio existente y operativo, propósito obligatorio de hasta 500 caracteres, fechas interpretables, `fin > inicio` y permisos. Antes de persistir, volver a comprobar dentro de la transacción la disponibilidad completa, incluso si CU06 mostró disponible segundos antes. La propiedad se toma del usuario autenticado; el cliente no elige otro solicitante, código, estado, responsables ni fechas de registro/decisión.

Si la comprobación final es válida, generar código en servidor, registrar PENDIENTE y `solicitada_en`, dejando los datos de resolución/cancelación nulos. Confirmar únicamente después del commit. Si apareció un bloqueo, APROBADA solapada, mantenimiento u otra condición incompatible, rechazar el registro sin fila parcial. Otra PENDIENTE no constituye ese conflicto. No se inventan horas mínimas de anticipación ni políticas de duración máxima.

### CU09 — Resolución y conflicto

Solo una PENDIENTE puede aprobarse o rechazarse. Ambas acciones requieren Administrador o Encargado con `reservas.resolver`, reautorización y comprobación del estado vigente dentro de la transacción.

Aprobar requiere la disponibilidad completa actual; excluir del conjunto de conflictos la propia solicitud por id. Registrar atómicamente APROBADA, `resuelta_por_id` del actor autenticado, `resuelta_en` del servidor y `motivo_rechazo = NULL`. El predicado de escritura debe exigir todavía PENDIENTE y afectar exactamente una fila; nunca aceptar un estado arbitrario enviado por el cliente.

Si la disponibilidad cambió, **no aprobar y mantener PENDIENTE** sin responsable/fecha de resolución nuevos. Informar el conflicto sin revelar detalles de terceros no autorizados. No auto-rechazar ni modificar las demás solicitudes pendientes, ni crear una prioridad de adjudicación por fecha de solicitud. El orden efectivo lo determina la adquisición del bloqueo y la validación completa.

Rechazar registra atómicamente RECHAZADA, `resuelta_por_id`, `resuelta_en` y `motivo_rechazo` opcional, de hasta 500 caracteres; ausencia o texto vacío normalizado se conserva como NULL. Un rechazo explícito no exige que el espacio esté disponible: puede rechazar una solicitud conflictiva. No se borra la solicitud. Una solicitud ya resuelta, cancelada, liberada o finalizada no vuelve a resolverse.

### Protocolo transaccional y orden de bloqueos

La unidad de exclusión de reservas es **la fila existente de `espacios_academicos`, identificada por `recurso_id`, mediante `SELECT ... FOR UPDATE` en InnoDB**. Bloquear solo las reservas encontradas no basta cuando aún no existe ninguna APROBADA. Todos los caminos que puedan alterar la decisión deben respetar la misma coordinación: CU07, CU09, CU10 y los escritores de estado/bloqueos de CU05. No hay tabla nueva de locks, bloqueo basado en navegador ni constraint de exclusión temporal inexistente en MySQL.

Orden previsto compatible con los servicios actuales:

1. Iniciar transacción. Adquirir el bloqueo de coordinación RBAC ya existente sobre la fila del rol Administrador y reautorizar al actor con datos vigentes. Es el protocolo de AdministrationService/SpaceManagementService, no un bypass ni una concesión de rol.
2. Adquirir `FOR UPDATE` sobre la fila del espacio por su PK. En CU09/CU10, un id obtenido antes de la transacción sirve solo para localizar; volver a comprobar la relación bajo bloqueo.
3. En una transición, bloquear después la reserva por PK y releer estado/propiedad/periodo. El espacio y periodo de una reserva no tienen edición genérica en CU07–CU10. Todas las operaciones usan ese orden, evitando invertir reserva→espacio.
4. Revalidar autorización, estado y guardas de la operación. Para solicitud/aprobación, el servicio central realiza lecturas actuales del estado del espacio, bloqueos activos solapados y reservas APROBADAS solapadas. Para cancelar, releer además el reloj del servidor después de cualquier espera y comprobar `ahora < inicio`.
5. Insertar la solicitud o actualizar condicionalmente el estado permitido, junto con todos sus campos de responsabilidad/fecha. Una transición fallida no modifica campos secundarios. Confirmar la transacción completa; liberar locks al commit o rollback.

El bloqueo RBAC existente también serializa operaciones de distintos espacios: es una limitación conocida de la implementación actual, no una promesa de paralelismo por espacio. Mantener además el bloqueo de espacio conserva el invariante de dominio y permite revisar la coordinación global en una fase futura sin depender de ella como única defensa. No se cambia esa arquitectura en Fase 3.5.

Con REPEATABLE READ, una consulta ordinaria puede conservar una instantánea anterior. Las comprobaciones decisorias usarán consultas de filas con bloqueo dentro de la transacción; un `FOR UPDATE` exterior no hace actuales ni bloquea por sí mismo las subconsultas de un `NOT EXISTS`. No usar `SKIP LOCKED`, porque omitiría conflictos. Estos cuidados se sustentan en [MySQL: locking reads](https://dev.mysql.com/doc/refman/9.7/en/innodb-locking-reads.html) y [niveles de aislamiento](https://dev.mysql.com/doc/refman/9.7/en/innodb-transaction-isolation-levels.html). No se cambia globalmente el aislamiento de MySQL.

Ante dos aprobaciones compatibles en todo salvo el solapamiento, A obtiene la coordinación, aprueba y confirma. B espera; después relee la decisión confirmada de A y devuelve conflicto, conservando su solicitud PENDIENTE. Si A revierte, B puede aprobar si sus demás condiciones siguen válidas. Si los intervalos son contiguos, ambas pueden aprobarse. El protocolo evita la carrera entre consultar y escribir; no promete disponibilidad inmutable después del commit frente a cambios operativos posteriores.

Un error previo al commit revierte toda la operación. Los reintentos por deadlock serán acotados a tres intentos totales de la transacción, repitiendo autorización y validación completa; un timeout agotado devuelve un fallo controlado, sin transición parcial. No reintentar ciegamente una creación si una desconexión deja incierto el resultado del commit: consultar el estado confirmado antes de repetir. No ejecutar efectos externos dentro de estos reintentos.

### CU10 — Cancelación propia y administrativa

Solo PENDIENTE o APROBADA son cancelables. RECHAZADA, CANCELADA, LIBERADA y FINALIZADA no lo son. La cancelación propia exige `solicitante_id == usuario_autenticado.id` y `reservas.cancelar_propias`. La administrativa exige rol Administrador/Encargado vigente y `reservas.cancelar_administrativas`; Docente/Estudiante no cancelan reservas ajenas.

**Convención temporal mínima: `ahora < inicio`.** En la igualdad y después del inicio se deniega la cancelación ordinaria, incluido el responsable administrativo. La comparación se hace con hora del servidor en America/La_Paz dentro de la transacción y después de esperar locks. No se fija 24 horas, dos horas, minutos de tolerancia ni una configuración nueva. No se autoriza una excepción administrativa después del inicio.

`motivo_cancelacion` es opcional en Fase 4 y conserva `VARCHAR(500) NULL`; vacío normalizado equivale a NULL. Persistir en una misma transición CANCELADA, `cancelada_por_id`, `cancelada_en` y el motivo si se aportó. Conservar código, solicitante, periodo, propósito, `solicitada_en`, `resuelta_por_id`, `resuelta_en` y motivo de resolución existente. No confundir CANCELADA con LIBERADA. La disponibilidad resultante se recalcula con todas las demás restricciones; no se fuerza DISPONIBLE ni se desactiva un bloqueo del espacio.

Si aprobar y cancelar compiten, se aplica el orden de locks: una cancelación que confirma primero impide resolver; una aprobación que confirma primero permite después cancelar solo si todavía cumple propiedad/permiso y `ahora < inicio`. Dos cancelaciones no producen dos transiciones ni reemplazan al primer responsable.

La [tabla formal de transiciones](DOMAIN_MODEL.md#transiciones-de-reserva--contrato-de-fase-35) contiene los únicos cuatro cambios de estado autorizados para Fase 4. Alta PENDIENTE no equivale a aprobación. No habrá selector genérico de estados ni asignación masiva de `estado = APROBADA` desde el formulario.

### CU08 y catálogo mínimo de permisos previsto

Docente/Estudiante consultan solo sus propias reservas. Administrador/Encargado pueden usar consulta administrativa con el permiso correspondiente. Por la precisión de esta instrucción sobre D-04, la consulta y gestión administrativa se habilitarán por módulo/acción, sin atribuir recursos asignados que el modelo no acredita. **El alcance fino por recurso continúa diferido**; no crear tablas de responsabilidad. Esto no concede acceso administrativo a quien solo tenga permisos propios.

Los siguientes seis códigos son un contrato, **no permisos creados en la base de datos**. Una marca indica elegibilidad de ese rol para la asignación prevista; no reemplaza comprobar el permiso concreto en servidor.

| Código previsto | CU / acción | Administrador | Encargado | Docente | Estudiante |
| --- | --- | --- | --- | --- | --- |
| `reservas.ver_propias` | CU08, propiedad obligatoria | Sí | Sí | Sí | Sí |
| `reservas.ver_administrativas` | CU08, consulta de gestión | Sí | Sí | No | No |
| `reservas.solicitar` | CU07 | No | No | Sí | Sí |
| `reservas.resolver` | CU09, aprobar o rechazar | Sí | Sí | No | No |
| `reservas.cancelar_propias` | CU10, propiedad obligatoria | Sí | Sí | Sí | Sí |
| `reservas.cancelar_administrativas` | CU10, cancelación de gestión | Sí | Sí | No | No |

No hay permiso adicional para cada estado, rechazo separado innecesario ni bypass del Administrador. Un rol elegible vigente debe aportar el permiso; no combinar elegibilidad de un rol y permiso de otro incompatible. Mantener cuenta/sesión válida, revocación y autorización servidor de Fase 2. Las facultades propias de un responsable permiten consultar/cancelar reservas de las que sea titular, sin darle por ello facultad de crear solicitudes con su solo rol administrativo.

Filtros conceptuales: fecha, estado y espacio; solicitante solo en consulta administrativa autorizada. Se aplican por conjunción después de delimitar el conjunto autorizado, también en detalle, contadores y paginación. Un filtro o id/código manipulado no amplía ese conjunto; rechazar el filtro de solicitante en la consulta propia. No devolver opciones de terceros a un usuario que no tiene permiso de consulta administrativa.

Convención técnica del filtro fecha: seleccionar reservas cuyo intervalo se solape con el día local `[00:00, 00:00 del día siguiente)`, incluyendo reservas que cruzan medianoche. El filtro de estado usa únicamente el enum oficial. Vista y filtros muestran el estado persistido; nunca simulan FINALIZADA por la hora actual.

### Código estable y estados de fases posteriores

`reservas.codigo` se genera exclusivamente en servidor como **`RES-` seguido de un UUID v4 de 36 caracteres, en minúsculas: 40 caracteres ASCII en total**. Es una convención técnica sin significado institucional, secuencia por facultad ni datos personales. Utilizar aleatoriedad criptográfica, conservar `uq_reservas_codigo` y reintentar con código nuevo solo ante colisión de ese UNIQUE, hasta tres intentos totales; cada intento fallido revierte. No confundir con un token de autenticación ni con un QR. El código es inmutable después de la creación y no se edita por interfaz.

LIBERADA conserva su lugar en el enum y se reserva para CU23/Fase 8 bajo D-08; no hay liberación automática en Fase 4. FINALIZADA también se conserva, pero su mecanismo/disparador queda diferido a una fase posterior autorizada. No crear cron, scheduler ni transición por `ahora >= fin` en Fase 4. Una APROBADA cuyo intervalo terminó puede conservar ese estado; su intervalo no se solapa con un periodo futuro y no lo bloquea. No se cambia el resultado de consultas históricas ni se inventa una transición para limpiar estados.

### Integraciones diferidas y contrato físico

Después del commit de una creación, resolución o cancelación quedará un punto conceptual para avisos y actividad, cuando su fase sea autorizada. Notificaciones pertenecen a Fase 10; `registros_actividad`/CU30 permanecen fuera del alcance actual, sin ser sustituidos por logs técnicos. RF6/RF7 conservan esas obligaciones pendientes y no se declararán íntegramente satisfechos por este cierre documental.

Blockchain/CU24 corresponde a Fase 9, solo después de una operación confirmada que cumpla la condición de crítica: conservar los puntos previstos en CU09/CU10, sin agregar CU07 como operación crítica por inferencia. No crear hashes ficticios, evidencias, outbox ni envíos externos en Fase 4. QR/CU11 se implementará en Fase 6; IoT en Fase 7; liberación en Fase 8. Ninguna de esas integraciones se ejecuta dentro de la transacción de reservas ni se anticipa en este cierre.

Se mantienen las quince columnas, nulabilidades, cuatro FK, PK, UNIQUE, CHECK y tres índices oficiales de `reservas`, descritos en [DATA_MODEL.md](DATA_MODEL.md#reservas). Los índices apoyan las consultas; **no garantizan por sí solos ausencia de intervalos solapados**. Esa garantía corresponde al protocolo de transacción/bloqueo y a sus pruebas. No añadir constraint temporal imposible, tabla de locks ni tabla de asignación de recursos.

Las [pruebas obligatorias de Fase 4](BUSINESS_RULES.md#aceptación-futura-de-reservas--fase-4) incluyen los cinco escenarios de concurrencia exigidos, frontera temporal, RBAC y rollback. Son obligaciones futuras, no resultados ejecutados en Fase 3.5.

## Readiness for Phase 4

**D-05: RESUELTA PARA FASE 4. Bloqueadores documentales restantes para CU07–CU10: 0.**

| Punto de preparación | Contrato cerrado |
| --- | --- |
| PENDIENTE | Solicitud en revisión; admite coexistencia y no concede exclusividad |
| Estados que bloquean | Solo APROBADA, mismo espacio y periodo solapado |
| Estados cancelables | PENDIENTE y APROBADA |
| Transiciones | PENDIENTE→APROBADA/RECHAZADA/CANCELADA; APROBADA→CANCELADA |
| Concurrencia | Transacción, coordinación RBAC existente, lock del espacio antes de la reserva, lecturas actuales y escritura condicional; perdedora de aprobación continúa PENDIENTE |
| Intervalos | [inicio, fin), fin>inicio; contiguos permitidos |
| Consulta por rol | Propia para Docente/Estudiante; administrativa con permiso para Administrador/Encargado; ámbito fino D-04 diferido |
| Cancelación temporal | Hora del servidor estrictamente anterior a inicio; motivo opcional |
| Responsables/código | Actores y fechas del servidor; historia preservada; código RES-UUID v4 único e inmutable |
| Servicio compartido | Ampliar SpaceAvailabilityService en Fase 4; mismo predicado en CU06/CU07/CU09 |
| Fuera de Fase 4 | LIBERADA automática, FINALIZADA automática, QR, IoT, notificaciones, Blockchain y bitácora general |
| Verificación futura | Cinco escenarios obligatorios, dos procesos MySQL reales, fronteras, permisos y rollback |

Puede comenzar Fase 4 mediante **una nueva instrucción del usuario**. Este cierre no ejecuta migraciones, crea permisos ni implementa CU07–CU10. Los diferidos enumerados no impiden implementar el núcleo acordado; tampoco desaparecen del alcance de sus fases posteriores. El cero de bloqueadores certifica preparación documental, no ejecución de pruebas ni funcionamiento de reservas.

## Aplicación y verificación de D-05 — Fase 4

D-05 permanece **RESUELTA PARA FASE 4**. El contrato precedente se materializó sin nueva ambigüedad: SQL exacto, APROBADA exclusiva, pendientes coexistentes, cuatro transiciones, permisos por rol/propiedad, reloj posterior al lock y lecturas actuales. Se ejecutaron los cinco escenarios obligatorios y nueve pruebas multiproceso, incluida espera del espacio independiente de la coordinación RBAC. [Código, resultados y límites](RESERVATIONS_IMPLEMENTATION.md).

El Readiness anterior es histórico: la nueva instrucción de Fase 4 autorizó su ejecución. No cambia D-04 fino ni los diferidos QR/IoT/liberación/FINALIZADA/Blockchain/notificaciones/CU30. Fase 5 continúa sin iniciar.

## Contrato de equipamiento y préstamos — Fase 4.5

**Fecha: 2026-10-23. Cierre exclusivamente documental para CU12–CU19/RF9–RF15. Fase 5 no iniciada.** Esta sección es el contrato vigente para ese alcance. Conserva la resolución de D-04 y D-05, el defecto visual documentado de D-11 y el historial de fases anteriores. No cambia código, esquema ejecutado, permisos en BD ni disponibilidad funcional.

### Evidencia y decisiones de interpretación

Se reconsultaron ambos originales: RF9–RF15, tablas 18–24 (impresas 122–131 / PDF 123–132); CU12–CU19, tablas 49–56 (impresas 191–202 / PDF 192–203); análisis figuras 86–93 (PDF 235–239); clases figura 108 (PDF 249), diseño lógico figura 109 (PDF 250), diccionarios y SQL final de las seis tablas del alcance (PDF 279–287); secuencia CU14, figura 112 (PDF 303). Se revisaron visualmente clases, ERD, análisis, secuencia y figura 23 de entrega. La secuencia confirma la revalidación antes de registrar PENDIENTE. No hay una secuencia final adicional que autorice delegación o confirmación digital posterior del receptor.

Los CREATE TABLE de tipos_equipamiento, equipamientos_tecnologicos, prestamos, entregas_prestamo, devoluciones_prestamo e incidencias_mantenimiento coinciden entre Word/PDF al normalizar espacios y encabezados. Sus columnas, enums, restricciones y acciones FK permanecen intactos en DATA_MODEL. Las huellas SHA-256 de los originales son las registradas al inicio de este documento.

| Cuestión | Evidencia contrastada | Decisión para Fase 5 |
| --- | --- | --- |
| Motivo de rechazo | RF12, tabla 21, paso 5 (PDF 127) exige registrar el motivo; CU16, tabla 53, paso 5.B (PDF 197) dice «cuando corresponda»; SQL permite NULL | La instrucción Fase 4.5 §19 exige resolver esta diferencia considerando RF12: todo rechazo explícito de préstamo requiere motivo no vacío, máximo 500 caracteres. NULL sigue siendo válido antes de resolver y al aprobar. Es una precisión específica de préstamos; no cambia el motivo opcional de reservas de D-05. |
| Receptor diferente | RF13/CU17 exigen receptor identificado/autorizado; FK distintas permiten representar personas diferentes, pero no establecen delegación ni cómo acreditarla | Receptor igual al solicitante; servidor deriva receptor_id del préstamo y verifica identidad física. No crear delegación, selector libre ni permiso de tercero. |
| Confirmación de recepción | Diccionario tabla 117 describe fecha de confirmación; clase EntregaPrestamo contiene confirmarRecepcion(Usuario, DateTime); RF13/CU17 y análisis 91 registran confirmación de entrega por el responsable | La existencia de campo/método no define actor, ruta ni acto digital posterior del receptor. recepcion_confirmada_en queda NULL en Fase 5; no copiar fecha_hora como si hubiera ocurrido una confirmación separada. NULL no invalida la entrega registrada ni bloquea devolución. Cualquier flujo digital adicional requiere definición y autorización futura. |
| Condición indeterminable | Excepción de CU18 (PDF 201), análisis 92 y enums de devolución | PENDIENTE_REVISION, préstamo ENTREGADO y equipo EN_MANTENIMIENTO preventivo (salvo BAJA ya vigente). La excepción prevalece sobre el paso general de finalizar. |
| Incidencia incompatible | CU19 afecta condición/disponibilidad; SQL no tiene bandera de compatibilidad ni estado operativo anterior | Convención conservadora: todos los registros ABIERTA de CU19 son restricciones operativas incompatibles. No inferir compatibilidad de texto libre ni inventar columnas. Restauración explícita y validada, nunca automática por cierre aislado. |

### Equipamiento, ubicación y edición

EquipamientoTecnologico especializa RecursoInstitucional mediante PK+FK `equipamientos_tecnologicos.recurso_id`. Crear padre y subtipo con el mismo ID en una transacción; no existe `equipamientos_tecnologicos.id`, discriminador ni segundo identificador autogenerado. Los nueve campos son recurso_id, tipo_equipamiento_id, espacio_base_id, descripcion, marca, modelo, numero_serie, estado y referencia_ubicacion. Código/nombre institucionales pertenecen al padre; el código es único entre todos los recursos.

`espacio_base_id` admite NULL y significa 0..1 ubicación base, no contención permanente, custodia ni ámbito del Encargado. Ubicación: equipo → espacio base → piso, cuando existe ese espacio. No añadir piso directo, módulo, edificio ni facultad. `numero_serie` es opcional y UNIQUE cuando existe; ausencia/vacío normalizado se guarda NULL, admitiendo varios equipos sin serie. Tipo obligatorio; datos y longitudes respetan DATA_MODEL. Características descriptivas usan campos existentes, sin inventar catálogo/pivot de características de equipo.

Estados exactos del equipo: DISPONIBLE, PRESTADO, EN_MANTENIMIENTO, BAJA. CU12 no es un atajo para falsificar custodia: PRESTADO deriva de entrega real; un alta no puede comenzar PRESTADO sin préstamo. Estado inicial DISPONIBLE por defecto; EN_MANTENIMIENTO/BAJA requieren cambiar_estado. Cambios administrativos no pueden eliminar un préstamo ENTREGADO, una revisión pendiente o una incidencia ABIERTA. Una modificación de código, tipo, base o descripción no reasigna ni elimina préstamos. No hay borrado físico de historia.

### D-02 y D-05 aplicadas al ciclo del préstamo

Estados persistidos únicos: PENDIENTE, APROBADO, RECHAZADO, ENTREGADO, DEVUELTO. No añadir ACTIVO, FINALIZADO, VENCIDO, CANCELADO ni EN_USO. «Activo» describe ENTREGADO; «finalizado» describe DEVUELTO tras retorno confirmado. Vencido es exactamente `estado = ENTREGADO AND ahora_servidor > fin_previsto`; en la igualdad no está vencido. No cambia el enum ni libera el equipo. Con devolución pendiente puede seguir calculándose vencido: CU15 mostrará además PENDIENTE_REVISION, sin ocultarla ni afirmar ausencia de recepción física.

| Origen | Destino | CU y guardas | Escritura atómica |
| --- | --- | --- | --- |
| PENDIENTE | APROBADO | CU16, resolver, disponibilidad vigente completa | resuelto_por_id de sesión; resuelto_en servidor; motivo_rechazo NULL |
| PENDIENTE | RECHAZADO | CU16, resolver, motivo obligatorio | resuelto_por_id y resuelto_en; motivo_rechazo no vacío, hasta 500 |
| APROBADO | ENTREGADO | CU17, registrar_entrega, receptor identificado, equipo compatible | Una entrega, préstamo ENTREGADO y equipo PRESTADO |
| ENTREGADO | DEVUELTO | CU18, registrar_devolucion, condición final determinable | Devolución CONFIRMADA y estado operativo según restricciones |

No hay selector genérico, reapertura, cancelación, salto de pasos ni transición por reloj. Solicitudes PENDIENTE pueden coexistir para el mismo equipo/periodo; no conceden exclusividad. Se adquiere compromiso temporal al aprobar. Un conflicto al aprobar deja la solicitud PENDIENTE y sin nuevos datos de resolución; solo un rechazo explícito la convierte en RECHAZADO. Rechazar no requiere disponibilidad, porque debe poder rechazarse una solicitud incompatible.

CU14 valida motivo obligatorio hasta 500 caracteres, equipo, permiso y periodo; toma solicitante_id/solicitado_en del servidor y revalida disponibilidad dentro de la transacción antes de insertar PENDIENTE con resolución NULL. Equipo, solicitante y periodo no se editan genéricamente después para eludir la decisión.

No existía formato de prestamos.codigo en el cierre anterior: se adopta `PRE-` + UUID v4 canónico en minúsculas (40 caracteres ASCII), generado por servidor, único e inmutable. Se mantiene VARCHAR(60)/uq_prestamos_codigo; reintento acotado de generación ante colisión de ese índice, máximo tres intentos, sin ocultar otros errores. Código no secreto, no credencial ni QR.

Se reutilizan `[inicio, fin)`, `fin > inicio`, solapamiento `inicio_A < fin_B AND inicio_B < fin_A`, America/La_Paz y reloj de servidor. Intervalos adyacentes no colisionan. No se inventan anticipación, duración máxima, prórroga, expiración de aprobación ni ventana horaria adicional de entrega ausentes de RF/CU. El periodo previsto no acredita devolución real; la custodia mantiene sus restricciones aunque el periodo termine.

### Disponibilidad única de equipamiento

Fase 5 creará **EquipmentAvailabilityService**, usado por CU13, CU14 y la aprobación CU16; CU17 reutilizará sus comprobaciones pertinentes excluyendo el propio préstamo aprobado. Un equipo es candidato solo si se cumplen conjuntamente:

1. Estado operativo DISPONIBLE; PRESTADO, EN_MANTENIMIENTO y BAJA excluyen, incluso para consultas de periodos futuros.
2. Periodo válido y filtros satisfechos: tipo, espacio base/piso mediante la relación existente, código/nombre y descripción/marca/modelo cuando se habilite búsqueda textual. Un filtro de estado no convierte recursos inhabilitados en candidatos. El catálogo CU12 puede mostrar otros estados, diferenciándolo de disponibilidad para solicitar.
3. Ningún préstamo APROBADO o ENTREGADO del mismo equipo cuyo intervalo previsto se solape; excluir por ID el préstamo evaluado al aprobar/entregar. PENDIENTE, RECHAZADO y DEVUELTO no bloquean temporalmente.
4. Ninguna incidencia o mantenimiento ABIERTA incompatible. También se comprueba que no exista préstamo ENTREGADO o devolución PENDIENTE_REVISION que contradiga un DISPONIBLE inconsistente; denegar, sin reparar silenciosamente historia.

La consulta es informativa: para un compromiso se exige el periodo completo; una consulta de catálogo sin periodo no promete disponibilidad temporal. La decisión transaccional usa los mismos predicados con lecturas actuales después de adquirir el lock. No duplicar reglas en Vue/controladores. No reservar por estado mostrado antes de confirmar ni liberar por fin_previsto vencido.

### D-11 aplicada a entrega y recepción

CU17 requiere Administrador o Encargado con prestamos.registrar_entrega. El responsable verifica físicamente equipo y receptor igual al solicitante antes de confirmar. Dentro de la transacción y del orden común de locks: releer préstamo APROBADO y equipo; verificar ausencia de entrega existente, equipo DISPONIBLE, disponibilidad/conflictos e incidencias vigentes y correspondencia de la identidad verificada. El servidor deriva entregado_por_id de sesión y receptor_id del préstamo, y registra fecha_hora del servidor.

`condicion_entrega` es descripción breve obligatoria, texto no vacío hasta 255 caracteres; no enum. `observacion` opcional hasta 500. `recepcion_confirmada_en = NULL` conforme a la interpretación anterior. Crear entregas_prestamo, cambiar condicionalmente APROBADO→ENTREGADO y equipo→PRESTADO como una unidad. UNIQUE(prestamo_id) asegura como máximo una entrega; comprobación de estado y escritura condicional evitan reejecutarla. Reintento de una entrega confirmada no crea otra ni modifica su fecha/responsables. Fallo revierte todo.

La verificación física y confirmación del responsable forman CU17; no se inventa firma digital del receptor. QR no se exige, genera ni valida en Fase 5. CU20 se integra condicionalmente en Fase 6. CU24 queda para Fase 9, después del commit de la operación crítica. La corrección de la flecha de figura 23 sigue vigente: identificación inválida nunca autoriza entrega.

### Devolución y continuación de revisión

Solo un préstamo ENTREGADO con su entrega identificable entra a CU18. Administrador/Encargado con prestamos.registrar_devolucion verifica el equipo recibido; recibido_por_id/registrada_en provienen de sesión/reloj. La recepción no se deniega por mantenimiento o BAJA: debe poder registrarse el retorno sin levantar esas restricciones. UNIQUE(prestamo_id) en devoluciones_prestamo impide una segunda devolución.

| Condición recibida | Estado de devolución | Préstamo | Equipamiento y obligaciones |
| --- | --- | --- | --- |
| ADECUADA | CONFIRMADA | DEVUELTO | DISPONIBLE solo si no persisten BAJA, incidencias, otra revisión o custodia incompatible; de lo contrario conservar la restricción. Confirmador y fecha obligatorios. |
| CON_INCIDENCIA | CONFIRMADA | DEVUELTO | Crear o vincular incidencia ABIERTA del mismo equipo mediante devolucion_id; EN_MANTENIMIENTO, preservando BAJA si ya existe. Retorno físico confirmado no significa recurso apto. |
| NO_DETERMINABLE | PENDIENTE_REVISION | Sigue ENTREGADO | EN_MANTENIMIENTO preventivo, salvo BAJA vigente. confirmado_por_id/confirmada_en NULL. No liberar ni finalizar. |

Toda fila de la tabla representa una transacción indivisible junto con sus incidencias/estados derivados. Descripción de condición obligatoria para CON_INCIDENCIA y NO_DETERMINABLE como regla funcional explicable; SQL nullable se conserva. Para ADECUADA puede ser NULL; observacion es opcional. Ambos campos admiten hasta 500 caracteres. En confirmación inmediata, confirmado_por_id es el responsable de sesión y confirmada_en la hora de confirmación, no anterior a registrada_en; puede ser el mismo responsable/instante de recepción.

Para CON_INCIDENCIA se exige incidencias.gestionar además de registrar_devolucion. Se crea el reporte con descripción de anomalía o se vincula uno ABIERTA existente del mismo recurso, aún sin otra devolución vinculada; no se toma una incidencia de otro equipo ni se sobrescribe un vínculo histórico. Fallo del vínculo/reporte revierte la devolución completa. NO_DETERMINABLE no inventa por sí sola un diagnóstico: el estado preventivo y la fila pendiente bastan para bloquear; si se documenta una anomalía adicional se aplica CU19 con su permiso.

La continuación de CU18/CU19 permite que un Administrador/Encargado vigente con prestamos.registrar_devolucion revise y confirme **la misma fila** PENDIENTE_REVISION. No necesita ser el responsable que recibió inicialmente el equipo; debe verificar la condición final y conservar recibido_por_id/registrada_en. Condición final solo ADECUADA o CON_INCIDENCIA; la segunda necesita incidencias.gestionar. Completar confirmado_por_id/confirmada_en, pasar devolución a CONFIRMADA y préstamo ENTREGADO→DEVUELTO, recalculando equipo con las mismas guardas. No cerrar incidentes implícitamente. Una devolución CONFIRMADA es terminal para estas acciones; no crear otra para corregirla. No hay CU nuevo, registro de auditoría alternativo ni historia ficticia de revisiones (CU30 diferido).

### Incidencias, mantenimiento y recuperación

Una sola tabla `incidencias_mantenimiento` se refiere a recursos_institucionales, aplicable a espacios y equipamiento. Tipos exactos INCIDENCIA/MANTENIMIENTO; estados ABIERTA/CERRADA. Reportado_por_id/cerrado_por_id derivan del actor autenticado. Descripción no vacía obligatoria, recurso existente y fechas válidas; devolucion_id opcional solo cuando coincide el equipo de la devolución. No tabla puente, tablas por subtipo ni enlace a devolución de un espacio.

**Alcance temporal conservador:** todo registro ABIERTA de CU19 restringe desde su registro hasta cierre explícito. inicio/fin_previsto describen el periodo de atención; fin_previsto es una previsión, no un alta automática al vencer. Incluso un mantenimiento planificado a futuro no promete disponibilidad mientras figure ABIERTA. Esta convención evita inventar un planificador, una marca de incompatibilidad o una restauración por reloj; debe explicarse en la futura interfaz. Para un fin previsto informado, la aplicación exige fin_previsto > inicio; SQL conserva literalmente su CHECK más permisivo `>=`. NULL significa sin fin previsto. Cierre exige cerrada_en >= inicio; no se falsea una fecha futura para cerrar antes de inicio. Estas guardas no alteran el SQL ni los intervalos de D-05.

Apertura CU19 identifica préstamos/reservas activos o futuros afectados y presenta el impacto permitido al actor, sin cancelarlos ni rechazarlos automáticamente. Equipo pasa EN_MANTENIMIENTO salvo BAJA ya vigente o decisión administrativa explícita de BAJA. Espacio pasa EN_MANTENIMIENTO o BLOQUEADO según condición confirmada por el responsable, sin valor NO_DISPONIBLE. La ausencia de reservas no habilita un recurso con restricción. En Fase 5 se ampliará **SpaceAvailabilityService** para excluir incidencias ABIERTA en CU06/CU07/CU09, conservando estados, reservas y bloqueos actuales; todos los escritores de estado de CU05 deberán respetar esta guarda. No se modifica ese servicio en Fase 4.5.

Cerrar solo ABIERTA: registrar CERRADA, cerrado_por_id, cerrada_en y solucion no vacía que describa solución o justificación del cierre; conservar la fila. La obligatoriedad funcional no cambia la nulabilidad previa al cierre. Verificar bajo lock otras incidencias/mantenimientos ABIERTA, devoluciones pendientes, préstamos ENTREGADO, bloqueos y estado administrativo. Se puede cerrar un problema resuelto sin habilitar todavía el recurso por otro impedimento.

**Prioridad de recuperación de equipos:** BAJA se conserva; si no, otra ABIERTA o PENDIENTE_REVISION mantiene EN_MANTENIMIENTO; si no, un préstamo ENTREGADO mantiene PRESTADO; si no, puede restituirse DISPONIBLE tras confirmación autorizada de aptitud. Un préstamo APROBADO futuro no obliga al enum PRESTADO, pero sigue excluyendo su periodo en el servicio. Cerrar una incidencia no confirma una devolución pendiente. La revisión pendiente prevalece sobre PRESTADO porque se recibió físicamente el equipo sin certificar todavía su condición.

**Estado administrativo no reconstruible:** SQL no guarda el estado previo ni la causa de cada cambio. Por ello el cierre no ejecuta una restauración automática. Sin una confirmación explícita de aptitud/estado por actor con permiso de cambiar_estado se conserva el estado restrictivo. Con ella se aplica la prioridad anterior. No añadir previous_state, tabla de causas ni inferir que todo EN_MANTENIMIENTO pertenecía únicamente a la incidencia que se cierra. Esta misma guarda aplica a retorno ADECUADA: si el estado previo era PRESTADO, la confirmación permite DISPONIBLE cuando cesó la custodia y no hay otras restricciones. Si era EN_MANTENIMIENTO, la confirmación del retorno por sí sola no acredita que cesaron todas las causas: para habilitar se exige además equipamientos.cambiar_estado y confirmación explícita de aptitud tras revisar las restricciones; en su ausencia se conserva EN_MANTENIMIENTO. BAJA siempre se conserva. Así la revisión pendiente puede confirmarse sin forzar una habilitación ni inventar la causa anterior.

Para espacios: otra ABIERTA mantiene su restricción. Sin ellas, se conserva BLOQUEADO/EN_MANTENIMIENTO hasta confirmación expresa del responsable con espacios.cambiar_estado de que cesó también cualquier causa administrativa. No restituir DISPONIBLE mientras haya bloqueo activo aplicable al instante de restauración. Los bloqueos activos futuros y reservas APROBADA siguen excluyendo sus periodos en SpaceAvailabilityService aunque el estado operativo pueda ser DISPONIBLE ahora; nunca se eliminan para restaurar. Un cierre puede completarse dejando el espacio restringido. La nueva incidencia concurrente se coordina con esa misma comprobación.

BAJA representa retiro operativo y excluye solicitud, aprobación y entrega. Solo Administrador/Encargado con equipamientos.cambiar_estado puede decidir entrada o salida de BAJA. No se aplica automáticamente por una incidencia ni se levanta por cierre/retorno. Entrar BAJA no borra compromisos; debe mostrar su impacto y permitir recibir equipos ya entregados. Salir requiere decisión explícita y revalidación: con incidencias/revisión conserva EN_MANTENIMIENTO; con custodia ENTREGADO corresponde PRESTADO; solo libre de esas causas puede quedar DISPONIBLE. Ningún cambio administrativo suplanta entregar/devolver.

### D-04 aplicada y catálogo previsto

Se mantiene RBAC por módulo/acción y propiedad autorizado en fases anteriores y aplicado por la petición Fase 4.5 §§47–49. Ámbito fino por recurso continúa diferido; no se inventa relación encargado–equipo/espacio, ni se interpreta espacio_base_id como autorización. No se reabre D-04 ni se exige esa asignación inexistente para bloquear el núcleo autorizado. Administrador/Encargado acceden al módulo administrativo solo con permiso explícito; Docente/Estudiante consultan solicitudes propias por solicitante_id. Receptor y demás participantes no adquieren facultad de resolver, entregar o confirmar devoluciones.

Catálogo **previsto, exclusivamente documental**, aún no creado en PermissionCatalog ni seeders ni BD. A = Administrador; E = Encargado de Laboratorio; D = Docente; S = Estudiante. Cada permiso debe proceder de un mismo rol vigente elegible; cuenta/sesión válidas, sin bypass de A, roles nuevos ni comodines.

| Código previsto | Roles elegibles | RF / CU | Límite |
| --- | --- | --- | --- |
| equipamientos.ver | A, E, D, S | RF9/10; CU12/13 | Catálogo/detalle permitido; no datos privados de otros préstamos |
| equipamientos.gestionar | A, E | RF9; CU12 | Alta/edición de padre, equipo y tipos; no borrar historia ni alterar custodia |
| equipamientos.cambiar_estado | A, E | RF9/15; CU12/19 | Estado administrativo, incluido BAJA; guardas de custodia/incidencias/revisión |
| prestamos.ver_propios | D, S | RF11–14; CU15 | solicitante_id = sesión, sin acceso por ID ajeno |
| prestamos.ver_administrativos | A, E | RF11–14; CU15/16–18 | Consulta administrativa por módulo/acción; ámbito fino diferido |
| prestamos.solicitar | D, S | RF11; CU14 | Solicitante desde sesión; revalidación y PENDIENTE |
| prestamos.resolver | A, E | RF12; CU16 | Solo PENDIENTE; motivo obligatorio al rechazar |
| prestamos.registrar_entrega | A, E | RF13; CU17 | APROBADO, entrega única y receptor = solicitante |
| prestamos.registrar_devolucion | A, E | RF14; CU18 | ENTREGADO; recibir y confirmar revisión existente |
| incidencias.ver | A, E | RF15; CU19 | Información administrativa del recurso; no consulta libre por solicitantes |
| incidencias.gestionar | A, E | RF15; CU19/CU18 | Apertura/datos de atención y enlace compatible con devolución |
| incidencias.cerrar | A, E | RF15; CU19 | Cierre una sola vez; no equivale a permiso de habilitar o dar de baja |

CU19 exige además el permiso de cambio de estado del subtipo cuando modifica/restaura su estado (equipamientos.cambiar_estado o espacios.cambiar_estado ya existente). CU18 con anomalía requiere incidencias.gestionar; los cambios derivados obligatorios de entrega/devolución y EN_MANTENIMIENTO preventivo están cubiertos por su permiso de operación y no permiten estados arbitrarios. Retirar un permiso debe denegar la siguiente solicitud, también después de esperar un lock. Ser A/E sin rol D/S habilitado no concede solicitar. Los listados no conceden sus acciones; las Policies vuelven a comprobar cada objeto.

### Transacciones y orden único de locks

El protocolo toma como base el código existente: AdministrationService/SpaceManagementService/ReservationService coordinan cambios sobre la fila de Rol Administrador; SpaceManagementService::save bloquea subtipo antes que padre. Esa fila coordina RBAC, no concede privilegios. Fase 4.5 no cambia esos servicios.

Jerarquía única para Fase 5, omitiendo únicamente niveles no afectados; conservar la solicitud objetivo antes de sus conflictos, como exige el contrato vigente de reservas:

1. Transacción InnoDB → fila de Rol Administrador usada por la coordinación RBAC → reautorización con identidad/permisos vigentes. No adquirir después otro lock de usuario en orden inverso al protocolo de seguridad; cualquier escritor de permisos debe conservar la coordinación común.
2. Filas de subtipo de los recursos afectados (equipo o espacio), por recurso_id ascendente global; luego filas padre recursos_institucionales por id ascendente si se necesitan. Nunca bloquear incidencia/préstamo antes del recurso ni padre antes del subtipo existente. Al crear equipo nuevo no existe fila que bloquear: después de RBAC insertar padre y subtipo atómicamente; UNIQUE coordina identificadores.
3. Solicitud objetivo, si existe: reserva o préstamo por PK, revalidando su relación con el recurso. Después, dependencias/conflictos en orden bloqueos_espacio → otras reservas → otros prestamos, por ID ascendente en cada conjunto. La fila objetivo ya bloqueada se excluye de conflictos. Es el mismo orden lógico para todos los servicios; si no hay solicitud objetivo se omite ese nivel. El recurso coordinador adquirido antes impide que otro escritor bloquee un objetivo distinto del mismo recurso y cause una inversión entre solicitudes.
4. entregas_prestamo → devoluciones_prestamo, por PK ascendente; después incidencias_mantenimiento por PK ascendente. Si una incidencia enlaza devolución, localizar IDs previamente y comprobar otra vez las relaciones bajo locks. No adquirir primero la incidencia y luego remontar al préstamo/recurso.
5. Tras adquirir todos los locks necesarios, revalidar guardas/estado, escribir transición condicional cuyo origen todavía coincida y exigir exactamente una fila afectada; commit o rollback total. Una fila insertada en esta transacción no necesita un lock previo imposible; se mantiene bloqueado su recurso coordinador antes de consultar ausencia o crear hijos.

Las lecturas previas solo localizan IDs; no autorizan ni congelan el resultado. Bajo REPEATABLE READ no usar una lectura de snapshot anterior al tiempo de espera para decidir. Leer explícitamente conflictos actuales con FOR UPDATE después de bloquear el recurso; un FOR UPDATE exterior no convierte automáticamente subconsultas NOT EXISTS en lecturas actuales. No lanzar esos locks desde una comprobación tardía que invierta los niveles ya adquiridos. El servicio central debe separar adquisición de filas y evaluación compartida de predicados. Se conserva expresamente el orden de ReservationService: RBAC → espacio → reserva objetivo → bloqueos/conflictos consultados por SpaceAvailabilityService. Fase 5 añadirá las incidencias al final de esa adquisición y aplicará el mismo protocolo a préstamos. No se invierte ni reabre el contrato de D-05; la integración requiere regresión T-P15 y de Fase 4.

Todos los escritores que afectan disponibilidad respetarán este protocolo: CU12, CU14, CU16–CU19 y modificaciones de estado/bloqueos de espacios CU05, además de CU07/CU09/CU10 al integrar CU19. La fila del recurso evita la carrera incluso cuando no existen préstamos/incidencias que bloquear. No tabla de locks ni exclusión por navegador. Mantener reintentos de transacción acotados (tres, como el código actual) para deadlock; no confundir conflicto de negocio con error reintentable. La coordinación RBAC global serializa recursos distintos: limitación conocida, sin prometer escalabilidad medida ni cambiarla en esta fase.

Operaciones atómicas obligatorias: padre+equipo; edición/estado administrativo; solicitud; aprobación; rechazo; entrega+hito+estados; devolución+hito+estados; devolución con incidencia/vínculo; confirmación de revisión existente; apertura/cierre de incidencia y sus cambios derivados. Ninguna llamada remota, interacción humana ni espera de QR/Blockchain dentro de la transacción. Formularios/inspección física preceden a la confirmación; el servidor vuelve a validar al recibirla.

### Integraciones diferidas y límites de aceptación

CU20: extensión QR en Fase 6; entrega/devolución de Fase 5 funcionan sin QR, tablas/códigos/validaciones ni obligación simulada. En este cierre de Fase 4.5, D-09 permanecía pendiente para esa integración; el contrato de Fase 5.5 siguiente la resuelve documentalmente. CU24: evidencia de operaciones críticas después del commit principal, Fase 9; no generar hashes/evidencias ni prometer auditoría Blockchain ahora. RF21/CU26: Fase 10, sin notificaciones temporales; solo confirmación/error de la petición en la interfaz. CU30: fase posterior autorizada, sin registros_actividad ni tabla alternativa en Fase 5. Campos de responsabilidad/fechas y conservación de filas no equivalen a bitácora completa.

Pruebas futuras obligatorias, con IDs T-P01–T-P18, están en [BUSINESS_RULES.md](BUSINESS_RULES.md#aceptación-futura-de-equipamiento-y-préstamos--fase-5). Deben ejecutarse en MySQL real aislado al implementar Fase 5; no se presentan como ejecutadas en este cierre. La regresión de reservas deberá comprobar además la integración CU19 sin reabrir D-05.

## Readiness for Phase 5

| Puerta documental | Estado al cerrar Fase 4.5 |
| --- | --- |
| D-02 | RESUELTA PARA FASE 5: enums exactos, cuatro transiciones, vencido calculado y retorno/revisión definidos |
| D-04 | Aplicación confirmada: módulo/acción y propiedad; mismo rol elegible con permiso; ámbito fino diferido sin entidades inventadas |
| D-05 | Principios reutilizados sin reapertura: [inicio, fin), pendientes sin exclusividad, APROBADO/ENTREGADO bloqueantes y revalidación actual bajo lock |
| D-11 | RESUELTA PARA FASE 5: texto CU17 prevalece; receptor igual al solicitante; entrega única/atómica; recepcion_confirmada_en NULL |
| Catálogo y modelo | PK compartida, nueve campos de equipo, ubicación opcional, serie opcional única, PRE-UUIDv4 y SQL sin cambios |
| Disponibilidad | EquipmentAvailabilityService único previsto; condiciones operativas/temporales/incidencias; CU19 extenderá SpaceAvailabilityService |
| Devolución | ADECUADA/CON_INCIDENCIA confirmadas; NO_DETERMINABLE pendiente; confirmación posterior sobre la misma fila con actor/fecha |
| Incidencias y restauración | Recurso común, ABIERTA/CERRADA, prioridad de restricciones, BAJA protegida y habilitación explícita validada |
| Concurrencia | Aprobaciones incompatibles: una gana y otra queda PENDIENTE; entrega/devolución únicas; restauración serializada con apertura |
| Locking y atomicidad | RBAC → subtipos → padres necesarios → solicitud objetivo → bloqueos/otras reservas/otros prestamos → entregas → devoluciones → incidencias; relacionados por PK |
| Permisos | Doce códigos previstos, matriz y guardas definidos; ninguno creado en código/BD |
| Integraciones diferidas | QR 6; Blockchain 9; notificaciones 10; CU30 posterior. No bloquean el núcleo delimitado de Fase 5 |
| Implementación/pruebas de Fase 5 | Pendientes, requieren nueva instrucción; no son bloqueantes documentales ni evidencia funcional existente |
| Bloqueantes documentales restantes para el alcance autorizado de Fase 5 | **0** |

Fase 5 puede comenzar cuando el usuario la instruya. Este cierre no la inicia ni declara funcionales CU12–CU19. D-04 fino, D-09, D-12 y demás integraciones conservan sus límites futuros expresos.

## Aplicación de decisiones en Fase 5

D-02 y D-11 se implementaron conforme al cierre de Fase 4.5; D-04 por módulo/acción/propiedad y D-05 por intervalos, exclusividad y lecturas actuales se aplicaron sin reabrirse. [Evidencia de implementación, T-P01–18 y concurrencia](EQUIPMENT_LOANS_IMPLEMENTATION.md). Se conservan el SQL final y las resoluciones documentales. Al cerrar Fase 5, D-09 permanecía pendiente; el cierre documental de Fase 5.5 siguiente la resuelve para Fase 6, todavía no iniciada. Ámbito fino, D-12 y demás detalles posteriores mantienen su límite original.

## Contrato QR y validaciones — cierre D-09 de Fase 5.5

**D-09: RESUELTA PARA FASE 6.** Fecha: 2026-10-23. Alcance exclusivamente documental autorizado por la petición de Fase 5.5. No se crean migraciones, modelos, enums PHP, permisos en BD, dependencias, endpoints, cámara ni integración funcional. La matriz, algoritmo y pruebas siguientes son el contrato obligatorio de la futura Fase 6; no resultados de software ejecutado.

### Fuentes recontrastadas y precedencia

| Fuente Word/PDF | Referencia comprobada | Consecuencia para el contrato |
| --- | --- | --- |
| RF8 | Tabla 17, p. 121 / PDF 122 | Identidad, permiso, recurso y reserva; cámara denegada no valida |
| CU11 | Tabla 48, p. 190 / PDF 191; análisis figura 85, PDF 234 | Validación contextual del uso, responsable y fecha/hora |
| CU17 | Tabla 54, p. 198 / PDF 199; análisis figura 91, PDF 237 | QR condicional; entrega sigue en su servicio principal |
| CU18 | Tabla 55, pp. 199–200 / PDF 200–201; análisis figura 92, PDF 238 | Identificación, condición física y revisión; QR inválido no confirma la rama QR |
| CU20 | Tabla 57, p. 203 / PDF 204; análisis figura 94, PDF 239 | Compara recurso/préstamo/operación; devuelve resultado al flujo principal |
| Clases y BD lógica | Figuras 108–109, PDF 249–250 | CodigoQR y ValidacionQR separados; recurso común y vínculos de contexto |
| SQL final | codigos_qr y validaciones_qr, PDF 288–292 | Objetivo exclusivo, contexto obligatorio, código reconocido nullable y enlaces a hitos UNIQUE |
| Secuencia CU11 | Figura 111, p. 301 / PDF 302 | Cámara fuera de transacción, comprobación servidor y persistencia del resultado |

Se revisaron texto y diagramas relevantes; ambos CREATE TABLE son equivalentes entre Word/PDF al retirar encabezados, comentarios y espacios. No se modifican originales ni SQL. La frase narrativa «actualiza el estado operativo» de RF8/CU11/figura 111 no permite inventar estados de reserva: conforme a la petición §21 y a los enums oficiales, CU11 registra evidencia, sin transición de reserva/espacio. La frontera móvil dibujada no introduce una aplicación móvil en Fase 6 web. Las precisiones de token, ciclo, correlación y permisos que siguen son convenciones de implementación autorizadas, no números o mecanismos atribuidos al informe.

### Identidad, payload y hash

**Un código QR NO autentica al usuario y NO autoriza por sí solo una operación.** Identifica recurso u operación y correlaciona el contexto físico con el lógico. Una imagen copiada tampoco prueba presencia física por sí sola; se conservan identidad, permisos y comprobaciones humanas del CU.

Convención exacta: generar 32 bytes con CSPRNG (`random_bytes(32)`), codificarlos en base64url sin padding. El token tiene **43 caracteres ASCII**, distingue mayúsculas/minúsculas y es el payload completo. No URI, JSON, prefijo, ID de BD, código RES-/PRE-, serie ni datos personales. El token no tiene significado institucional. No se crean tokens reales en esta fase documental.

Normalización única N(s): conservar exactamente el string UTF-8 recibido; no recortar espacios, convertir mayúsculas, normalizar Unicode, quitar padding, interpretar URLs ni decodificar porcentajes. Un token canónico debe cumplir 43 caracteres `[A-Za-z0-9_-]`, decodificar estrictamente a 32 bytes y recodificar exactamente al mismo string. Esto rechaza representaciones base64 no canónicas con bits sobrantes. Excluir el campo del trimming/normalización automática de formularios en la implementación futura.

H(s) = **SHA-256 de los bytes UTF-8 exactos de N(s), representado como 64 caracteres hexadecimales minúsculos**. Para el token canónico esos bytes son ASCII. `token_hash = H(token)` al emitir; `hash_leido = H(payload)` en cada intento registrado. Se utiliza el mismo procedimiento al buscar token_hash, sin sal ni algoritmo de contraseña: la entrada ya tiene 256 bits aleatorios. Los VARCHAR(255) originales se conservan.

Solo el hash se persiste. Token e imagen existen transitoriamente en memoria durante generación/presentación y lectura/request. No guardar originales en archivos, BD, sesión, caché, localStorage/sessionStorage/IndexedDB, historial Inertia, logs, excepciones ni analytics. Generación/renderizado local, respuesta privada `no-store`, fuera de props persistibles de Inertia, y eliminación de buffers/URLs de objeto al cerrar o navegar. La etiqueta física autorizada es el soporte de instalación previsto en IH-03; no implica que el servidor conserve una imagen o un token recuperable. Sin el original en memoria no se puede volver a mostrar el mismo QR a partir del hash: debe rotarse. No ofrecer descarga digital persistente automática ni reconstrucción ficticia.

### Objetivos, emisión y ciclo de vida

Cada fila de codigos_qr tiene exactamente uno de recurso_id/reserva_id/prestamo_id no NULL. El tipo conceptual se deriva de esa FK; no se añade tipo_qr. Recurso es RecursoInstitucional con subtipo espacio o equipo, sin FKs paralelas. El QR de recurso es el mecanismo físico principal. Los QR de reserva/préstamo se emiten explícitamente cuando se necesitan, sin generación automática al solicitar/aprobar.

Gestión prevista: consultar metadatos autorizados, emitir, desactivar y rotar; no CRUD genérico ni edición de objetivo/hash/historia. Administrador/Encargado con qr.gestionar y acceso de consulta al objetivo pueden operar sobre los tres tipos. Recurso exige espacios.ver o equipamientos.ver; reserva exige reservas.ver_administrativas; préstamo exige prestamos.ver_administrativos. Ningún solicitante adquiere emisión por ser propietario. La emisión requiere objetivo existente/coherente, pero no habilita su uso: incluso un código emitido preparatoriamente para una operación no apta falla en validación hasta cumplir el CU.

Vigencia: activo=true **y** generada_en <= ahora_servidor **y** (vence_en IS NULL **o** ahora_servidor < vence_en), zona America/La_Paz. Intervalo `[generada_en, vence_en)`; a igualdad de vencimiento es inválido. Default de emisión: sin vencimiento (NULL), particularmente apropiado para etiqueta física; fecha opcional explícita del gestor, sin duración implícita ni derivación automática del fin del préstamo/reserva. Si se indica, la aplicación exige vence_en > generada_en; SQL conserva su CHECK >=. Una fila con igualdad permitida por SQL tiene ventana vacía y nunca es utilizable. NULL no omite activo ni las reglas temporales de CU11.

Emitir/rotar desactiva, dentro de la misma transacción, **todos** los códigos activo=true del mismo objetivo exacto (también expirados) e inserta otro con token nuevo. Puede quedar como máximo un activo por objetivo; recurso y su reserva/préstamo son objetivos diferentes y pueden tener uno cada uno. La fila estable del objetivo/recurso coordina incluso cuando no existe QR anterior. No se agrega UNIQUE parcial ni columna. Conservar códigos y validaciones anteriores; un token revocado no se reactiva ni reutiliza. Desactivar es idempotente y no borra. Cambiar objetivo/vigencia requiere rotación. Una colisión de uq_codigo_qr_token_hash revierte el intento completo y permite hasta tres intentos con token nuevo; otros errores no provocan regeneración indiscriminada.

El QR **no es de un solo uso**. No se desactiva al validar, entregar o devolver; cada intento admitido produce su fila. El estado terminal de la operación impide usos incompatibles aunque el código siga activo. No se crea un cron de vencimiento.

### Admisión, código desconocido y evidencia de rechazo

El escaneo de Fase 6 es contextual: se inicia desde una reserva o desde la entrega/devolución de un préstamo seleccionado mediante consulta autorizada. Sus IDs son localizadores, nunca autoridad. El servidor recupera el contexto, deriva recurso_esperado_id y tipo_operacion del flujo y fija validado_por_id desde sesión y fecha_hora desde su reloj. No toma esos campos, resultado, hash_leido, codigo_qr_id ni los enlaces de hitos libremente del cliente. No existe validación genérica sin reserva/préstamo que luego se pueda aplicar a cualquier operación.

Antes de admitir intento: sesión vigente, usuario ACTIVO, CSRF, rol/permiso y propiedad/acceso al contexto. Fallo de autenticación/autorización, contexto inexistente o cuerpo ilegible se rechaza sin fabricar fila ni revelar existencia ajena. El contexto inexistente no permite satisfacer recurso_esperado_id/FK/CHECK y no se inventa uno. La revocación durante espera se vuelve a evaluar: denegación sin mutación de dominio, sin forzar evidencia QR a través de una autorización fallida. Esto no implementa CU30.

Límite técnico de entrada: string UTF-8 de 1 a 1024 bytes; ausencia, tipo incorrecto o exceso de tamaño se rechaza antes del intento (422/413), sin truncar ni guardar contenido. Un string admitido pero no canónico se registra INVALIDA con H(s), codigo_qr_id=NULL y motivo «Formato de código no admitido». El mismo H se aplica sin transformaciones encubiertas. Si es canónico, buscar por token_hash; coincidencia guarda codigo_qr_id incluso si luego es inactivo/vencido/ajeno; ausencia guarda NULL, INVALIDA y «Código no registrado». No crear CodigoQR ficticio ni aceptar hash/ID aportado por el cliente como token.

Un intento admitido inválido conserva actor, contexto, recurso esperado, reloj, hash y motivo seguro <=500; ambos enlaces entrega_id/devolucion_id quedan NULL. No cambia reserva, préstamo, espacio, equipo, entrega ni devolución. Motivos deterministas según primera comprobación fallida: formato/no registrado, inactivo, fuera de vigencia, objetivo/recurso/contexto incorrecto, estado/periodo/restricción incompatible. No incluir el token, hashes, SQL ni identidad/datos del objetivo ajeno en la respuesta. VALIDA deja motivo_rechazo=NULL. Un fallo de persistencia no permite mostrar éxito ni continuar el flujo QR.

### Contextos y correspondencia exactos

| Operación | Contexto obligatorio del intento | QR aceptable | Enlaces de hitos |
| --- | --- | --- | --- |
| USO_ESPACIO / CU11 | reserva_id; recurso_esperado_id=Reserva.espacio_id; prestamo_id=NULL | QR del mismo espacio o de esa reserva exacta; nunca QR de préstamo | entrega_id=NULL, devolucion_id=NULL siempre |
| ENTREGA / CU20→CU17 | prestamo_id; recurso_esperado_id=Prestamo.equipamiento_id; reserva_id=NULL | QR del mismo equipo o de ese préstamo exacto; nunca QR de reserva | entrega_id inicialmente NULL y se enlaza al hito creado; devolucion_id=NULL |
| DEVOLUCION / CU20→CU18 | prestamo_id; recurso_esperado_id=Prestamo.equipamiento_id; reserva_id=NULL | QR del mismo equipo o de ese préstamo exacto; nunca QR de reserva | devolucion_id inicialmente NULL y se enlaza al hito creado; entrega_id=NULL |

Una operación distinta sobre el mismo recurso no satisface la igualdad del objetivo de un QR de operación. Comparar también las relaciones actuales al cargar reserva/préstamo/hito; no basta que todos los IDs existan. Mantener exactamente chk_qr_objetivo, chk_qr_vigencia, chk_validacion_contexto, chk_validacion_entrega_devolucion y ambos UNIQUE nullable de hitos.

### CU11 y autorización contextual

Solo reserva **APROBADA** y reloj dentro de `[reserva.inicio, reserva.fin)`. PENDIENTE, RECHAZADA, CANCELADA, LIBERADA y FINALIZADA son incompatibles. No anticipos, tolerancias, estado EN_USO/UTILIZADA/CONFIRMADA ni actualización de reserva/espacio por escaneo. Se registra únicamente ValidacionQR. Puede haber varias válidas del mismo QR y reserva dentro del periodo.

Docente/Estudiante requieren qr.validar_uso_espacio y reserva propia, con acceso por reservas.ver_propias. Administrador/Encargado requieren qr.validar_uso_espacio y reservas.ver_administrativas. El permiso administrativo no se infiere del nombre del rol. Se aplica D-04 por módulo/acción y propiedad, sin asociación fina nueva.

El QR no sustituye controles operativos. Como integración conservadora con Fases 4/5, se reutiliza SpaceAvailabilityService para revalidar el intervalo de la reserva, excluyendo de conflictos **la propia reserva** mediante su ID; siguen aplicándose estado del espacio, bloqueos, otras APROBADAS e incidencias abiertas. No crear otra fórmula de disponibilidad ni eliminar restricciones. El resultado solo acredita validación contextual, no ocupación medida ni activación de una política. Fase 8 podrá consumir esta evidencia mediante las relaciones ya previstas, sin ejecutar liberación en Fase 6.

### CU20, continuidad de CU17/CU18 y revisión

CU20 es una **extensión disponible y explícita**, no requisito global de todas las entregas/devoluciones. No existe configuración universal requiere_qr en préstamos: no se agrega tabla/columna ni se usa politicas_liberacion.requiere_qr (reservas, Fase 8). La futura parametrización institucional de obligatoriedad queda diferida y no bloquea este alcance delimitado.

Invocar la rama QR obliga a obtener VALIDA dentro de ella; un resultado inválido no llama automáticamente a la ruta sin QR ni omite el token para reintentar. El núcleo de Fase 5 sin QR permanece como flujo separado autorizado, sin prometer una política global que no existe. Volver a él es una elección explícita nueva del responsable, nunca fallback silencioso.

ENTREGA: préstamo APROBADO, sin entrega, mismo equipo/receptor solicitante, actor con qr.validar_operacion y prestamos.registrar_entrega, más todas las guardas vigentes de CU17 (estado compatible, disponibilidad, incidencias y confirmación física). Un escaneo no confirma identidad del receptor, condición del equipo ni recepcion_confirmada_en; este último continúa NULL.

DEVOLUCION: préstamo ENTREGADO, entrega válida, ninguna devolución previa, mismo equipo, actor con qr.validar_operacion y prestamos.registrar_devolucion. No exigir equipo DISPONIBLE/PRESTADO como única condición: EN_MANTENIMIENTO/BAJA o incidencia no impiden registrar una devolución física identificada. Se preservan las tres condiciones, permisos adicionales para crear incidencia/recuperar estado y restricciones de Fase 5. NO_DETERMINABLE crea devolución pendiente y puede quedar correlacionada con una validación VALIDA: validez de identidad no es confirmación de condición. La revisión posterior actualiza ese mismo hito con su autorización propia, conserva el enlace previo y no requiere ni agrega otro QR a la recepción ya registrada.

### Correlación y persistencia atómicas

**Contrato elegido para Fase 6: una sola petición de confirmación con token leído en memoria y datos del flujo principal.** La lectura de cámara precede a la petición; «leído» en frontend nunca se presenta como «VALIDA». Para cumplir el tiempo de respuesta sin una pausa humana después de leer, la pantalla recoge primero datos, verificaciones físicas y la intención explícita «escanear y confirmar»; informa que la lectura enviará esa operación. Al decodificar envía inmediatamente el request. La confirmación persistente sigue ocurriendo únicamente después de validar en servidor; no se inicia una entrega/devolución por escanear fuera de ese flujo controlado. No hay endpoint de prevalidación que emita una autorización reutilizable, ticket, nonce persistente ni ventana de 5/10/30 minutos. No se acepta validacion_id histórica enviada por cliente para ejecutar otra operación. La frescura se acredita porque el servidor crea y usa la validación en esa petición, con actor/contexto/estado/reloj revalidados en ese momento.

1. Antes de adquirir locks, comprobar estructura de entrada, condición/motivo y campos requeridos; luego coordinar autorización y filas actuales según el orden siguiente. Si el QR explícito falta, rechazar sin ejecutar el núcleo.
2. Dentro de transacción breve, derivar contexto, volver a autorizar y comprobar token, vigencia, relaciones y precondiciones actuales. Si hay invalidez de dominio/QR de un intento admitido, insertar INVALIDA con enlaces NULL y **confirmar solo ese resultado**; devolver conflicto/explicación después del commit. No lanzar una excepción que deshaga accidentalmente ese rechazo registrado.
3. Si todo es compatible, insertar VALIDA con entrega_id/devolucion_id inicialmente NULL. El coordinador continúa el servicio principal CU17/CU18, que es el único dueño de sus transiciones; el servicio QR no entrega, devuelve ni habilita recursos por sí mismo.
4. Tras crear el hito, actualizar esa misma ValidacionQR con su entrega_id o devolucion_id, verificando mismo actor/préstamo/equipo/tipo y enlace aún NULL. Commit único de validación, hito, relación y estados. No hay segunda validación para asociar el hito ni estado intermedio confirmado separadamente.
5. Un fallo técnico durante hito/enlace/commit revierte el conjunto completo, incluida la VALIDA tentativa. No mostrar validación/operación confirmada; informar fallo seguro y permitir nueva lectura/reintento explícito. No guardar una falsa INVALIDA por error de infraestructura. Los rechazos anteriores ya confirmados permanecen intactos.

No se identifica al actor por el token: validado_por_id es el mismo usuario autenticado que confirma CU17/CU18. No se transfiere una validación entre responsables o sesiones. CU11 usa una petición corta que confirma únicamente el resultado. Si una futura arquitectura necesitara requests separados y reutilización de una validación persistida, deberá revisar expresamente este contrato; no es una opción abierta para Fase 6.

Reintento tras respuesta perdida: reconsultar primero el estado autorizado; no recrear el hito ni afirmar una nueva confirmación. Si se reenvía la operación ya completada, sus guardas impiden duplicado; un nuevo intento QR admitido incompatible se registra INVALIDA con enlaces NULL. UNIQUE(entrega_id) y UNIQUE(devolucion_id) permiten máximo una asociación por hito y múltiples intentos previos con NULL; codigo_qr_id no es UNIQUE. Una validación enlazada no se reasigna, y una INVALIDA nunca se convierte retrospectivamente en VALIDA.

### Orden de locks y concurrencia futura

Todas las escrituras QR se incorporarán al protocolo existente: coordinación RBAC → subtipo de recurso por recurso_id → padre si es necesario → reserva/préstamo objetivo → bloqueos y otros compromisos pertinentes → entregas → devoluciones → incidencias → codigos_qr → validaciones_qr. Filas de cada grupo en PK ascendente; lecturas actuales FOR UPDATE después de esperar, no snapshots previos. Contextos/IDs localizados antes de bloquear se vuelven a comprobar bajo lock. No se adquiere otro recurso porque el QR sea ajeno: se registra la incompatibilidad del contexto esperado.

Emisión/rotación/desactivación bloquean primero el recurso derivado del objetivo y, para objetivo de operación, su reserva/préstamo; después los QR del objetivo. Esto serializa ausencia, dos rotaciones y validación/desactivación sin índices nuevos. Desactivación por id verifica pertenencia al objetivo. El coordinador de CU20 debe compartir el contexto ya bloqueado con LoanService, sin adquirir dependencias de grupos anteriores después de QR ni duplicar reglas de negocio. La futura refactorización se limita a esa integración y conserva regresión de Fase 5.

Una vez adquiridos los locks, el reloj de decisión se obtiene en servidor y se usa para comprobar vigencia/horario y registrar fecha_hora. Si vence entre lectura física y validación, es INVALIDA. Un commit posterior no reescribe el resultado histórico calculado al instante de decisión. Si desactivación/rotación confirma primero, la lectura actual rechaza el QR anterior; si validación confirma primero, se conserva su evidencia válida y la revocación afecta intentos posteriores. Ningún cambio puede intercalarse entre validación y hito dentro de la transacción común.

Dos confirmaciones sobre la misma entrega/devolución: solo una crea y enlaza el hito; la otra revalida el estado y no duplica ni reemplaza el vínculo. Dos rotaciones pueden confirmar serialmente: queda activo solo el último código y ambas filas conservan historia. Mantener los tres reintentos acotados de deadlock existentes, rollback completo y error seguro si se agotan; no prometer escalabilidad por recurso mientras exista coordinación RBAC global.

Nunca retener transacción/row lock durante permiso de cámara, encuadre, lectura, confirmación humana o llamada externa. El tiempo de captura no forma parte del lock. No cambiar aislamiento global para hacer pasar pruebas.

### Permisos previstos y privacidad de cámara

| Código definitivo previsto | Administrador | Encargado | Docente | Estudiante | Guarda adicional |
| --- | --- | --- | --- | --- | --- |
| qr.gestionar | Sí | Sí | No | No | Acceso de consulta al objetivo según el apartado de emisión; no incluye validación automáticamente |
| qr.validar_uso_espacio | Sí | Sí | Sí | Sí | CU11: permiso de consulta administrativo o propio + propiedad, estado/periodo/restricciones |
| qr.validar_operacion | Sí | Sí | No | No | CU20: registrar_entrega o registrar_devolucion, según la operación, y demás permisos de Fase 5 |

Son tres códigos planificados, **cero permisos creados ahora**. Fase 5 conserva 33; Fase 6 materializará 36, con concesiones iniciales completas A33/E22/D8/S8 si no existen revocaciones. Mantener concesión por un mismo rol elegible vigente, seeder incremental e idempotente, propiedad y ausencia de bypass. No se crea un permiso administrativo paralelo para CU11: se combina el permiso QR con reservas.ver_administrativas.

Cámara es solo entrada local: lectura/decodificación en navegador compatible, permiso explícito y contexto seguro que permita su API; producción HTTPS. No imagen/frame a servicios externos, backend, analytics o almacenamiento. Al abandonar el flujo se detienen tracks y se descartan buffers/token. Cámara ausente/denegada o imagen ilegible muestra aviso y no crea VALIDA ni intento en BD si no hubo payload admitido. No se añade entrada manual al usuario; las pruebas futuras sí enviarán payloads sintéticos directamente al backend.

La futura librería debe estar mantenida, ser compatible con el stack, mínima y de procesamiento local, sin API/CDN/proveedor que reciba tokens o frames. Se seleccionará y auditará al autorizar Fase 6; no se instala en 5.5. El payload solo viaja por cuerpo POST al backend propio con sesión/CSRF y HTTPS en producción, nunca en URL/query/fragmento, Referer o redirects. Excluir el cuerpo QR del flash de errores/old input, logs HTTP/APM, trazas, consola y capturas. No enviar hash almacenado al navegador para decidir validez.

Logs técnicos permitidos: ID de validación, resultado y tipo, sin token/payload/hash sensible. Repositorio, tests y documentación no incluirán tokens reales ni QR activos: pruebas generan tokens aleatorios sintéticos en la base aislada; capturas usan QR ficticio o previamente revocado. No se publican imágenes utilizables de desarrollo.

### Integraciones que permanecen diferidas

Fase 8: consumo de evidencia QR para liberación, tolerancias y politicas_liberacion.requiere_qr; no función de esa política en Fase 6. Fase 9: evidencia Blockchain de operación confirmada, fuera de locks; QR no genera Blockchain. RF21/notificaciones y CU30/bitácora general siguen sus fases. Las validaciones son historia específica del proceso QR, no sustituyen registros_actividad. Siguen diferidos IoT, móvil/API propio y alcance fino de recursos. La parametrización global de obligatoriedad QR se resolverá cuando exista un alcance/modelo autorizado; la extensión explícita definida aquí es implementable sin ella.

## Readiness for Phase 6

| Punto | Cierre documental |
| --- | --- |
| D-09 | RESUELTA PARA FASE 6; contrato anterior obligatorio |
| Token / payload / hash | 32 bytes CSPRNG → base64url canónico sin padding, 43 ASCII; payload opaco; SHA-256 hexadecimal minúsculo del string exacto |
| Almacenamiento | Solo token_hash/hash_leido; original y representación únicamente transitorios, sin persistencia ni filtración |
| Objetivos | Exactamente recurso/reserva/préstamo; QR físico de recurso principal, operaciones opcionales |
| Vida / vigencia / rotación | activo + [generada_en, vence_en), NULL permitido; máximo un activo por objetivo exacto; revocación histórica, sin uso único |
| CU11 | Reserva APROBADA propia o administrativa autorizada, dentro de [inicio,fin); contexto exacto y guardas operativas; solo evidencia |
| CU20 | Extensión explícita de entrega APROBADO y devolución ENTREGADO; permite retorno físico con restricción, conserva revisión y BAJA |
| Obligatoriedad | No global; invalidez detiene la rama invocada; política de liberación no se reutiliza |
| Correlación | Un request y transacción compartida; enlaces existentes NULL→hito; no validacion_id antigua ni ventana temporal arbitraria |
| Permisos | qr.gestionar, qr.validar_uso_espacio, qr.validar_operacion; matriz/contexto cerrados, sin seeds actuales |
| Seguridad / privacidad | Sesión/ACTIVO/RBAC/CSRF, campos servidor, payload sin datos personales, cámara local, sin frames/logs/tokens persistidos |
| Concurrencia | Orden de locks compatible con Fases 4/5 y lecturas actuales; rotación, revocación, expiración, estado cambiado y hitos únicos |
| Aceptación futura | [T-Q01–24](BUSINESS_RULES.md#aceptación-futura-de-qr--fase-6), esquema exacto, MySQL real, navegador y rendimiento |
| Integraciones diferidas | Liberación, obligatoriedad configurable global, IoT, Blockchain, notificaciones y CU30 no bloquean la extensión delimitada |
| Bloqueantes documentales restantes | **0** para el alcance definido; no equivale a implementación ni a pruebas funcionales QR aprobadas |

**Fase 6 puede comenzar con una nueva instrucción del usuario. No se inició en Fase 5.5.** La elección de librería compatible es una tarea técnica de esa fase, no una decisión de negocio pendiente. La evidencia funcional vigente sigue siendo la de Fase 5 (262 pruebas / 3318 aserciones); no se atribuye a QR ni se repite por este cierre documental.

## Excepción técnica autorizada durante Fase 6 — MySQL 3823

La ejecución del SQL literal en MySQL real detectó incompatibilidad entre CHECK y ON UPDATE CASCADE en las mismas columnas. El usuario autorizó expresamente usar ON UPDATE RESTRICT solo en siete FK nuevas (tres de codigos_qr y cuatro de validaciones_qr). [Lista exacta, evidencia e impacto](QR_IMPLEMENTATION.md#esquema-y-excepción-autorizada). Esta excepción sustituye únicamente la exigencia de CASCADE en esas siete actualizaciones del contrato D-09; conserva todos los CHECK, ON DELETE RESTRICT, tipos, columnas, índices, UNIQUE y demás FK. No se modifican Word/PDF ni migraciones anteriores. Los IDs padres con historial QR no se renumeran. El resto de D-09 continúa vinculante; Fase 7 no autorizada.

## Aplicación de D-09 en Fase 6

La nueva instrucción autorizó las dos tablas/modelos/enums, tres permisos, lifecycle, CU11/CU20, extensiones CU17/CU18 y cámara local. D-09 no se reabre. Se mantiene la excepción de siete ON UPDATE RESTRICT autorizada arriba, con constraints/metadatos reales probados y originales intactos. [Implementación, trazabilidad y resultados](QR_IMPLEMENTATION.md): 325 pruebas/4984 aserciones, 16 escenarios concurrentes nuevos. El usuario aceptó la lectura física sin exigir nueva corroboración independiente, habilitando cierre/integración final; Fase 7 no iniciada. Las afirmaciones de cero implementación del apartado Fase 5.5 describen solo esa entrega documental.


## Aceptación manual final de Fase 6

El usuario autorizó cerrar la comprobación física por su confirmación («funciona yo se que funciona dalo por hecho aunque no haya la prueba»). Se conserva la distinción entre pruebas automatizadas/sintéticas, intento físico inválido por recurso equivocado y ausencia de una nueva evidencia física válida corroborada. Esta instrucción resuelve la puerta de aceptación para publicar/integrar sin inventar un resultado técnico. Fase 7 no iniciada.

## Contrato IoT — cierre D-10 de Fase 6.5

**D-10: RESUELTA PARA FASE 7.** Este apartado es la referencia normativa única del protocolo. Cierre exclusivamente documental sobre `ceae7cb`, rama `docs/phase-6.5-iot-decisions`. No se modifican código, SQL, migraciones, configuración real, `.env.example`, permisos DB, vistas ni Word/PDF. No se generan credenciales ni datos IoT. Fase 7 no iniciada; las obligaciones futuras no son pruebas ejecutadas.

### Fuentes y alcance IoT

Revisados RF16/RF17, CU21–22, CU23 solo como integración futura, IC-06/IC-07 y RD-40 en Word/PDF final. Referencias PDF: CU21 pp. 205–206, CU22 pp. 207–208, CU23 pp. 208–209; IC p. 155, RD-40 p. 163. SQL: políticas pp. 275–276; sensores/eventos pp. 292–294. Los tres CREATE TABLE son equivalentes entre Word y PDF tras eliminar encabezados/espacios; sus hashes originales siguen intactos. Se contrastaron arquitectura, dominio, reglas, diccionario, seguridad y plan. Las convenciones técnicas siguientes completan el informe, no se atribuyen literalmente a él.

**IoT detecta presencia u ocupación del espacio. NO identifica personas ni asistencia individual.** No inferir usuario, estudiante, docente, nombre, correo, matrícula o CI. Prohibidos imagen, audio, biometría, cámara, reconocimiento facial, seguimiento personal Bluetooth, MAC Wi-Fi de personas e identificadores móviles. Identificador del sensor y código del espacio son datos técnicos, no identificadores de ocupantes.

### Transporte y configuración prevista

Piloto: **HTTPS/HTTP REST hacia Laravel**, endpoint exacto **`POST /api/iot/events`**, sin query string ni barra final. HTTP únicamente sobre localhost/loopback en desarrollo; un dispositivo fuera del host requiere HTTPS y validación de certificado. No conectar sensores a MySQL. La ruta tendrá autenticación propia, sin Session web, Inertia, CSRF web, cookies, usuario humano ni rol. La administración humana conserva sesión, CSRF y autorización actuales.

MQTT **opcional futuro, no requerido para el piloto**: no instalar broker en Fase 7 salvo necesidad posterior explícita. Su adaptador autenticará dispositivo/broker con TLS y entregará el mismo evento lógico/contexto autenticado al **mismo servicio de ingestión, validación, deduplicación y persistencia**. No copiar reglas por transporte. La envoltura MQTT se especificará solo si se autoriza; no bloquea el piloto REST.

| Configuración futura, solo servidor | Valor inicial y función |
| --- | --- |
| `IOT_MASTER_SECRET` | Vacío en plantilla futura; CSPRNG de 32 bytes (256 bits), codificado en 64 caracteres hexadecimales minúsculos; nunca ejemplo utilizable. |
| `IOT_REQUEST_MAX_SKEW_SECONDS` | `300`; tolerancia de autenticación y límite de evento futuro, única fuente de configuración. |
| `IOT_MONITORING_FRESHNESS_SECONDS` | `300`; vigencia informativa de lecturas y comunicación, independiente del anterior y de políticas. |
| `IOT_RATE_LIMIT_PER_MINUTE` | `120` solicitudes autenticadas por sensor por ventana de 60 segundos, configurable según frecuencia real; incluye duplicados y rechazos posteriores a autenticación. |
| `IOT_MAX_BODY_BYTES` | `4096` bytes RAW, máximo inclusive y configurable; rechazar durante recepción al excederlo. |

Son valores técnicos, no reglas académicas. Se leerán de configuración central, sin constantes dispersas. Configuración inválida, secreto ausente o almacén de limitación indisponible: rechazo cerrado `503`; nunca aceptar sin firma ni usar una sola cuota para todos. No se crean estas variables en Fase 6.5.

### Credencial, identificador y provisionamiento

Convención: `HMAC_SHA256(clave_bytes, mensaje_bytes)` produce 32 bytes. `M = hex_decode(IOT_MASTER_SECRET)` y **`K_sensor = HMAC_SHA256(M, UTF8("sensor:" + identificador))`**. Prefijo literal `sensor:` para separación de dominio, sin espacios/saltos/normalización. Maestro exclusivo por entorno, únicamente en configuración privada del servidor (`.env` local ignorado; gestor seguro al desplegar). No reutilizar APP_KEY, contraseña de BD o credencial humana.

Provisionar `hex_lower(K_sensor)` de 64 caracteres; el dispositivo lo decodifica a **32 bytes** antes de firmar, nunca usa los 64 caracteres ASCII como clave. Recibe únicamente su clave, nunca el maestro. No guardar maestro, clave derivada ni su hash en `sensores_iot`, otra tabla, frontend normal o logs. El servidor deriva cuando necesita. La seguridad depende de CSPRNG, secreto maestro y HMAC-SHA-256: comprometer una credencial permite suplantar ese sensor, pero no debe permitir obtener el maestro ni otras claves.

`identificador` mantiene `VARCHAR(120)` y UNIQUE. Perfil ASCII del alta: `[A-Z0-9][A-Z0-9_-]{0,119}`, sin espacios. Validación/comparación exacta, sin trim, cambio de mayúsculas ni igualdad permisiva por collation en el protocolo. **Inmutable desde el alta**, convención conservadora que garantiza la inmutabilidad tras provisionar sin inventar una columna de provisionamiento. CU21 permite editar tipo, asociación y estado, no renombrar como edición ordinaria.

Mecanismo previsto: **acción administrativa explícita de provisionamiento**, sesión vigente, CSRF y `iot.sensores.gestionar` de un mismo rol elegible A/E; sensor registrado/habilitado. Reautorizar y leer sensor actual bajo protocolo de gestión; derivar en memoria y mostrar solo en respuesta solicitada expresamente, privada `no-store`, después del commit. Pantalla dedicada en memoria hasta salir, sin props persistibles de Inertia, flash, historial, almacenamiento del navegador, descarga automática ni analytics. Ver catálogo no revela claves. No retener locks esperando UI. Repetir provisionamiento con mismo identificador/maestro entrega la misma clave: **no es rotación**.

Clave comprometida: deshabilitar sensor, registrar explícitamente otro identificador y reprovisionar dispositivo; conservar anterior deshabilitado e historia. No rehabilitar el mismo identificador suponiendo una clave nueva. Reasociar espacio no rota credenciales. Cambio/compromiso de maestro: suspender ingestión, deshabilitar sensores, sustituir secreto privado, recargar todos los procesos/configuración, reprovisionar cada dispositivo por canal seguro y habilitar explícitamente tras comprobar nueva credencial. Sin aceptación silenciosa de clave antigua ni rotación automática. Tratar caches/copias de configuración como secretos; nunca claves en argumentos de comandos.

### Payload y validación de tipos

Ejemplo conceptual sin firma ni credenciales reales:

```json
{
  "sensor_identifier": "SENSOR-001",
  "space_code": "ESP-001",
  "type": "PRESENCIA",
  "value": 1,
  "occurred_at": "2026-10-23T20:30:00-04:00"
}
```

| Campo exacto | Contrato |
| --- | --- |
| `sensor_identifier` | String obligatorio, perfil del alta; idéntico a `X-IoT-Sensor` y al identificador registrado. |
| `space_code` | String obligatorio, código técnico ASCII de `recursos_institucionales.codigo` del espacio asociado; 1–60 caracteres; comparación exacta, sin normalizar ni aceptar ID arbitrario. |
| `type` | String obligatorio, exclusivamente `PRESENCIA` o `OCUPACION`; persiste en eventos_ocupacion.tipo. sensores_iot.tipo describe dispositivo, no este enum. |
| `value` | Opcional; omisión o JSON null → SQL NULL. Si no es nulo, número JSON finito representable exactamente en DECIMAL(10,2). |
| `occurred_at` | String obligatorio, instante con zona explícita y segundos según perfil temporal inferior. |

`Content-Type: application/json`, con único parámetro opcional `charset=utf-8`. UTF-8 sin BOM, objeto JSON único, sin claves duplicadas (incluso tras decodificar escapes), comentarios, arrays raíz ni campos adicionales. `Content-Encoding` ausente o identity; no cuerpos comprimidos. Rechazar `sensor_id`, `espacio_historico_id`, `fecha_recepcion`, PII o credenciales en JSON. Aplicar máximo incluso sin Content-Length; no confiar solo en esa cabecera.

Perfil decimal: número JSON sin exponente, hasta dos decimales, rango **−99999999.99 a 99999999.99**. Rechazar strings numéricos, booleanos, NaN/Infinity y exceso de escala/rango, sin redondear/truncar. Normalización decimal exacta: `1`, `1.0` y `1.00` equivalentes; `-0` equivale a cero; NULL difiere de cero. Validar token numérico sin pérdida binaria antes de parametrizar persistencia. No imponer positividad ni interpretar universalmente 1 como una persona o 0 como libre. Guardar/mostrar tipo y valor; las unidades/umbrales del hardware se describirán al incorporarlo sin alterar esquema ni reglas genéricas. [Precisión y escala de DECIMAL](https://dev.mysql.com/doc/refman/8.4/en/fixed-point-types.html).

### Firma de la solicitud y anti-replay

Cabeceras: **`X-IoT-Sensor`**, **`X-IoT-Timestamp`**, **`X-IoT-Signature`** (nombres HTTP sin sensibilidad a mayúsculas). Valor único por cabecera, rechazar repetidas/ambiguas. Sensor canónico; timestamp Unix decimal en segundos, entero positivo de 1–12 dígitos, sin signo, fracciones, espacios ni ceros iniciales, validado antes de conversión aritmética; firma de 64 caracteres `[0-9a-f]`, sin prefijo. No transmitir clave ni secretos en URL.

`body_hash = hex_lower(SHA256(raw_body_bytes))`. Cadena canónica de cinco líneas UTF-8 unidas por **LF (0x0A)**, sin CR, BOM, espacios añadidos ni LF final:

```text
POST
/api/iot/events
<sensor_identifier>
<unix_timestamp>
<sha256_hex_del_body_RAW_exacto>
```

Equivalencia: `"POST\n/api/iot/events\n" + header_sensor + "\n" + header_timestamp + "\n" + body_hash`. Sustituir marcadores sin `< >`. Firma: **`hex_lower(HMAC_SHA256(K_sensor, canonical_bytes))`**. No reserializar, ordenar, trimar ni normalizar body antes del hash. Cualquier byte cambiado exige nueva firma aunque el JSON sea equivalente. Comparación constant-time como `hash_equals(expected, received)`, tras validar longitud/formato. Bases técnicas: [RFC 2104](https://www.rfc-editor.org/rfc/rfc2104), [hash_hmac](https://www.php.net/manual/en/function.hash-hmac.php), [hash_equals](https://www.php.net/manual/en/function.hash-equals.php); cadena/codificación propias del piloto.

Orden: limitar envoltura/recepción y validar cabeceras; comprobar timestamp y **autenticar HMAC sobre RAW antes de parsear/validar JSON**. No middleware de transformación/trim previo. Para identificador bien formado puede derivarse/compararse firma antes de consultar existencia; después verificar registro exacto. Desconocido y firma inválida reciben el mismo `401`, sin alta automática ni enumeración de identificadores. Solo firma válida de sensor registrado permite informar deshabilitación. Autenticación no permite indicar otro sensor en JSON.

Aceptar si **`abs(unix_servidor - X-IoT-Timestamp) <= IOT_REQUEST_MAX_SKEW_SECONDS`**, límites inclusivos. Servidor autoridad; dispositivos/servidores sincronizados por NTP o equivalente. Revalidar ventana después de esperar locks; request expirado en espera no se acepta. La ventana acota replay, **no impide por sí sola repetir dentro de 300 s**: deduplicación exacta evita efectos repetidos. No prometer nonce, uso único o exactly-once absoluto. Reintento tardío conserva medición/occurred_at y usa nuevo timestamp de request/firma; nunca rejuvenece la medición.

### Fechas y frescura del monitoreo

Perfil: `YYYY-MM-DDTHH:MM:SSZ` o `YYYY-MM-DDTHH:MM:SS±HH:MM`; T/Z mayúsculas, calendario válido, hora 00–23, segundos 00–59, offset conocido (`-00:00` rechazado). Rechazar zona ausente, epoch, fracciones o segundos intercalares, sin truncar. Es un subconjunto deliberado de [RFC 3339](https://www.rfc-editor.org/rfc/rfc3339), compatible con DATETIME sin fracciones. Convertir conservando instante a **America/La_Paz** y validar rango DATETIME. Z/offsets equivalentes producen misma fecha local y deduplicación.

`fecha_evento` es medición declarada; **`fecha_recepcion` es recepción/aceptación al persistir por el servidor**, nunca input. Conservar `DEFAULT CURRENT_TIMESTAMP`; la inserción lo utiliza y ultima_comunicacion adopta ese mismo instante leído de fila insertada. No columna adicional de llegada HTTP. Aplicación/sesión MySQL con zona coherente y reloj sincronizado según D-16; ventana de request en epoch. [DATETIME y precisión](https://dev.mysql.com/doc/refman/8.4/en/date-and-time-type-syntax.html).

Rechazar si `fecha_evento > ahora_servidor + IOT_REQUEST_MAX_SKEW_SECONDS`; igualdad del límite admisible, revalidar al persistir. Pasados auténticos se aceptan como históricos sin corte arbitrario, si cumplen sensor/estado/contexto/payload/rango SQL. No sustituir evento por recepción. Futuros dentro de tolerancia se guardan, pero no son lectura actual hasta alcanzar su instante (convención conservadora).

Lectura **ACTUAL** exige ambas condiciones a precisión de segundos:

- `0 <= now - fecha_recepcion <= IOT_MONITORING_FRESHNESS_SECONDS`;
- `0 <= now - fecha_evento <= IOT_MONITORING_FRESHNESS_SECONDS`.

Filtrar por espacio_historico_id del espacio mostrado. Aporte actual requiere sensor HABILITADO y asociado actualmente a ese espacio; deshabilitado/reasignado conserva historia sin informar ocupación actual del espacio anterior. Seleccionar candidatos recientes por sensor/tipo y ordenar **fecha_evento DESC, fecha_recepcion DESC, id DESC**: llegada tardía no desplaza medición más nueva por ordenar solo recepción/ID. Tipos separados, sin sumar valores ni inferir identidad. Valores contradictorios del mismo sensor/instante/tipo se conservan y marcan insuficiencia; no elegir arbitrariamente última inserción como verdad física.

Sin lectura reciente/suficiente: **NO_DETERMINABLE**, jamás LIBRE, DESOCUPADO o SIN_USO. Con lecturas mostrar tipo, valor y fechas como evidencia ambiental. Frescura no determina una interpretación física universal: semántica no documentada o contradicción mantiene ocupación NO_DETERMINABLE aunque haya comunicación reciente. Etiquetas calculadas, sin nuevos enums/columnas.

Salud de comunicación: calcular edad no negativa de ultima_comunicacion con ventana de monitoreo; NULL/vencida implica sin comunicación reciente, no prueba permanente de ONLINE. Evento histórico nuevo puede indicar comunicación reciente sin ocupación actual. Persistir solo **HABILITADO / DESHABILITADO**. Gestión, cambio de asociación o provisionamiento no actualizan ultima_comunicacion.

### Contexto, deduplicación y transacción por sensor

IC-07 exige `space_code`, comparado exactamente con código actual del recurso del espacio en BD. `espacio_historico_id` proviene de **sensor.espacio_actual_id validado al aceptar**, no de ID cliente ni asociación inferida desde occurred_at. Tras A→B, anteriores conservan A y nuevos aceptados usan B. Payload A firmado antes y procesado después se rechaza, incluso histórico/retransmitido. Si CU05 cambia código del espacio, actualizar explícitamente dispositivo; rechazar código anterior sin modificar IDs/historia ni prohibir silenciosamente edición CU05 existente.

Duplicado exacto: **`(sensor_id, espacio_historico_id, tipo, valor NULL-safe, fecha_evento)`** normalizados. Comparar `valor <=> valor_normalizado`: omisión/null equivalentes, NULL distinto de cero. Firma, serialización, timestamp de request y recepción no forman identidad. Dos mediciones iguales en mismo segundo son indistinguibles bajo SQL y se consideran una. No event_id externo, nonce, UNIQUE, columnas ni tabla adicionales.

Protocolo obligatorio para toda ingestión futura:

1. Recibir cuerpo acotado, autenticar HMAC y validar sintaxis fuera de transacción; resolver ID candidato sin confiar en estado/asociación cacheados. Aplicar cuota por sensor autenticado. Sin locks durante transmisión, red o UI.
2. Abrir **transacción nueva breve sin snapshot consistente previo**. Bloquear fila objetivo de sensores_iot por PK con **SELECT FOR UPDATE**. Releer identificador, estado HABILITADO y asociación; rechazo sin escritura si inválido.
3. Leer espacio actual por PK **FOR SHARE** en espacios_academicos y luego padre recursos_institucionales; comparar código. Retener locks compartidos hasta commit para evitar cambio entre comprobación/inserción. No exigir DISPONIBLE: monitorear mantenimiento/bloqueo sigue siendo informativo.
4. Revalidar reloj/request/evento después de espera. Buscar duplicado como **primera lectura consistente de la transacción**, después del lock de sensor, usando índice `(sensor_id, fecha_evento)` y demás predicados. Sin snapshot previo, rango FOR UPDATE ni bloqueo global. En REPEATABLE READ el snapshot se crea entonces y ve al escritor previo ya confirmado. Todo escritor del sensor respeta el mutex; SQL ajeno al servicio no está cubierto.
5. Si existe: `200 duplicate` y ID original, sin nueva fila ni actualización de **ultima_comunicacion** o fechas. Estado/contexto actuales prevalecen sobre detección de duplicado.
6. Si no existe: insertar con espacio validado y recepción DEFAULT servidor, actualizar **ultima_comunicacion con esa recepción** en la misma transacción; commit antes de `201`. Fallo intermedio revierte ambas escrituras.

Orden: **sensor exclusivo → espacio/subtipo y recurso compartidos → deduplicación → evento → ultima_comunicacion → commit**. Sensores distintos usan mutex distintos y lecturas compartidas compatibles, incluso en mismo espacio; sin fila Administrador/RBAC, bloqueo de tabla ni cola global para ingestión. Fundamento: [lecturas con locks](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html) y [snapshots](https://dev.mysql.com/doc/refman/8.4/en/innodb-consistent-read.html). La garantía requiere probar protocolo completo.

**Gestión humana CU21** conserva coordinación RBAC/reautorización: fila RBAC → sensor objetivo exclusivo → espacio/subtipo y padre compartidos (PK ascendente si varios). Habilitar/deshabilitar/reasociar/provisionar comparte lock del sensor; alta valida espacio y UNIQUE en transacción, sin sensor inexistente como mutex global. CU05 mantiene espacio→recurso sin adquirir después sensor. Prohibir inversión espacio→sensor y llamadas desde ingestión a servicios que adquieren RBAC.

Si gestión obtiene lock y confirma primero, ingestión revalida nuevo estado/contexto. Si ingestión confirma primero, evento permanece válido/histórico y gestión se aplica después. Igual para cambio de código con locks compartidos/exclusivos. Hasta tres intentos por deadlock, rollback total, reloj y comprobaciones renovados; agotados → `503`. Duplicado concurrente: una fila y una actualización de comunicación. Rechazados, desconocidos, deshabilitados y duplicados nunca renuevan ultima_comunicacion.

### Respuestas, límites y registros técnicos

JSON mínimo y `Cache-Control: no-store`, sin stack, clave, firma, eco del cuerpo ni contexto no autorizado. Resultados de API no son enums de BD.

| HTTP | Resultado conceptual | Efecto |
| --- | --- | --- |
| 201 | `accepted`, `event_id` confirmado | Una fila y ultima_comunicacion atómicos. |
| 200 | `duplicate`, `event_id` original | Sin fila nueva ni renovación. |
| 401 | `authentication_failed`: cabecera/firma/timestamp inválido o desconocido | Ninguno; uniforme sin enumeración. |
| 403 | `device_not_authorized`: firma válida de registrado deshabilitado | Ninguno. |
| 400 | `invalid_json`: no interpretable, después de autenticar | Ninguno, sin reflejar entrada. |
| 422 | `invalid_payload`: campos/tipos/valor/fecha/header-vs-body | Ninguno, razones acotadas. |
| 409 | `space_context_mismatch` | Ninguno; no revelar espacio esperado ni reasociar. |
| 413 / 415 | `payload_too_large` / `unsupported_media_type` | Ninguno; se permite verificar antes de autenticar. |
| 429 | `rate_limited`, Retry-After | Ninguno; esperar antes de reintentar. |
| 503 | `temporarily_unavailable`: configuración/BD/limitador/reintentos | Sin confirmación parcial; reintento idempotente. |

Método/ruta no admitidos → 405/404 sin detalles. Red/5xx/429 admiten reintento con espera incremental acotada y timestamp/firma nuevos; 201/200 confirman. 401 exige corregir reloj/credencial; 403/409/422, configuración/contexto/datos. No bucle de errores permanentes. Respuesta perdida después de commit se recupera como duplicado mientras sensor/contexto sigan autorizados.

Cuota: contador atómico por **ID interno autenticado**, ventana de 60 s desde primera solicitud, 120 permitidas, siguiente 429 hasta vencer; configurable. Solicitudes sin autenticación no consumen cuota del identificador de una víctima. Protección previa por origen/IP es complementaria, no cuota global de sensores. Backend de contadores coordina procesos; no memoria aislada por worker. Al desplegar varias instancias verificar almacén compartido. Ajustar al piloto y probar >=120 eventos/min válidos agregados sin pérdida y reflejo <=5 s (R-CC-05/R-PR-02); no ejecutado ahora.

Logs permitidos: ID interno sensor conocido, resultado, HTTP status, motivo técnico seguro e ID evento. Excluir firma completa, claves, maestro, cabeceras secretas, cuerpo completo, PII y provisión de logs/APM/analytics/proxy/errores. Payload malicioso extra tampoco se registra. Logs técnicos no sustituyen CU30 ni autorizan anticipar registros_actividad.

### Consulta humana, privacidad e integración futura

| Permiso futuro exacto | Administrador | Encargado | Docente | Estudiante |
| --- | --- | --- | --- | --- |
| `iot.sensores.ver` | Sí | Sí | No | No |
| `iot.sensores.gestionar` | Sí | Sí | No | No |
| `iot.monitoreo.ver` | Sí | Sí | No | No |

AuthorizationService/Gates/Policies: cuenta/sesión vigentes, permiso de mismo rol elegible, sin bypass de Administrador; revocación en siguiente petición y reautorización de mutaciones tras locks. D-04 mantiene ámbito fino diferido con módulo/acción autorizado A/E para estos CU, sin tabla de asignaciones. Permisos **previstos**, ninguno insertado ahora; catálogo materializado sigue en 36. Dispositivo sin permisos humanos.

CU21: registrar, consultar, asociar/reasociar, habilitar/deshabilitar, ver comunicación y provisionar; **sin eliminación física**. CU22: autenticar, validar, deduplicar, persistir y consultar eventos por sensor, espacio, tipo y rango de fecha_evento normalizado `[desde,hasta)`, paginación de 15, orden fecha_evento/fecha_recepcion/id descendente; no público. Eventos inmutables, sin editar/borrar desde UI; corrección técnica excepcional requiere autorización/contrato específico, no endpoint oculto.

Conservación piloto: **sin purga automática**, sin TTL destructivo. Cierra la puerta D-16 previa a ingestión; capacidad, respaldo y retención productiva se revisarán al desplegar, sin inventar plazo académico.

CU06 puede mostrar a sus lectores autorizados evidencia mínima informativa (reciente/no determinable, tipo/valor y hora), sin identificadores/credenciales de sensores ni historial/admin. Conservar SpaceAvailabilityService y reglas de reservas, bloqueos, incidencias/estado. **Ningún evento IoT cancela, libera, aprueba, invalida reservas ni cambia espacio a DISPONIBLE**, incluso “sin presencia”. Caída IoT no impide reservas/préstamos.

CU23/Fase 8 combina QR, IoT, reserva y política; **no se implementa en Fase 7**. `politicas_liberacion.max_antiguedad_lectura_min` (minutos, nullable según SQL) sigue específico de política; no reemplazar con `IOT_MONITORING_FRESHNESS_SECONDS` (segundos, UI). D-08 resolverá significado de NULL/usa_iot=false antes de Fase 8. Silencio/evidencia insuficiente/contradictoria nunca justifica por sí sola liberar. QR válido tampoco identifica a quien detecta el sensor.

## Readiness for Phase 7

| Punto | Cierre de Fase 6.5 |
| --- | --- |
| D-10 | **RESUELTA PARA FASE 7**; contrato precedente normativo. |
| Transporte / endpoint | REST; HTTPS desplegado, HTTP localhost; POST /api/iot/events. |
| Autenticación / credencial | HMAC específico sin usuario/sesión/CSRF web; maestro servidor 256 bits, derivación sensor: + identificador. |
| Firma / canonical string | Clave binaria, HMAC-SHA-256 hex minúsculo; cinco líneas LF: método/path/sensor/timestamp/SHA256 RAW, sin LF final; autenticar antes de JSON. |
| Timestamps / anti-replay | Unix request ±300 s inclusive; evento con zona/segundos; deduplicación controla repetición dentro de ventana, sin nonce. |
| Payload / espacio | Cuatro campos obligatorios, value opcional nullable, dos tipos; validar space_code, persistir asociación al aceptar. |
| Reloj / fechas | Servidor autoridad, NTP/equivalente, America/La_Paz y fechas distintas; futuro máximo 300 s, históricos retrasados aceptables. |
| Frescura / monitoring state | Ambas edades 0..300 s; insuficiente → NO_DETERMINABLE; comunicación distinta de ocupación. |
| Duplicados / concurrencia | Tupla NULL-safe, transacción breve con sensor bloqueado, contexto actual/snapshot posterior; sin lock global/RBAC en ingestión. |
| Reassociation / disabled sensors | A→B conserva historia A; payload A posterior rechazado. Deshabilitado no inserta/renueva; sin delete. |
| Identificador / provisioning | UNIQUE e inmutable desde alta, acción administrativa explícita/no-store; maestro nuevo exige reprovisionamiento. |
| Privacy / permisos | Sin identidad/PII/tracking; tres permisos A/E previstos, D/S sin administración; ningún permiso DB creado. |
| Límites / seguridad | 120/min por sensor y 4096 bytes, configurables; errores acotados, secretos fuera de Git/UI normal/logs. |
| MQTT status | Opcional futuro, TLS/autenticación y mismo servicio; sin broker obligatorio. |
| CU06 / Fase 8 integration | Informativo sin alterar disponibilidad/reservas; CU23/parámetro de política en Fase 8. |
| Verificación futura | [T-I01–T-I32](BUSINESS_RULES.md#aceptación-iot-prevista-para-fase-7), obligatorias, no ejecutadas en 6.5. |
| Bloqueantes documentales | **0** para REST CU21/CU22; D-08/despliegue conservan fase propia. |
| Continuación | **Fase 7 puede comenzar con nueva instrucción del usuario. No se inicia en esta entrega.** |

### Verificación documental de Fase 6.5

Revisión de los 12 Markdown autorizados, diff y alcance: sin archivos funcionales/configuración/migraciones modificados. Los textos de los 217 requisitos y los tres apartados SQL del diccionario son idénticos a la base ceae7cb. Los hashes del Word/PDF original se mantienen. Comprobados 206 enlaces locales sin destinos/anclas rotos y 32 escenarios de aceptación futuros, sin ejecutarlos como pruebas funcionales.

Escaneo privado de los 309 archivos públicos y diferencias/historial sin coincidencias de secretos locales ni patrones sensibles; 81 archivos locales revisados para evitar persistencia de payloads QR, sin coincidencias. .env y .env.testing ignorados; plantilla sin valores privados. No se generó IOT_MASTER_SECRET ni claves derivadas. Consulta de metadatos exclusivamente de lectura: 23 tablas académicas, cero tablas sensores_iot/eventos_ocupacion, 36 permisos y cero permisos iot.*. Estos controles no son pruebas de ingestión IoT.

Rama de trabajo docs/phase-6.5-iot-decisions desde main/origin/main limpio en ceae7cb; commit de cierre documental, publicación de rama e integración fast-forward a main según autorización, sin force push. El informe de entrega confirma el hash resultante, igualdad remota y árbol limpio tras ejecutar esas operaciones. No se crean copias del proyecto ni un segundo workspace.


## Aplicación de D-10 en Fase 7

Contrato aplicado sin cambiar tablas, enums, derivación, formato RAW, tolerancias, identidad semántica, orden de locks ni permisos. La implementación seleccionada sigue siendo REST y provisión administrativa explícita. RawIotRequest evita el parseo anticipado del framework; el limitador usa file cache coordinado por mutex en este host. Configuración y procedimiento privado, ensayos MySQL, revisión visual y límites de despliegue se documentan en [IOT_IMPLEMENTATION.md](IOT_IMPLEMENTATION.md). Los criterios T-I01–T-I32 dejaron de ser solo futuros; los apartados de cierre 6.5 conservan su fecha y evidencia documental. No se reabre ni implementa D-08/CU23.

## Contrato de liberación de reservas — cierre D-08 de Fase 7.5

Autoridad: instrucción oficial de Fase 7.5, §§1–84, posterior a ffdebb9. Es una decisión documental de implementación; los detalles técnicos añadidos aquí son convenciones explícitas para resolver los vacíos, no texto atribuido al informe. No modifica D-05, D-09, D-10 ni la aplicación vigente. RF18/CU23 queda preparado, no acreditado como implementado.

### Fuentes contrastadas para D-08

Word final y PDF final conservados, con las huellas SHA-256 registradas al inicio de este documento. Se releyeron las tablas Word RF18/CU06/CU11/CU22/CU23 y sus equivalentes PDF, los cuatro CREATE TABLE completos y las figuras de dominio, arquitectura y clases (incluida ampliación de política/evaluación/sensores).

| Fuente | Página impresa / PDF y alcance |
| --- | --- |
| RF18, tabla 27 | 135 / 136: tolerancia, uso, alerta/liberación y fallo IoT conservador |
| CU06, tabla 43 | 182–183 / 183–184: disponibilidad operativa y complemento IoT |
| CU11, tabla 48 | 190 / 191: validación contextual de uso |
| CU22, tabla 59 | 206–207 / 207–208: eventos ambientales y extensión condicional |
| CU23, tabla 60 | 207–208 / 208–209: decisión, evidencia insuficiente y configuración |
| Dominio / arquitectura / clases | Figuras 19, 107 y 108; 109/247/248 impresas, 110/248/249 PDF |
| SQL políticas / evaluaciones / pivotes | 274–275 / 275–276; 293–295 / 294–296 |
| Contratos vigentes | BUSINESS_RULES, DATA_MODEL, DOMAIN_MODEL, USE_CASE_MATRIX, REQUIREMENTS_TRACEABILITY, ARCHITECTURE y SECURITY_POLICY; cierres RESERVATIONS_IMPLEMENTATION, QR_IMPLEMENTATION e IOT_IMPLEMENTATION |

Se contrastó además el código existente de cancelación, QR, gestión/ingestión IoT y coordinación de espacios, solo en lectura. La narrativa «actualizar disponibilidad» no autoriza asignar DISPONIBLE al espacio; el enum de reserva y el servicio central concretan el efecto. «Incidencia de configuración» no crea una incidencia de mantenimiento. «Sistema» es proceso, no usuario ni rol.

### Elegibilidad y reloj de evaluación

**Ninguna reserva puede liberarse automáticamente cuando la evidencia es insuficiente, desactualizada, contradictoria o técnicamente no interpretable. Ante duda se mantiene la reserva. Sin datos no significa espacio libre.** La suficiencia se refiere a las fuentes exigidas por la política y a los vetos fiables; no exige datos de una fuente opcional ausente en una política solo QR.

Sea T la hora confiable del servidor, normalizada a America/La_Paz y segundos enteros, coherente con MySQL y obtenida después de los locks. Es también evaluaciones_liberacion.fecha_hora, no una fecha enviada por el cliente. Reserva evaluable: estado APROBADA, política asignada y `inicio + tolerancia_min <= T < fin`. El instante inicial es inclusivo; fin es exclusivo. PENDIENTE, RECHAZADA, CANCELADA, LIBERADA y FINALIZADA no entran. No evaluar antes del umbral ni después del fin; tampoco crear filas de evaluación para esos descartes normales.

Si `inicio + tolerancia_min >= fin`, no hay ventana útil: política no aplicable a esa reserva, omitir evaluación y mantener estado. Comparar duraciones de forma segura para evitar overflow al sumar minutos grandes. Sin política (`espacios_academicos.politica_liberacion_id IS NULL`): omisión normal, sin evaluación, sustituto global ni error crítico.

Política inactiva o inválida: no ejecutar decisión normal ni liberar. Si existen reserva APROBADA dentro de su periodo y FK de política resoluble, permitir una evaluación técnica NO_DETERMINABLE con motivo de configuración y sin sustituir política. Para tolerancia no negativa legible se respeta su umbral; si la configuración impide calcularlo, el diagnóstico solo ocurre desde inicio y antes de fin. Tolerancia válida fuera del periodo sigue siendo omisión. Un fallo de BD/lectura no equivale a ausencia de evidencia: rollback y error técnico seguro, nunca liberar ni registrar un éxito ficticio.

### Configuración, versiones y asignación

Se conservan los nueve campos oficiales de politicas_liberacion, UNIQUE(nombre,version), defaults y CHECK exactos. Validación de aplicación futura: nombre recortado no vacío, hasta 120 caracteres; version entero positivo 1..2147483647; tolerancia_min entero 0..2147483647; booleanos reales; modo ALERTA/AUTOMATICA; requiere_qr o usa_iot verdadero. Con usa_iot=true, max_antiguedad_lectura_min entero no negativo dentro de INT; con usa_iot=false, almacenar NULL y rechazar un valor contradictorio, sin reutilizarlo ocultamente. Cero es válido: la ventana IoT se limita al segundo T, por lo que la falta de evidencia produce NO_DETERMINABLE.

Convención de versionado: la UI crea versiones, no edita reglas de una versión existente, incluso antes de su primera evaluación. Nombre/version y los cinco campos de decisión quedan inmutables desde creación; esto satisface y refuerza la inmutabilidad obligatoria después de uso. Un cambio de reglas exige otra fila/version. No añadir columna snapshot a reservas ni JSON de configuración a evaluaciones. Validar la pareja mediante el UNIQUE de MySQL y su collation vigente; una colisión concurrente solicita otra versión, sin sobrescribir. No borrar físicamente versiones, usadas o no; retirarlas con activa=false.

Asignación o retiro mediante la FK existente del espacio, únicamente hacia una versión activa/válida o NULL. **Prohibido cambiar esa FK, incluido NULL→política o política→NULL, si existe una reserva APROBADA de ese espacio con fin>T.** Aplica a futuras y activas. Reasignar al mismo ID es operación sin cambio. Consultar bajo lock de espacio, compatible con la aprobación existente; no confiar en un chequeo previo de UI.

Desactivar siempre puede detener decisiones futuras, incluso con reservas aprobadas; no altera historia ni LIBERADA. Para reactivar una versión, exigir configuración válida y ausencia de APROBADA con fin>T en todos los espacios que la referencian: evita volver a habilitar silenciosamente una regla durante compromisos existentes. Crear una nueva versión no la asigna automáticamente. En cada evaluación se guarda el politica_id exacto; nunca reconstruir la política histórica desde la FK actual del espacio. Una nueva versión sería otro contexto, pero la prohibición de reasignación impide usarla para reabrir una reserva APROBADA todavía vigente.

### Evidencia QR de CU23

Conjunto Q: todas las validaciones persistidas de la misma reserva, `recurso_esperado_id = reserva.espacio_id`, tipo_operacion=USO_ESPACIO y `inicio <= fecha_hora <= T < fin`. VALIDA acredita uso; basta una, también cuando requiere_qr=false. No pedir un segundo QR ni invalidar retroactivamente una validación por rotación/vencimiento actual de su código. La validez ya fue determinada por CU11 en el momento registrado. Otro recurso, reserva u operación queda excluido.

INVALIDA es contexto de intento fallido: se enlaza si pertenece a Q, pero nunca acredita no uso. Si requiere_qr=true y no hay VALIDA, falta el criterio QR obligatorio; en política solo QR eso basta para la condición administrativa de inactividad tras tolerancia, salvo veto/contradicción. No se presenta como medición física de espacio vacío. Si también usa_iot=true, se exige además evidencia negativa IoT suficiente. Si requiere_qr=false, la falta de QR no aporta evidencia negativa.

### Evidencia IoT de CU23

Con usa_iot=true, `W = max(inicio, T - max_antiguedad_lectura_min minutos)`. Esta ventana es independiente de IOT_MONITORING_FRESHNESS_SECONDS. Sensores relevantes: **todos** los HABILITADO asociados actualmente al espacio al adquirir los locks, sin filtrar por el texto libre tipo ni seleccionar solo el sensor favorable. Debe haber al menos uno para concluir no utilización.

Conjunto E: eventos de esos sensores con espacio_historico_id correcto, `W <= fecha_evento <= T` y `fecha_recepcion <= T`, ya confirmados y visibles al corte transaccional. No exigir la doble antigüedad de CU22: el contrato CU23 limita medición y prohíbe recepción futura. No usar eventos futuros tolerados por ingestión, eventos de otro espacio ni historia fuera de W. No borrar esa historia. ultima_comunicacion no sustituye una lectura. Una reasignación no cambia espacio_historico_id; sensores deshabilitados o actualmente en otro espacio no forman el conjunto relevante. Si el inventario requerido queda vacío o incompleto, no liberar.

Cuando usa_iot=false, max_antiguedad_lectura_min sigue NULL. Para concretar el veto de fuente opcional se congela una **ventana auxiliar de 300 segundos**, con W=max(inicio,T−300 s) y los mismos filtros de sensor/espacio/recepción. Es convención D-08 exclusiva del veto/contradicción, independiente de la configuración de CU22 y sin columna nueva. Sus ceros no demuestran inactividad: solo la falta de QR obligatorio puede hacerlo en esa política. Datos opcionales ausentes/ambiguos sin señal fiable ni contradicción no añaden un requisito IoT oculto. Una falla técnica al consultar el veto obliga a abortar, no equivale a conjunto vacío.

Interpretación exclusiva de CU23; no cambia el valor genérico ni las vistas de CU22:

| Evento relevante | Significado para CU23 |
| --- | --- |
| PRESENCIA, cualquiera sea su valor permitido por D-10, incluido NULL/0/negativo | Evidencia positiva por el tipo; no interpretar su silencio como ausencia |
| OCUPACION con valor>0 | Evidencia positiva, sin afirmar un conteo de personas |
| OCUPACION con valor=0 | Evidencia explícita compatible con no ocupación |
| OCUPACION NULL, negativo o no interpretable | Insuficiente; nunca evidencia negativa |

Se consideran **todos** los eventos de E, no solo el último por sensor. Un cero posterior no borra una presencia anterior todavía en ventana. Clasificación derivada en memoria (no enums/estados/tablas nuevos):

| Clase IoT para la matriz | Condición exacta, en orden de prioridad |
| --- | --- |
| Contradictoria | Coexisten evidencia positiva y cero explícito en E, incluso entre sensores/instantes distintos; o valores no equivalentes del mismo sensor/tipo/fecha_evento, incluidos NULL frente a valor. No elegir arbitrariamente uno. |
| Positiva | Al menos una PRESENCIA u OCUPACION>0, sin contradicción anterior; domina la carencia/ambigüedad de otros sensores. |
| Negativa completa | Inventario no vacío; cada sensor relevante aporta al menos una OCUPACION=0; todos sus eventos considerados son OCUPACION=0, sin positivos ni valores ambiguos. Solo sirve para demostrar inactividad si usa_iot=true. |
| Insuficiente | Cualquier otro caso: ningún sensor, falta de lectura en alguno, solo silencio, antigüedad, NULL/negativos o ambigüedad sin positivo fiable. |

No exigir muestras de PRESENCIA a un sensor que solo aporta OCUPACION; tampoco inventar capacidades desde tipo. Un sensor que solo emite presencia y calla impide la conclusión negativa de una política IoT. Las contradicciones internas dominan incluso cuando existe QR válido. QR válido frente a IoT negativa completa también es contradicción y produce NO_DETERMINABLE; un cero parcial con sensores faltantes es insuficiencia, no prueba negativa del espacio.

### Matriz completa de decisión de D-08

Aplicar solo tras elegibilidad, política activa/válida e idempotencia. Q indica existencia de VALIDA en Q; «sin QR» incluye únicamente INVALIDA. I usa las cuatro clases anteriores. «Según modo» se expande en ambas columnas; no es un quinto resultado. Para usa_iot=false, I corresponde únicamente a la ventana auxiliar del veto. Configuración requiere_qr=false/usa_iot=false es inválida y produce diagnóstico NO_DETERMINABLE, no una fila normal de esta matriz.

| requiere_qr | usa_iot | Q | I | modo ALERTA | modo AUTOMATICA |
| --- | --- | --- | --- | --- | --- |
| TRUE | TRUE | Válido | Positiva | MANTENER_POR_USO | MANTENER_POR_USO |
| TRUE | TRUE | Válido | Negativa completa | NO_DETERMINABLE | NO_DETERMINABLE |
| TRUE | TRUE | Válido | Insuficiente | MANTENER_POR_USO | MANTENER_POR_USO |
| TRUE | TRUE | Válido | Contradictoria | NO_DETERMINABLE | NO_DETERMINABLE |
| TRUE | TRUE | Sin QR | Positiva | MANTENER_POR_USO | MANTENER_POR_USO |
| TRUE | TRUE | Sin QR | Negativa completa | ALERTAR | LIBERAR |
| TRUE | TRUE | Sin QR | Insuficiente | NO_DETERMINABLE | NO_DETERMINABLE |
| TRUE | TRUE | Sin QR | Contradictoria | NO_DETERMINABLE | NO_DETERMINABLE |
| FALSE | TRUE | Válido | Positiva | MANTENER_POR_USO | MANTENER_POR_USO |
| FALSE | TRUE | Válido | Negativa completa | NO_DETERMINABLE | NO_DETERMINABLE |
| FALSE | TRUE | Válido | Insuficiente | MANTENER_POR_USO | MANTENER_POR_USO |
| FALSE | TRUE | Válido | Contradictoria | NO_DETERMINABLE | NO_DETERMINABLE |
| FALSE | TRUE | Sin QR | Positiva | MANTENER_POR_USO | MANTENER_POR_USO |
| FALSE | TRUE | Sin QR | Negativa completa | ALERTAR | LIBERAR |
| FALSE | TRUE | Sin QR | Insuficiente | NO_DETERMINABLE | NO_DETERMINABLE |
| FALSE | TRUE | Sin QR | Contradictoria | NO_DETERMINABLE | NO_DETERMINABLE |
| TRUE | FALSE | Válido | Positiva | MANTENER_POR_USO | MANTENER_POR_USO |
| TRUE | FALSE | Válido | Negativa completa | NO_DETERMINABLE | NO_DETERMINABLE |
| TRUE | FALSE | Válido | Insuficiente | MANTENER_POR_USO | MANTENER_POR_USO |
| TRUE | FALSE | Válido | Contradictoria | NO_DETERMINABLE | NO_DETERMINABLE |
| TRUE | FALSE | Sin QR | Positiva | MANTENER_POR_USO | MANTENER_POR_USO |
| TRUE | FALSE | Sin QR | Negativa completa | ALERTAR | LIBERAR |
| TRUE | FALSE | Sin QR | Insuficiente | ALERTAR | LIBERAR |
| TRUE | FALSE | Sin QR | Contradictoria | NO_DETERMINABLE | NO_DETERMINABLE |

**Veto:** ninguna celda con uso positivo fiable o contradicción permite LIBERAR, aunque la fuente sea opcional. La prioridad de contradicción explica por qué un QR válido puede dar NO_DETERMINABLE en vez de MANTENER_POR_USO. Ambos mantienen APROBADA.

### Resultados, persistencia y disponibilidad

Solo resultados SQL MANTENER_POR_USO, NO_DETERMINABLE, ALERTAR y LIBERAR. Modo pertenece a política; resultado a evaluación. Los cuatro crean evaluación y enlazan evidencia considerada. Los tres primeros mantienen APROBADA. ALERTAR registra la situación para supervisión autorizada, sin liberar ni enviar notificación en Fase 8. LIBERAR requiere modo AUTOMATICA y la celda correspondiente de la matriz; cambia exclusivamente APROBADA→LIBERADA dentro del mismo commit que evaluación y pivotes.

Fase 8 incorporará exactamente evaluaciones_liberacion (6 columnas), evaluaciones_eventos_ocupacion (2) y evaluaciones_validaciones_qr (2), con seis FK, PK simples/compuestas e índices del SQL; ON UPDATE CASCADE en todas, ON DELETE CASCADE solo desde pivotes hacia evaluación y RESTRICT en las otras cuatro. No hay UNIQUE(reserva,politica), soft delete, timestamps extra, tabla de ejecución ni columna de snapshot. Politicas_liberacion ya existe y no se remigra. Conservar motivo físicamente nullable, pero exigir en aplicación texto humano seguro no vacío <=500 caracteres en toda evaluación, incluidas configuración/contradicción. Identificar regla, fuentes y causa; para inventario incompleto indicar cantidades relevantes/con lectura suficiente, sin PII, códigos QR, hashes, firmas, secretos ni cuerpos JSON.

Enlazar exactamente Q y E que participaron, incluidos intentos QR INVALIDA y eventos ambiguos/contradictorios usados para explicar la decisión. No enlazar otras reservas, espacios, operaciones, futuro o historia fuera de ventana. Si el diagnóstico se limita a política inválida antes de consultar evidencia, los conjuntos usados son vacíos y el motivo lo explicita. No copiar eventos ni snapshots JSON. Política, resultados, motivos y pivotes históricos son inmutables por la aplicación: sin editar, borrar ni recalcular al llegar nuevos eventos. Las FK CASCADE no habilitan borrado funcional. Las asociaciones reconstruyen las lecturas originales; no prometen reconstruir un inventario de sensores o el flag activa pasado, que el esquema no versiona. El motivo conserva la conclusión/contexto seguro y no se reejecuta historia sobre el inventario actual.

LIBERADA deja de comprometer el intervalo según SpaceAvailabilityService. No cambia espacios_academicos.estado, bloqueos, incidencias ni mantenimiento; cualquier otro impedimento conserva la indisponibilidad. CU06 y las revalidaciones usan el mismo servicio central. No finalizar naturalmente reservas ni cambiar datos de resolución/cancelación para aparentar una liberación; el instante y motivo están en la evaluación.

### Idempotencia y reevaluación sin columnas nuevas

Unidad: pareja (reserva_id,politica_id) y su evaluación de inicio, no vigilancia continua de abandono. Antes de decidir, con lock de reserva, consultar historia propia. Si existe MANTENER_POR_USO, ALERTAR o LIBERAR para esa pareja, omitir automáticamente nuevas evaluaciones, incluso si llega otra lectura. Son terminales **del ciclo de evaluación**, no nuevos estados de reserva. Tras ALERTAR no se convierte en LIBERAR por silencio, por otra ejecución o por cambiar modo: esa versión no puede editarse. LIBERADA queda además fuera de elegibilidad.

Sin evaluación previa, evaluar una vez. Si la última es NO_DETERMINABLE (orden fecha_hora DESC, id DESC), solo reevaluar con nueva evidencia relevante. Si T retrocede respecto de la última fecha_hora, diferir hasta recuperar reloj coherente. No reintentar simplemente porque pasó un minuto, vencieron lecturas, cambió conectividad o se habilitó/deshabilitó un sensor. Un diagnóstico de política inactiva tampoco se repite sin nueva evidencia; reactivación por sí sola no evade la regla.

Guarda específica de configuración: si la política actual sigue inactiva/inválida y la pareja ya tiene alguna evaluación, omitir otro diagnóstico automático, aun si llega telemetría. Solo una pareja sin historia crea el diagnóstico inicial con Q/E vacíos. Esto evita que registros no consultados/no enlazados se presenten repetidamente como novedad, especialmente dentro del mismo segundo. La administración puede mostrar el problema vigente; no requiere insertar evaluaciones idénticas. Al corregir reglas se exige nueva versión y las guardas de asignación siguen vigentes.

Evidencia nueva para esa pareja significa un registro de Q/E actual **no enlazado a ninguna evaluación anterior de la pareja**, y una de estas condiciones:

1. QR: fecha_hora posterior a la última evaluación.
2. IoT: fecha_recepcion o fecha_evento posterior a la última evaluación y ambos <=T, dentro de la ventana actual. Un evento antiguo recibido ahora pero fuera de W no habilita reevaluación.
3. Empate de segundos: si fecha QR o recepción/medición IoT coincide con la última fecha_hora, un ID persistido aún no enlazado se trata como nueva evidencia visible. Es el desempate conservador para DATETIME sin fracciones; no se deduce orden de commit por un ID mayor, ni se pierde un evento distinto confirmado en el mismo segundo.

No admitir registros con ambas fechas estrictamente anteriores solo porque ahora se reasignó un sensor. Un evento futuro antes excluido puede volverse relevante cuando llega su fecha_evento (>fecha de la evaluación anterior), pero nunca se usa antes de ese instante. Una retransmisión IoT conserva ID/fechas, por lo que no dispara reevaluación. La nueva evaluación enlaza todo Q/E usado, no solo la novedad. Los IDs ya considerados no vuelven a habilitar un ciclo por desaparecer/reaparecer en una consulta. Pivotes e índices existentes bastan; sin hash de decisión, watermark ni tabla auxiliar.

Dos workers con la misma novedad compiten por el lock de reserva: solo el primero crea la evaluación; el siguiente ve los pivotes confirmados o el resultado terminal y omite. Si aparece otra novedad real, otra NO_DETERMINABLE puede producir una evaluación posterior. Un fallo antes del commit no consume evidencia ni deja una transición parcial; reintentar el trabajo completo.

### Transacción CU23 y compatibilidad de locks

La reserva es coordinador principal de CU23. El código actual de reservas/QR toma espacio antes de reserva y el de IoT toma sensor antes de espacio. Por ello **no se permite añadir una espera bloqueante reserva→espacio/sensor**, que invertiría ese orden. Convención de Fase 8: transacción nueva, reserva primero y adquisiciones posteriores NOWAIT; cualquier conflicto revierte todo y difiere/reintenta fuera de la transacción. No modificar el orden vigente de CU09/CU10/CU11/CU21/CU22 para esta integración ni adquirir el lock global de RBAC en el proceso interno.

Protocolo implementable:

1. Localizar ID candidato fuera de transacción; sus datos son orientativos. Abrir transacción nueva sin lectura consistente/snapshot previo, en MySQL InnoDB; no anidar en otra transacción.
2. Reserva por PK `FOR UPDATE NOWAIT`. Releer estado y espacio_id; si no es APROBADA, omitir sin evaluación. Dos CU23 nunca deciden simultáneamente sobre la misma reserva.
3. Espacio por PK `FOR UPDATE NOWAIT`, luego política por PK `FOR SHARE NOWAIT`. Releer FK, activa y reglas. Sin FK: omitir. El lock X del espacio estabiliza asignación y bloquea commits QR/ingestión entrantes que necesitan ese espacio.
4. Adquirir sensores relevantes actuales por espacio, orden id ascendente, `FOR SHARE NOWAIT`, usando el índice de asociación. No usar SKIP LOCKED: saltar un sensor podría fabricar unanimidad negativa. Un sensor retenido por ingestión/gestión provoca rollback, no silencio ni NO_DETERMINABLE por ese mero conflicto. Los locks de sensor impiden también reasignación saliente; el espacio bloquea altas/entradas/habilitaciones hacia él. No tomar locks de rango en eventos.
5. Leer T del servidor tras adquirir todos los locks y validar elegibilidad/configuración. Establecer la primera lectura consistente **después** de los locks; así incluye los commits previos de QR/IoT incluso bajo REPEATABLE READ. Leer evaluaciones/pivotes de la pareja e idempotencia, y Q/E. No reusar relaciones Eloquent cargadas antes de esperar.
6. Decidir con la matriz sobre ese corte completo. El lock del espacio y de los sensores mantiene estable la evidencia entrante relevante hasta commit. Si la hora avanzó mientras se procesaba, refrescar T y volver a comprobar fin, ventanas e idempotencia con ese mismo conjunto persistido; si ya no hay ventana útil, omitir. Nunca truncar silenciosamente eventos para concluir ausencia; si un límite técnico impide una lectura completa, abortar con error seguro.
7. INSERT evaluación con T y motivo, INSERT de ambos conjuntos M:N y, solo para LIBERAR, UPDATE condicionado a `id` y `estado=APROBADA`; exigir exactamente una fila afectada. Revalidar tiempo inmediatamente antes de esa escritura. Si falla cualquier operación o guarda final, rollback íntegro, sin evaluación que afirme una liberación inexistente.
8. Commit; efectos externos futuros únicamente después. Conflictos NOWAIT/deadlock/timeout: máximo tres intentos completos con espera breve fuera de transacción; agotamiento difiere al siguiente barrido y emite diagnóstico técnico sanitizado. Ninguna llamada a sensores, APIs, Blockchain o red dentro de locks; tampoco esperar nueva evidencia ni hacer sleep reteniendo locks.

NOWAIT impide esperar por un lock de fila ocupado; el snapshot RR nace en la primera lectura consistente. Su aplicación aquí es una convención de coordinación, no una garantía ya probada de CU23. Se fundamenta en el [manual MySQL sobre locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html) y [lecturas consistentes](https://dev.mysql.com/doc/refman/8.4/en/innodb-consistent-read.html); Fase 8 debe probar los intercalados reales y fallos descritos en T-L.

Operaciones humanas de políticas: conservar auth/sesión/CSRF y coordinación RBAC existente. Asignación: RBAC→espacio X→política S, reautorización y consulta actual de APROBADA con fin>T bajo espacio. Reactivación: RBAC→espacios referenciantes X por id→política X, revalidación actual de referencias y compromisos. Desactivación: RBAC→política X; CU23 ve el estado confirmado o termina antes de la desactivación, nunca usa una lectura previa al lock. Crear versión no altera otra. Ningún camino humano intenta lock de reserva después de retener política si ello invierte otro camino.

### Cancelación, QR e IoT concurrentes

CU10 conserva D-05: cancelación únicamente T<inicio; CU23 exige T>=inicio+tolerancia>=inicio. No abrir cancelación tardía para fabricar una carrera. Cerca de inicio, una cancelación legal puede haber tomado sus locks antes y confirmar primero: CU23 omite al releer CANCELADA. Si CU23 decide primero ya dentro de su ventana, la cancelación posterior falla estado/tiempo. Si una cancelación empezó antes pero esperó hasta inicio, debe fallar su propia guarda temporal. Resultado posible CANCELADA o LIBERADA según el orden **y las guardas**, nunca dos transiciones confirmadas ni una evaluación LIBERAR para una reserva ya inelegible.

QR confirmado antes de los locks CU23 entra en Q y puede vetar; si está esperando reserva mientras retiene espacio, NOWAIT hace retroceder CU23 y permite su finalización. Si CU23 confirma LIBERADA primero, CU11 relee la reserva y no registra una VALIDA posterior como si siguiera APROBADA. IoT que ya tenga sensor/espacio provoca el mismo retroceso o confirma antes del corte; si CU23 obtuvo los locks primero, el evento se acepta después y no reescribe la evaluación pasada. No prometer conocimiento de mensajes todavía en red ni de actividad física sin evidencia persistida. Reasignación/deshabilitación y nuevas asociaciones se resuelven por el mismo orden y relectura, sin usar inventario parcialmente bloqueado.

### Scheduler, consultas y permisos previstos

Laravel Scheduler invocará un comando/servicio interno de CU23 cada minuto por defecto, con frecuencia técnica configurable; no es tolerancia_min. No depende de un nuevo evento ni de que alguien abra CU22, lo que permite política solo QR. Es la ejecución temporal de la extensión condicional de CU22 sobre su evidencia persistida; el endpoint IoT no libera dentro de su transacción.

Consulta de candidatos en BD: APROBADA, inicio<=hora de barrido, fin>hora de barrido y política asignada. Paginación por clave en lotes acotados (100 por defecto); nunca hidratar todo el sistema. Filtrar tolerancia/activa cuando sea posible, sin perder el diagnóstico controlado de configuración inválida. La hora de barrido no sustituye T tras locks. Revalidar cada candidato y aislar errores por reserva; un conflicto no detiene todas. Sin nuevo índice/tabla por anticipación; verificar planes con índices oficiales y carga representativa en Fase 8. Evitar solapamiento del comando como optimización, sin convertir el mutex de scheduler en garantía de integridad: la transacción e idempotencia cubren dos workers/hosts.

| Permiso futuro | Administrador | Encargado de Laboratorio | Docente | Estudiante |
| --- | --- | --- | --- | --- |
| liberacion.politicas.ver | Sí | Sí | No | No |
| liberacion.politicas.gestionar | Sí | Sí | No | No |
| liberacion.evaluaciones.ver_administrativas | Sí | Sí | No | No |
| liberacion.evaluaciones.ver_propias | No | No | Sí | Sí |

Aplicar elegibilidad y permiso del mismo rol activo, sin bypass. El titular D/S consulta únicamente evaluaciones de reservas con solicitante_id propio; A/E puede inspeccionar contexto administrativo autorizado. Proyección propia: reserva, política/version, fecha, resultado y motivo seguro; no entregar validado_por, tokens/hashes QR, identificadores de sensores ni historial técnico completo. Mutaciones reautorizan bajo coordinación existente y no exponen endpoints de ejecución manual. No botón «Liberar ahora», rol/usuario ficticio Sistema ni permiso humano para que el scheduler actúe. Estos cuatro permisos no se crean en DB en 7.5.

### Límites de Fase 8 y aceptación futura

Notificación funcional de ALERTAR/LIBERAR: Fase 10; Fase 8 solo persiste resultado y lo expone en UI autorizada, sin tabla notificaciones ni aviso temporal. Blockchain: Fase 9 tras commit si D-12 define LIBERADA como operación crítica; no se presume ese alcance ni se llama red dentro de CU23. CU30 sigue diferido; evaluaciones son evidencia funcional propia. FINALIZADA natural continúa sin scheduler ni transición manual autorizada. MQTT, móviles y dashboard final quedan fuera.

Antes de aceptar Fase 8 deben pasar los [criterios T-L01–T-L36](BUSINESS_RULES.md#aceptación-de-liberación-prevista-para-fase-8), las 371 pruebas de regresión actuales y la revisión manual de permisos/historia. La cifra 371/5.862 pertenece a Fase 7; no se reejecuta ni se atribuye a esta fase documental. Las pruebas de CU23 aún no existen ni se declaran superadas.

## Readiness for Phase 8

**D-08: RESUELTA PARA FASE 8. Bloqueantes documentales restantes: 0.**

| Aspecto | Contrato congelado |
| --- | --- |
| Elegibilidad y tolerancia | APROBADA; inicio+tolerancia<=T<fin; sin ventana útil, omitir |
| Política aplicable | FK del espacio; sin política no evaluar; inactiva/inválida nunca libera |
| Configuración y versión | Fuente obligatoria al menos una; antigüedad IoT no negativa; versiones inmutables y FK histórica exacta |
| Cambio de política | Prohibido asignar/retirar mientras exista APROBADA con fin>T; desactivar conserva historia |
| QR y veto | VALIDA contextual desde inicio; INVALIDA nunca es no uso; positivo opcional también impide liberación |
| IoT y fuentes requeridas | Ventana de política independiente de CU22, todos los habilitados actuales; PRESENCIA/OCUPACION>0 positivos; cero explícito por todos para negativo |
| Contradicción / NO_DETERMINABLE | Matriz de 24 combinaciones × 2 modos; conserva APROBADA |
| ALERTA / AUTOMATICA | Inactividad suficiente produce ALERTAR / LIBERAR; solo el segundo cambia reserva |
| LIBERADA y disponibilidad | Transición atómica; estado operativo y otros impedimentos intactos |
| Evidencia M:N | Q/E exactos, motivo seguro obligatorio y tres tablas oficiales, sin snapshots adicionales |
| Scheduler | Proceso interno, minuto configurable y lotes; sin actor humano ni petición a sensor |
| Idempotencia | Terminales por reserva/política; NO_DETERMINABLE requiere novedad relevante, incluido desempate de segundos mediante pivotes |
| Locking / concurrencia | Reserva X primero; espacio/política/sensores NOWAIT, snapshot posterior, rollback/reintento; cancelación conserva D-05 |
| Permisos | Cuatro códigos previstos, matriz A/E y D/S propia; sin cambios DB en 7.5 |
| Integraciones diferidas | Notificaciones 10, Blockchain 9, CU30 y FINALIZADA fuera |
| Continuación | **Fase 8 puede comenzar con una nueva instrucción expresa. No comienza en esta entrega.** |

### Verificación documental de Fase 7.5

Revisión de fuentes, matriz completa, invariantes, enlaces y alcance exclusivamente Markdown; comparación del SQL de las cuatro tablas Word/PDF y conservación del diccionario físico y de los 217 textos de requisitos. Se comprueban las huellas originales, archivos funcionales sin diferencias, secreto/índice/historial y ausencia de nuevas tablas/permisos. Ninguna migración, seed, evaluación ni scheduler se ejecuta. Las extracciones/controles locales permanecen ignorados dentro del workspace oficial.

Resultados del control documental: **14 Markdown modificados, 0 archivos funcionales**, cuatro SQL Word/PDF equivalentes y cuatro secciones físicas del diccionario intactas; 217 textos de requisitos preservados; 48 resultados de matriz coherentes, 36 criterios futuros y 248 enlaces locales sin errores. SHA-256 originales sin cambios. Escaneo de 347 archivos públicos y 105 artefactos locales (95 anteriores y 10 de esta revisión), sin secretos detectados. Metadatos de solo lectura: 25 tablas académicas, 169 columnas, 48 FK, 39 permisos; cero tablas evaluaciones_* y cero permisos liberacion.*.

Rama docs/phase-7.5-release-policy-decisions desde main/origin/main limpio ffdebb9; commit documental, push e integración fast-forward autorizados, sin force push. El informe final confirma el SHA, igualdad remota y árbol limpio después de las operaciones. El cierre documental no equivale a implementación, calibración de hardware ni certificación de comportamiento concurrente: son condiciones de aceptación de Fase 8.

## Contrato Blockchain — cierre D-12 de Fase 8.5

**2026-10-25: D-12 RESUELTA PARA FASE 9. Solo documentación; CU24/CU25 no implementados.** La instrucción oficial de Fase 8.5 fija las decisiones siguientes. MySQL permanece como fuente operativa principal; Blockchain es evidencia complementaria y nunca decide el estado del negocio. No se modifican Word/PDF, el contrato D-08 ni el alcance de CU30.

### Fuentes y alcance de la decisión

Se contrastaron RF19/RF20 (tablas 28/29, PDF 138–139), CU24/CU25 (tablas 61/62, PDF 210–212), dominio (figura 19, PDF 110), clases/diseño lógico (figuras 108/109, PDF 249–250), arquitectura (figuras 106/107, PDF 247–248), secuencia 114 (PDF 305) y SQL de ambas tablas (PDF 296–299). El SQL extraído de Word y PDF coincide tras normalizar espacios. PROJECT_CONSTITUTION, reglas, modelos, seguridad, matrices y los cuatro informes de reservas, préstamos, liberación y QR se revisaron como contratos vigentes. Las fuentes no fijaban JCS, red ni recuperación: esas elecciones provienen de esta instrucción, no se atribuyen retrospectivamente al informe académico.

RF20 permite un usuario autorizado de auditoría/supervisión, sin asignar ese rol al Encargado. CU25 identifica Administrador como iniciador; D-03 prioriza ese detalle. Se congela inicialmente Administrador para los tres permisos, sin extenderlos implícitamente a otro rol. La privacidad se comprueba **antes** de generar/transmitir datos externos, conforme a la secuencia 114; el paso tardío del texto CU24 no autoriza una fuga previa.

Excepción física autorizada expresamente por el usuario el 2026-10-25: **solo `fk_registro_actor` tendrá ON UPDATE RESTRICT**, conservando ON DELETE RESTRICT y `chk_registro_responsable`. La restricción MySQL entre CHECK y cascada impide copiar esa cláusula literalmente. [Detalle y límites](DATA_MODEL.md#compatibilidad-mysql-para-fase-9--excepción-autorizada). No se ejecutó DDL; la autorización de siete FK QR continúa separada.

### Acciones críticas y transacción local

El conjunto cerrado es exactamente el siguiente. Cada transición exitosa crea **una fila en `registros_actividad`** dentro de la misma transacción que la operación y su hito histórico. El bloqueo/guarda de estado vigente impide repetir la transición y su actividad; no se añade un UNIQUE nuevo al SQL. Si falla esta escritura local obligatoria, se revierte la transacción de negocio completa. Un intento fallido/rechazado no crea actividad crítica exitosa ni evidencia externa.

| accion exacta | Caso y condición | actor_id / fecha_hora | usuario_afectado_id / recurso_id | Contexto / proceso_responsable |
| --- | --- | --- | --- | --- |
| RESERVA_APROBADA | CU09: PENDIENTE → APROBADA | resuelta_por_id / resuelta_en | solicitante / espacio_id | reserva_id; `CU09` |
| RESERVA_CANCELADA | CU10: cancelación legal de PENDIENTE o APROBADA | cancelada_por_id / cancelada_en | solicitante / espacio_id | reserva_id; `CU10` |
| PRESTAMO_ENTREGADO | CU17: APROBADO → ENTREGADO y entrega única | entregado_por_id / entrega.fecha_hora | receptor = solicitante / equipamiento_id | prestamo_id; `CU17` |
| PRESTAMO_DEVUELTO | CU18: devolución CONFIRMADA y ENTREGADO → DEVUELTO | confirmado_por_id / confirmada_en | solicitante / equipamiento_id | prestamo_id; `CU18` |

En las cuatro filas: `critica=TRUE`, `resultado=EXITO`, `descripcion=NULL` porque los hitos ya conservan la información necesaria; `incidencia_id=NULL`. Solo una FK de reserva/préstamo tiene valor. Se exige actor humano persistido para estas acciones aunque el SQL general permita proceso sin actor. La fecha de actividad es el mismo instante persistido en el hito, no un reloj posterior. No se inventa un usuario Sistema para procesar la evidencia.

Se excluyen RESERVA_RECHAZADA, PRESTAMO_RECHAZADO, aprobación de préstamo CU16, solicitudes, validaciones QR individuales, IoT, incidencias/mantenimiento y **APROBADA → LIBERADA de CU23**. Una devolución PENDIENTE_REVISION/NO_DETERMINABLE tampoco produce PRESTAMO_DEVUELTO; solo la confirmación final, incluso en una revisión posterior. CU23 conserva evaluaciones_liberacion, eventos IoT y validaciones QR como su historia. Ampliar este conjunto requiere nueva decisión/versionado, nunca una inferencia por la palabra «crítica».

Orden obligatorio: **operación + actividad crítica en transacción → commit MySQL → crear/confirmar evidencia local → intento externo**. No hay RPC bajo locks de reserva, préstamo o equipo. El callback posterior al commit prepara la evidencia local; su objetivo R-PR-03 es máximo 3 s desde confirmación, medido en Fase 9. Si falla hash/persistencia posterior, la operación ya confirmada sigue válida y la actividad permite recuperación; no se inventa un hash ni se oculta el fallo. Crear `registros_actividad` no implementa CU30: solo estas escrituras, sin buscador, pantalla o consulta general de bitácora.

### Esquema canónico y representación

`algoritmo_hash` se persiste exactamente como **`SHA-256`**, y `version_canonica` como **`espacio-u-jcs-v1`**. El hash principal es hexadecimal minúsculo de 64 caracteres sobre los bytes UTF-8 del documento JCS; en el contrato externo se decodifica a bytes32. No se sustituye por MD5, SHA-1 ni Keccak. Las firmas/selectores Ethereum usan su mecanismo de protocolo sin cambiar el algoritmo de evidencia.

Se adopta [JCS, RFC 8785](https://www.rfc-editor.org/rfc/rfc8785): serialización determinista sin espacios entre tokens, orden recursivo de propiedades por unidades UTF-16, strings Unicode válidos sin normalización y salida UTF-8. No basta ordenar claves manualmente o usar el JSON predeterminado del framework. Claves duplicadas, Unicode inválido y datos fuera del contrato se rechazan. El orden manual de los ejemplos no define el hash.

Todos los BIGINT son strings decimales positivos sin signo ni ceros a la izquierda, jamás números JSON; no pasar por float. Cada fecha canónica es UTC [RFC3339](https://www.rfc-editor.org/rfc/rfc3339), segundos y sufijo `Z`, sin fracciones: `YYYY-MM-DDTHH:mm:ssZ`. DATETIME y la operación de la aplicación conservan America/La_Paz; convertir explícitamente desde esa zona, no reinterpretar el texto local como UTC. No se usa la hora del navegador.

El objeto común contiene **exactamente seis claves**; no se incorporan atributos del modelo automáticamente:

| Clave | Tipo y valor/fuente |
| --- | --- |
| activity_id | String decimal: registros_actividad.id |
| occurred_at | String UTC: registros_actividad.fecha_hora |
| operation | String: una de las cuatro acciones exactas |
| schema | String constante `espacio-u-trace` |
| subject | Objeto con el esquema exacto por acción de las tablas siguientes |
| version | String constante `1`, no número JSON |

#### Subject RESERVA_APROBADA

| Clave | Fuente y representación |
| --- | --- |
| reserva_id | reserva.id, string decimal |
| reserva_codigo | reserva.codigo, string exacto persistido |
| espacio_id | reserva.espacio_id, string decimal |
| inicio | reserva.inicio, UTC |
| fin | reserva.fin, UTC |
| resuelta_por_id | reserva.resuelta_por_id, string decimal |
| resuelta_en | reserva.resuelta_en, UTC |

No se incluye `estado` actual. La cancelación o liberación posterior conserva los campos de resolución y no invalida la evidencia de aprobación. Se conserva también código, recurso e intervalo históricos: no pueden editarse tras la aprobación.

#### Subject RESERVA_CANCELADA

| Clave | Fuente y representación |
| --- | --- |
| reserva_id | reserva.id, string decimal |
| reserva_codigo | reserva.codigo, string exacto |
| espacio_id | reserva.espacio_id, string decimal |
| cancelada_por_id | reserva.cancelada_por_id, string decimal |
| cancelada_en | reserva.cancelada_en, UTC |
| motivo_cancelacion_hash | SHA-256 del texto persistido o JSON null |

Este esquema no exige aprobación previa: CU10 también admite cancelar una solicitud pendiente dentro de las guardas vigentes.

#### Subject PRESTAMO_ENTREGADO

| Clave | Fuente y representación |
| --- | --- |
| prestamo_id | prestamo.id, string decimal |
| prestamo_codigo | prestamo.codigo, string exacto |
| equipamiento_id | prestamo.equipamiento_id, string decimal |
| entrega_id | entrega.id, string decimal |
| entregado_por_id | entrega.entregado_por_id, string decimal |
| receptor_id | entrega.receptor_id, string decimal |
| fecha_hora | entrega.fecha_hora, UTC |
| condicion_entrega_hash | SHA-256 de entrega.condicion_entrega |
| observacion_hash | SHA-256 de entrega.observacion o JSON null |

La entrega se obtiene por el préstamo, con la unicidad oficial, no buscando «la última» por fecha. `recepcion_confirmada_en` no forma parte de este documento ni se modifica su contrato vigente.

#### Subject PRESTAMO_DEVUELTO

| Clave | Fuente y representación |
| --- | --- |
| prestamo_id | prestamo.id, string decimal |
| prestamo_codigo | prestamo.codigo, string exacto |
| equipamiento_id | prestamo.equipamiento_id, string decimal |
| devolucion_id | devolucion.id, string decimal |
| recibido_por_id | devolucion.recibido_por_id, string decimal |
| confirmado_por_id | devolucion.confirmado_por_id, string decimal |
| registrada_en | devolucion.registrada_en, UTC |
| confirmada_en | devolucion.confirmada_en, UTC |
| condicion | Valor literal ADECUADA o CON_INCIDENCIA de la condición final persistida |
| descripcion_condicion_hash | SHA-256 de devolucion.descripcion_condicion o JSON null |
| observacion_hash | SHA-256 de devolucion.observacion o JSON null |

La revisión conserva el mismo hito de devolución. Se admite corregir los campos de condición mientras está pendiente; después de confirmar quedan inmutables para esta versión. `recibido_por_id` y `registrada_en` conservan la recepción inicial, aunque otra persona confirme después.

#### Textos secundarios, historia y reconstrucción

Todo hash secundario es SHA-256 hexadecimal minúsculo de los **bytes UTF-8 exactos del texto persistido**: sin trim, cambios de mayúsculas, normalización Unicode ni conversión de saltos de línea. NULL → JSON `null`; string vacío → hash del string vacío; no omitir claves. La condición de entrega es obligatoria según SQL; un NULL indebido en un campo obligatorio no se transforma silenciosamente en dato válido. La política general NULL → null aplica a campos textuales opcionales.

El contrato no contiene nombres, apellidos, correos, CI, contraseña, teléfono ni texto personal innecesario. Solo IDs técnicos necesarios y hashes de los textos indicados. Un hash secundario no es cifrado y no habilita publicar textos o correlaciones adicionales.

La reconstrucción usa actividad + reserva/préstamo + entrega/devolución única e historia persistida. Todos los campos utilizados quedan protegidos contra edición/borrado/renumeración por flujos de aplicación después de su hito crítico; las transiciones posteriores conservan ese pasado. No se guarda JSON canónico en BD, descripcion, archivos, caché ni logs como sustituto de una columna. El SQL no tiene columna para ello. Si falta historia obligatoria, la verificación es NO_VERIFICABLE; si se puede reconstruir y cambia el hash, INCONSISTENTE. No se «repara» una evidencia reemplazando su hash.

### Persistencia local e idempotencia

La futura tabla evidencias_trazabilidad conserva sus **11 columnas**: id, registro_actividad_id, algoritmo_hash, hash, version_canonica, referencia_externa, registrada_localmente_en, registrada_externamente_en, estado, ultima_verificacion y resultado_verificacion. `registro_actividad_id` NOT NULL y UNIQUE; `hash` UNIQUE. El contrato conceptual 0..* de un dibujo se subordina al SQL: una actividad tiene 0..1 evidencia y la recuperación busca completar exactamente una para cada actividad crítica válida. Durante un fallo posterior al commit puede faltar temporalmente esa evidencia.

La creación usa una transacción local corta: bloquear actividad, buscar evidencia por su ID, reconstruir y crear si falta; ante carrera recuperar la existente por UNIQUE. No mezclar dos actividades por una colisión de hash: detener ese caso para revisión, sin sal/sufijo ni una segunda identidad. `activity_id` dentro del canónico separa operaciones distintas; no se promete imposibilidad matemática de colisiones.

Nueva evidencia: PENDIENTE, referencia/fecha externa/última verificación NULL, resultado_verificacion=PENDIENTE (NOT NULL y default oficial) y registrada_localmente_en del servidor. El resultado inicial no acredita una comprobación ejecutada; ultima_verificacion NULL lo distingue. Algoritmo, hash, versión, actividad y fecha local no se reescriben al reintentar. Deshabilitar la integración externa no desactiva actividad ni evidencia locales. No hay backfill automático de operaciones de fases anteriores a partir de su estado actual: una reconstrucción histórica masiva necesitaría una decisión expresa.

### Red, adaptador, configuración y contrato externo

Única red inicial: **Ethereum Sepolia Testnet, chain ID 11155111**. Mainnet y fondos reales quedan fuera. [Redes oficiales Ethereum](https://ethereum.org/en/developers/docs/networks/) y [configuración Sepolia de go-ethereum](https://raw.githubusercontent.com/ethereum/go-ethereum/master/params/chainspecs/sepolia.json). La interfaz conceptual `BlockchainTraceabilityDriver` separa negocio de RPC; `EthereumSepoliaDriver` es la implementación futura inicial. El proveedor RPC es configurable: cambiarlo no cambia red, contrato, hash o referencia histórica.

Configuración prevista, **sin editar .env ni .env.example en Fase 8.5**:

| Variable futura | Contrato |
| --- | --- |
| BLOCKCHAIN_ENABLED | Booleano; desactivado hasta configurar y validar integración |
| BLOCKCHAIN_NETWORK | `sepolia` |
| BLOCKCHAIN_CHAIN_ID | `11155111` |
| BLOCKCHAIN_RPC_URL | Secreto; vacío en plantilla futura |
| BLOCKCHAIN_PRIVATE_KEY | Secreto; vacío en plantilla futura |
| BLOCKCHAIN_CONTRACT_ADDRESS | Dirección pública del despliegue autorizado; vacía hasta tenerlo |
| BLOCKCHAIN_CONFIRMATIONS | `1` inicialmente, configurable, entero >=1 |

Fase 9 preparará wallet exclusiva del proyecto/testnet, sin reutilizar wallet personal, seed ni clave de Mainnet. Private key solo en .env local ignorado o gestor de secretos del despliegue: nunca BD, Git, logs, UI, README o fixtures públicos. RPC URL puede contener una API key y recibe el mismo tratamiento. No se generan ahora wallet, clave, RPC key ni contrato.

El driver valida configuración y `eth_chainId == 11155111` antes de enviar; fija también ese chain ID al firmar. Cambiar solo una variable no permite Mainnet. Si red, contrato, bytecode esperado o wallet autorizada no coinciden, no enviar; evidencia PENDIENTE y diagnóstico seguro. RPC solo en backend, HTTPS en integración remota, límites de conexión/respuesta y errores saneados sin cuerpos/URLs sensibles.

Contrato conceptual **TraceabilityRegistry**: `bytes32 evidenceHash → timestamp`, con registro restringido a la wallet autorizada del proyecto y consulta pública del hash/timestamp. La primera inscripción guarda `block.timestamp` y emite `EvidenceRegistered(bytes32 indexed evidenceHash, uint256 timestamp)`; una repetición devuelve el registro existente sin sobrescribirlo ni emitir un segundo evento lógico. No borra ni modifica registros, no recibe objetos de negocio ni fondos por la operación de registro. La dirección autorizada es configuración técnica del contrato, no identidad de usuario de ESPACIO-U.

El único argumento de negocio enviado es el hash de **32 bytes**. El selector ABI, firma, nonce, gas, dirección técnica de contrato/wallet y chain ID son metadatos necesarios; no se confunden con un envío de JSON de negocio. El timestamp externo nace del bloque, no de una fecha de operación enviada. No enviar IDs, códigos, motivos, observaciones, timestamps de negocio ni documento canónico. [Especificación oficial ABI y eventos](https://docs.soliditylang.org/en/stable/abi-spec.html#events).

En Fase 9 se fijarán licencia, versión estable de Solidity/compilador, configuración reproducible, ABI, bytecode y manifiesto público de despliegue (chain ID, dirección, bloque inicial, transacción, writer autorizado). Source podrá vivir en blockchain/contracts/. No se crea nada de ello ahora. Dirección y saldo testnet son preparación técnica de esa fase, no un contrato funcional pendiente. Cambiar contrato/writer no es equivalente a cambiar RPC: exige documentar el despliegue y conservar la capacidad de verificar referencias antiguas autorizadas.

### Referencia, confirmación y estados

`referencia_externa` se congela como **`eip155:11155111/<contract_address>/<tx_hash>`**: dirección `0x` + 40 hex y transacción `0x` + 64 hex, normalizadas a minúsculas; longitud 125, dentro de VARCHAR(255). No es una URL RPC. Solo se consulta un contrato reconocido del despliegue; una referencia no puede introducir una URL/host arbitrario. Un enlace de explorador se deriva de esa referencia validada y una base pública Sepolia sin credenciales.

| Estado persistido | Condición |
| --- | --- |
| PENDIENTE | No enviado, integración deshabilitada, RPC/contrato no disponible, envío incierto, transacción pendiente, revertida aún no recuperada o confirmaciones insuficientes |
| REGISTRADA | Cadena correcta, receipt exitoso en bloque canónico con >=N confirmaciones, evento del contrato para el hash y consulta contractual coherente |

Una respuesta de broadcast/tx_hash no es confirmación. Se comprueba receipt.status=1, contrato/evento/hash, blockHash frente al bloque canónico y `head - blockNumber + 1 >= N`, además del registro consultable en ese bloque confirmado. El timestamp de contrato debe corresponder al bloque de inscripción. Una confirmación inicial no equivale a finalidad absoluta; reorgs requieren comprobación posterior. [JSON-RPC Ethereum](https://ethereum.org/en/developers/docs/apis/json-rpc/).

Puede persistirse una referencia candidata con tx_hash conocido mientras sigue PENDIENTE, idealmente calculada antes de broadcast. `registrada_externamente_en` continúa NULL hasta confirmar; entonces se toma timestamp del bloque, convertido a la convención DATETIME local. La fecha local siempre es del servidor. Ni fecha externa ni referencia se inventan para dar por exitoso un envío incierto.

### Reconciliación y concurrencia

El procesamiento externo normal es automático. Fase 9 tendrá `trazabilidad:reconciliar`, invocado periódicamente por scheduler interno (inicialmente cada minuto), sin usuario humano ni dependencia durable de un callback en memoria. Recorre por lotes actividades críticas EXITO sin evidencia y evidencias PENDIENTE; un caso fallido no bloquea los demás. La actividad es el origen durable de trabajo: no se agregan tablas outbox/jobs ni columnas de payload/intentos a este contrato. El callback posterior al commit intenta la generación local inmediata; el reconciliador repara interrupciones.

Antes de cualquier reenvío, reconstruir y comprobar hash local, consultar contrato por hash y buscar transacción/receipt si existe referencia candidata. Si el registro ya existe, no emitir otra transacción: recuperar evento indexado por hash desde el bloque de despliegue, paginando logs, y validar la misma prueba de confirmación. Si existe hash pero aún no puede recuperarse una referencia coherente, conservar PENDIENTE y volver a consultar, sin inventarla ni reenviar.

Timeout tras broadcast significa **resultado incierto**, no fracaso. Una transacción conocida pendiente impide un nuevo envío. Revert definitivo o ausencia externa comprobada, sin transacción conocida pendiente, permite otro intento del mismo hash; un RPC inaccesible no demuestra ausencia. Tras caída antes/después de envío o actualización local, el siguiente recorrido repite estas comprobaciones. Propagación lenta puede causar más de un intento físico; se garantiza un único registro **lógico**, no «exactly once» de broadcast ni coste único de gas.

Dos workers coordinan creación local mediante actividad + UNIQUE. Para envíos desde una misma wallet, se usa exclusión entre procesos mediante GET_LOCK de MySQL, nombre acotado por proyecto/red/wallet, en conexión dedicada y con espera cero; si no se obtiene, se difiere. Bajo esa exclusión se consulta contrato y nonce pendiente y se envía. Se libera en finally/cierre de conexión, pues commit/rollback no liberan ese lock. No mantener una transacción SQL abierta ni locks de filas de negocio durante RPC. [Semántica de locks de sesión MySQL](https://dev.mysql.com/doc/refman/8.4/en/locking-functions.html).

La coordinación cubre instancias que comparten el MySQL del proyecto; no habilita usar la misma wallet desde procesos independientes ajenos al servicio. El contrato idempotente sigue siendo la defensa final. La publicación local de confirmación usa una transacción corta de evidencia y revalida su identidad; no retrocede una evidencia REGISTRADA por una carrera de un intento viejo. Límites/timeout de red acotados y liberación del lock se probarán, sin una espera de RPC dentro del negocio.

Reintento manual solo de evidencia PENDIENTE y con permiso: dispara el mismo servicio, sin nueva actividad, evidencia o hash. No garantiza disponibilidad de red. Una inconsistencia local detiene el envío y requiere revisión; nunca se recalcula y sobrescribe el original para «hacerlo coincidir».

### Verificación CU25

Primero autorización y lectura coherente de actividad/historia/evidencia; luego reconstrucción con algoritmo/versión guardados y comparación local. Cerrar cualquier transacción de lectura antes del RPC. No se usan estado actual ni timestamps del cliente. La prioridad de resultados es:

| Condición comprobada | resultado_verificacion | Efecto sobre estado |
| --- | --- | --- |
| Historia necesaria ausente o versión/algoritmo no soportado | NO_VERIFICABLE | Conserva estado |
| Documento reconstruible y hash recalculado distinto | INCONSISTENTE; no requiere RPC | Conserva estado |
| Hash local igual y estado PENDIENTE | PENDIENTE | Conserva estado, salvo reconciliación confirmada previa |
| Hash local igual, REGISTRADA y prueba externa/referencia/contrato/red coherentes | VALIDO | Conserva REGISTRADA |
| Hash local igual, REGISTRADA y consulta externa temporalmente imposible o confirmación actual no concluyente | NO_VERIFICABLE | Conserva REGISTRADA, referencia y fecha externa |
| Prueba positiva de red/contrato/hash/transacción incompatibles, referencia inválida en REGISTRADA o ausencia confirmada del registro esperado | INCONSISTENTE | Conserva REGISTRADA para revisión; no reenvío automático |

Una respuesta RPC temporalmente errónea no prueba ausencia. Se verifica la referencia exacta, no solo que «algún hash» exista en el contrato. Reorg que haga no concluyente la confirmación produce NO_VERIFICABLE; prueba de referencia desplazada/incompatible produce INCONSISTENTE. Nunca convertir REGISTRADA a PENDIENTE por indisponibilidad. Solo cuatro valores oficiales: **VALIDO, INCONSISTENTE, PENDIENTE, NO_VERIFICABLE**.

Cada comprobación completada actualiza `ultima_verificacion` con hora del servidor y `resultado_verificacion`. Petición denegada o evidencia inexistente no fabrica resultado persistido. Serializar comprobaciones de la misma evidencia mediante exclusión por evidencia sin locks de negocio durante red; reautorizar y guardar resultado bajo transacción local corta. Releer identidad y estado antes de guardar: si la reconciliación cambió PENDIENTE a REGISTRADA durante la comprobación, repetir la evaluación con el estado nuevo, sin publicar un resultado pendiente obsoleto. Si está ocupada, informar que hay una comprobación en curso sin inventar un quinto resultado. No crear actividad crítica por verificar/reintentar, evitando recursión; la auditoría general condicional de CU30 sigue diferida.

### Permisos y presentación previstos

| Permiso futuro exacto | Administrador | Encargado | Docente / Estudiante |
| --- | --- | --- | --- |
| trazabilidad.evidencias.ver | Sí | No | No |
| trazabilidad.evidencias.verificar | Sí | No | No |
| trazabilidad.reintentar | Sí | No | No |

Son códigos documentales, sin alta en BD durante esta fase. Exigir rol elegible, permiso efectivo en ese rol, cuenta/sesión vigente y guardas de servidor, sin bypass de Administrador. Verificar/reintentar requieren también poder ver la evidencia; acciones mediante petición con CSRF, nunca efecto lateral de GET. Revocación de sesión/permiso invalida la siguiente acción; no confiar en botones ocultos. El proceso automático interno no asume identidad humana ni depende de esos botones.

La futura UI muestra operación, actividad, hash, algoritmo, versión, estado, referencia externa, fechas, última verificación y resultado. Hash y referencia pueden mostrarse, pero el módulo sigue autorizado. No exponer private key, RPC API key, payload canónico completo o datos personales innecesarios en props, HTML, errores ni logs. Es una consulta de evidencias, no la pantalla general CU30. El enlace opcional de explorador se deriva de red/referencia segura.

### Estrategia de aceptación

[T-B01–T-B40](BUSINESS_RULES.md#aceptación-futura-blockchain--fase-9) son especificaciones futuras, no tests ejecutados. Suite normal offline con driver fake/mock y MySQL de pruebas aislado, fixtures sintéticos sin secretos; pruebas de protocolo/contrato con entorno EVM local controlado cuando se implemente. Integración real Sepolia separada y explícita, una vez configurados wallet exclusiva, RPC y saldo testnet. Nunca depender de Internet para la suite normal ni publicar una clave activa como fixture.

El cierre exige JCS/Unicode/null/BIGINT/fechas/textos, cuatro acciones y exclusiones, límites de privacidad externa, rollback solo por fallo local dentro de la transacción, fallos de red sin pérdida de negocio, recuperación y dos workers sin duplicación lógica, permisos/CU25 y generación local <=3 s. La Fase 8.5 solo comprueba documentos, fuentes, enlaces, alcance y secretos; no ejecuta CU24/CU25 ni afirma validación de Sepolia.

## Readiness for Phase 9

**D-12: RESUELTA PARA FASE 9. Bloqueantes documentales: 0.** Fecha de cierre: 2026-10-25. La excepción de una FK fue autorizada expresamente; no queda una autorización pendiente escondida en el esquema.

| Aspecto | Decisión cerrada |
| --- | --- |
| Fuente principal y atomicidad | MySQL; negocio + actividad EXITO/critica en la misma transacción; evidencia y red después del commit |
| Acciones | RESERVA_APROBADA, RESERVA_CANCELADA, PRESTAMO_ENTREGADO, PRESTAMO_DEVUELTO final |
| Exclusiones | Rechazos, aprobación préstamo, QR individual, IoT, incidencias/mantenimiento y LIBERADA CU23 |
| Dependencia | registros_actividad estructural y escritura crítica; CU30 general diferido |
| Esquema | Dos tablas oficiales, 13 + 11 columnas, UNIQUE actividad/hash; solo fk_registro_actor UPDATE RESTRICT autorizada |
| Algoritmo y canónico | SHA-256; JCS RFC8785; espacio-u-jcs-v1; schema espacio-u-trace/version string 1; seis claves comunes y cuatro subjects exactos |
| Representación e historia | UTF-8 sin normalización; IDs strings; UTC segundos Z; hashes secundarios; hitos inmutables sin JSON persistido |
| Privacidad | Solo hash bytes32 y metadatos de protocolo externos; no PII, IDs, códigos, fechas de negocio ni canónico |
| Red/adaptador | Sepolia/11155111, BlockchainTraceabilityDriver/EthereumSepoliaDriver, RPC configurable y guarda anti-Mainnet |
| Wallet/seguridad | Exclusiva testnet; secretos locales/gestor; ninguna generación en esta fase |
| Registry | Hash→timestamp, writer autorizado, evento indexado, consulta e inscripción idempotente |
| Referencia/confirmación | eip155:11155111/contrato/tx; N=1 configurable; receipt/contrato/red/evento coherentes |
| Recuperación | Reconciliador automático, actividad durable, UNIQUE, consulta antes de reenvío, logs por hash y timeout incierto |
| Verificación | Recalcular primero; cuatro resultados; REGISTRADA no retrocede por RPC caído; última comprobación del servidor |
| RBAC/UI | Tres permisos, inicialmente solo Administrador; evidencia mínima sin CU30 ni secretos |
| Pruebas | T-B01–T-B40, fake offline, MySQL aislado, concurrencia, privacidad y live Sepolia separado |
| Continuación | **Fase 9 puede comenzar con una nueva instrucción expresa. No comienza en esta entrega.** |

Versión/licencia del compilador estable, dependencias compatibles, dirección/ABI/bytecode efectivos, despliegue, claves y saldo testnet se prepararán y verificarán al implementar Fase 9. No son ambigüedades del contrato de negocio ni evidencia de infraestructura ya existente. Este cierre no certifica rendimiento, red externa o funcionamiento que todavía no se ha implementado.

### Verificación documental de Fase 8.5

Revisión limitada a **13 Markdown y cero archivos funcionales**. Los 217 textos de requisitos, los dos diccionarios físicos y el contrato/Readiness D-08 se compararon con main de partida y se conservaron. SQL de las dos tablas equivalente entre Word/PDF y SHA-256 originales intactos. Se comprobaron las seis claves comunes, los subjects de 7/6/9/11 campos y los 40 criterios futuros. **293 enlaces locales comprobados, sin errores.**

Escaneo de 389 archivos públicos, índice, historial Git y material temporal de esta revisión contra valores privados locales y patrones de credenciales: sin secretos detectados. Los valores usados en el control nunca se imprimen. Extracciones y ayudas de revisión se mantuvieron ignoradas dentro del workspace oficial y se retiran al terminar; no se publican copias de Word/PDF.

No se ejecutaron migraciones, seeders, pruebas funcionales, llamadas RPC, generación de wallet/clave, compilación Solidity ni cambios de configuración/BD. T-B01–T-B40 son obligaciones futuras. Las pruebas de Fase 8 conservan su evidencia histórica y no se atribuyen a esta fase documental.

Rama docs/phase-8.5-blockchain-decisions desde main/origin/main limpio f3c38ea2f2093b79941e29b5d71fe97860f1a67b. Commit documental, push e integración fast-forward autorizados, sin force push. El informe final confirma el SHA, igualdad remota y árbol limpio una vez realizadas esas operaciones.

## Aplicación D-12 en Fase 9 y excepciones autorizadas

D-12 permanece RESUELTA PARA FASE 9. Su implementación está descrita en [BLOCKCHAIN_IMPLEMENTATION](BLOCKCHAIN_IMPLEMENTATION.md). La instrucción posterior §11 fija proceso_responsable NULL para las cuatro actividades humanas. ON UPDATE RESTRICT solo fk_registro_actor conserva la autorización 8.5 y el CHECK.

El usuario autorizó canonicalize 5.1.0 por proceso Node local tras fallos RFC8785 de los candidatos PHP; builders, servicios y orquestación permanecen PHP. También autorizó temporalmente cinco entradas altas transitivas Zod exclusivamente dev del toolchain Hardhat, con runtime productivo en cero, controles y deuda D-12-DEV-ZOD. Ambas decisiones y sus límites se documentan en el informe. Ninguna autoriza vulnerabilidades productivas ni ampliar operaciones críticas. Sepolia pendiente de testETH es una dependencia externa permitida por §62/157, sin falsificar resultados.

## Notification Contract / Readiness for Phase 10

Cierre exclusivamente documental de **Fase 9.5, 2026-10-26**. No existe un identificador D pendiente específico de notificaciones en el registro: se utiliza este nombre, **sin crear D-13 ni renumerar decisiones**. D-13 conserva su significado original. Contrato RF21/CU26 RESUELTO PARA FASE 10; implementarlo requiere una nueva instrucción. Las decisiones siguientes concretan la petición oficial de Fase 9.5, no atribuyen al informe académico parámetros técnicos que este no fija.

### N1. Fuentes, alcance y precedencia

Se contrastaron Word/PDF finales: RF21, tabla 30, p. 139 (PDF 140); CU26, tabla 63, p. 212 (PDF 213); CU07–CU10, tablas 44–47, pp. 183–189; CU14–CU19, tablas 51–56, pp. 194–202; CU23, tabla 60, pp. 207–208; SQL de registros_actividad, pp. 295–297, y notificaciones, pp. 298–299 (PDF 299–300). Word y PDF contienen el mismo contrato operativo; la numeración automática de CU26 no altera su flujo. Se revisaron también las reglas, dominio, datos, matrices RF/CU, arquitectura, seguridad, plan e informes de reservas, préstamos, liberación y Blockchain. Los originales conservan las huellas de «Fuentes verificadas» y no se modifican ni publican.

RF21 genera avisos internos a partir de eventos confirmados; CU26 permite a los cuatro roles consultar exclusivamente sus propios avisos y registrar lectura. No incluye envío arbitrario entre usuarios, email, SMS, WhatsApp, push móvil, Firebase ni servicios externos. No hay tipos nuevos SISTEMA, ERROR, BLOCKCHAIN, QR o IOT. No se notifica evidencia Blockchain PENDIENTE/REGISTRADA, verificaciones ni caída RPC. CU30 general sigue diferido: sin listado global, buscador, filtros de bitácora ni auditoría administrativa completa.

### N2. Destinatarios, permisos y punto de observación

En las tablas siguientes **S** es el solicitante/usuario_afectado_id del evento; en entrega también es el receptor, por D-11. Los conjuntos administrativos se calculan con permisos efectivos de un rol activo y elegible conforme a D-04, nunca solo con rol.nombre. Todos los destinatarios, incluidos S, deben estar ACTIVO en la lectura de resolución. El ámbito administrativo vigente es por módulo/acción, sin inventar asignaciones finas a recursos.

| Conjunto | Permisos efectivos requeridos conjuntamente | Finalidad |
| --- | --- | --- |
| AR | reservas.resolver + reservas.ver_administrativas | Resolver y seguir reservas |
| AP | prestamos.resolver + prestamos.ver_administrativos | Resolver solicitudes de préstamo |
| AD | prestamos.registrar_devolucion + prestamos.ver_administrativos | Gestionar devolución, revisión y vencimiento |
| AI | incidencias.ver, con elegibilidad administrativa D-04 | Supervisar incidencias y mantenimiento |
| AL | liberacion.evaluaciones.ver_administrativas | Supervisar alertas y liberaciones |

Regla uniforme: excluir actor_id de los conjuntos administrativos por redundancia; la condición de autor nunca agrega destinatarios. Una regla directa S sí se conserva aunque S coincida con el actor, **excepto autocancelación**, que expresamente no genera aviso propio. La unión se deduplica por usuario, incluso con varios roles o reglas; si coincide S con un grupo administrativo, prevalece la plantilla directa y hay una sola fila. No se exige notificaciones.ver_propias para producir el evento: ese permiso controla la consulta posterior; no existe un permiso humano de generación ni una cuenta Sistema.

El momento normal de resolución es el procesamiento inmediatamente posterior al commit, no un supuesto snapshot histórico de permisos dentro de la transacción de negocio. El primer snapshot de lectura de la transacción, abierto solo después de adquirir el lock de actividad, fija el corte de resolución; las consultas de usuarios activos, asignaciones/roles vigentes y permisos efectivos usan ese mismo corte. La revocación/desactivación confirmada **antes** de ese corte excluye; la confirmada **después** no borra avisos ni invalida el corte ya tomado. El servidor vuelve a autorizar cada lectura/enlace. Esta regla evita prometer orden por reloj o IDs frente a commits concurrentes. No retener locks de recursos ni el mutex RBAC exclusivo para resolver avisos. La limitación de recuperación administrativa se fija en N6.

### N3. Matriz congelada de eventos, acciones y tipos

Todas las filas requieren resultado EXITO. «Rechazar correctamente» es accion RESERVA_RECHAZADA/PRESTAMO_RECHAZADO + resultado EXITO, nunca resultado técnico RECHAZO. ERROR/RECHAZO técnico no genera avisos. Cualquier acción no incluida se omite. Las abreviaturas de destinatario están definidas en N2.

| Acción | Tipo | Evento confirmado / origen | Destinatarios | Actividad crítica |
| --- | --- | --- | --- | --- |
| RESERVA_SOLICITADA | SOLICITUD | CU07: nueva PENDIENTE | AR | No, nueva |
| RESERVA_APROBADA | DECISION | CU09: PENDIENTE → APROBADA | S | Sí, reutilizar Fase 9 |
| RESERVA_RECHAZADA | DECISION | CU09: PENDIENTE → RECHAZADA | S | No, nueva |
| RESERVA_CANCELADA | CANCELACION | CU10: cancelación válida | Si actor ≠ S: S; si actor = S: AR, sin S | Sí, reutilizar Fase 9 |
| PRESTAMO_SOLICITADO | SOLICITUD | CU14: nuevo PENDIENTE | AP | No, nueva |
| PRESTAMO_APROBADO | DECISION | CU16: PENDIENTE → APROBADO | S | No, nueva |
| PRESTAMO_RECHAZADO | DECISION | CU16: PENDIENTE → RECHAZADO | S | No, nueva |
| PRESTAMO_ENTREGADO | ENTREGA | CU17: entrega confirmada | S, igual a receptor | Sí, reutilizar Fase 9 |
| DEVOLUCION_PENDIENTE_REVISION | DEVOLUCION | CU18: recepción inicial NO_DETERMINABLE/PENDIENTE_REVISION | AD | No, nueva |
| PRESTAMO_DEVUELTO | DEVOLUCION | CU18: confirmación final, directa o tras revisión | S | Sí, reutilizar Fase 9 |
| PRESTAMO_VENCIDO | VENCIMIENTO | Primera detección ENTREGADO y ahora > fin_previsto | S ∪ AD | No, nueva |
| INCIDENCIA_REGISTRADA | INCIDENCIA | CU19 o extensión CU18: nueva INCIDENCIA | AI; además S si deriva de devolución | No, nueva |
| INCIDENCIA_CERRADA | INCIDENCIA | CU19: INCIDENCIA ABIERTA → CERRADA | AI; además S si deriva de devolución | No, nueva |
| MANTENIMIENTO_REGISTRADO | INCIDENCIA | CU19: nuevo MANTENIMIENTO | AI; además S solo si existe vínculo de devolución | No, nueva |
| MANTENIMIENTO_CERRADO | INCIDENCIA | CU19: MANTENIMIENTO ABIERTA → CERRADA | AI; además S solo si existe vínculo de devolución | No, nueva |
| RESERVA_ALERTADA_LIBERACION | LIBERACION | CU23: nueva evaluación ALERTAR | S ∪ AL | No, nueva |
| RESERVA_LIBERADA | LIBERACION | CU23: evaluación LIBERAR y APROBADA → LIBERADA | S ∪ AL | No, nueva |

Se adopta expresamente DEVOLUCION_PENDIENTE_REVISION para asignar atención a AD. No avisa al solicitante en cada ciclo ni crea PRESTAMO_DEVUELTO antes de confirmar. Se adoptan las dos acciones de mantenimiento, con tipo INCIDENCIA porque no existe enum MANTENIMIENTO. La cancelación propia siempre informa a AR para seguimiento de la retirada de una solicitud/compromiso; la ajena informa solo a S. El cierre de incidencia/mantenimiento informa siempre a AI y, si está vinculada a devolución, también al solicitante de ese préstamo. No se infiere afectación de todos los usuarios con reservas/préstamos futuros del recurso.

ALERTAR conserva APROBADA y comunica posible no utilización/requiere atención; jamás dice que se liberó. LIBERADA informa siempre a S y AL. CU23 MANTENER_POR_USO y NO_DETERMINABLE no son eventos notificables iniciales. El NO_DETERMINABLE físico de CU18 es otro contexto y sí produce el aviso de revisión adoptado arriba. Cerrar una incidencia no significa que el recurso quedó disponible: se conservan todas las guardas de Fase 5.

### N4. Actividad durable, contexto y atomicidad del negocio

No hay nueva outbox: registros_actividad es la fuente durable existente. Cada nueva actividad se inserta en la misma transacción que el evento de negocio; si falla esa escritura local obligatoria, la operación no se confirma parcialmente. **Después del commit**, generación de avisos y Blockchain son consumidores independientes. Fallos al despachar, crear avisos, crear evidencia local o contactar RPC no revierten ni presentan como fallida una operación de negocio ya confirmada. Un fallo en un consumidor no impide intentar el otro. La confirmación HTTP no depende del éxito de los avisos; diagnóstico técnico saneado e intento posterior.

Las cuatro actividades críticas de Fase 9 conservan su identidad, contexto, inmutabilidad y critica=TRUE. Una aprobación, por ejemplo, produce **una actividad → una evidencia Blockchain + las notificaciones aplicables**, sin otra actividad equivalente ni cambio de canonicalización. Las trece acciones nuevas tienen critica=FALSE; no amplían el allowlist D-12. No se escriben actividades por leer/listar avisos ni por marcar lectura; eso anticiparía CU30 sin necesidad de RF21.

| Familia | Campos de actividad y responsabilidad |
| --- | --- |
| Reserva humana | actor_id = solicitante al solicitar, responsable real al resolver/cancelar; usuario_afectado_id = solicitante; recurso_id = espacio_id; reserva_id; prestamo_id/incidencia_id NULL; proceso_responsable NULL |
| Préstamo humano | actor_id = solicitante al solicitar, responsable real al resolver/entregar/recibir/confirmar; usuario_afectado_id = solicitante; recurso_id = equipamiento_id; prestamo_id; reserva_id/incidencia_id NULL; proceso_responsable NULL |
| Incidencia/mantenimiento | actor_id = quien registra/cierra; recurso_id e incidencia_id; si devolucion_id existe, prestamo_id y usuario_afectado_id derivados de devolución → préstamo; si no, ambos NULL; reserva_id NULL; proceso_responsable NULL |
| Vencimiento | actor_id NULL; proceso_responsable = scheduler:vencimientos; usuario_afectado_id = solicitante; recurso_id = equipamiento_id; prestamo_id; reserva_id/incidencia_id NULL |
| Liberación/alerta | actor_id NULL; proceso_responsable = scheduler:liberacion; usuario_afectado_id = solicitante; recurso_id = espacio_id; reserva_id; prestamo_id/incidencia_id NULL |

fecha_hora conserva el instante del evento confirmado, con reloj de servidor en America/La_Paz y precisión SQL de segundos. Los hitos humanos existentes conservan sus fechas; para alta de incidencia sin fecha de creación específica se toma el reloj de registro, no su inicio programado. Todas las filas cumplen chk_registro_responsable; no se crea usuario Sistema. descripcion de nuevas actividades permanece NULL salvo CU23, que guarda texto técnico humano seguro: `Evaluación CU23 #{evaluacion_id}; política #{politica_id}.`. Es una referencia explicativa inmutable, no una FK, snapshot de destinatarios ni estado del procesador; no se modifica una actividad para marcarla procesada. Los avisos no copian esa descripción ni los motivos libres.

La actividad CU23 se crea únicamente al insertar la evaluación nueva ALERTAR/LIBERAR, en el mismo commit que evaluación, pivotes y transición cuando corresponda. Se conserva la idempotencia D-08 por reserva/política, resultados terminales y locks/NOWAIT vigentes. Un reintento que devuelve/omite una evaluación existente no vuelve a registrar actividad. La referencia de descripcion permite localizar la evaluación exacta sin columna nueva ni unión ambigua por fecha. No se hace backfill de evaluaciones antiguas.

Los hooks futuros pertenecen a servicios transaccionales compartidos, incluidos caminos QR de entrega/devolución, no a controladores separados. Un rollback de solicitud/decisión/hito/incidencia/evaluación no deja actividad ni aviso. No alterar orden de locks de Fases 4–8; actividad nueva al final del trabajo local, despacho solo después de liberar esos locks.

### N5. Vencimiento único por préstamo

VENCIDO sigue siendo condición calculada: estado ENTREGADO y ahora estrictamente mayor que fin_previsto. En igualdad no vence. No agregar estado, cambiar custodia ni liberar equipamiento. El scheduler futuro podrá barrer cada minuto por defecto (frecuencia técnica configurable, no regla académica), en lotes de 100 candidatos por clave. La consulta inicial es una ayuda; bajo lock se revalida estado, límite y ausencia de PRESTAMO_VENCIDO/EXITO para ese préstamo.

Transacción nueva por préstamo: bloquear exclusivamente su fila; tomar tiempo actual después de esperar; leer la actividad existente mediante lectura actual; si no existe y sigue vencido, insertar una sola actividad. No tomar después locks de equipo, devolución, incidencia o RBAC, lo que invertiría el orden de operaciones humanas. La devolución utiliza el mismo lock de préstamo en su orden vigente: si confirma primero, el detector omite; si el detector confirma primero, el evento histórico de vencimiento permanece aunque luego se devuelva. La generación/reparación del aviso no vuelve a exigir ENTREGADO: comunica el hecho pasado y no inventa otro evento. Dos detectores no producen dos actividades, sin nuevo UNIQUE. Una recepción pendiente que aún conserva ENTREGADO sigue sujeta a la condición literal; no se inventa una excepción de estado. Un conflicto/deadlock del detector revierte su transacción completa; reintentar como máximo tres veces fuera de la transacción y, si persiste, dejar el candidato para el siguiente barrido con diagnóstico saneado. Los locks internos de FK no justifican invertir el orden explícito de recursos.

### N6. Generación, idempotencia y reconciliación acotada

Contratos futuros: NotificationRuleRegistry centraliza únicamente las 17 reglas; NotificationGenerationService recibe un ID de actividad durable y resuelve acción, tipo, destinatarios y plantilla. Solo admite EXITO y contexto coherente. Un evento sin regla no genera avisos. No aceptar título, texto, destinatario o tipo arbitrarios desde HTTP.

**Clave lógica: registro_actividad_id + destinatario_id + tipo.** No agregar UNIQUE, columna procesada_en ni tabla snapshot. Toda vía de inserción pasa por el mismo servicio y transacción. Protocolo:

1. Iniciar una transacción nueva después del commit de negocio; sin snapshot previo. Bloquear la fila registros_actividad por PK con FOR UPDATE.
2. Leer sus notificaciones en el primer snapshot consistente abierto después del lock, por lo que incorpora el commit del worker anterior tras cualquier espera. No usar FOR UPDATE sobre rangos vacíos de notificaciones: el coordinador es la actividad y evita locks de hueco que acoplen actividades distintas. Aplicar las guardas de inicio/ventana y cierre administrativo descritas abajo.
3. Obtener el conjunto permitido de destinatarios con estado/permisos del mismo snapshot posterior al lock, con exclusión del actor y unión de identidades. Recalcular actuales solo cuando la regla de recuperación lo permite.
4. Ordenar destinatarios por ID, comprobar la clave lógica e insertar exclusivamente faltantes. Insertar **todo el conjunto en una sola transacción**, sin commits por destinatario. Cualquier error revierte todas las nuevas filas del lote; no continuar parcialmente tras un error.
5. Commit. Un segundo worker que esperaba ve las filas confirmadas y no duplica. No hay lock global, mutex exclusivo RBAC ni locks de reserva/préstamo/equipo/incidencia durante este trabajo. Actividades distintas pueden avanzar concurrentemente.

La garantía es de aplicación entre escritores que respetan el protocolo; SQL directo fuera del servicio no queda protegido por una UNIQUE inexistente. Mantener pequeña la transacción, sin red ni trabajo Blockchain. La fila de actividad protegida y la prohibición de escritores alternativos permiten comprobar faltantes con lecturas consistentes sin bloquear rangos de notificaciones; no cambiar el aislamiento global de MySQL. Conflictos/deadlocks permiten hasta tres reintentos completos; agotamiento deja la actividad recuperable y no altera negocio.

**Estrategia temporal elegida:** ventana automática fija de 24 horas desde fecha_hora, intervalo inclusivo [evento, evento + 24 h]; es una convención técnica de este cierre, no un requisito numérico del Word/PDF. El mismo límite rige generación normal, trabajo retrasado y reconciliación. Se fijará además en configuración de despliegue el instante de habilitación inicial de RF21; solo eventos iguales/posteriores son elegibles, sin mover ese instante en reinicios ni hacer backfill de Fase 9. No se añade configuración funcional en 9.5. Dentro de la ventana:

| Evidencia persistida bajo lock | Conducta determinista |
| --- | --- |
| Ningún aviso de la actividad | Resolver S y grupos administrativos con estado/permisos actuales y crear el lote completo; es primer intento o recuperación técnica de fallo total |
| Existe al menos un aviso de la actividad | El conjunto administrativo queda cerrado: **no incorporar otros administradores** por nuevos permisos, ni siquiera dentro de la ventana. Reintentos conservan filas existentes; reparar únicamente destinatarios directos S faltantes, reconstruibles del evento y activos ahora |
| Ningún destinatario válido | No insertar fila ficticia ni aviso sin receptor. Sin marca persistente de procesamiento, permanece elegible para reintento hasta vencer la ventana; al vencer deja de explorarse |
| Evento sin regla, contexto incoherente, resultado distinto de EXITO o anterior a habilitación | Omitir; incoherencia produce diagnóstico técnico sin secretos y sin alterar actividad/negocio |
| Fuera de las 24 h o fecha futura respecto del reloj | No generar filas; las antiguas permanecen. Fuera de ventana, registrar diagnóstico acotado de recuperación no automática; reloj futuro se difiere |

Esto congela expresamente el límite del esquema: no hay snapshot histórico de permisos ni marca para distinguir «sin destinatarios» de «falló antes de insertar». Si el primer intento no dejó filas, un administrador que adquirió permiso **dentro de las 24 h** puede recibir al recuperarse; quien lo perdió antes del nuevo corte no recibe. Tras el primer lote persistido no se abre el grupo a nuevos administradores, y nunca se reconstruye por permisos adquiridos semanas después. Las cuentas que pasan a INACTIVO conservan avisos previos, pero no reciben nuevas filas; una reactivación no dispara backfill fuera de ventana.

**Reparación parcial y límite verificable:** un fallo ordinario durante generación no deja un subconjunto confirmado porque el lote es atómico. Si se detecta un conjunto persistido parcial (por ejemplo, aviso administrativo existente y S faltante), se repara S dentro de la ventana sin duplicar el administrativo. Un supuesto administrador faltante de un lote ya confirmado no puede distinguirse de un nuevo administrador sin un snapshot que el SQL no permite; **no se promete reconstruir ni ampliar ese conjunto**. No borrar avisos ni fabricar un estado de entrega para disimular esta limitación. Una pérdida/corrupción externa de filas administrativas requiere revisión técnica de datos, fuera del reconciliador automático inicial. Estos límites concretan las secciones 68–70 de la instrucción y son parte del contrato, no funcionalidades pendientes por decidir.

El comando futuro interno `notificaciones:reconciliar` se ejecutará cada minuto por defecto, con frecuencia configurable, lotes de 100 por clave y horizonte de 24 h, consultando actividades de la allowlist/EXITO desde la habilitación. Reintenta sin filas y reparaciones directas detectables; invoca el mismo servicio, nunca un insert alternativo. Ejecutarlo una o diez veces con el mismo corte de elegibilidad conserva el conjunto y leida_en. No escanea ni redistribuye todo el historial. No depende de visitar CU26, de tener testETH ni de éxito de Blockchain. Diagnósticos técnicos solo incluyen IDs/categoría y conteos, no mensajes ni motivos personales; no generan notificaciones técnicas recursivas.

### N7. Plantillas e información histórica

Plantillas fijas en texto plano. titulo y mensaje se guardan una vez; leer/reconciliar no reescribe textos existentes ni su creada_en. creada_en es el instante real de inserción; el evento mantiene fecha_hora. Todas las plantillas siguientes se completan con el sufijo común «Evento registrado: {fecha_evento} (America/La_Paz).», incluso cuando se recuperan después. {fecha_evento} proviene de actividad; {reserva}/{prestamo} de códigos inmutables del negocio; {incidencia} del ID estable, nunca de una descripción editable. No usar nombre actual del recurso, estado actual ni motivo libre para reconstruir mensajes pasados.

| Acción | Título fijo (≤160 caracteres) | Mensaje base |
| --- | --- | --- |
| RESERVA_SOLICITADA | Nueva solicitud de reserva | La solicitud de reserva {reserva} fue registrada y requiere resolución. |
| RESERVA_APROBADA | Reserva aprobada | Tu solicitud de reserva {reserva} fue aprobada. |
| RESERVA_RECHAZADA | Reserva rechazada | Tu solicitud de reserva {reserva} fue rechazada. Consulta el detalle autorizado para más información. |
| RESERVA_CANCELADA | Reserva cancelada | S: Tu reserva {reserva} fue cancelada por un responsable autorizado. / AR: El titular canceló la reserva {reserva}. |
| PRESTAMO_SOLICITADO | Nueva solicitud de préstamo | La solicitud de préstamo {prestamo} fue registrada y requiere resolución. |
| PRESTAMO_APROBADO | Préstamo aprobado | Tu solicitud de préstamo {prestamo} fue aprobada. |
| PRESTAMO_RECHAZADO | Préstamo rechazado | Tu solicitud de préstamo {prestamo} fue rechazada. Consulta el detalle autorizado para más información. |
| PRESTAMO_ENTREGADO | Equipamiento entregado | Se confirmó la entrega del equipamiento de tu préstamo {prestamo}. |
| DEVOLUCION_PENDIENTE_REVISION | Devolución pendiente de revisión | La recepción del préstamo {prestamo} quedó pendiente de revisión de su condición física. |
| PRESTAMO_DEVUELTO | Devolución confirmada | Se confirmó la devolución de tu préstamo {prestamo}. |
| PRESTAMO_VENCIDO | Préstamo vencido | S: Tu préstamo {prestamo} fue detectado como vencido. / AD: El préstamo {prestamo} fue detectado como vencido y requiere seguimiento. |
| INCIDENCIA_REGISTRADA | Incidencia registrada | AI: Se registró la incidencia #{incidencia}; consulta el detalle autorizado. / S: El equipamiento de tu préstamo {prestamo} requiere revisión; se registró una incidencia. |
| INCIDENCIA_CERRADA | Incidencia cerrada | AI: Se cerró la incidencia #{incidencia}. / S: Se cerró la incidencia vinculada a la devolución de tu préstamo {prestamo}. |
| MANTENIMIENTO_REGISTRADO | Mantenimiento registrado | AI: Se registró el mantenimiento #{incidencia}. / S: El equipamiento de tu préstamo {prestamo} requiere revisión; se registró mantenimiento. |
| MANTENIMIENTO_CERRADO | Mantenimiento cerrado | AI: Se cerró el mantenimiento #{incidencia}. / S: Se cerró el mantenimiento vinculado a la devolución de tu préstamo {prestamo}. |
| RESERVA_ALERTADA_LIBERACION | Reserva requiere atención | S: Se detectó posible no utilización de tu reserva {reserva}; requiere atención. Esta alerta no liberó la reserva. / AL: La reserva {reserva} presenta posible no utilización; requiere atención. Esta alerta no liberó la reserva. |
| RESERVA_LIBERADA | Reserva liberada | S: Tu reserva {reserva} fue liberada conforme a la política aplicable. / AL: La reserva {reserva} fue liberada conforme a la política aplicable. |

La notificación describe lo sucedido en ese instante: una aprobación histórica no cambia a cancelación, y un aviso de vencimiento puede coexistir con otro de devolución posterior. No acusa al solicitante de causar daño ni afirma disponibilidad tras cerrar mantenimiento. No copiar observaciones, solución ni motivos completos; el detalle protegido conserva su propia política. No incluir passwords, tokens, QR token/hash, HMAC, claves privadas, RPC key, CI, correos innecesarios, nombres de terceros ni payloads de sensores. Escapar texto en Vue; sin HTML arbitrario, markdown ejecutable, URL almacenada o contenido del usuario interpretado. El límite de título se valida también en aplicación.

### N8. CU26, lectura, propiedad y RBAC previsto

Único permiso nuevo previsto: **notificaciones.ver_propias**, suficiente para listar, contar, ver detalle y marcar lectura de avisos propios. No permisos administrativos globales, notificaciones.enviar ni gestión de avisos ajenos. No se crea permiso/seed en esta fase. Generación automática no necesita ese permiso ni un actor humano.

| Capacidad inicial de CU26 | Administrador | Encargado de Laboratorio | Docente | Estudiante |
| --- | --- | --- | --- | --- |
| notificaciones.ver_propias: listado, contador, detalle y lectura propios | Sí | Sí | Sí | Sí |
| Consultar o marcar avisos ajenos | No | No | No | No |
| Enviar, borrar, marcar todas o volver a no leída | No | No | No | No |

«Sí» expresa elegibilidad/asignación inicial prevista; exige cuenta activa, rol/asignación vigentes y permiso efectivo actual. La consulta siempre filtra destinatario_id = usuario autenticado en servidor, antes de resolver IDs, paginar, contar o exponer datos. ID ajeno e inexistente responden igual (404); ausencia del permiso de módulo, 403. Ser Administrador no omite propiedad. Deshabilitar una cuenta bloquea acceso y conserva historia.

leida_en NULL = no leída; con fecha = leída. La única escritura es condicional, por ID y propietario, **WHERE leida_en IS NULL**, con fecha/hora del servidor; si ya estaba leída se devuelve/conserva la primera fecha. Dos peticiones concurrentes no la reemplazan. El cliente no decide destinatario ni fecha. Sin enum de lectura, borrado, marcar todas ni retorno a no leída en el alcance inicial.

Se adopta lectura al **abrir expresamente el detalle**: GET obtiene datos sin mutar; una vez mostrado al propietario, la UI solicita POST/PATCH de lectura con CSRF al mismo control autorizado. Una precarga/prefetch o listado no dispara esa petición. También puede ofrecer «Marcar como leída» mediante esa misma operación; un fallo no muestra éxito ficticio ni afecta negocio. Abrir URL de detalle directamente usa el mismo flujo de lectura posterior al render. Permiso revocado entre GET y POST impide la escritura. No mutar en GET ni registrar lectura por cargar el contador.

Centro futuro: acceso desde navegación con nombre accesible, contador de **leida_en IS NULL propio**, lista paginada de 15 por defecto con creada_en DESC, id DESC como desempate, filtros todas/no leídas/leídas y tipo oficial. Detalle: título, mensaje, tipo, creada_en, estado/fecha de lectura y enlace contextual permitido. Consulta vacía tiene estado comprensible. El contador representa todas las propias no leídas, no solo la página/filtro. Actualizar con navegación Inertia normal y tras marcar; sin WebSockets, Reverb, Pusher, Firebase ni polling agresivo. Teclado, foco y presentación responsive forman parte de aceptación futura, sin implementar UI aquí.

El enlace se deriva en servidor de la actividad y se reautoriza en cada respuesta y al entrar al destino. Reserva → CU08; préstamo/entrega/devolución/vencimiento → CU15; incidencia → CU19 únicamente con permiso; el afectado sin incidencias.ver puede enlazar su préstamo propio si tiene acceso. Liberación → evaluación autorizada CU23 o reserva propia autorizada. No exponer IDs de terceros ni el modelo completo de actividad; no inventar acceso a CU30. Si perdió permiso del objeto, conserva lectura del mensaje histórico propio pero no enlace ni detalles protegidos. El aviso **no concede permisos** y no se regenera con el estado actual.

### N9. Esquema, rendimiento y aceptación futura

Implementación futura exacta de notificaciones: ocho columnas id, destinatario_id, registro_actividad_id, titulo, mensaje, tipo, creada_en, leida_en; tipos/defaults/nulabilidad/PK/FK/índices en DATA_MODEL. Ambos FK ON UPDATE CASCADE / ON DELETE RESTRICT. Ninguna UNIQUE extra, updated_at, read_at, deleted_at, metadata JSON ni tabla de framework alternativa. registros_actividad conserva trece columnas, seis FK y CHECK; se mantiene la excepción autorizada ON UPDATE RESTRICT solo en fk_registro_actor. Esta fase no cambia ninguna migración ni modelo.

Aprovechar idx_notificacion_destinatario(destinatario_id, leida_en) para contador/no leídas y idx_notificacion_fecha(creada_en) cuando el optimizador lo elija para fechas/orden. No prometer que un único índice cubre a la vez todos los filtros y ORDER BY; comprobar EXPLAIN con carga sintética y usuario acotado. Paginación en BD y DTO mínimo; generación/reconciliación en lotes sin cargar el historial completo, sin N+1 por permisos ni llamadas a red. Los índices FK existentes apoyan actividad/préstamo; no añadir índices por conveniencia fuera del SQL.

Los criterios **T-N01–T-N40** de [BUSINESS_RULES](BUSINESS_RULES.md#aceptación-de-notificaciones-prevista-para-fase-10) son pruebas futuras obligatorias, no resultados de 9.5. Incluyen esquema, matriz de 17 acciones, permisos/actor, fallos antes/después del commit, límites de recuperación, concurrencia MySQL real entre procesos, lectura, privacidad, vencimiento, liberación, incidencia/mantenimiento y planes de consulta. CU30, carga final y despliegue no se acreditan con estas decisiones.

## Readiness for Phase 10

| Contrato | Cierre documental |
| --- | --- |
| RF21/CU26 e identificador | Notification Contract / Readiness for Phase 10; sin nuevo D; N1 |
| Acciones, ocho tipos y destinatarios | 17 filas cerradas; S/AR/AP/AD/AI/AL y exclusiones de actor; N2–N3 |
| Permisos efectivos y RBAC | notificaciones.ver_propias para los cuatro roles, solo propios; N2/N8 |
| Actividad/outbox y criticidad | Una actividad del evento; cuatro críticas reutilizadas, trece nuevas no críticas; N4 |
| Post-commit y fallo | Consumidores de avisos/Blockchain independientes, negocio conservado; N4 |
| Idempotencia y locks | Clave actividad+destinatario+tipo; lock por actividad y lote atómico; N6 |
| Reconciliación | Ventana de 24 h, inicio RF21 estable, cierre administrativo tras primer lote, reparación directa y límites expresos; N6 |
| Vencimiento y revisión | Una actividad por préstamo, comparación estricta, revisión inicial distinta de devolución final; N3/N5 |
| Incidencias/mantenimiento | Apertura/cierre, grupos y afectado por devolución sin imputar culpa; N3/N7 |
| Liberación | ALERTAR y LIBERAR atómicos con evaluación; MANTENER/NO_DETERMINABLE excluidos; N3/N4 |
| Lectura, propiedad y enlaces | Primer timestamp, sin GET mutante, sin bypass de Administrador, reautorización del objeto; N8 |
| Privacidad y plantillas | Snapshot plano y determinista, sin datos sensibles/motivos libres; N7 |
| Concurrencia y pruebas | T-N01–T-N40 futuros, incluidos permisos en el corte y reparación parcial limitada; N9 |
| Exclusiones | Canales externos, realtime complejo, avisos técnicos Blockchain, nuevos enums/esquema y CU30 general; N1/N8/N9 |
| Sepolia | Smoke real pendiente por testETH, independiente de RF21; no se intenta resolver en 9.5 |
| Bloqueantes documentales para Fase 10 | **0**; límites de recuperación definidos, no promesas de snapshot histórico inexistente |
| Autorización de siguiente fase | **Fase 10 puede comenzar con una nueva instrucción; no iniciada ni autorizada por este cierre** |

Fase 9.5 solo modifica documentación. El estado funcional base continúa en 30 tablas académicas, 203 columnas, 61 FK y 46 permisos. La futura tabla completaría el modelo de 31 tablas/211 columnas/63 FK y el permiso previsto elevaría el catálogo a 47; esas cifras futuras no describen la base actual. La excepción temporal dev Hardhat/Zod y la validación offline de Fase 9 conservan su informe, sin declararse nuevas auditorías o pruebas funcionales en esta fase.

### Verificación documental de Fase 9.5

Revisión del 2026-10-26: 14 documentos autorizados modificados; cero cambios de código funcional, migraciones, permisos DB o configuración. Matriz comprobada: 17 acciones, 8 tipos, 4 actividades críticas reutilizadas y 13 nuevas no críticas; 17 plantillas con títulos dentro de 160 caracteres; 40 criterios T-N futuros. Los enlaces nuevos resuelven a archivos/encabezados existentes. SQL original de notificaciones y registros_actividad equivalente entre Word/PDF; huellas SHA-256 de ambos originales sin cambio. Se conservan los identificadores D y textos normativos de requisitos.

Revisión de diferencias/índice y escaneo de secretos sobre los 445 archivos públicos, diff e historial: sin hallazgos de valores privados locales ni de los formatos de secreto comprobados; .env/.env.testing y material local continúan fuera de Git. Esto no equivale a una auditoría nueva de dependencias ni a pruebas funcionales de RF21. No se ejecutaron T-N01–T-N40, cambios en MySQL, smoke Sepolia ni gestiones de testETH.

Documentos del cierre: DECISIONS, BUSINESS_RULES, DOMAIN_MODEL, DATA_MODEL, USE_CASE_MATRIX, REQUIREMENTS_TRACEABILITY, ARCHITECTURE, SECURITY_POLICY, IMPLEMENTATION_PLAN, RESERVATIONS_IMPLEMENTATION, EQUIPMENT_LOANS_IMPLEMENTATION, RELEASE_POLICY_IMPLEMENTATION, BLOCKCHAIN_IMPLEMENTATION y README. Rama de entrega: docs/phase-9.5-notification-decisions; mensaje de commit previsto: «docs: finalize notification generation decisions». El hash y la publicación se verifican en Git y en el informe final, sin insertar un hash autorreferencial en este documento.

## Aplicación del contrato RF21/CU26 — Fase 10

La nueva instrucción autorizó N1–N9. Se conserva el contrato 9.5, cierre administrativo y límite de reconstrucción histórica, sin nuevo expediente D ni excepción SQL. Los 40 criterios tienen evidencia funcional; CU30 general y Fase 11 no iniciados. [Implementación y evidencia](NOTIFICATIONS_IMPLEMENTATION.md).
