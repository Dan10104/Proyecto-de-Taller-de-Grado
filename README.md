# ESPACIO-U

Sistema de información para la gestión y control de reservas de espacios académicos y préstamos de equipamiento tecnológico en la FICCT, con monitoreo de ocupación mediante IoT y trazabilidad de operaciones mediante Blockchain.

**Estado: Fase 3 implementada y validada.** Laravel + Vue 3 + Inertia + Vite con seguridad web, gestión de pisos/espacios/tipos/características, bloqueos y disponibilidad base sobre MySQL. CU05 tiene su núcleo funcional; CU06 está en **IMPLEMENTACIÓN PARCIAL — FASE 3**: comprueba estado y bloqueos, con reservas pendientes de Fase 4 e IoT de Fase 7. Políticas solo como dependencia estructural del FK. CU30, auditoría general, Flutter/Android, API móvil y despliegue continúan pendientes.

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

La suite usa exclusivamente `espacio_u_testing` en MySQL real, con cuenta separada y guarda previa a migraciones/limpieza. Pasaron 127 pruebas / 1395 aserciones, sin omitidas; build y auditorías Composer/npm correctos. Incluye dos procesos simultáneos para asignaciones, último administrador y renovación/logout. No certifica CU30, módulos posteriores, carga ni despliegue.

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
├── database/            # Catorce migraciones, catálogo RBAC y factories sintéticas
├── docs/                # Contratos y procedimiento de desarrollo
├── public/              # Punto de entrada; build generado e ignorado
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   ├── js/Pages/{Auth,Users,Roles,Spaces}/
│   └── views/app.blade.php
├── routes/              # Seguridad, espacios, catálogos, disponibilidad y /up
├── scripts/             # Entorno de terminal y ciclo de MySQL local
├── storage/             # Datos generados privados
├── tests/Feature/
├── artisan
├── composer.json / composer.lock
├── package.json / package-lock.json
└── vite.config.js
```

`.tools/`, `.local/`, `.env`, `vendor/`, `node_modules/`, logs y compilados permanecen locales e ignorados. `.env.example` contiene secretos vacíos. MySQL de desarrollo usa `espacio_u` en `127.0.0.1:3308`; 14 tablas académicas están materializadas (seis de seguridad, siete de espacios y una política estructural); las otras 17 siguen pendientes. `espacio_u_testing` tiene credenciales exclusivas en `.env.testing` local.

## Continuidad

Fases 0 y 0.5 conservan su cierre documental y sus 17 expedientes. D-15 queda atendida para el entorno web de Fase 1; Docker sigue pendiente de su fase. D-16 se aplica a la configuración `America/La_Paz` y sesión MySQL `-04:00`, sin lógica de conversión externa anticipada.

D-04 y D-07 están implementadas para el núcleo web: RBAC sin bypass, historia de asignaciones y control de sesiones por hash. [Detalles y límites](docs/SECURITY_IMPLEMENTATION.md). El ámbito fino por recurso, móvil/API, almacenamiento distribuido y CU30 permanecen diferidos.

La revisión automática y manual de Fase 3 está completada; su evidencia y límites están en [SPACES_IMPLEMENTATION.md](docs/SPACES_IMPLEMENTATION.md). La petición de Fase 3 autoriza RBAC por módulo/acción y mantiene el ámbito fino diferido. Fase 4 requiere nueva instrucción; antes de iniciarla, cerrar los parámetros pendientes D-05 e integrar reservas activas en el servicio de disponibilidad. No se inició Fase 4.
