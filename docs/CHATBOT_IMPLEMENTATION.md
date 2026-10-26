# Chatbot de asistencia — Fase 12

RF24 → CU29 implementados bajo [CH1–CH12](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12), instrucción oficial de Fase 12 y D-04/D-07/D-14. Fecha de verificación: 2026-10-26. Workspace único ESPACIO-U; base main/origin/main `1105d41`; rama `feature/phase-12-chatbot`. Validación offline completada: 877 pruebas / 10.217 assertions, QA y controles de publicación aprobados; smoke OpenAI real pendiente por falta de credencial.

El asistente orienta sobre procedimientos autorizados. No ejecuta operaciones, consulta registros operativos, adjunta identidad, realiza búsquedas, abre herramientas ni conserva conversación. CU30 continúa **NO IMPLEMENTADO**, aplicación móvil diferida y ninguna fase posterior iniciada. El smoke Sepolia pendiente de testETH es independiente.

## Arquitectura implementada

| Pieza | Responsabilidad y archivo |
| --- | --- |
| Entrada | [ChatbotController](../app/Http/Controllers/ChatbotController.php), GET /asistente y POST /asistente/consultar; controlador delgado |
| Validación | [ChatbotRequest](../app/Http/Requests/ChatbotRequest.php): texto, Unicode, campos cerrados, sin eco ni flash |
| Límite exterior | [ChatbotBoundary](../app/Http/Middleware/ChatbotBoundary.php): inicio monotónico, debug desactivado, tamaño HTTP y no-store |
| Admisión | [ChatbotRateLimit](../app/Http/Middleware/ChatbotRateLimit.php): contador y lock compartidos por usuario |
| Aplicación | [ChatbotService](../app/Services/Chatbot/ChatbotService.php): autorización inicial/final, contexto, proveedor, validación y destino |
| Contrato | [ChatbotProvider](../app/Services/Chatbot/ChatbotProvider.php) recibe ChatbotContext y deadline, devuelve estructura; ningún repositorio de negocio |
| Integración | [OpenAIChatbotProvider](../app/Services/Chatbot/OpenAIChatbotProvider.php): Laravel HTTP Client, HTTPS Responses, schema y fallos |
| Pruebas | [FakeChatbotProvider](../app/Services/Chatbot/FakeChatbotProvider.php): doble inyectado exclusivamente en pruebas; no opción de configuración productiva |
| Contexto | [ChatbotKnowledgeBase](../app/Services/Chatbot/ChatbotKnowledgeBase.php), [catálogo](../resources/chatbot/knowledge.json) y ChatbotContext |
| Salida | [ChatbotOutputValidator](../app/Services/Chatbot/ChatbotOutputValidator.php) y [ChatbotRouteRegistry](../app/Services/Chatbot/ChatbotRouteRegistry.php) |
| Presentación | [Asistente/Index.vue](../resources/js/Pages/Asistente/Index.vue), enlace condicional en AppLayout |

No migraciones, tablas, modelos Eloquent, jobs, embeddings, vector DB ni dependencias nuevas. La única lectura MySQL necesaria al consultar corresponde a identidad/permisos/sesión existente; AccessSessionService puede renovar la sesión de seguridad. La prohibición de acceso a BD del chatbot significa **cero consulta operativa para componer contexto o responder**, no eliminar autenticación D-07. El provider no recibe acceso a Eloquent. Abrir la página utiliza el layout y sus datos habituales, que nunca se adjuntan al request de IA.

## OpenAI: verificación oficial y contrato HTTP

