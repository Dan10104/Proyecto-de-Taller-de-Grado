# Política de seguridad

**Estado vigente — Fase 11 implementada y verificada (2026-10-26).** RF22/CU27 y RF23/CU28: 747 tests / 9111 assertions; QA y auditorías productivas aprobadas. [Implementación, scopes, formatos y evidencia](DASHBOARD_REPORTING_IMPLEMENTATION.md). Sin nuevas tablas ni índices; CU30 NO IMPLEMENTADO y Fase 12 NO iniciada. Los cierres anteriores son históricos.

**Estado histórico — Fase 10.5 documental (2026-10-26).** RF22/CU27 y RF23/CU28: [contrato de dashboard y reportes cerrado](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11), **0 bloqueantes documentales**. Fase 11 NO iniciada; CU30 NO IMPLEMENTADO. Fase 10 funcional se conserva, sin cambios de código, datos, permisos o dependencias. Los cierres anteriores mantienen su contexto histórico.

**Estado histórico funcional — Fase 10 (2026-10-26).** RF21/CU26 implementados y verificados: 17 reglas, avisos propios, primera lectura y recuperación de 24 h. [Informe y evidencia](NOTIFICATIONS_IMPLEMENTATION.md). CU30 general y Fase 11 no iniciados. Sepolia real sigue pendiente de testETH. Los apartados de cierres anteriores conservan su contexto histórico.

**Estado histórico al cierre de Fase 9 implementada (2026-10-25/26).** RF19/CU24 y RF20/CU25, cuatro actividades críticas, dos tablas y tres permisos implementados conforme a D-12. [Implementación, pruebas offline, excepción temporal dev de Zod y Sepolia pendiente de testETH](BLOCKCHAIN_IMPLEMENTATION.md). CU30 general y Fase 10 permanecen pendientes. Las secciones de cierres anteriores conservan su contexto histórico.

**Estado al cierre funcional de Fase 8 (2026-10-24).** Se añaden exactamente cuatro permisos liberacion.*, sin bypass ni usuario Sistema. Autorización humana coordina/revalida RBAC; CU23 interno no toma ese lock. Scope propio obligatorio en servidor y DTO sin actores ni identificadores IoT. QR/HMAC/tokens/credenciales no aparecen en motivos, logs, respuestas ni documentación pública. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

