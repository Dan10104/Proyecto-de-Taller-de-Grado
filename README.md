# ESPACIO-U

Sistema de información para la gestión y control de reservas de espacios académicos y préstamos de equipamiento tecnológico en la FICCT, con monitoreo de ocupación mediante IoT y trazabilidad de operaciones mediante Blockchain.

**Estado actual: Fase 0 documental.** El repositorio contiene constitución, modelo, trazabilidad y planificación. Todavía no hay aplicación ejecutable, autenticación, CRUD, API ni integraciones implementadas. La siguiente fase requiere una nueva instrucción. La implementación prevista es exclusivamente web; Flutter/Android quedan diferidos.

## Fuente de verdad

El informe final Word/PDF gobierna el comportamiento y diseño. Las fuentes, cobertura de revisión y diferencias están en [Decisiones y auditoría](docs/DECISIONS.md). No se publican los documentos originales ni datos personales. Ante una contradicción se detiene el punto afectado y se consulta el informe.

Repositorio oficial: [Dan10104/Proyecto-de-Taller-de-Grado](https://github.com/Dan10104/Proyecto-de-Taller-de-Grado). Workspace local único: `C:\Users\BlackMaster69\Desktop\ESPACIO-U`, abierto como raíz en Visual Studio Code. Git conserva las versiones; no crear copias/ZIP del proyecto.

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
| [Decisiones](docs/DECISIONS.md) | Hallazgos confirmados, vacíos y puertas de implementación |

## Estructura actual

```text
ESPACIO-U/
├── .env.example
├── .gitignore
├── README.md
└── docs/
    ├── PROJECT_CONSTITUTION.md
    ├── ARCHITECTURE.md
    ├── DOMAIN_MODEL.md
    ├── DATA_MODEL.md
    ├── BUSINESS_RULES.md
    ├── USE_CASE_MATRIX.md
    ├── REQUIREMENTS_TRACEABILITY.md
    ├── SECURITY_POLICY.md
    ├── IMPLEMENTATION_PLAN.md
    └── DECISIONS.md
```

## Tecnología prevista y preparación posterior

PHP/Laravel centralizará las reglas; Vue 3/Inertia/Vite construirá la web; MySQL almacenará información completa e histórica. Las integraciones IoT usarán HTTP o MQTT según el piloto; Blockchain y chatbot serán servicios externos. El despliegue futuro utilizará Docker, Google Cloud Run, Cloud SQL for MySQL y Google Artifact Registry.

En Fase 1 se seleccionarán versiones compatibles, se inicializará el stack en esta misma carpeta y se validarán compilación/arranque/conexión. El [plan](docs/IMPLEMENTATION_PLAN.md) registra PHP 8.2.12, Composer 2.9.8, Node 24.15.0, dos MySQL instalados y el motor Docker aún no disponible durante la auditoría. No ejecutar el SQL del informe directamente: hay discrepancias registradas, entre ellas `código`/`codigo` en permisos.

`.env.example` es una plantilla segura con secretos vacíos; no es configuración suficiente para arrancar una aplicación. El `.env` real y APP_KEY solo se crearán localmente cuando corresponda y permanecerán ignorados. No incluir cuentas reales, claves ni información académica privada en pruebas o documentación.
