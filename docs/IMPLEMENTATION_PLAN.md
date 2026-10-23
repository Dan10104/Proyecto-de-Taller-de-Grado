# Plan de implementación web

Estado: Fases 1–14 **no iniciadas**. Este plan respeta la secuencia solicitada; la única fase ejecutada ahora es la Fase 0 documental. No existe Flutter, Android ni cliente nativo en el plan de ejecución.

## Entorno observado el 2026-10-23

| Componente | Resultado comprobado | Siguiente paso |
| --- | --- | --- |
| Workspace / Git | Repositorio oficial clonado inicialmente vacío; rama main; origin oficial | Mantener una sola copia y verificar sincronización al iniciar cada fase |
| Visual Studio Code | 1.143.0; `code --status` mostró Folder ESPACIO-U y proceso `--new-window` con la raíz oficial | Continuar en esa ventana/carpeta |
| PHP CLI | 8.2.12, instalación XAMPP | Seleccionar versión compatible y soportada junto con Laravel en Fase 1; no asumir que cualquier versión reciente funciona con este PHP |
| Extensiones PHP | Disponibles PDO/pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, curl, dom, fileinfo, filter, hash, session y otras | Contrastar requerimientos de versiones elegidas; no confundir inventario con pruebas de aplicación |
| Composer | 2.9.8, utiliza el PHP observado | Confirmar requisitos y generar lockfile en Fase 1 |
| Node / npm | v24.15.0 / 11.13.0 | Validar compatibilidad con Vite elegido y fijar versiones/lockfile |
| MySQL | MySQL Server 8.0.46 y 9.7.0 instalados; servicios MySQL80 y MySQL97 activos | Elegir una instancia, puerto y credenciales privadas de desarrollo; no se probaron conexiones autenticadas ni se crearon BD |
| XAMPP mysql | El binario reporta MariaDB 10.4.32 | No utilizarlo como sustituto inadvertido del MySQL obligatorio |
| Docker | Cliente 29.5.3; motor desktop-linux no disponible durante revisión | Arrancar/verificar el motor antes de ensayos de contenedores; no afirmar que Docker ya funciona |
| Integraciones / nube | No configuradas ni probadas | Definir proveedores/contratos y configuración privada en sus fases |

No se instalaron dependencias de la aplicación, no se generó APP_KEY, no se ejecutó SQL y no se provisionó infraestructura. El entorno local está identificado y el workspace está preparado; compatibilidad final y conectividad de la aplicación son trabajo de Fase 1.

## Reglas comunes de ejecución

1. Nueva instrucción del usuario para comenzar la fase; abrir la raíz oficial, comprobar rama/remoto/estado y sincronizar sin perder cambios locales.
2. Resolver las decisiones D que bloqueen el punto afectado, registrando fundamento en el informe. Si existe contradicción, detener ese punto y consultar antes de modificar el modelo.
3. Usar ubicaciones convencionales del framework. Crear carpetas solamente al incorporar archivos reales. No producir copias completas del proyecto ni scripts temporales permanentes.
4. Implementar únicamente el alcance de la fase; mantener contratos de los componentes posteriores sin simular que ya funcionan. Planificar migraciones por dependencias: no crear FK hacia tablas inexistentes ni omitirlas silenciosamente del modelo final. Las ampliaciones de tablas por fases deben terminar equivalentes al esquema aprobado.
5. Ejecutar las pruebas pertinentes a cada cambio y comprobar invariantes integradas. Los hitos que requieren un componente posterior quedan explícitamente pendientes hasta su integración.
6. Revisar estructura, borrar temporales/duplicados, actualizar documentación, revisar diff/staged y secretos, commit coherente y push. Los mensajes propuestos pueden dividirse en commits pequeños, siempre descriptivos.

## Fases

### Fase 1 — Fundación técnica

