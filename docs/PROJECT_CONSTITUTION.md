# Constitución del proyecto ESPACIO-U

Estado: Fase 0 documental. Fecha de auditoría: 2026-10-23. No hay aplicación, migraciones ni integraciones implementadas.

## Identidad y autoridad

**Sistema de información para la gestión y control de reservas de espacios académicos y préstamos de equipamiento tecnológico en la FICCT, con monitoreo de ocupación mediante IoT y trazabilidad de operaciones mediante Blockchain.** El alcance institucional es exclusivamente la FICCT.

La petición de Fase 0 gobierna el trabajo autorizado. El informe final Word y su PDF son la fuente oficial de requisitos, comportamiento y diseño. La documentación de `docs/` sintetiza esas fuentes; no las reemplaza ni corrige silenciosamente. Las referencias y huellas de ambos archivos constan en [DECISIONS.md](DECISIONS.md). No se publican copias de los originales ni datos personales de sus ejemplos.

> Ante cualquier contradicción entre implementación y documentación oficial, se debe detener la implementación del punto contradictorio y consultar el informe antes de modificar el modelo.

> El único workspace local oficial del proyecto es `C:\Users\BlackMaster69\Desktop\ESPACIO-U`, y GitHub constituye la fuente oficial de control de versiones.

> No se deben conservar copias paralelas o versiones manuales duplicadas de archivos fuente; Git debe utilizarse para consultar y recuperar versiones anteriores.

Repositorio oficial: https://github.com/Dan10104/Proyecto-de-Taller-de-Grado.git. Editor: Visual Studio Code con esa única carpeta como raíz. No usar otros checkouts, worktrees, ZIP ni carpetas con sufijos de versión para este proyecto.

## Alcance vigente

La implementación posterior comprenderá la plataforma **web**: acceso y administración; espacios y reservas; equipamiento y préstamos; QR; monitoreo IoT; políticas de liberación; trazabilidad Blockchain; notificaciones internas; indicadores y reportes; asistencia mediante chatbot. Cada capacidad debe trazarse a un RF/CU y a una prueba.

Flutter, Android, APK y pantallas móviles nativas quedan diferidos por instrucción expresa. La web responsiva y la API REST compatible con un cliente futuro sí pertenecen al diseño. Un requisito móvil diferido no se considera satisfecho por la web. Esta fase solo autoriza documentación, inventario del entorno y archivos base de seguridad. No iniciar Fase 1 sin una nueva instrucción.

No agregar PWA, voz, predicción, recuperación de contraseña ni historial persistente del chatbot solo porque aparezcan en un cronograma, un boceto o una figura de análisis; consultar las discrepancias registradas antes de ampliar alcance.

## Tecnología y arquitectura obligatorias

| Responsabilidad | Tecnología oficial |
| --- | --- |
| Servidor y reglas de negocio | PHP y Laravel |
| Presentación web | Vue 3, Inertia.js y Vite |
| Persistencia operativa | MySQL relacional |
| Comunicación | Rutas web Laravel/Inertia; API REST/JSON donde corresponda |
| Integraciones | HTTP o MQTT para IoT; red/servicio Blockchain externo; proveedor externo de chatbot |
| Despliegue posterior | Docker, Google Cloud Run, Cloud SQL for MySQL y Google Artifact Registry |

No sustituir por React, Next.js, Angular, NestJS, Django, Firebase, Supabase, PostgreSQL, MongoDB u otro stack ajeno al informe. Node/npm apoyan la compilación del frontend; no sustituyen al backend PHP. El informe menciona Tailwind CSS y Bootstrap como herramientas de presentación; su uso concreto se decidirá sin cambiar el stack ni duplicar sistemas visuales innecesariamente. No se fija una versión de Laravel que el informe no haya especificado.

Laravel centraliza autenticación, autorización, validación, transacciones y reglas; Vue presenta resultados. Las llamadas externas salen desde el backend. Las capas, paquetes y ubicaciones futuras están en [ARCHITECTURE.md](ARCHITECTURE.md).

## Lenguaje del dominio

Usuario, Rol, Permiso, AsignacionRol, SesionAcceso, Piso, RecursoInstitucional, EspacioAcademico, TipoEspacio, CaracteristicaEspacio, PoliticaLiberacion, BloqueoEspacio, Reserva, CodigoQR, ValidacionQR, SensorIoT, EventoOcupacion, EquipamientoTecnologico, TipoEquipamiento, Prestamo, EntregaPrestamo, DevolucionPrestamo, IncidenciaMantenimiento, EvaluacionLiberacion, RegistroActividad, EvidenciaTrazabilidad y Notificacion son los conceptos del diseño. En interfaz se emplean sus denominaciones comprensibles en español y con tildes.

`RecursoInstitucional` es la superclase oficial del diagrama de clases y del SQL, no una entidad añadida por esta fase. No introducir Facultad como entidad, Activo, ReservaActivo, Aula como entidad general ni Módulo como clase de negocio. “Paquete” o “módulo” solo describe una agrupación de implementación.

## Persistencia y estados

