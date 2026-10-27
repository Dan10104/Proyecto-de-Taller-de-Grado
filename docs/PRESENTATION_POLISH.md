# Pulido visual de presentación

## Alcance y línea base

Fase exclusivamente visual autorizada el 26/10/2026. Rama `feature/presentation-polish`, base `47f42cf7963b0db691fcc1b6379526be3483f377`; main y origin/main iguales, árbol limpio antes de cambios. No servicios, controladores, modelos, SQL, migraciones, RBAC, integración ni contratos modificables. Todas las cifras/estados continúan viniendo del backend existente.

## Skills: origen, instalación y auditoría

| Skill | Fuente oficial y commit fijado | Licencia | Uso |
| --- | --- | --- | --- |
| Impeccable 4.8.0 | [pbakaus/impeccable](https://github.com/pbakaus/impeccable/tree/414603bf14c7a344edb466bb1f85690c77ce8b7d/.agents/skills/impeccable) | Apache-2.0 | Dirección principal, auditoría, modo Operate, craft floor y polish |
| Taste v2 | [Leonxlnx/taste-skill](https://github.com/Leonxlnx/taste-skill/tree/12af471f627f866411b4bd785b19646d3672784c/skills/taste-skill) | MIT | Jerarquía, composición, densidad y tipografía de app screens |
| Emil Design Engineering | [emilkowalski/skills](https://github.com/emilkowalski/skills/tree/2d228630ba1446365f614a108cb8252f1feda202/skills/emil-design-eng) | MIT | Feedback, duración, easing, foco y movimiento reducido |

Correspondencia contrastada con impeccable.style/docs, tasteskill.dev y emilkowal.ski/skill. Instalación mediante helper oficial skill-installer, sin npx/curl-pipe-shell, en el directorio personal de skills de Codex. No dependencias añadidas al proyecto. Verificados 63/39/1 archivos de texto frente a los commits oficiales (normalizando CRLF del checkout Git de Windows).

Impeccable incluye lanzadores shell/cmd, manifiesto de checksums, un motor descargable y scripts de inspección/overlay; no se ejecutaron ni se activaron hooks. Sus definiciones de agentes quedan como archivos, sin registro adicional ni delegación. Taste incluye scripts Node para sortear composición, descargar imágenes, analizar fuentes y abrir un navegador CDP temporal: no se ejecutan sus herramientas de navegación, servidores ni descargadores. Emil contiene únicamente SKILL.md. Se revisaron imports, destinos de red, escrituras, subprocess y borrados de los scripts; la instalación solo copia archivos. El motor binario de Impeccable no fue auditado ni ejecutado. No se afirma auditoría dinámica de esos ejecutables. El navegador controlado y la revisión directa sustituyen esos inspectores. Se conservan instrucciones de diseño aplicables; se excluyen frameworks ajenos, contenido inventado, cambios funcionales y sugerencias de herramientas fuera de alcance.

## Auditoría previa a cambios

Inventario: 50 páginas Vue, 24 componentes compartidos y AppLayout; login/error/inicio, dashboard, reportes, actividad, notificaciones, asistente, usuarios/roles, espacios/catálogos/disponibilidad/reservas, equipamiento/tipos/préstamos/incidencias, QR, sensores/monitoreo, liberación y trazabilidad. Revisión directa de fuentes y navegador local a 1440×900: login, inicio, dashboard, trazabilidad, IoT, reportes, bitácora y notificaciones.

| Prioridad | Hallazgo comprobado | Tratamiento visual |
| --- | --- | --- |
| P1 | Navegación extensa, veinte destinos y escasa agrupación; destinos fuera del primer viewport | Agrupar enlaces existentes, scroll propio, iconos específicos y ubicación actual |
| P2 | Filtros Blockchain apilados a ancho completo incluso en desktop | Rejilla adaptable que recupera espacio para evidencia |
| P2 | Diez métricas con grandes blancos; segunda fila casi fuera de 900 px | Banda de métricas compacta, cifras tabulares y contextos legibles |
| P2 | Texto secundario/cabeceras/badges de 10–11 px | Escala 12/14/16/28, contraste y separación tipográfica |
| P2 | Sensores y trazabilidad tratados como CRUD genérico | Identidad propia mediante diagrama explicativo, estados y paneles de evidencia |
| P2 | Múltiples estilos locales para filtros, tablas y detalles | Tokens compartidos y consistencia entre superficies |
| P2 | Datos antiguos SYN/QA en sensores, espacios y usuarios | Inventario separado; ninguna sustitución visual engañosa de identidad/historia |
| P3 | Feedback de botones y diálogos poco uniforme | Transiciones breves, foco visible y movimiento reducido |

Se conservan: etiquetas de campos, confirmaciones nativas, escape de contenido, contratos de estado, tablas accesibles, permisos efectivos y rutas. Ninguna certificación WCAG completa se deriva de esta auditoría visual.

## Dirección de diseño

Modo Operate. Composición inspirada en framed-sheet de Taste, seleccionada por el uso académico diario; sin sorteo ni seed ejecutado. Marco neutro claro, superficies blancas, azul institucional existente como acción y teal como acento ambiental; el contraste oscuro se reserva a la introducción de evidencia IoT/Blockchain. Segoe UI Variable/Segoe UI y fallback de sistema locales: no dependencia/CDN ni fuente externa. Espaciado base 4 px; radios 8/12/16 px. La identidad procede de ESPACIO-U/FICCT, recursos, horarios, sensores y evidencias reales, nunca cifras decorativas.

Dashboard responde qué operaciones necesitan atención; Blockchain cómo comprobar la evidencia; IoT qué se recibió y cuándo; reportes qué conjunto se consultará/exportará; actividad quién/qué/cuándo; notificaciones qué aviso requiere lectura. Ningún nuevo agregado ni estado se calcula para decorar.

| Before | After | Why |
| --- | --- | --- |
| Controles sin feedback común | Press breve, hover solo con puntero preciso y foco inmediato | Confirmar interacción sin retrasar tareas |
| Diálogos con apariencia aislada | Superficie y espaciado comunes, entrada discreta | Mantener continuidad y foco |
| Movimiento sin política única | 140–200 ms y prefers-reduced-motion | Evitar distracción y respetar preferencias |

## Implementación visual

`resources/css/presentation.css` concentra la capa visual y se importa después de los estilos existentes; no requiere instalar paquetes. AppLayout agrupa los mismos veinte enlaces en Mi espacio, Gestión académica, Monitoreo y evidencia y Administración, conservando cada condición de permiso y acción. Iconos SVG locales por dominio; foco visible, botones de 44 px en móvil, scroll propio del menú y Escape existente.

Se armonizan login, inicio, formularios, tablas, estados, detalles, QR, operaciones de reservas/préstamos/entrega/devolución, incidencias, liberación, avisos, asistente y administración mediante componentes compartidos. Se retiran cintillos decorativos repetidos; se conservan códigos y contexto útil. El acceso tiene un h1 visible también en móvil. No se modifican acciones ni confirmaciones.

Dashboard: banda de diez indicadores institucionales o siete personales; barras horizontales para distribución y eje temporal sin etiquetas superpuestas. Todas las series/buckets/ceros y las tablas accesibles originales permanecen; la geometría y la frecuencia de ticks son presentación, no nuevas métricas. Fechas completas continúan en la tabla. Gráficos locales SVG, sin biblioteca adicional.

Blockchain e IoT: EvidenceFlow presenta un esquema explicativo de operación–huella–verificación y sensor–medición–espacio, sobre superficies sobrias oscuras. Tarjetas de sensor, comunicación y evidencia; hashes legibles y estados existentes. Los nodos no son un indicador de red activa ni una confirmación externa. No se habilitaron sensores, no se fabricaron lecturas, no se simularon hashes/transacciones y no se cambió el polling existente.

Reportes, bitácora y notificaciones reciben controles, densidad, tablas y jerarquía comunes. «Sin FK de contexto» pasa a «Sin referencias asociadas», manteniendo exactamente la condición y la información. Chatbot conserva aviso de proveedor/privacidad y su comportamiento de memoria temporal. No se hizo una consulta al proveedor externo.

Movimiento: feedback de presión 140 ms, entrada de diálogo 180 ms, hover solo en puntero preciso y prefers-reduced-motion. Se preservan manejo de foco, cancelar, Escape y eventos. La corrección de overflow en sensores posiciona el contenedor de tabla para contener sus textos accesibles: la tabla sigue desplazable y no se ocultan columnas.

## Limpieza aprobada y límites de datos

[Plan y resultado del renombrado](PRESENTATION_DEMO_DATA.md). El usuario aprobó expresamente 17 recursos y 22 usuarios ficticios; operación local el 26/10/2026 a las 23:59 mediante servicios existentes. Solo nombres, con sufijo «Demo» para todos los recursos. Manifiesto previo privado y transacción con verificación de las 32 tablas; cinco hashes canónicos críticos sin cambios; 39 entradas normales de auditoría añadidas.

Se preservaron IDs, códigos, correos, credenciales, estados, permisos, roles, FK, pisos, políticas, reservas, préstamos, incidencias, QR, telemetría, Blockchain, notificaciones e historial. Los textos históricos, tipos, marcas, números de piso y códigos técnicos de QA siguen tal como estaban: no se ocultaron ni sobrescribieron. La propuesta futura de cuatro pisos es documental; no hay carga adicional ni consolidación automática. No se presenta la demo como inventario oficial FICCT.

## Validación final — 27/10/2026

| Comprobación | Resultado |
| --- | --- |
| Regresión MySQL espacio_u_testing | 1.095 tests, 13.046 assertions; 1.384,23 s; todos aprobados |
| Tests existentes de foco de diálogo | 4/4 aprobados, 63 ms; no se alteraron |
| Pint --test | Aprobado |
| npm run build | Aprobado, Vite 8.3.5; sin dependencias nuevas |
| Composer audit | 0 advisories; 0 paquetes abandonados |
| npm audit frontend | 0 vulnerabilidades |
| Blockchain npm audit --omit=dev | 0 vulnerabilidades |
| Blockchain npm audit completo | 5 altas transitivas dev Hardhat/Zod; excepción temporal previa vigente; 0 críticas |
| Preservación del código | 35 scripts Vue modificados visualmente conservan su lógica; 734 directivas conservadas; backend, pruebas, dependencias y contratos sin cambios |
| Escaneo de secretos | Sin hallazgos en worktree, índice, diff, historial y artefactos revisados; .env y archivos privados ignorados |
| Enlaces documentales | Enlaces locales revisados, sin errores |

La comparación excluye de la igualdad geométrica el dibujo de AccessibleChart y la adición de paths de Icon; se revisaron expresamente. El script de datos aprobado vive únicamente en .local/qa/presentation y no es seeder ni parte del runtime. No se escribe ni modifica código de servicios para ejecutarlo.

Revisión manual en navegador integrado: veinte destinos principales de Administrador, dashboard y navegación permitida de Encargado y Docente, login/logout, filtros de periodo, gráficos y tabla alternativa, preview de equipamiento con sus controles PDF/XLSX, formulario de espacio sin enviar, menú móvil y Escape. No se activó una cuenta Estudiante ni se alteró RBAC para la revisión; el dashboard personal usa la misma vista y la cobertura de cuatro roles permanece en la suite existente.

Dimensiones observadas: 1440×900, 1366×768, 768×1024 y 390×844. Recorrido de veinte destinos en móvil; se encontró y corrigió el desbordamiento de sensores. Comprobaciones de las pantallas prioritarias sin overflow global, etiquetas de campos presentes; scroll interno conservado en tablas amplias. Login y dashboard personal comprobados también en móvil/escritorio. Foco y contraste básico revisados; no constituye certificación WCAG ni prueba exhaustiva de lectores de pantalla. Consola de las sesiones revisadas sin errores/warnings.

Build final observado: CSS principal 53,67 kB / 12,60 kB gzip; EvidenceFlow 2,04 kB / 0,81 kB gzip. Estas son medidas locales del artefacto, no un benchmark de producción ni una comparación de rendimiento completa.

Las capturas se inspeccionaron en la herramienta. La escritura directa de PNG en el workspace no estuvo disponible para el navegador; la revisión automática rechazó guardarlos fuera del proyecto. No se guardaron capturas externas ni se presentan imágenes de referencia como evidencia de ejecución.

## Entrega y operación

Rama `feature/presentation-polish`; commit visual `e2de47b` (`style: refine academic interface and evidence presentation`). Informe y estrategia se versionan en un commit documental posterior. Se publica la rama solicitada; main conserva la línea base de Fase 14. Para usar el estilo en otro checkout autorizado, instalar las dependencias existentes y ejecutar `npm run build`; no hay migración, nuevo permiso ni cambio de .env. VS Code mantiene la raíz oficial. El servidor local y MySQL aislado permanecen disponibles.

No se certifica ningún smoke externo nuevo. Las limitaciones previamente documentadas de OpenAI/Sepolia y la excepción dev Hardhat/Zod no desaparecen con esta fase visual. Las fuentes Word/PDF, contratos y lógica del sistema quedan intactos.
