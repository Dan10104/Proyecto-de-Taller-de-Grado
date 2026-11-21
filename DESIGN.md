---
name: ESPACIO-U
description: Señalética de campus aplicada a una herramienta de trabajo; números, pictogramas e insignias orientan antes que el texto.
colors:
  ground: "#f6f8fb"
  surface: "#ffffff"
  surface-sunken: "#f1f4f9"
  surface-raised: "#ffffff"
  line: "#e3e8ef"
  line-strong: "#8592a6"
  ink: "#0e1b2e"
  ink-2: "#4a5a70"
  ink-3: "#66758a"
  primary: "#0b2e6b"
  primary-hover: "#123e8a"
  primary-press: "#082252"
  primary-tint: "#e8f0ff"
  on-primary: "#ffffff"
  link: "#1b4fb8"
  rail: "#0b2e6b"
  rail-hover: "#143a7c"
  rail-active: "#1c4589"
  rail-line: "#1c3f80"
  rail-ink: "#dbe6f8"
  rail-ink-2: "#9db3da"
  accent: "#12b5d9"
  accent-ink: "#0a7790"
  electric: "#2f6bff"
  focus: "#2f6bff"
  success: "#14794a"
  success-solid: "#1e9e61"
  success-bg: "#e7f6ee"
  success-line: "#b9e2cb"
  warning: "#8f5600"
  warning-solid: "#d98a00"
  warning-bg: "#fff3dc"
  warning-line: "#f1d6a1"
  danger: "#b92d28"
  danger-solid: "#c9342f"
  danger-bg: "#fdecea"
  danger-line: "#f3c2be"
  info: "#1b4fb8"
  info-bg: "#e8f0ff"
  info-line: "#c3d5f8"
  neutral: "#4a5a70"
  neutral-bg: "#eef1f6"
  neutral-line: "#d6dde7"
  tech: "#071226"
  tech-panel: "#0c1d3d"
  tech-raised: "#12284f"
  tech-line: "#1c335f"
  tech-ink: "#dce8ff"
  tech-ink-2: "#8aa0c8"
  tech-cyan: "#22d3ee"
  tech-blue: "#3d7bff"
  tech-green: "#34d399"
  tech-violet: "#8b5cf6"
  tech-amber: "#fbbf24"
  tech-red: "#ff8f8a"
typography:
  display:
    fontFamily: "'Onest Variable', 'Segoe UI Variable Text', 'Segoe UI', system-ui, -apple-system, Arial, sans-serif"
    fontSize: "2.5rem"
    fontWeight: 700
    lineHeight: 1.05
    letterSpacing: "-0.03em"
    fontFeature: "'tnum'"
  headline:
    fontFamily: "'Onest Variable', 'Segoe UI Variable Text', 'Segoe UI', system-ui, -apple-system, Arial, sans-serif"
    fontSize: "1.375rem"
    fontWeight: 650
    lineHeight: 1.2
    letterSpacing: "-0.015em"
  title:
    fontFamily: "'Onest Variable', 'Segoe UI Variable Text', 'Segoe UI', system-ui, -apple-system, Arial, sans-serif"
    fontSize: "1rem"
    fontWeight: 650
    lineHeight: 1.35
    letterSpacing: "-0.005em"
  subtitle:
    fontFamily: "'Onest Variable', 'Segoe UI Variable Text', 'Segoe UI', system-ui, -apple-system, Arial, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 650
    lineHeight: 1.4
  body:
    fontFamily: "'Onest Variable', 'Segoe UI Variable Text', 'Segoe UI', system-ui, -apple-system, Arial, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  body-sm:
    fontFamily: "'Onest Variable', 'Segoe UI Variable Text', 'Segoe UI', system-ui, -apple-system, Arial, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 400
    lineHeight: 1.45
  label:
    fontFamily: "'Onest Variable', 'Segoe UI Variable Text', 'Segoe UI', system-ui, -apple-system, Arial, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.2
  mono:
    fontFamily: "ui-monospace, 'Cascadia Mono', Consolas, 'Liberation Mono', monospace"
    fontSize: "0.8125rem"
    fontWeight: 500
    letterSpacing: "0.01em"
rounded:
  s: "4px"
  m: "6px"
  l: "10px"
