# ESPACIO-U

Sistema de información para la gestión y control de reservas de espacios académicos y préstamos de equipamiento tecnológico en la FICCT, con monitoreo de ocupación mediante IoT y trazabilidad de operaciones mediante Blockchain.

**Estado: Fase 1 completada y Fase 1.5 documental cerrada; Fase 2 no iniciada.** Funciona la fundación Laravel + Vue 3 + Inertia + Vite, con MySQL real y una única página técnica. No están implementados autenticación, usuarios, roles, permisos, reservas, préstamos, QR, IoT, Blockchain, notificaciones, reportes ni chatbot. Flutter/Android y despliegue siguen fuera de esta fase.

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

Abrir http://127.0.0.1:8000. Debe mostrarse “ESPACIO-U” y “Fundación técnica configurada correctamente”. Para editar con Vite, activar el mismo entorno en una segunda terminal y ejecutar `npm run dev`; Laravel sigue ejecutándose en la primera. Detener los servidores con Ctrl+C y MySQL con `.\scripts\mysql-dev.ps1 -Action Stop`.

Para preparar un entorno sin dependencias instaladas, consultar primero [DEVELOPMENT_SETUP.md](docs/DEVELOPMENT_SETUP.md). Allí se explican `composer install`, `npm ci`, `.env`, herramientas locales y creación segura de MySQL. No ejecutar migraciones de dominio en Fase 1.

## Comprobar la fundación

Con MySQL iniciado y los assets compilados:

```powershell
php artisan test
composer audit
npm audit
```

La suite realiza lecturas reales de MySQL y comprueba HTTP, Inertia, español y zona operativa. Al cierre pasaron 3 pruebas / 17 aserciones, build de producción, comprobación de navegador y ambas auditorías. No certifica CU01–CU30 ni requisitos completos de rendimiento.

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
| [Seguridad](docs/SECURITY_POLICY.md) | Secretos, datos, controles y revisión antes de cada push |
| [Plan](docs/IMPLEMENTATION_PLAN.md) | Inventario real del entorno y Fases 1–14 |
| [Entorno de desarrollo](docs/DEVELOPMENT_SETUP.md) | Versiones, instalación, MySQL aislado, ejecución y verificaciones |
| [Decisiones](docs/DECISIONS.md) | 17 decisiones clasificadas, contratos congelados y detalles diferidos |

## Estructura actual

```text
ESPACIO-U/
├── app/                 # Entrada HTTP, middleware Inertia y proveedor base
├── bootstrap/           # Arranque Laravel
├── config/              # Configuración sin secretos
├── database/            # Sin migraciones, modelos o seeders del dominio
├── docs/                # Contratos y procedimiento de desarrollo
├── public/              # Punto de entrada; build generado e ignorado
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   ├── js/Pages/Foundation.vue
│   └── views/app.blade.php
├── routes/              # Solo / y /up
├── scripts/             # Entorno de terminal y ciclo de MySQL local
├── storage/             # Datos generados privados
├── tests/Feature/
├── artisan
├── composer.json / composer.lock
├── package.json / package-lock.json
└── vite.config.js
```

`.tools/`, `.local/`, `.env`, `vendor/`, `node_modules/`, logs y compilados permanecen locales e ignorados. `.env.example` contiene secretos vacíos. MySQL de desarrollo usa `espacio_u` en `127.0.0.1:3308`; el modelo documental de 31 tablas todavía no se ha materializado.

## Continuidad

Fases 0 y 0.5 conservan su cierre documental y sus 17 expedientes. D-15 queda atendida para el entorno web de Fase 1; Docker sigue pendiente de su fase. D-16 se aplica a la configuración `America/La_Paz` y sesión MySQL `-04:00`, sin lógica de conversión externa anticipada.

D-04 y D-07 están **resueltas para Fase 2**, con cero bloqueantes para el núcleo web: RBAC sobre el esquema oficial, Laravel Session y control por hash en sesiones_acceso. El ámbito fino por recurso y la autenticación móvil/API siguen diferidos. Consultar [Readiness for Phase 2](docs/DECISIONS.md#readiness-for-phase-2).

La siguiente instrucción podrá autorizar las seis tablas de seguridad y CU01–CU04, precedidos por el catálogo de permisos trazado y MySQL de testing separado. La bitácora funcional/CU30 requiere autorización de registros_actividad, fuera de esa lista de seis tablas. Fase 1.5 solo modificó documentos; no creó autenticación, migraciones ni una base de testing.
