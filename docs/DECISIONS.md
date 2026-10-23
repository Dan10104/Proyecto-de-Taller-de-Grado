# Registro de decisiones y auditoría documental

Fecha: 2026-10-23. Fase 0.5: contratos de implementación congelados según la instrucción del usuario. Se conservan los 17 expedientes D-01–D-17 y su conflicto original; Git conserva la redacción inicial de Fase 0. No se cambian el Word/PDF, el alcance funcional ni el stack. Fase 1 técnica completada por instrucción posterior; D-15 y la configuración de D-16 tienen evidencia en [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md). Fase 1.5 documental completada: D-04/D-07 resueltas para el núcleo web de Fase 2, todavía no iniciado.

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

Esta clasificación conserva la fotografía histórica de Fase 0.5, con **un único estado por expediente en ese cierre**. Los estados vigentes de D-04 y D-07 se actualizan en sus fichas y en Readiness for Phase 2; no se reescribe el recuento histórico. La clasificación cuenta expedientes de Fase 0, no cada parámetro interno. Una convención puede dejar parámetros posteriores expresamente enumerados; eso no los declara resueltos ni bloquea la fundación.

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
| D-05 | Vacíos y precisión temporal | CONVENCIÓN DE IMPLEMENTACIÓN | 3–5; servicios de disponibilidad y transición. |
| D-06 | Diferencia entre modelo conceptual y diseño de datos | RESUELTA | 2–10 según tabla. |
| D-07 | Decisión técnica pendiente: autenticación y sesión | DIFERIDA A UNA FASE POSTERIOR | 2 antes de CU01–CU02; estudio de compatibilidad permitido en 1 cuando se autorice. |
| D-08 | Vacío de ejecución de liberación | DIFERIDA A UNA FASE POSTERIOR | 8; contrato con monitoreo en 7. |
| D-09 | Vacío de contrato QR y correlación | DIFERIDA A UNA FASE POSTERIOR | 6; prever contratos de entrega/devolución en 5. |
| D-10 | Vacío de contrato IoT | DIFERIDA A UNA FASE POSTERIOR | 7 antes de ingestión; coordinación con 8. |
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
| Estado | RESUELTA |
| Fase afectada | 2–12 según entidad. |
| Detalle diferido / límite de la resolución | Las políticas temporales de transiciones no especificadas son parámetros posteriores de D-05, no nuevos estados. |
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
| Estado | CONVENCIÓN DE IMPLEMENTACIÓN |
| Fase afectada | 3–5; servicios de disponibilidad y transición. |
| Detalle diferido / límite de la resolución | Antes de Fase 4 fijar parámetros temporales de cancelación y disparador de FINALIZADA; motivos cuando corresponda según CU09/CU10/CU16, sin convertir NULL en NOT NULL. No bloquean Fase 1. |
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
| Decisión vigente de Fase 0.5 | Congelar reserva aprobada aplicable, política, tolerancia y evidencias; uso confirmado mantiene; datos insuficientes/contradictorios o falta de IoT necesario no liberan automáticamente. Guardar evaluación con resultado exacto; ALERTAR no cambia reserva a LIBERADA; solo AUTOMATICA y condiciones inequívocas permiten LIBERAR. No mutar reglas históricas usadas sin preservar versión aplicable. |
| Justificación | El CU23 exige suficiente evidencia y la política limita la automatización; falla de sensor no prueba desocupación. No se añade una relación de persistencia. |
| Estado | DIFERIDA A UNA FASE POSTERIOR |
| Fase afectada | 8; contrato con monitoreo en 7. |
| Detalle diferido / límite de la resolución | Cerrar disparador desde monitoreo incluso sin nuevo evento, umbral de frescura NULL y combinación QR/IoT; garantizar referencia histórica de política versionada usando el modelo. No bloquea Fase 1. |
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
| Impacto | Emisión, vigencia concreta y correlación de intentos requieren detalle, pero el propósito y exclusividad ya están definidos. |
| Decisión vigente de Fase 0.5 | QR identifica recurso/operación y no autentica ni autoriza. Servidor valida usuario, permiso, código, recurso esperado, operación, estado y tiempo aplicable. Código con exactamente un objetivo recurso/reserva/préstamo. USO_ESPACIO exige reserva y excluye préstamo/entrega/devolución; ENTREGA o DEVOLUCION exige préstamo y excluye reserva y el enlace del tipo contrario. Entrega/devolución enlazadas son opcionales y únicas; no relajar esos constraints. |
| Justificación | Aplicación directa de CU y SQL; tener una cámara o conocer el token nunca habilita una operación protegida. Un intento sin contexto válido no se fuerza a una fila que viole el modelo. |
| Estado | DIFERIDA A UNA FASE POSTERIOR |
| Fase afectada | 6; prever contratos de entrega/devolución en 5. |
| Detalle diferido / límite de la resolución | Cerrar emisión/renovación, vencimiento, política de QR requerido y manejo de reescaneos/correlación. No añadir un CRUD ni uso único por suposición. No bloquea Fase 1. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

