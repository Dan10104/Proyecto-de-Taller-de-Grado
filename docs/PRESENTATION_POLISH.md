# Pulido visual de presentación

La revisión final del 28/10/2026 se documenta al final de este archivo. Las secciones anteriores conservan el resultado histórico del primer pulido y del renombrado autorizado; no describen una nueva limpieza de datos.

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

## Rediseño visual final — 28/10/2026

### Alcance, autoridad y preservación

Continuación de `feature/presentation-polish` desde `74aaf8e5ad44df6777e936db83c8b7e927c6df78`, inicialmente limpia y sincronizada con su remoto. Trabajo en el workspace oficial ESPACIO-U. La nueva instrucción limita esta entrega a frontend/presentación: no se cambia ningún archivo PHP, servicio, controlador, modelo, policy, Form Request, ruta, configuración, dependencia, SQL, migración, contrato ni prueba funcional existente. Main permanece en la línea base de Fase 14.

No se ejecutó otro renombrado, seeder, limpieza de datos ni operación sobre el inventario Demo. IDs, nombres, códigos técnicos, pisos, estados, permisos, relaciones e historial permanecen tal como se recibieron. La revisión del navegador utiliza consultas y vistas; no confirma reservas, modifica sensores, verifica/reintenta Blockchain, exporta reportes ni envía consultas al proveedor del asistente. Login/logout utiliza el flujo existente. La suite escribe únicamente en `espacio_u_testing`.

El backend continúa calculando indicadores, disponibilidad, vencimientos, permisos, ámbitos y evidencia. Los componentes nuevos solo organizan props ya recibidos, dibujan geometría, acortan hashes visibles y mantienen selección local de presentación. No incorporan solicitudes HTTP, métricas, estados de negocio ni persistencia. HMAC, QR, liberación, sesiones, Blockchain, notificaciones, reportes, chatbot, bitácora y todos los CU mantienen sus implementaciones.

### Auditoría y criterio de las tres skills

Se reutilizan las tres instalaciones auditadas anteriormente. Se leen directamente sus guías y el contexto del repositorio. No se ejecuta el lanzador de Impeccable que puede descargar un motor, ni los scripts CDP/descarga de Taste; no se instalan herramientas ni hooks.

| Skill | Aplicación en esta revisión |
| --- | --- |
| Impeccable | Auditoría de las páginas y patrones compartidos; jerarquía, consistencia, menos texto persistente y revisión posterior de dashboard, espacios, IoT, Blockchain y módulos generales en navegador. |
| Taste | Criterio de app screens: densidad de operación, escala tipográfica local, proporción entre navegación/contenido, composición de tarjetas e imágenes. La dirección explícita del usuario (sidebar navy y acentos cyan/violeta) prevalece sobre preferencias estilísticas genéricas de la skill. |
| Emil | Feedback breve de controles, hover discreto de tarjetas/cubos, foco visible inmediato, teclado, reducción de movimiento y ausencia de animación continua que simule actividad. |

No se modifica el funcionamiento para satisfacer una recomendación de diseño. La auditoría identificó sidebar clara sin suficiente contraste jerárquico, contextos extensos bajo diez KPIs, inventario dominado por tabla y esquemas genéricos de IoT/Blockchain sin relación visual con los registros. Se revisaron las fuentes de las páginas y componentes antes de modificar y el resultado real después de cada grupo.

### Sistema visual y comparación por módulo

Sidebar navy `#101f3a`, contenido claro, superficies blancas y azul institucional. Cyan, verde, violeta y ámbar se reservan para apoyo visual y estados apropiados; los estados siempre conservan texto. Tokens de color, radio, sombras y transición en `resources/css/presentation.css`; tipografía Segoe/sistema existente, sin otra familia ni CDN. Se mantiene Tailwind y la misma familia de iconos SVG locales.