**Cierre histórico de Fase 7.5:** [D-08 y permisos futuros](DECISIONS.md#scheduler-consultas-y-permisos-previstos). Ninguna reserva se libera con evidencia requerida insuficiente/desactualizada/no interpretable o con contradicción. Positivo fiable opcional también veta. Los cuatro permisos liberacion.* están previstos, todavía ausentes de DB; ejecución interna independiente de RBAC humano, sin rol Sistema ni endpoint de liberación manual.

**Estado histórico: Fase 7 implementada conforme a D-10.** CU21/CU22 y evidencia informativa en CU06; dos tablas IoT y tres permisos nuevos. [Implementación, seguridad, pruebas y límites](IOT_IMPLEMENTATION.md). CU23 y Fase 8 no iniciados.

La Fase 6 conserva su aceptación y su límite de corroboración física descritos en [QR_IMPLEMENTATION.md](QR_IMPLEMENTATION.md). Los cierres documentales de Fase 6.5 y anteriores se conservan como evidencia histórica; sus criterios futuros se verifican ahora en Fase 7.

Repositorio público. Esta política aplica a documentación, código, pruebas, historial Git, imágenes, logs, issues y artefactos. Fuentes: instrucciones de Fases 0, 0.5, 1 y 1.5; RNF-10–17, IS-14, IC-02/04/05/08/11, RD-19/20/23/43/44 y DB-21–24.

## Secretos y datos

Nunca publicar `.env`, contraseñas, API keys, tokens, claves privadas, certificados privados, credenciales de base de datos/Google Cloud/Blockchain/chatbot/GitHub ni JSON de service accounts. No usar valores reales como datos de prueba. Seeders/factories futuros utilizarán identidades y correos ficticios; los originales del informe, sus nombres y las capturas con datos personales no se incorporan al repositorio público.

`.env.example` es la única excepción a `.env.*`; contiene valores vacíos o inocuos. `APP_KEY`, usuarios/contraseñas y claves externas permanecen vacíos. Las variables de integración de la plantilla son propuestas de configuración, aún no consumidas por software. No crear ni publicar una clave de aplicación real en Fases 0/0.5. Las variables con secretos no usarán prefijos destinados a exponerlas al frontend, como `VITE_`.

Las llamadas a proveedores salen del backend. Redactar o excluir secretos de logs, excepciones y trazas. Un QR o una sesión se almacenan como hash según el modelo; el hash no autoriza mostrar indiscriminadamente el token. No incluir información sensible en mensajes de commit, comentarios, pruebas ni ejemplos. El control de versiones no reemplaza la gestión privada de secretos.

## Exclusiones Git

`.gitignore` contiene todas las reglas mínimas exigidas: `.env`, `.env.*`, excepción de `.env.example`, dependencias, logs/cache, claves/certificados, archivos de credenciales, `auth.json`, configuración local de IDE y archivos del sistema. También excluye material de trabajo, compilados, cobertura y datos locales generados. No usar excepciones posteriores que reintroduzcan secretos. No asumir que ignorar un archivo lo elimina del índice si ya estaba seguido.

No colocar reportes privados o respaldos de BD dentro de documentación versionada. Los respaldos operativos se almacenarán en el destino privado aprobado para despliegue. No generar ZIP ni copias manuales del repositorio.

## Revisión obligatoria antes de cada commit y push

1. Desde la raíz oficial, comprobar `git rev-parse --show-toplevel`, `git branch --show-current`, `git remote -v` y `git status --short --branch`.
2. Revisar `git diff` y seleccionar explícitamente los archivos que pertenecen a la fase. No usar `git add .` para incluir material no revisado.
3. Revisar `git diff --cached --name-status`, `git diff --cached --check` y el contenido completo de `git diff --cached`. Inspeccionar también archivos binarios si existieran; no se esperan en esta fase.
4. Revisar los nombres obtenidos de `git ls-files`: no debe haber `.env` o variantes privadas, JSON de credenciales, claves/certificados, dumps, archivos personales ni dependencias vendorizadas. `.env.example` se revisa separadamente.
5. Escanear el contenido **staged** para asignaciones sospechosas de password, secret, token, API key, credenciales y cabeceras de claves privadas, además de formatos conocidos de tokens. Las palabras de una política o variables vacías no son por sí mismas secretos: inspeccionar cada coincidencia sin imprimir valores reales.
6. Comprobar que `git check-ignore` rechaza las rutas privadas esperadas y permite `.env.example`. Revisar los valores de esta última: secretos vacíos; ejemplos no sensibles. Un escáner adicional fiable ayuda, pero no reemplaza revisar archivos y diferencias.
7. Si aparece un secreto: detener commit/push, retirar el dato del contenido y del índice, investigar alcance y volver a validar. Si ya se expuso, revocar/rotar con el titular y tratar el historial afectado con un procedimiento acordado. No publicar el valor para explicarlo.
8. Crear commit descriptivo, volver a comprobar estado y destino, y ejecutar el push autorizado. Si GitHub bloquea un secreto, no usar bypass; corregir y volver a verificar. Un falso positivo requiere evidencia explícita.
9. Verificar que la rama remota coincide con el commit local y no quedan cambios inesperados.

En fases futuras, las comprobaciones automatizadas podrán incorporarse en la ubicación convencional de CI. No se afirma que exista actualmente un pipeline o un escáner dedicado instalado.

## Controles de aplicación futuros

| Superficie | Control y comprobación |
| --- | --- |
| Identidad | Hash de contraseñas; cuenta activa; vigencia y revocación de sesión; fallos sin información sensible; Laravel Session web y control por hash definidos en D-07/Fase 1.5; móvil/API diferido |
| Autorización | Política por acción/objeto, propietario y ámbito; probar acceso directo a rutas/API y descargas, no solo visibilidad de botones |
| Web | Sesión segura y protección CSRF donde corresponda; validación del lado servidor; salida escapada; probar entradas no confiables |
| Persistencia | Consultas parametrizadas/ORM, FK/UNIQUE/CHECK y transacciones; no permitir borrado de historia necesaria |
| QR | Token vigente y objetivo/contexto correctos; no usar conocimiento del código como autorización; no registrar token sin hash |
| IoT | Identificación/autenticación del dispositivo, validación del esquema y estado habilitado; HMAC RAW, repetidos y frescura de D-10 implementados y verificados en Fase 7; [evidencia](IOT_IMPLEMENTATION.md) |
| Blockchain | Minimización y privacidad antes del envío; referencia técnica; hash/versión consistente; pendiente durable ante fallo |
| Chatbot | Contexto limitado por permisos, proveedor solo desde backend, ausencia de herramientas que ejecuten operaciones protegidas |
| Archivos/reportes | Generación y descarga autorizadas, ubicación privada/controlada, sin acceso por adivinar un identificador |
| Producción | HTTPS, secretos por entorno, debug desactivado y revisión de errores/logs; configuración de red/acceso en Fase 14 |

CSRF, parametrización y salida escapada son controles técnicos propuestos para satisfacer los requisitos de seguridad; no se presentan como nuevas funcionalidades del informe. Límites de autenticación/reintentos y mecanismo de tokens deben documentarse antes de implementarse.

## Respaldo y recuperación

R-DISP-04 exige al menos un respaldo diario y conservar como mínimo los siete más recientes. R-DISP-05 establece un objetivo de restauración de hasta cuatro horas. Deben probarse con información sintética y luego con el procedimiento operativo autorizado. Es una obligación sobre la información operativa, compatible con la prohibición de duplicar manualmente código fuente.

## Autorización y privacidad congeladas en Fase 0.5

Aplicar D-03: iniciador y flujo del CU, permiso efectivo, RBAC y guardas por objeto. Participantes de RF no reciben facultades implícitas; una acción protegida sin ámbito acreditado se deniega. Fase 1.5 cierra RBAC por capacidad y sesión web para Fase 2; solo el ámbito fino por recurso y la autenticación móvil/API siguen diferidos. No convertir rol Encargado en acceso general a todos los recursos ni inventar tablas de asignación.

QR no autentica: verificar sesión, permisos, código, recurso, operación, estado y tiempo aplicable en servidor. Sensor registrado/habilitado/asociado tampoco identifica personas. Datos insuficientes no autorizan liberación automática. Todos los valores persistidos y exclusividades SQL conservan el contrato de DOMAIN_MODEL/DATA_MODEL.

Blockchain solo recibe evidencia mínima tras confirmación principal; excluir nombres, correos, contraseñas, datos personales completos y datos operativos completos. Especificación canónica cerrada en D-12/Fase 8.5: SHA-256/JCS, orden/fechas estables, UTF-8, selección permitida y version_canonica espacio-u-jcs-v1. Fallo externo conserva operación y estado local PENDIENTE cuando corresponda; no afirmar que la cadena sea fuente principal.

El cierre histórico de Fase 0.5 solo actualizó documentación y un comentario de la plantilla; no creó secretos ni infraestructura. La Fase 1 posterior añadió la aplicación y su configuración privada conforme a las medidas siguientes.

## Medidas aplicadas en Fase 1

APP_KEY se genera con Artisan y las credenciales de MySQL con un generador criptográfico. Los valores legibles residen solamente en `.env`, ignorado y con ACL local restringida. `.env.example` conserva vacíos APP_KEY, usuario de BD, contraseñas y claves futuras. No se muestran secretos en salida de comandos, chat, SQL guardado ni argumentos de procesos.

MySQL del proyecto está limitado a loopback 3308, con datos privados en `.local/`, sin servicio del sistema ni modificación de las instancias existentes. La cuenta de aplicación solo tiene permisos sobre `espacio_u.*`; la credencial administrativa distinta se usa únicamente para administrar la instancia aislada. Los scripts verifican su directorio antes de apagarla. Logs generales, lentos y binarios están desactivados en esta instancia de desarrollo; no se configura aún la política de auditoría/recuperación de producción.

`.tools/`, `.local/`, dependencias, sesiones, logs, compilados y material temporal están excluidos de Git. Las excepciones de `.gitignore` permiten exclusivamente marcadores vacíos del skeleton. APP_DEBUG está desactivado, Inertia no comparte configuración privada y DevTools/SSR no están activados. La revisión de secretos contrasta los valores locales con el índice sin imprimirlos y comprueba formatos de claves/tokens. Las auditorías de dependencias no reemplazan los futuros controles funcionales ni una revisión de producción.

## Contrato de seguridad de Fase 1.5

Ningún rol, incluido Administrador, evita los permisos explícitos y las guardas centrales. Asignaciones activas por fecha_retiro NULL y relaciones M:N oficiales; evaluar cambios en cada solicitud sin privilegios retenidos desde el login. Denegar si falta permiso, actor elegible, propiedad o ámbito aplicable. No exponer recursos bajo responsabilidad sin resolver su alcance fino en la fase correspondiente.

Laravel Session será el mecanismo web, con Hash/Argon2id para password_hash, cuenta ACTIVO, respuesta genérica ante credenciales inválidas, limitación configurable de intentos, ID regenerado y CSRF. sesiones_acceso guarda únicamente SHA-256 del ID final, titular y fechas; el hash no autentica por sí mismo. Exigir simultáneamente sesión técnica y registro vigente no revocado. Renovar por inactividad desde session.lifetime después de validar; no revivir expirados ni permitir que una petición concurrente deshaga logout. No guardar secretos o hashes sensibles en logs/props ni credenciales de acceso en localStorage.

Cookie de autenticación HttpOnly/SameSite=Lax y Secure bajo HTTPS; excepción Secure=false solo para HTTP local. Conservar el flujo CSRF estándar, incluida la cookie XSRF-TOKEN de propósito distinto. Logout requiere revocación, invalidación y CSRF nuevo; un fallo de red no demuestra revocación y la UI no puede borrar HttpOnly. El comportamiento compensatorio está definido en D-07.

Las pruebas con escritura usarán MySQL separado y usuario exclusivo con permisos solo en espacio_u_testing; secretos en .env.testing local ignorado, nunca en phpunit.xml, plantillas, docs, Git ni consola. Comprobar base efectiva y entorno antes de migrar/limpiar; abortar ante desvío o configuración cacheada de otro entorno. No se creó esa base ni se generaron credenciales en Fase 1.5. El núcleo autorizado tampoco incluye todavía bitácora general; sesiones_acceso no sustituye registros_actividad.

## Controles materializados en Fase 2

Autenticación Session web/Argon2id, CSRF, RateLimiter, hash de sesión final, vigencia/revocación, consultas RBAC en servidor sin bypass, validación de solicitudes y bloqueo del último administrador implementados. Testing tiene base/cuenta/APP_KEY propias y guarda estricta antes de comandos destructivos, incluidos --force. Secretos de desarrollo y bootstrap solo en .env; testing solo en .env.testing, ambos ignorados y privados. No se sustituyen CU30 ni políticas futuras por estos controles. [Evidencia y límites](SECURITY_IMPLEMENTATION.md).

## Extensión controlada de Fase 3

La petición §35 autoriza para CU05 RBAC por módulo/acción; el alcance fino por recurso sigue diferido, precisando la puerta histórica de Fase 1.5. AuthorizationService exige elegibilidad y permiso de un mismo rol vigente. Se incorporan cuatro permisos de espacios con matriz 4/4/1/1; los once administrativos anteriores siguen exclusivos de Administrador. El catálogo total es 15 y no hay bypass. El seeder incremental no restituye revocaciones al repetirse.

Gates/Policy y reautorización dentro de transacciones protegen escrituras. No se aceptan responsable de bloqueo, PK compartida ni política desde cliente. Lectores no reciben motivos/responsables administrativos de bloqueos. Se mantienen CSRF, sesión, último administrador y guarda MySQL testing; credenciales de revisión solo temporales en .env privado. [Evidencia y límites](SPACES_IMPLEMENTATION.md).

## Aplicación a reservas — Fase 4

Seis permisos específicos, elegibilidad de rol y scope propio/administrativo aplicados en servidor. Código, solicitante, estado, actores y fechas de resolución/cancelación no son editables desde request. Reautorización después de coordinación RBAC; cancelación exige hora confiable después de esperar locks. DTO sin correos, hashes o sesiones. [Matriz y verificación](RESERVATIONS_IMPLEMENTATION.md#permisos-y-alcance). El ámbito fino de D-04 continúa diferido; no hay bypass administrativo.

## Aplicación a Fase 5

Doce permisos exactos del contrato Fase 4.5, sin bypass de Administrador ni alcance fino inventado. Propiedad aplicada antes de filtros/opciones; actores, código y fechas de decisión exclusivamente del servidor; CSRF y reautorización tras espera; DTO sin correo/hash/sesión. CON_INCIDENCIA exige gestión de incidencias además de devolución; habilitación explícita exige cambiar_estado del subtipo. [Matriz y controles verificados](EQUIPMENT_LOANS_IMPLEMENTATION.md#permisos-y-alcance). Las cuentas de QA se deshabilitaron y sus sesiones se revocaron; credenciales temporales retiradas de .env. No hay secretos en .env.example ni archivos privados publicados.

## Contrato de seguridad QR — Fase 5.5

**QR no autentica ni autoriza por sí solo.** [D-09 resuelta para Fase 6](DECISIONS.md#readiness-for-phase-6): token aleatorio 256 bits/base64url canónico sin padding, payload opaco sin IDs/PII; persistencia exclusiva de SHA-256 hexadecimal del string exacto en token_hash/hash_leido. No normalización permisiva ni hash almacenado enviado al navegador. El formato y los rechazos registrables están definidos en DECISIONS; contexto inexistente/ajeno o autorización fallida no se fuerzan a una fila con FK falsa.

Sesión vigente, ACTIVO, CSRF, permiso de un rol elegible, contexto/propiedad, código/vigencia, recurso/operación y estado/reloj servidor son obligatorios. Solo A/E gestionan QR y validan equipamiento con sus permisos correspondientes; CU11 exige autorización propia/administrativa. Token solo en memoria de generación/lectura y cuerpo POST al backend propio; nunca URL, logs, analytics, consola, flash de errores, sesión, props persistibles/historial Inertia, caché o almacenamiento del navegador. Respuestas con representación QR privadas no-store; descartar buffers y detener cámara al abandonar. La impresión física prevista no autoriza archivar imágenes digitales con token.

No frames/fotos guardados ni enviados al servidor/terceros; decodificación local, HTTPS en producción, librería compatible/mantenida/mínima sin servicio remoto. Sin entrada manual. Pruebas futuras con tokens sintéticos generados en testing; documentación/capturas con QR ficticio o revocado. Nada real utilizable en Git. Logs técnicos solo ID de validación/resultado/tipo. No instalar ni configurar librerías/cámara en esta fase. La misma petición y transacción liga validación nueva e hito; ningún ID de validación vieja, token revocado o cambio concurrente puede autorizar por sí solo una operación.

## Aplicación QR de Fase 6

Tres permisos implementados (36 totales), auth/ACTIVO/CSRF/sesión y contexto actual tras espera. Token exacto solo en memoria y POST; exclusión de trim/flash, no persistencia Inertia, errores QR sin debug/payload, no-store y cámara local. Ningún frame/imagen va al backend o terceros. [Controles, matriz y pruebas](QR_IMPLEMENTATION.md). Prohibido activar logs/APM de cuerpos QR. El arnés es fuente de tests separada, sus assets se eliminan con build normal; no permite bypass ni es una función del producto.

## Seguridad IoT prevista — Fase 6.5

[D-10 normativo](DECISIONS.md#contrato-iot--cierre-d-10-de-fase-65): HTTPS desplegado; HTTP solo loopback. HMAC-SHA-256 de cadena canónica y hash RAW, comparado constant-time antes de parsear JSON. Timestamp ±300 s configurable más deduplicación exacta; no prometer que la ventana impide replay interior. Desconocido/firma inválida → 401 genérico, deshabilitado autenticado → 403. Sin cookie, rol o cuenta humana del dispositivo.

Maestro CSPRNG de 256 bits solo servidor, valor futuro vacío en .env.example; claves derivadas con dominio sensor: e identificador inmutable. No se generan secretos ni se cambia plantilla en 6.5. Provisionamiento administrativo explícito con sesión/CSRF/permiso A/E; respuesta privada no-store solo solicitada, memoria temporal sin historial/flash/props persistibles. Claves nunca en BD, frontend ordinario, URL, logs, errores, APM, capturas, documentación o Git. Maestro comprometido exige reprovisionar todos los dispositivos; repetir provisión sin cambiar identificador/maestro no rota clave.

Tres permisos previstos: iot.sensores.ver, iot.sensores.gestionar, iot.monitoreo.ver, A/E elegibles sin bypass. D/S sin administración/historial; CU06 puede mostrar solo evidencia ambiental mínima con su permiso existente. Cuota independiente por sensor autenticado y cuerpo máximo configurable, sin permitir agotar cuota ajena con identificador falsificado. Desactivar/reasociar y recibir comparten lock de sensor; estado BD vigente prevalece.

Solo ambiente: ningún dato personal, imagen/audio, biometría, cámara, tracking Bluetooth/MAC personal/identificador móvil. Campos extra se rechazan sin registrar body. Logs limitados a IDs internos autorizados, resultado, HTTP status y razón segura; no firma completa ni cabeceras secretas. Histórico inmutable conservado, sin eliminación física de sensor ni purga del piloto. CU30 continúa diferido, no sustituido por logs.


## Actualización de Fase 7

Maestros separados en .env/.env.testing, claves derivadas solo en memoria, provisión explícita no-store con sesión/CSRF y permiso elegible. API autentica RAW antes de JSON, sin identidad humana, con anti-replay, cuota atómica y fallos cerrados. Logs y errores excluyen cuerpos/cabeceras/firma/credenciales. Proxy/TLS/NTP y privacidad de APM deben conservar el contrato al desplegar. [Implementación y verificación](IOT_IMPLEMENTATION.md).

## Seguridad de CU23 prevista — Fase 7.5

Fuente positiva fiable opcional veta liberación; incertidumbre requerida y contradicción conservan APROBADA. La consulta del veto es obligatoria: un error de BD no equivale a ausencia. El servicio interno usa hora confiable y revalida estado/configuración/evidencia tras locks; ningún payload, parámetro UI o resultado CU22 puede ordenar LIBERAR. NOWAIT ocupado aborta/reintenta, sin saltar sensores ni inventar unanimidad.

Gestión futura mantiene sesión/CSRF/permiso elegible y reautorización; evaluaciones propias se filtran por solicitante_id y proyectan solo resultado/contexto seguro. Motivo <=500 sin PII, token/hash QR, clave/firma IoT ni JSON de cuerpos. Sin edición/borrado histórico, rol Sistema ni botón/API manual. No cambiar secretos, configuración, roles ni permisos persistidos en 7.5. [Matriz y límites](DECISIONS.md#scheduler-consultas-y-permisos-previstos).

## Seguridad de trazabilidad congelada — Fase 8.5

[D-12](DECISIONS.md#contrato-blockchain--cierre-d-12-de-fase-85) fija SHA-256/JCS sobre datos mínimos históricos; la privacidad se comprueba antes de transmitir. Canónico solo en memoria durante generación/verificación, sin PII directa ni textos libres: hashes secundarios exactos para motivo/condición/observaciones. IDs técnicos permitidos localmente no se envían a Sepolia. No presentar hashes como cifrado o anonimización absoluta.

El único dato de negocio externo es digest bytes32; firma, gas, nonce, chain ID y direcciones de wallet/contrato son metadatos técnicos. No JSON canónico, identificadores internos, códigos o fechas de negocio. Pruebas inspeccionarán el argumento ABI y las solicitudes para impedir contenido adicional; no exigir que todo el mensaje JSON-RPC mida 32 bytes.

Wallet futura exclusiva de proyecto/testnet; ni wallet personal ni fondos reales ni clave reutilizada. BLOCKCHAIN_PRIVATE_KEY y BLOCKCHAIN_RPC_URL se tratan como secretos: solo .env local ignorado o gestor del despliegue, nunca BD/Git/logs/props/errores/fixtures. Plantilla futura con valores sensibles vacíos; esta fase no edita .env.example ni genera secretos. No registrar requests/tx firmadas/cuerpos RPC ni URLs con credenciales como diagnóstico; usar códigos de error seguros y evidencia técnica mínima.

Driver restringido a Sepolia/11155111, configuración + eth_chainId + firma con red fijada antes de enviar; bytecode/contrato/writer del despliegue reconocidos. Mainnet no se habilita cambiando una variable. Timeout acotado, HTTPS remoto y saneamiento de errores. Referencias estrictas eip155, sin URLs arbitrarias; explorador público derivado de red/contrato/tx validados. Cambiar proveedor no invalida historia; cambiar despliegue exige conservar referencias anteriores autorizadas.

Tres permisos exactos: trazabilidad.evidencias.ver, trazabilidad.evidencias.verificar y trazabilidad.reintentar. Inicialmente solo Administrador; Encargado/Docente/Estudiante no reciben acceso. Sesión y cuenta vigentes, asignación efectiva en rol elegible, CSRF en mutaciones y reautorización ante cambios; no bypass por nombre de rol. Verificar/reintentar exige además ver. Proceso automático interno separado de sesión humana, sin usuario Sistema inventado.

Actividad obligatoria en la transacción del negocio; fallo local revierte esa transacción. RPC después del commit sin locks de negocio; fallo externo no la revierte. Evidencia/historia inmutables: no reemplazar hash para ocultar alteración. Reintentos idempotentes, consulta antes del envío, exclusión por wallet y validación de pruebas externas. PENDIENTE no equivale a fracaso definitivo; REGISTRADA no retrocede por RPC caído. UI autorizada puede mostrar hash/referencia y metadatos, nunca canónico completo o secretos. CU30 sigue diferido; no se registra una nueva acción crítica por CU25.

Aceptación futura T-B01–T-B40: suite normal offline sin claves activas; integración live separada con fondos de prueba. Las huellas de Word/PDF y el escaneo de secretos/índice se verifican en este cierre documental; ello no acredita seguridad de un contrato aún no escrito.

## Controles aplicados a Blockchain — Fase 9

Solo Administrador con permisos efectivos trazabilidad.evidencias.ver, trazabilidad.evidencias.verificar y trazabilidad.reintentar. Acciones POST requieren ver, sesión y CSRF; revalidación después de RPC. Llave dedicada únicamente en .env ignorado, sin argv/stdin/logs/UI; environment de procesos aislado. Red rígida Sepolia/11155111, chain real, bytecode y owner comprobados; nunca Mainnet. Datos externos limitados a bytes32 y metadatos Ethereum. No procesamiento de datos arbitrarios externos por Hardhat/Zod.

Excepción temporal expresamente autorizada: cinco entradas altas del advisory Zod GHSA-mrhx-c7xc-m3w5 / CVE-2023-54404, solo toolchain de desarrollo blockchain/. Producción debe auditar en cero e instalar --omit=dev. No ampara nuevas vulnerabilidades críticas, RCE, robo de credenciales, supply-chain compromise ni dependencias productivas. [Versiones, controles, deuda y última revisión](BLOCKCHAIN_IMPLEMENTATION.md#excepción-temporal-de-desarrollo-autorizada).

## Seguridad de notificaciones — contrato de Fase 9.5

[Contrato N2/N6/N7/N8](DECISIONS.md#notification-contract--readiness-for-phase-10), pendiente de implementación autorizada. Único permiso previsto notificaciones.ver_propias para Administrador, Encargado de Laboratorio, Docente y Estudiante: listar/contar/ver/marcar solo propias. No hay bypass administrativo, lectura global, envío arbitrario ni permiso humano de generación. El permiso aún no existe en DB; siguen vigentes los 46 de Fase 9.

Aplicar autenticación, estado ACTIVO y permisos efectivos vigentes en servidor; filtrar por destinatario antes de consulta/contador/paginación. ID ajeno e inexistente comparten 404; falta del permiso de módulo, 403. POST/PATCH de lectura exige CSRF y propiedad, toma fecha del servidor y solo actualiza si leida_en IS NULL. GET/listado/prefetch/contador no mutan. Revocar entre GET y POST impide marcar; no aceptar fecha, destinatario, tipo o contenido suministrados por el cliente.

Resolver grupos administrativos por permisos efectivos y ámbito D-04, no nombre de rol. Excluir actor redundante y cuentas inactivas. Corte consistente posterior al lock de actividad; una revocación confirmada antes excluye y una posterior no borra historia. La recuperación usa solo 24 h y cierra administrativos tras primer lote persistido. La limitación ante cero filas o corrupción de un lote se declara en N6; no se inventan snapshots ni receptores para ocultarla.

Plantillas planas deterministas con IDs/códigos estables y fecha del evento, escapadas en Vue. No copiar motivos, descripciones libres, solución, CI, correos innecesarios, nombres de terceros, passwords, tokens, QR/hash, HMAC, private keys, RPC keys ni payloads de sensores. No almacenar HTML o URLs arbitrarios. Mensajes propios de incidencia solo dicen requiere revisión/cierre, sin imputar responsabilidad. Logs técnicos mínimos de ID/categoría/conteo, sin cuerpo de notificación; sin avisos recursivos de errores de generación.

El aviso propio permanece si cambia el permiso del objeto. El enlace se reautoriza en servidor tanto al mostrarlo como al acceder; no expone actividad completa ni otorga acceso a CU19/CU30. No borrado histórico, marcar todas ni volver a no leída. T-N03, T-N06, T-N16–T-N29 y T-N40 serán evidencia futura; no se presentan como pruebas realizadas en 9.5. Las reglas de secretos y la excepción exclusivamente dev Hardhat/Zod conservan su alcance previo.

## Seguridad aplicada a CU26 — Fase 10

notificaciones.ver_propias para cuatro roles, sin bypass administrativo. Query por destinatario antes de listar/contar/abrir; ajeno/inexistente 404, sin permiso 403. Primera lectura POST con CSRF y hora servidor, GET sin mutación. Enlaces reautorizados, DTO mínimo y snapshots sin texto libre/secreto. [Implementación y evidencia](NOTIFICATIONS_IMPLEMENTATION.md).

## Seguridad prevista de CU27/CU28 — Fase 10.5

[Contrato DR3/DR6–DR11](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11). Nuevos permisos solo previstos: dashboard.ver para cuatro roles, reportes.ver y reportes.exportar para Admin/Encargado. Exigen identidad ACTIVO, sesión vigente, rol elegible y permiso efectivo; los permisos de dominio y el scope siguen siendo obligatorios. No bypass, reportes personales ni métrica institucional oculta en payload de Docente/Estudiante. Ownership en SQL, no solo en Vue; selectores, agregados, columnas y exportaciones se autorizan también.

Revalidar al generar y antes de entregar archivo; una revocación invalida el resultado. Proyección mínima idéntica en preview/PDF/XLSX: excluir correo, CI, credenciales, tokens, datos QR/HMAC, canónicos y textos libres de motivos/descripción/solución. Número de serie exige equipamientos.gestionar; reportante exige consulta de la incidencia autorizada; actor de actividad sin usuarios.ver se reduce a referencia técnica. CU28 no habilita búsqueda global ni detalle de CU30; solo whitelist de negocio y exportaciones anteriores propias.

Query Builder/Eloquent parametrizado; catálogo fijo de slugs, filtros, columnas y orden. Rechazar claves desconocidas, IDs fuera de scope, SQL/ORDER BY/tabla aportados por cliente y payload de filas. CSRF para generación con efecto de auditoría; respuestas sensibles no-store, descarga attachment/MIME/nosniff. Filename público seguro del catálogo; temporal impredecible privado, sin path de cliente/URL permanente ni acceso de otro usuario. Limpieza inmediata tras servir/fallar y huérfanos en <=1 hora, limitada a carpeta del módulo.

XLSX: todas las cadenas se escriben con tipo explícito string, sin detección de fórmulas/hipervínculos. Probar prefijos =,+,-,@ incluidos espacios/controles iniciales; no basta escapar CSV/HTML ni aplicar formato visual. Datos numéricos validados mantienen su tipo. Sin macros, fórmulas de usuario ni relaciones externas. PDF: escapar contenido y limitar recursos a assets locales autorizados; impedir HTML/URL/file arbitrarios. Librerías locales, sin CDN/servicios externos, evaluadas antes de instalar en Fase 11.

Actividad de exportación no crítica EXITO solo para archivo generado y autorizado listo para servir; descripción mínima segura, sin payload. Fallos/preview no producen REPORTE_EXPORTADO EXITO; no agregar notificación ni evidencia Blockchain. Poner a disposición no acredita recepción física completa en el navegador. Estos controles son requisitos futuros T-DR01–T-DR48, no funcionalidad implementada por Fase 10.5.

## Controles de dashboard y reportes — Fase 11

Sesión vigente, dashboard.ver/reportes.ver/reportes.exportar, roles elegibles y permisos de dominio acumulativos. Ownership SQL en panel personal; no IDs arbitrarios del actor. Opciones y columnas autorizadas; serie restringida, datos libres excluidos, actor de actividad sin nombre salvo usuarios.ver. Revalidar permisos al finalizar; firma de preview no contiene filas. XLSX StringCell inerte incluso ante prefijos/control chars; PDF literal local sin HTML/recursos remotos. Parámetros tipados/whitelist, temporales privados no públicos, limpieza y presupuestos; EXITO de exportación no crítico/no Blockchain/no avisos. [Pruebas y operación](DASHBOARD_REPORTING_IMPLEMENTATION.md).
