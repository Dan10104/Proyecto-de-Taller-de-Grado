# ESPACIO-U

**Fase 11.5 documental cerrada.** [Chatbot Assistance Contract / Readiness for Phase 12](docs/DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12): **0 bloqueantes documentales**. OpenAI Responses/Luna configurable, orientación por permisos y sin registros operativos ni historial persistido. **Fase 12 NO iniciada; CU30 NO IMPLEMENTADO.**

**Fase 11 completada: dashboard y reportes.** 747 tests / 9.111 assertions. [Informe de implementación](docs/DASHBOARD_REPORTING_IMPLEMENTATION.md). Navegación: Dashboard para cuatro roles; Reportes para Admin/Encargado según permisos de dominio. PDF/XLSX locales. CU30 NO IMPLEMENTADO; Fase 12 NO iniciada.

Sistema de información para la gestión y control de reservas de espacios académicos y préstamos de equipamiento tecnológico en la FICCT, con monitoreo de ocupación mediante IoT y trazabilidad de operaciones mediante Blockchain.

**Estado histórico: Fase 10.5 documental cerrada.** [Contrato de dashboard/reportes y Readiness for Phase 11](docs/DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11): **0 bloqueantes documentales**, 10 tarjetas/6 gráficos administrativos, 7 tarjetas personales y 7 reportes PDF/XLSX definidos. Fase 11 NO iniciada; CU30 NO IMPLEMENTADO. Solo se actualizó documentación.

**Base funcional conservada: Fase 10 implementada y verificada — RF21/CU26.** Diecisiete reglas, destinatarios por permisos efectivos, centro propio de avisos, primera lectura, contador y recuperación de 24 h. [Informe, pruebas y límites](docs/NOTIFICATIONS_IMPLEMENTATION.md). CU30 general y Fase 11 no iniciados.

Blockchain conserva su validación offline de Fase 9; Sepolia real sigue pendiente de testETH. Auditorías productivas sin vulnerabilidades; excepción temporal autorizada para cinco entradas altas de Zod exclusivamente dev de Hardhat, sin modificar ese módulo en Fase 10. [Evidencia y controles](docs/BLOCKCHAIN_IMPLEMENTATION.md).

Se mantienen CU23, QR/IoT y scheduler. Eventos de reserva, préstamo, devolución, vencimiento, incidencia/mantenimiento y ALERTAR/LIBERAR generan avisos después del commit. La aceptación física QR conserva el límite de su informe.

