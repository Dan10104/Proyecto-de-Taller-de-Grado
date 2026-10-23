# Constitución del proyecto ESPACIO-U

Estado vigente: Fases 0, 0.5, 1, 1.5, 2 y alcance autorizado de Fase 3 completados, 2026-10-23. Seguridad web y CU05 funcionales; CU06 parcial por estado/bloqueos. Políticas solo estructurales. Fase 4 y las integraciones posteriores no iniciadas; las secciones históricas conservan las decisiones de su momento.

## Identidad y autoridad

**Sistema de información para la gestión y control de reservas de espacios académicos y préstamos de equipamiento tecnológico en la FICCT, con monitoreo de ocupación mediante IoT y trazabilidad de operaciones mediante Blockchain.** El alcance institucional es exclusivamente la FICCT.

Las peticiones de Fases 0, 0.5, 1 y 1.5 gobiernan el trabajo autorizado, incluida la instrucción de MySQL de desarrollo aislado. Fase 1.5 cierra exclusivamente contratos de autorización y sesión web. El informe final Word y su PDF son la fuente oficial de requisitos, comportamiento y diseño. La documentación de `docs/` sintetiza esas fuentes; no las reemplaza ni corrige silenciosamente. Las referencias y huellas de ambos archivos constan en [DECISIONS.md](DECISIONS.md). No se publican copias de los originales ni datos personales de sus ejemplos.

> Ante cualquier contradicción entre implementación y documentación oficial, se debe detener la implementación del punto contradictorio y consultar el informe antes de modificar el modelo.

> El único workspace local oficial del proyecto es `C:\Users\BlackMaster69\Desktop\ESPACIO-U`, y GitHub constituye la fuente oficial de control de versiones.

> No se deben conservar copias paralelas o versiones manuales duplicadas de archivos fuente; Git debe utilizarse para consultar y recuperar versiones anteriores.

Repositorio oficial: https://github.com/Dan10104/Proyecto-de-Taller-de-Grado.git. Editor: Visual Studio Code con esa única carpeta como raíz. No usar otros checkouts, worktrees, ZIP ni carpetas con sufijos de versión para este proyecto.

## Alcance vigente

La implementación posterior comprenderá la plataforma **web**: acceso y administración; espacios y reservas; equipamiento y préstamos; QR; monitoreo IoT; políticas de liberación; trazabilidad Blockchain; notificaciones internas; indicadores y reportes; asistencia mediante chatbot. Cada capacidad debe trazarse a un RF/CU y a una prueba.

Flutter, Android, APK y pantallas móviles nativas quedan diferidos por instrucción expresa. La web responsiva y la API REST compatible con un cliente futuro sí pertenecen al diseño. Un requisito móvil diferido no se considera satisfecho por la web. Fase 0 autorizó documentación, inventario y archivos base de seguridad; Fase 0.5 únicamente congela y versiona contratos documentales. La instrucción posterior de Fase 1 autorizó únicamente la fundación técnica. Toda fase funcional requiere su propia autorización.

No agregar PWA, voz, predicción, recuperación de contraseña ni historial persistente del chatbot solo porque aparezcan en un cronograma, un boceto o una figura de análisis; D-13/D-14 los clasifican como NO AFECTA IMPLEMENTACIÓN; no forman parte del alcance por esas menciones aisladas.

## Tecnología y arquitectura obligatorias

| Responsabilidad | Tecnología oficial |
| --- | --- |
| Servidor y reglas de negocio | PHP y Laravel |
| Presentación web | Vue 3, Inertia.js y Vite |
| Persistencia operativa | MySQL relacional |
| Comunicación | Rutas web Laravel/Inertia; API REST/JSON donde corresponda |
| Integraciones | HTTP o MQTT para IoT; red/servicio Blockchain externo; proveedor externo de chatbot |
| Despliegue posterior | Docker, Google Cloud Run, Cloud SQL for MySQL y Google Artifact Registry |

