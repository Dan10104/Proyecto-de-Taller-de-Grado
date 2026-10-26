# Validación integral web — Fase 13

Estado: **VALIDADO — cierre técnico de Fase 13**. Fecha de trabajo: 2026-10-26, America/La_Paz. No habilita Fase 14 ni despliegue. La matriz [CU01–CU30](FINAL_USE_CASE_VALIDATION.md) distingue evidencia automatizada, recorrido de interfaz y límites externos.

## Autoridad y alcance

Workspace oficial único; origen Git verificado. Inicio en main limpio, idéntico a origin/main: `e50fc387d3235af55e6ea557c37106220aaaebeb`. Rama `feature/phase-13-system-hardening`. Solo correcciones, endurecimiento, pruebas y documentación; sin nuevas tablas, índices, dependencias, permisos, estados o prestaciones de negocio. Word/PDF se conservan. RF26 móvil sigue diferido.

Fuentes: PROJECT_CONSTITUTION, ARCHITECTURE, DOMAIN_MODEL, DATA_MODEL, BUSINESS_RULES, USE_CASE_MATRIX, REQUIREMENTS_TRACEABILITY, SECURITY_POLICY, IMPLEMENTATION_PLAN, DECISIONS y los doce informes funcionales de seguridad, espacios, reservas, equipamiento/préstamos, QR, IoT, liberación, Blockchain, notificaciones, dashboard/reportes, chatbot y actividad. D-01–D-17, DR, CH1–CH12 y AC1–AC15 mantienen su autoridad. Un cierre anterior describe su fecha histórica, no el estado vigente de otro módulo.

## Baseline y pruebas

Baseline completa **antes de cambios productivos**: `php artisan test --compact --no-ansi`, 1.030 tests / 11.467 assertions / 971,96 s; 0 failures, errors y skipped. MySQL `espacio_u_testing`, nunca desarrollo ni SQLite. TestingDatabaseGuard verifica entorno, driver, nombre, host, puerto y directorio del MySQL aislado; cuenta testing no tiene acceso a desarrollo. No se tocaron las instancias MySQL del sistema.

Regresiones iniciales nuevas: SystemHttpHardeningTest reproduce el log no saneado y ausencia de cabeceras fuera del grupo web. SystemAuthorizationTest recorre cuatro roles y catorce revocaciones durante la misma sesión; seeder no repone permisos. SystemAttackSurfaceTest recorre todas las rutas web mutables con CSRF ausente, diez filtros hostiles, dos roles propios con IDs ajenos y mass assignment. SystemSchemaContractTest contrasta el diccionario completo. SystemCrossModuleTest conecta operaciones, actividad, evidencia, avisos, dashboard, reportes y disponibilidad.

Subset tras corrección: **147 tests / 3.136 assertions / 91,91 s**, con Authentication, SecurityBoundaries, QR HTTP, IoT ingestion y ChatbotFeature. Ataques adicionales: **14 tests / 161 assertions / 8,85 s**. Dos repeticiones críticas (Concurrency, ReleaseScheduler, NotificationRecovery, ReleaseMatrix y SystemCrossModule) pasaron: **178 tests / 1.730 assertions** cada una, **325,73 s y 332,37 s**, sin fallos intermitentes ni skips. Cuatro tests Node de foco pasan en 76,13 ms. Suite final completa: **1.072 tests / 12.964 assertions / 924,48 s**, 0 failures, 0 errors y 0 skipped. Se conservan los 1.030 casos anteriores y se añaden 42 casos PHP (más cuatro Node independientes). Los subsets complementan esta ejecución completa.

## Defectos encontrados

