# Fase 14 — Docker y preparación de despliegue

Fecha: 2026-10-26, America/La_Paz. Preparación local completada; **recursos cloud creados: NONE; deployment real: NO EJECUTADO**. No APIs habilitadas, IAM modificado, imágenes subidas a Artifact Registry ni Cloud Build ejecutado. gcloud solo versión/autenticación existente y listado local de contexto, sin publicar identificadores privados.

Se conserva CU01–CU30 web, RF26 móvil diferido y los 217 textos normativos. Sin features/enums/reglas nuevas. Las 31 migrations académicas originales permanecen idénticas: 31 tablas, 211 columnas, 63 FK; una migration agrega cuatro tablas framework autorizadas (sessions/cache/cache_locks/jobs), más migrations técnica preexistente = 36 tablas físicas. Word/PDF originales intactos por SHA-256. Composer/npm lockfiles intactos y ninguna biblioteca funcional añadida.

## Entregables y diseño

[Docker y ejecución local](DOCKER_DEPLOYMENT.md), [arquitectura/diagrama/IAM/secretos](DEPLOYMENT_ARCHITECTURE.md), [checklist](DEPLOYMENT_CHECKLIST.md), [recuperación](DEPLOYMENT_RECOVERY.md) y [plantillas/scripts GCP](../deploy/gcp/README.md) constituyen el procedimiento reproducible. El plan no tiene modo execute. Las cuatro cargas cloud comparten digest; los jobs no arrancan con cada instancia web. Plantillas requieren resolver proyecto/URL/SHA/digest/versiones/fecha RF21 y autorización posterior.

La imagen usa PHP 8.5.12 oficial Apache, Node 24.22.1 mínimo para canonicalize/ethers, Composer 2.10.3 solo builder y locks existentes. Digests completos en Dockerfile. Apache 2.4.68, 8 workers, UID33, PORT8080 configurable, public exclusivo, storage/bootstrap/cache privados escribibles. Config/route/view caches se crean al iniciar con secretos runtime, no durante build. Sin artisan serve/Vite dev/npm/Composer/Hardhat/Zod/solc/gcc/g++/make/autoconf en filesystem final; las capas históricas oficiales base sí contienen su toolchain de origen, eliminado antes de la imagen ejecutable final. Páginas Vue fuera de public se conservan porque Inertia3 verifica su existencia; el navegador recibe assets compilados.

Sesiones/locks/cuota IoT compartidas en MySQL y queue database post-commit, con dos handlers existentes y payloads por ID. No failed_jobs: excepción/payload no se conserva allí; recuperación durable original desde actividad/evidencia y warnings seguros. El desarrollo tradicional conserva deferred y su DB; Compose usa DB nueva privada, cuentas DML/DDL separadas, red/volumen exclusivos y ningún puerto MySQL host. Los servicios MySQL preexistentes no se modificaron.

## Validación ejecutada

| Comprobación | Resultado observado |
| --- | --- |
| Baseline Fase14, antes de cambios | 1.072 tests / 12.963 assertions / 1.023,93 s |
| Suite final host MySQL espacio_u_testing | **1.095 tests / 13.046 assertions / 1.012,37 s**, todos aprobados |
| Tests anteriores | 109 archivos y 532 métodos baseline conservados; data providers generan los casos |
| Incremento | 23 casos PHP: 16 configuración/proxy/health + 7 almacenamiento/locks/queue |
| Fallos / errores / skipped | 0 / 0 / 0 |
| Pint / npm run build | Aprobados; build frontend 454 ms observado |
| Plantillas cloud | 6 unittest Python aprobados; validación local, sin invocar API |
| Smoke HTTP Linux | 41 checks de health, login/RBAC, módulos, exports, QR/HMAC, sesiones y providers disabled |
| Kernel production Linux | 11 checks: HTTPS/proxy, URLs/cookies/HSTS/assets, error seguro/headers y ChatbotService con FakeProvider |
| Concurrencia | Cache/wallet locks entre 2 réplicas; 20 requests de cuota con 8 workers: 10 aceptadas/10 limitadas; 8 productores: 1 notificación y 1 evaluación |
| Activos reales | Cinco CSS/JS compilados servidos; sin Vite dev |
| HTTP local concurrente | 32 requests dashboard, 8 clientes, 2 réplicas: media 216,80 ms, máximo 673,89 ms |
| Parada ordenada | 1,604 s, exit 0, sin OOM; reinicio y health correctos |
| Restauración local | 5,371 s; esquema sintético separado, 36 tablas/63 FK y conteos coincidentes; sesiones recuperadas invalidadas |
| Contratos | 217 textos, 30 CU, 17 D, CH1–CH12/20 rutas/50 criterios y AC1–AC15/56 acciones/40 criterios intactos |