No sustituir por React, Next.js, Angular, NestJS, Django, Firebase, Supabase, PostgreSQL, MongoDB u otro stack ajeno al informe. Node/npm apoyan la compilación del frontend; no sustituyen al backend PHP. El informe menciona Tailwind CSS y Bootstrap como herramientas de presentación; en Fase 1 se usa Tailwind para el CSS mínimo, sin duplicar sistemas visuales. Las versiones concretas seleccionadas con fuentes oficiales están en [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md); son una decisión de entorno, no versiones atribuidas al informe.

Laravel centraliza autenticación, autorización, validación, transacciones y reglas; Vue presenta resultados. Las llamadas externas salen desde el backend. Las capas, paquetes y ubicaciones futuras están en [ARCHITECTURE.md](ARCHITECTURE.md).

## Lenguaje del dominio

Usuario, Rol, Permiso, AsignacionRol, SesionAcceso, Piso, RecursoInstitucional, EspacioAcademico, TipoEspacio, CaracteristicaEspacio, PoliticaLiberacion, BloqueoEspacio, Reserva, CodigoQR, ValidacionQR, SensorIoT, EventoOcupacion, EquipamientoTecnologico, TipoEquipamiento, Prestamo, EntregaPrestamo, DevolucionPrestamo, IncidenciaMantenimiento, EvaluacionLiberacion, RegistroActividad, EvidenciaTrazabilidad y Notificacion son los conceptos del diseño. En interfaz se emplean sus denominaciones comprensibles en español y con tildes.

`RecursoInstitucional` es la superclase oficial del diagrama de clases y del SQL, no una entidad añadida por esta fase. No introducir Facultad como entidad, Activo, ReservaActivo, Aula como entidad general ni Módulo como clase de negocio. “Paquete” o “módulo” solo describe una agrupación de implementación.

## Persistencia y estados

Mantener el modelo de 31 tablas, 211 columnas y 63 FK documentadas, con las decisiones de Fase 0.5 aplicadas. El inventario detallado está en [DATA_MODEL.md](DATA_MODEL.md). El contrato implementable normaliza únicamente `permisos.codigo` (D-01); las tablas se implementarán progresivamente en sus fases funcionales. Fase 1 crea solamente la base de desarrollo vacía, sin migraciones del dominio. Los estados persistidos son exactamente los enums del diseño; “ocupado”, “activo” o “vencido” en la prosa no autorizan nuevos valores.

MySQL conserva la información operativa completa e histórica. Usar operaciones atómicas para cambios relacionados, revalidar disponibilidad bajo concurrencia e impedir solapamientos incompatibles. Los índices de fechas no garantizan por sí solos esas reglas. Conservar autores, responsables, fechas y contexto. No eliminar historia necesaria para auditoría, incluso cuando el SQL contenga relaciones en cascada. Las copias de seguridad operativas de la base de datos son obligatorias y diferentes de las copias manuales de código prohibidas.

## Reglas transversales innegociables

- **Acceso:** credenciales válidas, cuenta activa, roles y permisos efectivos. Autorizar cada acción y cada objeto en el servidor, incluida la propiedad de solicitudes y el ámbito del encargado. Cerrar sesión con confirmación del servidor invalida el acceso anterior; sin comunicación no se afirma revocación remota confirmada (D-07). Las contraseñas se almacenan como hash. No hay SSO/LDAP incluido en el alcance inicial.
- **Reservas y préstamos:** verificar periodos, estados, recurso y permisos antes de persistir o resolver. Entrega exige préstamo aprobado, equipo disponible y receptor identificado. Devolución de condición desconocida queda pendiente de revisión; no liberar automáticamente el equipo.
- **QR:** identifica y aporta una validación contextual; no concede permisos. Validar usuario, token, vigencia, objetivo, recurso esperado, operación y contexto. Un QR inválido no confirma uso, entrega o devolución. Conservar solo hashes de los tokens.
- **IoT:** mide presencia/ocupación y no identidad. Solo aceptar sensores registrados, habilitados y asociados a espacio. Guardar el espacio histórico del evento. Lecturas ausentes, antiguas o insuficientes producen ocupación no determinable; no equivalen a espacio libre.
- **Liberación:** evaluar reserva, política, tolerancia y evidencias QR/IoT. Información insuficiente o contradictoria conserva la reserva, salvo que informe y política permitan inequívocamente otra decisión. La liberación automática solo procede cuando la política la permite; el modo ALERTA no equivale a liberar.
- **Blockchain:** la operación principal debe estar confirmada en MySQL. Registrar hash y evidencia mínima local, y enviar únicamente los datos permitidos. La indisponibilidad externa deja la evidencia pendiente y nunca revierte la operación. Verificar usando el mismo algoritmo y versión canónica.
- **Chatbot:** orienta según los permisos del usuario; no ejecuta aprobaciones, rechazos, cancelaciones protegidas, entregas, devoluciones ni acciones administrativas. No recibe secretos ni contexto fuera del alcance autorizado. Su fallo no bloquea reservas ni préstamos.
- **Notificaciones:** son internas, se generan después de confirmar la operación y se consultan por destinatario. El fallo de notificación no deshace la operación principal.