Mantener el modelo de 31 tablas, 211 columnas y 63 FK documentadas, sometido a las discrepancias abiertas. El inventario detallado está en [DATA_MODEL.md](DATA_MODEL.md). No crear migraciones a partir del SQL sin revisar las inconsistencias. Los estados persistidos son exactamente los enums del diseño; “ocupado”, “activo” o “vencido” en la prosa no autorizan nuevos valores.

MySQL conserva la información operativa completa e histórica. Usar operaciones atómicas para cambios relacionados, revalidar disponibilidad bajo concurrencia e impedir solapamientos incompatibles. Los índices de fechas no garantizan por sí solos esas reglas. Conservar autores, responsables, fechas y contexto. No eliminar historia necesaria para auditoría, incluso cuando el SQL contenga relaciones en cascada. Las copias de seguridad operativas de la base de datos son obligatorias y diferentes de las copias manuales de código prohibidas.

## Reglas transversales innegociables

- **Acceso:** credenciales válidas, cuenta activa, roles y permisos efectivos. Autorizar cada acción y cada objeto en el servidor, incluida la propiedad de solicitudes y el ámbito del encargado. Cerrar sesión invalida el acceso anterior. Las contraseñas se almacenan como hash. No hay SSO/LDAP incluido en el alcance inicial.
- **Reservas y préstamos:** verificar periodos, estados, recurso y permisos antes de persistir o resolver. Entrega exige préstamo aprobado, equipo disponible y receptor identificado. Devolución de condición desconocida queda pendiente de revisión; no liberar automáticamente el equipo.
- **QR:** identifica y aporta una validación contextual; no concede permisos. Validar usuario, token, vigencia, objetivo, recurso esperado, operación y contexto. Un QR inválido no confirma uso, entrega o devolución. Conservar solo hashes de los tokens.
- **IoT:** mide presencia/ocupación y no identidad. Solo aceptar sensores registrados y habilitados. Guardar el espacio histórico del evento. Lecturas ausentes, antiguas o insuficientes producen ocupación no determinable; no equivalen a espacio libre.
- **Liberación:** evaluar reserva, política, tolerancia y evidencias QR/IoT. Información insuficiente o contradictoria conserva la reserva. La liberación automática solo procede cuando la política la permite; el modo ALERTA no equivale a liberar.
- **Blockchain:** la operación principal debe estar confirmada en MySQL. Registrar hash y evidencia mínima local, y enviar únicamente los datos permitidos. La indisponibilidad externa deja la evidencia pendiente y nunca revierte la operación. Verificar usando el mismo algoritmo y versión canónica.
- **Chatbot:** orienta según los permisos del usuario; no ejecuta aprobaciones, rechazos, cancelaciones protegidas, entregas, devoluciones ni acciones administrativas. No recibe secretos ni contexto fuera del alcance autorizado. Su fallo no bloquea reservas ni préstamos.
- **Notificaciones:** son internas, se generan después de confirmar la operación y se consultan por destinatario. El fallo de notificación no deshace la operación principal.

Las reglas y sus pruebas se desarrollan en [BUSINESS_RULES.md](BUSINESS_RULES.md), [USE_CASE_MATRIX.md](USE_CASE_MATRIX.md) y [REQUIREMENTS_TRACEABILITY.md](REQUIREMENTS_TRACEABILITY.md).

## Seguridad del repositorio público

Aplicar [SECURITY_POLICY.md](SECURITY_POLICY.md) antes de cada commit y push. Nunca versionar `.env`, claves, tokens, contraseñas, cuentas reales, certificados privados, JSON de credenciales ni datos académicos privados. La única plantilla pública permitida es `.env.example`, con valores vacíos o inocuos. `APP_KEY` real se genera en el entorno autorizado, no se publica. No escribir secretos en logs, ejemplos, pruebas, issues o mensajes de commit. No eludir un bloqueo de secretos de GitHub.

## Organización y flujo Git

Documentación en `docs/`; código posterior en la estructura estándar Laravel/Vue/Inertia; pruebas en `tests/`; configuración en `config/`; rutas en `routes/`; migraciones, factories y seeders en `database/`. Servicios propios en `app/Services/` según su responsabilidad; páginas y componentes en `resources/js/`. No crear carpetas vacías por anticipado ni reorganizar el framework por estética. Scripts permanentes en `scripts/` solo si tienen propósito mantenido.

Al empezar cada fase: abrir la raíz oficial; comprobar `git rev-parse --show-toplevel`, `git remote -v`, rama y `git status`; revisar y sincronizar sin sobrescribir cambios ajenos. Al terminar: validar requisitos/pruebas pertinentes, revisar estructura, borrar material temporal, comprobar duplicados, revisar `.gitignore`, diferencias y staged, escanear secretos, hacer un commit coherente y push al remoto verificado. No usar nombres `old`, `backup`, `final2` ni versiones manuales. No hacer reset destructivo para obtener una carpeta limpia.

## Puerta de salida de Fase 0

Deben existir los diez documentos requeridos, con cobertura de 30 CU, 26 RF y 217 requisitos, modelo y diagramas contrastados, decisiones pendientes identificadas, entorno inventariado y controles Git/seguridad ejecutados. El cierre documental no afirma que el software esté construido ni que las ambigüedades estén resueltas. Las fases posteriores respetarán las puertas de decisión de [DECISIONS.md](DECISIONS.md) y [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md).