spacing:
  "1": "0.25rem"
  "2": "0.5rem"
  "3": "0.75rem"
  "4": "1rem"
  "5": "1.25rem"
  "6": "1.5rem"
  "8": "2rem"
  "10": "2.5rem"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.m}"
    padding: "0 0.875rem"
    height: "2.25rem"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
  button-primary-active:
    backgroundColor: "{colors.primary-press}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.m}"
    padding: "0 0.875rem"
    height: "2.25rem"
  button-secondary-hover:
    backgroundColor: "{colors.surface-sunken}"
  button-danger:
    backgroundColor: "{colors.danger-solid}"
    textColor: "{colors.on-primary}"
    rounded: "{rounded.m}"
    padding: "0 0.875rem"
    height: "2.25rem"
  button-danger-hover:
    backgroundColor: "{colors.danger}"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.m}"
    padding: "0 0.625rem"
    height: "2.25rem"
  input-disabled:
    backgroundColor: "{colors.surface-sunken}"
    textColor: "{colors.ink-3}"
  badge:
    backgroundColor: "{colors.neutral-bg}"
    textColor: "{colors.neutral}"
    typography: "{typography.label}"
    rounded: "{rounded.s}"
    padding: "0 0.4375rem 0 0.3125rem"
    height: "1.375rem"
  badge-positive:
    backgroundColor: "{colors.success-bg}"
    textColor: "{colors.success}"
  badge-attention:
    backgroundColor: "{colors.warning-bg}"
    textColor: "{colors.warning}"
  badge-negative:
    backgroundColor: "{colors.danger-bg}"
    textColor: "{colors.danger}"
  badge-info:
    backgroundColor: "{colors.info-bg}"
    textColor: "{colors.info}"
  panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.l}"
    padding: "1.25rem"
  numeral:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.display}"
    padding: "1rem"
  numeral-hover:
    backgroundColor: "{colors.surface-sunken}"
  nav-item:
    backgroundColor: "{colors.rail}"
    textColor: "{colors.rail-ink}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.m}"
    padding: "0.3125rem 0.5rem"
    height: "2rem"
  nav-item-hover:
    backgroundColor: "{colors.rail-hover}"
  nav-item-active:
    backgroundColor: "{colors.rail-active}"
    textColor: "{colors.on-primary}"
  tech-surface:
    backgroundColor: "{colors.tech}"
    textColor: "{colors.tech-ink}"
    rounded: "{rounded.l}"
    padding: "1.25rem"
  tech-panel:
    backgroundColor: "{colors.tech-panel}"
    textColor: "{colors.tech-ink}"
    rounded: "{rounded.l}"
    padding: "1rem"
  tech-chip:
    backgroundColor: "{colors.tech-panel}"
    textColor: "{colors.tech-ink}"
    typography: "{typography.label}"
    rounded: "{rounded.m}"
    padding: "0 0.75rem"
    height: "1.625rem"
---

# Design System: ESPACIO-U

## Overview

**Creative North Star: "Señalética"**

ESPACIO-U se lee como la señalética de un campus aplicada a una herramienta de trabajo: una cifra grande, un pictograma de trazo uniforme o una insignia con figura orientan antes que cualquier párrafo. La interfaz general es clara y sobria: hojas blancas sobre un gris frío muy suave, filetes de un píxel y un riel de navegación en azul institucional profundo. El cian eléctrico aparece poco y siempre señala algo: el elemento actual, un contador o el dato tecnológico. Verde, ámbar y rojo no decoran; solo comunican un estado operativo.

La densidad es la de una aplicación de trabajo que se usa muchas horas: texto base de 14 px, controles de 36 px, tablas compactas y filtros en una sola retícula. La información no se elimina para ganar limpieza: lo secundario o técnico queda a un clic, detrás de un «Ver detalles» nativo (`details`/`summary`). Cada pantalla responde de inmediato dónde estoy, qué puedo hacer y si algo requiere atención.

Dentro del mismo sistema existen dos tratamientos oscuros, y solo dos contenidos los usan: IoT («Planta y nivel», un edificio isométrico de niveles apilados en cian, azul y verde) y Blockchain (bloques enlazados en azul eléctrico, cian y violeta, con brillo discreto). Son la misma retícula, la misma tipografía y los mismos radios sobre otra superficie; no son un segundo producto.