Las reglas y sus pruebas se desarrollan en [BUSINESS_RULES.md](BUSINESS_RULES.md), [USE_CASE_MATRIX.md](USE_CASE_MATRIX.md) y [REQUIREMENTS_TRACEABILITY.md](REQUIREMENTS_TRACEABILITY.md).

## Seguridad del repositorio público

Aplicar [SECURITY_POLICY.md](SECURITY_POLICY.md) antes de cada commit y push. Nunca versionar `.env`, claves, tokens, contraseñas, cuentas reales, certificados privados, JSON de credenciales ni datos académicos privados. La única plantilla pública permitida es `.env.example`, con valores vacíos o inocuos. `APP_KEY` real se genera en el entorno autorizado, no se publica. No escribir secretos en logs, ejemplos, pruebas, issues o mensajes de commit. No eludir un bloqueo de secretos de GitHub.

## Organización y flujo Git

Documentación en `docs/`; código en la estructura estándar Laravel/Vue/Inertia; pruebas en `tests/`; configuración en `config/`; rutas en `routes/`; migraciones, factories y seeders en `database/`. Servicios propios en `app/Services/` según su responsabilidad; páginas y componentes en `resources/js/`. No crear carpetas vacías por anticipado ni reorganizar el framework por estética. Scripts permanentes en `scripts/` solo si tienen propósito mantenido.

Al empezar cada fase: abrir la raíz oficial; comprobar `git rev-parse --show-toplevel`, `git remote -v`, rama y `git status`; revisar y sincronizar sin sobrescribir cambios ajenos. Al terminar: validar requisitos/pruebas pertinentes, revisar estructura, borrar material temporal, comprobar duplicados, revisar `.gitignore`, diferencias y staged, escanear secretos, hacer un commit coherente y push al remoto verificado. No usar nombres `old`, `backup`, `final2` ni versiones manuales. No hacer reset destructivo para obtener una carpeta limpia.

## Cierre documental de Fases 0 y 0.5