| Módulo | Antes → Después | Conservación |
| --- | --- | --- |
| Layout e inicio | Marco neutro y navegación clara → sidebar navy, selección azul, contenido más limpio, controles compactos y accesos con separación consistente. | Mismos destinos, agrupaciones, permisos, usuario y acciones. |
| Dashboard | Explicaciones y desgloses siempre abiertos → diez tarjetas compactas con iconos y contexto desplegable; seis gráficos con acentos coherentes. | Cifras, contextos completos, series, buckets y tablas alternativas intactos; siete tarjetas personales usan el mismo tratamiento. |
| Espacios | Tabla como única entrada → galería con imagen conceptual, nombre, piso recibido, capacidad, tipo, estado, hasta cuatro características y enlace de detalle. | Tabla completa accesible bajo «Ver tabla completa y características»; campos, filtros, paginación, códigos y acciones originales. |
| Disponibilidad / reservas | Aviso largo persistente y resultados solo textuales → explicación desplegable, filtros compactos y fotografía conceptual en las tarjetas existentes. | Consulta al servicio central, horario, restricciones, evidencia IoT y acciones originales. No se añade calendario. |
| Equipamiento | Primera columna plana → identidad con icono coherente, código y nombre; tabla con jerarquía más clara. | Marca, modelo, ubicación, serie autorizada, estado y detalle existentes. |
| Préstamos y detalles de reserva | Historial lineal → guía visual de timeline sobre los elementos ya presentes. | No se inventan eventos, fechas ni transiciones de estado. |
| IoT | Diagrama explicativo genérico → maqueta SVG seleccionable con espacios y sensores recibidos, panel de detalle y fondo navy. | Reglas, polling, filtros, lecturas y tabla histórica conservados. |
| Blockchain | Esquema genérico → cinco nodos conectados de una evidencia concreta, cubos SVG y estado real diferenciado. | Contrato, hashes, registro externo, verificación, listado y acciones conservados. |
| Reportes | Formulario sin guía visual → secuencia Reporte → Filtros → Vista previa → PDF/XLSX, controles y tabla más claros. | Mismos siete reportes, consultas, preview y exportaciones; no se cambia PDF/XLSX. |
| Notificaciones | Lista plana → guía vertical tipo feed, icono y diferenciación de no leídas. | Título, resumen, fecha, lectura y destinos existentes. |
| Bitácora | Tabla administrativa → filtros compactos, cabeceras/badges y detalle con el sistema compartido. | Consulta y detalle CU30 íntegros, sin alterar filtros ni alcance. |
| Asistente | Superficies neutras → toolbar y acentos integrados al azul de ESPACIO-U. | Prompt, envío, historial temporal, privacidad y respuesta sin cambios. |
| Login | Campus ilustrado con CSS → imagen conceptual local y frase breve, formulario blanco de alta legibilidad. | Campos, validación, mostrar contraseña, sesión y credenciales intactos. |

La reducción de texto utiliza `details/summary` nativos para contexto, desglose e interpretación. Se preservan los términos oficiales y las advertencias necesarias; no se recortan descripciones contractuales ni se ocultan datos mediante cambios de permiso. La tabla completa de espacios sigue accesible y las visualizaciones no sustituyen las tablas originales.

### IoT: maqueta y límites de los datos

`IotTwin.vue` recibe exclusivamente `sensores.data`. Agrupa los sensores recibidos por `espacio_actual.piso_id` y por su espacio; dibuja una plataforma y volúmenes mediante SVG. Los botones de piso y la selección de espacio solo cambian el panel visual local. El sensor mantiene su ID, identificador, estado de habilitación y comunicación recibido. El detalle muestra nombre, código, capacidad y estado operativo del espacio, todos los sensores de ese espacio y el componente **IotEvidence existente**, sin reescribir su criterio de vigencia.

El payload actual contiene la referencia `piso_id`, pero no nombre ni número físico del piso. Por eso las etiquetas son «Piso · ref. N». No se presentan los IDs como «Piso 1–4», ni se inventa un edificio de cuatro plantas. La maqueta es esquemática, no un plano físico verificado, y solo representa los sensores/espacios de la página actual. El catálogo/paginación existente sigue disponible. Esta limitación se indica junto al mapa.

La sesión local observada contiene ocho sensores deshabilitados en siete espacios y siete referencias de piso. Un espacio contiene dos sensores; ambos se muestran y se verificaron por teclado. Los pines diferencian habilitado/deshabilitado con leyenda y texto; un pin habilitado no significa online ni ocupación positiva. No se generan temperatura, humedad, CO₂ ni porcentajes. `valor` mantiene su significado genérico. «Ocupación: no determinable» nunca se convierte en «libre»; DISPONIBLE se identifica expresamente como estado operativo. El historial mantiene mediciones antiguas y espacio histórico.

El mapa usa botones nativos para piso y nodos SVG con rol, nombre accesible, foco y Enter/Espacio para elegir espacio. Los títulos muestran identificación y estado; el panel textual y el listado/tabla originales permiten consultar sin depender del mapa, color o hover. La forma y posición de los volúmenes no expresan tamaño real, aforo ocupado ni topología institucional.

### Blockchain: cadena visual y veracidad

`TraceabilityChain.vue` recibe la evidencia del detalle o la primera evidencia del listado filtrado. Sus cinco nodos muestran **Origen → Operación → Huella digital → Registro Blockchain → Verificación**. El origen corresponde a la reserva/préstamo del registro; acción y fecha proceden de la actividad; algoritmo, versión canónica y hash proceden de la evidencia. El listado conserva el enlace al detalle exacto.

Cubos SVG de tres caras, conectores CSS y sombra discreta aportan profundidad sin WebGL ni motor 3D. El hash se abrevia visualmente (inicio/final) con valor completo en el atributo title; el detalle original conserva el hash completo. No se implementa un flujo nuevo de copia ni se elimina información.

