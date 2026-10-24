# ESPACIO-U

Sistema de información para la gestión y control de reservas de espacios académicos y préstamos de equipamiento tecnológico en la FICCT, con monitoreo de ocupación mediante IoT y trazabilidad de operaciones mediante Blockchain.

**Estado: Fase 7 implementada — sensores IoT, recepción REST/HMAC, monitoreo e información complementaria en CU06.** Se conserva seguridad, espacios, reservas, equipamiento/préstamos/incidencias y QR. [Informe de Fase 7, pruebas, seguridad y trazabilidad](docs/IOT_IMPLEMENTATION.md). IoT no modifica disponibilidad base ni libera reservas. CU23/Fase 8, Blockchain, notificaciones y CU30 siguen diferidos. La aceptación física de QR conserva el límite descrito en su informe.

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

La suite usa exclusivamente `espacio_u_testing` en MySQL real, con cuenta separada y guarda previa a migraciones/limpieza. La suite de Fase 7 conserva los 325 casos previos y añade 46 IoT, incluidos 13 escenarios con procesos/conexiones concurrentes: 371 pruebas / 5862 aserciones. El informe IoT registra duración y evidencia final. Los 63 casos QR de Fase 6 se conservan. La aceptación física confirmada por el usuario se distingue de las lecturas sintéticas del navegador en QR_IMPLEMENTATION.md. No certifica CU30, módulos posteriores, carga ni despliegue.

## Documentación

| Documento | Propósito |
| --- | --- |
| [Constitución](docs/PROJECT_CONSTITUTION.md) | Alcance, stack, reglas permanentes y organización |
| [Arquitectura](docs/ARCHITECTURE.md) | Capas, paquetes, integraciones y despliegue previsto |
| [Dominio](docs/DOMAIN_MODEL.md) | Conceptos, actores y estados exactos |
| [Datos](docs/DATA_MODEL.md) | 31 tablas, 211 columnas, 63 FK, cardinalidades y restricciones |
| [Reglas de negocio](docs/BUSINESS_RULES.md) | Invariantes y comprobaciones futuras |
| [Casos de uso](docs/USE_CASE_MATRIX.md) | Fichas completas CU01–CU30 y pruebas necesarias |
| [Trazabilidad](docs/REQUIREMENTS_TRACEABILITY.md) | Los 217 requisitos, incluidos 26 RF, con criterios y fases |
| [Sensores IoT y monitoreo](docs/IOT_IMPLEMENTATION.md) | CU21/CU22, REST/HMAC, provisionamiento, historia, frescura, concurrencia y CU06 informativo |
| [Códigos QR y validaciones](docs/QR_IMPLEMENTATION.md) | Token/hash, esquema, CU11/CU20, cámara, concurrencia, pruebas y límite de corroboración física |
| [Equipamiento y préstamos](docs/EQUIPMENT_LOANS_IMPLEMENTATION.md) | Esquema, permisos, transiciones, concurrencia y evidencia de Fase 5 |
| [Reservas](docs/RESERVATIONS_IMPLEMENTATION.md) | Ciclo y evidencia de Fase 4 |
| [Espacios y disponibilidad](docs/SPACES_IMPLEMENTATION.md) | Esquema, RBAC, disponibilidad parcial y evidencia de Fase 3 |
| [Implementación de seguridad](docs/SECURITY_IMPLEMENTATION.md) | Catálogo, matriz, sesiones, invariantes y evidencia de Fase 2 |
| [Seguridad](docs/SECURITY_POLICY.md) | Secretos, datos, controles y revisión antes de cada push |
| [Plan](docs/IMPLEMENTATION_PLAN.md) | Inventario real del entorno y Fases 1–14 |
| [Entorno de desarrollo](docs/DEVELOPMENT_SETUP.md) | Versiones, instalación, MySQL aislado, ejecución y verificaciones |
| [Decisiones](docs/DECISIONS.md) | 17 decisiones clasificadas, contratos congelados y detalles diferidos |

## Estructura actual

```text
ESPACIO-U/
├── app/                 # Modelos, servicios, solicitudes validadas, Gates y middleware
├── bootstrap/           # Arranque Laravel
├── config/              # Configuración sin secretos
├── database/            # Veinticinco migraciones, catálogo RBAC y factories sintéticas
├── docs/                # Contratos y procedimiento de desarrollo
├── public/              # Punto de entrada; build generado e ignorado
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   ├── js/Pages/{Auth,Users,Roles,Spaces,Reservations,Equipment,Loans,Incidents,Qr}/
│   └── views/app.blade.php
├── routes/              # Seguridad, espacios, reservas, equipamiento, préstamos, incidencias, QR y /up
├── scripts/             # Entorno de terminal y ciclo de MySQL local
├── storage/             # Datos generados privados
├── tests/Feature/
├── artisan
├── composer.json / composer.lock
├── package.json / package-lock.json
└── vite.config.js
```

`.tools/`, `.local/`, `.env`, `vendor/`, `node_modules/`, logs y compilados permanecen locales e ignorados. `.env.example` contiene secretos vacíos. MySQL de desarrollo usa `espacio_u` en `127.0.0.1:3308`; 23 tablas académicas están materializadas (21 previas y dos QR); las otras ocho siguen pendientes. Las siete excepciones QR ON UPDATE RESTRICT fueron autorizadas y están documentadas; CHECK y demás SQL permanecen preservados. `espacio_u_testing` tiene credenciales exclusivas en `.env.testing` local.

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