El informe histórico Fase13 contiene 12.964 assertions. La nueva ejecución baseline observó 12.963; no se cambió ese resultado histórico ni se igualó artificialmente el conteo. Todos los métodos anteriores se conservaron y ambas ejecuciones de esta fase pasaron. Los nuevos casos aumentan en 83 las aserciones respecto al baseline observado.

Smoke navegable: login, dashboard, navegación a reportes, selector/vista previa de espacios y tabla sintética revisados en navegador integrado; consola sin errores/warnings. Sesión y pestaña QA cerradas al finalizar. La comprobación HTTP automatizada recorre reservas, préstamos, notificaciones, bitácora, IoT y trazabilidad, además del dashboard personal. No se presenta como una nueva revisión manual de los 30 CU o matriz completa responsive de F13. La herramienta de navegador no permitió guardar PNG en el workspace; no se inventa un archivo de captura.

QR: SVG generado por endpoint Linux, sin guardar/mostrar payload o imagen sensible. No prueba de cámara server-side. IoT: HTTP directo rechazado; endpoint/kernel real con HMAC y señal TLS aceptó ingestión. JCS: vectores numéricos RFC8785 (1e20/1e30/1e-6/-0/unicode) y orden de claves; ethers: PHP→Node contra RPC simulado dentro del contenedor, chainId Sepolia, sin red Sepolia/clave/fondos. Chatbot deshabilitado devuelve error controlado; FakeProvider inyectado en ChatbotService Linux sin llamadas externas. Blockchain disabled conserva evidencia local y permite operar.

### Rendimiento PDF/XLSX

Tres muestras por formato, 10.000 reservas sintéticas, CPU1/memoria1GiB por web. Inserción dentro de transacción y rollback al terminar; no fixtures productivas ni benchmark cloud.

| Formato | Preview | Media export | Muestras ms | Pico PHP | Tamaño |
| --- | --- | --- | --- | --- | --- |
| PDF | 93,45 ms | 2.263,84 ms | 2315,84 / 2226,99 / 2248,70 | 58 MiB | 1.715.176 bytes |
| XLSX | 44,87 ms | 1.430,71 ms | 1441,73 / 1408,94 / 1441,45 | 28 MiB | 287.848 bytes |

El pico PDF coincide con ~58 MiB de F13, sin regresión grave observada. Descargas HTTP adicionales verificadas con pypdf/openpyxl: firma, texto/marca/fuentes/metadata, hoja y celdas; contenido `=1+1` explícitamente string sin fórmula. Dos exports produjeron exactamente dos REPORTE_EXPORTADO. Temporales eliminados por respuesta y huérfano de más de una hora por janitor de su misma instancia. No persistencia pública.

### Fallos y límites observados

Al detener únicamente MySQL Compose, `/up` continuó en 200: no depende de SQL. La petición `/login` superó el timeout cliente de 10 s al desaparecer DNS `db`; se documenta como timeout, **no** como respuesta HTTP exitosa. Al restaurar DB, login/health se recuperaron. Error 500 controlado se comprobó por separado en kernel Linux production: mensaje seguro español, sin stack trace, con headers. El comportamiento y tiempos de fallo de Cloud SQL por socket deben medirse en staging real.

Durante preparación se corrigieron caches de paquetes dev arrastradas desde host, falta de Pages para Inertia3, orden de trusted proxy antes de IoT, inicialización RF21 y lectura consistente de cuota MySQL bajo concurrencia. Ninguno queda abierto. Estos cambios pertenecen a empaquetado/configuración/almacenamiento framework, no a nuevas reglas funcionales.

