# Blockchain y trazabilidad verificable — Fase 9

**Estado vigente — Fase 13 completada y validada (2026-10-26).** QA integral de CU01–CU30 y RF1–RF25 web; RF26 móvil diferido. [Informe de seguridad, rendimiento y pruebas](PHASE_13_SYSTEM_VALIDATION.md) y [matriz final por CU](FINAL_USE_CASE_VALIDATION.md). Los estados de fases anteriores que siguen son históricos. OpenAI/Sepolia pendientes independientes; excepción dev Hardhat/Zod explícita. Fase 14 NO iniciada.

**Estado vigente — Fase 10.5 documental (2026-10-26).** RF22/CU27 y RF23/CU28: [contrato de dashboard y reportes cerrado](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11), **0 bloqueantes documentales**. Fase 11 NO iniciada; CU30 NO IMPLEMENTADO. Fase 10 funcional se conserva, sin cambios de código, datos, permisos o dependencias. Los cierres anteriores mantienen su contexto histórico.

**Estado histórico funcional — Fase 10 (2026-10-26).** RF21/CU26 implementados y verificados: 17 reglas, avisos propios, primera lectura y recuperación de 24 h. [Informe y evidencia](NOTIFICATIONS_IMPLEMENTATION.md). CU30 general y Fase 11 no iniciados. Sepolia real sigue pendiente de testETH. Los apartados de cierres anteriores conservan su contexto histórico.

**Fase 9 implementada y validada offline, 2026-10-25/26.** Rama `feature/phase-9-blockchain` desde main limpio `a7731994f9a7339a8398739721af043c83ede6fc`. El contrato D-12 sigue vigente. Sepolia real pendiente de testETH; excepción temporal dev de Zod autorizada. No se inicia Fase 10 ni CU30 general. Se distinguen resultados ejecutados offline de la integración pública pendiente.

## Evaluación JCS y propuesta de implementación

La instrucción de Fase 9 §29 pide una implementación PHP compatible con RFC8785. Se revisaron código, licencia, requisitos de PHP, mantenimiento, pruebas y advisories de los candidatos. El spike ejecutado con PHP 8.5.12 encontró diferencias de conformidad:

| Paquete | Versión / licencia | Hallazgo reproducible |
| --- | --- | --- |
| mmccook/php-json-canonicalization-scheme | 1.0.0 / MIT; release 2023, PHP ^8.1 | 1e20 produce `1` en vez de `100000000000000000000`; U+2028 se escapa aunque JCS exige conservarlo |
| truschery/kanon | 1.0.1 / MIT; release marzo 2026, PHP ^8.2 | 1e20 produce `1e+20`; U+2028 también se escapa |
| root23/php-json-canonicalization | 1.0.1; Packagist no declara licencia | Inspección confirma la misma rutina de siete decimales/rtrim del primer candidato; no adoptado |
| ahg/inference-receipts | 0.1.1 / Apache-2.0; PHP ^8.2 | Su normalización conserva `.0` en exponentes como 1e30; además emite warning de conversión en PHP 8.5 para 1e20 |

