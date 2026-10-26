# Dashboard, indicadores y reportes — Fase 11

**Estado vigente — Fase 13 completada y validada (2026-10-26).** QA integral de CU01–CU30 y RF1–RF25 web; RF26 móvil diferido. [Informe de seguridad, rendimiento y pruebas](PHASE_13_SYSTEM_VALIDATION.md) y [matriz final por CU](FINAL_USE_CASE_VALIDATION.md). Los estados de fases anteriores que siguen son históricos. OpenAI/Sepolia pendientes independientes; excepción dev Hardhat/Zod explícita. Fase 14 NO iniciada.

Estado: implementada y verificada el 2026-10-26. RF22/CU27 y RF23/CU28, desde main limpio ffbed66. Rama feature/phase-11-dashboard-reports. Referencia: [DR1–DR12](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11). CU30 NO IMPLEMENTADO; Fase 12 NO iniciada.

## Precisiones de la instrucción de Fase 11

La petición actual §13 ordena contar evaluaciones LIBERAR por fecha_hora en el periodo. Esta precisión explícita prevalece sobre la fórmula histórica A3 que además exigía estado actual LIBERADA: el indicador contabilizará los hitos LIBERAR autorizados, sin inferir fecha ni depender de posteriores estados de reserva. Su etiqueta explicará que cuenta liberaciones registradas. Se mantiene autorización de reservas/evaluaciones y no se cuenta ALERTAR.

§59 añade objetivo de memoria de 64 MiB, configurable y medido, además del límite de archivo/temporales de 64 MiB de DR11. §100 se comprobará con 9.999, 10.000 y 10.001 filas; el límite es el entero 10000, no texto formateado «10.000». Nunca se trunca silenciosamente.

## Evaluación previa de dependencias — 2026-10-26

Entorno comprobado: PHP 8.5.12, Laravel 13, Vue 3.5.44. Antes de instalar, Composer dry-run resuelve seis paquetes nuevos, sin actualizar los existentes, y no encuentra advisories aplicables. Las cifras acumuladas de advisories en páginas de paquetes no significan vulnerabilidades activas de la versión seleccionada.

