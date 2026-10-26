# Plan de implementación web

**Estado vigente — Fase 13 completada y validada (2026-10-26).** QA integral de CU01–CU30 y RF1–RF25 web; RF26 móvil diferido. [Informe de seguridad, rendimiento y pruebas](PHASE_13_SYSTEM_VALIDATION.md) y [matriz final por CU](FINAL_USE_CASE_VALIDATION.md). Los estados de fases anteriores que siguen son históricos. OpenAI/Sepolia pendientes independientes; excepción dev Hardhat/Zod explícita. Fase 14 NO iniciada.

**Cierre histórico — Fase 12.6 — RF25/CU30 implementados y validados localmente (2026-10-26).** [Registro de actividad y bitácora](ACTIVITY_AUDIT_IMPLEMENTATION.md): 56 acciones, recorder transaccional, permiso exclusivo de Administrador y consulta individual. Sin nuevas tablas/dependencias. Fase 13 NO iniciada; smokes OpenAI/Sepolia pendientes independientes. Los cierres siguientes conservan su alcance histórico.

**Estado histórico — Fase 12.5 documental cerrada (2026-10-26).** [Activity Audit Contract / Readiness for CU30](DECISIONS.md#activity-audit-contract--readiness-for-cu30), **0 bloqueantes documentales**. RF25 con productores parciales; CU30 **NO IMPLEMENTADO**. Implementación solo con nueva instrucción. Sin cambios de código, datos, permisos, migraciones o dependencias; OpenAI/Sepolia pendientes independientes.

**Base funcional conservada — Fase 12 implementada y validada offline (2026-10-26).** RF24/CU29: [implementación del asistente](CHATBOT_IMPLEMENTATION.md), conforme a CH1–CH12. Orientación por permisos, OpenAI Responses y contexto mínimo sin datos operativos ni historial persistido. CU30 **NO IMPLEMENTADO**; aplicación móvil diferida; ninguna fase posterior iniciada. Los cierres siguientes conservan su contexto histórico.

**Estado histórico — Fase 11.5 documental cerrada (2026-10-26).** RF24/CU29: [Chatbot Assistance Contract / Readiness for Phase 12](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12), **0 bloqueantes documentales**. Fase 12 NO iniciada; CU30 NO IMPLEMENTADO. No cambios de código, dependencias, configuración o datos.

**Base funcional conservada — Fase 11 implementada y verificada (2026-10-26).** RF22/CU27 y RF23/CU28: 747 tests / 9111 assertions; QA y auditorías productivas aprobadas. [Implementación, scopes, formatos y evidencia](DASHBOARD_REPORTING_IMPLEMENTATION.md). Sin nuevas tablas ni índices; CU30 NO IMPLEMENTADO y Fase 12 NO iniciada. Los cierres anteriores son históricos.

**Estado histórico — Fase 10.5 documental (2026-10-26).** RF22/CU27 y RF23/CU28: [contrato de dashboard y reportes cerrado](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11), **0 bloqueantes documentales**. Fase 11 NO iniciada; CU30 NO IMPLEMENTADO. Fase 10 funcional se conserva, sin cambios de código, datos, permisos o dependencias. Los cierres anteriores mantienen su contexto histórico.

**Estado histórico funcional — Fase 10 (2026-10-26).** RF21/CU26 implementados y verificados: 17 reglas, avisos propios, primera lectura y recuperación de 24 h. [Informe y evidencia](NOTIFICATIONS_IMPLEMENTATION.md). CU30 general y Fase 11 no iniciados. Sepolia real sigue pendiente de testETH. Los apartados de cierres anteriores conservan su contexto histórico.

**Estado histórico al cierre de Fase 9 implementada (2026-10-25/26).** RF19/CU24 y RF20/CU25, cuatro actividades críticas, dos tablas y tres permisos implementados conforme a D-12. [Implementación, pruebas offline, excepción temporal dev de Zod y Sepolia pendiente de testETH](BLOCKCHAIN_IMPLEMENTATION.md). CU30 general y Fase 10 permanecen pendientes. Las secciones de cierres anteriores conservan su contexto histórico.

**Estado al cierre funcional de Fase 8 (2026-10-24).** Fase 8 implementa RF18/CU23. Fases 9–14 no iniciadas. Siguiente paso: instrucción explícita para cerrar D-12 (operaciones críticas, canonicalización, privacidad, proveedor y recuperación) antes de programar Fase 9. [Informe de implementación, verificaciones y límites](RELEASE_POLICY_IMPLEMENTATION.md). Las declaraciones de fases anteriores que siguen a continuación se conservan como historia de esos cierres.

Estado histórico: **Fase 7 implementada conforme a D-10; Fases 8–14 no iniciadas**. [Evidencia funcional IoT](IOT_IMPLEMENTATION.md). Fase 6 mantiene su implementación, suite y aceptación del usuario. Fases 0 y 0.5 conservaron su alcance documental; la nueva instrucción de Fase 1 autorizó la fundación técnica descrita en [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md). No existe Flutter, Android ni cliente nativo en el plan de ejecución.

## Inventario histórico anterior a Fase 1 — 2026-10-23

| Componente | Resultado comprobado | Siguiente paso |
| --- | --- | --- |
| Workspace / Git | Repositorio oficial clonado inicialmente vacío; rama main; origin oficial | Mantener una sola copia y verificar sincronización al iniciar cada fase |
| Visual Studio Code | 1.143.0; `code --status` mostró Folder ESPACIO-U y proceso `--new-window` con la raíz oficial | Continuar en esa ventana/carpeta |
| PHP CLI | 8.2.12, instalación XAMPP | Seleccionar versión compatible y soportada junto con Laravel en Fase 1; no asumir que cualquier versión reciente funciona con este PHP |
| Extensiones PHP | Disponibles PDO/pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, curl, dom, fileinfo, filter, hash, session y otras | Contrastar requerimientos de versiones elegidas; no confundir inventario con pruebas de aplicación |
| Composer | 2.9.8, utiliza el PHP observado | Confirmar requisitos y generar lockfile en Fase 1 |
| Node / npm | v24.15.0 / 11.13.0 | Validar compatibilidad con Vite elegido y fijar versiones/lockfile |
| MySQL | MySQL Server 8.0.46 y 9.7.0 instalados; servicios MySQL80 y MySQL97 activos | Elegir una instancia, puerto y credenciales privadas de desarrollo; no se probaron conexiones autenticadas ni se crearon BD |
| XAMPP mysql | El binario reporta MariaDB 10.4.32 | No utilizarlo como sustituto inadvertido del MySQL obligatorio |
| Docker | Cliente 29.5.3; motor desktop-linux no disponible durante revisión | Arrancar/verificar el motor antes de ensayos de contenedores; no afirmar que Docker ya funciona |
| Integraciones / nube | No configuradas ni probadas | Definir proveedores/contratos y configuración privada en sus fases |

Ese inventario corresponde a Fases 0/0.5, cuando no se instalaron dependencias ni se creó configuración privada. Fase 1 añadió PHP/Composer/Node/MySQL locales bajo el workspace, sin modificar las instalaciones globales. El inventario vigente y la conectividad validada están en DEVELOPMENT_SETUP.md.

## Reglas comunes de ejecución

1. Nueva instrucción del usuario para comenzar la fase; abrir la raíz oficial, comprobar rama/remoto/estado y sincronizar sin perder cambios locales.
2. Aplicar las decisiones vigentes de Fases 0.5 y 1.5. Cerrar solo los detalles diferidos antes de su fase/punto dependiente, registrando fundamento. Una contradicción nueva exige consultar el informe; no reabrir decisiones resueltas sin evidencia.
3. Usar ubicaciones convencionales del framework. Crear carpetas solamente al incorporar archivos reales. No producir copias completas del proyecto ni scripts temporales permanentes.
4. Implementar únicamente el alcance de la fase; mantener contratos de los componentes posteriores sin simular que ya funcionan. Planificar migraciones por dependencias: no crear FK hacia tablas inexistentes ni omitirlas silenciosamente del modelo final. Las ampliaciones de tablas por fases deben terminar equivalentes al esquema aprobado.
5. Ejecutar las pruebas pertinentes a cada cambio y comprobar invariantes integradas. Los hitos que requieren un componente posterior quedan explícitamente pendientes hasta su integración.
6. Revisar estructura, borrar temporales/duplicados, actualizar documentación, revisar diff/staged y secretos, commit coherente y push. Los mensajes propuestos pueden dividirse en commits pequeños, siempre descriptivos.

## Fases

### Fase 1 — Fundación técnica

**Completada el 2026-10-23.** Una raíz Laravel, página Vue/Inertia real, Vite/Tailwind, MySQL 9.7.4 aislado y base vacía, español y zona operativa. Pasaron 3 pruebas / 17 aserciones, build, navegador y auditorías sin vulnerabilidades reportadas. Sin modelos/migraciones de dominio, autenticación, API funcional ni integraciones. Versiones y procedimiento: DEVELOPMENT_SETUP.md. La tabla conserva el alcance planificado y satisfecho a nivel de fundación.

| Campo | Plan |
| --- | --- |
| Módulos afectados | Configuración y arquitectura base |
| Carpetas principales futuras | Raíz convencional del framework; config/; routes/; resources/js/; tests/; docs/ |
| Requisitos relacionados | IS-01–06; IC-01–05; RD-01–14/23–26/30–32/43–44; RNF-23/25/28/32 |
| Casos de uso | Preparación transversal; ningún CU de negocio se declara terminado |
| Trabajo previsto | Elegir versiones compatibles; inicializar Laravel y Vue 3/Inertia/Vite en la misma raíz conservando docs; fijar lockfiles; configurar conexión MySQL, locale y zona operativa America/La_Paz conforme D-16; punto de arranque y compilación; comprobar compatibilidad futura de sesión/API, sin implementar CU01 ni exigir resolver D-07 en esta fase. |
| Pruebas requeridas | Arranque, compilación de producción, configuración sin secretos, conexión de prueba a MySQL elegido y comprobación de stack real. |
| Riesgos / puertas | D-15: elegir versiones como primer trabajo; aplicar D-16 temporal; starter kits que agreguen autenticación, tablas o React; dos MySQL activos; .env local nunca versionado. |
| Condición de finalización | Base reproducible y documentada; build/arranque correctos; secretos protegidos; sin funciones de negocio anticipadas. |
| Commit esperado | `chore: establish Laravel Vue Inertia foundation` |

### Fase 1.5 — Contratos de autorización y sesión web

**Cierre documental el 2026-10-23.** D-04 y D-07 resueltas para el núcleo web; cero bloqueantes de decisión para Fase 2. RBAC oficial, historia de asignaciones, Laravel Session, control por hash, expiración coherente y aislamiento MySQL de pruebas definidos en DECISIONS.md y DEVELOPMENT_SETUP.md. No se implementaron funciones, ni se aprovisionó testing ni se modificó configuración. El ámbito fino por recurso y la autenticación móvil/API siguen diferidos.

### Fase 2 — Núcleo web de autenticación, usuarios, roles y permisos

**Núcleo implementado y validado el 2026-10-23 mediante 65 pruebas / 493 aserciones y el flujo manual de 15 pasos.** La lista de Fase 1.5 §15 sustituye el alcance anterior de bitácora base: solo seis tablas académicas. RF25/CU30 y la auditoría general exigida por otros CU permanecen pendientes hasta que se autorice implementar registros_actividad. No se consideran satisfechos por logs técnicos ni por sesiones_acceso. Esta dependencia no bloquea construir el núcleo, pero limita la aceptación integral de esos CU.

| Campo | Plan |
| --- | --- |
| Módulos afectados | Núcleo de Seguridad y Administración |
| Carpetas principales futuras | app/Models/; app/Http/Controllers/ y Requests/; app/Policies/; app/Services/; routes/; database/; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF1–3; RNF-10–16/21 en su parte web aplicable; DB-13/20–22; IC-04/05 como límites de seguridad, sin implementar todavía autenticación móvil/API |
| Casos de uso | CU01–CU04 en el núcleo web; auditoría general pendiente de su componente autorizado; CU30 fuera de esta fase delimitada |
| Tablas académicas permitidas | usuarios, roles, permisos, roles_permisos, asignaciones_roles, sesiones_acceso. Tablas técnicas de Laravel solo si son estrictamente necesarias y diferenciadas; file no exige una tabla sessions. |
| Trabajo previsto | Aplicar D-01/D-03/D-04/D-07/D-17 ya resueltas para este núcleo. Derivar el catálogo trazable de permisos antes de seeders/Policies; adaptar correo/password_hash; autenticación nativa, cuenta ACTIVO, hash Argon2id, regeneración/CSRF, control obligatorio de sesión, logout y expiración desde configuración. RBAC central sobre relaciones M:N e historia oficial, sin bypass de Administrador ni tablas de alcance inventadas. |
| Pruebas requeridas | Antes de escribir: aprovisionar y verificar MySQL espacio_u_testing y cuenta exclusiva según DEVELOPMENT_SETUP. Probar credenciales/inactividad, fijación, CSRF, vigencia/revocación, concurrencia de renovación/logout, fallos de registro, autorización directa/escalada, iniciador CU, cambios de permisos e historial de roles. Criterios en BUSINESS_RULES; no secretos en logs. |
| Riesgos / puertas | Derivar permisos de RF/CU, no de participantes; no usar espacio_u para pruebas con escritura. Mantener esquema académico y límites de tablas. No declarar auditoría general completa ni habilitar recursos de fases posteriores sin acreditar ámbito. |
| Condición de finalización | Núcleo de CU01–CU04 probado, con aceptación de auditoría general explícitamente pendiente; pruebas negativas e aislamiento MySQL verificados; seis tablas equivalentes al modelo; ninguna funcionalidad posterior anticipada. |
| Commit esperado | `feat: add web authentication users roles and permissions` |

### Fase 3 — Pisos, espacios, características, bloqueos y disponibilidad

**Completada en su alcance autorizado el 2026-10-23: 127 tests / 1395 aserciones y revisión manual.** CU05 núcleo funcional (bitácora diferida). CU06: **IMPLEMENTACIÓN PARCIAL — FASE 3**; reservas activas pendientes de Fase 4 e IoT de Fase 7. Siete tablas operativas y politicas_liberacion solo para FK, sin lógica de Fase 8. La petición §35 autoriza RBAC por módulo/acción y mantiene el ámbito fino diferido. La tabla siguiente conserva criterios futuros de carga dependientes de reservas; no se declaran ejecutados. [Implementación y evidencia](SPACES_IMPLEMENTATION.md).

| Campo | Plan |
| --- | --- |
| Módulos afectados | Gestión de Espacios y Reservas |
| Carpetas principales futuras | app/Models/; app/Services/; app/Policies/; app/Http/; database/; routes/; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF4–5; DB-05/14/16/17/20; R-TR-01; R-UR-03 |
| Casos de uso | CU05–CU06 |
| Trabajo previsto | Catálogos y superclase de recursos; características M:N; bloqueos; búsqueda por fechas/condiciones; aplicar intervalos/estados conflictivos D-05 y conservar dependencias con reservas/política. |
| Pruebas requeridas | Capacidad/código/periodos inválidos; bloqueos y mantenimiento; ámbito; cálculo de disponibilidad; <=3 s con 500 reservas sintéticas cuando exista el esquema relacionado. |
| Riesgos / puertas | No reemplazar RecursoInstitucional; no confundir estado de espacio con ocupación; dependencia temporal de tablas de reserva/política. |
| Condición de finalización | Catálogo y disponibilidad coherentes con contratos aprobados; dependencias FK planificadas sin relaciones ficticias; interfaz responsiva. |
| Commit esperado | `feat: add academic spaces and availability` |

### Fase 4 — Reservas

**Completada en el núcleo autorizado.** CU07–CU10 y disponibilidad operativa de CU06, con pruebas MySQL multiproceso y revisión manual. [Evidencia y límites](RESERVATIONS_IMPLEMENTATION.md).

| Campo | Plan |
| --- | --- |
| Módulos afectados | Gestión de Espacios y Reservas |
| Carpetas principales futuras | app/Models/; app/Services/; app/Policies/; app/Http/; database/; routes/; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF6–7; DB-04/08/15–17/19/21; R-TR-02; R-PR-01 |
| Casos de uso | CU07–CU10 |
| Trabajo previsto | Aplicar D-05 cerrado en Fase 3.5: solicitud PENDIENTE, consulta propia/administrativa, aprobación/rechazo y cancelación antes de inicio; extender SpaceAvailabilityService con APROBADA; lock del espacio, lecturas actuales y escritura condicional. Solo documentar puntos posteriores al commit para integraciones diferidas. |
| Pruebas requeridas | T-R01–17 de BUSINESS_RULES: dos pendientes coexisten; primera aprobación; segunda en conflicto sigue pendiente; dos procesos MySQL reales con una aprobada; contiguas válidas; permisos, frontera de cancelación, rollback y regresión; máximo 3 s según CU. |
| Riesgos / puertas | Aplicar D-03/D-04 y D-05 RESUELTA PARA FASE 4; cero bloqueadores documentales del núcleo. Evitar snapshot obsoleto, inversión de locks, bypass de permisos y efectos externos antes del commit. |
| Condición de finalización | CU07–CU10 y excepciones probados; nunca dos APROBADAS solapadas del mismo espacio; responsables/fechas e historia preservados. CU30, avisos y evidencia siguen diferidos y no se declaran satisfechos por los campos de reservas. |
| Commit esperado | `feat: add reservation lifecycle and conflict checks` |

### Fase 4.5 — Cierre documental de equipamiento y préstamos

Completada documentalmente: D-02/D-11 resueltas para Fase 5; D-04/D-05 aplicadas sin reabrirlas. Estados, receptor, rechazo motivado, devolución/revisión, incidencias, BAJA, recuperación, doce permisos previstos y orden de locks congelados. [Readiness for Phase 5](DECISIONS.md#readiness-for-phase-5): **0 bloqueantes documentales**. Solo documentación; ninguna migración, modelo, permiso en BD, servicio, interfaz ni cambio funcional de disponibilidad.

### Fase 5 — Equipamiento, préstamos, entrega, devolución e incidencias

**Completada el 2026-10-23 en el núcleo autorizado sin QR.** [Implementación, 262 pruebas / 3318 aserciones y evidencia manual](EQUIPMENT_LOANS_IMPLEMENTATION.md). La tabla conserva el contrato planificado y satisfecho para ese núcleo.

| Campo | Plan |
| --- | --- |
| Módulos afectados | Gestión de Equipamiento y Préstamos; integración CU19 con disponibilidad de espacios |
| Carpetas principales futuras | app/Models/; app/Services/; app/Policies/; app/Http/; database/; routes/; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF9–15; DB-07/09/18/21; R-TR-03; R-PR-01 |
| Casos de uso | CU12–CU19 |
| Trabajo previsto | Implementar el contrato Fase 4.5: inventario con PK compartida; EquipmentAvailabilityService; solicitud/resolución; entrega al solicitante; devolución y revisión sobre la misma fila; incidencias de ambos subtipos y ampliación de SpaceAvailabilityService. Sin QR en esta fase. |
| Pruebas requeridas | T-P01–18 de BUSINESS_RULES: MySQL real, aprobaciones incompatibles, entrega/devolución/confirmación únicas, apertura contra cierre, permisos y rollback; esquema exacto, regresión de reservas/CU19, navegador y <=3 s listados con 1.000 registros. |
| Riesgos / puertas | Puerta documental satisfecha en Fase 4.5: aplicar D-02/D-11 cerradas y D-04/D-05 vigentes. Ámbito fino diferido; no inventar asociaciones/estados. QR en Fase 6 no bloquea entrega/devolución sin QR del núcleo de Fase 5; no declarar probada esa extensión. |
| Condición de finalización | Núcleo CU12–CU19 sin QR probado; T-P01–18 y regresión de Fase 4 aprobadas, evidencia funcional/visual y rendimiento documentada. Integraciones QR/Blockchain/RF21/CU30 pendientes visibles; esquema, estados y responsables preservados. |
| Commit esperado | `feat: add equipment loans returns and maintenance` |

### Fase 6 — Códigos QR y validaciones

Implementación disponible, 325 pruebas/4984 aserciones aprobadas. [Evidencia y aceptación física y su límite de evidencia](QR_IMPLEMENTATION.md). El plan siguiente conserva los criterios exigidos.

| Campo | Plan |
| --- | --- |
| Módulos afectados | Validación QR transversal |
| Carpetas principales futuras | app/Services/; app/Http/; app/Policies/; app/Models/; database/; routes/; resources/js/Pages/ y Components/; tests/ |
| Requisitos relacionados | RF8/13/14; IH-02/03; RD-08; R-TR-04; R-CC-04 |
| Casos de uso | CU11/CU20; extensiones de CU17/CU18 |
| Trabajo previsto | Aplicar D-09 resuelta en Fase 5.5: token/hash y ciclo exactos, gestión/rotación, lectura web local, validación contextual servidor y correlación atómica en el mismo request; extensión explícita de entrega/devolución, sin obligación global. |
| Pruebas requeridas | Cámara ausente/denegada; código falso/caducado/ajeno; permiso insuficiente; reintentos; inválido no muta; <=3 s y >=30/min. |
| Riesgos / puertas | Tratar QR como permiso; cambios silenciosos a UNIQUE/CHECK; exponer tokens; sustituir web por Flutter. |
| Condición de finalización | CU11/CU20 y ramas QR de entrega/devolución aceptados; contexto y persistencia consistentes. |
| Commit esperado | `feat: add contextual QR validation` |

### Fase 7 — Sensores IoT, eventos y monitoreo

**Implementada y validada:** 371 tests / 5.862 aserciones, revisión manual y ensayo local de carga/reflejo. La tabla conserva el plan de referencia; [resultados finales y límites](IOT_IMPLEMENTATION.md#cierre-de-verificación).

| Campo | Plan |
| --- | --- |
| Módulos afectados | Monitoreo IoT y Disponibilidad |
| Carpetas principales futuras | app/Services/; app/Http/; app/Models/; config/; routes/; database/; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF16–17; IH-04–08; IS-11; IC-06/07; DB-06/10/23/27; RD-16/40; R-CC-05; R-PR-02 |
| Casos de uso | CU21–CU22 |
| Trabajo previsto | Implementar D-10 cerrado en Fase 6.5: catálogo/asociación/provisión segura; REST/HMAC; recepción validada/deduplicada, historial y monitoreo conservador. MQTT no requerido; CU23 fuera de Fase 7. |
| Pruebas requeridas | T-I01–T-I32 de BUSINESS_RULES: autenticación, tipos/fechas, historia/frescura, RBAC, secretos y carreras en procesos MySQL reales; >=120 eventos/min sin pérdida aceptada y reflejo <=5 s. |
| Riesgos / puertas | Inferir libre de silencio; identificar personas; confundir fecha de evento/recepción; replay dentro de ventana, snapshot previo al lock y mezcla entre frescura UI/política. |
| Condición de finalización | Eventos válidos persistidos y consultables; fallos aislados; monitor distingue frescura y no determinable. |
| Commit esperado | `feat: add IoT sensors occupancy ingestion and monitoring` |

### Fase 8 — Políticas de liberación

**Preparada documentalmente en Fase 7.5, no iniciada.** [Readiness for Phase 8](DECISIONS.md#readiness-for-phase-8): D-08 resuelta, 0 bloqueantes; requiere nueva instrucción de implementación.

| Campo | Plan |
| --- | --- |
| Módulos afectados | Monitoreo IoT y Disponibilidad |
| Carpetas principales futuras | app/Services/; app/Models/; app/Http/; database/; config/; rutas/tareas convencionales Laravel según versión; resources/js/Pages/; tests/ |
| Requisitos relacionados | RF18; DB-08; RNF-26; R-PR-01 |
| Casos de uso | CU23; extensión de CU22 |
| Trabajo previsto | Implementar D-08 cerrada en 7.5: tres tablas exactas, gestión de versiones/asignación, cuatro permisos, servicio interno/scheduler y consulta autorizada de evaluaciones; matriz QR/IoT, veto, evidencia e idempotencia. |
| Pruebas requeridas | T-L01–T-L36, 48 resultados de matriz, MySQL multiproceso con NOWAIT/snapshot/empate de segundos, regresión de 371 casos y revisión manual. |
| Riesgos / puertas | Liberación indebida; política mutable que cambia historia; ausencia de eventos impide disparador. |
| Condición de finalización | Cada resultado del enum probado; ALERTA no libera; AUTOMATICA solo con condiciones suficientes; historia de evaluación íntegra. |
| Commit esperado | `feat: add evidence based reservation release policies` |

### Fase 8.5 — Cierre de D-12

**Completada documentalmente el 2026-10-25.** [Readiness for Phase 9](DECISIONS.md#readiness-for-phase-9): 0 bloqueantes documentales. SHA-256/JCS, cuatro esquemas, privacidad, Sepolia, contrato mínimo, recuperación, CU25/RBAC y pruebas congelados. Excepción autorizada solo para ON UPDATE de fk_registro_actor. Sin código, migraciones, permisos, wallet, contrato o configuración nuevos.

### Fase 9 — Trazabilidad Blockchain

**No iniciada; requiere una nueva instrucción expresa.** Preparación técnica y aceptación siguientes no se presentan como realizadas.

| Campo | Plan |
| --- | --- |
| Módulos afectados | Trazabilidad Blockchain; puntos críticos CU09/CU10/CU17/CU18, sin CU23/LIBERADA |
| Carpetas principales futuras | app/Models, Services, Policies, Http y Console; database/migrations y seeders de tres permisos; config; resources/js/Pages; tests; blockchain/contracts para source público |
| Requisitos relacionados | RF19–20; RNF-17/19; IS-09/10; IC-09; DB-11/24; RD-17/20/41/42; R-DISP-06; R-PR-03/04 |
| Casos de uso | CU24/CU25; subconjunto de escritura crítica de registros_actividad, sin CU30 general |
| Trabajo previsto | Aplicar D-12 cerrado: dos tablas SQL exactas y una FK autorizada; actividad en transacción; JCS/SHA-256 histórico tras commit; evidencia/reconciliación; driver Sepolia; Registry idempotente; UI autorizada y cuatro resultados |
| Preparación técnica | Verificar y fijar dependencias compatibles, licencia/versión estable de Solidity/compilador, ABI/bytecode y deployment; wallet exclusiva testnet, RPC secreto y saldo de prueba; ninguna clave en repositorio |
| Pruebas requeridas | T-B01–T-B40 de BUSINESS_RULES: suite normal offline/fake, MySQL aislado, EVM local controlado, privacidad, fallos, recuperación, concurrencia, historia, permisos, <=3 s local; regresión de reservas/préstamos/QR/CU23; live Sepolia separado |
| Riesgos / puertas | Documentación cerrada; no Mainnet, no PII externa, no rollback por RPC, no locks de negocio durante red, no snapshot/columnas extra, no backfill ni CU30 anticipados |
| Condición de finalización | Cuatro acciones y exclusiones probadas, evidencia única y recuperable, verificación reproducible, secretos protegidos; registrar evidencia real y límites de integración sin confundir mocks con live |
| Commit esperado | `feat: add Blockchain evidence generation and verification` |

### Fase 10 — Notificaciones

| Campo | Plan |
| --- | --- |
| Módulos afectados | Servicios de Notificación y Asistencia |
| Carpetas principales futuras | app/Services/; app/Models/; app/Http/; database/; recursos de interfaz Vue; tests/ |
| Requisitos relacionados | RF21; DB-08/09/28; R-PR-07 |
| Casos de uso | CU26 y productores CU07–CU10, CU14–CU19, CU23; detector de vencimientos RF21 |
| Trabajo previsto | Contrato N1–N9 cerrado en Fase 9.5: 17 acciones, 8 tipos, SQL exacto, grupos por permisos, lote post-commit, recuperación de 24 h y CU26 propio. |
| Pruebas requeridas | T-N01–T-N40 y regresiones; carreras MySQL, límites de recuperación, privacidad, lectura y enlaces. |
| Riesgos / puertas | Enviar antes de commit; fuga por destinatario; introducir email/SMS no especificados. |
| Condición de finalización | Avisos internos consistentes y aislados; consulta personal y estado de lectura probados. |
| Commit esperado | `feat: add internal operation notifications` |

### Fase 11 — Dashboard, indicadores y reportes

**Implementada y verificada por instrucción de Fase 11: 747 tests / 9111 assertions.** [Cierre contractual Fase 10.5, 0 bloqueantes documentales](DECISIONS.md#dashboard--reporting-contract--readiness-for-phase-11).

| Campo | Plan |
| --- | --- |
| Módulos afectados | Reportes e Indicadores |
| Carpetas implementadas | app/Services/Reporting; app/Services/DashboardService; app/Http; resources/js/Pages/Dashboard y Reports; config/reporting; tests/Feature |
| Requisitos relacionados | RF22–23; IU-06/07; IS-12/13; RD-09/22; R-TR-05/06; R-UR-03/06; R-PR-05/06 |
| Casos de uso | CU27–CU28; CU30 permanece NO IMPLEMENTADO |
| Trabajo previsto | Materializar DR1–DR12: 10 tarjetas/6 gráficos administrativos y 7 personales; siete reportes whitelist, preview/PDF/XLSX comunes; auditoría mínima de exportación |
| Permisos previstos | dashboard.ver para cuatro roles; reportes.ver/reportes.exportar solo Admin/Encargado, además de permisos/scopes de dominio; sin bypass |
| Arquitectura | DashboardService; ReportRegistry/Definition, QueryService y ExportService o equivalentes; fuente MySQL operacional; sin tablas BI/cache compleja |
| Evaluación de dependencias | Antes de instalar, comparar compatibilidad PHP 8.5/Laravel/Vue, mantenimiento/licencia, auditorías, streaming/memoria y bundle; procesamiento local, sin CDN/servicio externo |
| Límites iniciales | 10.000 filas, 30 s servidor, 64 MiB salida/temporal, TTL huérfanos 3600 s; configurables y medidos, sin límite arbitrario de fechas |
| Pruebas requeridas | T-DR01–T-DR48 y regresiones; <=5 s dashboard; <=30 s PDF/XLSX hasta 10.000 filas; otros usuarios operan; revisión visual y formula injection |
| Riesgos / puertas | Fuga en métricas/archivo; cambio de permisos; confundir stock/historia, 0/indeterminado, estado/disponibilidad o recencia/ocupación; RAM/temporales; no ampliar CU30 |
| Esquema e índices | No nuevas tablas de negocio; índices solo tras EXPLAIN/medición y propuesta documentada; cualquier tabla exige justificación previa |
| Condición de finalización | Catálogo contractual completo, controles y mediciones verificadas, auditoría no crítica correcta; archivos locales seguros; CU30 declarado pendiente |
| Commit esperado | feat: add role dashboards and PDF Excel reports |

### Fase 12 — Chatbot

**Lista para comenzar mediante nueva instrucción; NO iniciada.** [Contrato CH1–CH12 cerrado en Fase 11.5, 0 bloqueantes documentales](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12).

| Campo | Plan |
| --- | --- |
| Módulos afectados | Servicios de Notificación y Asistencia |
| Carpetas principales futuras | app/Services/; app/Http/; config/; resources/js/Pages/ y Components/; tests/ |
| Requisitos relacionados | RF24; IU-09; IS-07/08; IC-08; RD-15/42; R-TR-07 |
| Casos de uso | CU29 |
| Trabajo previsto | Ejecutar CH1–CH12: OpenAI Responses/Luna configurable, provider/fake, conocimiento local por permisos, salida/rutas estrictas y UI efímera; solo orientación. |
| Pruebas requeridas | T-CB01–T-CB50; fake provider y fake HTTP sin Internet; MySQL espacio_u_testing y baseline 747 conservada; smoke externo separado/condicional. |
| Riesgos / puertas | Verificar modelo/SDK/política oficial al iniciar; minimizar texto libre; injection/alucinación y fallo externo; clave/cuota solo para smoke real, no para tests. |
| Condición de finalización | Asistencia acotada, proveedor aislado y navegación operativa durante caída; sin funciones protegidas ejecutadas por el bot. |
| Commit esperado | `feat: add scoped advisory chatbot` |

### Fase 13 — Auditoría web integral, seguridad, pruebas y rendimiento

| Campo | Plan |
| --- | --- |
| Módulos afectados | Todos los paquetes web |
| Carpetas principales futuras | tests/; ubicaciones convencionales de herramientas de QA; docs/; correcciones en carpeta propietaria |
| Requisitos relacionados | Todos los RF web y requisitos aplicables; RF25; RNF; R-TR/R-CC/R-UR/R-DISP/R-ESC/R-PR |
| Casos de uso | CU01–CU30; sin cliente móvil |
| Trabajo previsto | Completar bitácora transversal; recorrer flujos y excepciones; revisar modelo final, permisos, concurrencia, privacidad, accesibilidad práctica y rendimiento; reconciliar cobertura. |
| Pruebas requeridas | Todas las pruebas T-CU; datasets R-CC; doble volumen R-ESC; 30 concurrentes; 120 req/min API; revisiones negativas y fallos externos; secretos y archivos. |
| Riesgos / puertas | Declarar cubierto móvil; medir promedios en vez de máximos; usar datos reales; cerrar puertas pendientes sin evidencia. |
| Condición de finalización | Cada requisito web tiene evidencia o brecha explícita por resolver; pruebas críticas pasan y no quedan fallos incompatibles con entrega; se conserva lista móvil diferida. |
| Commit esperado | `test: validate integrated web workflows security and performance` |

### Fase 14 — Docker y preparación de despliegue

| Campo | Plan |
| --- | --- |
| Módulos afectados | Infraestructura de todos los paquetes |
| Carpetas principales futuras | Dockerfile y .dockerignore en raíz; compose convencional si corresponde; config/; .github/workflows/ si se incorpora CI; docs/ |
| Requisitos relacionados | RNF-18/22/30/31/32/37; RD-36–39/43/44; IC-01/02; R-DISP-01–05; R-ESC-01/05 |
| Casos de uso | Soporte operativo de CU01–CU30 |
| Trabajo previsto | Construcción reproducible; preparación Cloud Run + Cloud SQL MySQL + Artifact Registry; configuración privada, sesiones/tareas/archivos, respaldos y recuperación; guía de operación. |
| Pruebas requeridas | Imagen sin secretos; arranque/healthcheck; HTTPS; conexión segura; varias instancias; respaldo diario con siete retenidos; restauración objetivo <=4 h; procedimiento de disponibilidad. |
| Riesgos / puertas | Motor Docker inactivo; almacenamiento efímero; credenciales embebidas; costes/recursos externos; no confundir preparación con producción desplegada. |
| Condición de finalización | Imagen y configuración de despliegue verificadas, guía y ensayos disponibles; publicar/desplegar recursos solo dentro de la instrucción de esa fase; objetivos operativos con evidencia apropiada. |
| Commit esperado | `build: prepare Docker and Google Cloud deployment` |


## Propuesta histórica anterior a Fase 3

Esta propuesta corresponde al cierre de Fase 2. Fase 3 fue posteriormente autorizada e implementada, con RBAC por módulo/acción y ámbito fino diferido según su instrucción §35. La continuación vigente es Fase 4, cuyo contrato se cierra en Fase 3.5 al final de este documento.

## Preparación de Fase 1 y puertas posteriores

[Readiness for Phase 1](DECISIONS.md#readiness-for-phase-1): **0 contradicciones bloqueantes**. Los identificadores, enums, fuentes, actores y cardinalidades son implementables documentalmente. No falta una decisión de dominio para inicializar el framework. D-15 tiene selección compatible de versiones y MySQL completada en Fase 1; solo Docker sigue pendiente antes de los ensayos de Fase 14. La autenticación web y RBAC quedaron definidos en Fase 1.5; no se adelantan CU. [Readiness for Phase 2](DECISIONS.md#readiness-for-phase-2): **0 bloqueantes para el núcleo web de seis tablas**.

| Decisión / detalle restante | Límite antes de trabajo dependiente |
| --- | --- |
| D-04, alcance fino por recurso DIFERIDO EN PARTE; RBAC cerrado | Fase 3 autoriza CU05/CU06 por módulo/acción; Fase 3.5 precisa CU07–CU10 y Fase 4.5 aplica el mismo criterio a CU12–CU19 por módulo/acción y propiedad. Las asignaciones finas siguen diferidas, sin nuevas tablas inferidas |
| D-07, móvil/API y almacenamiento distribuido; sesión web cerrada | Móvil/API cuando se autorice; despliegue distribuido antes de Fase 14 |
| D-05, reservas resueltas y aplicación a préstamos confirmada en Fase 4.5 | [inicio, fin), exclusividad APROBADO/ENTREGADO, locks/lecturas actuales; sin reapertura. FINALIZADA automática de reservas sigue diferida |
| D-09, resuelta en Fase 5.5 | Implementar el contrato cerrado en Fase 6; parametrización global de obligatoriedad fuera de ese alcance |
| D-10, RESUELTA PARA FASE 7 en 6.5 | Contrato REST implementado y verificado en Fase 7; T-I01–T-I32 cubiertos, con límites de entorno documentados |
| D-08, RESUELTA PARA FASE 8 en 7.5 | Disparador, matriz, versiones, evidencia, idempotencia, locks y permisos cerrados; cero bloqueantes documentales |
| D-12, canonicalización exacta, algoritmo, proveedor, operaciones críticas y recuperación | Antes de Fase 9 |
| D-16, protocolo de medición y retención operativa | Piloto sin purga automática según 6.5; retención productiva antes de operación en 14; medición antes de aceptación en 13; zona operativa ya fijada |
| D-17, mecanismos transaccionales y guardas de servidor | Al implementar el componente de Fases 2–10; sin modificar constraints |

D-13/D-14 no requieren desarrollar las funciones históricas/ilustrativas. Los estados RESUELTA/CONVENCIÓN describen contratos. Las pruebas de fundación de Fase 1 están completadas; el núcleo web de Fase 2 tiene 65 pruebas / 493 aserciones aprobadas. Las pruebas de módulos posteriores y rendimiento siguen pendientes.

Evidencia de Fase 2: [SECURITY_IMPLEMENTATION.md](SECURITY_IMPLEMENTATION.md). Las pruebas no acreditan la bitácora pendiente ni amplían el alcance de Fase 3.

## Cierre documental histórico de Fase 3.5 y puerta de Fase 4

D-05 está **RESUELTA PARA FASE 4**: exclusividad solo de APROBADA, pendientes coexistentes, cuatro transiciones, cancelación estrictamente antes del inicio con motivo opcional, consulta/permisos y código estable definidos. El [Readiness for Phase 4](DECISIONS.md#readiness-for-phase-4) registra cero bloqueadores documentales para el núcleo.

Fase 4 podrá comenzar con una nueva instrucción. Deberá implementar y verificar el contrato, conservar SQL e índices oficiales y usar exclusivamente testing MySQL aislado en las pruebas con escritura. La suite obligatoria incluye dos procesos reales y comprobación de lectura vigente después de la espera; no basta un test secuencial.

No se adelantan scheduler/cron de FINALIZADA, liberación automática, CU11/QR (6), IoT (7), CU23 (8), Blockchain (9), notificaciones (10) ni registros_actividad/CU30 (pendiente de autorización de alcance). Su ausencia no bloquea el núcleo acordado y sus obligaciones siguen visibles en trazabilidad. La entrega 3.5 modifica únicamente documentación; no crea la tabla reservas, permisos, servicios, pantallas ni tests funcionales de Fase 4.

Validación documental de Fase 3.5: diez archivos Markdown, ningún archivo funcional modificado, veinte enlaces locales añadidos comprobados y diff sin errores de espacios. SQL de reservas equivalente entre Word/PDF; SHA-256 de ambos originales intacto. Escaneo privado de los 175 archivos públicos, diferencias e historial sin hallazgos de secretos; .env/.env.testing y runtime local siguen excluidos. No se ejecutaron nuevas pruebas funcionales ni migraciones: T-R01–17 permanecen como aceptación obligatoria de Fase 4.

## Continuidad vigente después de Fase 4.5

Siguiente alcance: Fase 5/CU12–CU19, únicamente tras nueva instrucción. El cierre documental de Fase 4.5 satisface sus decisiones: D-02/D-11 resueltas y D-04/D-05 aplicadas. La revisión de decisiones que recomendaba el informe de Fase 4 ya está atendida aquí; ese informe conserva su contexto histórico. [Cierre funcional de reservas](RESERVATIONS_IMPLEMENTATION.md). No se inicia Fase 5 ni se ejecutan pruebas funcionales nuevas en Fase 4.5.

Validación del cierre documental: nueve Markdown afectados; RF/CU, diagramas y seis tablas SQL reconsultados; modelos físicos sin alteraciones. Se verificaron consistencia y trazabilidad, 99 enlaces locales incluidos sus anchors, doce permisos previstos y dieciocho criterios futuros; diff sin errores de espacios. El escaneo privado de 201 archivos públicos, índice, diferencias e historial no encontró secretos; .env/.env.testing siguen ignorados y .env.example no contiene credenciales. T-P01–18 son obligaciones futuras y no resultados de este cierre. Los originales Word/PDF permanecen intactos.

## Cierre vigente de Fase 5 y puerta de Fase 6

Completado el núcleo CU12–CU19 y RF9–RF15 conforme a Fase 4.5, con ampliación de CU06/CU07/CU09. Seis migraciones incrementales, 12 permisos nuevos, 65 pruebas añadidas, total 262 / 3318 aserciones sin fallos/errores/omitidas. QA manual de 28 pasos, cuatro tamaños, teclado y consola completada; build/Pint/auditorías correctos. [Informe y trazabilidad](EQUIPMENT_LOANS_IMPLEMENTATION.md).

Siguiente alcance recomendado, sujeto a nueva instrucción: Fase 6 — Códigos QR y validaciones. La necesidad de cerrar D-09 quedó atendida en Fase 5.5; la implementación futura comprende CU11/CU20 y extensiones de CU17/CU18 conforme al plan, preservando transacciones, RBAC y constraints. No se inició CU20 ni ningún componente QR. Fases 6–14 siguen pendientes.

## Cierre documental de Fase 5.5 y Readiness for Phase 6

D-09 **RESUELTA PARA FASE 6**, cero bloqueantes documentales para CU11/CU20 y las extensiones explícitas de CU17/CU18. [Formato/hash, ciclo, permisos, correlación, privacidad y concurrencia cerrados](DECISIONS.md#readiness-for-phase-6). Se revisaron Word/PDF, RF8, CU y diagramas; SQL intacto. Fase 5.5 solo modifica documentación.

La próxima instrucción podrá autorizar Fase 6. Implementará únicamente las dos tablas oficiales, modelos/enums correspondientes, tres permisos previstos, gestión de QR y validación contextual con cámara local, integración atómica y T-Q01–24 más regresión. Seleccionar/auditar librería mínima compatible durante esa fase; nunca enviar tokens/frames a proveedores. Sin fuente de política global, la obligatoriedad configurable sigue diferida y CU20 es extensión explícita. No se han creado tablas/permisos/rutas/UI ni instalado dependencias; Fases 6–14 permanecen no iniciadas.

Verificación de Fase 5.5: doce Markdown afectados, 166 enlaces locales incluidos anchors válidos y 24 criterios T-Q futuros; git diff --check correcto. Diccionario de ambas tablas QR sin cambios y SQL Word/PDF equivalente; huellas originales intactas. Escaneo privado de 272 archivos públicos, índice, diferencias e historial sin hallazgos; plantillas/exclusiones correctas. Lectura de metadatos de desarrollo: 21 tablas académicas más migrations, 33 permisos, cero tablas/permisos QR. Ninguna migración, seed, suite funcional o captura de cámara ejecutada en este cierre; 262 pruebas/3318 aserciones siguen siendo evidencia histórica de Fase 5. Rama documental docs/phase-5.5-qr-decisions desde main/origin/main af80471, con publicación e integración sin force push.

## Avance vigente de Fase 6

La instrucción posterior autorizó la implementación sobre feature/phase-6-qr desde 08baa06. Dos tablas QR, tres permisos, lifecycle, CU11/CU20, extensiones CU17/CU18 y cámara local realizados; regresión sin QR conservada. Excepción de siete FK aprobada. Suite 325/4984 y revisión interactiva documentadas; el usuario resolvió el cierre manual aceptando expresamente la lectura física sin una nueva fila válida independiente. Se permite la integración final, conservando ese límite de evidencia. En ese cierre, Fase 7 no estaba iniciada y quedaba pendiente D-10, resuelta después en Fase 6.5 según el apartado siguiente. Las secciones Fase 5/5.5 anteriores reflejan su momento histórico.

## Cierre documental de Fase 6.5 y puerta de Fase 7

[Readiness for Phase 7](DECISIONS.md#readiness-for-phase-7): D-10 RESUELTA PARA FASE 7, **0 bloqueantes documentales**. Fase 7 podrá comenzar con nueva instrucción; no se inicia en esta entrega. REST/HMAC, payload, derivación/provisión, firma, reloj, frescura, duplicados, carreras, permisos y privacidad congelados. Sin broker obligatorio ni código/esquema/configuración/permisos DB modificados.

Plan futuro CU21/CU22: implementar las dos tablas del SQL sin cambios, administración con tres permisos previstos, acción controlada de provisionamiento, ingestión única por dispositivo y consulta paginada. Conservar bitácora general/CU30 en su alcance diferido; logs técnicos no acreditan esa parte de RF16. CU06 solo informativo; no implementar CU23, liberación o cambios de estado operativos. Fase 8 mantiene max_antiguedad_lectura_min y D-08.

Antes de aceptar Fase 7 ejecutar T-I01–T-I32 con MySQL de testing aislado, incluyendo dos procesos sincronizados y regresión del núcleo; ensayos de rendimiento/continuidad sin adjudicarlos ahora. Los resultados 325/4984 pertenecen a Fase 6 y no se volvieron a ejecutar en esta entrega documental. Control actual: revisión de documentos, enlaces, diff, alcance y secretos, conservando hashes originales.


## Implementación de Fase 7

La nueva instrucción autorizó CU21/CU22 y el complemento IoT de CU06. Dos migraciones exactas, tres permisos, REST/HMAC RAW, provisión controlada, deduplicación serializada por sensor y monitoreo conservador están implementados. Se preservan 325 tests previos y se añaden 46; los resultados y el cierre operativo están en [IOT_IMPLEMENTATION.md](IOT_IMPLEMENTATION.md). Fase 8 no iniciada: requiere nueva instrucción y revisión de D-08/contrato de liberación; no hay scheduler ni tablas de evaluación.

## Cierre documental de Fase 7.5 y puerta de Fase 8

La nueva instrucción cerró D-08, sin implementar CU23. [Readiness for Phase 8](DECISIONS.md#readiness-for-phase-8): **0 bloqueantes documentales**. Plan futuro: tres migraciones exactas de evaluación, gestión de versiones/asignación y cuatro permisos, consulta administrativa/propia, servicio transaccional y scheduler interno por lotes. La matriz, ventanas, veto, idempotencia y protocolo NOWAIT están congelados; T-L01–T-L36 especifican aceptación pendiente con regresión de Fase 7.

No se adelantó ningún archivo funcional, interfaz, configuración, permiso DB ni cambio de datos. Fase 8 puede comenzar con otra instrucción expresa. No incluir FINALIZADA natural, liberación manual, notificaciones, Blockchain ni CU30. Esta conclusión sustituye los pendientes D-08 del cierre histórico de Fase 7, sin reescribir su evidencia.

## Entrega de Fase 9 y continuación

RF19/CU24 y RF20/CU25 implementados conforme a D-12, con pruebas Laravel, RFC8785, contrato e integración local separadas. [Resultados y pendientes externos](BLOCKCHAIN_IMPLEMENTATION.md). Sepolia real requiere testETH para wallet dedicada; no se acredita deployment inexistente. Excepciones autorizadas: serializer JCS Node y vulnerabilidades Zod exclusivamente dev, además de la FK ya aprobada. No se amplía a CU30 general.

Recomendación siguiente: una instrucción de cierre documental para RF21/CU26 que concrete eventos y destinatarios de avisos internos, vínculo actividad/notificación, idempotencia después del commit, lectura/acceso propio, recuperación ante fallo y retención. No introducir email/SMS/push sin autorización. No se inició Fase 10.

## Cierre de Fase 9.5 y puerta de Fase 10

**Fase 9.5 documental completada:** [Notification Contract / Readiness for Phase 10](DECISIONS.md#notification-contract--readiness-for-phase-10). No había D específico de notificaciones; no se crea D-13. Bloqueantes documentales para Fase 10: **0**. El siguiente trabajo requiere nueva instrucción; Fase 10 no está iniciada.

Secuencia futura verificable, sin ejecutarla en este cierre:

1. Materializar exactamente notificaciones (8 columnas, 2 FK y los índices oficiales) y el permiso notificaciones.ver_propias para los cuatro roles; conservar esquema de actividad y D-12.
2. Incorporar solo trece productores no críticos N3, reutilizando las cuatro actividades críticas; cubrir camino QR/normal y atomicidad de incidencia derivada/CU23. Añadir detector de vencimiento único sin nuevo estado.
3. Implementar registro central de reglas y servicio de lote atómico por actividad, plantillas, destinatarios efectivos y generación post-commit independiente de Blockchain.
4. Fijar corte inicial de habilitación RF21 en configuración de despliegue; reconciliación cada minuto configurable, lotes de 100 y ventana de 24 h. Probar cierre administrativo, cero receptores y reparación directa limitada antes de declarar recuperación correcta.
5. Implementar CU26 propio: contador, listado de 15 por página, filtros, detalle, primera lectura POST/PATCH y enlaces reautorizados; navegación Inertia, responsive y teclado.
6. Ejecutar T-N01–T-N40, regresiones pertinentes, verificaciones de seguridad/rendimiento y aceptación visual. Documentar resultados reales y límites; publicar/integrar solo bajo la instrucción de Fase 10.

No implementar email/SMS/WhatsApp/push/Firebase, infraestructura realtime, tabla outbox/snapshot, UNIQUE adicional, avisos técnicos Blockchain ni CU30 general. La recuperación no garantiza reconstruir un conjunto administrativo perdido tras un lote confirmado: el esquema y la estrategia N6 lo delimitan. El smoke Sepolia real continúa pendiente por testETH, independiente y fuera de 9.5; no usar wallet personal ni fondos reales. No reabrir auditorías/dependencias como parte de este cierre documental.

## Cierre y continuación — Fase 10

RF21/CU26 implementados según N1–N9: SQL exacto, 17 acciones, destinatarios efectivos, lote atómico, reconciliación 24 h, detector y UI. Total 656 / 8721 en 788,96 s. CU30 general diferido. Fase 11 no iniciada: cerrar primero RF22/CU27 y RF23/CU28 (fórmulas, permisos, filtros, PDF/Excel, privacidad, vacíos y rendimiento); implementar solo tras nueva instrucción. [Implementación y evidencia](NOTIFICATIONS_IMPLEMENTATION.md).

## Cierre documental de Fase 10.5 y continuación

RF22/CU27 y RF23/CU28 cuentan con catálogo, fórmulas, RBAC, filtros/columnas, PDF/XLSX, auditoría, seguridad y rendimiento congelados en DR1–DR12. No se inició Fase 11 ni se instaló biblioteca, ejecutó migración, creó permiso o produjo REPORTE_EXPORTADO. La siguiente instrucción podrá autorizar su implementación en este orden: evaluar herramientas locales; capacidades y queries centrales; dashboard por rol; reportes y preview; renderizadores/temporales/auditoría; T-DR y regresiones/mediciones/revisión visual; publicación con evidencia real. Mantener CU30 separado.

## Ejecución Fase 11

Dashboard por cuatro roles y siete reportes implementados según DR1–DR12 y precisiones explícitas de la instrucción Fase 11. [Servicios, trazabilidad, controles y pruebas](DASHBOARD_REPORTING_IMPLEMENTATION.md). Sin nuevas migraciones/tablas; índice no justificado por mediciones. CU30 sigue NO IMPLEMENTADO; Fase 12 NO iniciada. El contrato orientativo D-14/RF24/CU29 quedó cerrado posteriormente en Fase 11.5; implementar Fase 12 requiere nueva instrucción.


## Cierre documental de Fase 11.5 y continuación

Contrato completo en [Readiness for Phase 12](DECISIONS.md#chatbot-assistance-contract--readiness-for-phase-12), 0 bloqueantes documentales. Orden futuro: revalidar fuentes oficiales/modelo/SDK/política; incorporar chatbot.consultar y configuración deshabilitada por defecto; provider abstracto/fake y contrato HTTP; conocimiento y rutas autorizados; entrada/salida segura, límites y errores; UI sin persistencia; T-CB/regresión/QA/auditorías/secret scan; smoke real sintético solo con clave/cuota; publicación autorizada. Preferir HTTP Client Laravel sin SDK nuevo salvo justificación actualizada.

No configurar claves ni realizar smoke antes de Fase 12. No crear código, rutas, UI, permisos DB, configuración, dependencias o migraciones en este cierre. CU30 NO IMPLEMENTADO, app móvil diferida y smoke Sepolia pendiente independiente. El cierre documental no reejecuta ni renombra como nuevas las pruebas/auditorías de Fase 11, ni elimina la excepción temporal dev Hardhat/Zod de Fase 9.

## Ejecución de Fase 12 y límite de continuación

La instrucción oficial posterior autoriza la implementación RF24/CU29, ahora materializada conforme a CH1–CH12: permiso, provider abstracto/OpenAI/Fake, catálogo/rutas, límites, contexto mínimo, interfaz y pruebas. [Informe y verificación](CHATBOT_IMPLEMENTATION.md). No clave local disponible: smoke real OpenAI pendiente, conforme a sección 126, sin bloquear validación offline ni fingir inferencia. No iniciar fase posterior; CU30 NO IMPLEMENTADO y app móvil diferida. Siguiente acción sugerida: smoke sintético cuando el operador configure credencial/cuota local segura; cualquier ampliación funcional requiere nueva instrucción.

## Fase 12.5 — Cierre documental de RF25/CU30

[Activity Audit Contract / Readiness for CU30](DECISIONS.md#activity-audit-contract--readiness-for-cu30): **cerrada documentalmente, 0 bloqueantes; CU30 NO IMPLEMENTADO**. Incluye 56 acciones (18 existentes/38 nuevas), fallos selectivos, actividad.ver solo Administrador, consulta individual con filtros/detalle/25 por página, inmutabilidad/retención, separación RF23/D-12/N/CH y pruebas T-A01–T-A40.

Implementación futura únicamente tras nueva instrucción: (1) mantener main limpio y revisar AC1–AC15; (2) centralizar escritores conservando los existentes e integrar nuevos hooks transaccionales; (3) permiso incremental y consulta autorizada; (4) pruebas por evento/rollback/concurrencia/privacidad sobre espacio_u_testing y regresión; (5) mediciones/EXPLAIN y QA; (6) documentación/publicación. No atribuir número de fase nuevo ni iniciar esa secuencia ahora. No exportaciones CU30, app móvil o integración de chatbot con auditoría.

Baseline funcional conservado: 877 tests/10.217 assertions de Fase 12; no se ejecutan como si fueran nuevos resultados de esta fase documental. Smoke OpenAI sin clave y Sepolia sin testETH independientes. No cambiar .env, servicios, librerías, permisos DB, índices o datos para cerrar documentos.


## Implementación RF25/CU30 — Fase 12.6

La instrucción posterior autorizó Fase 12.6, insertada antes de Fase 13: recorder/productores, permiso/consulta/UI, pruebas de integridad/privacidad/concurrencia, QA, medición y documentación. No sustituye la QA integral de todo el sistema: Fase 13 permanece NO INICIADA. Tras cerrar esta entrega, la siguiente instrucción puede autorizar ese endurecimiento global; smokes OpenAI/Sepolia continúan separados. [Implementación, evidencia y límites](ACTIVITY_AUDIT_IMPLEMENTATION.md).

## Revalidación integral — Fase 13

CU30 se completó en Fase 12.6; esta fase lo revalida con los otros 29 CU. El trabajo histórico que decía completar bitácora se satisface por esa implementación previa. Fase 13 ejecuta baseline, esquema, seguridad, UI, rendimiento y regresiones cruzadas. Evidencia final/publicación en PHASE_13_SYSTEM_VALIDATION. Fase 14 requiere nueva instrucción: HTTPS/proxies, sesiones/locks/scheduler/almacenamiento según topología y secretos privados. Sin Dockerfile final, nube, CI/CD de despliegue ni móvil.