Los diez documentos cubren 30 CU, 26 RF y 217 requisitos. Fase 0.5 clasifica los 17 expedientes: 5 RESUELTA, 3 CONVENCIÓN DE IMPLEMENTACIÓN, 2 NO AFECTA IMPLEMENTACIÓN, 7 DIFERIDA A UNA FASE POSTERIOR y 0 REALMENTE BLOQUEANTE. La preparación documental para Fase 1 está cerrada; no certifica software construido ni pruebas funcionales. Los detalles diferidos tienen fase/límite en [DECISIONS.md](DECISIONS.md#readiness-for-phase-1) y [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md). La instrucción posterior de Fase 1 fue ejecutada; su evidencia técnica consta en DEVELOPMENT_SETUP.md. Este cierre histórico de Fase 0.5 no se confunde con esa implementación.

## Contratos de Fase 0.5

La jerarquía depende del problema: persistencia = SQL final → mapeo/volumen → BD lógica → clases → narrativa; comportamiento = detalle CU → RF → reglas DB → secuencias → análisis → ilustraciones anteriores; dominio = clases finales → dominio final → BD lógica → SQL, contrastando cardinalidades con FK/nullable; arquitectura = diseño final cap. VII → RD → IS/IC → capítulo tecnológico → cronograma. El cronograma es planificación histórica y no define tecnologías, dominio ni funcionalidades (D-13, NO_AFECTA_IMPLEMENTACION).

Identificadores técnicos ASCII sin tildes, snake_case donde corresponda; clases siguen PascalCase y convenciones del framework. UI en español correcto. Los 19 ENUM se conservan y las etiquetas narrativas/condiciones calculadas no agregan estados. Intervalos `[inicio, fin)`, fin>inicio; zona operativa `America/La_Paz` para captura, presentación y DATETIME existentes, convirtiendo instantes externos antes de comparar/persistir.

El iniciador/flujo del CU, permisos, RBAC, propiedad y ámbito determinan autorización; la lista Usuarios del RF no concede acciones. Reserva apunta a espacio, préstamo a equipamiento y equipo a espacio base opcional. El alcance del encargado se resolverá con permisos/ámbito acreditado; no crear otro sistema de asignaciones. Las cardinalidades físicas y tablas intermedias se conservan (D-06/D-17).

La representación previa al hash será determinista, con orden de campos y formato de fecha estables, UTF-8, datos mínimos no personales y `version_canonica`. Su especificación exacta se difiere explícitamente hasta antes de Fase 9; no bloquea la fundación técnica. Los contratos QR/IoT/liberación y el flujo correcto de CU17 se aplican según BUSINESS_RULES y USE_CASE_MATRIX.

## Contratos de Fase 1.5 y límite de la próxima implementación

D-04 y D-07: **RESUELTAS PARA FASE 2** en [DECISIONS.md](DECISIONS.md#readiness-for-phase-2), sin software funcional creado. Los únicos roles humanos son Administrador, Encargado de Laboratorio, Docente y Estudiante. Autorizar por identidad/cuenta activa, rol vigente, permiso explícito, iniciador/flujo del CU, restricciones y ámbito cuando exista; denegar por defecto. Administrador recibe capacidades amplias mediante RBAC, sin excepción universal. Las asignaciones históricas y ambas relaciones M:N son obligatorias; no sustituirlas con un campo de rol en usuarios o tablas de un paquete.

La web utilizará Laravel Session, correo/password_hash, Hash/Argon2id y CSRF; sesiones_acceso controlará el hash SHA-256 del ID final de sesión, su vigencia y revocación. La duración deriva de la configuración Laravel y cada acceso protegido exige coherencia entre sesión técnica y registro. El alcance fino por recurso se cierra antes de su flujo funcional; móvil/API sigue diferido.

La petición Fase 1.5 §15 limita la siguiente Fase 2 a usuarios, roles, permisos, roles_permisos, asignaciones_roles y sesiones_acceso; tablas técnicas solo cuando sean necesarias y documentadas. No autoriza registros_actividad ni otros módulos: RF25/CU30 conservan vigencia como requisitos pendientes, sin confundir el registro de sesiones con bitácora general. Ninguna prueba con escritura podrá usar la base normal de desarrollo: se exige MySQL de testing separado. Comenzar Fase 2 requiere nueva instrucción.

## Alcance posterior autorizado — Fase 3

La petición Fase 3 autoriza siete tablas operativas de espacios y politicas_liberacion solo como FK estructural. CU05 núcleo funcional; CU06 IMPLEMENTACIÓN PARCIAL — FASE 3, con reservas Fase 4 e IoT Fase 7 pendientes. §35 concreta RBAC por módulo/acción, manteniendo ámbito fino diferido sin nuevas tablas. No se autorizan reservas, política funcional, CU30 ni otros módulos. Workspace y remoto originales se mantienen. [Implementación y validación](SPACES_IMPLEMENTATION.md).