Fase 7.5 cerró D-08 con cero bloqueantes documentales; la instrucción oficial posterior autorizó la implementación actual. [Contrato congelado](docs/DECISIONS.md#readiness-for-phase-8) y [mapa de los 36 criterios ejecutados](docs/RELEASE_POLICY_IMPLEMENTATION.md#verificación-y-trazabilidad-de-aceptación).

## Fuente de verdad

El informe final Word/PDF y las decisiones congeladas de Fase 0.5 gobiernan el diseño. Las fuentes, auditoría y decisiones están en [DECISIONS.md](docs/DECISIONS.md). Los originales y sus datos personales no se publican. Los detalles diferidos se resolverán en su fase correspondiente.

Repositorio oficial: [Dan10104/Proyecto-de-Taller-de-Grado](https://github.com/Dan10104/Proyecto-de-Taller-de-Grado). Workspace local único: `C:\Users\BlackMaster69\Desktop\ESPACIO-U`, abierto como raíz en Visual Studio Code. Git conserva las versiones; no crear copias/ZIP del proyecto.

## Ejecutar en el equipo preparado

En una terminal PowerShell 7 de VS Code, desde la raíz oficial:

```powershell
. .\scripts\dev-environment.ps1
.\scripts\mysql-dev.ps1 -Action Start
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Abrir http://127.0.0.1:8000. Se muestra el login institucional. El administrador local ya fue creado; sus credenciales permanecen únicamente en `.env` privado. No ejecutar de nuevo el bootstrap ni publicar ese archivo. Para editar con Vite, activar el mismo entorno en una segunda terminal y ejecutar `npm run dev`; Laravel sigue ejecutándose en la primera. Detener los servidores con Ctrl+C y MySQL con `.\scripts\mysql-dev.ps1 -Action Stop`.

Para preparar un entorno sin dependencias instaladas, consultar primero [DEVELOPMENT_SETUP.md](docs/DEVELOPMENT_SETUP.md). Allí se explican `composer install`, `npm ci`, `.env`, herramientas locales y creación segura de MySQL. En desarrollo se usan migraciones incrementales; la limpieza destructiva se permite exclusivamente en testing protegido.

## Comprobar seguridad, espacios y disponibilidad

Con MySQL iniciado y los assets compilados:

```powershell
php artisan test
composer audit
npm audit
```

La suite usa exclusivamente `espacio_u_testing` en MySQL real, con cuenta separada y guarda previa a migraciones/limpieza. La suite final conserva las 570 pruebas previas y añade 86 casos: 656 pruebas / 8721 aserciones en 788,96 s. El informe de notificaciones registra evidencia final, incluidas carreras con procesos/conexiones MySQL independientes. Los 63 casos QR de Fase 6 se conservan. La aceptación física confirmada por el usuario se distingue de las lecturas sintéticas del navegador en QR_IMPLEMENTATION.md. No certifica CU30, módulos posteriores, carga ni despliegue.

## Documentación

| Documento | Propósito |
| --- | --- |
| [Contrato de dashboard y reportes](docs/DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11) | Fase 10.5: fórmulas, roles, siete reportes, formatos, privacidad, auditoría y preparación de Fase 11 |
| [Notificaciones internas](docs/NOTIFICATIONS_IMPLEMENTATION.md) | RF21/CU26, 17 reglas, destinatarios, lectura, recuperación y concurrencia |
| [Constitución](docs/PROJECT_CONSTITUTION.md) | Alcance, stack, reglas permanentes y organización |
| [Arquitectura](docs/ARCHITECTURE.md) | Capas, paquetes, integraciones y despliegue previsto |
| [Dominio](docs/DOMAIN_MODEL.md) | Conceptos, actores y estados exactos |
| [Datos](docs/DATA_MODEL.md) | 31 tablas, 211 columnas, 63 FK, cardinalidades y restricciones |
| [Reglas de negocio](docs/BUSINESS_RULES.md) | Invariantes y comprobaciones futuras |
| [Casos de uso](docs/USE_CASE_MATRIX.md) | Fichas completas CU01–CU30 y pruebas necesarias |
| [Trazabilidad](docs/REQUIREMENTS_TRACEABILITY.md) | Los 217 requisitos, incluidos 26 RF, con criterios y fases |
| [Políticas de liberación](docs/RELEASE_POLICY_IMPLEMENTATION.md) | RF18/CU23, versiones, matriz, evidencia M:N, scheduler, idempotencia, concurrencia y pruebas |
| [Sensores IoT y monitoreo](docs/IOT_IMPLEMENTATION.md) | CU21/CU22, REST/HMAC, provisionamiento, historia, frescura, concurrencia y CU06 informativo |
| [Códigos QR y validaciones](docs/QR_IMPLEMENTATION.md) | Token/hash, esquema, CU11/CU20, cámara, concurrencia, pruebas y límite de corroboración física |
| [Equipamiento y préstamos](docs/EQUIPMENT_LOANS_IMPLEMENTATION.md) | Esquema, permisos, transiciones, concurrencia y evidencia de Fase 5 |
| [Reservas](docs/RESERVATIONS_IMPLEMENTATION.md) | Ciclo y evidencia de Fase 4 |
| [Espacios y disponibilidad](docs/SPACES_IMPLEMENTATION.md) | Esquema, RBAC, disponibilidad parcial y evidencia de Fase 3 |
| [Implementación de seguridad](docs/SECURITY_IMPLEMENTATION.md) | Catálogo, matriz, sesiones, invariantes y evidencia de Fase 2 |
| [Seguridad](docs/SECURITY_POLICY.md) | Secretos, datos, controles y revisión antes de cada push |
| [Plan](docs/IMPLEMENTATION_PLAN.md) | Inventario real del entorno y Fases 1–14 |
| [Entorno de desarrollo](docs/DEVELOPMENT_SETUP.md) | Versiones, instalación, MySQL aislado, ejecución y verificaciones |
| [Decisiones](docs/DECISIONS.md) | 17 decisiones clasificadas y contrato RF21/CU26 cerrado sin inventar un nuevo D |

## Estructura actual

```text
ESPACIO-U/
├── app/                 # Modelos, servicios, solicitudes validadas, Gates y middleware
├── bootstrap/           # Arranque Laravel
├── config/              # Configuración sin secretos
├── database/            # Treinta y una migraciones, catálogo RBAC y factories sintéticas
├── docs/                # Contratos y procedimiento de desarrollo
├── public/              # Punto de entrada; build generado e ignorado
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   ├── js/Pages/{Auth,Users,Roles,Spaces,Reservations,Equipment,Loans,Incidents,Qr,Iot,Release,Traceability,Notifications}/
│   └── views/app.blade.php
├── routes/              # Seguridad, espacios, reservas, equipamiento, préstamos, incidencias, QR, IoT, liberación y /up
├── scripts/             # Entorno de terminal, MySQL local y scheduler
├── storage/             # Datos generados privados
├── tests/Feature/
├── artisan
├── composer.json / composer.lock
├── package.json / package-lock.json
└── vite.config.js
```

`.tools/`, `.local/`, `.env`, `vendor/`, `node_modules/`, logs y compilados permanecen locales e ignorados. `.env.example` contiene secretos vacíos. MySQL de desarrollo usa `espacio_u` en `127.0.0.1:3308`; 31 tablas académicas están materializadas (211 columnas, 63 FK), incluida notificaciones. Las siete excepciones QR ON UPDATE RESTRICT fueron autorizadas y están documentadas; CHECK y demás SQL permanecen preservados. `espacio_u_testing` tiene credenciales exclusivas en `.env.testing` local.

## Continuidad

Fases 0 y 0.5 conservan su cierre documental y sus 17 expedientes. D-15 queda atendida para el entorno web de Fase 1; Docker sigue pendiente de su fase. D-16 se aplica a la configuración `America/La_Paz` y sesión MySQL `-04:00`, sin lógica de conversión externa anticipada.

D-04 y D-07 están implementadas para el núcleo web: RBAC sin bypass, historia de asignaciones y control de sesiones por hash. [Detalles y límites](docs/SECURITY_IMPLEMENTATION.md). El ámbito fino por recurso, móvil/API, almacenamiento distribuido y CU30 permanecen diferidos.

La Fase 4 aplica el contrato cerrado en Fase 3.5: solicitudes, consultas, resolución, cancelación y disponibilidad con APROBADA. D-05 permanece resuelta. [Evidencia de Fase 4](docs/RESERVATIONS_IMPLEMENTATION.md). Fase 4.5 atendió la revisión previa de Fase 5: D-02/D-11 cerradas, D-04/D-05 aplicadas sin reapertura. Quedan definidos receptor, entrega, devolución/revisión, incidencias, recuperación, permisos previstos y concurrencia. [Contrato y fuentes](docs/DECISIONS.md#contrato-de-equipamiento-y-préstamos--fase-45). Fase 5 implementó el núcleo CU12–CU19 bajo la nueva instrucción, sin alterar esas decisiones. D-09 quedó **RESUELTA PARA FASE 6** en el cierre documental de Fase 5.5. [Readiness for Phase 6: 0 bloqueantes documentales](docs/DECISIONS.md#readiness-for-phase-6). La nueva instrucción de Fase 6 autorizó su implementación; el estado de verificación vigente está en QR_IMPLEMENTATION.md. La integración final fue autorizada tras la aceptación manual expresa del usuario. Fase 6.5 completó ahora ese cierre documental; Fase 7 puede comenzar con nueva instrucción, sin iniciarla aquí.

## Cierre documental QR — Fase 5.5

[Contrato exacto D-09](docs/DECISIONS.md#contrato-qr-y-validaciones--cierre-d-09-de-fase-55): token opaco de 256 bits/base64url, solo hashes SHA-256 persistidos, objetivo único, un QR activo por objetivo y rotación con historia; CU11 contextual y CU20 como extensión explícita. Validación y correlación con entrega/devolución en la misma petición/transacción, sin autorización por token ni ventanas arbitrarias. Tres permisos previstos, cámara/decodificación local y [24 criterios de aceptación futura](docs/BUSINESS_RULES.md#aceptación-futura-de-qr--fase-6).

Históricamente, Fase 5.5 modificó solo documentación y no implementó QR. La Fase 6 posterior sí incorpora las dos tablas, modelos/enums, tres permisos, librerías locales, cámara, endpoints e interfaces descritos arriba. La suite de 262 pruebas / 3318 aserciones es evidencia anterior de Fase 5, no una ejecución QR ni una nueva ejecución de Fase 5.5.

## Uso QR

Administrador/Encargado autorizado: Códigos QR → elegir recurso/reserva/préstamo → generar y, si procede, imprimir en ese flujo. Al perder la representación debe rotarse; no existe recuperación del token desde su hash. El QR físico anterior deja de servir tras rotación/desactivación.

CU11 se abre desde una reserva propia/administrativa autorizada. Entrega/devolución permiten elegir «Validar mediante QR», confirmar primero datos físicos y luego escanear. Un inválido registra evidencia y detiene esa rama. Cámara local, sin frames subidos ni campo manual; HTTPS en producción o localhost en desarrollo. El arnés de tests no se publica con el build normal.

## Contrato IoT cerrado en Fase 6.5

Piloto previsto: POST /api/iot/events, HTTPS (HTTP solo localhost), HMAC-SHA-256 por sensor derivado de maestro privado, evento ambiental con sensor/espacio/tipo/valor nullable/fecha con zona. Maestro y claves todavía no generados. MQTT opcional futuro sobre el mismo servicio, sin broker obligatorio.

Reloj servidor; tolerancia de request/futuro y frescura de monitoreo con valores iniciales independientes de 300 s. Ambas fechas deben ser recientes; histórico tardío no implica ocupación actual. Duplicados NULL-safe bajo lock de sensor no insertan ni renuevan comunicación. Identificador inmutable, reasociación preserva historia y sensor deshabilitado rechaza eventos. Sin datos personales ni inferencias de identidad.

CU06 será solo informativo: ninguna lectura libera/cancela/aprueba reservas ni altera disponibilidad base. CU23 y su parámetro de política son de Fase 8. Tres permisos IoT A/E previstos; catálogo actual de 36 intacto. [Aceptación futura T-I01–T-I32](docs/BUSINESS_RULES.md#aceptación-iot-prevista-para-fase-7). Esta entrega cambia únicamente documentación; no ejecuta migraciones, pruebas funcionales, provisión o ingestión IoT.

## Ejecutar políticas de liberación

Con MySQL y el entorno local activos:

```powershell
php artisan reservas:evaluar-liberacion
.\scripts\release-scheduler.ps1 -Action start
.\scripts\release-scheduler.ps1 -Action status
```

El scheduler ejecuta CU23 cada minuto mientras su proceso permanece activo; reiniciarlo después de apagar el equipo. `-Action stop` detiene solo ese proceso. Frecuencia configurable mediante `RELEASE_SCHEDULE` y lote mediante `RELEASE_BATCH_SIZE`; ninguna equivale a tolerancia. No requiere login ni usuario Sistema. Administrador/Encargado con permisos gestionan versiones y asignaciones; Docente/Estudiante consultan sus evaluaciones. Las reglas de una versión no se editan. [Operación y límites](docs/RELEASE_POLICY_IMPLEMENTATION.md#comando-y-scheduler).

D-12 fue cerrado en Fase 8.5 e implementado en Fase 9. Siguiente alcance recomendado, sujeto a nueva instrucción: cerrar el contrato de notificaciones RF21/CU26 antes de Fase 10. No hay notificaciones funcionales, CU30 general ni finalización automática por tiempo.

## Trazabilidad Blockchain

```powershell
. .\scripts\dev-environment.ps1
npm ci --prefix blockchain --ignore-scripts
npm run compile --prefix blockchain
npm run export:abi --prefix blockchain
npm run test:runtime --prefix blockchain
npm test --prefix blockchain
npm run test:integration --prefix blockchain
php artisan trazabilidad:reconciliar
```

Ejecutar la integración local y `php artisan test` secuencialmente: ambas usan exclusivamente MySQL `espacio_u_testing`. Las migraciones de desarrollo son incrementales (`php artisan migrate` y `php artisan db:seed --class=SecuritySeeder`); nunca recrear `espacio_u`. El scheduler Laravel existente ejecuta también reconciliación cada minuto. Administrador autorizado accede a «Trazabilidad» para consultar, verificar y reintentar evidencia pendiente.

Configurar únicamente en `.env` local: `BLOCKCHAIN_ENABLED`, `BLOCKCHAIN_NETWORK`, `BLOCKCHAIN_CHAIN_ID`, `BLOCKCHAIN_RPC_URL`, `BLOCKCHAIN_PRIVATE_KEY`, `BLOCKCHAIN_CONTRACT_ADDRESS`, `BLOCKCHAIN_CONFIRMATIONS`, `BLOCKCHAIN_DEPLOYMENT_BLOCK`, `BLOCKCHAIN_NODE_BINARY`. `.env.example` incluye solo defaults seguros/placeholders. Con integración deshabilitada, las operaciones y evidencias locales funcionan sin red. Sepolia exige wallet dedicada y testETH: [procedimiento, scripts, privacidad y estado real](docs/BLOCKCHAIN_IMPLEMENTATION.md#configuración-y-operación-reproducible). Nunca usar fondos o wallet personal.

Producción del módulo: `npm ci --prefix blockchain --omit=dev --ignore-scripts`, Node compatible y ABI generado previamente; no instalar Hardhat/Zod en el runtime web. Auditar por separado raíz, módulo completo y módulo `--omit=dev`. La excepción temporal de Zod está delimitada en el informe y debe retirarse al existir una actualización compatible.

## Cierre histórico del contrato de notificaciones — Fase 9.5

Centro interno RF21/CU26 previsto, con ocho tipos oficiales y 17 acciones. Se reutilizan las cuatro actividades críticas de Fase 9; trece productores no críticos cubrirán solicitudes, decisiones, revisión pendiente, vencimiento, incidencias/mantenimiento y ALERTAR/LIBERADA. Solo cuentas activas y permisos efectivos; CU26 propio para los cuatro roles con notificaciones.ver_propias, todavía no creado.

Actividad y negocio en una transacción; avisos después del commit. Clave lógica actividad+destinatario+tipo, lock por actividad y lote atómico sin nueva UNIQUE. Recuperación automática de 24 h: primer lote persistido cierra el conjunto administrativo; los faltantes directos se reconstruyen y los límites sin snapshot quedan expresos en N6. Plantillas históricas planas, lectura idempotente y enlaces reautorizados. Sin canales externos, realtime complejo, avisos técnicos Blockchain ni CU30 general.

Los originales Word/PDF se conservaron y no se publican. La revisión de 9.5 es documental; no presenta T-N01–T-N40 como pruebas ejecutadas ni vuelve a acreditar auditorías de Fase 9. Smoke Sepolia real pendiente por testETH, independiente y sin gestiones de financiación/despliegue en esta fase.

## Notificaciones internas

Abrir **Notificaciones** o la campana. Cuatro roles consultan solo propios; 15 por página, filtros por lectura/tipo y contador. Abrir detalle registra primera lectura mediante POST protegido; recargar conserva fecha.

[Habilitación y scheduler](docs/DEVELOPMENT_SETUP.md#habilitar-notificaciones-internas--fase-10): fijar una vez NOTIFICATIONS_EVENTS_FROM local, migración incremental y SecuritySeeder, compilar y mantener scheduler. Recuperación cada minuto, ventana 24 h sin redistribución administrativa histórica. Sin canales externos ni realtime. Fase 11 requiere nueva instrucción.

## Continuación tras Fase 10.5

En el cierre histórico de Fase 10.5, Fase 11 quedó lista para una nueva instrucción; su implementación posterior está en [el informe Fase 11](docs/DASHBOARD_REPORTING_IMPLEMENTATION.md). [48 criterios de aceptación previstos](docs/BUSINESS_RULES.md#aceptación-prevista-para-fase-11) y [plan](docs/IMPLEMENTATION_PLAN.md#fase-11--dashboard-indicadores-y-reportes). La documentación no acredita implementación, nuevas pruebas funcionales ni nuevas auditorías: los resultados Fase 10 y las limitaciones de QR/Blockchain mantienen su alcance histórico. No hay cambio de código, esquema, dependencias ni permisos en este cierre; los Word/PDF fuente permanecen intactos y privados.

## Habilitar dashboard y reportes — Fase 11

Ejecutar composer install y npm ci con los runtimes oficiales; php artisan db:seed --class=SecuritySeeder --force incorpora los tres permisos nuevos sin restaurar permisos retirados. Compilar con npm run build y mantener scheduler para reportes:limpiar-temporales cada minuto. No hay migración nueva. Límites opcionales en .env local: 10.000 filas, 30 s, 64 MiB; temporales privados con máximo de una hora. [Procedimiento, dependencias y seguridad](docs/DASHBOARD_REPORTING_IMPLEMENTATION.md#verificación-y-operación).


## Continuación tras Fase 11.5

El contrato RF24/CU29 está definido en CH1–CH12; [50 criterios de aceptación futuros](docs/BUSINESS_RULES.md#aceptación-prevista-para-fase-12) y [plan Fase 12](docs/IMPLEMENTATION_PLAN.md#fase-12--chatbot). Fase 12 puede comenzar con una nueva instrucción. El asistente previsto solo orientará; no consultará registros ni ejecutará operaciones. La pregunta del usuario sí se enviará al proveedor, con snippets mínimos autorizados; store=false no implica retención cero.

Este cierre solo modifica documentación: no instalar SDK, configurar API keys, editar .env/.env.example, crear rutas/servicios/UI, permisos DB o migraciones. Modelo/SDK/política se revalidarán antes de implementar; smoke real solo con clave local válida y cuota, independiente de los tests fake. La baseline 747 tests/9.111 assertions pertenece a Fase 11 y no se reejecutó aquí. Los límites físicos QR, smoke Sepolia y excepción dev Hardhat/Zod mantienen su alcance documentado. Word/PDF fuente intactos y privados.
