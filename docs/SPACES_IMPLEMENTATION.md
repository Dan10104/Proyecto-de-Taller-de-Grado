# Espacios académicos y disponibilidad base — Fase 3

**Estado vigente — Fase 8 implementada (2026-10-24).** El espacio usa su FK politica_liberacion_id existente; asignación/retiro protegidos por APROBADA fin>T. SpaceAvailabilityService se conserva: LIBERADA no bloquea intervalo y permanecen bloqueos/incidencias/estado operativo. No se añade lógica paralela ni se marca DISPONIBLE automáticamente. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

**Continuidad documental de Fase 7.5:** [D-08 cerrada](DECISIONS.md#configuración-versiones-y-asignación). Fase 8 usará la FK politica_liberacion_id existente; no se podrá cambiar/asignar/retirar con APROBADA cuyo fin>T. LIBERADA dejará de bloquear el intervalo mediante SpaceAvailabilityService, sin cambiar estado operativo ni ignorar incidencias/mantenimiento/bloqueos. No se modifica código en esta entrega.

**Estado histórico: Fase 5 implementada y validada (CU12–CU19), con incidencias integradas en CU06/CU07/CU09.** Los cierres anteriores conservan su contexto histórico. [Implementación, pruebas y límites actuales](EQUIPMENT_LOANS_IMPLEMENTATION.md). Fase 6 no iniciada.

Estado: **alcance de Fase 3 implementado y validado el 2026-10-23**. CU05 tiene su núcleo funcional; la integración de bitácora CU30 continúa diferida. CU06: **IMPLEMENTACIÓN PARCIAL — FASE 3**. No se inició Fase 4.

## Contrato contrastado

Se releyeron RF4/RF5 (PDF 116–117), CU05/CU06 (PDF 181–184), clases/diseño lógico (PDF 249–250, inspeccionados también visualmente), mapeo (PDF 252–253) y SQL físico. Los ocho CREATE TABLE del Word y PDF coinciden después de normalizar saltos de página y espacios. Las migraciones conservan ese SQL. Los originales mantienen las huellas registradas en DECISIONS; no se modificaron ni se publicaron sus copias o datos personales.

## Esquema y dominio

| Orden de migración | Tabla | Contrato principal |
| --- | --- | --- |
| 000007 | pisos | numero UNIQUE; sin edificio, módulo ni nuevas jerarquías |
| 000008 | recursos_institucionales | codigo UNIQUE para todos los recursos institucionales |
| 000009 | tipos_espacio | codigo UNIQUE |
| 000010 | caracteristicas_espacio | nombre UNIQUE; descripción opcional |
| 000011 | politicas_liberacion | Estructura oficial, sin comportamiento funcional |
| 000012 | espacios_academicos | recurso_id PK/FK compartida; capacidad > 0; estado oficial; política nullable |
| 000013 | espacios_caracteristicas | PK compuesta; asociaciones sin duplicados |
| 000014 | bloqueos_espacio | fin > inicio; responsable FK a usuarios; activo booleano |

Los archivos tienen prefijo `2026_10_23_` en database/migrations. Son 37 columnas, ocho FK y cuatro CHECK adicionales: total materializado de 14 tablas académicas, 66 columnas, 15 FK y seis CHECK, más migrations técnica. No hay timestamps convencionales añadidos ni tabla reservas. Se preservan tamaños, ENUM, nulabilidad, defaults, índices, UNIQUE y acciones CASCADE/RESTRICT del SQL. Migrate/fresh se verifica solo en MySQL testing protegido; desarrollo recibe migración incremental.

**Tabla incorporada anticipadamente únicamente como dependencia estructural del FK de espacios académicos.** Esta declaración corresponde a politicas_liberacion: no tiene CRUD, navegación, selector, configuración automática, evaluación ni seeder funcional. Fase 8 conserva su implementación. El modelo mínimo permite la relación y protege asignación masiva; alta de espacio deja el FK nulo y edición conserva cualquier asociación estructural existente.

Modelos: Piso, RecursoInstitucional, TipoEspacio, CaracteristicaEspacio, EspacioAcademico, BloqueoEspacio y PoliticaLiberacion. Todos declaran tablas/timestamps/casts apropiados. EspacioAcademico usa primaryKey recurso_id e incrementing=false; el ID lo genera el recurso padre. Recurso tiene hasOne espacio; piso, tipo y política tienen hasMany espacios; espacio belongsTo recurso/piso/tipo/política, belongsToMany características y hasMany bloqueos; bloqueo belongsTo espacio/responsable. La M:N simple no necesita un modelo artificial EspacioCaracteristica.

## CU05 y autorización

CU05: listado, búsqueda, filtros, detalle, alta, edición, asociaciones M:N, estado operativo, consulta/alta/desactivación de bloqueos y catálogos auxiliares integrados de pisos/tipos/características. No hay eliminación física expuesta ni incidencias CU19. El estado EN_MANTENIMIENTO sí es administrable.

La petición Fase 3 §35 precisa D-04: autorización a nivel de módulo/acción para CU05; el alcance fino por recurso sigue diferido. Esta autorización expresa sustituye para esta fase la puerta histórica que exigía acreditarlo antes de exponer recursos. No se crea una tabla de asignación encargado–espacio. Un Encargado con los permisos correspondientes puede gestionar los espacios del módulo; no se afirma que exista restricción por espacio asignado.

| Permiso | Administrador | Encargado de Laboratorio | Docente | Estudiante |
| --- | --- | --- | --- | --- |
| espacios.ver | Sí | Sí | Sí | Sí |
| espacios.gestionar | Sí | Sí | No | No |
| espacios.cambiar_estado | Sí | Sí | No | No |
| espacios.gestionar_bloqueos | Sí | Sí | No | No |

Los once permisos de seguridad conservan elegibilidad exclusiva de Administrador. Catálogo total: 15; asignación inicial por rol: 15/4/1/1. Eligibility y permiso deben proceder del mismo rol vigente. No hay bypass de Administrador. SecuritySeeder concede solo permisos recién incorporados a roles ya existentes; ejecutarlo de nuevo conserva revocaciones. Roles nuevos reciben su catálogo elegible. La edición de permisos valida elegibilidad en servidor.

AuthorizationService central, Gates, EspacioAcademicoPolicy y servicios autorizan cada operación; ocultar botones es únicamente presentación. Cambio de estado requiere su permiso también dentro de alta/edición si difiere del default/estado anterior. Gestionar catálogos requiere espacios.gestionar. La sesión vigente y la cuenta activa siguen siendo obligatorias.

SpaceManagementService crea recurso, subclase con el mismo ID y asociaciones dentro de una transacción. Fallo de subclase o pivot revierte el padre; la edición de recurso/espacio/características también es atómica. El bloqueo común de administración y la reautorización tras adquirirlo impiden usar permisos revocados durante una espera. Se conservan las invariantes del último administrador y los permisos de recuperación de Fase 2. Validación de códigos duplicados es global a recursos, con respaldo UNIQUE y respuesta comprensible.

## CU06 incremental y bloqueos

**IMPLEMENTACIÓN PARCIAL — FASE 3**: filtros por código/nombre, piso, tipo, capacidad mínima, estado y características. Consulta de un día mediante fecha/hora inicial/hora final; bloques pueden abarcar varios días. Fechas/horas locales America/La_Paz; fin > inicio; intervalos [inicio, fin).

SpaceAvailabilityService::candidates devuelve un espacio si está DISPONIBLE, cumple filtros y no existe bloqueo activo con `inicio < fin_consulta AND fin > inicio_consulta`. Las características se combinan con AND: debe tener TODAS. BLOQUEADO y EN_MANTENIMIENTO excluyen siempre. El estado general BLOQUEADO y los bloqueos temporales son independientes. 08:00–09:00 no excluye 09:00–10:00.

Los bloqueos se mantienen activos hasta desactivación explícita autorizada. Un periodo terminado deja de afectar consultas posteriores por no solaparse; no se reescribe historia al vencer. Se admiten solapamientos entre bloqueos: cada motivo conserva su registro y cualquiera activo excluye. Desactivar uno no desactiva otros. El responsable se toma de la sesión, nunca del formulario. Motivo y responsable se muestran a gestores; los lectores reciben solo periodos relevantes, sin correo, hashes ni información administrativa del bloqueo.

Pendiente Fase 4: comprobar reservas activas y conflictos de reserva. Pendiente Fase 7: dato complementario IoT. candidates concentra el punto de extensión, sin repositorios ficticios, tablas simuladas ni consultas a reservas. La vista aclara que son candidatos según estado y bloqueos actuales; no ofrece una acción Reservar funcional.

Las consultas cargan recurso, piso, tipo y características con eager loading; filtros/NOT EXISTS se ejecutan en MySQL usando los índices oficiales. El listado, disponibilidad, bloqueos y catálogos paginan 15 registros. Prueba de consulta con carga relacionada: cinco consultas constantes, sin N+1. Los filtros reinician la página. No se declara cumplido el ensayo futuro de 500 reservas ni una certificación de carga.

## Interfaces y datos de demostración

Rutas principales: /espacios, /espacios/crear, /espacios/{recurso_id}, /espacios/{recurso_id}/editar, /espacios/catalogos/{pisos|tipos|caracteristicas} y /disponibilidad. Vue/Inertia conserva navegación, mensajes, estados visuales, validación inline, etiquetas y foco. Tablas con región identificada, tabindex y desplazamiento interno; filtros adaptables y tarjetas para disponibilidad.

No se sembró inventario institucional. SecuritySeeder solo extiende RBAC; cinco factories (Piso, TipoEspacio, CaracteristicaEspacio, RecursoInstitucional, EspacioAcademico) generan datos sintéticos para testing. La demostración manual local conserva un piso sintético, un tipo, dos características, un espacio con capacidad 36 y un bloqueo desactivado. Ninguno representa inventario FICCT real; no se exportan datos de la BD al repositorio. politicas_liberacion permanece vacía en desarrollo.

## Evidencia automatizada

| Archivo | Pruebas nuevas | Cobertura |
| --- | --- | --- |
| SpaceSchemaTest | 14 | Metadatos exactos, compartición PK, FK/checks/unicidad, rollback, política solo estructural |
| SpaceManagementTest | 22 | CU05 por rol, validación, permisos separados, revocación, estado, M:N, bloques y catálogos |
| SpaceAvailabilityTest | 26 | CU06 por rol, filtros/AND, nueve escenarios temporales, privacidad, paginación, N+1 y ausencia de reservas |

Se añaden **62 tests** y se conservan los 65 de Fase 2. Solo se adaptaron sus expectativas al nuevo catálogo/tablas permitidas; no se eliminó cobertura. `php artisan test`: **127 aprobados, 1395 aserciones, cero fallos y cero omitidos**, 65,87 s. MySQL real espacio_u_testing, base/cuenta separadas y TestingDatabaseGuard intacto. Pint aprobado; `npm run build` aprobado (588 módulos); `composer audit` sin avisos de vulnerabilidad ni abandonados; `npm audit` cero vulnerabilidades. No cambian dependencias ni lockfiles. pint.json excluye únicamente herramientas/datos/evidencias locales (.tools, .local, work) del análisis de formato.

Informes locales ignorados: work/phase3/final-tests.txt y final-tests.xml, pint-check.json, build.txt, composer-audit.json y npm-audit.json. No contienen credenciales ni se publican datos de prueba.

## Evidencia manual

| Recorrido | Resultado |
| --- | --- |
| Administrador | Login, catálogos, alta/edición de espacio, capacidad, dos características y cambios de estado persistieron |
| Estados | EN_MANTENIMIENTO excluyó; restaurar DISPONIBLE permitió evaluar bloqueos |
| Bloqueos | Intervalo vacío rechazado con mensaje español; bloqueo 08:00–09:00 excluyó ese periodo y permitió desde 09:00 |
| Desactivación | Diálogo enfoca Cancelar; Escape cancela; confirmar desactiva conservando motivo, responsable e historia |
| Filtros | Piso, tipo, capacidad y dos características juntos; búsqueda y cambio de página; filtros vuelven a primera página |
| Encargado | Acceso a gestión, edición persistente y navegación permitida |
| Docente y Estudiante | Consulta con resultados; URL directa de edición/alta devolvió 403 |
| Teclado/validación | Envío con Enter, foco visible en campo inválido, etiquetas asociadas, edición de catálogo enfoca nombre y tabla desplazable con flecha |
| Consola | Sin errores ni advertencias en los recorridos inspeccionados |

Tamaños revisados: 1440×1000 escritorio (disponibilidad y catálogos), 768×1024 tablet (disponibilidad), 390×844 móvil (disponibilidad, listado, detalle, edición, catálogos y menú). Sin desbordamiento horizontal global; las tablas desplazan internamente. Se corrigieron contraste del título y reinicio de paginación detectados en esta revisión. La búsqueda literal de 0 se verifica también en los tests de filtros y catálogos. Se restableció el tamaño del navegador. Esto acredita estos recorridos, no una auditoría exhaustiva WCAG ni todos los navegadores.

Las tres cuentas sintéticas de revisión quedaron INACTIVAS mediante AdministrationService, con sesiones revocadas e historial conservado. Sus credenciales temporales se retiraron del .env local al terminar. Se conserva el administrador local para revisión; Laravel y MySQL aislado siguen disponibles en loopback y la vista permanece abierta. Los servicios MySQL80/MySQL97 existentes no se modificaron.

## Continuidad y límites

Bitácora CU30/registros_actividad, alcance fino por recurso, reservas/CU07–CU11, QR, IoT, equipamiento, préstamos, notificaciones y política funcional permanecen fuera de esta entrega. Fase 3.5 cerró documentalmente el ciclo oficial y D-05: [contrato de reservas](DECISIONS.md#contrato-de-reservas--cierre-d-05-de-fase-35). Fase 4 requiere nueva instrucción y deberá añadir APROBADAS solapadas al mismo SpaceAvailabilityService; CU07/CU09 reutilizarán su regla mediante revalidación transaccional y lecturas actuales bajo lock del espacio. PENDIENTE no bloquea; varias pendientes pueden coexistir. La evidencia de esta fase no acredita esas integraciones futuras.

El cierre de Fase 3.5 no modifica el servicio ni los escritores de CU05. La disponibilidad actual sigue siendo parcial y no consulta reservas. El futuro protocolo conserva el orden de coordinación RBAC→espacio usado por SpaceManagementService y añade la reserva después del espacio en las transiciones; el detalle y pruebas están en D-05. No se repitió la validación funcional de Fase 3 para este cambio documental.

## Actualización vigente de Fase 4

La evidencia anterior describe el cierre histórico de Fase 3. CU06 ahora está **FUNCIONALMENTE COMPLETO PARA DISPONIBILIDAD OPERATIVA; ENRIQUECIMIENTO IoT DIFERIDO A FASE 7**: `SpaceAvailabilityService` incluye reservas APROBADAS y es compartido por CU06/CU07/CU09. La interfaz permite solicitar desde un resultado autorizado. Se conserva la regresión previa; las aserciones de ausencia de reservas se actualizan al alcance nuevo. [Evidencia de reservas, concurrencia y ensayo con 500 filas](RESERVATIONS_IMPLEMENTATION.md).

## Ampliación vigente de Fase 5

CU19 ahora usa incidencias_mantenimiento para espacios y equipos mediante recurso_id. SpaceAvailabilityService añade ABIERTA al predicado existente de estado/bloqueos/reservas; CU06/CU07/CU09 comparten esa regla. SpaceManagementService impide habilitar con incidencia o bloqueo vigente. El cierre explícito no elimina bloqueos, reservas ni otras incidencias, ni levanta mantenimiento administrativo sin aptitud/permisos. [Integración y pruebas](EQUIPMENT_LOANS_IMPLEMENTATION.md#cu19-y-espacios). Se comprobó en navegador la exclusión del espacio y su recuperación tras cierre autorizado; la regresión completa pasa. IoT sigue pendiente.

## Preparación informativa IoT — Fase 6.5

[D-10 y puerta de Fase 7](DECISIONS.md#readiness-for-phase-7) cerradas documentalmente; no se cambia CU05/CU06 ni SpaceAvailabilityService. Fase 7 puede aportar tipo/valor/hora y lectura reciente/NO_DETERMINABLE a lectores autorizados de CU06, sin credenciales, identificador de sensor, administración o historial técnico. Deben ser recientes tanto medición como recepción; silencio nunca significa libre.

IoT no cambia estado operativo, reserva, bloqueo o incidencia, ni aprueba/cancela/libera. Cambio de código del recurso exige actualizar configuración del dispositivo; persistencia usa asociación actual validada, histórico inmutable. Locks compartidos de espacio/recurso de ingestión son compatibles entre sensores; CU05 no debe adquirir sensor después del espacio. CU23 sigue exclusivamente en Fase 8.


## Actualización de Fase 7

CU06 incorpora lecturas ambientales mínimas. El conjunto de candidatos y las reglas de estados, bloqueos, reservas e incidencias permanecen intactos; ausencia o falla IoT produce NO_DETERMINABLE. La evidencia histórica de Fase 3 conserva su alcance. [Implementación y verificación](IOT_IMPLEMENTATION.md).


## Límite de asistencia previsto — Fase 11.5

Solo explicar catálogo, estado operativo y consulta temporal. No enviar espacios concretos, resultados de disponibilidad o evidencia de ocupación; remitir a los módulos autorizados, sin calcular otra disponibilidad. [Contrato CH1–CH12](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12). Cambio exclusivamente documental; Fase 12 NO iniciada.

## Continuidad documental de RF25/CU30 — Fase 12.5

El [contrato AC1–AC15](DECISIONS.md#activity-audit-contract--readiness-for-cu30) cubre creación/edición/estado de espacios, bloqueo/desactivación y ocho acciones de alta/edición de catálogos (incluye tipo de equipamiento en su módulo). No auditar disponibilidad ni cada lectura. Cambios reales, precedencia de estado sobre edición, R correcto y motivo libre excluido. Hooks futuros en SpaceManagementService y flujo de catálogos dentro de transacción; no alterar reglas de disponibilidad o historial.

Cambio exclusivamente documental. CU30 **NO IMPLEMENTADO**; implementación requiere nueva instrucción. La evidencia funcional anterior conserva su fecha y no se presenta como verificación nueva.


## Integración de auditoría — Fase 12.6

SpaceManagementService registra cinco acciones de espacio/bloqueo y seis de pisos/tipos/características. saveCatalog concentra ahora la escritura antes alojada en el callback del controlador. Alta/estado/edición siguen precedencia AC4; no-op no duplica actividad, motivo libre no se copia y fallo de auditoría revierte negocio. Disponibilidad y consultas no generan actividad; las reglas existentes permanecen.

[Implementación RF25/CU30, pruebas y resultados de integración](ACTIVITY_AUDIT_IMPLEMENTATION.md). Los cierres de fases anteriores conservan su alcance histórico. Fase 13 no iniciada.