| Campo | Plan |
| --- | --- |
| Módulos afectados | Configuración y arquitectura base |
| Carpetas principales futuras | Raíz convencional del framework; config/; routes/; resources/js/; tests/; docs/ |
| Requisitos relacionados | IS-01–06; IC-01–05; RD-01–14/23–26/30–32/43–44; RNF-23/25/28/32 |
| Casos de uso | Preparación transversal; ningún CU de negocio se declara terminado |
| Trabajo previsto | Elegir versiones compatibles; inicializar Laravel y Vue 3/Inertia/Vite en la misma raíz conservando docs; fijar lockfiles; configurar conexión MySQL, locale y estrategia horaria; punto de arranque y compilación; definir mecanismos de sesión/API sin implementar aún CU01. |
| Pruebas requeridas | Arranque, compilación de producción, configuración sin secretos, conexión de prueba a MySQL elegido y comprobación de stack real. |
| Riesgos / puertas | D-15/D-16; starter kits que agreguen autenticación, tablas o React; dos MySQL activos; .env local nunca versionado. |
| Condición de finalización | Base reproducible y documentada; build/arranque correctos; secretos protegidos; sin funciones de negocio anticipadas. |
| Commit esperado | `chore: establish Laravel Vue Inertia foundation` |

### Fase 2 — Autenticación, usuarios, roles, permisos y bitácora base

| Campo | Plan |
| --- | --- |
| Módulos afectados | Seguridad y Administración |
| Carpetas principales futuras | app/Models/; app/Http/Controllers/ y Requests/; app/Policies/; app/Services/; routes/; database/; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF1–3/25; RNF-10–16/21; DB-13/20–22; IC-04/05 |
| Casos de uso | CU01–CU04; CU30 básico |
| Trabajo previsto | Resolver D-01/D-03/D-04/D-07; credenciales y cuenta activa; sesión/logout; roles/permisos con historia; bitácora base y consulta autorizada. |
| Pruebas requeridas | Acceso válido/erróneo/inactivo; revocación; autorización directa; escalada de privilegios; historial de roles; no secretos en logs. |
| Riesgos / puertas | Inconsistencia de permisos; ámbito del encargado no modelado; incompatibilidad de sesiones; FK a entidades de fases posteriores. |
| Condición de finalización | CU de acceso/administración aceptados con pruebas negativas; bitácora base lista; migraciones compatibles con modelo aprobado. |
| Commit esperado | `feat: add access control users and activity audit` |

### Fase 3 — Pisos, espacios, características, bloqueos y disponibilidad

| Campo | Plan |
| --- | --- |
| Módulos afectados | Gestión de Espacios y Reservas |
| Carpetas principales futuras | app/Models/; app/Services/; app/Policies/; app/Http/; database/; routes/; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF4–5; DB-05/14/16/17/20; R-TR-01; R-UR-03 |
| Casos de uso | CU05–CU06 |
| Trabajo previsto | Catálogos y superclase de recursos; características M:N; bloqueos; búsqueda por fechas/condiciones; definir D-05 y dependencias con reservas/política. |
| Pruebas requeridas | Capacidad/código/periodos inválidos; bloqueos y mantenimiento; ámbito; cálculo de disponibilidad; <=3 s con 500 reservas sintéticas cuando exista el esquema relacionado. |
| Riesgos / puertas | No reemplazar RecursoInstitucional; no confundir estado de espacio con ocupación; dependencia temporal de tablas de reserva/política. |
| Condición de finalización | Catálogo y disponibilidad coherentes con contratos aprobados; dependencias FK planificadas sin relaciones ficticias; interfaz responsiva. |
| Commit esperado | `feat: add academic spaces and availability` |

### Fase 4 — Reservas

| Campo | Plan |
| --- | --- |
| Módulos afectados | Gestión de Espacios y Reservas |
| Carpetas principales futuras | app/Models/; app/Services/; app/Policies/; app/Http/; database/; routes/; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF6–7; DB-04/08/15–17/19/21; R-TR-02; R-PR-01 |
| Casos de uso | CU07–CU10 |
| Trabajo previsto | Solicitud, consulta, resolución y cancelación; estados/periodos definidos; control transaccional; actividad confirmada preparada para QR, liberación, notificaciones y evidencia. |
| Pruebas requeridas | Conflicto concurrente al registrar/aprobar; doble decisión; cancelación ajena/fuera de plazo; rollback; máximo 3 s. |
| Riesgos / puertas | D-03/D-05; políticas temporales sin definición; efectos externos antes del commit. |
| Condición de finalización | Flujos y excepciones aceptados; no solapamientos incompatibles; auditoría de decisiones y estados exactos. |
| Commit esperado | `feat: add reservation lifecycle and conflict checks` |

### Fase 5 — Equipamiento, préstamos, entrega, devolución e incidencias