| ID | Severidad | Módulo / causa comprobada | Corrección / evidencia | Estado |
| --- | --- | --- | --- | --- |
| F13-01 | ALTA | Reporter general serializaba mensaje, excepción previa y traza. Un marcador sintético privado llegó al logger en memoria | Callback general conserva solo clase y huella de archivo/línea, después de los handlers específicos; SystemHttpHardeningTest | CORREGIDO |
| F13-02 | MEDIA | PrivateResponse estaba solo en web; 404, 500 y /up carecían de cabeceras y no-store | Middleware exterior global; protege también fallos de routing y límites IoT/chatbot. Conserva no-referrer de IoT. Regresión HTTP | CORREGIDO |
| F13-03 | BAJA | 500 genérico mostraba texto inglés; abort(500, mensaje) podía devolver detalles de la excepción | Con debug=false, JSON fijo en español y Error Inertia sin detalles; SystemHttpHardeningTest | CORREGIDO |
| F13-04 | BAJA | CORS heredaba configuración genérica con todos los orígenes; no había credentials wildcard, pero tampoco contrato explícito de API | Orígenes vacíos, sin credenciales; IoT servidor a servidor. CSP de navegación/embebido y HSTS condicionado a producción HTTPS | CORREGIDO |
| F13-05 | MEDIA | Ensayo PHP→bridge→contrato anterior a CU26 esperaba cuatro actividades totales y fallaba al encontrar PRESTAMO_APROBADO | Comprueba cinco actividades, una aprobación no crítica y exactamente cuatro hitos críticos; procesa solo estos cuatro. Integración local completa repetida | CORREGIDO |
| F13-06 | BAJA | Algunas celdas analíticas de trazabilidad y encabezados conservaban estados de fases antiguas como si fueran actuales | Estado vigente explícito, RF1–RF25 reconciliados y RF26 diferido; verificador conserva 217 textos y CH/AC | CORREGIDO |
| F13-07 | MEDIA | Tab/Shift+Tab podía salir del contenido de los diálogos nativos hacia el navegador | Helper compartido keepDialogFocus en ConfirmAction/OperationForm/ReservationActions; cuatro tests Node y repetición real de teclado/Escape/retorno | CORREGIDO |
| F13-08 | MEDIA | Breadcrumbs y eyebrow de fondo claro tenían contraste 3,98–4,12:1 y 1,67:1 | Color #526781 sobre #f5f7fb, 5,41:1; rótulos claros solo en heroes oscuros; medición de estilos y capturas | CORREGIDO |
| F13-09 | BAJA | Cambiar filtros durante una consulta pendiente podía perder la edición al llegar props anteriores | Fieldset disabled durante carga; exportación bloquea sus filtros; repetición real del selector y estado loading | CORREGIDO |
| F13-10 | BAJA | Atributos globales inicio/fin mencionaban bloqueo en errores de otros dominios | Etiquetas neutrales inicio/fin en validación española; reglas y validadores intactos | CORREGIDO |

No se atribuyen fallos del arnés a defectos de negocio. Config cache en Windows requiere APP_CONFIG_CACHE/APP_ROUTES_CACHE relativos al proyecto: el primer ensayo con ruta C: absoluta fue rechazado por la resolución de Laravel; se repitió con rutas privadas relativas y pasó. No se modifica el framework por ese uso incorrecto del arnés.

## Esquema, integridad y seeders

SystemSchemaContractTest pasa contra el diccionario de DATA_MODEL: **31 tablas académicas, 211 columnas y 63 FK**, más migrations técnica. Contrasta orden, tipo, NULL, default y autoincremento de todas las columnas; cada FK se compara por origen/destino, DELETE y UPDATE. Consulta de huérfanos para las 63 relaciones: cero. Las suites Schema de cada módulo prueban además CHECK, UNIQUE, enums, XOR y transacciones con inserciones reales. Instalación completa migrate:fresh --seed ocurre en testing protegido, sin pasos manuales.

Solo UPDATE RESTRICT en fk_qr_recurso, fk_qr_reserva, fk_qr_prestamo, fk_validacion_reserva, fk_validacion_prestamo, fk_validacion_entrega, fk_validacion_devolucion y fk_registro_actor; las otras 55 conservan CASCADE. Ninguna migración modificada o añadida. SecuritySeeder incremental conserva las 52 definiciones y cuatro roles; es idempotente, no crea cuentas ni contraseñas y no restaura permisos retirados. Se prueban revocaciones también de capacidades de recuperación mediante manipulación controlada de fixtures, sin modificar la política administrativa del producto.