**Key Characteristics:**
- Tema claro: blanco y grises fríos muy suaves; un único azul institucional como identidad.
- Jerarquía por numerales, pictogramas, insignias y composición, no por texto.
- Una sola familia tipográfica (Onest Variable) y cifras tabulares donde se comparan.
- Estados del dominio con un único mapa: etiqueta + tono + figura; nunca solo color.
- Divulgación progresiva en lugar de recortar información.
- Superficie oscura reservada al contenido de IoT y de Blockchain.
- Sin imágenes ráster: la ilustración es SVG dibujado con los tokens del sistema.
- Movimiento solo para transiciones de estado (120–180 ms); sin secuencias de entrada.

## Colors

Paleta fría y contenida: un azul profundo que da identidad, un cian que señala, neutros casi blancos y tres tonos operativos con significado fijo. Los valores normativos están en el frontmatter y en el bloque `@theme static` de `resources/css/app.css`.

### Primary
- **Azul institucional profundo** (`primary`, `rail`): el riel de navegación, el botón de acción principal, la marca, la selección de texto y las columnas de los gráficos. Sus pasos `primary-hover` y `primary-press` son los estados del botón; `primary-tint` es el fondo de avatares, pictogramas de tarea y el realce de fila (al 40 %).
- **Azul de enlace** (`link`, igual a `info`): enlaces de texto, acciones de fila y el `summary` de las divulgaciones.
- **Familia del riel** (`rail-hover`, `rail-active`, `rail-line`, `rail-ink`, `rail-ink-2`): estados, filete y dos niveles de texto sobre el azul profundo.

### Secondary
- **Cian eléctrico** (`accent`): la marca del elemento actual (icono del enlace activo, subrayado de la pestaña activa), los contadores de avisos y la última columna de un gráfico. Sobre él el texto va en `ink`, no en blanco. `accent-ink` es su versión legible como texto sobre claro (códigos de registro en la línea meta).
- **Azul eléctrico** (`electric`, `focus`): anillo de foco, cursor de texto, borde del campo enfocado y el tono «info» en barras de datos.

### Tertiary
- **Verde operativo** (`success`, `success-solid`, `success-bg`, `success-line`): disponible, aprobado, válido, habilitado.
- **Ámbar operativo** (`warning`, `warning-solid`, `warning-bg`, `warning-line`): pendiente, en mantenimiento, con incidencia, alertar.
- **Rojo operativo** (`danger`, `danger-solid`, `danger-bg`, `danger-line`): rechazado, bloqueado, inconsistente, error; también la acción destructiva.
- Cada tono tiene cuatro papeles fijos: texto, relleno sólido para datos, fondo de insignia y borde de insignia.

### Neutral
- **Gris frío de fondo** (`ground`): el lienzo de la aplicación.
- **Blanco de hoja** (`surface`, `surface-raised`): paneles, barra superior, campos y diálogos.
- **Gris hundido** (`surface-sunken`): cabecera de tabla, pistas de barras, hover de botón secundario y campos deshabilitados.
- **Filete** (`line`) y **filete fuerte** (`line-strong`): divisores de un píxel; el fuerte bordea campos y botones secundarios.
- **Tinta** (`ink`, `ink-2`, `ink-3`): texto principal, secundario y de apoyo.
- **Neutro de estado** (`neutral`, `neutral-bg`, `neutral-line`): la insignia de lo inactivo, finalizado o no determinable.

### Superficie tecnológica
- **Azul noche** (`tech`, `tech-panel`, `tech-raised`, `tech-line`): fondo, panel interior, relieve y filete de la superficie oscura, con un único degradado radial azul muy tenue en la parte superior.
- **Tinta sobre oscuro** (`tech-ink`, `tech-ink-2`).
- **Luces** (`tech-cyan`, `tech-blue`, `tech-green`, `tech-violet`, `tech-amber`, `tech-red`): IoT usa cian, azul y verde; Blockchain usa azul, cian y violeta (más un índigo mezclado de azul y violeta). Ámbar y rojo son los equivalentes sobre oscuro de los tonos operativos.

### Named Rules
**Regla del color con significado.** Verde, ámbar y rojo aparecen solo cuando comunican un estado operativo o una acción destructiva. Nunca como decoración, categoría o acento.

**Regla del acento escaso.** El cian marca lo actual o lo tecnológico: un icono activo, un subrayado, un contador, una columna. Si ocupa un fondo amplio en una pantalla clara, está mal usado.

**Regla de las dos superficies oscuras.** `.tech-surface` es exclusiva del contenido de IoT y de Blockchain: el gemelo digital, la cadena de evidencia y la tarjeta resumen de monitoreo del dashboard, que es contenido IoT. Ningún otro módulo la adopta.