| Campo | Plan |
| --- | --- |
| Módulos afectados | Gestión de Equipamiento y Préstamos |
| Carpetas principales futuras | app/Models/; app/Services/; app/Policies/; app/Http/; database/; routes/; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF9–15; DB-07/09/18/21; R-TR-03; R-PR-01 |
| Casos de uso | CU12–CU19 |
| Trabajo previsto | Inventario y disponibilidad; solicitudes/decisiones; entrega con receptor; retorno con condición/revisión; incidencias/mantenimiento; contratos condicionales QR para Fase 6. |
| Pruebas requeridas | Doble préstamo/entrega/devolución; identificación incorrecta; equipo prestado/mantenimiento/baja; retorno desconocido no cierra; incidencias simultáneas; <=3 s listados. |
| Riesgos / puertas | D-02/D-04/D-05/D-11; no inventar enum cancelado/finalizado; no marcar completo un flujo que exige QR antes de Fase 6. |
| Condición de finalización | Ciclo sin QR condicional probado; integraciones pendientes visibles; estados exactos e historia preservada. |
| Commit esperado | `feat: add equipment loans returns and maintenance` |

### Fase 6 — Códigos QR y validaciones

| Campo | Plan |
| --- | --- |
| Módulos afectados | Validación QR transversal |
| Carpetas principales futuras | app/Services/; app/Http/; app/Policies/; app/Models/; database/; routes/; resources/js/Pages/ y Components/; tests/ |
| Requisitos relacionados | RF8/13/14; IH-02/03; RD-08; R-TR-04; R-CC-04 |
| Casos de uso | CU11/CU20; extensiones de CU17/CU18 |
| Trabajo previsto | Resolver D-09; generación/identificación según alcance; lectura web; validación servidor; hash, objetivo, contexto y vigencia; correlación con entrega/devolución. |
| Pruebas requeridas | Cámara ausente/denegada; código falso/caducado/ajeno; permiso insuficiente; reintentos; inválido no muta; <=3 s y >=30/min. |
| Riesgos / puertas | Tratar QR como permiso; cambios silenciosos a UNIQUE/CHECK; exponer tokens; sustituir web por Flutter. |
| Condición de finalización | CU11/CU20 y ramas QR de entrega/devolución aceptados; contexto y persistencia consistentes. |
| Commit esperado | `feat: add contextual QR validation` |

### Fase 7 — Sensores IoT, eventos y monitoreo

| Campo | Plan |
| --- | --- |
| Módulos afectados | Monitoreo IoT y Disponibilidad |
| Carpetas principales futuras | app/Services/; app/Http/; app/Models/; config/; routes/; database/; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF16–17; IH-04–08; IS-11; IC-06/07; DB-06/10/23/27; RD-16/40; R-CC-05; R-PR-02 |
| Casos de uso | CU21–CU22 |
| Trabajo previsto | Resolver D-10; catálogo y asociación; contrato HTTP o MQTT piloto; recepción validada, historial, última comunicación y vista de ocupación. |
| Pruebas requeridas | Sensor desconocido/deshabilitado; duplicados/tardíos según contrato; reasignación histórica; ausencia no determinable; >=120 eventos/min sin pérdida aceptada; <=5 s reflejo. |
| Riesgos / puertas | Inferir libre de silencio; identificar personas; confundir fecha de evento/recepción; transporte/proveedor no definido. |
| Condición de finalización | Eventos válidos persistidos y consultables; fallos aislados; monitor distingue frescura y no determinable. |
| Commit esperado | `feat: add IoT sensors occupancy ingestion and monitoring` |

### Fase 8 — Políticas de liberación

| Campo | Plan |
| --- | --- |
| Módulos afectados | Monitoreo IoT y Disponibilidad |
| Carpetas principales futuras | app/Services/; app/Models/; app/Http/; database/; config/; rutas/tareas convencionales Laravel según versión; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF18; DB-08; RNF-26; R-PR-01 |
| Casos de uso | CU23; extensión de CU22 |
| Trabajo previsto | Resolver D-08; política/version/tolerancia; ejecución temporal; combinar QR/IoT; guardar evaluación y evidencias; modos ALERTA/AUTOMATICA. |
| Pruebas requeridas | Uso confirmado; QR/IoT contradictorios; datos insuficientes/antiguos; usa_iot=false; tolerancia; reserva ya modificada; concurrencia. |
| Riesgos / puertas | Liberación indebida; política mutable que cambia historia; ausencia de eventos impide disparador. |
| Condición de finalización | Cada resultado del enum probado; ALERTA no libera; AUTOMATICA solo con condiciones suficientes; historia de evaluación íntegra. |
| Commit esperado | `feat: add evidence based reservation release policies` |