Fuentes: [mmccook](https://github.com/mmccook/php-json-canonicalization-scheme), [Kanon](https://github.com/truschery/kanon), [root23](https://packagist.org/packages/root23/php-json-canonicalization), [AHG](https://github.com/ArchiveHeritageGroup/inference-receipts), [RFC8785](https://www.rfc-editor.org/rfc/rfc8785). No se instaló ninguno como dependencia de producción ni se corrigió su algoritmo artesanalmente.

**Excepción autorizada por el usuario el 2026-10-25:** mantener TraceabilityCanonicalizer y builders en PHP, delegando únicamente la serialización JCS a `canonicalize` 5.1.0 (Apache-2.0, autor coeditor del RFC) mediante proceso Node local de vida corta, sin HTTP ni microservicio. El proceso de canonicalización no recibe environment de Blockchain ni secretos; entrada/salida por pipes, sin archivos temporales, logs o persistencia del canónico. El bridge ethers conserva su entrada separada de hash/referencia. RFC8785 y vectores golden de las cuatro acciones verificados antes de adoptarlo. [Paquete y código oficial](https://github.com/erdtman/canonicalize).

Esta excepción afecta al lenguaje de la biblioteca serializadora, no a JCS, SHA-256, schemas, privacidad o arquitectura Laravel. Respuesta explícita: «Autorizar JCS con Node local (recomendado)».

## Precisión de la instrucción Fase 9

Fase 9 §11 prevalece sobre la tabla histórica de Fase 8.5: las cuatro actividades humanas guardan actor_id real y proceso_responsable NULL. Se conserva chk_registro_responsable. La excepción fk_registro_actor ON UPDATE RESTRICT sigue autorizada, sin otras modificaciones del SQL.

## Excepción temporal de desarrollo autorizada

`npm audit` inicial informó siete entradas altas. Se corrigieron `tmp`/`solc` fijando `tmp` 0.2.7 mediante override compatible; no se bajó el compilador 0.8.38 que corrige un fallo del optimizador. Quedan cinco entradas propagadas desde Zod: `zod`, `hardhat-zod-utils`, Hardhat y sus dos plugins. Hardhat 3.21.0 y hardhat-zod-utils 3.0.8 requieren Zod ^3.23.8; el advisory GHSA-mrhx-c7xc-m3w5 marca todas las versiones anteriores a 4.6.6. No existe corrección compatible publicada en su rango. Forzar Zod 4 rompe APIs usadas por Hardhat (`z.record` con un argumento y `z.function`).

El código afectado valida configuraciones locales de compilación/pruebas, no recibe solicitudes web ni datos de usuarios. Zod y Hardhat pertenecen exclusivamente al árbol de devDependencies de blockchain/; el runtime usa ethers, canonicalize y jsonc-parser. El usuario autorizó explícitamente esta excepción temporal: auditorías productivas sin vulnerabilidades; las cinco entradas transitivas exclusivamente dev pueden permanecer bajo estos controles. La auditoría completa NO se reportará como cero. Fuentes: [GHSA-mrhx-c7xc-m3w5 / CVE-2023-54404, severidad alta, agotamiento de memoria al validar colecciones sobredimensionadas](https://github.com/advisories/GHSA-mrhx-c7xc-m3w5), [GHSA-7c78-jf6q-g5cm / CVE-2026-49982, tmp corregido en 0.2.7](https://github.com/advisories/GHSA-7c78-jf6q-g5cm).

Controles obligatorios: versiones directas exactas y lockfile; no `audit fix --force`, overrides incompatibles ni downgrades inseguros; toolchain sin exposición HTTP/API pública ni procesamiento de entradas externas; EVM Hardhat en memoria durante tests, con gateway efímero autenticado en loopback que permite únicamente métodos ETH JSON-RPC; ningún parser de configuración expuesto; producción instala `npm ci --omit=dev` dentro de blockchain y recibe ABI público previamente generado. El runtime web no importa Hardhat ni Zod. Esta excepción no ampara producción, nuevas vulnerabilidades críticas, RCE, robo de credenciales o compromiso de cadena de suministro.

**Deuda técnica D-12-DEV-ZOD:** retirar la excepción cuando Hardhat publique una combinación compatible con Zod corregido. Volver a consultar versiones y auditar antes del cierre de esta fase; repetir la revisión al actualizar dependencias. Reportar por separado auditoría completa Blockchain, `--omit=dev` y frontend principal. Resultado productivo comprobado durante implementación: cero vulnerabilidades.

### Inventario exacto y frontera de producción

| Dependencia directa | Versión | Alcance / licencia |
| --- | --- | --- |
| canonicalize | 5.1.0 | Runtime JCS / Apache-2.0 |
| ethers | 6.17.0 | Runtime Ethereum / MIT |
| jsonc-parser | 3.3.1 | Runtime: detección estricta de claves duplicadas antes de JCS / MIT |
| hardhat | 3.21.0 | Desarrollo / MIT |
| @nomicfoundation/hardhat-ethers | 4.2.1 | Desarrollo / MIT |
| @nomicfoundation/hardhat-node-test-runner | 3.1.2 | Desarrollo / MIT |
| @openzeppelin/contracts | 5.6.1 | Compilación / MIT |
| solc | 0.8.38 | Compilación / MIT |

Cadena exacta afectada: `blockchain (dev) → hardhat@3.21.0 / hardhat-ethers@4.2.1 / hardhat-node-test-runner@3.1.2 → @nomicfoundation/hardhat-zod-utils@3.0.8 → zod@3.25.76`; Hardhat y plugins también dependen directamente de Zod en el mismo rango 3.x. Son cinco entradas de auditoría de severidad alta derivadas del mismo advisory, no cinco CVE distintos. La versión corregida comienza en Zod 4.6.6; no se instala por override incompatible. `tmp@0.2.7` es el único override, compatible con solc. Cada `package-lock.json` queda versionado y se usa `npm ci`, sin `audit fix --force`.

Laravel no importa Hardhat ni Zod. `blockchain/runtime/` importa exclusivamente dependencias de producción. El futuro contenedor de Fase 14 necesitará PHP, Node compatible, estos tres paquetes y el ABI generado; debe excluir Hardhat, sus plugins, Zod, solc, caches y tests. No se implementa Docker en esta fase. Las claves y el RPC se pasan al bridge por environment aislado; JCS recibe solamente bytes por pipe, sin environment de aplicación. No existe servidor Node productivo.

## Esquema, actividades y atomicidad

Se añaden únicamente las migraciones `2026_10_25_000029_create_registros_actividad_table` y `2026_10_25_000030_create_evidencias_trazabilidad_table`: 13 y 11 columnas oficiales, respectivamente. Se conservan tipos, defaults, índices, seis FK de actividad, una FK de evidencia, CHECK responsable y los UNIQUE de actividad/hash. Solo `fk_registro_actor` cambia ON UPDATE a RESTRICT según autorización; ON DELETE y las otras FK conservan el SQL. No se altera ninguna de las 28 tablas académicas anteriores ni se crea `notificaciones`. Proyección instalada: 30 tablas académicas, 203 columnas y 61 FK, además de la tabla técnica `migrations`. El catálogo pasa de 43 a 46 permisos.

Modelos `RegistroActividad` y `EvidenciaTrazabilidad`; enums `AccionCritica`, `ResultadoRegistroActividad`, `EstadoEvidencia`, `ResultadoVerificacionEvidencia`. Identidad histórica inmutable mediante modelos, FK y guardas del servicio; los scripts de prueba que simulan corrupción usan SQL directo exclusivamente en testing. No se hace backfill ni se fabrica historia de operaciones anteriores.

| Acción | Punto transaccional / actor | Hito conservado |
| --- | --- | --- |
| RESERVA_APROBADA | CU09, ReservationService, responsable de resolución | Reserva PENDIENTE → APROBADA; resolución, intervalo y código |
| RESERVA_CANCELADA | CU10, ReservationService, cancelada_por | PENDIENTE/APROBADA → CANCELADA; cancelación y motivo |
| PRESTAMO_ENTREGADO | CU17, LoanService compartido por flujo normal y QR; entregado_por | APROBADO → ENTREGADO; entrega y receptor |
| PRESTAMO_DEVUELTO | CU18, LoanService; confirmado_por real | Devolución CONFIRMADA y préstamo DEVUELTO; recepción original y confirmación |

Cada actividad se inserta dentro de la misma transacción MySQL que su operación: `EXITO`, `critica=true`, actor real, usuario afectado solicitante/receptor, recurso y reserva o préstamo; `incidencia_id`, `descripcion` y `proceso_responsable` son NULL. Un fallo de inserción revierte el negocio. NO_DETERMINABLE pendiente no crea actividad: la confirmación posterior crea una sola, conservando recibido_por/registrada_en iniciales. La incidencia derivada de una devolución no duplica evidencia.

Solicitud/rechazo de reserva, solicitud/aprobación/rechazo de préstamo, QR individual, IoT, incidencia, mantenimiento, evaluación CU23, ALERTAR, NO_DETERMINABLE y APROBADA → LIBERADA quedan excluidos. CU23 conserva `evaluaciones_liberacion` y pivotes. La aprobación previa sigue reconstruible después de CANCELADA/LIBERADA; la entrega sigue reconstruible después de DEVUELTO. CU30 general permanece pendiente, sin pantalla ni búsqueda general de actividades.

## Canónico y privacidad

`CanonicalEvidenceBuilder` construye exclusivamente los cuatro schemas; `TraceabilityCanonicalizer` delega JCS al proceso Node local; `TraceabilityHashService` recibe bytes y devuelve SHA-256 hex minúsculo de 64 caracteres. Se persisten `SHA-256` y `espacio-u-jcs-v1`. El hash externo es `0x` más esos 64 caracteres; el Keccak empleado por Ethereum para bytecode/tx no reemplaza SHA-256 de evidencia.

Las seis claves comunes son `activity_id`, `occurred_at`, `operation`, `schema`, `subject`, `version`. Valores fijos `schema=espacio-u-trace`, `version="1"`; IDs strings decimales (también BIGINT >2^53), fechas desde el DATETIME persistido America/La_Paz a UTC `YYYY-MM-DDTHH:MM:SSZ`, segundos sin fracción. `occurred_at` procede de actividad.fecha_hora, no del navegador ni de una nueva lectura del reloj.

| Subject | Claves exactas |
| --- | --- |
| Aprobación, 7 | reserva_id, reserva_codigo, espacio_id, inicio, fin, resuelta_por_id, resuelta_en |
| Cancelación, 6 | reserva_id, reserva_codigo, espacio_id, cancelada_por_id, cancelada_en, motivo_cancelacion_hash |
| Entrega, 9 | prestamo_id, prestamo_codigo, equipamiento_id, entrega_id, entregado_por_id, receptor_id, fecha_hora, condicion_entrega_hash, observacion_hash |
| Devolución, 11 | prestamo_id, prestamo_codigo, equipamiento_id, devolucion_id, recibido_por_id, confirmado_por_id, registrada_en, confirmada_en, condicion, descripcion_condicion_hash, observacion_hash |

Textos secundarios: SHA-256 de los bytes UTF-8 exactos persistidos, sin trim/case folding/normalización Unicode; NULL produce null y vacío produce su propio hash. JCS rechaza UTF-8 o surrogates inválidos, claves duplicadas incluso escapadas, números no finitos, comentarios y comas finales; entrada limitada a 256 KiB y profundidad 32. Orden UTF-16 y representación numérica RFC8785 verificados con vectores independientes del RFC, 23 vectores binary64 del apéndice B y cuatro golden con canónico/hash exactos. Se preservan composiciones Unicode distintas.

No se persisten ni muestran canónicos completos. Nombres, correo, CI, contraseñas, teléfonos y textos completos no entran en el schema. On-chain solo un argumento bytes32, además de metadatos técnicos públicos de Ethereum. El arnés intercepta calldata de 36 bytes (selector + bytes32), valor cero y chain ID. Los golden publicados contienen exclusivamente datos sintéticos.

## Procesamiento y recuperación

`CriticalActivityRecorder` registra un callback afterCommit: crea evidencia PENDIENTE lo antes posible y despacha `RegisterTraceabilityEvidence` por la conexión Laravel `deferred`. En web se ejecuta después de enviar la respuesta, sin un servicio adicional; CLI de reconciliación ejecuta el handler. No se usan tablas queue/outbox ni Redis. Un cierre entre commit/callback, fallo de JCS o dispatch perdido deja la actividad como fuente durable de recuperación; no se inserta un hash provisional.

`LocalEvidenceService::ensure` reutiliza la evidencia existente o resuelve la carrera por UNIQUE. Hash, algoritmo, versión, actividad y fecha local no cambian al reintentar. Estados solo PENDIENTE/REGISTRADA; `resultado_verificacion` arranca PENDIENTE con `ultima_verificacion=NULL`, que la UI presenta como «Sin verificar».

`TraceabilityLock` usa GET_LOCK de MySQL mediante una conexión dedicada, espera cero, liberación finally y purge. Una clave por evidencia serializa jobs/CU25/retries; otra por identidad de wallet coordina nonce pending, firma y broadcast entre procesos. No depende de locks file locales. Nunca mantiene transacciones ni locks de reserva/préstamo/equipo durante RPC. El guard rechaza ejecución dentro de una transacción de negocio. La publicación de la prueba externa usa una transacción breve, relee con lock de evidencia y compara identidad antes de actualizar.

`php artisan trazabilidad:reconciliar` procesa actividades críticas sin evidencia y evidencias pendientes, con lote 25 y cursor rotatorio para que un fallo no bloquee el resto. Scheduler cada minuto, `withoutOverlapping(10)`, compartido con CU23. El cache es solo throttle/cursor, no fuente de verdad: backoff creciente hasta una hora y expiración; el reintento autorizado lo restablece. No hay un estado ERROR añadido al SQL.

Se consulta antes de enviar. Registro existente se recupera por mapping y evento indexado `EvidenceRegistered`; la fecha on-chain acota la búsqueda de bloques/logs, con páginas limitadas. Una referencia conocida pendiente nunca se reenvía. El bridge emite la referencia candidata pública antes del broadcast; si se pierde la respuesta, PHP conserva esa referencia y consulta después. Si se pierde todo el callback, el reconciliador busca el hash en contrato. Solo un receipt revertido positivamente comprobado permite reemplazar una referencia pendiente en un nuevo intento; no se interpreta timeout como revert. Garantía: una evidencia local y un registro lógico externo; no se promete exactamente un broadcast ante todos los fallos posibles.

## Contrato y driver

`TraceabilityRegistry.sol`, Solidity 0.8.38, optimizador 200, EVM Cancun, OpenZeppelin Ownable 5.6.1. Contrato sin proxy/upgrade/token/NFT/custodia. Solo owner registra, hash cero rechazado, mapping bytes32 → timestamp de bloque; duplicado conserva timestamp y no emite un segundo evento. Lecturas `isRegistered`/`registeredAt`, registro nonpayable. Source y ABI final con hash del bytecode runtime están versionados; caches/artifacts se ignoran.

`BlockchainDriver` desacopla chainId/isRegistered/register/registrationTimestamp/find. `FakeBlockchainDriver` reside solo en tests. `EthereumSepoliaDriver` invoca ethers 6.17.0 mediante proceso local, stdin con acción/hash/referencia y environment aislado con configuración. No firma criptografía artesanal ni usa un proveedor específico. Logs/errores contienen códigos técnicos e IDs permitidos, sin environment, stack de ethers, RPC credential, llave ni payload.

Guard obligatorio de configuración **y eth_chainId real 11155111**, HTTPS (HTTP solo loopback), dirección y hash de bytecode runtime del Registry, owner de la wallet antes de firmar. Mainnet no es configurable como alternativa. EIP-1559 con estimateGas + margen 20%, fees actuales y nonce pending; límites 500.000 gas, 100 gwei y coste máximo 0,01 testETH por registro. Se comprueba saldo. RPC individual timeout 8 s y proceso externo máximo 15 s; JCS máximo 3 s. No se espera minería en la solicitud de negocio.

`REGISTRADA` exige receipt exitoso, bloque canónico, N confirmaciones (mínimo 1), cadena, contrato, calldata/hash/value, evento único, timestamp y mapping tanto actual como en el bloque del receipt coherentes. La fecha externa viene del bloque, convertida a DATETIME America/La_Paz. Ref estricta de 125 caracteres: `eip155:11155111/<contract hexadecimal minúsculo>/<tx hash hexadecimal minúsculo>`. El enlace opcional a Sepolia Etherscan se deriva de esa referencia; nunca sirve como prueba. Reorg/información no concluyente conserva pendiente o NO_VERIFICABLE, sin inventar éxito.

## CU25 y administración

Verificación local primero: versión/algoritmo/schema soportados, historia suficiente y hash reconstruido. Inconsistencia local evita RPC. PENDIENTE con hash correcto sigue PENDIENTE salvo recuperación externa confirmada; REGISTRADA con prueba completa produce VALIDO; contradicción positiva produce INCONSISTENTE; historia/version no disponible o RPC caído sobre REGISTRADA produce NO_VERIFICABLE. Este último resultado conserva estado, referencia y fecha externa. Toda CU25 concluida actualiza resultado y hora servidor; vuelve a comprobar historia local y permisos después de RPC para evitar publicar resultados obsoletos.

Tres permisos exactos, asignados únicamente a Administrador sin bypass: `trazabilidad.evidencias.ver`, `trazabilidad.evidencias.verificar`, `trazabilidad.reintentar`. Verificar/reintentar requieren también ver, sesión vigente y CSRF; revalidación de permisos en la transacción breve de publicación. Encargado, Docente y Estudiante no los reciben. Catálogo 46; Administrador 42, Encargado 28, Docente/Estudiante 9 cada uno.

UI Inertia/Vue: `/trazabilidad` listado paginado 15 y filtros por operación/estado/resultado/rango de registro local; detalle con contexto, metadatos, hash, fechas y explicación de los cuatro resultados. GET no modifica evidencia. Verificar y reintentar son POST; retry visible y permitido solo PENDIENTE. Inputs etiquetados, tabla con región desplazable y caption, botones semánticos, hash con wrap, enlaces de explorador con noopener/noreferrer. No hay UI de configuración de claves ni bitácora general.

## Configuración y operación reproducible

Con el entorno local del proyecto activo:

```powershell
. .\scripts\dev-environment.ps1
npm ci --prefix blockchain --ignore-scripts
npm run compile --prefix blockchain
npm run export:abi --prefix blockchain
npm run test:runtime --prefix blockchain
npm test --prefix blockchain
npm run test:integration --prefix blockchain
php artisan test
```

La integración local y Laravel escriben únicamente `espacio_u_testing`, protegida por TestingDatabaseGuard, nunca SQLite/desarrollo. Ejecutarlas secuencialmente: ambas pueden recrear la base de testing. La integración monta EVM en memoria y gateway autenticado efímero loopback con allowlist ETH; wallet aleatoria solo en memoria, sin output de cuentas/llaves. Desmonta recursos al finalizar. La suite normal no requiere Internet ni testETH.

Para aplicar en desarrollo, conservar `.env` y ejecutar migraciones incrementales y `SecuritySeeder`, nunca migrate:fresh. El helper existente `scripts/release-scheduler.ps1 -Action start/status` sirve a CU23 y al reconciliador. Reiniciarlo después de apagar el equipo. Se puede ejecutar reconciliación manual aun con Blockchain deshabilitada; crea/repara evidencia local sin red.

Variables de `.env.example`: BLOCKCHAIN_ENABLED, NETWORK, CHAIN_ID, RPC_URL, PRIVATE_KEY, CONTRACT_ADDRESS, CONFIRMATIONS, DEPLOYMENT_BLOCK y NODE_BINARY. Únicamente los placeholders quedan en Git. `NODE_BINARY` debe señalar el Node compatible si no está en PATH; `DEPLOYMENT_BLOCK` facilita recuperar logs. Laravel lee env exclusivamente desde config.

`scripts/prepare-wallet.mjs` crea una wallet dedicada únicamente si falta y la guarda directamente en `.env` local; imprime solo su dirección pública. No genera mnemonic ni utiliza wallet personal. `scripts/check-sepolia.mjs` comprueba red/saldo sin transacciones. Con testETH y configuración segura: `npm run deploy:sepolia --prefix blockchain`, luego `npm run smoke:sepolia --prefix blockchain`. Deployment conserva address/bloque en `.env` y manifiesto público en `blockchain/deployments/sepolia.json` cuando realmente finaliza. Activar BLOCKCHAIN_ENABLED únicamente tras verificar esa configuración. El smoke está separado de la suite normal.

## Estado de Sepolia real

Wallet pública dedicada: **0xD308121631a30b1CDF35bdd9F7553B8303b974b1**. La clave existe exclusivamente en `.env` ignorado. Consulta real RPC: red 11155111 accesible y saldo **0,0 testETH**. No se ha desplegado contrato: no existen dirección ni transaction hash de deployment que acreditar. No se ha ejecutado el smoke de registro/CU25 en Sepolia.

Se revisaron faucets seguros: [Google Cloud Sepolia](https://cloud.google.com/application/web3/faucet/ethereum/sepolia) requiere login humano; [Alchemy Sepolia](https://www.alchemy.com/faucets/ethereum-sepolia) exige saldo/actividad mainnet que esta wallet dedicada nueva no posee. No se compraron fondos, usaron wallets personales ni intentaron eludir requisitos. Dependencia externa pendiente: suministrar testETH a la dirección pública mediante un faucet legítimo, desplegar y ejecutar smoke. La excepción de cierre §62/157 autoriza completar y validar offline conservando este límite. Referencia de red: [Ethereum networks](https://ethereum.org/developers/docs/networks/).

## Trazabilidad de aceptación T-B01–T-B40

Los nombres siguientes corresponden a archivos de `tests/Feature/`, salvo indicación. La cobertura offline no acredita T-B40.

| Criterios | Evidencia ejecutable |
| --- | --- |
| T-B01–05, T-B12, T-B14 | TraceabilityCanonicalTest, TraceabilityReservationTest; `blockchain/test-runtime/jcs.test.mjs`: cuatro golden, RFC/binary64, Unicode, duplicados, IDs, UTC, textos |
| T-B06–09 | TraceabilityReservationTest / TraceabilityLoanTest: operaciones reales, rollback, recepción/revisión, QR compartido |
| T-B10–11 | TraceabilityExclusionsTest / TraceabilityLoanTest; regresión Reservation/Equipment/QR/IoT/Release |
| T-B13, T-B15–16 | Hardhat `TraceabilityRegistry.ts` e integración local con captura de calldata |
| T-B17–22 | TraceabilityVerificationTest e integración local: chain real incorrecta, code/owner/ref/hash, confirmaciones, incertidumbre |
| T-B23–27 | TraceabilityReservationTest / TraceabilityVerificationTest; integración local recupera logs y pérdida de respuesta tras aceptar broadcast |
| T-B28 | TraceabilityConcurrencyTest: procesos PHP/MySQL distintos, barreras, UNIQUE, mutex de wallet, colisión registro/CU25 y liberación de locks |
| T-B29–34 | TraceabilityReservationTest / TraceabilityLoanTest / TraceabilityExclusionsTest / TraceabilityVerificationTest |
| T-B35–36 | TraceabilityHttpTest / TraceabilityVerificationTest / TraceabilityConcurrencyTest; QA interactiva |
| T-B37 | TraceabilityConcurrencyTest: generación local <=3 s; segunda conexión toma NOWAIT filas de negocio mientras RPC está retenido |
| T-B38 | TraceabilitySchemaTest y regresión SchemaContractTest, rollback/reaplicación incremental |
| T-B39 | phpunit.xml deshabilita red real; TestingDatabaseGuard; proceso JCS aislado; revisión de secretos y auditorías |
| T-B40 | Pendiente exclusivamente de testETH/deployment/smoke real; no sustituido por mocks |

## Resultados de cierre

| Verificación ejecutada | Resultado |
| --- | --- |
| Laravel completo, MySQL testing | **570 pruebas / 7.747 aserciones / 582,20 s**, cero fallos, errores u omitidas; se conservan las 537 pruebas anteriores y se añaden 33 en ocho archivos |
| Pint completo `php vendor/bin/pint --test` | Aprobado |
| Vite / Vue | Build aprobado; última compilación 382 ms; comprobación visual posterior de fechas en Bolivia |
| Hardhat compile / export:abi | Aprobados; compilador local solc 0.8.38, sin descarga durante tests |
| Hardhat tests | **1 test contractual compuesto aprobado**: owner, ajeno, cero, timestamp, evento, consultas, duplicado y nonpayable |
| Runtime JCS Node | **3 tests aprobados**, incluyendo 23 vectores RFC8785 binary64 y validación estricta |
| Integración local PHP → ethers → EVM | Aprobada para las cuatro operaciones, confirmación y CU25; recuperación sin ref, pérdida de respuesta después de aceptar envío, no duplicados, confirmaciones, calldata privada, chain/contrato/writer/config incorrectos; inyección explícita de faults receipt/reorg |
| Composer audit | 0 advisories, 0 paquetes abandonados |
| npm audit frontend principal | **0 vulnerabilidades** |
| npm audit Blockchain completo | **5 altas, 0 críticas**: excepción temporal dev de Zod; NO se reporta como cero |
| npm audit Blockchain --omit=dev | **0 vulnerabilidades** |
| Dev-only / versiones | package-lock confirma Hardhat/Zod/plugins dev-only; ausentes del lock raíz; dependencias directas exactas |
| Última revisión de compatibilidad | 26/10/2026 UTC: Hardhat latest 3.21.0 exige Zod ^3.23.8; hardhat-zod-utils latest 3.0.8, Zod 3 latest 3.25.76; Zod latest 4.6.7. Sigue sin combinación compatible corregida |
| Latencia local observada | Operación completa + evidencia: **0,2576 s disabled**, **0,2303 s fake**; cero llamadas RPC antes del retorno. Además, aserciones <=3 s y NOWAIT en procesos concurrentes |
| Desarrollo | Migraciones incrementales y SecuritySeeder aplicados, 30/203/61/46 comprobados; scheduler existente activo con CU23 y reconciliación cada minuto |
| Secret scan | 445 archivos públicos, diff, historial y logs revisados sin hallazgos; .env/.env.testing ignorados, placeholders sin secretos; capturas y logs de QA locales e ignorados |
| Originales | PDF SHA-256 `5848a6f0c5a6549d8f84ae18ae9cd886859c39d053046109dc6476a1f22762e5`; Word `318d9b5e6021041aa9f1c4a93cd8dd697553de19e87c6dd8919cb38164db5a0e`, sin cambios |
| Sepolia real | RPC/chain/saldo consultados; saldo 0,0 testETH; deployment, address, tx y smoke pendientes, no simulados como reales |

Los ajustes a tests anteriores actualizan cantidades de permisos/tablas/FK, planificación de dos comandos y rollback de las dos nuevas migraciones antes de comprobar Fase 8. Se conserva la prueba de constraints MySQL usando SQL directo cuando la nueva guarda del modelo impide llegar al motor; no se eliminan pruebas de comportamiento. La corrección visual posterior a la suite afecta solo el formatter de fechas y la explicación del resultado pendiente; build y navegador revalidados.

### QA interactiva y operativa

Instancia temporal `127.0.0.1:8089`, base **espacio_u_testing**, cuatro cuentas sintéticas, credencial aleatoria únicamente en `.env` local, sin modificar credenciales de desarrollo. Driver fake solo inyectado por el arnés ignorado de QA, nunca por rutas/configuración productiva. Se cerró exclusivamente ese proceso al terminar, se retiró la credencial y se reinicializó testing. Servidor de desarrollo 8000, MySQL aislado 3308 y scheduler quedaron activos.

| Paso §149 | Resultado observado |
| --- | --- |
| 1–4 Login Admin, aprobación, actividad, evidencia | Login real por formulario; reserva 1 aprobada; actividad EXITO/humana y evidencia local 1 PENDIENTE visibles |
| 5 Cancelación | Reserva 2 pendiente cancelada con motivo sintético; segunda actividad/evidencia |
| 6–7 Entrega/devolución | Préstamo 1 entregado y devuelto ADECUADA con confirmación real de UI; cuatro actividades exactas y cuatro evidencias |
| 8 Exclusiones | Préstamo aprobado inicialmente sin actividad; reserva 3 rechazada por UI sin evidencia; SQL de control confirma cero actividades de esa reserva; QR/IoT/CU23 cubiertos además por suite |
| 9–11 Pendiente/fake success/CU25 | PENDIENTE con integración deshabilitada; retry fake registra misma identidad; CU25 muestra VALIDO |
| 12 Inconsistencia | Alteración controlada del motivo persistido en testing; CU25 INCONSISTENTE; luego historia restaurada. Hash original no sobrescrito |
| 13 Indisponibilidad | Driver fake indisponible; REGISTRADA conserva estado/ref/fecha y CU25 muestra NO_VERIFICABLE |
| 14 Retry | Mismo servicio/ID/hash; botón desaparece al quedar REGISTRADA; ningún nuevo registro lógico local |
| 15 Reconciliador | Se retira solo la evidencia sintética de actividad 4; comando crea exactamente una faltante y vuelve a cuatro evidencias |
| 16 Permisos | Retiro de ver al Admin produce 403; se restaura explícitamente solo el permiso de la cuenta de QA. Seeder idempotente no restituye por sí mismo permisos retirados |
| 17–19 Roles | Login real independiente de Docente, Encargado de Laboratorio y Estudiante; los tres reciben 403 en /trazabilidad |
| 20 Responsive | Viewports reales 320, 375, 768 y 1280 px; detalle sin overflow horizontal (anchos útiles/scroll 305, 360, 753 y 1265); listado móvil con tabla desplazable y filtros operables |
| 21 Teclado | Tab desde Verificar alcanza enlace de explorador con foco visible; controles semánticos, labels, skip link y región de tabla desplazable |
| 22 Consola | Cero warnings/errores registrados de la aplicación en el navegador de QA |
| 23 Logs | Escaneo de logs de aplicación/QA sin claves, tokens, passwords ni environment; mensajes técnicos saneados |
| Filtros adicionales | Seleccionar Reserva cancelada produce exactamente una fila, conserva paginación y muestra fechas Bolivia |
| 24–29 Sepolia | Pendientes por testETH; ninguna captura fake se utiliza para acreditarlos |

QA descubrió y corrigió el uso indebido del formatter DATETIME sobre ISO UTC serializado por Eloquent. El módulo usa ahora Intl con `America/La_Paz`; cruce UTC al día siguiente correctamente presentado en fecha local. El texto PENDIENTE se refiere expresamente a la última comprobación para no contradecir una confirmación posterior. La captura local de evidencia usa **datos y driver simulados**; no constituye prueba Sepolia.

## Índice del informe solicitado en §158

| Nº | Entrega / resultado |
| --- | --- |
| 1 | RF19/CU24 y RF20/CU25 implementados y validados offline |
| 2 | registros_actividad y evidencias_trazabilidad |
| 3 | Migraciones 000029 y 000030 incrementales |
| 4 | fk_registro_actor ON UPDATE RESTRICT autorizado; CHECK conservado |
| 5 | Dos modelos y cuatro enums; detalle en esquema |
| 6 | Las cuatro acciones exactas de D-12 |
| 7 | Rechazos, préstamo aprobado, QR/IoT/incidencias y CU23/LIBERADA excluidos |
| 8 | Aprobación atómica, actor resolución y contexto correctos |
| 9 | Cancelación atómica desde pendiente/aprobada |
| 10 | Entrega atómica, actor/receptor e hito conservados |
| 11 | Devolución solo al confirmar; actor confirmado_por real |
| 12 | JCS RFC8785 y versión espacio-u-jcs-v1 |
| 13 | canonicalize 5.1.0 vía Node local, excepción aprobada |
| 14 | Golden cuatro acciones + RFC/UTF-16/binary64/duplicados |
| 15 | SHA-256 hex lower64 mediante servicio separado |
| 16 | Aprobación: subject cerrado de siete claves |
| 17 | Cancelación: seis claves, motivo como hash |
| 18 | Entrega: nueve claves, condición/observación como hash |
| 19 | Devolución: once claves e historia de recepción/confirmación |
| 20 | Sin PII ni canónico persistido; externo bytes32 únicamente |
| 21 | LocalEvidenceService idempotente y fuente durable de actividad |
| 22 | PENDIENTE/REGISTRADA; cuatro resultados de verificación |
| 23 | Registry no upgradeable, Ownable, nonpayable, mapping/evento |
| 24 | Solidity 0.8.38 |
| 25 | OpenZeppelin 5.6.1 |
| 26 | Hardhat 3.21.0 |
| 27 | ethers 6.17.0, bridge local controlado |
| 28 | Test contractual compuesto aprobado |
| 29 | Ethereum Sepolia, nunca Mainnet |
| 30 | Chain ID 11155111, configuración y RPC real verificados |
| 31 | Wallet pública dedicada indicada en Estado de Sepolia real |
| 32 | Saldo 0,0 testETH; dependencia de faucet humano |
| 33 | Deployment Sepolia no realizado por falta de testETH |
| 34 | Contract address real todavía inexistente |
| 35 | Tx deployment real todavía inexistente |
| 36 | BlockchainDriver + EthereumSepoliaDriver |
| 37 | FakeBlockchainDriver exclusivamente tests |
| 38 | Guard config/eth_chainId, contrato y writer |
| 39 | Envío post-commit, consulta previa y confirmación posterior |
| 40 | N configurable >=1, inicial 1 |
| 41 | Ref eip155:11155111/contract/tx, 125 caracteres |
| 42 | RPC 8 s / proceso 15 s; timeout incierto conserva candidato |
| 43 | Mapping/logs/receipt recuperan envío o referencia perdidos |
| 44 | trazabilidad:reconciliar con lote y cursor rotatorio |
| 45 | Cada minuto, scheduler existente activo |
| 46 | UNIQUE + locks + contrato idempotente |
| 47 | Mutex MySQL por wallet para nonce/firma/broadcast |
| 48 | CU24 completo dentro del alcance de cuatro operaciones |
| 49 | CU25 completo con cuatro resultados persistidos |
| 50 | Reconstrucción histórica y hash local antes de RPC |
| 51 | Red/bytecode/receipt/evento/mapping/confirmaciones coherentes |
| 52 | Tres permisos exactos solo Administrador, sin bypass |
| 53 | Listado, filtros, detalle, verificar/reintentar y explorador |
| 54 | 33 pruebas Laravel nuevas, ocho archivos |
| 55 | 570 pruebas Laravel totales |
| 56 | 7.747 aserciones |
| 57 | 582,20 segundos |
| 58 | 1 test Hardhat compuesto aprobado, separado de Laravel |
| 59 | Integración PHP/ethers/EVM de cuatro acciones y fallos aprobada |
| 60 | Sepolia separada pendiente, sin fingir evidencia |
| 61 | Pint completo aprobado |
| 62 | Build Vue aprobado |
| 63 | Composer audit cero |
| 64 | npm audit web cero |
| 65 | Blockchain dev: cinco altas bajo excepción; --omit=dev cero |
| 66 | QA interactiva/operativa de 23 pasos descrita arriba |
| 67 | Cuatro viewports, foco teclado, filtros y consola comprobados |
| 68 | Este informe, README y contratos/matrices actualizados |
| 69 | RF19 → CU24 y RF20 → CU25 → actividad/hash/driver/contrato/tests |
| 70 | feature/phase-9-blockchain |
| 71 | Commits funcionales 29b6ce3, b9e3a0d y 1cdfa2b; cierre documental identificado por su mensaje en el registro Git siguiente |
| 72 | Publicación de feature/phase-9-blockchain en origin |
| 73 | Integración de feature en main con --ff-only y push sin force |
| 74 | Control final exigido y comprobado en la entrega: main == origin/main y árbol limpio |
| 75 | Escaneo de archivos/diff/historial/logs sin secretos; originales intactos |
| 76 | CU30 general no implementado |
| 77 | Notificaciones no implementadas; tabla ausente |
| 78 | Fase 10 no iniciada |
| 79 | Cerrar contrato RF21/CU26 de avisos internos, destinatarios, vínculo a actividad, idempotencia post-commit, lectura propia y recuperación antes de programar Fase 10 |

## Registro Git de entrega

Secuencia coherente desde `a773199`: `29b6ce3 feat: implement critical traceability and Sepolia registry`; `b9e3a0d feat: add authorized traceability verification interface`; `1cdfa2b test: cover traceability privacy recovery and concurrency`; cierre `docs: record phase 9 validation and development-only exception`. La rama de entrega es `feature/phase-9-blockchain`; se publica antes de integrar por fast-forward en main, sin reescritura de historial. El commit documental final puede consultarse con `git log -1`; no se incluye su propio hash dentro de este archivo para evitar una autorreferencia imposible. El informe de entrega comunica el resultado efectivo del push y la comparación final de referencias.

## Coexistencia documental con RF21 — Fase 9.5

[Notification Contract / Readiness for Phase 10](DECISIONS.md#notification-contract--readiness-for-phase-10) reutiliza exactamente RESERVA_APROBADA, RESERVA_CANCELADA, PRESTAMO_ENTREGADO y PRESTAMO_DEVUELTO, sin segunda actividad, cambio de criticidad, canonicalización o evidencia. Una actividad confirmada puede tener una evidencia y varios avisos internos. Ambos consumidores actúan después del commit y se aíslan: un fallo en evidencia/RPC no impide intentar avisos y un fallo de avisos no impide evidencia. La operación confirmada conserva su validez.

Las trece acciones RF21 nuevas serán no críticas y no amplían el allowlist D-12. No generar avisos por evidencia PENDIENTE/REGISTRADA, verificaciones, RPC caído, QR o IoT aislados. registros_actividad mantiene su esquema e inmutabilidad; ampliarlo solo para los eventos RF21 no implementa CU30 general. El reconciliador de avisos será independiente del de trazabilidad y tendrá la ventana/límites N6; no se incorpora ahora.

Esta fase es documental: no ejecuta nuevos tests Blockchain ni auditorías, no modifica dependencias, contratos o configuración. La excepción temporal dev Hardhat/Zod conserva las condiciones y deuda técnica de este informe; auditorías productivas sin vulnerabilidades corresponden al cierre de Fase 9, no a una nueva medición. El smoke real de Sepolia sigue pendiente exclusivamente de testETH y separado de RF21; no se intenta financiar ni desplegar en 9.5. Fase 10 puede comenzar con nueva instrucción según Readiness, sin que ese pendiente externo la bloquee.

## Frontera documental de reportes — Fase 10.5

[DR7/DR10](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11) conserva las cuatro acciones críticas, builders y evidencia. REPORTE_EXPORTADO futuro tendrá critica=FALSE; no invoca Blockchain, no requiere Sepolia/RPC/fondos y no genera avisos. Resumen de actividad no exporta canónicos, hashes de contenido sensible, payloads ni detalle de evidencia; no implementa CU30. No se añade estado Blockchain como tarjeta general.

No se modifica blockchain/, no se instala ni audita de nuevo el toolchain en esta fase documental. La excepción temporal conocida de cinco entradas altas exclusivamente dev de Hardhat/Zod y Sepolia pendiente de testETH conservan el alcance de sus apartados anteriores; no se afirman auditorías nuevas ni cero vulnerabilidades de desarrollo. Las futuras bibliotecas de PDF/XLSX se evalúan por separado; esta excepción no cubre su runtime.

## Integración con Fase 11

[Dashboard y reportes](DASHBOARD_REPORTING_IMPLEMENTATION.md) reutilizan las reglas, consultas y scopes de este dominio. No modifican sus estados, decisiones o productores. REPORTE_EXPORTADO no se añade a la whitelist de notificaciones ni a eventos críticos Blockchain. CU30 sigue NO IMPLEMENTADO; las limitaciones históricas de este informe conservan su alcance.


## Límite de asistencia previsto — Fase 11.5

Solo explicación funcional permitida, sin evidencia/canónico/hash/dirección/clave concreta enviada al chatbot ni llamadas de verificación/reintento. Smoke Sepolia pendiente y excepción dev Hardhat/Zod se conservan sin nueva auditoría o prueba externa. [Contrato CH1–CH12](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12). Cambio exclusivamente documental; Fase 12 NO iniciada.

## Continuidad documental de RF25/CU30 — Fase 12.5

El [contrato AC1–AC15](DECISIONS.md#activity-audit-contract--readiness-for-cu30) mantiene exclusivamente cuatro EXITO críticos D-12, sin cambios de hash/canónico/contrato/builders. Centralizar CriticalActivityRecorder no duplica actividad/evidencia; procesamiento externo posterior al commit sigue independiente. Verificar/reintentar/reconciliar no añade acción de bitácora. CU30 futuro solo enlaza evidencia existente con permiso, no verifica cadena al leer. Excepción dev Hardhat/Zod y smoke Sepolia pendiente independientes; sin nuevas auditorías ni modificación del módulo en Fase 12.5.

Cambio exclusivamente documental. CU30 **NO IMPLEMENTADO**; implementación requiere nueva instrucción. La evidencia funcional anterior conserva su fecha y no se presenta como verificación nueva.


## Integración de auditoría — Fase 12.6

CriticalActivityRecorder delega la inserción única al ActivityRecorder y conserva las cuatro acciones D-12, canonicalización, contexto y callbacks posteriores al commit. Las 38 acciones administrativas siguen no críticas. CU30 solo enlaza evidencia existente con trazabilidad.evidencias.ver: consultar listado/detalle no ejecuta CU25 ni RPC. Retry/reconcile/verify no producen nuevas actividades. Auditoría actual del módulo: cinco vulnerabilidades altas dev Hardhat/Zod bajo la excepción temporal documentada; runtime --omit=dev cero. Smoke Sepolia continúa pendiente; no se cambian dependencias ni se amplía D-12.

[Implementación RF25/CU30, pruebas y resultados de integración](ACTIVITY_AUDIT_IMPLEMENTATION.md). Los cierres de fases anteriores conservan su alcance histórico. Fase 13 no iniciada.

## Revalidación offline y dependencia dev — Fase 13

2026-10-26: contrato 1 test y runtime 3 tests aprobados; integración PHP→ethers→contrato local repetida y aprobada después de corregir el harness antiguo que omitía PRESTAMO_APROBADO no crítica. Se comprueban cinco actividades y solo los cuatro hitos críticos enviados a cadena. Cuatro operaciones, recovery, duplicados, receipt/reorg, referencias y privacidad conservados.

Auditoría final: frontend 0, Composer 0/abandonados 0, blockchain --omit=dev 0; blockchain completo **5 altas dev**. Consulta final npm: Hardhat latest 3.21.1 todavía requiere zod ^3.23.8 y hardhat-zod-utils ^3.0.5; no existe combinación compatible corregida según advisory/audit. Instalación conservada Hardhat 3.21.0 / ethers plugin 4.2.1 / node-test-runner 3.1.2 / utils 3.0.8 / Zod 3.25.76; lockfiles sin cambios. La deuda D-12-DEV-ZOD y sus controles siguen vigentes, sin ampliar excepción. RPC Sepolia consultado, saldo dedicado 0 testETH; no transacción ni dinero real. [QA integral](PHASE_13_SYSTEM_VALIDATION.md).