**Regla de lo no determinable.** En IoT la ocupación se declara «no determinable» con insignia neutra y figura de anillo. `NO_DETERMINABLE` nunca se pinta como libre ni como ocupado, y el verde en IoT significa «lectura reciente», no «espacio libre».

## Typography

**Display Font:** Onest Variable (con Segoe UI Variable Text, Segoe UI, system-ui)
**Body Font:** Onest Variable (la misma familia)
**Label/Mono Font:** pila monoespaciada del sistema (ui-monospace, Cascadia Mono, Consolas) solo para hashes, identificadores de sensor y credenciales

**Character:** una sola familia contemporánea y muy legible, alojada en el propio proyecto (`@fontsource-variable/onest`). La jerarquía se resuelve con tamaño y peso; los pesos intermedios del eje variable (550, 650) evitan saltos bruscos.

### Hierarchy
- **Display** (700, 2.5rem, 1.05, -0.03em): los numerales del tablero. Baja a 2rem por debajo de 36rem. Sin dato muestra «Sin datos» a 1rem/600 en tinta de apoyo.
- **Headline** (650, 1.375rem, 1.2, -0.015em, `text-wrap: balance`): el `h1` de cada página y el veredicto de la cadena de evidencia. Inicio e inicio de sesión lo suben a 1.75rem.
- **Title** (650, 1rem, 1.35): `h2` de panel. En diálogos sube a 1.125rem.
- **Subtitle** (650, 0.875rem, 1.4): `h3` dentro de un panel.
- **Body** (400, 0.875rem, 1.5): texto de trabajo y valores de campo. Los párrafos explicativos se limitan a 70ch.
- **Body small** (400–600, 0.8125rem): línea meta bajo el título, tablas, botones (600), navegación (500) y ayudas.
- **Label** (500–600, 0.75rem): insignias, cabeceras de tabla, rótulos de dato (`dt`) y etiquetas de ficha. Sin mayúsculas forzadas ni espaciado ancho.
- **Mono** (500, 0.8125rem, 0.01em): hash de evidencia, identificador de sensor, código técnico.

### Named Rules
**Regla de una sola familia.** No se añade una segunda tipografía de titulares. La monoespaciada es un instrumento para cadenas técnicas, no un estilo.

**Regla de la línea meta.** No hay antetítulos sobre los encabezados. El código del registro (reserva, equipo, espacio) vive en la línea bajo el `h1`, en `accent-ink`, 600, junto a tipo y fechas separados por «·».

**Regla de las cifras tabulares.** `tabular-nums` solo donde las cifras se alinean o cambian en el sitio: contador del menú, paginación y campos de fecha y número. Los numerales y contadores sueltos usan cifras proporcionales, porque las tabulares abren un hueco en cifras como «11».

## Layout

Armazón de dos columnas: riel fijo de 14rem (224 px) y cuerpo fluido. El cuerpo tiene una barra superior fija de 3.25rem (52 px) con la ruta de navegación a la izquierda y la fecha y los avisos a la derecha; el contenido se limita a 90rem con 1.5rem de margen y 1rem de separación vertical entre bloques.

El espaciado sigue una base de 4 px (`--space-1` a `--space-10`). Los controles miden 2.25rem de alto. Las retículas son intrínsecas (`auto-fit`/`auto-fill` con mínimos): 9.5rem para numerales, 11.5rem para filtros, 13rem para datos de un registro, 17rem para repartos por estado.

Cada página abre con un bloque de título: `h1` y línea meta a la izquierda, acción principal a la derecha. En el tablero le sigue la fila de numerales sobre una sola hoja, luego el reparto por estado y los gráficos del periodo. Los formularios se limitan a 56rem en dos columnas.

Adaptación observada:
- **≤ 64rem:** el riel pasa a cajón lateral (ancho `min(17rem, 86vw)`) con fondo atenuado y botón de menú; la ruta pierde el nombre del grupo.
- **36–80rem:** con cinco o seis numerales, tres por fila, para no dejar filas cojas.
- **≤ 56rem:** el gemelo digital, inicio e inicio de sesión pasan a una columna.
- **≤ 48rem:** los formularios pasan a una columna y **las tablas se muestran como fichas por fila**: la cabecera se oculta visualmente, la primera celda (identidad) ocupa todo el ancho y cada dato lleva su rótulo encima, tomado de `data-label` (lo asigna `resources/js/tableLabels.js`, que además fija los roles ARIA de tabla). Estado y acción quedan siempre visibles.
- **≤ 44rem:** la cadena de evidencia pasa de horizontal a un recorrido vertical con bloques de 3.5rem.
- **≤ 36rem:** paneles con 1rem de relleno, numerales en dos columnas y botones de cabecera a todo el ancho.
- **Impresión:** se ocultan riel y barra superior; el contenido ocupa la página.