### Fase 9 — Trazabilidad Blockchain

| Campo | Plan |
| --- | --- |
| Módulos afectados | Trazabilidad Blockchain |
| Carpetas principales futuras | app/Services/; app/Models/; app/Http/; config/; database/; ejecución diferida convencional si procede; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF19–20; RNF-17/19; IS-09/10; IC-09; DB-11/24; RD-17/20/41/42; R-DISP-06; R-PR-03/04 |
| Casos de uso | CU24–CU25; extensiones críticas acordadas |
| Trabajo previsto | Resolver D-06/D-12; representación/hash local, evidencia, adaptador externo, pendiente/reintentos y verificación. |
| Pruebas requeridas | Proveedor caído; privacidad previa; operación no confirmada; duplicados; alteración de datos; cuatro resultados; local <=3 s. |
| Riesgos / puertas | Datos personales on-chain; revertir transacción principal; serialización inestable; doble envío en varias instancias. |
| Condición de finalización | Operación persistente ante caída externa; evidencia recuperable y verificación reproducible; contrato/algoritmo versionados y documentados. |
| Commit esperado | `feat: add Blockchain evidence generation and verification` |

### Fase 10 — Notificaciones

| Campo | Plan |
| --- | --- |
| Módulos afectados | Servicios de Notificación y Asistencia |
| Carpetas principales futuras | app/Services/; app/Models/; app/Http/; database/; recursos de interfaz Vue; tests/ |
| Requisitos relacionados | RF21; DB-08/09/28; R-PR-07 |
| Casos de uso | CU26 y eventos de reservas/préstamos |
| Trabajo previsto | Resolver cardinalidad D-06; generación interna tras operación confirmada; destinatario/actividad; listado, detalle y lectura. |
| Pruebas requeridas | Acceso propio/ajeno; lectura; vacío; fallo de notificación conserva operación; enlace a evento correcto. |
| Riesgos / puertas | Enviar antes de commit; fuga por destinatario; introducir email/SMS no especificados. |
| Condición de finalización | Avisos internos consistentes y aislados; consulta personal y estado de lectura probados. |
| Commit esperado | `feat: add internal operation notifications` |

### Fase 11 — Dashboard, indicadores y reportes

| Campo | Plan |
| --- | --- |
| Módulos afectados | Reportes e Indicadores |
| Carpetas principales futuras | app/Services/; app/Http/; app/Policies/; resources/js/Pages/ y Components/; almacenamiento privado/configuración de exportación; tests/ |
| Requisitos relacionados | RF22–23; IU-06/07; IS-12/13; RD-09/22; R-TR-05/06; R-UR-03/06; R-PR-05/06 |
| Casos de uso | CU27–CU28 |
| Trabajo previsto | Indicadores por rol; filtros; resultados vacíos; PDF/Excel; descarga autorizada; agregados cuando se justifiquen. |
| Pruebas requeridas | Conteos contra datos fuente; filtros/permisos; <=5 s dashboard; <=30 s para 10.000 filas; otros usuarios siguen operando. |
| Riesgos / puertas | Fuga en exportación; métricas inventadas; confundir cero con falta de datos; archivos efímeros/públicos. |
| Condición de finalización | Indicadores verificables, exportaciones correctas y autorizadas, tiempos/carga medidos con dataset sintético. |
| Commit esperado | `feat: add role dashboards and PDF Excel reports` |

### Fase 12 — Chatbot

| Campo | Plan |
| --- | --- |
| Módulos afectados | Servicios de Notificación y Asistencia |
| Carpetas principales futuras | app/Services/; app/Http/; config/; resources/js/Pages/ y Components/; tests/ |
| Requisitos relacionados | RF24; IU-09; IS-07/08; IC-08; RD-15/42; R-TR-07 |
| Casos de uso | CU29 |
| Trabajo previsto | Resolver D-14 y proveedor permitido; integración backend, contexto autorizado y mensajes de error; únicamente orientación. |
| Pruebas requeridas | Solicitar acciones protegidas no cambia estado; datos ajenos excluidos; claves no llegan al cliente; fallo/timeout objetivo <=15 s. |
| Riesgos / puertas | Alucinación de permisos; ejecución de acciones; filtración al proveedor; tabla de historial no autorizada. |
| Condición de finalización | Asistencia acotada, proveedor aislado y navegación operativa durante caída; sin funciones protegidas ejecutadas por el bot. |
| Commit esperado | `feat: add scoped advisory chatbot` |

