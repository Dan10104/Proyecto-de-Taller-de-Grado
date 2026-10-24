# ESPACIO-U

Sistema de información para la gestión y control de reservas de espacios académicos y préstamos de equipamiento tecnológico en la FICCT, con monitoreo de ocupación mediante IoT y trazabilidad de operaciones mediante Blockchain.

**Estado: Fase 5 implementada y validada; Fase 5.5 documental cerrada.** Seguridad web, espacios, reservas y núcleo CU12–CU19 de equipamiento, préstamos, entrega, devolución/revisión e incidencias sobre Laravel, Vue 3, Inertia y MySQL. CU06 integra estado, bloqueos, reservas e incidencias. [Implementación, pruebas, trazabilidad y límites](docs/EQUIPMENT_LOANS_IMPLEMENTATION.md). QR, IoT, liberación, Blockchain, notificaciones y CU30 continúan diferidos; Fase 6 no iniciada.

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

La suite usa exclusivamente `espacio_u_testing` en MySQL real, con cuenta separada y guarda previa a migraciones/limpieza. La evidencia final de Fase 5 registra 262 pruebas / 3318 aserciones, 178,23 s, sin fallos, errores ni omitidas; Pint, build y auditorías Composer/npm correctos. Incluye 14 escenarios concurrentes nuevos de préstamos/incidencias y la regresión previa. No certifica CU30, módulos posteriores, carga ni despliegue.

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
├── database/            # Veintiuna migraciones, catálogo RBAC y factories sintéticas
├── docs/                # Contratos y procedimiento de desarrollo
├── public/              # Punto de entrada; build generado e ignorado
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   ├── js/Pages/{Auth,Users,Roles,Spaces,Reservations,Equipment,Loans,Incidents}/
│   └── views/app.blade.php
├── routes/              # Seguridad, espacios, reservas, equipamiento, préstamos, incidencias y /up
├── scripts/             # Entorno de terminal y ciclo de MySQL local
├── storage/             # Datos generados privados
├── tests/Feature/
├── artisan
├── composer.json / composer.lock
├── package.json / package-lock.json
└── vite.config.js
```

`.tools/`, `.local/`, `.env`, `vendor/`, `node_modules/`, logs y compilados permanecen locales e ignorados. `.env.example` contiene secretos vacíos. MySQL de desarrollo usa `espacio_u` en `127.0.0.1:3308`; 21 tablas académicas están materializadas (15 de fases previas y seis de equipamiento/préstamos/incidencias); las otras diez siguen pendientes. `espacio_u_testing` tiene credenciales exclusivas en `.env.testing` local.

## Continuidad

Fases 0 y 0.5 conservan su cierre documental y sus 17 expedientes. D-15 queda atendida para el entorno web de Fase 1; Docker sigue pendiente de su fase. D-16 se aplica a la configuración `America/La_Paz` y sesión MySQL `-04:00`, sin lógica de conversión externa anticipada.

D-04 y D-07 están implementadas para el núcleo web: RBAC sin bypass, historia de asignaciones y control de sesiones por hash. [Detalles y límites](docs/SECURITY_IMPLEMENTATION.md). El ámbito fino por recurso, móvil/API, almacenamiento distribuido y CU30 permanecen diferidos.

La Fase 4 aplica el contrato cerrado en Fase 3.5: solicitudes, consultas, resolución, cancelación y disponibilidad con APROBADA. D-05 permanece resuelta. [Evidencia de Fase 4](docs/RESERVATIONS_IMPLEMENTATION.md). Fase 4.5 atendió la revisión previa de Fase 5: D-02/D-11 cerradas, D-04/D-05 aplicadas sin reapertura. Quedan definidos receptor, entrega, devolución/revisión, incidencias, recuperación, permisos previstos y concurrencia. [Contrato y fuentes](docs/DECISIONS.md#contrato-de-equipamiento-y-préstamos--fase-45). Fase 5 implementó el núcleo CU12–CU19 bajo la nueva instrucción, sin alterar esas decisiones. D-09 quedó **RESUELTA PARA FASE 6** en el cierre documental de Fase 5.5. [Readiness for Phase 6: 0 bloqueantes documentales](docs/DECISIONS.md#readiness-for-phase-6). La siguiente fase es Códigos QR y validaciones, aplicando el contrato cerrado; requiere nueva instrucción y no se ha iniciado.

## Cierre documental QR — Fase 5.5

[Contrato exacto D-09](docs/DECISIONS.md#contrato-qr-y-validaciones--cierre-d-09-de-fase-55): token opaco de 256 bits/base64url, solo hashes SHA-256 persistidos, objetivo único, un QR activo por objetivo y rotación con historia; CU11 contextual y CU20 como extensión explícita. Validación y correlación con entrega/devolución en la misma petición/transacción, sin autorización por token ni ventanas arbitrarias. Tres permisos previstos, cámara/decodificación local y [24 criterios de aceptación futura](docs/BUSINESS_RULES.md#aceptación-futura-de-qr--fase-6).

Este cierre modifica solo documentación. No hay migraciones/modelos/permisos QR, librerías, cámara, endpoints ni interfaces implementados. La suite de 262 pruebas / 3318 aserciones es evidencia anterior de Fase 5, no una ejecución QR ni una nueva ejecución de Fase 5.5.