## Seguridad transversal

Cuatro roles con permisos efectivos elegibles; ninguna condición Admin concede bypass. Matriz HTTP global cubre navegación común, gestión Admin/Encargado y seguridad/trazabilidad/bitácora exclusiva de Admin. Catorce revocaciones incluyen usuarios, reservas, préstamos, QR, sensores/monitoreo, políticas/evaluaciones, trazabilidad, avisos, dashboard, reportes, chatbot y actividad. Exportar vuelve a autorizar; revocar reportes.exportar invalida la siguiente petición sin REPORTE_EXPORTADO.

IDOR/BOLA: reserva/préstamo/QR ajenos 403; notificación/evaluación propia inaccesible 404. Scope administrativo forzado no amplía acceso. Ownership se aplica antes de paginar/contar. Request no controla responsables, código, decisiones ni criticidad; los campos protegidos se rechazan y los campos extra ignorados no cambian actor ni actividad. SQL se parametriza y los identificadores dinámicos vienen de catálogos internos. Payloads SQL/path en filtros se rechazan con 422. Reportes no aceptan rutas de descarga ni filas del cliente; StringCell XLSX conserva texto inerte incluso ante =,+,-,@ y controles.

Argon2id, errores de login genéricos, 5 intentos por correo+IP/60 s y 30 por IP/60 s. Regeneración de ID/CSRF; hash de sesión, ACTIVO, caducidad y revocación comprobados en cada request; logout fallido no simula éxito. HttpOnly, SameSite=Lax, 120 minutos iniciales y Secure fuera de local/testing. No se cambia .env del usuario. Todos los métodos mutables web conservan CSRF; la excepción de API IoT exige HMAC y no acepta sesión web como sustituto.

Cabeceras en toda respuesta de aplicación: no-store/private, nosniff, DENY; Referrer-Policy same-origin salvo la variante más restrictiva no-referrer de IoT. CSP: `base-uri 'self'; object-src 'none'; frame-ancestors 'none'`. Es protección de base, objetos y embebido, **no** una política script-src ni garantía contra XSS. Se mantienen el escape Vue y PDF literal. No bloquea Vite, Inertia, Blob QR ni getUserMedia. HSTS `max-age=31536000` solo si APP_ENV=production y request HTTPS reconocido; sin preload/includeSubDomains. Proxies confiables y terminación TLS se deben definir en Fase 14, nunca confiar en X-Forwarded-* arbitrario.

CORS explícito: API sin orígenes permitidos ni credentials. No cliente navegador cross-origin autorizado. Los dispositivos HMAC no dependen de CORS. 403/404/419/422/429/500 comprobados; debug=false para producción y límites QR/IoT/chatbot. 503 de datos/sesión conserva sus manejadores seguros. Logger general no conserva mensaje, request, URL/query, excepción previa, stack ni SQL. Los handlers específicos retienen sus códigos fijos; bitácora de negocio sigue siendo independiente y no se amplía su catálogo.

## Integraciones, tiempo y tareas

QR: generación criptográfica, hash, vigencia/rotación, XOR/contexto, transacción con hitos y ausencia de token en URL/log/storage. IoT: firma del cuerpo exacto, replay/duplicado, timestamps, sensor/estado/asociación y cuotas por sensor; sin lectura no significa libre. CU23 usa su propia interpretación congelada y ventana inclusiva hasta evaluación, conserva pivotes y deduplicación. Ningún estado derivado se persiste.

SystemCrossModuleTest observa aprobación→actividad crítica/evidencia local/aviso/dashboards/reporte/disponibilidad; entrega→vencimiento estricto→devolución con custodia/estado/evidencias/avisos/reporte; LIBERAR con vínculos QR/IoT, disponibilidad y A3 sin Blockchain. Reloj La Paz: inicio incluido, fin excluido; vencimiento solo después de fin_previsto; las suites previas cubren medianoche, tolerancia y esperas de locks.