PENDIENTE permanece ámbar y «Sin confirmación externa» cuando falta fecha de registro. La verificación se muestra aparte: sin `ultima_verificacion` se indica «Sin verificar», nunca «Válido» por el mero hecho de tener hash o registro. El verde de verificación exige los datos recibidos de una verificación VALIDO. Las cinco evidencias observadas siguen pendientes; no se ejecuta ninguna operación externa durante esta revisión.

Los nodos son etapas de **una** evidencia. No representan bloques criptográficamente enlazados entre operaciones ni prueban conectividad con una red. Esta aclaración permanece visible. No se cambia Solidity, JCS, servicio de registro, worker, reintento, verificación, Sepolia ni reglas de datos canónicos.

### Activos, rendimiento y movimiento

Tres imágenes generadas para demostración mediante Imagegen, guardadas localmente como WebP 1536×1024, calidad 82. No se representan como fotos de inventario oficial ni de la FICCT. El texto alternativo y rótulo visible indican imagen conceptual/Demo. Los nombres de los recursos no cambian.

| Activo local | Tamaño | Descripción de generación |
| --- | ---: | --- |
| `public/images/presentation/campus-demo.webp` | 273.714 bytes | Campus universitario latinoamericano conceptual, arquitectura de ingeniería moderna, concreto claro, vidrio azul, palmeras y luz natural; sin personas, textos ni logos. |
| `public/images/presentation/classroom-demo.webp` | 152.382 bytes | Aula conceptual luminosa con mesas claras, sillas azules, ventanales y pizarra sin escritura; sin personas ni marcas. |
| `public/images/presentation/laboratory-demo.webp` | 209.438 bytes | Laboratorio conceptual de cómputo con monitores oscuros, madera clara, sillas azules y luz natural; sin personas, textos ni logos. |

Total de los tres activos: 635.534 bytes. Las tarjetas cargan imágenes con `loading="lazy"` y `decoding="async"`; la imagen principal de login tiene prioridad alta. Dos recursos visuales se reutilizan como representaciones genéricas según el nombre recibido, sin clasificación persistida ni afirmación de correspondencia fotográfica real.

No dependencias nuevas, motor 3D, canvas continuo, vídeo, fuente externa ni solicitudes de telemetría. Build local: CSS principal **70,80 kB / 16,13 kB gzip**, frente a 53,67 / 12,60 kB de la revisión anterior (+3,53 kB gzip). Chunk TraceabilityChain **5,40 / 2,07 kB gzip**; página Monitoring con maqueta incluida **11,12 / 4,14 kB gzip**; chunk compartido SpaceMedia **4,70 / 1,89 kB gzip**. Son tamaños de artefactos locales, no un benchmark de producción.

| Antes | Después | Motivo (pasada Emil) |
| --- | --- | --- |
| Tarjeta plana | Hover de hasta 3 px, sombra leve, 220 ms | Indicar agrupación interactiva con poco movimiento. |
| Bloques estáticos sin jerarquía | Cube hover de hasta 4 px y contraste de selección IoT | Profundidad y selección sin simular una transmisión. |
| Contextos siempre expandidos | Disclosure nativo, foco visible inmediato | Menos ruido y control por teclado sin espera. |
| Posible movimiento ambiental decorativo | Ningún pulso/animación infinita | No aparentar sensores activos, red disponible o progreso inexistente. |

Transiciones centrales 160/220 ms, hover limitado a puntero preciso. `prefers-reduced-motion: reduce` desactiva animaciones/transiciones mediante las reglas compartidas y elimina traslaciones de tarjetas/cubos. El foco de los nodos IoT conserva contorno blanco y el de controles/enlaces sigue visible. No se altera gestión funcional de modales ni de foco.

### Archivos y clasificación

| Clase | Archivos de esta revisión |
| --- | --- |
| Estilos compartidos | `resources/css/presentation.css` |
| Páginas existentes, solo template/imports visuales | `Auth/Login.vue`, `Dashboard/Index.vue`, `Equipment/Index.vue`, `Iot/Monitoring.vue`, `Reports/Index.vue`, `Spaces/Availability.vue`, `Spaces/Index.vue`, `Traceability/Index.vue`, `Traceability/Show.vue` bajo `resources/js/Pages/` |
| Componentes presentacionales nuevos | `IotTwin.vue`, `TraceabilityChain.vue`, `SpaceMedia.vue`, `SpaceGallery.vue` bajo `resources/js/Components/` |
| Activos locales | Tres WebP bajo `public/images/presentation/` |
| Pruebas frontend nuevas | `tests/Browser/presentation.test.mjs` |
| Documentación | Este informe |

