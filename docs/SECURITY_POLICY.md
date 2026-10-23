# Política de seguridad

Repositorio público. Esta política aplica a documentación, código, pruebas, historial Git, imágenes, logs, issues y artefactos. Fuentes: instrucción de Fase 0; RNF-10–17, IS-14, IC-02/04/05/08/11, RD-19/20/23/43/44 y DB-21–24.

## Secretos y datos

Nunca publicar `.env`, contraseñas, API keys, tokens, claves privadas, certificados privados, credenciales de base de datos/Google Cloud/Blockchain/chatbot/GitHub ni JSON de service accounts. No usar valores reales como datos de prueba. Seeders/factories futuros utilizarán identidades y correos ficticios; los originales del informe, sus nombres y las capturas con datos personales no se incorporan al repositorio público.

`.env.example` es la única excepción a `.env.*`; contiene valores vacíos o inocuos. `APP_KEY`, usuarios/contraseñas y claves externas permanecen vacíos. Las variables de integración de la plantilla son propuestas de configuración, aún no consumidas por software. No crear ni publicar una clave de aplicación real en Fase 0. Las variables con secretos no usarán prefijos destinados a exponerlas al frontend, como `VITE_`.

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
| Identidad | Hash de contraseñas; cuenta activa; vigencia y revocación de sesión; fallos sin información sensible; mecanismo concreto pendiente D-07 |
| Autorización | Política por acción/objeto, propietario y ámbito; probar acceso directo a rutas/API y descargas, no solo visibilidad de botones |
| Web | Sesión segura y protección CSRF donde corresponda; validación del lado servidor; salida escapada; probar entradas no confiables |
| Persistencia | Consultas parametrizadas/ORM, FK/UNIQUE/CHECK y transacciones; no permitir borrado de historia necesaria |
| QR | Token vigente y objetivo/contexto correctos; no usar conocimiento del código como autorización; no registrar token sin hash |
| IoT | Identificación/autenticación del dispositivo, validación del esquema y estado habilitado; tratamiento de repetidos/frescura pendiente D-10 |
| Blockchain | Minimización y privacidad antes del envío; referencia técnica; hash/versión consistente; pendiente durable ante fallo |
| Chatbot | Contexto limitado por permisos, proveedor solo desde backend, ausencia de herramientas que ejecuten operaciones protegidas |
| Archivos/reportes | Generación y descarga autorizadas, ubicación privada/controlada, sin acceso por adivinar un identificador |
| Producción | HTTPS, secretos por entorno, debug desactivado y revisión de errores/logs; configuración de red/acceso en Fase 14 |

CSRF, parametrización y salida escapada son controles técnicos propuestos para satisfacer los requisitos de seguridad; no se presentan como nuevas funcionalidades del informe. Límites de autenticación/reintentos y mecanismo de tokens deben documentarse antes de implementarse.

## Respaldo y recuperación

R-DISP-04 exige al menos un respaldo diario y conservar como mínimo los siete más recientes. R-DISP-05 establece un objetivo de restauración de hasta cuatro horas. Deben probarse con información sintética y luego con el procedimiento operativo autorizado. Es una obligación sobre la información operativa, compatible con la prohibición de duplicar manualmente código fuente.