Scheduler: trazabilidad:reconciliar cada minuto, reservas:evaluar-liberacion según config, notificaciones:reconciliar según config, prestamos:detectar-vencimientos según config; los cuatro withoutOverlapping(10). reportes:limpiar-temporales cada minuto con withoutOverlapping(5), barrido antes de una hora y lease para no borrar trabajos activos. Recuperación durable desde actividad/evidencia; no reejecuta negocio. GenerateNotifications aísla fallos y reconciliador reintenta; RegisterTraceabilityEvidence tiene tries=5, timeout=60 y backoff 60/120/300/900/3600 con throttle adicional, sin llamadas externas dentro del lock de negocio. Los productores usan afterCommit y conexión deferred; no se supone instalada una tabla jobs ni queue worker productivo inexistente.

Blockchain offline: contrato **1 test**, runtime **3 tests**, bridge PHP/ethers/contrato local completo aprobado. Verifica cuatro operaciones, confirmaciones, CU25, recuperación tras respuesta perdida, duplicados, cadena/contrato/writer, calldata y faults simulados de receipt/reorg. La red es local; no es prueba Sepolia.

## Rendimiento local y EXPLAIN

PHP 8.5.12, MySQL 9.7.4 aislado, Node 24.22.1. Fixtures solo testing: 50 espacios/equipos/sensores, 10.000 reservas, 500 préstamos, 10.000 eventos, 10.000 actividades y 1.000 avisos. Segundo ensayo duplica las cinco tablas de volumen (20.000/1.000/20.000/20.000/2.000); conserva catálogos. Cinco muestras por lectura; tres por formato. Misma máquina, sin objetivo de SLA ni representatividad institucional. Memoria aproximada del proceso PHP completo, no memoria exclusivamente atribuible a la query. Conteos incluyen autorización y relaciones del servicio/controlador medido, pero no transporte/middleware de HTTP.

| Operación | Media 10k ms | Máximo 10k ms | Máximo doble volumen ms | Consultas 10k / doble | Pico MiB |
| --- | ---: | ---: | ---: | --- | ---: |
| Dashboard Admin | 115.46 | 128.65 | 138.35 | 23 / 23 | 26 |
| Dashboard personal | 10.60 | 11.58 | 12.81 | 16 / 16 | 26 |
| Disponibilidad espacios + IoT | 30.60 | 35.46 | 35.63 | 7 / 7 | 26 |
| Disponibilidad equipos | 4.04 | 6.38 | 7.23 | 7 / 7 | 28 |
| Reservas administrativas + opciones | 18.42 | 19.11 | 25.61 | 15 / 15 | 28 |
| Préstamos administrativos + opciones | 12.90 | 13.32 | 14.22 | 16 / 16 | 28 |
| Monitoreo IoT | 35.78 | 40.66 | 43.42 | 10 / 10 | 28 |
| Bitácora individual | 7.79 | 10.05 | 12.01 | 6 / 6 | 28 |
| Preview reservas | 30.16 | 31.01 | 50.93 | 9 / 9 | 28 |
| Notificaciones propias | 1.52 | 2.09 | 3.00 | 2 / 2 | 28 |

| Exportación 10.000 filas | Media s | Máximo s | Pico MiB | Consultas | Bytes |
| --- | ---: | ---: | ---: | ---: | ---: |
| PDF | 2.845 | 2.856 | 58 | 50 | 1475083 |
| XLSX | 1.937 | 1.953 | 28 | 50 | 271412 |

PDF/XLSX cumplen aquí 30 s / 64 MiB; 9.999, 10.000 y 10.001 se revalidan en ReportExportsTest, sin truncamiento. La prueba de volumen doble no intenta exportar 20.000: excedería deliberadamente el contrato. Cada ensayo de exportación elimina su temporal y conserva su auditoría no crítica de testing.