| Componente | Decisión y licencia | Comparación / verificación previa |
| --- | --- | --- |
| PDF | dompdf/dompdf 3.1.6, LGPL-2.1 | [Paquete](https://packagist.org/packages/dompdf/dompdf) / [API CPDF](https://github.com/dompdf/dompdf/blob/v3.1.6/src/Adapter/CPDF.php); PHP ^8.0 incluye 8.5, independiente de versión Laravel. Usar canvas PDF y texto local con paginación controlada, evitando construir un DOM HTML de 10.000 filas y sin interpretar HTML de usuarios. Medición posterior obligatoria |
| XLSX | openspout/openspout 5.12.2, MIT | [Paquete](https://packagist.org/packages/openspout/openspout) / [documentación](https://github.com/openspout/openspout/blob/v5.12.2/docs/documentation.md); declara PHP ~8.5.0, escritura por filas y tipos explícitos. Seleccionado para memoria acotada |
| Gráficos | Componentes Vue 3 + SVG locales, sin nueva dependencia | La instrucción §33 permite librería si es necesaria; seis gráficos simples de barras/tendencias tienen tabla accesible equivalente. Coordenadas visuales no calculan métricas de negocio. Impacto de bundle se medirá contra build previo |
| Alternativa PDF | mPDF 8.3.1, GPL-2.0-only | [Paquete](https://packagist.org/packages/mpdf/mpdf): compatible PHP 8.5; motor HTML y dependencias adicionales no necesarios para tablas programáticas |
| Alternativa PDF | tc-lib-pdf 8.82.2, LGPL-3.0-or-later | [Paquete](https://packagist.org/packages/tecnickcom/tc-lib-pdf): PHP ^8.2; mayor conjunto modular. No usar TCPDF legacy, declarado deprecado por su mantenedor |

Sin wrapper Laravel extra, CDN, LibreOffice ni servicio externo. Versiones directas exactas y lockfile versionado. Build previo guardado de forma privada; auditorías y mediciones finales consignadas a continuación. Ninguna excepción Hardhat/Zod autoriza vulnerabilidades del runtime productivo de reportes.

## Arquitectura y alcance implementado

RF22 → CU27 → DashboardRequest → DashboardService → consultas agregadas y servicios de dominio → Dashboard/Index.vue + AccessibleChart.vue → DashboardReportingTest/ReportContractTest. RF23 → CU28 → ReportRequest/ReportOptionsRequest → ReportRegistry/ReportDefinition → ReportFilters/ReportReferenceService → ReportQueryService → OperationalReportQuery/ActivityReportQuery → Reports/Index.vue → ReportGenerationService → ReportExportService → PdfReportWriter/XlsxReportWriter → ReportExportsTest/ReportQueriesTest/ReportContractTest.

[Servicios](../app/Services/Reporting), [dashboard](../app/Services/DashboardService.php), [rutas](../routes/web.php), [interfaz](../resources/js/Pages/Reports/Index.vue), [configuración](../config/reporting.php). GET /dashboard, GET /reportes, GET /reportes/opciones y POST /reportes/exportar requieren sesión institucional vigente. Inicio/login existentes se conservan. No hay tablas, índices, modelos de negocio, cache analítica ni servicios externos nuevos. No se inicia CU29/Fase 12. **CU30 = NO IMPLEMENTADO.**

### Permisos y roles

| Capacidad | Administrador | Encargado de Laboratorio | Docente | Estudiante |
| --- | --- | --- | --- | --- |
| dashboard.ver | Sí | Sí | Sí | Sí |
| reportes.ver | Sí | Sí | No | No |
| reportes.exportar | Sí | Sí | No | No |
| Contenido institucional | Según permisos efectivos de cada dominio | Según permisos efectivos de cada dominio | No | No |
| Dashboard personal | Si además tiene rol personal | Si además tiene rol personal | Propio | Propio |

PermissionCatalog pasa de 47 a 50 definiciones; SecuritySeeder idempotente incorpora solo capacidades nuevas a roles elegibles y no restaura permisos retirados previamente. No bypass por rol. Una cuenta mixta puede elegir vista; cada una conserva sus scopes. ReportingAccess comprueba estado, roles activos, permisos efectivos y vuelve a validarlos al finalizar la lectura/generación. Las opciones de filtros proceden de entidades del dataset autorizado, con búsqueda parametrizada y máximo 30 coincidencias; no exponen un directorio general.

### Diez tarjetas administrativas

| ID | Indicador / fórmula | Permisos adicionales |
| --- | --- | --- |
| A1 | Reservas estado PENDIENTE actual | reservas.ver_administrativas |
| A2 | APROBADA con inicio <= t < fin | reservas.ver_administrativas |
| A3 | Evaluaciones LIBERAR, A <= fecha_hora < B, reserva del scope | reservas.ver_administrativas + liberacion.evaluaciones.ver_administrativas |
| A4 | Distribución espacios DISPONIBLE/BLOQUEADO/EN_MANTENIMIENTO | espacios.ver |
| A5 | Préstamos PENDIENTE | prestamos.ver_administrativos |
| A6 | Préstamos ENTREGADO; desglose vigentes/vencidos | prestamos.ver_administrativos |
| A7 | ENTREGADO y t > fin_previsto (igualdad no vence) | prestamos.ver_administrativos |
| A8 | Equipos DISPONIBLE/PRESTADO/EN_MANTENIMIENTO/BAJA | equipamientos.ver |
| A9 | Incidencias ABIERTA por INCIDENCIA/MANTENIMIENTO | incidencias.ver |
| A10 | Espacios con sensor habilitado y monitoreo NO_DETERMINABLE | iot.monitoreo.ver + espacios.ver |

Admin y Encargado pueden ver las diez con su matriz inicial; cada tarjeta/gráfico desaparece si falta un permiso requerido. No se presupone igualdad de roles. A1/A2/A4–A10 son stocks actuales sin recorte por periodo; A3 es hito del periodo. Incidencias ABIERTA incluyen mantenimientos futuros según regla existente. No se añade tarjeta opcional ALERTAR: el catálogo inicial conserva diez. No métricas financieras ni Blockchain.

A4 expresa estado operativo, nunca disponibilidad para reservar. El reporte temporal reutiliza SpaceAvailabilityService::candidates, incluyendo reserva APROBADA, bloqueos activos, incidencias ABIERTA y estado operativo; LIBERADA no ocupa el intervalo. IotMonitoringService comparte su consulta de frescura entre monitoreo y summary; no carga eventos completos en PHP para contarlos. Ambas fechas recientes, sensor habilitado y asociación histórica=actual. Se muestra evidencia reciente/ausente; valor genérico no autoriza porcentaje de ocupación ni asistencia. Sin sensores: 0 espacios monitorizados; fallo de consulta: valor null / «Sin datos suficientes».

### Siete tarjetas personales y seis gráficos

P1 reservas propias PENDIENTE; P2 APROBADA propia con inicio >= t, total y listado de cinco por inicio/id; P3 préstamos propios PENDIENTE; P4 propios APROBADO pendientes de entrega; P5 propios ENTREGADO; P6 propios ENTREGADO con t > fin_previsto; P7 NotificationReadService::unread para destinatario actual. Docente y Estudiante reciben solo estas tarjetas habilitadas por sus capacidades, sin datos institucionales. user_id/actor/filas del cliente se rechazan. Ownership se aplica en SQL, no solo en componentes.

G1 reservas por estado (cohorte solicitada_en del periodo); G2 préstamos por estado (solicitado_en); G3 equipamiento actual por estado; G4 incidencias ABIERTA actuales por tipo; G5 solicitudes de reserva por fecha de solicitud; G6 solicitudes de préstamo por fecha de solicitud. Backend envía conteos y buckets completos; Vue calcula solo geometría SVG. Cada gráfico tiene tabla equivalente operable por teclado, etiquetas y valores; no depende de color/hover/canvas. Vacío: «Sin datos para el periodo seleccionado» y tabla con ceros válidos. No polling/WebSocket; cargar, filtrar, navegar o Actualizar.

### Tiempo y filtros

America/La_Paz en consultas DATETIME locales y presentación. Hoy, últimos 7 y 30 días incluyen hoy y los 6/29 anteriores. Personalizado valida fechas locales y A <= fecha_final; intervalo interno [inicio del primer día, inicio del día siguiente al último). Nunca 23:59:59 como límite de consulta. Tendencias diarias para hasta 31 fechas y mensuales para más de 31; meses extremos recortados por el filtro y buckets intermedios con 0. No se reconstruye un estado histórico a partir del estado actual. La fecha de corte de cada request es visible.

## Siete reportes, proyecciones y orden

| Reporte | Filtros y base temporal | Columnas de preview/PDF/XLSX; orden |
| --- | --- | --- |
| Reservas | periodo por solapamiento inicio < B y fin > A; estado, espacio, piso, solicitante autorizado | código, espacio, solicitante, inicio, fin, estado, solicitada_en, resuelta_en, cancelada_en; inicio/id ASC |
| Disponibilidad y espacios | inventario: piso/tipo/estado, sin fechas; disponibilidad: inicio/fin estrictamente crecientes y disponible sí/no | código, nombre, piso, tipo, capacidad, estado operativo; temporal añade disponible calculado; código/recurso_id ASC |
| Equipamiento tecnológico | estado, tipo, ubicación base o sin ubicación; inventario actual | código, nombre, tipo, marca, modelo, serie solo con equipamientos.gestionar, ubicación base, estado; código/recurso_id ASC |
| Préstamos y vencimientos | intervalo previsto solapado; estado, equipo, solicitante, vencido sí/no | código, equipo, solicitante, inicio/fin previstos, estado, vencido booleano, solicitado_en, resuelto_en; inicio_previsto/id ASC |
| Incidencias y mantenimiento | inicio en [A,B); tipo, estado, recurso | recurso, tipo, inicio, fin_previsto, estado, reportante autorizado, cerrada_en; inicio/id DESC |
| Ocupación IoT | fecha_evento en [A,B); espacio histórico, sensor, tipo | espacio histórico, sensor, tipo, valor, fecha_evento, fecha_recepcion; evento/recepción/id DESC |
| Actividad operacional | fecha_hora en [A,B); categorías/acciones permitidas y resultado | día local, acción, resultado, responsable permitido, categoría, referencia, cantidad; día/categoría/acción/resultado/clase e ID responsable/clave referencia ASC |

Todos los filtros se combinan por AND. Vencido=no niega la condición completa, no significa necesariamente ENTREGADO vigente; combinar estado=ENTREGADO si se desea eso. La fecha de solicitud alimenta tendencias; los reportes de reservas/préstamos usan intervalo de uso/previsto. Se muestran criterios temporales para evitar comparaciones incorrectas. Campos nullable permanecen vacíos (guion en preview/PDF). Proyección de nombres necesaria por dominio; no correo, CI, contraseñas, propósito/motivos, descripción/solución libre, credenciales IoT, QR ni canónicos Blockchain.

Actividad operacional aplica las 17 acciones de negocio de DR7 y REPORTE_EXPORTADO propia, permisos por familia y contexto consistente con los productores de Fase 10. Agrupa en SQL antes de paginar; Cantidad puede ser 1 sin convertirse en detalle individual. Actor humano: nombre+ID solo con usuarios.ver; de lo contrario Usuario #ID. Procesos se presentan con etiquetas seguras. Liberación requiere evaluación correlacionada exacta. Incidencia vinculada a devolución exige coherencia de préstamo/recurso/titular; contexto inválido queda fuera. No /actividad, búsqueda global, detalle universal, texto de actividad ni reconstrucción CU30.

### Preview, snapshot y exportación

Una definición sirve a preview y ambos formatos; mismo scope, filtros normalizados, columnas y orden. Preview paginada 25/50/100; export contiene dataset completo, no solo página. El servidor guarda en la sesión existente únicamente una firma de tipo/filtros/columnas/permisos/periodo resuelto. Un cambio de permisos, filtros o de día en un preset exige nueva preview. El cliente nunca envía filas ni identidad. Datos operacionales pueden cambiar entre requests: se informa cada corte, sin prometer un snapshot persistente de la preview.

Durante generación, COUNT acotado a límite+1 e iteración lazy de 250 filas usan la misma transacción de lectura MySQL REPEATABLE READ y orden estable. El dataset mínimo se materializa en JSONL privado para cerrar el snapshot antes de renderizar; no es una tabla de negocio. Revalidación actual de permisos fuera del snapshot antes de entrega. No carga Model::all(), SQL arbitrario, índices ni cache nuevos. Parámetros/whitelist en Query Builder/Eloquent.

PDF A4 horizontal con texto literal Unicode, marca ESPACIO-U/FICCT, título, generador, corte local, filtros, encabezados repetidos, filas alternadas y paginación. No HTML de usuario, recursos remotos, JavaScript o PHP embebido. XLSX una hoja Reporte, encabezados, filtros, generador/corte, auto-filtro, fechas Excel reales, números numéricos, booleanos y nulos vacíos. Sin macros. StringCell explícito para **todo** texto, incluidos encabezados/filtros y valores con =,+,-,@, espacios, tabulaciones o controles; nunca inferencia de fórmulas. XML normaliza CRLF a LF al leer, sin cambiar contenido semántico ni seguridad. Pruebas comprueban t=inlineStr y ausencia de nodos de fórmula/macros.

### Límites, temporales y auditoría

Defaults configurables: REPORT_EXPORT_MAX_ROWS=10000, REPORT_EXPORT_TIMEOUT_SECONDS=30, REPORT_EXPORT_MAX_BYTES=67108864, REPORT_EXPORT_MEMORY_BYTES=67108864, REPORT_TEMP_TTL_SECONDS=3600. Hasta 10.000 filas permitido; 10.001 rechazado antes del render. Sin datos: preview informativa y ningún archivo. Presupuesto de tiempo/memoria/tamaño se comprueba durante lotes y escritura; si se supera, error comprensible y cleanup, sin truncar ni publicar un archivo incompleto.

storage/app/private/reports/<aleatorio 128 bits>/, fuera de public; dataset y archivo no tienen URL permanente. Lease exclusivo evita borrar generación/descarga activa. Respuesta binaria autenticada, private/no-store/nosniff, limpia al terminar sendContent y como salvaguarda al destruir respuesta. Fallos limpian en finally/catch. Comando reportes:limpiar-temporales cada minuto, huérfanos desde TTL-60 s para respetar máximo de una hora cuando scheduler funciona; TTL configurable se acota a una hora. No reusar carpetas ni conservar archivos de reportes por defecto. Filename fijo espacio-u_<reporte>_<YYYYMMDD-HHMMSS>.<ext> sin entrada libre.

REPORTE_EXPORTADO se registra una vez por archivo generado, validado y autorizado listo para respuesta; resultado EXITO, actor autenticado, critica=false, proceso_responsable=null, todas las FK de negocio null y descripción acotada tipo/formato/corte/filas/filtros tipados no sensibles. Preview/fallo no crean EXITO. Una nueva request exitosa crea su propio registro. La red no puede garantizar que el cliente guarde el archivo: éxito significa generación autorizada lista para descarga, conforme DR10. No Blockchain ni notificación. La propia fila se inserta después del snapshot; un reporte de actividad incluye solo exportaciones propias anteriores, evitando ciclos.

## Mediciones locales y EXPLAIN

MySQL espacio_u_testing, instancia aislada; PHP 8.5.12, datos sintéticos. 10.000 reservas con un recurso/titular, sin concurrencia: preview 0,376 s; dashboard 0,032 s; PDF 6,300 s / pico absoluto PHP 54 MiB / 1.367.351 bytes / 715 páginas; XLSX 5,522 s / 28 MiB / 264.098 bytes. Ambos temporales eliminados. No benchmark de producción ni garantía universal de hardware/datos; valores largos, más dominios y concurrencia requieren medición del piloto Fase 13. Pruebas PHPUnit elevan el presupuesto de proceso a 256 MiB por clases de toda la suite retenidas; estas mediciones separadas usan el default real de 64 MiB.

EXPLAIN FORMAT=JSON guardado localmente para reservas, disponibilidad, actividad agregada e IoT. Reservas usa índices existentes de relaciones/PK y ordenación; disponibilidad EXISTS/antijoin conserva índices del servicio central; actividad requiere agrupación/tabla temporal SQL; IoT usa joins PK y orden temporal. Sin evidencia de cuello de botella en el piloto local medido, **0 migraciones/índices adicionales**. EXPLAIN de datasets pequeños no demuestra escalabilidad de todas las cardinalidades; ampliar carga como validación posterior sin inventar índices.

## Verificación y operación

Regresión completa, pruebas del contrato, QA en navegador, accesibilidad/responsive y auditoría Composer remota completadas. La suite conserva los 656 tests previos; solo se actualizan expectativas históricas 47→50 permisos en siete pruebas de permisos y una de scheduler.

Matriz de pruebas: DashboardReportingTest cubre diez tarjetas, siete personales para ambos roles, seis gráficos, propiedad, permisos, fronteras exactas, días/meses/ceros e IoT. ReportQueriesTest cubre siete definiciones, proyecciones, filtros, SQL inyectado, solapamientos, servicio de disponibilidad real, serie restringida, histórico IoT y agregado operacional. ReportExportsTest cubre PDF/XLSX, hoja/celdas/fechas, nueve entradas de texto peligroso, auditoría por request, 9.999/10.000/10.001, fallos, memoria/tamaño, temporales privados. ReportContractTest cruza los siete reportes y ambos formatos, igualdad de dataset/orden, revocación durante generación, cambio de fecha/filtros, contexto inválido, separación de permisos y cleanup al servir.

Para habilitar: runtimes del proyecto; composer install; npm ci; php artisan db:seed --class=SecuritySeeder --force; npm run build; mantener php artisan schedule:run cada minuto (o schedule:work local). No migrate nueva de Fase 11, no acceso a MySQL ajenos. Configuraciones opcionales en .env local; plantilla sin secretos. Mantener /storage/app/private inaccesible desde servidor web. Sin requerir LibreOffice, proceso gráfico externo ni Hardhat para reportes.


### Límite técnico de puntos del dashboard

DASHBOARD_MAX_BUCKETS=1200 configurable limita solo el número de puntos de cada tendencia, comprobado antes de construir arrays/SVG/tablas. Sin este tope, las fechas válidas de MySQL permiten casi 108.000 meses, más de 200.000 puntos/filas entre las dos tendencias y un DOM desproporcionado. El límite permite hasta cien años mensuales y no establece una regla académica ni altera los periodos válidos de reportes. Exceso: error de filtro comprensible, sin truncamiento ni buckets omitidos. Su prueba configura dos puntos para verificar rechazo temprano.

### Revisión manual completada

Datos sintéticos en espacio_u_testing, servidor local de QA separado del servicio de desarrollo. Admin: diez tarjetas/seis gráficos, Hoy/7/30/personalizado y buckets mensuales con ceros. Encargado: misma matriz inicial; al retirar incidencias.ver quedan nueve tarjetas/cinco gráficos y el reporte de incidencias sale del catálogo. Docente y Estudiante: siete tarjetas, cero gráficos institucionales y ausencia de datos del otro titular. Navegación por teclado (Enter), menú móvil y tablas accesibles.

Las siete previews muestran sus columnas autorizadas. Disponibilidad: aula operativamente DISPONIBLE devuelve No para el intervalo aprobado, otra sala devuelve Sí. PDF y XLSX descargados desde preview; fixture =1+1 visible como texto y exportado a XLSX. Preview vacía bloquea archivos; 10.007 resultados muestran aviso de refinar sin truncamiento. Se comprobaron REPORTE_EXPORTADO no críticas, sin notificación, y cero carpetas temporales tras las descargas. Consola sin errores/advertencias durante los flujos completados.

Responsive: 1440×900, 1366×768, 768×1024 y 390×844; ancho de documento no excede viewport, tablas/gráficos anchos tienen scroll interno. PDF de 10.000 filas: pypdf extrajo todas las referencias primera/última y 715 páginas sin OCR; Poppler renderizó primera y última para comprobar texto Unicode, tabla, márgenes y footer. Capturas y resultados sintéticos se conservan solo en .local/qa/phase11 (ignorado).


## Cierre de verificación — 2026-10-26

- php artisan test: **747 aprobados, 9.111 assertions, 902,05 s**. Base 656 / 8.721 conservada; **91 tests añadidos**, ninguno eliminado. Expectativas de permisos/scheduler actualizadas para las capacidades nuevas; primera regresión detectó esos conteos históricos, corregidos antes de esta ejecución final.
- Pint aprobado. npm run build aprobado (582 ms en ejecución final); sin nueva dependencia JS. Chunk de Dashboard 6.277 bytes / gzip 2.761; Reportes 10.539 / gzip 4.362; componente compartido de periodo 1.566 / gzip 681 y CSS común. Carga por ruta; SVG incluido en dashboard, sin paquete de gráficos ni incremento de los chunks anteriores de QR.
- composer audit remoto: 0 advisories, 0 paquetes abandonados. Hubo fallo transitorio de DNS; la repetición con COMPOSER_IPRESOLVE=4 completó la auditoría, sin usar ignorar advisories/offline como aprobación.
- npm audit frontend: 0. npm audit --omit=dev frontend: 0. Módulo blockchain npm audit: **5 altas exclusivamente dev Hardhat/Zod bajo la excepción temporal autorizada previamente**; no se afirma cero ni se modifica ese toolchain. blockchain npm audit --omit=dev: 0. Retiro de excepción sigue como deuda del informe Blockchain.
- Lectura independiente de PDF/XLSX de 10.000 filas: mismo conjunto y **orden exacto** BENCH-00000…BENCH-09999; XLSX sin fórmulas/macros. Extracción pypdf/PDFium y render visual Poppler, sin OCR.
- QA manual y responsive completados arriba; tres descargas reales produjeron tres actividades no críticas, ninguna notificación y cero temporales restantes. Se habilitaron los tres permisos en el desarrollo oficial mediante SecuritySeeder, sin restaurar capacidades previamente retiradas.
- Secret scan: worktree, índice, diffs e historia contra valores locales y patrones; **0 hallazgos**, .env/.env.testing/material privado ignorados. Enlaces locales/anchors documentales validados. Los **217 textos normativos** se compararon con ffbed66 y permanecen iguales.
- Sin migración, índice o tabla nuevos. Sin fondos reales ni llamada Sepolia nueva; smoke real pendiente por testETH permanece independiente. **CU30 NO IMPLEMENTADO; Fase 12 NO iniciada.**

Rama de trabajo: feature/phase-11-dashboard-reports; base ffbed66. Publicación de commits y fast-forward a main se verifican como paso final de entrega, sin force push. No se crean repositorios/copias/ZIP paralelos. Bloqueantes funcionales de Fase 11: 0 tras las verificaciones anteriores. Para Fase 12 se recomienda cerrar proveedor, contexto autorizado, privacidad y fallos del chatbot orientativo conforme D-14/RF24/CU29, mediante una nueva instrucción; este cierre no la inicia.


## Límite de asistencia previsto — Fase 11.5

Solo orientación sobre tarjetas, filtros, siete reportes y PDF/XLSX según permisos. No enviar métricas/filas ni ejecutar previews/exportaciones desde el chatbot; CU30 sigue NO IMPLEMENTADO. La evidencia funcional Fase 11 conserva su fecha y alcance. [Contrato CH1–CH12](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12). Cambio exclusivamente documental; Fase 12 NO iniciada.

## Continuidad documental de RF25/CU30 — Fase 12.5

El [contrato AC1–AC15](DECISIONS.md#activity-audit-contract--readiness-for-cu30) separa CU30 individual para Administrador del resumen agregado/exportable RF23. ActivityReportQuery mantiene exactamente 18 acciones/scopes; no incorporar automáticamente las 38 nuevas, seguridad/configuración o todas las exportaciones ajenas. REPORTE_EXPORTADO conserva transacción previa a respuesta, NULL de contexto, descripción tipada segura y ausencia de Blockchain/aviso; falla de auditoría impide descarga. Preview/dashboard sin actividad. CU30 futuro no añade exportación PDF/XLSX propia ni acceso al Encargado.

Cambio exclusivamente documental. CU30 **NO IMPLEMENTADO**; implementación requiere nueva instrucción. La evidencia funcional anterior conserva su fecha y no se presenta como verificación nueva.


## Integración de auditoría — Fase 12.6

ReportAuditService delega REPORTE_EXPORTADO al ActivityRecorder sin cambiar actor, resultado EXITO, criticidad false, contexto NULL ni metadata tipada. El fallo obligatorio sigue impidiendo descarga. ActivityReportQuery conserva sus dieciocho acciones, agregación y scopes: no se incorporan automáticamente las 38 administrativas. Preview/dashboard no generan actividad. CU30 ofrece consulta individual separada y no exporta; ActivitySemanticsTest comprueba la frontera y el recorrido manual verificó una sola actividad por PDF exitoso.

[Implementación RF25/CU30, pruebas y resultados de integración](ACTIVITY_AUDIT_IMPLEMENTATION.md). Los cierres de fases anteriores conservan su alcance histórico. Fase 13 no iniciada.