### D-10 — Vacío de contrato IoT

| Campo | Registro |
| --- | --- |
| ID | D-10 |
| Título | Vacío de contrato IoT |
| Fuente A | RF16–17; CU21–22; IH/IC/RD IoT. |
| Fuente B | sensores_iot, eventos_ocupacion y Fase 0.5 §12. |
| Conflicto original de Fase 0 | HTTP/MQTT son alternativas; faltan esquema final, autenticación de dispositivo, unidades/significado de valor, repetidos, eventos tardíos y política de reloj/frescura. |
| Tratamiento registrado en Fase 0 (histórico) | Fijar contrato del piloto y pruebas. Preservar espacio histórico, distinguir fecha_evento/fecha_recepcion y aceptar solo sensores habilitados. Elegir transporte sin inventar broker. |
| Impacto | Formato/transporte/seguridad y semántica de lecturas aún dependen del piloto. |
| Decisión vigente de Fase 0.5 | Congelar sensor registrado, HABILITADO y asociado; mide PRESENCIA/OCUPACION, nunca personas. Conservar espacio histórico y ambas fechas. Sin lectura reciente/suficiente se calcula NO_DETERMINABLE; no crear enum de ocupación en espacios ni inferir LIBRE. |
| Justificación | El SQL ya distingue estado del sensor, tipo de evento y resultado de evaluación. Salud del enlace/frescura son datos derivados, no estados persistidos adicionales. |
| Estado | DIFERIDA A UNA FASE POSTERIOR |
| Fase afectada | 7 antes de ingestión; coordinación con 8. |
| Detalle diferido / límite de la resolución | Elegir HTTP o MQTT, payload, credenciales privadas, unidades, reintentos/deduplicación y tratamiento de fechas/frescura. No bloquea Fase 1. |
| Bloquea iniciar Fase 1 | No |
| Fecha de adopción | 2026-10-23; instrucción Fase 0.5 |

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
| Estado | RESUELTA |
| Fase afectada | 5 y rama QR de 6. |
| Detalle diferido / límite de la resolución | Ninguno sobre la secuencia; no hay funcionalidad implementada. |
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
| Decisión vigente de Fase 0.5 | Congelar MySQL confirmado → datos mínimos permitidos → representación consistente → hash → evidencia local → envío externo → referencia. Privacidad antes de transmitir; no nombres/correos/contraseñas/datos completos. Caída externa conserva operación y evidencia PENDIENTE. Representación determinista, orden estable, fechas estables, UTF-8 y version_canonica obligatorios; no fijar ahora algoritmo/serialización exactos. |
| Justificación | Separar atomicidad local de dependencia externa satisface CU24 y R-DISP-06; version_canonica permite verificación reproducible. No adoptar SHA-256 de un boceto como decisión automática. |
| Estado | DIFERIDA A UNA FASE POSTERIOR |
| Fase afectada | 9, antes de generación/verificación funcional. |
| Detalle diferido / límite de la resolución | Definir campos permitidos, canonicalización exacta, algoritmo, proveedor, operaciones críticas concretas entre las extensiones del CU, idempotencia y recuperación. No bloquea Fase 1. |
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
| Detalle diferido / límite de la resolución | Antes de 13 fijar protocolo de medición y objetivo de login si se requiere; retención de eventos antes de 7/14. Formato canónico de fechas antes de 9. Ninguno bloquea Fase 1. |
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
| D-04 | Alcance fino por recurso DIFERIDO EN PARTE; RBAC y convención cerrados para Fase 2, sin nueva tabla inferida | Antes de habilitar el flujo funcional del recurso; catálogo trazable como trabajo de Fase 2 |
| D-05 | Plazos de cancelación, disparador de FINALIZADA y condiciones operativas de motivos | Antes del flujo correspondiente en Fase 4/5; intervalos y estados persistidos ya fijados |
| D-07 | Autenticación móvil/API y almacenamiento distribuido; web ya resuelta | Cuando se autorice el cliente/API; despliegue antes de Fase 14 |
| D-08 | Ejecución temporal de monitoreo/liberación, umbrales y combinación de evidencia, conservación de versiones aplicadas | Fase 8, con contrato de datos preparado en 7 |
| D-09 | Ciclo de emisión/vigencia, obligatoriedad QR por operación y correlación de intentos | Fase 6; ninguna relajación de CHECK/UNIQUE |
| D-10 | Transporte y contrato del piloto, autenticación, unidades, repetidos y frescura | Fase 7 antes de ingestión funcional |
| D-12 | Canonicalización exacta, campos permitidos, algoritmo, proveedor y recuperación | Antes de Fase 9 funcional; determinismo/orden/fechas/UTF-8/version_canonica ya obligatorios |
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