EXPLAIN guardado para las tres consultas más lentas por escenario y ambos volúmenes. MySQL devuelve TREE: IoT materializa ventanas temporales y usa PRIMARY para sensor; el cohort de actividad que abarca todas las filas puede elegir scan y joins por PRIMARY; disponibilidad conserva NOT EXISTS e índices existentes. Un scan de todo un cohort no demuestra por sí solo necesidad de índice. Máximos observados bajos y conteos constantes al duplicar volumen: no se justificó índice, cache ni reescritura de reglas. No N+1 observado en estos recorridos; las suites previas verifican conteos para paginación pequeña/grande. La carga HTTP se resume a continuación.

## Referencia OWASP

Se utiliza [OWASP Top 10:2025](https://top10.owasp.org/2025/) y [ASVS 5.0.0](https://asvs.owasp.org/) como checklist de ingeniería, sin certificación ni afirmación de cumplimiento de un nivel ASVS completo. Revisión oficial: 2026-10-26.

| Área | Control comprobado |
| --- | --- |
| A01 acceso | RBAC elegible, revocación, IDOR/BOLA, scopes de exports |
| A02 configuración | debug, cabeceras, CORS, sesiones, caches aisladas |
| A03 cadena de suministro | lockfiles, auditorías separadas, excepción dev restringida |
| A04 criptografía | Argon2id, hashes de tokens, HMAC, JCS/bytes32, secretos locales |
| A05 inyección | query builder, filtros tipados, escape Vue, PDF literal, XLSX StringCell |
| A06 diseño | contratos de dominio y ownership, cuatro críticas, chatbot sin herramientas/datos |
| A07 autenticación | sesión activa/revocable, regeneración, CSRF, throttling |
| A08 integridad | constraints DB, transacciones, concurrencia, evidencia inmutable |
| A09 registro | bitácora mínima y logs técnicos sin contenido privado |
| A10 excepciones | respuestas seguras, rollback y consumidores externos aislados |

## Auditorías y smokes externos

Auditorías inicial y final F13: Composer 0 avisos y 0 abandonados; npm frontend 0; npm frontend --omit=dev 0; blockchain --omit=dev 0. Blockchain desarrollo mantiene **5 altas**, no cero. La última comprobación de registro ofrece Hardhat 3.21.1 pero aún declara Zod ^3.23.8; no se actualiza por novedad ni se fuerza Zod 4. La excepción GHSA-mrhx-c7xc-m3w5 / CVE-2023-54404, versiones/cadena y controles siguen en [BLOCKCHAIN_IMPLEMENTATION](BLOCKCHAIN_IMPLEMENTATION.md#excepción-temporal-de-desarrollo-autorizada). Retirar cuando exista combinación compatible corregida; no cubre runtime ni nuevos avisos.

| Integración | Estado comprobado F13 |
| --- | --- |
| OpenAI | PENDIENTE: no OPENAI_API_KEY local; no se hizo inferencia real. FakeProvider/fake HTTP aprobados |
| Sepolia | PENDIENTE: RPC disponible, wallet dedicada existente con 0 testETH; ninguna transacción ni dinero real |

## Preparación de configuración para Fase 14

No se despliega ni se crea Docker/Cloud Run/Cloud SQL/Artifact Registry/CI de deployment. Requisitos para la siguiente fase autorizada: APP_ENV=production; APP_DEBUG=false; APP_URL HTTPS canónico; DB aislada y cuenta mínima; secretos por mecanismo privado; cookies Secure/HttpOnly/Lax y expiración; trusted proxies explícitos; almacenamiento/sesiones y locks compatibles con topología elegida; scheduler único/coordinado; mecanismo deferred/queue y recuperación probados en el hosting; limpieza de reportes privados; acceso limitado a logs; respaldos/retención/restauración demostrados allí. OpenAI opcional con cuota; Blockchain opcional solo Sepolia y clave dedicada; maestro HMAC IoT fuera de imágenes/Git. Mantener toolchain Hardhat fuera del runtime productivo.

config:cache, route:cache y view:cache pasan en .local/qa/phase13/cache; login 200 con debug=false y rutas/config cacheadas. Se limpiaron las tres caches aisladas después; no se cambió la configuración local del usuario. Probe de producción confirma Secure=true, HttpOnly=true, SameSite=Lax y La Paz. El probe HTTP usa sesión array efímera para no interferir con sesiones reales; no prescribe ese driver para producción.

## QA de navegador, accesibilidad y flujos

Navegador integrado Chromium disponible; servidor PHP dedicado 127.0.0.1:8002 con APP_ENV=testing, TestingDatabaseGuard, CSRF real y sesiones privadas. Desarrollo 8000 y sus pestañas no se modificaron. Fixture aislado con cuatro usuarios/roles; el login válido/incorrecto y logout se recorrieron con credencial sintética desechable. Para cambios de rol de QA se usó un bootstrap de sesión privado, nunca una ruta del producto. FakeProvider local para CU29 y sin llamadas externas de Blockchain; no hardware físico ni smoke remoto atribuido.

Checklist por CU completo en FINAL_USE_CASE_VALIDATION. Sesión Admin recorrió 20 destinos; Encargado 16, Docente 10 y Estudiante 10: **56 enlaces principales, cero destinos rotos/inaccesibles dentro del menú permitido**. La prueba negativa manual obtuvo 403/404 en objetos ajenos, reportes y administración, como corresponde. La suite comprueba además revocación real en la misma sesión y todos los dominios. No se confunde ocultar un enlace con autorización.

Reservas: disponibilidad con bloqueo → solicitud estudiantil → aprobación → cancelación antes de inicio, actividad/evidencia/notificación y estados visibles. Equipamiento: edición y marca =1+1; incidencia cerrada con habilitación explícita; solicitud/aprobación/entrega, recepción NO_DETERMINABLE y revisión posterior. El equipo restringido no se habilita implícitamente al confirmar revisión. Textos HTML sintéticos en nombre de usuario/rol, propósito, bloqueo, cancelación, catálogo, condición/observación de entrega y solución se mostraron inertes. No ejecución de elementos de payload; Vue no incorpora v-html. Las notificaciones y bitácora no copian esos textos libres.

QR: uso de espacio, entrega y devolución registraron **tres VALIDAS**. Decoder real con MediaStream de canvas: 74/320/297 ms tras lectura en este ensayo. `persistedPayload=false` y `tracksEnded=true` en las tres; cancelar y desmontar dejaron cero tracks activos. No capturas de etiquetas ni valores de token. Fuente del arnés permanece en tests/Browser; npm run build elimina public/build/qr-test. La salvedad física previa del usuario se conserva.

IoT: edición de sensor, POST HMAC real a loopback con OCUPACION=0 devuelve 201 accepted; monitor muestra sensor/espacio histórico y fechas Bolivia sin afirmar espacio libre. No se abrió/capturó la credencial. Política: intento de reasignación con reserva vigente rechazado; scanner real sobre fixture nuevo produjo una evaluación ALERTAR, sin errores, conservando reserva. UI explicó falta de QR y ausencia de medición universal de vacío. LIBERAR con QR/IoT se valida en SystemCrossModule y las dos repeticiones de ReleaseMatrix.

Trazabilidad: siete evidencias locales derivadas de las operaciones sintéticas. CU25 comprobó coincidencia local y estado externo PENDIENTE; integración offline comprobó publicación/confirmación/recuperación. CU26: aviso propio pasa a Leída y navega a contexto autorizado; ajeno devuelve 404. CU29: pregunta sintética, respuesta FakeProvider, enlace allowlisted, limpieza de conversación y logout. CU30: filtro de aprobación, detalle individual de actividad/actor/contexto y vínculo a evidencia, sin controles de edición.

CU27: cuatro roles, diez tarjetas administrativas/siete personales, seis gráficos con representación tabular, hoy/7/30/custom y agrupación mensual >31 días. CU28: siete previews; inventario vs disponibilidad muestra un espacio DISPONIBLE operativo pero No para el intervalo bloqueado. PDF y XLSX descargados desde UI: PDF válido de una página, ESPACIO-U/FICCT, generador, columnas/filas; XLSX sin macros ni nodos fórmula, =1+1 como texto; exactamente dos REPORTE_EXPORTADO no críticas. Reporte operacional agrupa esas dos exportaciones y conserva separación de CU30. Sin filas y >10k se comprueban por ReportExports/ReportQueries; no se atribuye ese caso de volumen a una descarga manual nueva.

Responsive: **1440×900, 1366×768, 768×1024 y 390×844**. Dashboard, bitácora, preview, formulario de usuario, detalle de préstamo y asistente: 24 combinaciones, un h1 y cero inputs/selects/textarea sin label accesible; cero overflow horizontal global. Tablas conservan scroll interno. Inspección visual de las cuatro dimensiones, menú móvil/Escape, skip link, labels, estados loading, errores y vacíos. Los tres diálogos conservan foco y Escape/retorno; gráficos tienen tabla accesible. Capturas locales sin credenciales/QR; consola final sin warnings/errors JavaScript. No certificación WCAG completa ni prueba con todos los lectores de pantalla.

La corrección de contraste alcanza 5,41:1 para el color #526781 sobre #f5f7fb (rótulos pequeños). No se alteró la paleta de héroes oscuros; el contraste se comprobó mediante estilos computados y visualmente. Los cambios de filtros quedan bloqueados mientras una respuesta está pendiente para evitar la pérdida observada. Se verificó el selector disabled durante carga y la persistencia de Últimos 30 días al terminar.

## Carga HTTP local controlada

30 clientes independientes con cookie/sesión propia; 120 GET en cuatro tandas. Dataset manual: 4 usuarios, 4 reservas, 3 préstamos, 1 evento IoT, 64 actividades, 7 evidencias, 22 avisos, 3 validaciones QR y 2 evaluaciones. Resultado: **120 respuestas 200, cero errores**, 6,47 s total de las tandas. Solo lecturas de negocio (sesiones se renuevan normalmente), ninguna carga contra desarrollo o Internet.

| Endpoint | Clientes | Media ms | p95 ms | Máximo ms |
| --- | ---: | ---: | ---: | ---: |
| /dashboard | 30 | 877,39 | 1664,16 | 1710,26 |
| /actividad | 30 | 817,82 | 1579,96 | 1641,22 |
| /notificaciones | 30 | 676,47 | 1292,03 | 1338,31 |
| /reportes?reporte=reservas | 30 | 874,35 | 1662,83 | 1728,59 |

El servidor incorporado de PHP en Windows serializa atención; 30 clientes en espera **no equivalen a 30 workers productivos**. La tabla mide transporte, middleware y cola local, distinta del benchmark SQL/servicios 10k/20k anterior. No extrapolar SLA ni capacidad de hosting.

## Artefactos, privacidad y limpieza

Primer escaneo de cierre: 571 archivos públicos, 1.134 blobs de historial y 204 artefactos locales, cero hallazgos. Inspección de worktree, índice, diffs, historia, public/build, storage/logs y evidencia F13; PDF extraído y XML interno XLSX. Valores reales del entorno se comparan en memoria y nunca se imprimen. Capturas inspeccionadas visualmente: solo datos sintéticos, ninguna etiqueta QR o credencial. .env/.env.testing, runtimes, logs, temporales y build ignorados; .env.example sin secretos. ACL de env, testing, storage/app/private y QA: cero grants amplios de escritura a Everyone/Users/Authenticated Users; no chmod global.

Verificador contractual: **217 textos intactos, 30 CU, 17 decisiones, CH1–CH12, 20 route_key, 50 criterios chatbot, AC1–AC15, 56 acciones y 40 criterios actividad**. 103 archivos de tests baseline y 516 métodos originales conservados (data providers explican 1.030 casos). Word/PDF sin modificación por hash. 643 referencias Markdown comprobadas en el cierre, sin destinos ni anchors locales rotos; escaneo repetido sobre el índice y cada commit.

Las cuatro cuentas manuales se deshabilitaron y sus hashes se reemplazaron por valores aleatorios no conservados; cero cuentas activas/sesiones sin revocar. Sensores QA deshabilitados. Servidor 8002 detenido tras comprobar PID/comando; pestaña QA cerrada y viewport restaurado, pestañas del usuario conservadas. Historial de fixture se conserva hasta el reinicio autorizado de testing para la suite final; la limpieza posterior a la suite confirmó cero usuarios activos y cero sesiones sin revocar; la base ya no conservaba cuentas de las pruebas transaccionales. Reportes temporales privados se eliminan por respuesta/cleanup; copias de evidencia son sintéticas y privadas. Se retiraron los scripts privados de bootstrap con credencial desechable, archivos de sesión QA y copias de Descargas/temporales del navegador tras conservar solo evidencia segura en el workspace. No rutas de QA en routes/ ni arnés compilado en build final. Búsqueda estática sin dd/dump/ray/console.log en producto; único innerHTML es literal fijo de cierre de sesión en app.js, sin interpolación de usuario. No herramienta dinámica adicional instalada: pruebas HTTP/PHPUnit, revisión DOM/navegador y carga urllib locales, con ataques sintéticos no destructivos.

## Cierre técnico y readiness

Los **30 CU quedan VALIDADO** en su alcance web acordado; RF1–RF26 trazados y 217 textos normativos conservados. RF26 móvil continúa diferido. Los diez defectos F13-01–10 se corrigieron: cero defectos nuevos críticos, altos, medios o bajos abiertos. Esto no oculta las **cinco altas dev Hardhat/Zod** bajo la única excepción temporal autorizada, ni convierte los smokes externos pendientes en pruebas realizadas.

| Comprobación final | Resultado |
| --- | --- |
| Baseline PHPUnit | 1.030 tests / 11.467 assertions / 971,96 s |
| Suite final completa | 1.072 tests / 12.964 assertions / 924,48 s |
| Failures / errors / skipped | 0 / 0 / 0 |
| Nuevos casos | 42 PHP + 4 Node de foco; ningún test anterior eliminado |
| Repeticiones críticas | Dos ejecuciones de 178 tests / 1.730 assertions, ambas aprobadas |
| Pint | Aprobado |
| Build productivo | Aprobado, 408 ms; sin warnings críticos ni arnés QR publicado |
| Composer | 0 advisories / 0 abandonados |
| npm frontend / frontend omit=dev | 0 / 0 vulnerabilidades |
| Blockchain omit=dev / desarrollo | 0 / 5 altas bajo excepción dev |
| Caches de configuración, rutas y vistas | Aprobadas y limpiadas en entorno aislado |
| Secret scan | 0 hallazgos; worktree, índice, diff, historial, logs, build y evidencia |
| Cleanup testing | 0 usuarios activos / 0 sesiones sin revocar; servidor QA y credenciales temporales retirados |

Commits técnicos: `8939372` (errores y HTTP), `1952c37` (foco/contraste/filtros), `16e2a49` (regresión integral y ensayo Blockchain). Esta documentación constituye el cuarto grupo. Rama de entrega: `feature/phase-13-system-hardening`; integración autorizada fast-forward a main, sin force push. El cierre Git exige publicación de ambas referencias, main == origin/main y árbol limpio, verificados después del commit documental y reportados en la entrega final.

**Lista para preparación de despliegue cuando se autorice Fase 14. Fase 14 NO iniciada.** Siguiente trabajo recomendado: elegir topología y validar HTTPS/proxies, sesiones/locks, scheduler/queue, almacenamiento privado, gestión de secretos y restauración de respaldo con las condiciones descritas arriba. No Docker, nube, deployment ni aplicación móvil implementados en F13. OpenAI y Sepolia permanecen pendientes independientes, sin fondos reales.
