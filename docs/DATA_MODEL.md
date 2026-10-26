# Modelo de datos documentado

**Estado vigente — Fase 11 implementada y verificada (2026-10-26).** RF22/CU27 y RF23/CU28: 747 tests / 9111 assertions; QA y auditorías productivas aprobadas. [Implementación, scopes, formatos y evidencia](DASHBOARD_REPORTING_IMPLEMENTATION.md). Sin nuevas tablas ni índices; CU30 NO IMPLEMENTADO y Fase 12 NO iniciada. Los cierres anteriores son históricos.

**Estado histórico — Fase 10.5 documental (2026-10-26).** RF22/CU27 y RF23/CU28: [contrato de dashboard y reportes cerrado](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11), **0 bloqueantes documentales**. Fase 11 NO iniciada; CU30 NO IMPLEMENTADO. Fase 10 funcional se conserva, sin cambios de código, datos, permisos o dependencias. Los cierres anteriores mantienen su contexto histórico.

**Estado histórico funcional — Fase 10 (2026-10-26).** RF21/CU26 implementados y verificados: 17 reglas, avisos propios, primera lectura y recuperación de 24 h. [Informe y evidencia](NOTIFICATIONS_IMPLEMENTATION.md). CU30 general y Fase 11 no iniciados. Sepolia real sigue pendiente de testETH. Los apartados de cierres anteriores conservan su contexto histórico.

**Estado histórico al cierre de Fase 9 implementada (2026-10-25/26).** RF19/CU24 y RF20/CU25, cuatro actividades críticas, dos tablas y tres permisos implementados conforme a D-12. [Implementación, pruebas offline, excepción temporal dev de Zod y Sepolia pendiente de testETH](BLOCKCHAIN_IMPLEMENTATION.md). CU30 general y Fase 10 permanecen pendientes. Las secciones de cierres anteriores conservan su contexto histórico.

## Compatibilidad MySQL para Fase 9 — excepción autorizada