Ningún cambio en `AppLayout` ni en componentes funcionales compartidos en esta revisión. Los estilos afectan también sus páginas consumidoras. Se preservan todas las dependencias y lockfiles.

### Validación final de esta revisión

| Comprobación | Resultado del 28/10/2026 |
| --- | --- |
| `php artisan test` / MySQL `espacio_u_testing` | **1.095 aprobados, 13.046 assertions, 959,79 s**. Ninguna prueba existente eliminada o modificada. |
| `node --test tests/Browser/*.test.mjs` | **11/11 aprobados**, 4 existentes y 7 nuevos; ejecución final 4.315 ms. |
| `npm run build` | Aprobado, Vite 8.3.5, 546 ms de compilación reportados. |
| Conservación de páginas existentes | Scripts de nueve páginas iguales tras retirar únicamente imports presentacionales; **226 directivas/bindings originales conservados**. Solo se retira el bucle decorativo de ventanas CSS del login. |
| Backend, contratos y pruebas originales | Comparación con `74aaf8e`: sin cambios en archivos protegidos y diez documentos normativos. |
| Pint `--test` | **No aprobado por cinco archivos PHP preexistentes**, idénticos por hash de blob a `74aaf8e`; detalle debajo. No se ejecuta autofix sobre backend. |
| Consola | Sin errores ni warnings en la sesión recorrida. |
| Escaneo de secretos | Sin hallazgos en worktree, índice, diff, historial Git y artefactos; archivos privados ignorados. Repetido antes de publicar. |
| Nuevas dependencias | Ninguna; no se atribuye una auditoría nueva de dependencias a esta entrega. La excepción dev Hardhat/Zod anterior sigue independiente. |

Los siete tests nuevos renderizan los componentes Vue reales mediante el compilador/SSR ya instalado, sin API ni BD. Comprueban: marcado explícito de imagen conceptual y enlace original; escape de texto malicioso; referencias reales de piso y estado deshabilitado/no determinable; dos sensores en el mismo espacio y valor cero sin inferir ocupación; ausencia de datos sin inventar espacios; evidencia pendiente sin confirmación; registro y verificación recibidos representados por separado.

Pint señaló `app/Http/Controllers/EquipmentController.php`, `app/Http/Controllers/LoanController.php`, `tests/Feature/EquipmentAvailabilityTest.php`, `tests/Feature/LoanConcurrencyTest.php` y `tests/Feature/LoanHttpTest.php`, por `method_argument_space`, `unary_operator_spaces` y/o `not_operator_with_successor_space`. Sus blobs son exactamente los mismos que al comenzar; tampoco cambian configuración ni versión del formatter. No se conoce la causa de la diferencia con el informe histórico de Pint del 27/10 y no se atribuye a este rediseño. La instrucción actual exige Pint si se toca PHP; no se ha tocado PHP. Se informa el resultado real, sin marcar todo como verde ni modificar pruebas/backend para silenciarlo.

Revisión manual: veinte destinos principales en 1440×900 y 390×844, más módulos prioritarios en 1920×1080, 1366×768 y 768×1024. Sin overflow global en las dimensiones efectivamente observadas; tablas amplias conservan desplazamiento interno. Se inspeccionan visualmente login desktop/tablet/móvil, dashboard (Hoy y 30 días con datos), tabla de gráfico, galería, disponibilidad para intervalo, equipamiento, asistente, bitácora, preview de equipamiento, maqueta y cadena/listado/detalle Blockchain. Se comprueban selección IoT con Enter/Espacio, espacio con dos sensores, apertura/cierre de menú con teclado/Escape y foco visible.

La revisión manual de esta vuelta usa Administrador. Los cuatro roles siguen cubiertos por la suite funcional; no se crean cuentas ni se cambian credenciales/roles para obtener una captura. Las capturas se inspeccionan directamente en el navegador: no se conservan PNG fuera del workspace ni se fuerza una escritura rechazada por el entorno. La preferencia reduced-motion se verifica en CSS; no se cambia la configuración del sistema operativo. Esto no constituye certificación WCAG ni prueba exhaustiva con lectores de pantalla.

### Publicación de la revisión final

Se publican commits separados de sistema visual/módulos, maqueta IoT, cadena Blockchain y documentación/pruebas en `feature/presentation-polish`, sin force push. No se modifica ni integra automáticamente main. El historial Git contiene los identificadores definitivos; el informe final los enumera. Para reproducir, usar las dependencias existentes y `npm run build`; no hay migraciones, nuevos permisos ni cambios de entorno.

La aplicación local y MySQL aislado se dejan en ejecución. No se certifican nuevos smokes OpenAI/Sepolia ni se modifica el estado de esas tareas previas. Resultado de alcance: **cero cambios de lógica funcional, schema, RBAC, contratos o comportamiento de los CU; ninguna modificación adicional de datos Demo o historial**.
