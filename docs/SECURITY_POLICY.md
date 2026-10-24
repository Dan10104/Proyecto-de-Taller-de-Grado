# Política de seguridad

**Actualización documental Fase 6.5: D-10 RESUELTA PARA FASE 7, cero bloqueantes documentales.** [Contrato IoT y puerta de Fase 7](DECISIONS.md#readiness-for-phase-7). CU21/CU22 no implementados. La evidencia funcional anterior sigue correspondiendo a Fase 6 y conserva sus límites.

**Estado vigente: Fase 6 implementada y aceptada; suite automatizada aprobada. La prueba física se acepta por confirmación del usuario, sin nueva evidencia válida corroborada independientemente.** CU11/CU20 y extensión opcional CU17/CU18 incorporados. [Arquitectura, 325 pruebas, trazabilidad y límites de aceptación](QR_IMPLEMENTATION.md). Los cierres anteriores se conservan como evidencia histórica; Fase 7 no iniciada.

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
| IoT | Identificación/autenticación del dispositivo, validación del esquema y estado habilitado; HMAC RAW, repetidos y frescura cerrados por D-10 en Fase 6.5, implementación pendiente |
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

Blockchain solo recibe evidencia mínima tras confirmación principal; excluir nombres, correos, contraseñas, datos personales completos y datos operativos completos. Especificación canónica antes de Fase 9: determinismo, orden/fechas estables, UTF-8, selección permitida y version_canonica. Fallo externo conserva operación y estado local PENDIENTE cuando corresponda; no afirmar que la cadena sea fuente principal.

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