### Named Rules
**Regla de la divulgación progresiva.** Lo técnico o secundario no se borra: se pliega tras un `summary` con verbo («Ver detalles técnicos», «Ver datos», «Más indicadores»). Primero la conclusión, después la evidencia.

**Regla de la ficha por fila.** Por debajo de 48rem una tabla no se desplaza en horizontal: cada fila se convierte en ficha con sus rótulos. Una tabla nueva debe ir dentro de `.table-scroll` para heredarlo.

## Elevation & Depth

El sistema es plano. La profundidad se expresa con tono (lienzo gris, hoja blanca, gris hundido) y filetes de un píxel; las sombras son casi imperceptibles y estructurales. Solo lo que flota sobre la página proyecta sombra real.

### Shadow Vocabulary
- **Hoja** (`box-shadow: 0 1px 2px rgb(14 27 46 / 0.04)`): paneles y la hoja de numerales; acompaña al borde, no lo sustituye.
- **Superposición** (`box-shadow: 0 16px 40px -12px rgb(14 27 46 / 0.28), 0 2px 8px rgb(14 27 46 / 0.1)`): diálogos, sobre un telón `rgb(14 27 46 / 0.45)`.
- **Filete compartido** (`box-shadow: 0 0 0 1px` en `line`): divide los numerales dentro de una misma hoja sin dejar celdas grises en filas incompletas.
- **Luz tecnológica** (solo sobre `.tech-surface`): `drop-shadow` del tono de cada bloque de la cadena (0.5rem, al 42 %), halo cian del ambiente seleccionado en el gemelo (7 px, al 55 %) y halo verde de 6–7 px en el punto de «lectura reciente».

### Named Rules
**Regla del brillo que informa.** El resplandor existe solo en la superficie oscura y solo sobre algo encendido: una etapa alcanzada, un espacio seleccionado, una lectura reciente. Una etapa no alcanzada pierde el brillo y pasa a trazo discontinuo.

**Regla de la altura dibujada.** En el gemelo digital un ambiente con sensores se eleva un poco y el seleccionado más. Esa altura es dibujo, no un dato.

## Shapes

Rectángulos sobrios con esquinas apenas suavizadas: 4 px para insignias, etiquetas y contadores; 6 px para botones, campos, enlaces del riel y avatares; 10 px para paneles, superficies tecnológicas y diálogos. No hay píldoras: las insignias son rectangulares. El círculo se reserva a puntos de estado, nodos de la cadena y marcas de cronología.

Los bordes son filetes de 1 px. Los pictogramas son SVG de trazo, sin relleno, dibujados en `currentColor` sobre una retícula de 24 px (12 px en las figuras de insignia) con extremos y uniones redondeados.

La geometría isométrica 2:1 es la forma propia de los dos módulos oscuros: placas de piso apiladas con sus ambientes en IoT y cubos numerados en Blockchain. El mismo motivo, reducido a tres rombos apilados, identifica la tarjeta de monitoreo del tablero.

La ilustración de ambientes (`SpaceIllustration.vue`) es un SVG dibujado con los tokens: aula, laboratorio o auditorio vacíos, decorativo y oculto a lectores de pantalla. No se publican imágenes ráster.

## Components

### Buttons
- **Shape:** esquinas suaves (6 px), 2.25rem de alto, relleno horizontal de 0.875rem, texto de 0.8125rem/600, icono de 1rem.
- **Primary:** fondo azul institucional con texto blanco. Una acción principal por bloque de título.
- **Secondary:** fondo blanco, borde `line-strong`, texto en tinta. Es el botón por defecto.
- **Danger:** fondo rojo sólido con texto blanco, solo para acciones destructivas.
- **Hover / Focus:** cambio de fondo en 120 ms ease-out; foco con contorno de 2 px en `focus` separado 2 px. Deshabilitado al 50 % de opacidad.
- **Enlace de texto:** `link`, 600, subrayado que aparece al pasar el cursor. El texto nombra su destino; no lleva flecha.
- **Sobre superficie oscura:** el secundario se vuelve transparente con borde `tech-line` y hover en `tech-raised`.