La revisión de Fase 8.5 identifica `actor_id` simultáneamente en `chk_registro_responsable` y en `fk_registro_actor ... ON UPDATE CASCADE`. MySQL impide esa combinación de CHECK y acción referencial. [Manual oficial](https://dev.mysql.com/doc/refman/9.7/en/create-table-check-constraints.html).

**Autorización expresa del usuario, 2026-10-25: «Autorizar RESTRICT solo en fk_registro_actor».** En la futura migración, cambiar únicamente esa FK de ON UPDATE CASCADE a **ON UPDATE RESTRICT**, conservando ON DELETE RESTRICT, el CHECK de actor/proceso, todas las columnas, índices y las otras seis FK de ambas tablas. Impide renumerar usuarios con actividad histórica, operación que la aplicación no ofrece. No modifica Word/PDF ni el diccionario fuente inferior. La autorización previa de siete FK QR no se amplía: esta es una excepción independiente y limitada a una FK nueva.

Estado: aprobada documentalmente para Fase 9; no se ejecutó DDL ni se creó migración. Cero bloqueantes documentales de compatibilidad pendientes para estas dos tablas.

**Estado al cierre funcional de Fase 8 (2026-10-24).** Tres migraciones 000026–000028 materializan evaluaciones_liberacion, evaluaciones_eventos_ocupacion y evaluaciones_validaciones_qr. Totales instalados: 28 tablas académicas / 179 columnas / 54 FK, más migrations técnica. Las 25 migraciones anteriores y los diccionarios físicos se conservan. El modelo completo previsto sigue siendo 31 tablas / 211 columnas / 63 FK. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

**Estado histórico: Fase 7 implementada conforme a D-10.** CU21/CU22 y evidencia informativa en CU06; dos tablas IoT y tres permisos nuevos. [Implementación, seguridad, pruebas y límites](IOT_IMPLEMENTATION.md). CU23 y Fase 8 no iniciados.

La Fase 6 conserva su aceptación y su límite de corroboración física descritos en [QR_IMPLEMENTATION.md](QR_IMPLEMENTATION.md). Los cierres documentales de Fase 6.5 y anteriores se conservan como evidencia histórica; sus criterios futuros se verifican ahora en Fase 7.

Estado: contrato documental congelado en Fase 0.5; **no es una migración ni un SQL para ejecutar**. Fuentes: figura 108 (clases), figura 109 (BD lógica), tablas 68–98 (mapeo), 99–129 (volúmenes/diccionario) y script del cap. VII, pp. 269–299 (PDF 270–300).

El script declara el esquema `ficct_reservas_prestamos`, `utf8mb4`, colación `utf8mb4_unicode_ci` e InnoDB. Se identificaron **31 tablas, 211 columnas y 63 claves foráneas**. Las PK simples `id` son BIGINT UNSIGNED autoincrementales; los subtipos comparten `recurso_id` sin autoincremento y cuatro asociaciones tienen PK compuesta. No se añaden `created_at`, `updated_at`, borrado lógico ni tablas de un starter kit sin revisar compatibilidad.

En Fase 1 se creó únicamente el esquema vacío de desarrollo `espacio_u`, nombre autorizado por el usuario. Es un alias local del destino del contrato; no se ejecutó el SQL del informe ni se crearon las 31 tablas, migraciones, seeders o tablas de autenticación del skeleton. Charset, colación y motor conservan la referencia anterior.

## Correspondencia clase → tabla → clave → reglas → CU

Los CU se vinculan por lectura/escritura directa de los flujos; es trazabilidad analítica, no una asociación adicional en el SQL. Los reportes y dashboard pueden consultar estas tablas según permisos. Autenticación y auditoría son transversales.

| Concepto | Tabla | PK | Regla principal | CU directos |
| --- | --- | --- | --- | --- |
| Usuario | `usuarios` | `id` | Correo único; contraseña como hash; desactivar conservando historia. | CU01, CU02, CU03, CU04 |
| Rol | `roles` | `id` | Nombre único; no confundir rol con permiso ni con el ámbito de recursos. | CU01, CU03, CU04 |
| Permiso | `permisos` | `id` | Identificador técnico `codigo`, único; corrección ASCII resuelta en D-01. | CU01, CU04 |
| Asociación Rol–Permiso | `roles_permisos` | `rol_id, permiso_id` | PK compuesta evita repetir la misma asociación rol–permiso. | CU01, CU04 |
| AsignacionRol | `asignaciones_roles` | `id` | Conservar usuario, rol, asignador, fechas y retirador opcional; evaluar roles/permisos como conjuntos sin agregar UNIQUE a la historia (D-17). | CU01, CU03, CU04 |
| SesionAcceso | `sesiones_acceso` | `id` | Token hash único; expiración posterior a creación; revocación conserva historia. | CU01, CU02 |
| Piso | `pisos` | `id` | Número único; cada espacio apunta a un piso. | CU05, CU06 |
| RecursoInstitucional (abstracto) | `recursos_institucionales` | `id` | Código único compartido por espacios y equipamiento; superclase abstracta del diseño. | CU05, CU12, CU19, CU11, CU20 |
| TipoEspacio | `tipos_espacio` | `id` | Código único; catálogo, sin crear una entidad general Aula. | CU05, CU06 |
| CaracteristicaEspacio | `caracteristicas_espacio` | `id` | Nombre único; características M:N con los espacios. | CU05, CU06 |
| PoliticaLiberacion | `politicas_liberacion` | `id` | Nombre/version únicos; reglas inmutables por versión y validación de fuentes/antigüedad en D-08 de Fase 7.5. | CU05, CU23 |
| EspacioAcademico | `espacios_academicos` | `recurso_id` | PK compartida con recurso; capacidad positiva; piso y tipo obligatorios; política opcional. | CU05, CU06, CU07, CU09, CU10, CU11, CU22, CU23 |
| Asociación Espacio–Característica | `espacios_caracteristicas` | `espacio_id, caracteristica_id` | Una fila por par espacio/característica; no duplicar valores. | CU05, CU06 |
| BloqueoEspacio | `bloqueos_espacio` | `id` | Intervalo estricto fin>inicio; responsable y motivo; considerar solo bloqueos aplicables. | CU05, CU06, CU07, CU09 |
| TipoEquipamiento | `tipos_equipamiento` | `id` | Código único; tipos se incorporan como registros. | CU12, CU13 |
| EquipamientoTecnologico | `equipamientos_tecnologicos` | `recurso_id` | PK compartida; serie opcional única; ubicación base opcional; estado operativo según enum. | CU12, CU13, CU14, CU16, CU17, CU18, CU19, CU20 |
| Reserva | `reservas` | `id` | Código único; solicitante/espacio obligatorios; fin>inicio; impedir solapamiento en servidor y transacción. | CU06, CU07, CU08, CU09, CU10, CU11, CU23 |
| Prestamo | `prestamos` | `id` | Código único; equipo/solicitante obligatorios; fin_previsto>inicio_previsto; aprobar solo si disponible. | CU13, CU14, CU15, CU16, CU17, CU18, CU20 |
| EntregaPrestamo | `entregas_prestamo` | `id` | Una entrega por préstamo; entregador y receptor obligatorios; comprobación física y estado aprobado. | CU17, CU18, CU20 |
| DevolucionPrestamo | `devoluciones_prestamo` | `id` | Una devolución por préstamo; recibido_por obligatorio, confirmado_por opcional; condición incierta conserva revisión. | CU18, CU19, CU20 |
| IncidenciaMantenimiento | `incidencias_mantenimiento` | `id` | Recurso y reportante obligatorios; devolución y cierre opcionales; fechas coherentes; bloquear uso incompatible. | CU06, CU13, CU17, CU18, CU19 |
| CodigoQR | `codigos_qr` | `id` | Exactamente un objetivo: recurso, reserva o préstamo; hash único; vigencia válida. | CU11, CU20 |
| ValidacionQR | `validaciones_qr` | `id` | Recurso esperado y validador obligatorios; contexto reserva/préstamo y operación coherentes; enlace único a entrega/devolución. | CU11, CU17, CU18, CU20, CU23 |
| SensorIoT | `sensores_iot` | `id` | Identificador único; espacio actual obligatorio; solo HABILITADO admite eventos válidos. | CU21, CU22, CU23 |
| EventoOcupacion | `eventos_ocupacion` | `id` | Sensor y espacio histórico obligatorios; conservar fecha de evento y recepción; no reescribir al reasignar sensor. | CU22, CU23 |
| EvaluacionLiberacion | `evaluaciones_liberacion` | `id` | Reserva/política obligatorias; resultado exacto y evidencias enlazadas; no liberar con datos insuficientes. | CU23 |
| Asociación Evaluación–Evento | `evaluaciones_eventos_ocupacion` | `evaluacion_id, evento_id` | PK compuesta; conserva los eventos usados en cada evaluación. | CU23 |
| Asociación Evaluación–Validación | `evaluaciones_validaciones_qr` | `evaluacion_id, validacion_id` | PK compuesta; conserva las validaciones usadas en cada evaluación. | CU23 |
| RegistroActividad | `registros_actividad` | `id` | Actor o proceso responsable obligatorio; operación, fecha, resultado e indicador crítica; preservar contexto histórico. | CU03, CU04, CU05, CU07, CU09, CU10, CU12, CU14, CU16, CU17, CU18, CU19, CU21, CU23, CU24, CU25, CU26, CU28, CU30 |
| EvidenciaTrazabilidad | `evidencias_trazabilidad` | `id` | Una evidencia por registro de actividad; hash único; algoritmo/versión y referencia; caída externa deja PENDIENTE. | CU24, CU25 |
| Notificacion | `notificaciones` | `id` | Destinatario y actividad obligatorios; leida_en nullable; lectura exclusivamente autorizada al destinatario. | CU26 |

## Claves, cardinalidades y diccionario

Cada FK de abajo indica: **fila hija → padre** y **padre → filas hijas**. NULL permite 0..1 padre; NOT NULL exige 1. En el extremo padre, la FK no obliga a que exista una fila hija. UNIQUE de una FK o una PK compartida limita el extremo hijo a 0..1; en otro caso es 0..*. Las tablas puente expresan M:N mediante dos relaciones 1:N. Todas las FK documentadas utilizan ON UPDATE CASCADE; se detalla ON DELETE por relación. Se conservan tipos, PK, FK, NULL, defaults, CHECK, UNIQUE e índices. Única normalización del contrato técnico: `permisos.codigo` (D-01). El SQL fuente declara originalmente `código` y su UNIQUE referencia `codigo`; se documenta el conflicto sin modificar los originales. Todos los identificadores de implementación serán ASCII, sin tildes y snake_case cuando corresponda.

### usuarios

Concepto: **Usuario**. Fuentes: tablas 68 y 99; script `usuarios`. Regla: Correo único; contraseña como hash; desactivar conservando historia.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `nombres` | `VARCHAR(120) NOT NULL` |
| `apellidos` | `VARCHAR(120) NOT NULL` |
| `correo` | `VARCHAR(190) NOT NULL` |
| `password_hash` | `VARCHAR(255) NOT NULL` |
| `estado` | `ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'` |
| `fecha_registro` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |

Sin claves foráneas.

Restricciones e índices documentados:

- `CONSTRAINT pk_usuarios PRIMARY KEY (id)`
- `CONSTRAINT uq_usuarios_correo UNIQUE (correo)`

### roles

Concepto: **Rol**. Fuentes: tablas 69 y 100; script `roles`. Regla: Nombre único; no confundir rol con permiso ni con el ámbito de recursos.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `nombre` | `VARCHAR(100) NOT NULL` |
| `descripcion` | `VARCHAR(255) NULL` |

Sin claves foráneas.

Restricciones e índices documentados:

- `CONSTRAINT pk_roles PRIMARY KEY (id)`
- `CONSTRAINT uq_roles_nombre UNIQUE (nombre)`

### permisos

Concepto: **Permiso**. Fuentes: tablas 70 y 101; script `permisos`. Regla: Identificador técnico `codigo`, único; corrección ASCII resuelta en D-01.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `codigo` | `VARCHAR(100) NOT NULL` |
| `nombre` | `VARCHAR(120) NOT NULL` |
| `descripcion` | `VARCHAR(255) NULL` |

Sin claves foráneas.

Restricciones e índices documentados:

- `CONSTRAINT pk_permisos PRIMARY KEY (id)`
- `CONSTRAINT uq_permisos_codigo UNIQUE (codigo)`

### roles_permisos

Concepto: **Asociación Rol–Permiso**. Fuentes: tablas 71 y 102; script `roles_permisos`. Regla: PK compuesta evita repetir la misma asociación rol–permiso.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `rol_id` | `BIGINT UNSIGNED NOT NULL` |
| `permiso_id` | `BIGINT UNSIGNED NOT NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `rol_id` | `roles.id` | 1 | 0..* | CASCADE |
| `permiso_id` | `permisos.id` | 1 | 0..* | CASCADE |

Restricciones e índices documentados:

- `CONSTRAINT pk_roles_permisos PRIMARY KEY (rol_id, permiso_id)`

### asignaciones_roles

Concepto: **AsignacionRol**. Fuentes: tablas 72 y 103; script `asignaciones_roles`. Regla: Conservar usuario, rol, asignador, fechas y retirador opcional; evaluar roles/permisos como conjuntos sin agregar UNIQUE a la historia (D-17).

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `usuario_id` | `BIGINT UNSIGNED NOT NULL` |
| `rol_id` | `BIGINT UNSIGNED NOT NULL` |
| `asignado_por_id` | `BIGINT UNSIGNED NOT NULL` |
| `retirado_por_id` | `BIGINT UNSIGNED NULL` |
| `fecha_asignacion` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `fecha_retiro` | `DATETIME NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `usuario_id` | `usuarios.id` | 1 | 0..* | RESTRICT |
| `rol_id` | `roles.id` | 1 | 0..* | RESTRICT |
| `asignado_por_id` | `usuarios.id` | 1 | 0..* | RESTRICT |
| `retirado_por_id` | `usuarios.id` | 0..1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_asignaciones_roles PRIMARY KEY (id)`
- `CONSTRAINT chk_asigrol_fechas CHECK (fecha_retiro IS NULL OR fecha_retiro >= fecha_asignacion)`
- `INDEX idx_asigrol_usuario (usuario_id)`
- `INDEX idx_asigrol_rol (rol_id)`

### sesiones_acceso

Concepto: **SesionAcceso**. Fuentes: tablas 73 y 104; script `sesiones_acceso`. Regla: Token hash único; expiración posterior a creación; revocación conserva historia.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `usuario_id` | `BIGINT UNSIGNED NOT NULL` |
| `token_hash` | `VARCHAR(255) NOT NULL` |
| `creada_en` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `expira_en` | `DATETIME NOT NULL` |
| `revocada_en` | `DATETIME NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `usuario_id` | `usuarios.id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_sesiones_acceso PRIMARY KEY (id)`
- `CONSTRAINT uq_sesiones_token_hash UNIQUE (token_hash)`
- `CONSTRAINT chk_sesion_expiracion CHECK (expira_en > creada_en)`
- `INDEX idx_sesion_usuario (usuario_id)`

### pisos

Concepto: **Piso**. Fuentes: tablas 74 y 105; script `pisos`. Regla: Número único; cada espacio apunta a un piso.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `numero` | `INT NOT NULL` |
| `nombre` | `VARCHAR(100) NOT NULL` |

Sin claves foráneas.

Restricciones e índices documentados:

- `CONSTRAINT pk_pisos PRIMARY KEY (id)`
- `CONSTRAINT uq_pisos_numero UNIQUE (numero)`

### recursos_institucionales

Concepto: **RecursoInstitucional (abstracto)**. Fuentes: tablas 75 y 106; script `recursos_institucionales`. Regla: Código único compartido por espacios y equipamiento; superclase abstracta del diseño.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `codigo` | `VARCHAR(60) NOT NULL` |
| `nombre` | `VARCHAR(160) NOT NULL` |

Sin claves foráneas.

Restricciones e índices documentados:

- `CONSTRAINT pk_recursos_institucionales PRIMARY KEY (id)`
- `CONSTRAINT uq_recurso_codigo UNIQUE (codigo)`

### tipos_espacio

Concepto: **TipoEspacio**. Fuentes: tablas 76 y 107; script `tipos_espacio`. Regla: Código único; catálogo, sin crear una entidad general Aula.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `codigo` | `VARCHAR(60) NOT NULL` |
| `nombre` | `VARCHAR(120) NOT NULL` |

Sin claves foráneas.

Restricciones e índices documentados:

- `CONSTRAINT pk_tipos_espacio PRIMARY KEY (id)`
- `CONSTRAINT uq_tipo_espacio_codigo UNIQUE (codigo)`

### caracteristicas_espacio

Concepto: **CaracteristicaEspacio**. Fuentes: tablas 77 y 108; script `caracteristicas_espacio`. Regla: Nombre único; características M:N con los espacios.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `nombre` | `VARCHAR(120) NOT NULL` |
| `descripcion` | `VARCHAR(255) NULL` |

Sin claves foráneas.

Restricciones e índices documentados:

- `CONSTRAINT pk_caracteristicas_espacio PRIMARY KEY (id)`
- `CONSTRAINT uq_caracteristica_nombre UNIQUE (nombre)`

### politicas_liberacion

Concepto: **PoliticaLiberacion**. Fuentes: tablas 78 y 109; script `politicas_liberacion`. Regla: Nombre/version únicos; tolerancia y antigüedad no negativas; versión histórica pendiente de precisar.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `nombre` | `VARCHAR(120) NOT NULL` |
| `version` | `INT NOT NULL` |
| `tolerancia_min` | `INT NOT NULL DEFAULT 0` |
| `requiere_qr` | `BOOLEAN NOT NULL DEFAULT FALSE` |
| `usa_iot` | `BOOLEAN NOT NULL DEFAULT FALSE` |
| `max_antiguedad_lectura_min` | `INT NULL` |
| `modo` | `ENUM('ALERTA','AUTOMATICA') NOT NULL` |
| `activa` | `BOOLEAN NOT NULL DEFAULT TRUE` |

Sin claves foráneas.

Restricciones e índices documentados:

- `CONSTRAINT pk_politicas_liberacion PRIMARY KEY (id)`
- `CONSTRAINT uq_politica_nombre_version UNIQUE (nombre, version)`
- `CONSTRAINT chk_politica_tolerancia CHECK (tolerancia_min >= 0)`
- `CONSTRAINT chk_politica_antiguedad CHECK (max_antiguedad_lectura_min IS NULL OR max_antiguedad_lectura_min >= 0)`

### espacios_academicos

Concepto: **EspacioAcademico**. Fuentes: tablas 79 y 110; script `espacios_academicos`. Regla: PK compartida con recurso; capacidad positiva; piso y tipo obligatorios; política opcional.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `recurso_id` | `BIGINT UNSIGNED NOT NULL` |
| `piso_id` | `BIGINT UNSIGNED NOT NULL` |
| `tipo_espacio_id` | `BIGINT UNSIGNED NOT NULL` |
| `politica_liberacion_id` | `BIGINT UNSIGNED NULL` |
| `capacidad` | `INT NOT NULL` |
| `referencia_ubicacion` | `VARCHAR(255) NULL` |
| `estado` | `ENUM('DISPONIBLE','BLOQUEADO','EN_MANTENIMIENTO') NOT NULL DEFAULT 'DISPONIBLE'` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `recurso_id` | `recursos_institucionales.id` | 1 | 0..1 | CASCADE |
| `piso_id` | `pisos.id` | 1 | 0..* | RESTRICT |
| `tipo_espacio_id` | `tipos_espacio.id` | 1 | 0..* | RESTRICT |
| `politica_liberacion_id` | `politicas_liberacion.id` | 0..1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_espacios_academicos PRIMARY KEY (recurso_id)`
- `CONSTRAINT chk_espacio_capacidad CHECK (capacidad > 0)`
- `INDEX idx_espacio_piso (piso_id)`
- `INDEX idx_espacio_tipo (tipo_espacio_id)`
- `INDEX idx_espacio_estado (estado)`

### espacios_caracteristicas

Concepto: **Asociación Espacio–Característica**. Fuentes: tablas 80 y 111; script `espacios_caracteristicas`. Regla: Una fila por par espacio/característica; no duplicar valores.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `espacio_id` | `BIGINT UNSIGNED NOT NULL` |
| `caracteristica_id` | `BIGINT UNSIGNED NOT NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `espacio_id` | `espacios_academicos.recurso_id` | 1 | 0..* | CASCADE |
| `caracteristica_id` | `caracteristicas_espacio.id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_espacios_caracteristicas PRIMARY KEY (espacio_id, caracteristica_id)`

### bloqueos_espacio

Concepto: **BloqueoEspacio**. Fuentes: tablas 81 y 112; script `bloqueos_espacio`. Regla: Intervalo estricto fin>inicio; responsable y motivo; considerar solo bloqueos aplicables.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `espacio_id` | `BIGINT UNSIGNED NOT NULL` |
| `responsable_id` | `BIGINT UNSIGNED NOT NULL` |
| `inicio` | `DATETIME NOT NULL` |
| `fin` | `DATETIME NOT NULL` |
| `motivo` | `VARCHAR(500) NOT NULL` |
| `activo` | `BOOLEAN NOT NULL DEFAULT TRUE` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `espacio_id` | `espacios_academicos.recurso_id` | 1 | 0..* | RESTRICT |
| `responsable_id` | `usuarios.id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_bloqueos_espacio PRIMARY KEY (id)`
- `CONSTRAINT chk_bloqueo_fechas CHECK (fin > inicio)`
- `INDEX idx_bloqueo_espacio_periodo (espacio_id, inicio, fin)`
- `INDEX idx_bloqueo_activo (activo)`

### tipos_equipamiento

Concepto: **TipoEquipamiento**. Fuentes: tablas 82 y 113; script `tipos_equipamiento`. Regla: Código único; tipos se incorporan como registros.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `codigo` | `VARCHAR(60) NOT NULL` |
| `nombre` | `VARCHAR(120) NOT NULL` |

Sin claves foráneas.

Restricciones e índices documentados:

- `CONSTRAINT pk_tipos_equipamiento PRIMARY KEY (id)`
- `CONSTRAINT uq_tipo_equipamiento_codigo UNIQUE (codigo)`

### equipamientos_tecnologicos

Concepto: **EquipamientoTecnologico**. Fuentes: tablas 83 y 114; script `equipamientos_tecnologicos`. Regla: PK compartida; serie opcional única; ubicación base opcional; estado operativo según enum.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `recurso_id` | `BIGINT UNSIGNED NOT NULL` |
| `tipo_equipamiento_id` | `BIGINT UNSIGNED NOT NULL` |
| `espacio_base_id` | `BIGINT UNSIGNED NULL` |
| `descripcion` | `TEXT NULL` |
| `marca` | `VARCHAR(120) NULL` |
| `modelo` | `VARCHAR(120) NULL` |
| `numero_serie` | `VARCHAR(160) NULL` |
| `estado` | `ENUM('DISPONIBLE','PRESTADO','EN_MANTENIMIENTO','BAJA') NOT NULL DEFAULT 'DISPONIBLE'` |
| `referencia_ubicacion` | `VARCHAR(255) NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `recurso_id` | `recursos_institucionales.id` | 1 | 0..1 | CASCADE |
| `tipo_equipamiento_id` | `tipos_equipamiento.id` | 1 | 0..* | RESTRICT |
| `espacio_base_id` | `espacios_academicos.recurso_id` | 0..1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_equipamientos_tecnologicos PRIMARY KEY (recurso_id)`
- `CONSTRAINT uq_equipamiento_numero_serie UNIQUE (numero_serie)`
- `INDEX idx_equipo_tipo (tipo_equipamiento_id)`
- `INDEX idx_equipo_estado (estado)`
- `INDEX idx_equipo_espacio_base (espacio_base_id)`

### reservas

Concepto: **Reserva**. Fuentes: tablas 84 y 115; script `reservas`. Regla: código único e inmutable; solicitante/espacio obligatorios; fin>inicio. Solo APROBADA bloquea un intervalo solapado del mismo espacio; PENDIENTE admite coexistencia. Transacción y lock del espacio garantizan exclusión al aprobar (D-05, cierre Fase 3.5).

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `codigo` | `VARCHAR(60) NOT NULL` |
| `espacio_id` | `BIGINT UNSIGNED NOT NULL` |
| `solicitante_id` | `BIGINT UNSIGNED NOT NULL` |
| `resuelta_por_id` | `BIGINT UNSIGNED NULL` |
| `cancelada_por_id` | `BIGINT UNSIGNED NULL` |
| `inicio` | `DATETIME NOT NULL` |
| `fin` | `DATETIME NOT NULL` |
| `proposito` | `VARCHAR(500) NOT NULL` |
| `solicitada_en` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `estado` | `ENUM('PENDIENTE','APROBADA','RECHAZADA', 'CANCELADA','LIBERADA','FINALIZADA') NOT NULL DEFAULT 'PENDIENTE'` |
| `resuelta_en` | `DATETIME NULL` |
| `motivo_rechazo` | `VARCHAR(500) NULL` |
| `cancelada_en` | `DATETIME NULL` |
| `motivo_cancelacion` | `VARCHAR(500) NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `espacio_id` | `espacios_academicos.recurso_id` | 1 | 0..* | RESTRICT |
| `solicitante_id` | `usuarios.id` | 1 | 0..* | RESTRICT |
| `resuelta_por_id` | `usuarios.id` | 0..1 | 0..* | RESTRICT |
| `cancelada_por_id` | `usuarios.id` | 0..1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_reservas PRIMARY KEY (id)`
- `CONSTRAINT uq_reservas_codigo UNIQUE (codigo)`
- `CONSTRAINT chk_reserva_fechas CHECK (fin > inicio)`
- `INDEX idx_reserva_espacio_periodo (espacio_id, inicio, fin)`
- `INDEX idx_reserva_solicitante (solicitante_id)`
- `INDEX idx_reserva_estado (estado)`

#### Escritura prevista por operación — Fase 3.5

Esta precisión es documental: no existe aún migración/tabla funcional de reservas en la aplicación. Se conservan las quince columnas anteriores, todos sus tipos y nulabilidades; las cuatro FK mantienen ON UPDATE CASCADE y ON DELETE RESTRICT según el SQL oficial. No añadir created_at/updated_at, tabla de locks ni constraint de exclusión temporal.

| Operación futura | Campos que toma/establece el servidor | Conservación |
| --- | --- | --- |
| Solicitar | codigo generado; solicitante_id autenticado; solicitada_en; estado=PENDIENTE. espacio_id, inicio, fin y proposito validados | resuelta_por_id, resuelta_en, motivo_rechazo, cancelada_por_id, cancelada_en y motivo_cancelacion inicialmente NULL |
| Aprobar | estado=APROBADA; resuelta_por_id actor; resuelta_en; motivo_rechazo=NULL | Identidad, solicitante, espacio, periodo y propósito; campos de cancelación nulos |
| Rechazar | estado=RECHAZADA; resuelta_por_id actor; resuelta_en; motivo_rechazo opcional | Identidad y datos de solicitud; campos de cancelación nulos |
| Cancelar | estado=CANCELADA; cancelada_por_id actor; cancelada_en; motivo_cancelacion opcional | Datos de solicitud y toda la resolución anterior, incluidos resuelta_por_id/resuelta_en |

Cada fila de la tabla describe una operación atómica, no actualizaciones separadas. Responsables y fechas no vienen de campos editables del cliente. Motivos opcionales admiten hasta 500 caracteres; ausencia/vacío normalizado equivale a NULL. No volverlos NOT NULL. Fecha/hora operativa conserva America/La_Paz y la precisión DATETIME del modelo.

Codigo: `RES-` + UUID v4 estándar en minúsculas, 40 caracteres ASCII, generado en servidor y estable. `VARCHAR(60)` y `uq_reservas_codigo` permanecen intactos. La regeneración acotada ante colisión y las guardas están en [D-05](DECISIONS.md#código-estable-y-estados-de-fases-posteriores). El código no acredita permiso ni sustituye QR.

`idx_reserva_espacio_periodo (espacio_id, inicio, fin)`, `idx_reserva_solicitante` e `idx_reserva_estado` se conservan; ninguno impide por sí solo solapamientos. Tampoco `CHECK (fin > inicio)` compara filas distintas. La garantía depende de transacción, lock de la fila de espacio por recurso_id, lecturas actuales y validación servidor, con pruebas de dos procesos en Fase 4. LIBERADA/FINALIZADA permanecen en el enum sin producirse automáticamente en esa fase.

### prestamos

Concepto: **Prestamo**. Fuentes: tablas 85 y 116; script `prestamos`. Regla: Código único; equipo/solicitante obligatorios; fin_previsto>inicio_previsto; aprobar solo si disponible.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `codigo` | `VARCHAR(60) NOT NULL` |
| `equipamiento_id` | `BIGINT UNSIGNED NOT NULL` |
| `solicitante_id` | `BIGINT UNSIGNED NOT NULL` |
| `resuelto_por_id` | `BIGINT UNSIGNED NULL` |
| `inicio_previsto` | `DATETIME NOT NULL` |
| `fin_previsto` | `DATETIME NOT NULL` |
| `motivo` | `VARCHAR(500) NOT NULL` |
| `solicitado_en` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `estado` | `ENUM('PENDIENTE','APROBADO','RECHAZADO', 'ENTREGADO','DEVUELTO') NOT NULL DEFAULT 'PENDIENTE'` |
| `resuelto_en` | `DATETIME NULL` |
| `motivo_rechazo` | `VARCHAR(500) NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `equipamiento_id` | `equipamientos_tecnologicos.recurso_id` | 1 | 0..* | RESTRICT |
| `solicitante_id` | `usuarios.id` | 1 | 0..* | RESTRICT |
| `resuelto_por_id` | `usuarios.id` | 0..1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_prestamos PRIMARY KEY (id)`
- `CONSTRAINT uq_prestamos_codigo UNIQUE (codigo)`
- `CONSTRAINT chk_prestamo_fechas CHECK (fin_previsto > inicio_previsto)`
- `INDEX idx_prestamo_equipo_periodo (equipamiento_id, inicio_previsto, fin_previsto)`
- `INDEX idx_prestamo_solicitante (solicitante_id)`
- `INDEX idx_prestamo_estado (estado)`

### entregas_prestamo

Concepto: **EntregaPrestamo**. Fuentes: tablas 86 y 117; script `entregas_prestamo`. Regla: Una entrega por préstamo; entregador y receptor obligatorios; comprobación física y estado aprobado.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `prestamo_id` | `BIGINT UNSIGNED NOT NULL` |
| `entregado_por_id` | `BIGINT UNSIGNED NOT NULL` |
| `receptor_id` | `BIGINT UNSIGNED NOT NULL` |
| `fecha_hora` | `DATETIME NOT NULL` |
| `condicion_entrega` | `VARCHAR(255) NOT NULL` |
| `observacion` | `VARCHAR(500) NULL` |
| `recepcion_confirmada_en` | `DATETIME NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `prestamo_id` | `prestamos.id` | 1 | 0..1 | CASCADE |
| `entregado_por_id` | `usuarios.id` | 1 | 0..* | RESTRICT |
| `receptor_id` | `usuarios.id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_entregas_prestamo PRIMARY KEY (id)`
- `CONSTRAINT uq_entrega_prestamo UNIQUE (prestamo_id)`

### devoluciones_prestamo

Concepto: **DevolucionPrestamo**. Fuentes: tablas 87 y 118; script `devoluciones_prestamo`. Regla: Una devolución por préstamo; recibido_por obligatorio, confirmado_por opcional; condición incierta conserva revisión.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `prestamo_id` | `BIGINT UNSIGNED NOT NULL` |
| `recibido_por_id` | `BIGINT UNSIGNED NOT NULL` |
| `confirmado_por_id` | `BIGINT UNSIGNED NULL` |
| `registrada_en` | `DATETIME NOT NULL` |
| `confirmada_en` | `DATETIME NULL` |
| `condicion` | `ENUM('ADECUADA','CON_INCIDENCIA','NO_DETERMINABLE') NOT NULL` |
| `descripcion_condicion` | `VARCHAR(500) NULL` |
| `observacion` | `VARCHAR(500) NULL` |
| `estado` | `ENUM('PENDIENTE_REVISION','CONFIRMADA') NOT NULL DEFAULT 'PENDIENTE_REVISION'` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `prestamo_id` | `prestamos.id` | 1 | 0..1 | CASCADE |
| `recibido_por_id` | `usuarios.id` | 1 | 0..* | RESTRICT |
| `confirmado_por_id` | `usuarios.id` | 0..1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_devoluciones_prestamo PRIMARY KEY (id)`
- `CONSTRAINT uq_devolucion_prestamo UNIQUE (prestamo_id)`

### incidencias_mantenimiento

Concepto: **IncidenciaMantenimiento**. Fuentes: tablas 88 y 119; script `incidencias_mantenimiento`. Regla: Recurso y reportante obligatorios; devolución y cierre opcionales; fechas coherentes; bloquear uso incompatible.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `recurso_id` | `BIGINT UNSIGNED NOT NULL` |
| `reportado_por_id` | `BIGINT UNSIGNED NOT NULL` |
| `cerrado_por_id` | `BIGINT UNSIGNED NULL` |
| `devolucion_id` | `BIGINT UNSIGNED NULL` |
| `tipo` | `ENUM('INCIDENCIA','MANTENIMIENTO') NOT NULL` |
| `descripcion` | `TEXT NOT NULL` |
| `inicio` | `DATETIME NOT NULL` |
| `fin_previsto` | `DATETIME NULL` |
| `cerrada_en` | `DATETIME NULL` |
| `solucion` | `TEXT NULL` |
| `estado` | `ENUM('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA'` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `recurso_id` | `recursos_institucionales.id` | 1 | 0..* | RESTRICT |
| `reportado_por_id` | `usuarios.id` | 1 | 0..* | RESTRICT |
| `cerrado_por_id` | `usuarios.id` | 0..1 | 0..* | RESTRICT |
| `devolucion_id` | `devoluciones_prestamo.id` | 0..1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_incidencias_mantenimiento PRIMARY KEY (id)`
- `CONSTRAINT chk_incidencia_fin_previsto CHECK (fin_previsto IS NULL OR fin_previsto >= inicio)`
- `CONSTRAINT chk_incidencia_cierre CHECK (cerrada_en IS NULL OR cerrada_en >= inicio)`
- `INDEX idx_incidencia_recurso (recurso_id)`
- `INDEX idx_incidencia_estado (estado)`

### codigos_qr

Concepto: **CodigoQR**. Fuentes: tablas 89 y 120; script `codigos_qr`. Regla: Exactamente un objetivo: recurso, reserva o préstamo; hash único; vigencia válida.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `recurso_id` | `BIGINT UNSIGNED NULL` |
| `reserva_id` | `BIGINT UNSIGNED NULL` |
| `prestamo_id` | `BIGINT UNSIGNED NULL` |
| `token_hash` | `VARCHAR(255) NOT NULL` |
| `generada_en` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `vence_en` | `DATETIME NULL` |
| `activo` | `BOOLEAN NOT NULL DEFAULT TRUE` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `recurso_id` | `recursos_institucionales.id` | 0..1 | 0..* | RESTRICT |
| `reserva_id` | `reservas.id` | 0..1 | 0..* | RESTRICT |
| `prestamo_id` | `prestamos.id` | 0..1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_codigos_qr PRIMARY KEY (id)`
- `CONSTRAINT uq_codigo_qr_token_hash UNIQUE (token_hash)`
- `CONSTRAINT chk_qr_objetivo CHECK ( (recurso_id IS NOT NULL) + (reserva_id IS NOT NULL) + (prestamo_id IS NOT NULL) = 1 )`
- `CONSTRAINT chk_qr_vigencia CHECK (vence_en IS NULL OR vence_en >= generada_en)`
- `INDEX idx_qr_recurso (recurso_id)`
- `INDEX idx_qr_reserva (reserva_id)`
- `INDEX idx_qr_prestamo (prestamo_id)`
- `INDEX idx_qr_activo (activo)`

### validaciones_qr

Concepto: **ValidacionQR**. Fuentes: tablas 90 y 121; script `validaciones_qr`. Regla: Recurso esperado y validador obligatorios; contexto reserva/préstamo y operación coherentes; enlace único a entrega/devolución.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `codigo_qr_id` | `BIGINT UNSIGNED NULL` |
| `recurso_esperado_id` | `BIGINT UNSIGNED NOT NULL` |
| `reserva_id` | `BIGINT UNSIGNED NULL` |
| `prestamo_id` | `BIGINT UNSIGNED NULL` |
| `entrega_id` | `BIGINT UNSIGNED NULL` |
| `devolucion_id` | `BIGINT UNSIGNED NULL` |
| `validado_por_id` | `BIGINT UNSIGNED NOT NULL` |
| `fecha_hora` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `resultado` | `ENUM('VALIDA','INVALIDA') NOT NULL` |
| `tipo_operacion` | `ENUM('USO_ESPACIO','ENTREGA','DEVOLUCION') NOT NULL` |
| `motivo_rechazo` | `VARCHAR(500) NULL` |
| `hash_leido` | `VARCHAR(255) NOT NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `codigo_qr_id` | `codigos_qr.id` | 0..1 | 0..* | RESTRICT |
| `recurso_esperado_id` | `recursos_institucionales.id` | 1 | 0..* | RESTRICT |
| `reserva_id` | `reservas.id` | 0..1 | 0..* | RESTRICT |
| `prestamo_id` | `prestamos.id` | 0..1 | 0..* | RESTRICT |
| `entrega_id` | `entregas_prestamo.id` | 0..1 | 0..1 | RESTRICT |
| `devolucion_id` | `devoluciones_prestamo.id` | 0..1 | 0..1 | RESTRICT |
| `validado_por_id` | `usuarios.id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_validaciones_qr PRIMARY KEY (id)`
- `CONSTRAINT chk_validacion_contexto CHECK ( (tipo_operacion = 'USO_ESPACIO' AND reserva_id IS NOT NULL AND prestamo_id IS NULL) OR (tipo_operacion IN ('ENTREGA','DEVOLUCION') AND reserva_id IS NULL AND prestamo_id IS NOT NULL) )`
- `CONSTRAINT chk_validacion_entrega_devolucion CHECK ( (tipo_operacion = 'USO_ESPACIO' AND entrega_id IS NULL AND devolucion_id IS NULL) OR (tipo_operacion = 'ENTREGA' AND devolucion_id IS NULL) OR (tipo_operacion = 'DEVOLUCION' AND entrega_id IS NULL) )`
- `CONSTRAINT uq_validacion_entrega UNIQUE (entrega_id)`
- `CONSTRAINT uq_validacion_devolucion UNIQUE (devolucion_id)`
- `INDEX idx_validacion_codigo (codigo_qr_id)`
- `INDEX idx_validacion_reserva (reserva_id)`
- `INDEX idx_validacion_prestamo (prestamo_id)`
- `INDEX idx_validacion_usuario (validado_por_id)`
- `INDEX idx_validacion_fecha (fecha_hora)`

### sensores_iot

Concepto: **SensorIoT**. Fuentes: tablas 91 y 122; script `sensores_iot`. Regla: Identificador único; espacio actual obligatorio; solo HABILITADO admite eventos válidos.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `espacio_actual_id` | `BIGINT UNSIGNED NOT NULL` |
| `identificador` | `VARCHAR(120) NOT NULL` |
| `tipo` | `VARCHAR(100) NOT NULL` |
| `estado` | `ENUM('HABILITADO','DESHABILITADO') NOT NULL DEFAULT 'HABILITADO'` |
| `ultima_comunicacion` | `DATETIME NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `espacio_actual_id` | `espacios_academicos.recurso_id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_sensores_iot PRIMARY KEY (id)`
- `CONSTRAINT uq_sensor_identificador UNIQUE (identificador)`
- `INDEX idx_sensor_espacio (espacio_actual_id)`
- `INDEX idx_sensor_estado (estado)`

### eventos_ocupacion

Concepto: **EventoOcupacion**. Fuentes: tablas 92 y 123; script `eventos_ocupacion`. Regla: Sensor y espacio histórico obligatorios; conservar fecha de evento y recepción; no reescribir al reasignar sensor.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `sensor_id` | `BIGINT UNSIGNED NOT NULL` |
| `espacio_historico_id` | `BIGINT UNSIGNED NOT NULL` |
| `tipo` | `ENUM('PRESENCIA','OCUPACION') NOT NULL` |
| `valor` | `DECIMAL(10,2) NULL` |
| `fecha_evento` | `DATETIME NOT NULL` |
| `fecha_recepcion` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `sensor_id` | `sensores_iot.id` | 1 | 0..* | RESTRICT |
| `espacio_historico_id` | `espacios_academicos.recurso_id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_eventos_ocupacion PRIMARY KEY (id)`
- `INDEX idx_evento_sensor_fecha (sensor_id, fecha_evento)`
- `INDEX idx_evento_espacio_fecha (espacio_historico_id, fecha_evento)`

### evaluaciones_liberacion

Concepto: **EvaluacionLiberacion**. Fuentes: tablas 93 y 124; script `evaluaciones_liberacion`. Regla: Reserva/política obligatorias; resultado exacto y evidencias enlazadas; no liberar con datos insuficientes.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `reserva_id` | `BIGINT UNSIGNED NOT NULL` |
| `politica_id` | `BIGINT UNSIGNED NOT NULL` |
| `fecha_hora` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `resultado` | `ENUM('MANTENER_POR_USO','NO_DETERMINABLE', 'ALERTAR','LIBERAR') NOT NULL` |
| `motivo` | `VARCHAR(500) NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `reserva_id` | `reservas.id` | 1 | 0..* | RESTRICT |
| `politica_id` | `politicas_liberacion.id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_evaluaciones_liberacion PRIMARY KEY (id)`
- `INDEX idx_eval_reserva_fecha (reserva_id, fecha_hora)`
- `INDEX idx_eval_politica (politica_id)`

### evaluaciones_eventos_ocupacion

Concepto: **Asociación Evaluación–Evento**. Fuentes: tablas 94 y 125; script `evaluaciones_eventos_ocupacion`. Regla: PK compuesta; conserva los eventos usados en cada evaluación.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `evaluacion_id` | `BIGINT UNSIGNED NOT NULL` |
| `evento_id` | `BIGINT UNSIGNED NOT NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `evaluacion_id` | `evaluaciones_liberacion.id` | 1 | 0..* | CASCADE |
| `evento_id` | `eventos_ocupacion.id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_eval_eventos PRIMARY KEY (evaluacion_id, evento_id)`

### evaluaciones_validaciones_qr

Concepto: **Asociación Evaluación–Validación**. Fuentes: tablas 95 y 126; script `evaluaciones_validaciones_qr`. Regla: PK compuesta; conserva las validaciones usadas en cada evaluación.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `evaluacion_id` | `BIGINT UNSIGNED NOT NULL` |
| `validacion_id` | `BIGINT UNSIGNED NOT NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `evaluacion_id` | `evaluaciones_liberacion.id` | 1 | 0..* | CASCADE |
| `validacion_id` | `validaciones_qr.id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_eval_validaciones PRIMARY KEY (evaluacion_id, validacion_id)`

### registros_actividad

Concepto: **RegistroActividad**. Fuentes: tablas 96 y 127; script `registros_actividad`. Regla: Actor o proceso responsable obligatorio; operación, fecha, resultado e indicador crítica; preservar contexto histórico.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `actor_id` | `BIGINT UNSIGNED NULL` |
| `usuario_afectado_id` | `BIGINT UNSIGNED NULL` |
| `recurso_id` | `BIGINT UNSIGNED NULL` |
| `reserva_id` | `BIGINT UNSIGNED NULL` |
| `prestamo_id` | `BIGINT UNSIGNED NULL` |
| `incidencia_id` | `BIGINT UNSIGNED NULL` |
| `accion` | `VARCHAR(160) NOT NULL` |
| `fecha_hora` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `resultado` | `ENUM('EXITO','RECHAZO','ERROR') NOT NULL` |
| `descripcion` | `TEXT NULL` |
| `proceso_responsable` | `VARCHAR(160) NULL` |
| `critica` | `BOOLEAN NOT NULL DEFAULT FALSE` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `actor_id` | `usuarios.id` | 0..1 | 0..* | RESTRICT |
| `usuario_afectado_id` | `usuarios.id` | 0..1 | 0..* | RESTRICT |
| `recurso_id` | `recursos_institucionales.id` | 0..1 | 0..* | RESTRICT |
| `reserva_id` | `reservas.id` | 0..1 | 0..* | RESTRICT |
| `prestamo_id` | `prestamos.id` | 0..1 | 0..* | RESTRICT |
| `incidencia_id` | `incidencias_mantenimiento.id` | 0..1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_registros_actividad PRIMARY KEY (id)`
- `CONSTRAINT chk_registro_responsable CHECK (actor_id IS NOT NULL OR proceso_responsable IS NOT NULL)`
- `INDEX idx_registro_fecha (fecha_hora)`
- `INDEX idx_registro_actor (actor_id)`
- `INDEX idx_registro_recurso (recurso_id)`
- `INDEX idx_registro_reserva (reserva_id)`
- `INDEX idx_registro_prestamo (prestamo_id)`
- `INDEX idx_registro_critica (critica)`

### evidencias_trazabilidad

Concepto: **EvidenciaTrazabilidad**. Fuentes: tablas 97 y 128; script `evidencias_trazabilidad`. Regla: Una evidencia por registro de actividad; hash único; algoritmo/versión y referencia; caída externa deja PENDIENTE.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `registro_actividad_id` | `BIGINT UNSIGNED NOT NULL` |
| `algoritmo_hash` | `VARCHAR(50) NOT NULL` |
| `hash` | `VARCHAR(255) NOT NULL` |
| `version_canonica` | `VARCHAR(60) NOT NULL` |
| `referencia_externa` | `VARCHAR(255) NULL` |
| `registrada_localmente_en` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `registrada_externamente_en` | `DATETIME NULL` |
| `estado` | `ENUM('PENDIENTE','REGISTRADA') NOT NULL DEFAULT 'PENDIENTE'` |
| `ultima_verificacion` | `DATETIME NULL` |
| `resultado_verificacion` | `ENUM('VALIDO','INCONSISTENTE', 'PENDIENTE','NO_VERIFICABLE') NOT NULL DEFAULT 'PENDIENTE'` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `registro_actividad_id` | `registros_actividad.id` | 1 | 0..1 | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_evidencias_trazabilidad PRIMARY KEY (id)`
- `CONSTRAINT uq_evidencia_registro UNIQUE (registro_actividad_id)`
- `CONSTRAINT uq_evidencia_hash UNIQUE (hash)`
- `INDEX idx_evidencia_estado (estado)`
- `INDEX idx_evidencia_referencia (referencia_externa)`

### notificaciones

Concepto: **Notificacion**. Fuentes: tablas 98 y 129; script `notificaciones`. Regla: Destinatario y actividad obligatorios; leida_en nullable; lectura exclusivamente autorizada al destinatario.

| Columna | Tipo, nulabilidad y valor por defecto documentados |
| --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| `destinatario_id` | `BIGINT UNSIGNED NOT NULL` |
| `registro_actividad_id` | `BIGINT UNSIGNED NOT NULL` |
| `titulo` | `VARCHAR(160) NOT NULL` |
| `mensaje` | `TEXT NOT NULL` |
| `tipo` | `ENUM('SOLICITUD','DECISION','CANCELACION', 'ENTREGA','DEVOLUCION','VENCIMIENTO', 'INCIDENCIA','LIBERACION') NOT NULL` |
| `creada_en` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `leida_en` | `DATETIME NULL` |

| FK hija | Referencia padre | Hija → padre | Padre → hijas | ON DELETE |
| --- | --- | --- | --- | --- |
| `destinatario_id` | `usuarios.id` | 1 | 0..* | RESTRICT |
| `registro_actividad_id` | `registros_actividad.id` | 1 | 0..* | RESTRICT |

Restricciones e índices documentados:

- `CONSTRAINT pk_notificaciones PRIMARY KEY (id)`
- `INDEX idx_notificacion_destinatario (destinatario_id, leida_en)`
- `INDEX idx_notificacion_fecha (creada_en)`


## Relaciones que requieren especial cuidado

- `recursos_institucionales` es el padre de espacios y equipamiento; no duplicar sus códigos en tablas independientes. El SQL no expresa todas las restricciones semánticas de la superclase abstracta; validar la creación del subtipo coherente sin inventar una jerarquía distinta.
- Un préstamo tiene como máximo una entrega y una devolución por sus UNIQUE. `validaciones_qr.entrega_id` y `.devolucion_id` también son opcionales y UNIQUE: una fila confirmada no puede vincular indiscriminadamente todos sus intentos de escaneo (D-09).
- Un código QR tiene exactamente un objetivo por CHECK. Una validación puede tener `codigo_qr_id` NULL para representar un token no reconocido, pero exige recurso esperado y contexto válido de reserva/préstamo. No se puede persistir arbitrariamente cualquier escaneo sin contexto (D-09).
- Un sensor mantiene su espacio actual; cada evento guarda su espacio histórico. Reasignar el sensor no debe mover los eventos antiguos a otro espacio.
- Cada evaluación enlaza la política, reserva, eventos y validaciones utilizados; D-08 de Fase 7.5 congela las reglas por versión y la aplicación impide mutación histórica, sin añadir snapshots.
- `registros_actividad` admite actor humano o proceso responsable; las operaciones automáticas no requieren un usuario ficticio. DB-22 enuncia usuario responsable de forma general; el SQL y CU automáticos concretan la responsabilidad del proceso.
- La evidencia referencia un registro de actividad único; la notificación exige actividad y destinatario. La figura conceptual 19 muestra cardinalidades diferentes: D-06 queda resuelta por el SQL final según la matriz siguiente.
- ON DELETE CASCADE está documentado en subtipos, algunas asociaciones y entrega/devolución. DB-21 exige preservar historia: la aplicación debe impedir borrados de operaciones históricas. No confiar solo en las FK ni cambiar las cascadas sin documentar la decisión.
- Los índices de intervalos aceleran consultas pero no prohíben solapamientos. La regla DB-17 exige validación transaccional y pruebas concurrentes. D-05 fija [inicio, fin), APROBADA para reservas y APROBADO/ENTREGADO para periodos de préstamos; el estado operativo y la custodia también restringen disponibilidad.
- DB-16 permite describir fin no anterior al inicio; los CHECK de reservas, préstamos y bloqueos son más estrictos (`fin > inicio`). Conservar la restricción concreta según D-05; no admitir intervalos de duración cero por una lectura aislada del texto.

## Volúmenes y aceptación futura

La sección titulada “Volúmenes” describe atributos, tipos y restricciones, no una estimación completa de bytes/retención. La carga mínima se toma de R-CC-03: 2.000 usuarios, 100 espacios, 2.000 equipos, 20.000 reservas y 20.000 préstamos. R-ESC-03 exige poder duplicarla sin cambiar relaciones. El volumen de eventos depende de sensores y frecuencia; no inventar una tasa de retención. Todos los datos de prueba serán sintéticos.

Validar posteriormente cada PK/FK, UNIQUE, CHECK, enum, nullable y default contra MySQL real; probar borrados históricos rechazados, fechas inválidas, asociaciones inexistentes, una sola entrega/devolución, objetivo único de QR, espacio histórico y recuperabilidad. No usar SQLite como sustituto de esa validación. D-01 está resuelta: cualquier futura migración usará `permisos.codigo`. El mapeo entre `sesiones_acceso` y el mecanismo de sesión elegido requiere D-07.

## Reconciliación de cardinalidades (D-06 resuelta)

Fuentes: figura 19 (dominio, p. 109), figura 108 (clases, p. 248), figura 109 (BD, p. 249), mapeo/volúmenes y SQL del cap. VII. Las dos primeras filas documentan divergencias conceptuales confirmadas. Las demás explicitan opcionalidad y restricciones que no deben deducirse de una asociación conceptual aislada. En la columna de diseño se expresa la cardinalidad implementable del diseño final contrastada con SQL; no se atribuyen al dibujo CHECK que solo aparecen en SQL. El diccionario precedente mantiene las 63 FK y sus acciones completas.

| Relación | Cardinalidad conceptual | Cardinalidad del diseño | FK correspondiente | Nullable | Decisión de implementación |
| --- | --- | --- | --- | --- | --- |
| RegistroActividad → EvidenciaTrazabilidad | Figura 19: 1 actividad → 0..* evidencias | Clases/final: 1 actividad → 0..1 evidencia; cada evidencia → 1 actividad | evidencias_trazabilidad.registro_actividad_id | NO; UNIQUE | Una evidencia local por actividad. Reintentar su envío no crea nuevas evidencias de esa actividad. |
| RegistroActividad → Notificacion | Figura 19: una notificación puede referirse a 0..1 actividad | Final: notificación → 1 actividad; actividad → 0..* notificaciones | notificaciones.registro_actividad_id | NO | La relación es obligatoria al persistir; no crear avisos desligados de actividad. |
| Usuario → Notificacion | Destinatario de avisos; asociación de dominio | Notificación → 1 destinatario; usuario → 0..* avisos | notificaciones.destinatario_id | NO | No significa que cada usuario deba tener un aviso; autorización por destinatario. |
| Espacio → SensorIoT | Sensores asociados a espacio; DB-06 menciona uno o varios | Sensor → 1 espacio actual; espacio → 0..* sensores | sensores_iot.espacio_actual_id | NO | DB-06 se aplica a espacios del piloto monitorizados; no obliga a todo espacio a tener sensor. |
| EventoOcupacion → sensor/espacio histórico | Evento producido por sensor en espacio | Cada evento → 1 sensor y 1 espacio histórico; ambos padres → 0..* eventos | eventos_ocupacion.sensor_id; espacio_historico_id | NO en ambas | Conservar asociación histórica aunque el sensor cambie de espacio. |
| Reserva → espacio/usuarios | Solicitud sobre un espacio, solicitante y responsables del proceso | Reserva → 1 espacio y 1 solicitante; 0..1 resolvedor y 0..1 cancelador | reservas.espacio_id; solicitante_id; resuelta_por_id; cancelada_por_id | NO en espacio/solicitante; SÍ en responsables posteriores | Ausencia de decisión/cancelación no exige usuarios ficticios ni una tabla de asignación. |
| Prestamo → equipo/usuarios | Solicitud sobre equipo, solicitante y resolución | Préstamo → 1 equipo y 1 solicitante; 0..1 resolvedor | prestamos.equipamiento_id; solicitante_id; resuelto_por_id | NO en equipo/solicitante; SÍ en resolvedor | Mantener asociación directa; no inventar una asignación general de recursos. |
| Equipamiento → espacio base | Ubicación base opcional | Equipo → 0..1 espacio; espacio → 0..* equipos | equipamientos_tecnologicos.espacio_base_id | SÍ | Ubicación base no equivale a custodia, reserva ni ámbito del encargado. |
| Espacio/Evaluacion → PoliticaLiberacion | Política asociada a espacio y aplicada a evaluación | Espacio → 0..1 política; evaluación → 1 política; política → 0..* espacios/evaluaciones | espacios_academicos.politica_liberacion_id; evaluaciones_liberacion.politica_id | SÍ en espacio; NO en evaluación | No añadir FK reserva→política; evaluación conserva el vínculo existente. Versiones inmutables según D-08 cerrada en 7.5. |
| Prestamo → entrega/devolución | Hitos de entrega y retorno | Préstamo → 0..1 entrega y 0..1 devolución; cada hito → 1 préstamo | entregas_prestamo.prestamo_id; devoluciones_prestamo.prestamo_id | NO; UNIQUE en cada tabla | Preservar unicidad y CASCADE documentado; la aplicación impide borrar historia. |
| ValidacionQR → código/recurso esperado | Validación de código dentro de contexto de recurso | Validación → 0..1 código reconocido y 1 recurso esperado | validaciones_qr.codigo_qr_id; recurso_esperado_id | SÍ en código; NO en recurso | Token desconocido permite código NULL, pero no una validación sin recurso/contexto. |
| ValidacionQR → reserva/préstamo | Contexto según uso de espacio u operación de equipo | USO_ESPACIO exige 1 reserva y 0 préstamos; ENTREGA/DEVOLUCION exige 1 préstamo y 0 reservas | validaciones_qr.reserva_id; prestamo_id | Ambas SÍ individualmente; CHECK restringe combinación | Aplicar exclusividad por tipo; nullable individual no permite ambos vacíos ni ambos presentes. |
| ValidacionQR → entrega/devolución | Vínculo a confirmación del proceso | Cada validación → 0..1 entrega o 0..1 devolución; cada hito → 0..1 validación enlazada | validaciones_qr.entrega_id; devolucion_id | SÍ; UNIQUE en ambas | USO_ESPACIO no enlaza hitos; ENTREGA no enlaza devolución y viceversa. El vínculo de su tipo es opcional; correlación de intentos se cierra en D-09. |
| CodigoQR → objetivo | Identificación de recurso u operación | Exactamente 1 entre recurso, reserva o préstamo | codigos_qr.recurso_id; reserva_id; prestamo_id | SÍ individualmente; CHECK exige exactamente uno | No confundir objetivo del código con contexto de validación. No añadir objetivo directo entrega/devolución. |
| Usuario ↔ Rol | M:N con historia de asignación | Asignación → 1 usuario, 1 rol y 1 asignador; 0..1 retirador | asignaciones_roles.usuario_id; rol_id; asignado_por_id; retirado_por_id | Solo retirado_por_id SÍ | Conservar entidad histórica; sin nueva UNIQUE usuario/rol. Duplicados no multiplican privilegios (D-17). |
| Rol ↔ Permiso; Espacio ↔ Caracteristica | M:N | Dos relaciones 1:N por cada tabla puente; PK compuesta | roles_permisos.rol_id/permiso_id; espacios_caracteristicas.espacio_id/caracteristica_id | NO | Conservar ambas tablas intermedias, sin sustituirlas por campos JSON. |
| Evaluacion ↔ Evento; Evaluacion ↔ Validacion | Evidencias utilizadas en evaluación | M:N mediante dos tablas puente con PK compuesta | evaluaciones_eventos_ocupacion.evaluacion_id/evento_id; evaluaciones_validaciones_qr.evaluacion_id/validacion_id | NO | Conservar todas las evidencias vinculadas; la existencia de pivotes no implica que siempre haya una fila de cada tipo. |
| RegistroActividad → responsable | Responsabilidad humana o de proceso | Actor humano 0..1; CHECK exige actor o proceso, admite ambos | registros_actividad.actor_id; proceso_responsable no es FK | SÍ en ambos; CHECK al menos uno | No exigir usuario ficticio a automatismos ni convertir el CHECK en XOR. |
| RecursoInstitucional → subtipos | Superclase abstracta de EspacioAcademico y EquipamientoTecnologico | Subtipo → 1 recurso; recurso → 0..1 fila por cada tabla subtipo | espacios_academicos.recurso_id; equipamientos_tecnologicos.recurso_id | NO; PK compartida | SQL no asegura totalidad/disyunción entre tablas. Servidor crea coherentemente recurso y subtipo en transacción; no añadir discriminador ni nuevas FK. |

La clase expresa el dominio; las FK, nulabilidad y restricciones expresan la persistencia. Esta reconciliación no añade relaciones, columnas, índices ni entidades. No se cambia el contrato por preferencias del ORM. D-17 cubre las guardas del servidor que complementan, sin sustituir, las restricciones del SQL.

## Contrato físico de seguridad para Fase 2 — cierre de Fase 1.5

No cambia ninguna columna, tipo, default, FK, índice, CHECK o ENUM del diccionario. Solo se autoriza implementar progresivamente usuarios, roles, permisos, roles_permisos, asignaciones_roles y sesiones_acceso, más tablas estrictamente técnicas justificadas. No se crearán en esa fase tablas funcionales de recursos, reservas, préstamos, IoT, Blockchain ni registros_actividad; este último permanece requisito del modelo y requiere autorización de alcance posterior. El registro de sesiones no lo sustituye.

| Correspondencia | Regla de implementación documental |
| --- | --- |
| Identidad Laravel | Adaptar a usuarios.correo y usuarios.password_hash; Argon2id mediante Hash. No añadir email, password o remember_token por convenciones de un starter. |
| Roles históricos | asignaciones_roles conserva usuario_id, rol_id, asignado_por_id, retirado_por_id, fecha_asignacion y fecha_retiro; activa si fecha_retiro IS NULL. No usuarios.rol_id ni enum nuevo. |
| Permisos | roles_permisos conserva M:N y PK compuesta. permisos.codigo sigue D-01 y límite 100; convención recurso.accion, sin catálogo definitivo inventado. |
| Control de sesiones | sesiones_acceso.token_hash guarda SHA-256 hex de 64 caracteres del ID final; se conserva VARCHAR(255)/UNIQUE, sin ID/cookie/token crudo. |
| Vigencia | expira_en deriva de session.lifetime y se renueva por inactividad solo tras validar vigencia; creada_en inmutable y revocada_en histórica. Se respeta expira_en > creada_en. |
| Sesión técnica | file en desarrollo; no usar sesiones_acceso como tabla sessions de Laravel ni agregarle payload/last_activity. |
| Pruebas con escritura | Mismo modelo en MySQL espacio_u_testing, separado de espacio_u, con usuario restringido y configuración local ignorada. No provisionado en Fase 1.5. |

La coordinación del guard web, revocación y cambios de permisos deberá verificarse según D-04/D-07. La mera existencia de una fila de sesión o de una asignación histórica no concede acceso.

## Materialización de Fase 2

Solo usuarios, roles, permisos, roles_permisos, asignaciones_roles y sesiones_acceso están implementadas: seis migraciones, 29 columnas, siete FK y dos CHECK. Se preservó el SQL oficial, incluyendo ENUM ACTIVO/INACTIVO, fechas propias, acciones CASCADE/RESTRICT y PK compuesta; únicamente codigo aplica D-01. No hay timestamps agregados, UNIQUE sobre la historia ni tabla users/sessions alternativa. migrations es técnica. El resto del diccionario continúa como contrato, no como esquema ejecutado. SchemaContractTest verifica metadatos y restricciones sobre MySQL testing real.

## Materialización posterior de Fase 3

Se añaden pisos, recursos_institucionales, tipos_espacio, caracteristicas_espacio, politicas_liberacion, espacios_academicos, espacios_caracteristicas y bloqueos_espacio: 37 columnas, ocho FK y cuatro CHECK. Total actual: 14 tablas académicas, 66 columnas, 15 FK y seis CHECK, más migrations técnica. Las 17 tablas restantes del diccionario no están materializadas.

politicas_liberacion: **Tabla incorporada anticipadamente únicamente como dependencia estructural del FK de espacios académicos.** Estructura exacta sin CRUD, selección, seed ni evaluación funcional; Fase 8. espacio.recurso_id es PK/FK no autoincremental compartida con recurso, pivot tiene PK compuesta; no se añaden timestamps ni ubicaciones alternativas. SpaceSchemaTest verifica metadatos, defaults, unicidad, CHECK, acciones FK y rollback; el detalle está en [SPACES_IMPLEMENTATION.md](SPACES_IMPLEMENTATION.md).

## Materialización física de Fase 4

La migración `2026_10_23_000015_create_reservas_table.php` materializa el SQL de reservas contrastado: 15 columnas, PK, UNIQUE, cuatro FK, CHECK y tres índices oficiales. Se conservan tipos, nulabilidad, defaults y acciones referenciales. Ninguna migración anterior cambia. Total materializado: 15 tablas académicas; `migrations` es control técnico adicional. `ReservationSchemaTest` comprueba metadatos y restricciones reales. [Implementación](RESERVATIONS_IMPLEMENTATION.md#esquema-y-dominio).

## Contrato físico para Fase 5 — cierre documental de Fase 4.5

**No se materializa ninguna tabla en esta fase.** Continúan las 15 tablas académicas de Fase 4 más migrations técnica. Se recontrastaron con Word/PDF los seis CREATE TABLE futuros: tipos_equipamiento, equipamientos_tecnologicos, prestamos, entregas_prestamo, devoluciones_prestamo e incidencias_mantenimiento. Coinciden al normalizar espacios/encabezados. No cambia el diccionario de 31 tablas, 211 columnas y 63 FK.

| Elemento físico intacto | Guarda funcional de Fase 5, sin alterar esquema |
| --- | --- |
| equipamientos_tecnologicos.recurso_id PK+FK | Padre recursos_institucionales primero y mismo ID en subtipo, en transacción; sin id independiente ni timestamps agregados. Exactamente los nueve campos del diccionario. |
| espacio_base_id NULL y numero_serie NULL UNIQUE | Base opcional 0..1, localización por espacio/piso; serie ausente/vacía normalizada a NULL, única cuando exista. Sin FK directa a piso ni relación de custodia/asignación inferida. |
| prestamos.codigo VARCHAR(60), uq_prestamos_codigo | PRE-UUIDv4 de 40 caracteres, servidor/inmutable/no secreto; colisión reintentada como máximo tres veces solo para ese UNIQUE. |
| prestamos.estado y datos de resolución | Solo cuatro transiciones del contrato. Aprobación completa resuelto_por_id/resuelto_en y motivo NULL; rechazo exige motivo no vacío <=500, aunque SQL conserve nullable. |
| inicio_previsto/fin_previsto y CHECK estricto | [inicio, fin), fin>inicio. Índice de periodo no excluye solapamientos por sí solo; lock del equipo, lecturas actuales y transición condicional. Vencido no se persiste. |
| entregas_prestamo: UNIQUE(prestamo_id), dos FK de actores | Máximo una entrega; receptor_id se deriva de solicitante_id; entregado_por_id de sesión; fecha_hora servidor; condicion_entrega obligatoria <=255, observacion opcional <=500. |
| recepcion_confirmada_en DATETIME NULL | NULL durante Fase 5; no convertir fecha_hora en una confirmación digital del receptor que no ocurrió. La entrega registrada basta para continuar CU18. |
| devoluciones_prestamo: UNIQUE(prestamo_id) | Máximo una devolución; pendiente se confirma actualizando la misma fila. No nuevas columnas de historial, enum ni segundo hito. |
| recibido_por_id/registrada_en y confirmado_por_id/confirmada_en | Recepción siempre atribuida; pendientes dejan confirmación NULL. Confirmación inmediata/posterior usa sesión/reloj y no precede a registrada_en; conserva recepción original. |
| condicion/estado de devolución | NO_DETERMINABLE solo PENDIENTE_REVISION; confirmación solo ADECUADA/CON_INCIDENCIA. descripcion_condicion requerida funcionalmente para anomalía/revisión, <=500; nullable físico intacto. |
| incidencias_mantenimiento.recurso_id y devolucion_id NULL | Un único modelo para espacios/equipos; vínculo directo de devolución opcional, solo mismo equipo y sin reasignar vínculo histórico; no puente. |
| fin_previsto NULL, CHECK >=inicio; cerrada_en NULL, CHECK >=inicio | Para periodo previsto informado la aplicación exige >inicio; conservar el CHECK original >=. Cierre explícito >=inicio, actor y solucion no vacía; nulabilidad previa intacta. Vencer fin_previsto no cierra ABIERTA. |
| Sin causa/estado anterior persistido del recurso | No inventar previous_state ni tablas auxiliares. Restauración explícita con permiso y comprobación de todas las restricciones; BAJA nunca automática. |

El contrato completo de campos/guardas y la jerarquía de locks están en [DECISIONS](DECISIONS.md#contrato-de-equipamiento-y-préstamos--fase-45). Toda entrega/devolución y cambio operativo asociado es atómico. SQL conserva sus acciones CASCADE/RESTRICT; no se deduce de CASCADE un permiso de eliminar historia. QR, evidencias, notificaciones y registros_actividad no se materializan anticipadamente para suplir las integraciones diferidas.

## Materialización física de Fase 5

Aplicadas seis migraciones 000016–000021, únicamente tipos_equipamiento, equipamientos_tecnologicos, prestamos, entregas_prestamo, devoluciones_prestamo e incidencias_mantenimiento. Añaden 54 columnas y completan 21 tablas académicas / 135 columnas / 35 FK; migrations es técnica. Las quince migraciones anteriores no cambian. PK/FK compartida, índices oficiales, acciones referenciales, tipos, nulabilidad, defaults, enums, UNIQUE y CHECK contrastados contra el SQL final en EquipmentLoanSchemaTest, sobre MySQL real. No hay timestamps añadidos ni estructuras auxiliares. El diccionario final de 31 tablas / 211 columnas / 63 FK permanece intacto. [Esquema y evidencia](EQUIPMENT_LOANS_IMPLEMENTATION.md#persistencia-y-dominio).

## Contrato físico QR para Fase 6 — cierre documental 5.5

No se materializa ninguna tabla: permanecen 21 académicas y migrations técnica. El SQL final de codigos_qr (8 columnas/3 FK) y validaciones_qr (13 columnas/7 FK) fue recontrastado con Word/PDF, sin alteraciones. No se cambian VARCHAR(255), defaults, NULL, enum, índices ni acciones ON UPDATE CASCADE/ON DELETE RESTRICT de las diez FK. No se agregan timestamps, tipo_qr, requiere_qr, nonce, consumido_en ni expiración de correlación.

| Campo o constraint existente | Guarda funcional cerrada para Fase 6 |
| --- | --- |
| token_hash UNIQUE; hash_leido obligatorio | SHA-256 hexadecimal minúsculo del string UTF-8 exacto; payload canónico base64url de 32 bytes/43 ASCII; solo hashes persistidos |
| chk_qr_objetivo | Exactamente recurso_id, reserva_id o prestamo_id; recurso común de ambos subtipos |
| chk_qr_vigencia >= | SQL intacto; aplicación exige > al emitir fecha opcional, uso [generada_en,vence_en); igualdad tiene ventana vacía |
| activo | Máximo uno verdadero por objetivo exacto mediante lock estable y transacción; no UNIQUE adicional; anteriores conservados inactivos |
| codigo_qr_id nullable | NULL cuando no reconocido/no canónico; FK cuando localizado por hash incluso si resulta inválido; no código ficticio |
| recurso_esperado_id / validado_por_id / fecha_hora | Contexto autorizado, sesión y reloj servidor; nunca valores libres del cliente |
| chk_validacion_contexto | USO_ESPACIO exige reserva y excluye préstamo; ENTREGA/DEVOLUCION exigen préstamo y excluyen reserva |
| chk_validacion_entrega_devolucion | USO_ESPACIO sin hitos; ENTREGA sin devolución; DEVOLUCION sin entrega |
| UNIQUE(entrega_id), UNIQUE(devolucion_id) | NULL durante evaluación; una VALIDA nueva enlaza el hito creado dentro de la misma transacción; inválidas quedan NULL; no segunda fila para correlacionar |
| resultado / motivo_rechazo | Solo VALIDA/INVALIDA; inválida requiere motivo seguro <=500, válida NULL; no EXPIRADA/REVOCADA/DESCONOCIDA como estados |

El SQL permite varios NULL y varios intentos por codigo_qr_id. Las guardas de aplicación relacionan todos los IDs con el mismo recurso/operación y no presentan un ID como autorización. [Contrato completo de D-09](DECISIONS.md#contrato-qr-y-validaciones--cierre-d-09-de-fase-55). Al implementar solo estas dos tablas, el inventario previsto será 23 tablas académicas / 156 columnas / 45 FK; hoy esos dos CREATE TABLE siguen pendientes y el modelo final 31/211/63 no cambia.

## Materialización QR y excepción autorizada de Fase 6

Las tablas codigos_qr y validaciones_qr están materializadas: 23 tablas académicas, 156 columnas, 45 FK, más migrations. Las definiciones del diccionario anterior conservan el SQL documental original. **Diferencia física autorizada:** ON UPDATE RESTRICT en fk_qr_recurso/reserva/prestamo y fk_validacion_reserva/prestamo/entrega/devolucion por incompatibilidad MySQL CHECK+CASCADE (error 3823). Las otras tres FK QR conservan CASCADE y todos los DELETE son RESTRICT. Columnas, CHECK, UNIQUE, índices y nulabilidad intactos. Las 21 migraciones previas no cambian. [Autorización, consecuencias y pruebas de metadatos/restricciones](QR_IMPLEMENTATION.md#esquema-y-excepción-autorizada). No describir la implementación como SQL idéntico sin mencionar esta excepción.

## Preservación del contrato SQL IoT — Fase 6.5

[D-10 resuelta documentalmente](DECISIONS.md#readiness-for-phase-7). Se contrastaron CREATE TABLE completos del Word/PDF, equivalentes tras normalización de espacios/encabezados: sensores_iot (6 columnas), eventos_ocupacion (7) y politicas_liberacion (9). El diccionario anterior no cambia. Las tres FK de sensores/eventos mantienen ON UPDATE CASCADE y ON DELETE RESTRICT; no tienen el conflicto CHECK/FK que motivó la excepción QR. No extender aquella excepción a estas tablas.

No columnas de secreto, flag de provisionamiento, nonce, fecha adicional, tabla de credenciales ni UNIQUE nuevo. Credencial derivada en memoria; identificador inmutable por regla de aplicación. Eventos conservan índices sensor/fecha y espacio/fecha, DECIMAL(10,2) NULL y DATETIME de segundos; fecha_recepcion mantiene DEFAULT CURRENT_TIMESTAMP. Deduplicación de sensor_id/espacio_historico_id/tipo/valor NULL-safe/fecha_evento mediante transacción y lock del sensor, no garantía inexistente del SQL.

Espacio histórico se fija desde asociación validada al aceptar, no se recalcula al reasignar; eventos inmutables y sin purga automática en piloto. max_antiguedad_lectura_min conserva nulabilidad, unidad y CHECK de política; no sustituir por ventana UI. Las 23 tablas académicas/156 columnas/45 FK y 36 permisos materializados permanecen sin cambios; sensores/eventos todavía no se materializan, politicas_liberacion sigue únicamente estructural.


## Actualización de Fase 7

Dos migraciones IoT exactas materializan sensores_iot/eventos_ocupacion. Total instalado: 25 tablas académicas, 169 columnas y 48 FK, más migrations. Sin alteración del SQL final de 31/211/63 ni de migraciones anteriores. Las tres FK nuevas conservan CASCADE/RESTRICT; no se añaden tablas de evaluación. [Implementación y verificación](IOT_IMPLEMENTATION.md).

## Preservación del SQL de liberación — Fase 7.5

[D-08 cerrada](DECISIONS.md#resultados-persistencia-y-disponibilidad). Los cuatro apartados físicos anteriores se conservan íntegros; la frase histórica «versión pendiente» del diccionario queda resuelta por reglas de aplicación, no por cambios SQL. Politicas_liberacion sigue estructural (9 columnas). Las tres tablas futuras de evaluación suman 10 columnas y seis FK: reserva/política obligatorias y dos pivotes M:N con PK compuesta. Mantener ON UPDATE CASCADE en las seis; ON DELETE CASCADE solo de pivot a evaluación y RESTRICT hacia reserva, política, evento y validación. No extender la excepción QR a nuevas FK sin conflicto acreditado y autorización.

Motivo permanece VARCHAR(500) NULL físicamente y será obligatorio en aplicación. No UNIQUE de evaluación terminal, snapshots JSON, watermark ni FK de política en reservas; idempotencia usa lock de reserva e historia/pivotes existentes. Antigüedad NULL se exige funcionalmente cuando no usa IoT; con IoT debe ser entero >=0. Nombre/version y campos de decisión serán inmutables por versión; activa solo gobierna uso futuro. El historial de asociaciones conserva datos originales, no se recalcula con inventario/configuración actual.

En 7.5 permanecen instaladas **25 tablas académicas, 169 columnas, 48 FK y 39 permisos**; cero tablas evaluaciones_* y cero permisos liberacion.*. El alcance futuro de Fase 8 llevaría a 28/179/54 y 43 permisos; son proyecciones, no migraciones/seed ejecutados. El modelo final 31/211/63 permanece intacto.

## Contrato de persistencia de trazabilidad — Fase 8.5

[D-12 y esquemas canónicos](DECISIONS.md#contrato-blockchain--cierre-d-12-de-fase-85). Las dos secciones físicas anteriores se conservan literales: registros_actividad tiene 13 columnas/6 FK y evidencias_trazabilidad 11 columnas/1 FK. Solo cambia en la futura materialización la acción ON UPDATE de fk_registro_actor según autorización superior; no se eliminan CHECK, UNIQUE ni índices. Las otras seis FK de ambas tablas conservan ON UPDATE CASCADE y todas ON DELETE RESTRICT.

Registros críticos: una fila EXITO/critica=TRUE por transición, accion exacta, responsable y fechas históricos, usuario afectado, recurso y reserva o préstamo; incidencia NULL. Se crea en la transacción principal. La tabla permite otros resultados para CU30 futuro, pero Fase 9 no implementa esa bitácora general. No añadir estados/flags/índices de deduplicación al SQL por conveniencia.

La evidencia se crea después del commit. FK NOT NULL + UNIQUE(registro_actividad_id) y UNIQUE(hash) mantienen una evidencia por actividad; 0..1 físicamente hasta recuperarla y exactamente una para cada actividad crítica procesable. Inicialmente PENDIENTE; algoritmo SHA-256; versión espacio-u-jcs-v1; hash hex minúsculo de 64 caracteres, aunque la columna admite 255. Fecha local del servidor; referencia, fecha externa y ultima_verificacion NULL; resultado_verificacion PENDIENTE por su NOT NULL/default oficial. No confundir este default con una verificación ejecutada.

Referencia: eip155:11155111/contrato/tx, 125 caracteres; puede ser candidata en PENDIENTE. Fecha externa del bloque confirmado traducida a America/La_Paz. Solo tras confirmación coherente pasa a REGISTRADA. CU25 escribe ultima_verificacion/resultado_verificacion; no cambia hash, versión ni negocio. Una interrupción de red no borra registro previo.

Sin columna JSON canónico, payload en descripcion, tabla auxiliar, timestamps Laravel adicionales ni persistencia de claves/RPC/nonce. Se reconstruye con campos históricos protegidos; NULL y string vacío no son equivalentes. Las fechas canónicas son UTC aunque el DATETIME siga local. La integridad de contexto, inmutabilidad histórica y transición única corresponden a servicios/locks además del SQL; no se atribuyen a constraints inexistentes.

Estado instalado conservado: **28 tablas académicas, 179 columnas y 54 FK**. Fase 9 agregará únicamente estas dos: proyección 30 tablas, 203 columnas, 61 FK; el modelo completo permanece 31/211/63. Estos son conteos del contrato, no migraciones ejecutadas ni una nueva inspección de BD en Fase 8.5. Los tres permisos futuros no se insertan ahora.

## Materialización de Fase 9

Migraciones 000029/000030: registros_actividad (13 columnas, 6 FK) y evidencias_trazabilidad (11 columnas, 1 FK), fieles al SQL salvo ON UPDATE RESTRICT autorizado en fk_registro_actor. chk_registro_responsable, defaults y UNIQUE actividad/hash conservados. Ninguna de las 28 tablas previas se modifica. Resultado: 30 tablas académicas / 203 columnas / 61 FK; migrations es técnica. El modelo documental completo sigue conteniendo 31 tablas: notificaciones aún no se materializa. Sin columna canónica, outbox ni backfill.

[Pruebas de esquema e historia inmutable](BLOCKCHAIN_IMPLEMENTATION.md#esquema-actividades-y-atomicidad). Fase 9 §11 precisa actor humano real y proceso_responsable NULL en las cuatro actividades; la tabla de decisión histórica 8.5 se interpreta con esta instrucción posterior.

## Preservación del SQL de notificaciones — Fase 9.5

[Contrato RF21/CU26](DECISIONS.md#notification-contract--readiness-for-phase-10), N4/N6/N9, contrastado nuevamente con SQL Word/PDF pp. 295–299. La tabla futura es exactamente la descrita en [notificaciones](#notificaciones): **8 columnas**, con PK id, destinatario_id y registro_actividad_id obligatorios, titulo VARCHAR(160), mensaje TEXT, enum oficial de ocho tipos, creada_en DATETIME DEFAULT CURRENT_TIMESTAMP y leida_en DATETIME NULL. Nombres de FK: fk_notificacion_destinatario y fk_notificacion_evento_origen; ambas ON UPDATE CASCADE / ON DELETE RESTRICT. Índices idx_notificacion_destinatario(destinatario_id, leida_en) e idx_notificacion_fecha(creada_en). No UNIQUE de entrega, timestamps automáticos, JSON, soft delete ni esquema del sistema genérico de notificaciones de un framework.

La unicidad lógica actividad+destinatario+tipo será responsabilidad del servicio mediante lock por actividad y lote completo en una transacción. Los índices existentes no equivalen a UNIQUE. registros_actividad conserva sus trece columnas y su inmutabilidad; no añadir procesada_en ni lista de receptores, ni reutilizar descripcion como estado mutable. Solo las nuevas actividades CU23 llevarán en descripcion la referencia humana segura a evaluación/política definida en N4; la cardinalidad no adquiere una FK inexistente. Las demás nuevas acciones usan contexto FK y descripcion NULL. Se conserva fk_registro_actor ON UPDATE RESTRICT, excepción ya autorizada para MySQL, y el CHECK de actor o proceso responsable.

No hay notification_outbox ni tabla snapshot de destinatarios. La ausencia de marcador/snapshot limita la recuperación administrativa después de un lote persistido: N6 fija ventana de 24 h, cierre del grupo y reparación directa detectable, sin prometer reconstrucción arbitraria de filas perdidas. Cambios de permisos no habilitan redistribuir historia.

**Estado real al cerrar 9.5: 30 tablas académicas / 203 columnas / 61 FK; notificaciones todavía ausente.** La futura implementación completaría 31/211/63, sin alterar las 30 tablas previas. No se creó migración, modelo ni índice. T-N01 y T-N10–T-N21 verificarán esquema/concurrencia/recuperación en Fase 10.

## Materialización física — Fase 10

Migración incremental 000031: ocho columnas, dos FK CASCADE/RESTRICT e índices oficiales. Sin UNIQUE nueva ni cambios de migraciones previas. Resultado instalado: **31 tablas académicas / 211 columnas / 63 FK**, más migrations técnica. [Implementación y evidencia](NOTIFICATIONS_IMPLEMENTATION.md).

## Lectura para dashboard y reportes — Fase 10.5

[Contrato DR1–DR12](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11). Se conserva íntegro el diccionario/SQL: **31 tablas académicas, 211 columnas, 63 FK**. Ninguna tabla, columna, índice, enum, migration o permiso de BD se añade en esta fase. No hacen falta nuevas tablas de negocio para Fase 11. Una propuesta distinta debe justificarse documentalmente antes de implementarse.

| Consulta futura | Fuente física y significado |
| --- | --- |
| Estado actual/cohorte de reserva | reservas.estado; actividad temporal inicio/fin; solicitud solicitada_en; resolución resuelta_en; cancelación cancelada_en |
| Liberadas del periodo | reservas.estado=LIBERADA y EXISTS evaluaciones_liberacion(reserva_id,resultado=LIBERAR,fecha_hora en rango), sin multiplicar por pivotes |
| Inventario/intervalo de espacio | recursos_institucionales + espacios_academicos; pisos/tipos; SpaceAvailabilityService considera reservas, bloqueos e incidencias existentes |
| Equipo y préstamo | recursos_institucionales + equipamientos_tecnologicos; prestamos.inicio_previsto/fin_previsto/solicitado_en/resuelto_en; vencido derivado, no columna |
| Incidencia | incidencias_mantenimiento.inicio/fin_previsto/estado/tipo/reportado_por_id/cerrada_en; sin descripción/solución en exportación inicial |
| IoT | eventos_ocupacion.espacio_historico_id/sensor_id/tipo/valor/fecha_evento/fecha_recepcion; no reemplazar espacio histórico por asociación actual |
| Notificaciones | notificaciones.destinatario_id/leida_en para contador central propio |
| Resumen operacional/auditoría de exportación | registros_actividad, columnas oficiales; DR7/DR10 limitan lectura/escritura futura |

DATETIME conserva America/La_Paz. Fechas civiles se convierten a [inicio de día, inicio del día posterior al final), sin 23:59:59. Un snapshot transaccional de lectura para consistencia no es una tabla de snapshots. Temporales de renderizado son privados y efímeros, fuera de la BD y Git.

Reutilizar índices ya documentados: estado/solicitante y periodo de reservas/préstamos, estado/tipo/piso de espacios, estado/tipo/base de equipo, recurso/estado de incidencias, índices IoT/evaluaciones/actividad/notificaciones. EXPLAIN y mediciones deben acreditar cualquier índice adicional en Fase 11 antes de proponer migración; no se anticipa aquí. COUNT/GROUP BY/EXISTS, proyecciones mínimas e iteradores evitan materializar tablas completas.

REPORTE_EXPORTADO futuro usa actor_id, accion textual existente, fecha_hora, resultado=EXITO, critica=FALSE y descripcion resumida; las FK de entidad, usuario_afectado_id y proceso_responsable quedan NULL. No cambia CHECK de responsable ni las cuatro actividades críticas. Los permisos previstos dashboard.ver/reportes.ver/reportes.exportar se documentan, no se siembran ahora.

## Esquema de Fase 11

Cero migraciones y cero índices nuevos. Se reutilizan tablas/columnas/índices operacionales y registros_actividad; SecuritySeeder añade tres filas de permisos y asociaciones elegibles. No métricas, snapshots, reportes persistidos o caché de negocio. JSONL/PDF/XLSX son temporales privados fuera del esquema y se eliminan. EXPLAIN/mediciones no justifican un índice adicional. [Informe](DASHBOARD_REPORTING_IMPLEMENTATION.md#mediciones-locales-y-explain).
