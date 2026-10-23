# Modelo de datos documentado

Estado: contrato documental congelado en Fase 0.5; **no es una migración ni un SQL para ejecutar**. Fuentes: figura 108 (clases), figura 109 (BD lógica), tablas 68–98 (mapeo), 99–129 (volúmenes/diccionario) y script del cap. VII, pp. 269–299 (PDF 270–300).

El script declara el esquema `ficct_reservas_prestamos`, `utf8mb4`, colación `utf8mb4_unicode_ci` e InnoDB. Se identificaron **31 tablas, 211 columnas y 63 claves foráneas**. Las PK simples `id` son BIGINT UNSIGNED autoincrementales; los subtipos comparten `recurso_id` sin autoincremento y cuatro asociaciones tienen PK compuesta. No se añaden `created_at`, `updated_at`, borrado lógico ni tablas de un starter kit sin revisar compatibilidad.

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
| PoliticaLiberacion | `politicas_liberacion` | `id` | Nombre/version únicos; tolerancia y antigüedad no negativas; versión histórica pendiente de precisar. | CU05, CU23 |
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

Concepto: **Reserva**. Fuentes: tablas 84 y 115; script `reservas`. Regla: Código único; solicitante/espacio obligatorios; fin>inicio; impedir solapamiento en servidor y transacción.

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
- Cada evaluación enlaza la política, reserva, eventos y validaciones utilizados; no concluir que esos enlaces congelan por sí solos una política mutable (D-08).
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
| Espacio/Evaluacion → PoliticaLiberacion | Política asociada a espacio y aplicada a evaluación | Espacio → 0..1 política; evaluación → 1 política; política → 0..* espacios/evaluaciones | espacios_academicos.politica_liberacion_id; evaluaciones_liberacion.politica_id | SÍ en espacio; NO en evaluación | No añadir FK reserva→política; evaluación conserva el vínculo existente. Versionado histórico se concreta en D-08. |
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