### Chips
- **Insignia de estado:** rectangular (4 px), 1.375rem de alto, figura de 12 px + etiqueta de 0.75rem/600. Cinco tonos: positivo, atención, negativo, info y neutro, cada uno con fondo, borde y texto de su familia. Etiqueta, tono y figura salen de un único mapa (`resources/js/status.js`, pintado por `StatusBadge.vue`), cuyas claves son exactamente los valores de los enums. Figuras: check, reloj, cruz, candado, herramienta, punto, anillo, guion, alerta y flecha. Un valor desconocido cae en neutro con anillo.
- **Sobre superficie oscura:** fondo transparente, borde del tono al 45 % y texto en la luz correspondiente.
- **Etiqueta descriptiva** (características, roles): blanca con filete y texto `ink-2`, 500. Neutra a propósito: no comunica estado.
- **Chip tecnológico:** 1.625rem, panel oscuro con filete; identifica la evidencia o declara el veredicto de ocupación.

### Cards / Containers
- **Panel:** hoja blanca, borde `line`, radio de 10 px, sombra de hoja y relleno de 1.25rem (1rem en móvil). Una tabla dentro del panel sangra hasta sus bordes.
- **Numerales:** indicadores sobre una sola hoja dividida por filetes, sin tarjetas separadas. Cada celda: pictograma arriba, cifra grande, etiqueta breve. La cifra pasa a rojo o ámbar solo cuando el indicador pide atención y su valor es mayor que cero. Un numeral enlazado abre su listado ya filtrado y lo indica con una flecha discreta que se desplaza 2 px al pasar el cursor.
- **Reparto por estado:** total, barra segmentada de 0.625rem con los tonos sólidos y una leyenda con punto cuadrado, etiqueta y cifra.
- **Divulgación:** `details` con borde y radio de 10 px; el `summary` va en color de enlace.
- **Estado vacío:** pictograma en una pastilla gris de 2.25rem, título, una frase que dice qué falta y, cuando existe, la acción. Nunca datos de relleno.
- **Aviso:** franja con borde y fondo del tono (info por defecto; éxito, error, advertencia), texto en tinta.
- **Diálogo:** hasta 28rem, radio de 10 px, sombra de superposición.

### Inputs / Fields
- **Style:** fondo blanco, borde `line-strong` de 1 px, radio de 6 px, 2.25rem de alto. Etiqueta real encima en 0.8125rem/600.
- **Hover / Focus:** el borde se oscurece al pasar; al enfocar pasa a azul eléctrico con el contorno de foco. El cursor de texto es azul eléctrico.
- **Error / Disabled:** borde y trazo interior en rojo sólido con mensaje en rojo/600 debajo; deshabilitado en gris hundido.
- **Select:** apariencia propia con un cheurón SVG a la derecha.
- **Filtros:** una retícula compacta común a todos los módulos (mínimo de 11.5rem por campo) con las acciones al pie.
- **Control segmentado:** pista gris hundida con el segmento activo en blanco y filete; para alternar grupos dentro de un panel.

### Navigation
- **Riel:** azul institucional, 14rem, con marca arriba, grupos de enlaces y la cuenta al pie. Cada grupo lleva un rótulo pequeño (0.6875rem/500) en `rail-ink-2`.
- **Enlace:** 2rem de alto, pictograma de 1.125rem + texto de 0.8125rem/500. Hover en `rail-hover`. El activo usa `rail-active`, texto blanco a 650 y el pictograma en cian. Los contadores van en cian con texto en tinta.
- **Barra superior:** blanca con filete inferior; ruta «grupo / sección / página», fecha y campana con contador cian.
- **Pestañas de alcance:** texto 600 con subrayado cian de 2 px en la activa.
- **Tablas:** cabecera gris hundida de 0.75rem/600, filas separadas por filete, hover con `primary-tint` al 40 %, la acción a la derecha como enlace de texto.
- **Móvil:** cajón lateral que entra en 180 ms (`cubic-bezier(0.22, 1, 0.36, 1)`), se cierra con Escape y devuelve el foco al botón de menú.

