# Product

## Platform

web

## Users

- **Estudiantes y docentes de la FICCT (UAGRM).** Consultan la disponibilidad de espacios académicos y de equipamiento tecnológico, registran solicitudes de reserva y de préstamo, validan el uso con un código QR y siguen el estado de sus solicitudes. Usan el sistema de forma ocasional, entre clases, desde una laptop o un teléfono.
- **Encargados de laboratorio.** Resuelven solicitudes, registran la entrega y la devolución de equipos, atienden incidencias y mantenimiento, y vigilan la ocupación. Trabajan muchas horas seguidas frente a tablas y formularios, en un escritorio o una laptop de oficina.
- **Administradores.** Gestionan usuarios, roles y permisos, catálogos, sensores, políticas de liberación, reportes, evidencias de trazabilidad y la bitácora de actividad.

## Product Purpose

ESPACIO-U es el sistema de información para la gestión y control de reservas de espacios académicos y préstamos de equipamiento tecnológico de la FICCT, con monitoreo de ocupación mediante IoT y trazabilidad de operaciones mediante Blockchain. Existe para sustituir un proceso manual y disperso por un registro único, verificable y con responsables identificados. Tiene éxito cuando una persona resuelve su trámite sin ayuda y cuando el encargado puede responder, con evidencia, quién usó qué, cuándo y en qué estado quedó.

## Positioning

Cada operación deja evidencia: el estado de una reserva o de un préstamo, la lectura de un QR, la lectura de un sensor y la huella verificable de las operaciones críticas quedan registrados y enlazados. El sistema no afirma lo que no puede demostrar (por ejemplo, declara la ocupación «no determinable» cuando no hay evidencia reciente).

## Operating Context

- Aplicación web de trabajo: tablero, tablas, formularios y procesos de varios pasos. El objetivo principal es escritorio y laptop; debe seguir siendo usable en tableta y teléfono.
- Se presenta ante un tribunal académico en una laptop conectada a un proyector, en un aula iluminada.
- Autenticación con sesión web; cada pantalla muestra solo lo que permiten los permisos del rol.
- Hora de referencia: Bolivia (America/La_Paz). Idioma: español.

## Capabilities and Constraints

- Treinta casos de uso implementados (CU01–CU30): sesión, usuarios, roles y permisos, espacios académicos y disponibilidad, reservas, equipamiento y préstamos, entrega y devolución, incidencias y mantenimiento, códigos QR, sensores IoT y monitoreo de ocupación, políticas de liberación, evidencias de trazabilidad, notificaciones, tablero, reportes (PDF y XLSX), asistente y bitácora de actividad.
- Stack fijo: Laravel + PHP, Vue 3, Inertia.js, Vite, Tailwind CSS 4, MySQL. Sin otro framework CSS.
- El rediseño visual no cambia rutas, controladores, servicios, policies, permisos, reglas de negocio ni el modelo de datos.
- Los estados del dominio se muestran con los valores exactos de los enums; no se inventan estados.
- La interfaz muestra únicamente datos reales de la base de datos. Sin registros reales se muestra un estado vacío; no se rellenan pantallas con nombres, cifras ni equipos inventados.
- La aplicación móvil (Flutter) está diferida; no se añade PWA.
- Terminología fija: ESPACIO-U, FICCT, espacio académico, equipamiento tecnológico, reserva, préstamo, entrega, devolución, incidencia, mantenimiento, sensor IoT, evidencia de trazabilidad, notificación, bitácora de actividad.

## Brand Commitments

- Nombre del producto: **ESPACIO-U**, siempre en mayúsculas y con guion.
- La identidad visual es la del software, no una identidad oficial de la FICCT ni de la UAGRM. No se usan logotipos institucionales sin evidencia de autorización; el repositorio no contiene ninguno.
- Voz: clara, concreta y respetuosa, de usted. Mensajes que dicen qué ocurrió y qué hacer, sin lenguaje promocional.
- Debe percibirse como institucional, tecnológico, universitario, profesional, confiable, ordenado, moderno y preciso. No debe parecer una página promocional ni una plantilla genérica.
- Dirección visual fijada por el responsable del proyecto (2026-11-20):
  - interfaz general clara, limpia y minimalista; blanco y grises muy suaves como superficies;
  - azul institucional profundo como identidad principal y cian/azul eléctrico como acento tecnológico;
  - verde, ámbar y rojo únicamente con significado operativo;
  - mucho menos texto visible: la jerarquía se apoya en números, iconos, gráficos, insignias y composición;
  - tipografía contemporánea y muy legible; barra lateral elegante y compacta; tablas profesionales (no tarjetas gigantes); formularios y filtros compactos; bordes y sombras muy sutiles;
  - sin estética antigua ni aspecto de hoja técnica seca o monocromática.
- Dos tratamientos especiales dentro del mismo sistema, y solo estos dos módulos:
  - **IoT:** superficie oscura de alta tecnología en cian, azul y verde; gemelo digital con los pisos y espacios en isometría cuando los datos existentes lo permitan; sensores representados visualmente; estados y lecturas fáciles de entender; profundidad y pseudo-3D sutiles.
  - **Blockchain:** superficie oscura de alta tecnología en azul eléctrico, cian y violeta; bloques y nodos conectados; la trazabilidad como una cadena visual; hashes, evidencia y verificación con jerarquía clara; profundidad, luz y brillo discretos; innovador, no «gamer».
- El resto de la aplicación es de tema claro.

## Evidence on Hand

- Datos reales: los que existan en la base de datos activa. La base de presentación arranca solo con roles, permisos y el administrador inicial (ver `docs/PRESENTATION_DATABASE.md`).
- No hay fotografías reales de la FICCT ni logotipos autorizados en el repositorio. Las imágenes generadas que simulaban el campus se retiran; no deben sustituirse por otras imágenes ficticias.
- No se inventan indicadores, porcentajes, tendencias ni testimonios.

## Product Principles

1. La información va antes que la decoración: cada pantalla existe para completar un trámite o tomar una decisión.
2. El estado siempre es visible y nunca depende solo del color.
3. Lo que no se puede demostrar se declara como tal; un vacío se muestra como vacío.
4. Un mismo control se ve y se comporta igual en todos los módulos.
5. Cada rol ve primero lo que le toca resolver.

## Accessibility & Inclusion

- Contraste suficiente de texto y controles (referencia WCAG 2.2 AA), foco visible y navegación completa por teclado.
- Etiquetas reales en los formularios, mensajes de error comprensibles y atributos ARIA donde correspondan.
- Áreas de interacción de tamaño razonable y respeto de la preferencia de movimiento reducido.