## Auditorías, imagen y privacidad

| Auditoría | Resultado |
| --- | --- |
| Composer host y runtime contenedor | 0 advisories, 0 abandonados; check-platform-reqs --no-dev aprobado |
| npm frontend completo / omit=dev | 0 / 0 vulnerabilidades |
| Blockchain omit=dev | 0 vulnerabilidades |
| Blockchain desarrollo | **5 altas Hardhat/Zod**, excepción temporal autorizada vigente |
| Imagen runtime | Hardhat/Zod dev/solc y toolchain de build ausentes del filesystem ejecutable |
| Reconsulta Hardhat | 3.21.1 disponible, sigue dependiendo Zod ^3.23.8; no elimina excepción compatible |

Se conserva la deuda técnica y los límites de [la excepción original](BLOCKCHAIN_IMPLEMENTATION.md): GHSA-mrhx-c7xc-m3w5 / CVE-2023-54404, solo desarrollo local, sin servicio expuesto ni datos arbitrarios externos; producción no está amparada por la excepción. Locks sin cambios, no audit fix --force/overrides/downgrades. No se afirma una auditoría de vulnerabilidades de todo el sistema operativo: resultados cero corresponden a Composer/npm especificados.

Escaneo de imagen: 41 capas/18.999 archivos, 18 valores secretos locales comparados en memoria, cero credenciales del proyecto y cero rutas privadas/dev prohibidas. Se identificaron cinco PEM públicos de autoevaluación incorporados en libgnutls de la base oficial: huellas y binario exactos cotejados con [crypto-selftests-pk.c upstream](https://github.com/gnutls/gnutls/blob/3.7.9/lib/crypto-selftests-pk.c). El escáner solo reconoce esas huellas con hash exacto de la biblioteca; no excluye bibliotecas completas ni oculta otro PEM. No son claves de ESPACIO-U ni utilizables como secretos del proyecto.

Contexto build/upload local: allowlist .dockerignore/.gcloudignore, 394 archivos revisados sin .env/.local/.tools/.git/node_modules del host. docker history e imagen/config contienen solo metadatos no secretos. `.env.docker` ignorado, valores no impresos y ACL sin permisos amplios Everyone/Users/Authenticated Users. No modificar `.env` original. Evidencia local privada en `.local/qa/phase14`; no dump de DB, cookies o etiquetas QR versionados. Escaneo de worktree/índice/diffs/historial/build/logs/PDF/XLSX se repite antes del commit y publicación.

Dos builds de igual configuración produjeron misma configuración runtime y mismas capas, con índices OCI distintos por atestaciones. Tamaño observado **243.684.289 bytes (232,40 MiB)**. La compilación final se repitió desde el commit `b3b32eeca47e0eca283c65b4b75fcaeb2a98bc92`; tag local `espacio-u:b3b32eeca47e0eca283c65b4b75fcaeb2a98bc92`, digest local final `sha256:504584f931fef6735b1f234e0663ac27b7e74405e21ea0a156355b1d72ccfc2e`. Mismo código/config/dependencias y mismas capas en ambas compilaciones. El commit documental posterior no cambia el contenido runtime. Este digest no fue publicado en Artifact Registry; el futuro push debe resolver/verificar su digest en ese registro. Nunca latest para publicar. PORT también se revalidó en 8181 sin publicar un puerto host adicional; el contenedor temporal fue retirado.

## Índice de los 94 puntos solicitados

| N.º | Resultado / ubicación |
| --- | --- |
| 1–2 | Preparación validada; rama feature/phase-14-deployment-prep |
| 3–7 | Dockerfile multistage, PHP oficial 8.5.12 Apache Bookworm, Apache2.4.68, PORT configurable8080 |
| 8–12 | Node24.22.1/canonicalize/ethers productivos; Hardhat excluido; Composer no-dev y Vite build desde locks |
| 13–15 | .dockerignore allowlist; 232,40 MiB observados; history/capas/secretos examinados |
| 16–19 | UID33, storage/bootstrap cache mínimos, stdout/stderr y /up sin SQL |
| 20–22 | Proxy HTTPS simulado, APP_URL HTTPS cloud, Secure/HttpOnly/SameSite=lax; HTTP solo excepción local |
| 23–26 | Sesiones database + sessions técnica; cache/cache_locks y exclusión entre réplicas |
| 27–30 | Queue database post-commit; queue acotada; scheduler schedule:run; migrate --force separado |
| 31–35 | Compose aislado, MySQL8.4.11 privado, instalación desde cero/seed seguro, dos réplicas y revocación compartida |
| 36–40 | PDF/XLSX válidos, QR SVG, HMAC Linux, chatbot/Blockchain disabled seguros |
| 41–43 | JCS/ethers bridge Linux, config/route/view cache runtime y graceful stop probado |
| 44–49 | Cloud Run preparado southamerica-west1 configurable, 1CPU/1GiB/concurrency20/min0/max3/timeout60 |
| 50–53 | Cloud SQL8.4 nativo socket /cloudsql, usuarios DML/DDL distintos; presupuesto173<200 durante rollout conservador |
| 54–56 | Artifact Registry tags SHA inmutables/digest; Cloud Build opcional preparado, nunca ejecutado |
| 57–60 | Secret Manager nombres/versiones sin valores; identidades web/jobs/migrate/scheduler/build e IAM mínimo documentado |
| 61–64 | Triggers OAuth cada minuto La Paz; backup diario7/PITR7; recuperación aislada y ensayo local |
| 65–68 | Orden backup/build/push/migrate/candidate/smoke/tráfico/triggers/monitor; rollback revisión; temporales y guard production |
| 69–71 | OpenAI/Sepolia opcionales desactivados; IOT master runtime64hex requerido antes de sensores reales |
| 72–75 | +23 PHP +6 Python; 1095 tests /13046assertions/1012,37s |
| 76–81 | Pint/build pasan; Composer/frontend/Blockchain runtime0; Blockchain dev5altas explícitas |
| 82–85 | Secret scan, smoke Docker real; Docker29.5.3/Compose5.1.4 disponibles; gcloud574.0.0 disponible |
| 86–87 | Cloud resources NONE; cinco documentos principales + README GCP y contratos/trazabilidad actualizados |
| 88–91 | Commits/publicación/fast-forward/main limpio se verifican al cierre y constan en entrega Git |
| 92–94 | OpenAI pendiente key/cuota; Sepolia pendiente testETH; listo para staging **solo tras autorización posterior expresa** |

## Commits y control de publicación

Base inicial limpia/sincronizada: `51e95f7b13e73566e6f6368087a96f079932a813` en main == origin/main. Rama `feature/phase-14-deployment-prep`. Commits técnicos:

- `5025152` — build: add production container and shared runtime infrastructure.
- `b3b32ee` — ops: prepare cloud run templates and container validation tools.

El commit documental completa README, cinco guías/informe nuevos y contratos afectados. Antes de cada commit se revisaron status, diff/diff cached y secret scan; publicación autorizada de feature y fast-forward main sin force push se confirma contra remoto al finalizar. El hash documental y la igualdad final de referencias constan en la entrega, sin inventar un hash autorreferencial dentro del propio commit.

## Readiness y siguiente acción autorizable

**Cero bloqueantes para cerrar preparación local. No se autoriza ni inicia deployment.** Antes de staging: elegir proyecto/presupuesto/región/URL, provisionar versiones de secretos y usuarios, autorizar recursos/API/IAM/push/deploy, ejecutar checklist y comprobar aceptación real de plantillas, socket/TLS, jobs/OAuth, límites/cold starts/costes, monitorización y restore/PITR cloud. La configuración inicial es de piloto ZONAL, no HA certificada. CPU asignada durante vida de instancia permite barrido local aun en idle y tiene coste; min0 no elimina coste de Cloud SQL/storage/backups/scheduler.

No se usaron fondos reales, no claves cloud descargadas, no datos locales importados a cloud y no proveedores externos activados. OpenAI y Sepolia pendientes independientes. La próxima acción recomendada es una autorización de staging con proyecto/región/presupuesto definidos y el checklist revisado, no una nueva feature.