### Gemelo digital IoT («Planta y nivel»)
Sobre `.tech-surface`: un edificio en isometría 2:1 con cuatro placas de piso apiladas, pasillo central, servicios como referencia y ambientes numerados. La figura distingue el tipo además del color: aula lisa, laboratorio con doble marco, auditorio con filas. Los ambientes sin sensores quedan atenuados; con sensores se elevan y se pueden seleccionar; el seleccionado se eleva más, se enmarca en cian y resalta su piso. Cada sensor es un pin: cabeza llena y verde con pulso si tiene lectura reciente, hueca si no, tachada y de tallo discontinuo si está deshabilitado. A la derecha, un inspector responde qué espacio se mira, si hay lecturas recientes y qué puede concluir el sistema, con los detalles técnicos plegados. Una lista de botones repite los espacios como equivalente accesible del dibujo, y los que no pueden ubicarse se listan aparte, nunca se descartan.

La distribución del edificio (`resources/js/building.js`) es **provisional y solo visual**: no es el plano real ni inventario institucional, no se guarda ni se envía, y la propia pantalla lo declara junto a la leyenda. Los espacios, sensores y lecturas llegan siempre del servidor.

### Cadena de evidencia Blockchain
Sobre `.tech-surface`: primero el veredicto en lenguaje llano (pictograma en un recuadro del tono + titular + una frase), después cinco cubos isométricos numerados y enlazados (origen, operación, huella digital, registro externo, verificación) que recorren cian, azul, índigo y violeta y terminan en el tono del resultado. Cada enlace es una línea de 2 px con un nodo en cada extremo y degradado entre el tono de origen y el de destino. Una etapa no alcanzada pierde el brillo y pasa a trazo discontinuo. El hash, el algoritmo y las referencias quedan tras «Ver detalles técnicos». La etapa de verificación solo es verde con registro externo y resultado válido. Una nota al pie aclara que los bloques son etapas de una sola evidencia.

### Gráficos
Barras horizontales con pista gris hundida y columnas en azul institucional con la última en cian; rejilla punteada y ejes en `line-strong`. Cada gráfico ofrece su tabla de datos tras «Ver datos».

## Do's and Don'ts

### Do:
- **Do** tomar colores, radios y tamaños de los tokens de `resources/css/app.css`; los estilos de un módulo viven en `resources/css/modules/` y solo consumen esos tokens.
- **Do** mostrar cada estado del dominio con `StatusBadge` y el mapa de `resources/js/status.js`: etiqueta, tono y figura juntos.
- **Do** abrir cada página con `h1` y línea meta a la izquierda y una sola acción principal a la derecha.
- **Do** poner el código del registro en la línea meta bajo el `h1`, en `accent-ink`.
- **Do** resumir con numerales sobre una sola hoja dividida por filetes y enlazar cada uno a su listado filtrado.
- **Do** plegar lo técnico tras un `summary` con verbo en lugar de eliminarlo.
- **Do** envolver cada tabla en `.table-scroll` para que por debajo de 48rem se lea como fichas con sus rótulos.
- **Do** mantener las transiciones en 120–180 ms ease-out y respetar `prefers-reduced-motion` (el pulso de los sensores se apaga).
- **Do** declarar la distribución del edificio como provisional siempre que se dibuje.
- **Do** mostrar un estado vacío cuando no hay datos reales.

### Don't:
- **Don't** usar verde, ámbar o rojo sin significado operativo.
- **Don't** llevar `.tech-surface` a un módulo que no sea contenido de IoT o de Blockchain.
- **Don't** representar `NO_DETERMINABLE` como libre u ocupado, ni usar el verde de IoT para afirmar disponibilidad.
- **Don't** añadir antetítulos sobre los encabezados ni rótulos en mayúsculas con espaciado ancho.
- **Don't** sustituir la fila de numerales por tarjetas idénticas con párrafos explicativos.
- **Don't** añadir una segunda familia tipográfica ni usar la monoespaciada como estilo.
- **Don't** usar imágenes ráster, fotografías ficticias ni logotipos institucionales; la ilustración es SVG propio.
- **Don't** comunicar un estado solo con color: siempre figura y etiqueta.
- **Don't** añadir secuencias de entrada ni animación decorativa en las pantallas claras.
- **Don't** inventar estados, cifras, porcentajes o tendencias para llenar una pantalla.
- **Don't** llevar el brillo o los degradados de la superficie oscura a las pantallas claras.