### Fase 13 — Auditoría web integral, seguridad, pruebas y rendimiento

| Campo | Plan |
| --- | --- |
| Módulos afectados | Todos los paquetes web |
| Carpetas principales futuras | tests/; ubicaciones convencionales de herramientas de QA; docs/; correcciones en carpeta propietaria |
| Requisitos relacionados | Todos los RF web y requisitos aplicables; RF25; RNF; R-TR/R-CC/R-UR/R-DISP/R-ESC/R-PR |
| Casos de uso | CU01–CU30; sin cliente móvil |
| Trabajo previsto | Completar bitácora transversal; recorrer flujos y excepciones; revisar modelo final, permisos, concurrencia, privacidad, accesibilidad práctica y rendimiento; reconciliar cobertura. |
| Pruebas requeridas | Todas las pruebas T-CU; datasets R-CC; doble volumen R-ESC; 30 concurrentes; 120 req/min API; revisiones negativas y fallos externos; secretos y archivos. |
| Riesgos / puertas | Declarar cubierto móvil; medir promedios en vez de máximos; usar datos reales; cerrar puertas pendientes sin evidencia. |
| Condición de finalización | Cada requisito web tiene evidencia o brecha explícita por resolver; pruebas críticas pasan y no quedan fallos incompatibles con entrega; se conserva lista móvil diferida. |
| Commit esperado | `test: validate integrated web workflows security and performance` |

### Fase 14 — Docker y preparación de despliegue

| Campo | Plan |
| --- | --- |
| Módulos afectados | Infraestructura de todos los paquetes |
| Carpetas principales futuras | Dockerfile y .dockerignore en raíz; compose convencional si corresponde; config/; .github/workflows/ si se incorpora CI; docs/ |
| Requisitos relacionados | RNF-18/22/30/31/32/37; RD-36–39/43/44; IC-01/02; R-DISP-01–05; R-ESC-01/05 |
| Casos de uso | Soporte operativo de CU01–CU30 |
| Trabajo previsto | Construcción reproducible; preparación Cloud Run + Cloud SQL MySQL + Artifact Registry; configuración privada, sesiones/tareas/archivos, respaldos y recuperación; guía de operación. |
| Pruebas requeridas | Imagen sin secretos; arranque/healthcheck; HTTPS; conexión segura; varias instancias; respaldo diario con siete retenidos; restauración objetivo <=4 h; procedimiento de disponibilidad. |
| Riesgos / puertas | Motor Docker inactivo; almacenamiento efímero; credenciales embebidas; costes/recursos externos; no confundir preparación con producción desplegada. |
| Condición de finalización | Imagen y configuración de despliegue verificadas, guía y ensayos disponibles; publicar/desplegar recursos solo dentro de la instrucción de esa fase; objetivos operativos con evidencia apropiada. |
| Commit esperado | `build: prepare Docker and Google Cloud deployment` |


## Propuesta exacta para la próxima instrucción: Fase 1

Comenzar con verificación Git/VS Code y lectura de D-07, D-15 y D-16. Consultar documentación oficial vigente para escoger versiones compatibles de PHP/Laravel, Vue 3, Inertia y Vite; elegir una de las instalaciones MySQL reales y comprobar conexión con credenciales privadas. Inicializar el framework en esta misma raíz conservando los documentos y `.gitignore`, sin starter de autenticación ni migraciones de negocio anticipadas. Configurar compilación, español, estrategia de zona horaria y separación de entornos; crear el `.env` local ignorado solo al necesitarlo, y generar allí la APP_KEY. Validar arranque/build/conexión, documentar versiones y comandos reproducibles, revisar secretos, hacer commit/push. Autenticación, CRUD y modelos definitivos de dominio comienzan en las fases correspondientes.

Nada de esa propuesta se ejecuta hasta recibir la siguiente instrucción del usuario.