Revisión oficial realizada el 2026-10-26 antes de implementar: [modelo gpt-6-luna](https://developers.openai.com/api/docs/models/gpt-6-luna), [Responses y separación de mensajes](https://developers.openai.com/api/docs/guides/text), [Structured Outputs](https://developers.openai.com/api/docs/guides/structured-outputs?api-mode=responses) y [controles de datos](https://developers.openai.com/api/docs/guides/your-data). El modelo previsto sigue documentado; no se sustituyó. La comprobación documental no acredita habilitación de una cuenta ni una inferencia real.

POST fijo `https://api.openai.com/v1/responses`, autenticación Bearer solo en cabecera del servidor, validación TLS activa, redirects deshabilitados. Laravel HTTP Client existente; sin SDK adicional ni paquete frontend. Modelo de config, inicialmente `gpt-6-luna`; `reasoning.effort=none` admitido por la documentación revisada. Configurar reasoning_effort=null omite el parámetro sin cambiar modelo. No fallback automático.

Cada request contiene `store=false`, `stream=false`, `background=false`, `max_output_tokens=500` configurable y `text.format` json_schema strict. Instrucciones estables en archivo propio; mensaje developer con CAPABILITIES, KNOWLEDGE y ALLOWED_ROUTE_KEYS; único mensaje user con la pregunta actual. Sin tools, functions, web/file search, MCP, code interpreter, metadata de identidad, previous_response_id, Threads o Conversations. La pregunta anterior nunca se reenvía.

Schema exacto con todas las propiedades requeridas y additionalProperties=false:

```json
{"status":"ANSWER","answer":"Orientación autorizada.","route_key":"reservas.propias"}
```

status acepta ANSWER, OUT_OF_SCOPE y RESTRICTED; answer string y route_key enum de veinte claves o null. Laravel vuelve a validar estructura, duplicados incluso escapados, tipos, longitud, texto y autorización. Solo se admite un mensaje assistant completado con un output_text; refusal reconocido usa plantilla local RESTRICTED. Incomplete/token limit/error/vacío/múltiples mensajes/tool output/JSON inválido se descartan, sin mostrar ni truncar el payload crudo.

## Permisos y contexto por rol

Se incorpora exactamente **chatbot.consultar** a PermissionCatalog/SecuritySeeder: 51 permisos totales. Elegible para Administrador, Encargado de Laboratorio, Docente y Estudiante. Seeder incremental e idempotente; no restaura concesiones retiradas. GET/POST exigen cuenta ACTIVO, sesión D-07 vigente, CSRF para POST y permiso efectivo D-04. No bypass por rol.

| Actor | Contexto que puede recibir |
| --- | --- |
| Administrador | Fragmentos administrativos y generales que autoricen sus permisos vigentes; el nombre del rol no concede solicitar reservas/préstamos |
| Encargado | Fragmentos autorizados de sus dominios; no hereda trazabilidad administrativa ni permisos retirados |
| Docente | Orientación general y procedimientos propios/solicitudes habilitados; no procedimientos administrativos protegidos |
| Estudiante | Mismo principio de propios y capacidades efectivas, sin datos ni comparativas institucionales |

Unión de roles vigentes y elegibles, no comparación fija de nombre. Se autorizan todos los permisos de cada snippet **antes** de puntuar relevancia; capabilities salientes son únicamente la unión de los fragmentos seleccionados, no los 51 permisos. Tras volver del proveedor se recargan usuario, sesión y permisos. Revocación de cuenta/sesión/chatbot o capacidades del contexto descarta la respuesta con 401/403. Un destino conocido fuera del contexto o permisos se elimina y su texto se reemplaza por orientación insuficiente.

## Conocimiento versionado y selección

32 fragmentos, versión `2026-10-26`, 16 temas: autenticación, espacios, disponibilidad, reservas, QR, equipamiento, préstamos, entregas, devoluciones, incidencias, IoT, liberación, Blockchain, notificaciones, dashboard y reportes. Cada entrada declara id, versión, fuente funcional, topic, text, required_permissions, allowed_route_keys, keywords y prioridad. Máximo actual 311 caracteres por texto; límite contractual 800 por fragmento.

Selección local determinista: normalización de acentos/minúsculas para coincidencia, cantidad de palabras clave coincidentes, prioridad descendente y desempate id. Máximo cuatro entradas y 3.200 caracteres de conocimiento; no se cortan reglas. No llamada previa para clasificar. Solo topic/text se envían como conocimiento; metadatos de fuentes/versiones permanecen locales. Sin coincidencia autorizada: aviso local «Esa información no está disponible en el contexto de asistencia», sin inventar ni consultar a OpenAI.

Contraste funcional: PENDIENTE no bloquea disponibilidad; estado DISPONIBLE no prueba libertad temporal; APROBADA activa cumple inicio<=ahora<fin; vencido es ENTREGADO y ahora>fin_previsto. QR no autentica, IoT no identifica personas ni convierte silencio en libre, ALERTAR no libera, fallo Blockchain no revierte negocio, notificaciones no se marcan por listar y actividad agregada no es CU30. Para el estado real propio o cantidades institucionales se orienta al módulo autorizado sin leer sus filas.

## Veinte destinos congelados

| route_key | Ruta nombrada Laravel | Permiso adicional a chatbot.consultar |
| --- | --- | --- |
| inicio | inicio | Ninguno |
| espacios | espacios.index | espacios.ver |
| reservas.disponibilidad | disponibilidad.index | espacios.ver |
| reservas.propias | reservas.index, alcance=propias | reservas.ver_propias |
| reservas.administrativas | reservas.index, alcance=administrativas | reservas.ver_administrativas |
| equipamientos | equipamientos.index | equipamientos.ver |
| equipamientos.disponibilidad | equipamientos.availability | equipamientos.ver |
| prestamos.propios | prestamos.index, alcance=propios | prestamos.ver_propios |
| prestamos.administrativos | prestamos.index, alcance=administrativos | prestamos.ver_administrativos |
| incidencias | incidencias.index | incidencias.ver |
| qr.gestion | qr.index | qr.gestionar y espacios.ver o equipamientos.ver |
| iot.monitoreo | iot.monitoring | iot.monitoreo.ver |
| iot.sensores | iot.sensors.index | iot.sensores.ver |
| liberacion.propias | release.evaluations.index, alcance=propias | liberacion.evaluaciones.ver_propias |
| liberacion.administrativas | release.evaluations.index, alcance=administrativas | liberacion.evaluaciones.ver_administrativas |
| liberacion.politicas | release.policies.index | liberacion.politicas.ver |
| trazabilidad | trazabilidad.index | trazabilidad.evidencias.ver |
| notificaciones | notifications.index | notificaciones.ver_propias |
| dashboard | dashboard | dashboard.ver |
| reportes | reports.index | reportes.ver |

Además, la clave debe pertenecer a los snippets seleccionados. Solo GET con labels fijos y parámetros de alcance del registro; sin IDs del modelo ni query arbitraria. No prefetch, apertura automática, descarga, lectura de aviso o mutación al recomendar. El destino conserva sus propios controles al abrir. Clave desconocida invalida toda la salida, según T-CB17/CH8; es el caso «no procede conservar texto seguro» de la sección 35 de la instrucción, no ampliación de allowlist. Clave conocida no autorizada pierde enlace y texto potencialmente protegido.

## Privacidad y límites de la defensa

Se transmite la **pregunta libre actual**, instrucciones procedimentales, capacidades mínimas, snippets y claves autorizadas. No se anexan nombre, apellido, correo, CI, perfil, sesión, identificador del actor, reservas, préstamos, notificaciones, recursos, lecturas, evidencias, reportes ni documentos completos. Si el usuario escribe información personal en la pregunta, sigue siendo texto aportado por él: no se promete anonimización universal. La UI advierte no introducir contraseñas/claves/códigos/información sensible y explica el proveedor externo.

OpenAI documenta que por defecto los datos API no se utilizan para entrenar salvo adhesión voluntaria. `store=false` evita almacenamiento recuperable de Responses, **no equivale a Zero Data Retention**: controles estándar de abuso pueden conservar contenido hasta 30 días, salvo excepciones de la política; ZDR requiere elegibilidad/configuración específica. No se afirma que esta instalación tenga ZDR.

Instrucciones y pregunta se separan jerárquicamente. Reglas locales bloquean ataques reconocidos, acciones imperativas, secretos/datos ajenos y tareas generales; RESTRICTED/OUT_OF_SCOPE usan texto local. Salida rechaza HTML, código activo, enlaces/Markdown y controles; se sustituye una falsa confirmación reconocida o exposición de secretos/instrucciones. Vue interpola texto escapado, nunca v-html/Markdown. **Heurísticas y prompt no prueban corrección semántica universal ni ausencia absoluta de alucinaciones**; las garantías ejecutables son no tools, no acceso a filas, no operaciones, whitelist, autorización y ausencia de secretos en contexto. La nota de confiabilidad permanece visible también en móvil.

No guardar preguntas/respuestas en tablas, sesión Laravel, archivos, cache, cola, logs, actividad, notificaciones o Blockchain. Solo cache de admisión numérica por actor. Logs permitidos implementados: provider/model configurado, HTTP status, intentos, usage numérico saneado, status y latencia; errores mediante códigos fijos. Nunca request/response body, Authorization, pregunta, respuesta ni instrucciones. Boundary desactiva debug en estas rutas; exception handler evita log de excepción cruda; FormRequest no hace withInput. Despliegues con APM/proxy externos deben excluir estos cuerpos y cabeceras: no se incorpora ni acredita un servicio de observabilidad externo.

## Presupuestos, errores y continuidad

| Control | Convención implementada |
| --- | --- |
| Entrada | 2.000 puntos de código Unicode; UTF-8 válido; normaliza CRLF→LF y espacios exteriores; conserva saltos/tab internos |
| Controles | Rechaza NUL, controles C0 inválidos y DEL antes del trim; solo texto, sin archivos/arrays/objetos/campos o query adicionales |
| Cuerpo de entrada | max_input_chars×12+1.024 bytes: permite emoji representado con dos escapes JSON UTF-16 sin relajar límite semántico |
| Salida | 500 max_output_tokens configurable; answer<=2.000 caracteres; body proveedor<=128 KiB mediante headers/progress y validación posterior |
| Cuota | Diez intentos admitidos por 60 s desde primer intento, actor compartido entre sesiones; undécimo 429 y Retry-After; fallos/invalidaciones autorizados también consumen |
| Concurrencia | Lock cache de dos segundos solo alrededor de comprobación+incremento; no espera activa ni lock durante red; contención falla cerrada |
| Plazo | Deadline monotónico total de 15 s desde Boundary; se reserva un segundo para salida, primer HTTP<=7 s, conexión<=3 s, segundo ajustado al restante |
| Retry | Máximo uno: 429 temporal, timeout o 5xx; backoff 250–500 ms, Retry-After solo si cabe. Cuota insuficiente, 400/401/403/404, schema/refusal/incomplete y DNS no reintentan |
| Falla | Disabled/clave ausente/config inválida o proveedor caído: indisponibilidad genérica; no cambio de modelo/proveedor ni bloqueo del núcleo |
| Navegador | Abort/descartar al limpiar, salir, navegar, desmontar y pagehide/pageshow; límite adicional de transporte cliente 20 s; este resguardo de conexión no amplía el presupuesto backend de 15 s |

El presupuesto de aplicación cuenta trabajo previo y retry; no constituye SLA de red, cola del servidor o MySQL. Cancelar en navegador descarta la respuesta local; no revoca una solicitud que el proveedor ya recibió. El servidor comprueba desconexión cuando PHP la detecta. No streaming, SSE, WebSocket ni polling. No se exige Redis nuevo; reutiliza cache existente con locks y contador compartidos. En varios nodos, el almacén configurado debe ser común a esos nodos; una cache array por request no satisface admisión productiva.

## Interfaz y memoria

Página Asistente: encabezado, alcance, ejemplo, burbujas, input con label/contador, enviar deshabilitado durante petición, estado aria-live, error role=alert, Limpiar y enlace resuelto. Enter envía, Shift+Enter añade línea y composición IME no envía prematuramente. Foco regresa al input; texto largo envuelve. Privacidad y confiabilidad junto al compositor.

Historial solo ref Vue, máximo veinte intercambios; no useForm/remember, localStorage, sessionStorage o IndexedDB. Limpiar aborta y vacía; un contador de generación descarta respuestas tardías. Reload, navegación, logout y bfcache vacían memoria; volver no reenvía nada. El servidor recibe exclusivamente pregunta actual.

## Verificación y trazabilidad T-CB

Pruebas nuevas en [ChatbotFeatureTest](../tests/Feature/ChatbotFeatureTest.php), [ChatbotProviderTest](../tests/Feature/ChatbotProviderTest.php) y reloj [ChatbotTestClock](../tests/ChatbotTestClock.php). Base de datos exclusiva espacio_u_testing con TestingDatabaseGuard, MySQL real. TestCase bloquea HTTP no simulado en toda la suite; phpunit fuerza chatbot deshabilitado y key vacía. Fixtures existentes de usuario/reserva/préstamo/incidencia; credencial inertemente sintética solo para fake, nunca key real. Siete archivos de pruebas anteriores actualizan únicamente expectativas del catálogo: 50→51, conteos por rol +1 y la lista explícita del Docente con chatbot.consultar; se conservan todos los casos.

| Criterios | Evidencia equivalente |
| --- | --- |
| T-CB01–05 | four_roles_get_permission_page_and_scoped_context; permission_revoked_has_no_role_bypass; unauthenticated_inactive_and_revoked_session; csrf_is_required; unauthorized_snippets_absent_and_mixed_roles |
| T-CB06–10 | invalid_inputs_do_not_flash_or_reach_provider; four_roles; operational_rows_are_neither_read_nor_sent_and_requests_have_no_history; contrato HTTP + aviso UI |
| T-CB11–14 | knowledge_and_twenty_routes_match_catalog_and_permissions; all_registered_destinations_are_read_only_get_routes_and_auth_topic_handles_accents; unsupported_topic_has_safe_insufficient_context; revisión de textos contra informes funcionales |
| T-CB15–19 | route_permissions_current_and_allowed_link_does_not_mutate; known_but_unauthorized_or_irrelevant_route; unknown_route_invalidates_whole_output; mid_request_revocation |
| T-CB20–24 | invalid_json_and_schema_fail_closed_without_retry (tabla adversarial); incomplete_refusal_empty_tool_and_oversized_output; structured_strings; render Vue escapado |
| T-CB25–30 | injection_secrets_actions_and_foreign_records_are_restricted_without_effects (16 ataques); adversarial_delimiters_and_encodings_remain_only_user_content; false_confirmations_and_secrets_are_replaced; operational_rows; out_of_scope local |
| T-CB31–33 | unicode_boundaries 1.999/2.000; badInputs 2.001/vacío/controles/tipos; normalization; all_client_context_fields_files_query_and_invalid_bytes_are_rejected; límites de salida y callbacks de transporte |
| T-CB34–35 | rate_limit_includes_invalid_attempts_is_shared_and_expires; limits_are_shared_across_sessions_but_independent_between_users; contended_lock_fails_closed |
| T-CB36–39 | disabled_configuration_never_sends_http; disabled_or_provider_failure; transient_failure_retries_once; quota_and_long_retry_after; timeout; permanent_errors; two_failures_stop |
| T-CB40–42 | deadline_counts_time_already_spent_and_discards_late_success; authentication_header_tls_redirect_and_body_limits; contrato HTTP; QA navegación/limpiar y escaneo público |
| T-CB43–46 | no SELECT operativo/no historial/no flash; ataques con assert de registros y conteos inalterados; question_and_answer_are_absent_from_request_error_logs; logs_contain_only_technical_metadata; QA storage |
| T-CB47–48 | QA de página, teclado, estados, consola y cuatro tamaños; prueba de fallo aislado conservando módulos principales |
| T-CB49 | TestCase preventStrayRequests, FakeChatbotProvider/Http::fake, baseline conservada y suite MySQL completa |
| T-CB50 | Fuentes oficiales revisadas; smoke real pendiente de credencial/cuota, conforme a sección 126, sin inferencia fingida |

Las comprobaciones de UI/inspección complementan pruebas de servidor; no se presenta un fake como evaluación adversarial de un modelo real. QA y mediciones locales descritas abajo; suite final completa aprobada: 877 pruebas y 10.217 assertions en 883,57 s.

## Operación y smoke real

Desde raíz oficial y runtimes scripts/dev-environment.ps1: ejecutar `php artisan db:seed --class=SecuritySeeder --force` para añadir el permiso sin restaurar revocados y `npm run build`. No migrate requerido. Configuración central [config/chatbot.php](../config/chatbot.php); servicios no leen env directamente.

Plantilla pública segura en [.env.example](../.env.example): CHATBOT_ENABLED=false, CHATBOT_PROVIDER=openai, CHATBOT_MODEL=gpt-6-luna, CHATBOT_MAX_INPUT_CHARS=2000, CHATBOT_MAX_OUTPUT_TOKENS=500, CHATBOT_TIMEOUT_SECONDS=15, CHATBOT_RATE_LIMIT_PER_MINUTE=10, OPENAI_API_KEY vacía. No sustituir ni copiar el .env local completo. Para habilitar, el operador introduce la key exclusivamente en .env LOCAL, activa CHATBOT_ENABLED y recarga configuración (`php artisan config:clear` en desarrollo; caché controlada en despliegue). Nunca enviarla por chat ni usar VITE_ para ella.

**Smoke real OpenAI pendiente por ausencia de credencial/cuota.** Se verificó solamente la ausencia de key y chatbot deshabilitado; no se imprimieron valores ni se solicitó una key. El modelo configurado/verificado documentalmente es gpt-6-luna; no se afirma un modelo efectivamente invocado ni latencia externa medida. Implementación y validación offline no quedan bloqueadas por ello.

Cuando exista configuración local segura, ejecutar fuera de la suite una consulta sintética «¿Cómo solicito una reserva?» con actor autorizado, comprobar schema, enlace reautorizado, store=false y ausencia de operaciones; medir latencia sin registrar texto ni credenciales. Incluir rechazo de acción protegida/fuera de alcance. No usar usuarios/datos reales para evaluar. Mantener pruebas normales offline.

Composer/frontend audit sin vulnerabilidades según verificación de esta fase; runtime Blockchain también cero. **Blockchain desarrollo mantiene cinco vulnerabilidades altas Hardhat/Zod bajo la excepción temporal autorizada**, no cero global; [advisory, versiones y controles](BLOCKCHAIN_IMPLEMENTATION.md). No se modificaron módulos ni lockfiles de dependencias. No retirar la excepción sin una actualización y auditoría efectiva.

## Medición local con proveedor simulado

PHP 8.5.12, equipo local, 1.000 iteraciones por componente: selección de conocimiento mediana 0,1782 ms/p95 0,2295 ms; resolución y autorización de ruta 0,0066/0,0079 ms; FakeProvider + validador de salida 0,0023/0,0031 ms. Las mediciones de componentes excluyen HTTP/autenticación/BD; no son benchmark productivo, SLA ni latencia externa. Recorrido HTTP real local de veinte consultas sintéticas, incluyendo autenticación/sesión y FakeProvider: mediana 61,447 ms, p95 63,605 ms y máximo 70,041 ms. OpenAI no fue invocado.

## Revisión visual y manual

Navegador integrado, assets compilados y servidor QA exclusivamente loopback:8001, MySQL espacio_u_testing, cuatro actores sintéticos y FakeProvider inyectado desde harness privado ignorado en Git. CSRF se fuerza activo también en QA. No credenciales reales, llamadas OpenAI, fixtures de desarrollo ni endpoints QA en rutas públicas del proyecto. El servidor temporal se detuvo al terminar, antes de la suite final.

| Casos de la instrucción | Resultado observado |
| --- | --- |
| 1–4: cuatro roles | Asistente accesible; contexto/navegación administrativos o propios según permisos |
| 5–6: permiso/cuenta | Retirar capacidad devuelve 403; cuenta inactiva termina sesión y vuelve a login |
| 7–11: reservas/préstamos/QR/IoT/reportes | Texto procedimental; enlaces fijos autorizados; Disponibilidad abierta desde respuesta propia |
| 12–16: operación/datos ajenos/injection/secreto/fuera de alcance | Rechazo local seguro, sin afirmación de ejecución; clima limitado a ESPACIO-U |
| 17–18: destinos | Ruta permitida abre módulo; sugerencia trazabilidad a Estudiante pierde enlace y texto protegido |
| 19–21: disabled/timeout/cuota | Mensaje genérico de indisponibilidad con fixture; no texto del error externo |
| 22: límite | Primer intento admite y siguiente limita con cuota QA reducida a uno; convención real 10/60 s cubierta en PHPUnit |
| 23: 2.000 caracteres | 2.000 puntos de código con emoji aceptados, contador correcto y respuesta visible |
| 24–25: limpiar/refresh | Memoria vacía, foco al input; clear durante petición aborta y respuesta tardía no restaura mensajes; reload pierde historial |
| 26: sin browser storage | Inspección de fuente confirma ausencia de localStorage/sessionStorage/IndexedDB/remember para chat; se complementa con clear/reload/logout reales. No se inspeccionó almacenamiento interno del navegador; el marcador de seguridad de logout D-07 no contiene conversación |
| 27: logs | Spy de éxito/error y comprobación de logs locales sin preguntas QA, texto de respuesta ni credencial sintética |
| 28–30: responsive | 1440×900, 1366×768, 768×1024 y 390×844; scrollWidth=clientWidth, sin desbordamiento horizontal; compositor y advertencias accesibles por scroll |
| 31: teclado | Enter envía, Shift+Enter conserva salto, foco de textarea comprobado, loading deshabilita enviar |
| 32: consola | Consulta de warnings/errors vacía al cierre; sin errores JS de aplicación observados |

Capturas sintéticas privadas en .local/qa/phase12/ (desktop-1440, desktop-1366, tablet y mobile); no se versionan. La automatización del navegador tuvo demoras/reinicio de sesión de herramientas, recuperados verificando la página actual; no se atribuyen al proveedor. Evaluar un modelo real ante ataques sigue formando parte del smoke separado, sin sustituir las barreras del servidor.

## Habilitación local realizada

Seeder incremental aplicado en la base de desarrollo espacio_u exclusivamente como setup, no como prueba: 50→51 permisos, cuatro concesiones de chatbot.consultar, usuarios y migraciones sin cambios. Las pruebas/fixtures permanecen exclusivamente en espacio_u_testing. Servidor de desarrollo oficial en 127.0.0.1:8000 y /up con HTTP 200. CHATBOT_ENABLED continúa false y no hay OPENAI_API_KEY configurada; .env local no fue modificado ni impreso. MySQL aislado del proyecto permanece separado de los servicios existentes.

## Resultados finales de validación

| Control | Resultado |
| --- | --- |
| php artisan test --compact | 877 passed; 10.217 assertions; 883,57 s; ninguna omitida ni fallida |
| Regresión | 747 casos anteriores conservados; 130 nuevos; 63 archivos/436 métodos anteriores conservados |
| Pint --test | Aprobado en toda la aplicación |
| npm run build | Aprobado; Vite 617 ms en la compilación final de UI |
| composer audit | 0 advisories; 0 paquetes abandonados |
| npm audit frontend / --omit=dev | 0 / 0 vulnerabilidades |
| npm audit blockchain / --omit=dev | 5 altas dev Hardhat/Zod bajo excepción autorizada / 0 runtime |
| Dependencias/esquema | Sin paquetes, cambios de lockfiles ni migraciones nuevas |
| Contrato/fuentes | 217 textos normativos, 30 CU, 17 decisiones D y huellas Word/PDF conservados; CH1–12, 20 rutas y 50 criterios T-CB |
| Secret scan | Worktree público, índice, diff e historial sin hallazgos; .env y material privado excluidos |
| Documentación | Enlaces locales y anchors comprobados; informe y trazabilidad actualizados |

La primera ejecución completa detectó tres expectativas anteriores del catálogo de permisos; se actualizaron al permiso nuevo manteniendo los tests. Los casos adicionales de entrada/encoding se verificaron y la ejecución final anterior corresponde al árbol corregido. No se atribuyen los resultados con fallos al cierre aprobado.

Implementación y pruebas versionadas en commit `9b2aafbdbedadc2bd609d249e634f908511ef6ca`. Rama de entrega: feature/phase-12-chatbot. Integración prevista y autorizada: main por fast-forward, sin force push; hashes finales y verificación remota se entregan en el informe del chat. CU30 NO IMPLEMENTADO, móvil diferida, Sepolia pendiente independiente y ninguna fase posterior iniciada. Siguiente paso: configurar credencial/cuota local segura y ejecutar el smoke sintético separado, sin iniciar otro alcance.
