# Fase 7 — Sensores IoT y monitoreo de ocupación

**Estado vigente — Fase 10.5 documental (2026-10-26).** RF22/CU27 y RF23/CU28: [contrato de dashboard y reportes cerrado](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11), **0 bloqueantes documentales**. Fase 11 NO iniciada; CU30 NO IMPLEMENTADO. Fase 10 funcional se conserva, sin cambios de código, datos, permisos o dependencias. Los cierres anteriores mantienen su contexto histórico.

**Estado vigente — Fase 8 implementada (2026-10-24).** CU23 interpreta el conjunto completo de sensores habilitados y eventos en ventana D-08; minutos de política o 300 s opcionales, independientes de CU22. No altera valores de CU22, ingestión, HMAC ni disponibilidad base. Veto/contradicciones/unanimidad y concurrencia de inventario están probados; no supone conocer mensajes todavía en red. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

**Continuidad documental de Fase 7.5:** [D-08 resuelta](DECISIONS.md#evidencia-iot-de-cu23), implementación de CU23 todavía pendiente. Su interpretación exclusiva de PRESENCIA/OCUPACION, ventana de política, todos los sensores relevantes, veto/contradicción e historia enlazada no alteran la semántica genérica de CU22 ni IOT_MONITORING_FRESHNESS_SECONDS. Las pruebas y mediciones siguientes pertenecen a Fase 7; no se vuelven a declarar ejecutadas por el cierre documental.

Implementación de CU21/CU22, RF16/RF17 e información complementaria de CU06, conforme al [contrato D-10 congelado](DECISIONS.md#contrato-iot--cierre-d-10-de-fase-65). Workspace único `C:\Users\BlackMaster69\Desktop\ESPACIO-U`; rama `feature/phase-7-iot`, desde `89090ae`. La instrucción oficial de Fase 7 autoriza código, pruebas, revisión manual y publicación. La evidencia de fases anteriores conserva su contexto histórico.

No hay CU23, liberación automática, scheduler, evaluaciones de liberación, Blockchain, notificaciones funcionales, dashboard final, CU30, aplicación móvil ni broker MQTT. La parte de bitácora general de RF16 permanece diferida; los logs técnicos no sustituyen registros_actividad.

## Esquema y dominio

Solo dos migraciones nuevas: `2026_10_23_000024_create_sensores_iot_table` y `2026_10_23_000025_create_eventos_ocupacion_table`. Ejecutan el SQL oficial, sin alterar las 23 migraciones anteriores. Estado instalado: **25 tablas académicas, 169 columnas y 48 FK**, más la tabla técnica migrations; el modelo final sigue siendo 31 tablas, 211 columnas y 63 FK.

| Tabla | Columnas | Integridad |
| --- | --- | --- |
| sensores_iot | id, espacio_actual_id, identificador, tipo, estado, ultima_comunicacion | UNIQUE identificador; FK espacio actual; índices espacio/estado |
| eventos_ocupacion | id, sensor_id, espacio_historico_id, tipo, valor, fecha_evento, fecha_recepcion | FK sensor/espacio histórico; índices compuestos sensor-fecha y espacio-fecha |

Las tres nuevas FK conservan **ON UPDATE CASCADE / ON DELETE RESTRICT**. No hay nuevos CHECK, UNIQUE de eventos, nonce, columnas de claves, soft delete ni timestamps convencionales. DECIMAL(10,2) admite NULL; fecha_recepcion conserva DEFAULT CURRENT_TIMESTAMP. Las excepciones QR anteriores no se extienden a IoT.

SensorIot y EventoOcupacion no usan timestamps Eloquent; asignación masiva protegida. Enums exactos: EstadoSensorIot = HABILITADO/DESHABILITADO; TipoEventoOcupacion = PRESENCIA/OCUPACION. Relaciones: sensor→espacioActual, sensor→eventos, evento→sensor/espacioHistorico y espacio→sensoresActuales/eventosHistoricos. Factories sintéticas sin credenciales ni datos de personas reales.

## Gestión, permisos y provisionamiento

| Permiso | Administrador | Encargado de Laboratorio | Docente | Estudiante |
| --- | --- | --- | --- | --- |
| iot.sensores.ver | Sí | Sí | No | No |
| iot.sensores.gestionar | Sí | Sí | No | No |
| iot.monitoreo.ver | Sí | Sí | No | No |

Catálogo total: 39 permisos. Asignaciones iniciales A/E/D/S: 36/25/8/8. SecuritySeeder agrega únicamente permisos nuevos a roles existentes, sin restaurar permisos retirados. Gates centrales exigen elegibilidad y permiso del mismo rol; no hay bypass del Administrador. Todas las pantallas humanas requieren sesión vigente, y las mutaciones conservan CSRF.

CU21 está en `/iot/sensores`: búsqueda por identificador/tipo, filtros de estado/espacio, 15 filas por página, alta, detalle, edición de tipo, reasociación y habilitación/deshabilitación. Identificador ASCII `[A-Z0-9][A-Z0-9_-]{0,119}`, inmutable desde el alta y sin trim/cambio de caso. No hay eliminación de sensores ni edición/eliminación de eventos. La última comunicación no cambia por editar, reasociar o provisionar.

La gestión toma el coordinador RBAC Administrador → sensor X → espacio S → recurso S; reautoriza después del primer lock. La misma coordinación cubre provisión, estado y asociación. CU05 sigue permitiendo cambiar el código del recurso; el dispositivo debe actualizar explícitamente ese código.

La acción **Provisionar credencial** deriva en memoria y entrega después del commit, solo para un sensor habilitado. Respuesta JSON directa `private, no-store`, sin props de Inertia, flash, almacenamiento web, descarga ni persistencia. Se presenta en un campo inicialmente oculto y se retira al cerrar, navegar, cambiar de pestaña o desmontar; la impresión la excluye. Aviso: **«Guarde esta credencial de forma segura para configurar el dispositivo.»** Reprovisionar con el mismo identificador/maestro devuelve la misma clave: no es rotación.

## Configuración privada

| Variable | Valor predeterminado | Propósito |
| --- | --- | --- |
| IOT_MASTER_SECRET | Vacío en plantilla | 32 bytes CSPRNG en hexadecimal minúsculo, 64 caracteres |
| IOT_REQUEST_MAX_SKEW_SECONDS | 300 | Tolerancia técnica del request y futuro de medición |
| IOT_MONITORING_FRESHNESS_SECONDS | 300 | Doble frescura para la consulta informativa |
| IOT_RATE_LIMIT_PER_MINUTE | 120 | Solicitudes autenticadas por sensor y ventana de 60 s |
| IOT_MAX_BODY_BYTES | 4096 | Límite inclusivo de bytes RAW |
| IOT_ORIGIN_RATE_LIMIT_PER_MINUTE | 1200 | Protección adicional antes de autenticar, por origen |

Configuración central en config/iot.php. Se conserva el nombre IOT_MAX_BODY_BYTES de D-10. Desarrollo guarda su maestro exclusivamente en `.env`; testing usa otro maestro en `.env.testing`. Ambos archivos están ignorados y no se publican. No se usa APP_KEY como maestro IoT. Configuración ausente o malformada rechaza la operación con 503.

Para preparar un nuevo entorno local con los archivos privados ya creados:

```powershell
. .\scripts\dev-environment.ps1
php scripts/iot/initialize-secret.php .env
php scripts/iot/initialize-secret.php .env.testing
php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
npm run build
```

El inicializador escribe directamente en el archivo local y nunca muestra el secreto. No reemplaza un valor existente ni realiza rotación. Reiniciar los procesos que mantengan configuración en memoria. No ejecutar config:cache en estos entornos locales: evitar copias adicionales de secretos. En despliegue, tratar cualquier caché de configuración como material privado, restringir ACL/secret manager y no exportarla.

Compromiso individual: deshabilitar el sensor, registrar un identificador nuevo y configurar su nueva credencial; conservar la historia. Reasociar no cambia la clave. Compromiso/cambio del maestro: suspender ingestión, deshabilitar todos los sensores, sustituir el secreto privado, recargar todos los procesos, reprovisionar por canal seguro y habilitar explícitamente tras verificar. No admitir simultáneamente claves antiguas ni reactivar el mismo identificador suponiendo que se rotó.

## REST, firma y validación

Endpoint único: **POST /api/iot/events**, sin query ni barra final. HTTPS para despliegue/dispositivos externos; HTTP solo en entorno local/testing y conexión loopback. No cookies, usuarios humanos ni roles para dispositivos. La autenticación web mantiene sus controles separados.

`M = hex_decode(IOT_MASTER_SECRET)`; `K_sensor = HMAC-SHA256(M, UTF8("sensor:" + identificador))`, salida binaria de 32 bytes. El provisionamiento representa esa clave como hexadecimal; el dispositivo debe decodificarlo antes de firmar.

Cabeceras únicas X-IoT-Sensor, X-IoT-Timestamp y X-IoT-Signature. Timestamp positivo, 1–12 dígitos decimales, sin ceros iniciales, espacios, signo ni fracción. Firma hexadecimal minúscula de 64 caracteres. Cadena canónica exacta de cinco líneas UTF-8, LF y sin salto final:

```text
POST
/api/iot/events
<identificador canónico>
<timestamp Unix de la cabecera>
<SHA256 hexadecimal minúsculo del cuerpo RAW>
```

Firma = HMAC-SHA256 con K_sensor binaria sobre esa cadena; comparación hash_equals. Se deriva/compara antes de consultar registro. Desconocido y firma inválida reciben el mismo 401. Un sensor autenticado deshabilitado recibe 403. Cambiar cualquier byte del cuerpo exige otra firma, aunque el JSON sea semánticamente equivalente.

RawIotRequest evita la decodificación anticipada de JSON de Request::capture de Laravel. IotBoundary lee el stream hasta el máximo + 1, sin confiar en Content-Length; los transformadores de strings se omiten para esa ruta. Primero se valida envoltura/cabeceras/reloj/HMAC; después se parsea JSON. Las pruebas HTTP reales también comprueban cabecera repetida. En despliegue, el proxy debe preservar/rechazar duplicados antes de cualquier normalización SAPI que los pierda, limitar cuerpo y tiempo de lectura y excluir cabeceras/cuerpos de logs o APM.

| Campo | Validación |
| --- | --- |
| sensor_identifier | String obligatorio, exactamente igual a cabecera y registro |
| space_code | String ASCII 1–60, igual al código actual del espacio asociado, sin normalizar |
| type | PRESENCIA u OCUPACION |
| occurred_at | Fecha válida con T/Z mayúsculas o offset explícito, segundos enteros; sin fracciones, leap seconds ni -00:00 |
| value | Omitido/null → NULL; número JSON exacto sin exponente, hasta dos decimales y ±99999999.99 |

Solo objeto UTF-8 sin BOM; application/json con único charset=utf-8 opcional; Content-Encoding ausente/identity. Se rechazan arrays raíz, campos extra, valores anidados, duplicados incluso con nombres escapados, PII y recepción impuesta por el cliente. El parser conserva el token decimal: 1/1.0/1.00 son iguales, -0 es cero; NULL es distinto de cero. No redondea ni interpreta unidades o personas.

Fechas normalizadas conservando instante a America/La_Paz y verificadas contra rango DATETIME. La zona IANA se aplica también a fechas históricas. Aplicación y sesión MySQL usan configuración coherente; el reloj de aceptación del servidor prevalece. Dispositivos y servidor deben sincronizarse por NTP o equivalente. Se acepta request a ±300 s inclusive, revalidado tras esperar locks. Un evento > ahora + 300 s se rechaza; un pasado representable se acepta como historia. Futuro tolerado se almacena, pero aún no es actual.

## Transacción, historia e idempotencia

Después de autenticar/validar fuera de transacción, IotIngestionService exige una transacción nueva. Orden: sensor por PK FOR UPDATE → relectura de identificador/estado/asociación → espacio por PK FOR SHARE → recurso por PK FOR SHARE → contexto y reloj → primera lectura consistente de duplicados. No toma lock de RBAC, bloqueo global ni rango FOR UPDATE de eventos. Sensores distintos comparten locks de espacio compatibles.

Identidad semántica: `(sensor_id, espacio_historico_id, tipo, valor NULL-safe, fecha_evento)`. SQL `<=>` para valor. La primera lectura consistente ocurre después del mutex y ve el commit del escritor previo bajo REPEATABLE READ. No hay UNIQUE adicional. Dos mediciones iguales del mismo segundo son indistinguibles y se consideran una.

Duplicado: 200 + ID original, sin insertar, renovar recepción ni última comunicación. Nuevo: INSERT usa DEFAULT CURRENT_TIMESTAMP; se lee esa recepción y se copia a ultima_comunicacion dentro de la misma transacción; 201 después del commit. Fallo intermedio revierte ambas escrituras; máximo tres intentos ante deadlock, 503 si se agotan. Los tests inyectan el fallo entre INSERT y actualización y ejercen el reintento completo; las carreras de ingestión/gestión usan procesos y conexiones MySQL reales.

Asociación A→B: los eventos existentes conservan A; solo los aceptados con contexto actual B guardan B. Un payload A que esperó un lock se rechaza aun siendo histórico/duplicado. Cambiar el código del recurso durante la espera también se revalida. El monitoreo sigue aceptando datos en espacios bloqueados o en mantenimiento; no modifica su estado.

## Cuotas, respuestas y privacidad

Cuota autenticada por ID interno del sensor, ventana de 60 s desde la primera solicitud: 120 incluidas retransmisiones y rechazos posteriores a autenticación; la 121 recibe 429/Retry-After. HMAC falso no consume la cuota de la víctima. File cache con lock atómico coordina procesos de este host, en directorios distintos para desarrollo/testing; se rechazan stores array/null/failover. El mutex no vence mientras trabaja su dueño, evitando que un proceso suspendido sobrescriba el contador de otro. Si muere abruptamente, queda cerrado (503): detener la ingestión, comprobar que no queden workers y recuperar el lock del store antes de reanudar; no borrar cuotas durante tráfico. La espera normal del mutex se limita a un segundo y su liberación ocurre en finally. Redis compartido sería necesario para varios hosts, con la misma atomicidad y pruebas. Un fallo del limitador cierra con 503.

| HTTP | result |
| --- | --- |
| 201 / 200 | accepted / duplicate, con event_id |
| 401 / 403 | authentication_failed / device_not_authorized |
| 400 / 422 | invalid_json / invalid_payload |
| 409 | space_context_mismatch, sin revelar código esperado |
| 413 / 415 | payload_too_large / unsupported_media_type |
| 429 / 503 | rate_limited / temporarily_unavailable |
| 404 / 405 | not_found / method_not_allowed |

HTTP externo sin TLS se rechaza con secure_transport_required. Respuestas privadas no-store, errores sin stack ni eco de entrada. Logs de recepción contienen únicamente status; no cuerpos, cabeceras secretas, firmas, maestros, claves, usuarios ni PII. No hay analytics ni APM de provisión. El simulador tampoco imprime secretos, firmas o cuerpos. Las lecturas son evidencia ambiental técnica, nunca identificación de personas.

## Monitoreo y CU06

`/iot/monitoreo`: consulta administrativa paginada a 15, filtros sensor/espacio/tipo/fecha_evento, intervalo normalizado [desde,hasta), orden fecha_evento DESC, fecha_recepcion DESC, id DESC. Historial sin edición/borrado ni purga automática. Consulta actual mediante funciones de ventana SQL, por sensor/tipo, sin cargar todo el historial ni hacer N+1. Polling cada 3 s, con el comportamiento de pausa/reducción en segundo plano de Inertia.

Lectura ACTUAL exige **ambas edades entre 0 y 300 s inclusive**, sensor habilitado y asociación vigente igual al espacio histórico. Eventos atrasados no desplazan mediciones más recientes. Futuros no son actuales. Valores contradictorios del mismo sensor/tipo/instante se conservan y se muestran insuficientes, sin seleccionar una verdad arbitraria. Sin semántica física documentada, incluso la evidencia reciente mantiene **NO_DETERMINABLE**. No se suman ocupantes ni se interpreta cero como espacio libre.

Comunicación reciente se deriva de ultima_comunicacion (edad 0..300); evento antiguo nuevo puede renovar comunicación sin aportar ocupación actual. Silencio, deshabilitación, reasignación o falta de datos nunca implican LIBRE/DESOCUPADO/SIN_USO. No se crean enums ONLINE/OFFLINE/ERROR.

CU06 añade exclusivamente tipo, valor, medición, recepción/frescura e insuficiencia; no entrega identificadores de sensores, credenciales ni historial técnico. La interfaz retira evidencia vencida con tiempo transcurrido monotónico aun si deja de recibir refrescos. Si falla la consulta complementaria, CU06 conserva sus resultados y muestra no determinable. SpaceAvailabilityService no fue modificado: estados, bloqueos, reservas e incidencias siguen definiendo disponibilidad. IoT no aprueba, cancela ni libera reservas. La ventana técnica en segundos no sustituye max_antiguedad_lectura_min ni D-08/Fase 8.

## Simulador y comprobación local

Con servidor en loopback: registrar un sensor sintético desde CU21, provisionarlo y luego ejecutar:

```powershell
php scripts/iot/simulate.php IDENTIFICADOR-SINTETICO
```

El simulador de desarrollo lee únicamente el maestro del entorno local, deriva en memoria, usa el código actual del espacio y envía una solicitud real firmada a localhost:8000. Imprime solo status/result/event_id. No es firmware, no exporta claves y no admite otros destinos ni producción. El dispositivo físico utilizará únicamente su credencial derivada, nunca el maestro. ESP32 físico no fue necesario ni se declara probado.

## Verificación y trazabilidad de aceptación

Pruebas sobre MySQL real `espacio_u_testing`, con cuenta y maestro separados y TestingDatabaseGuard antes de operaciones destructivas. Se conservan los 325 casos anteriores; los ajustes en sus expectativas se limitan al catálogo ampliado, las dos tablas y las tres FK nuevas. No SQLite ni pruebas destructivas sobre desarrollo.

| Criterio | Evidencia automatizada |
| --- | --- |
| T-I01 | IotManagementTest; IotSchemaTest: alta, perfil, UNIQUE/FK, inmutabilidad, rutas sin delete |
| T-I02 | IotManagementTest: provisión/CSRF/no-store; IotConcurrencyTest: revocación durante espera |
| T-I03 | IotIngestionTest: maestro inválido y derivación por sensor/entorno; provisión independiente de referencia |
| T-I04–06 | IotIngestionTest: firma RAW, mutaciones, método/path/query, sensor desconocido/deshabilitado, límites/cabeceras; IotConcurrencyTest: reloj tras espera |
| T-I07–12 | IotIngestionTest: JSON, identidad/contexto, tipos, decimal léxico, fechas/offsets/calendario/rango y futuro/recepción |
| T-I13–14 | IotIngestionTest: historia antigua, NULL/cero, equivalencias, recepción y comunicación inmutables en duplicados |
| T-I15–17 | IotConcurrencyTest: eventos distintos, duplicado simultáneo con/sin historia, sensores distintos mismo/otro espacio, sin RBAC global |
| T-I18–20 | IotConcurrencyTest: deshabilitación/reasociación en ambos órdenes, código del recurso concurrente |
| T-I21 | IotResilienceTest: rollback, tres intentos, reenvío idempotente, rechazo de snapshot previo |
| T-I22–24 | IotMonitoringTest: límites de ambas edades, llegadas tardías, tipos, contradicción/NULL, silencio/estado/asociación |
| T-I25–26 | IotManagementTest/IotMonitoringTest: matriz, revocación, URLs directas, filtros/rango, orden/paginación e historia inmutable |
| T-I27–29 | IotIngestionTest/IotConcurrencyTest: 120/121, dos workers, víctima protegida, store inseguro, tamaño/tipo/encoding; logs y provisión privados |
| T-I30–31 | IotMonitoringTest y regresión: CU06 mínimo, estados/políticas intactos, ventana separada; núcleo de reservas/QR conservado |
| T-I32 | IotResilienceTest: 120 aceptados con dos sensores y 2.000 filas históricas; consulta actual y polling |

## Cierre de verificación

Validación local del 23 de octubre de 2026 sobre MySQL aislado en puerto 3308; no se modificaron las instancias existentes de 3306/3307. Las migraciones y el seed se aplicaron incrementalmente en desarrollo, conservando sus datos. Solo testing usa reconstrucción controlada.

| Control | Resultado |
| --- | --- |
| php artisan test --compact | **371 tests, 5.862 aserciones, 326,22 s; todos pasan** |
| Regresión | 325 casos anteriores conservados; 46 nuevos en seis clases IoT, incluidos 13 casos concurrentes con procesos MySQL reales |
| Pint | Correcto; formato aplicado y comprobado antes de publicar |
| npm run build | Correcto; assets de producción generados, sin modificar dependencias |
| composer audit | Sin avisos de seguridad ni paquetes abandonados |
| npm audit | Cero vulnerabilidades |
| Rendimiento de ingestión | 120 eventos aceptados y persistidos, dos sensores, 2.000 filas históricas; 1,886 s |
| Consulta del monitor | 2,31 ms SQL en el ensayo; polling de 3.000 ms |
| Reflejo en navegador real | **Cota superior de 2.569 ms** entre instante anterior al envío HTTP y primera observación del valor renderizado; respuesta 201 y fila persistida |
| Privacidad | Escaneo de archivos públicos, diff, índice/historial de la fase y artefactos locales; sin secretos detectados; .env/.env.testing ignorados y plantilla vacía |
| Integridad | 23 migraciones anteriores y núcleos de disponibilidad/reservas/QR intactos; hashes Word/PDF originales conservados |

La duración de suite incluye todas las fases implementadas. La medición de reflejo incluye envío, procesamiento, polling y observación, por lo que limita por arriba el tiempo desde recepción al reflejo. El ensayo local acredita este escenario, no garantiza carga/red/TLS de producción, disponibilidad mensual ni hardware ESP32. Debe repetirse al desplegar y ampliar el piloto.

### Revisión manual completada

| N.º | Comprobación | Resultado observado |
| --- | --- | --- |
| 1 | Sesión Administrador | Acceso a CU21/CU22 |
| 2 | Alta de sensor sintético | Registro visible y único |
| 3 | Provisión | Campo oculto por defecto, mostrar/ocultar y cierre; sin publicar su contenido |
| 4 | Asociación inicial | Espacio sintético A |
| 5 | Simulador firmado por HTTP real | 201 y evento persistido |
| 6 | Monitoreo | Lectura y ambas fechas visibles |
| 7 | Reenvío exacto | 200 y mismo ID, sin fila adicional |
| 8 | Evento distinto | 201 y nueva fila |
| 9 | Firma inválida | 401 genérico |
| 10 | Identificador desconocido | 401 genérico |
| 11 | Deshabilitar e intentar enviar | 403, sin evento nuevo |
| 12 | Habilitar | Estado actualizado desde CU21 |
| 13 | Reasignar A→B | Asociación actual B; historia A conservada |
| 14 | Enviar con código anterior | 409 |
| 15 | Enviar con código actual | 201 |
| 16 | Evento de 30 días antes | Aceptado como historia; medición distinta de recepción |
| 17 | Evento 360 s futuro | 422; límites ±300/301 cubiertos además con reloj controlado en tests |
| 18 | Frescura y contradicción | Sin lectura suficiente/no determinable, sin inferir libre |
| 19 | CU06 con Docente | Evidencia ambiental mínima; sin identificadores de sensores ni historia técnica |
| 20 | Reserva sintética existente | Sigue APROBADA tras las operaciones IoT, sin transición automática |
| 21 | Encargado de Laboratorio | Edición de tipo confirmada por UI |
| 22 | Docente en URL administrativa directa | 403 |
| 23 | Estudiante en URL administrativa directa | 403 |
| 24 | Responsive | 390×844, 768×1024 y 1440×1000; sin desbordamiento horizontal de página |
| 25 | Accesibilidad básica | Etiquetas de formulario y navegación por Tab entre filtros comprobadas |
| 26 | Consola | Sin errores/avisos JS en la vista final |
| 27 | Logs y secretos | Escaneo local y público sin valores privados; claves de QA retiradas tras probar |

Se conservaron los eventos sintéticos y se dejaron deshabilitados el sensor y los tres accesos temporales de QA, con sus sesiones revocadas. El servidor y la pestaña del monitor se mantienen disponibles; no hay envío de eventos programado. La revisión de accesibilidad es básica, no una certificación WCAG completa.

La publicación usa commits por esquema, implementación, pruebas y documentación sobre feature/phase-7-iot. Tras verificar el remoto, la integración permitida es fast-forward a main, sin force push. La comprobación de los SHA remotos y del árbol limpio forma parte del informe final, después de ejecutar los pushes.

## Continuidad hacia Fase 8

MQTT queda como adaptación futura opcional y deberá reutilizar autenticación/validación equivalente, deduplicación, contexto, frescura y privacidad; no se instaló broker. CU23 y sus tres tablas de evaluación permanecen ausentes. Próximo paso recomendado: una nueva instrucción para revisar/congelar D-08 y el contrato transaccional de liberación, combinando reserva, política, QR e IoT con evidencia temporal suficiente. No usar NO_DETERMINABLE como permiso para liberar. Esta fase no inicia ese trabajo.

Referencias técnicas: [normalización de solicitudes Laravel](https://laravel.com/docs/13.x/requests), [locks atómicos de caché](https://laravel.com/docs/13.x/cache), contrato D-10 y SQL académico en DATA_MODEL.md. La implementación se comprobó también contra el código instalado del framework.

## Consumo documental por CU27/CU28 — Fase 10.5

[DR4–DR6](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11) mantiene D-10 y el lector central. Espacios monitorizados = scope autorizado con al menos un sensor HABILITADO actualmente asociado; contar espacios distintos. Frescura requiere ambas fechas dentro de la ventana configurada y espacio histórico coincidente con actual. Recencia/contradicción no convierten valor genérico en una ocupación universal: el resultado sigue NO_DETERMINABLE. Sin porcentaje ni asistencia inferida; no persistir online/offline ni afirmar libre. Sin sensor es «Sin monitoreo»; error de consulta no se presenta como 0.

Resumen autorizado distingue con/sin evidencia reciente, sin confundir comunicación del dispositivo. El reporte usa fecha_evento y espacio_historico_id; conserva historia de sensores deshabilitados/reasignados. Columnas: espacio histórico, identificador de sensor, tipo, valor decimal nullable, fecha_evento,fecha_recepcion. Sin identidad personal, secretos, firma ni HMAC. La interpretación particular de CU23 no se importa al dashboard. Fase 11 no iniciada; ningún endpoint/servicio/sensor se modifica aquí.

## Integración con Fase 11

[Dashboard y reportes](DASHBOARD_REPORTING_IMPLEMENTATION.md) reutilizan las reglas, consultas y scopes de este dominio. No modifican sus estados, decisiones o productores. REPORTE_EXPORTADO no se añade a la whitelist de notificaciones ni a eventos críticos Blockchain. CU30 sigue NO IMPLEMENTADO; las limitaciones históricas de este informe conservan su alcance.


## Límite de asistencia previsto — Fase 11.5

Explicar evidencia/frescura/no determinable sin enviar sensores/eventos concretos o inferir asistencia/porcentaje de ocupación. El chatbot no ingiere eventos ni reinterpreta valor genérico; conocimiento administrativo requiere sus permisos. [Contrato CH1–CH12](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12). Cambio exclusivamente documental; Fase 12 NO iniciada.

## Continuidad documental de RF25/CU30 — Fase 12.5

El [contrato AC1–AC15](DECISIONS.md#activity-audit-contract--readiness-for-cu30) prevé SENSOR_CREADO/EDITADO/REASIGNADO/HABILITADO/DESHABILITADO y SENSOR_CREDENCIAL_CONSULTADA. La última audita preparación autorizada de credencial derivada, no rotación; sin almacenar secreto/HMAC/hash/identificador. Recorder futuro en transacción; fallo de auditoría impide revelación. R conserva espacio al evento, reasignación destino y referencia mínima de origen. Eventos de ocupación y comunicación normal no duplican bitácora; D-10 intacta.

Cambio exclusivamente documental. CU30 **NO IMPLEMENTADO**; implementación requiere nueva instrucción. La evidencia funcional anterior conserva su fecha y no se presenta como verificación nueva.


## Integración de auditoría — Fase 12.6

IotManagementService registra las seis acciones SENSOR_* de AC4: alta, edición de tipo, reasignación, habilitación, deshabilitación y consulta de credencial. Reasignación conserva recurso destino y referencias técnicas del origen; prevalece sobre edición. Provisionamiento audita dentro de transacción antes de revelar credencial: si falla, no hay revelación. No guarda identificador libre, secreto, firma o HMAC. ActivityVolumeTest ingiere veinte eventos firmados sin producir filas de actividad por lectura.

[Implementación RF25/CU30, pruebas y resultados de integración](ACTIVITY_AUDIT_IMPLEMENTATION.md). Los cierres de fases anteriores conservan su alcance histórico. Fase 13 no iniciada.
