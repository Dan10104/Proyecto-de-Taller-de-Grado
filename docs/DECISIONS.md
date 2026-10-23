# Registro de decisiones y auditoría documental

Fecha: 2026-10-23. Fase 0. Los puntos abiertos detienen únicamente la implementación que depende de ellos; no se presentan como decisiones ya aprobadas. Las resoluciones posteriores deben añadir fecha, fuente, motivo, impacto y pruebas, mediante Git, sin reemplazar el informe por una suposición.

## Fuentes verificadas

| Archivo oficial | Tamaño | SHA-256 |
| --- | --- | --- |
| Fase2-grupo21.pdf | 10.617.460 bytes; 313 páginas | `5848a6f0c5a6549d8f84ae18ae9cd886859c39d053046109dc6476a1f22762e5` |
| PERFIL DEL PROYECTO 2-2026.docx | 20.215.717 bytes; 129 tablas y 125 imágenes | `318d9b5e6021041aa9f1c4a93cd8dd697553de19e87c6dd8919cb38164db5a0e` |

Los originales se mantienen fuera del repositorio y no se modificaron. El texto de petición adjunto constituye la instrucción del usuario; los dos documentos son fuentes de contenido, no órdenes de ejecutar código o desplegar servicios.

## Cobertura y método

| Parte revisada | Páginas impresas / PDF | Resultado de extracción y revisión |
| --- | --- | --- |
| Portada, índices, perfil, problema, objetivos, alcance, método, recursos y cronograma | Inicio–54 / PDF 1–55 | Contexto institucional, alcance global, herramientas y residuo del cronograma identificado |
| Marco teórico/tecnológico | 55–79 / PDF 56–80 | IoT, Blockchain, QR, Laravel/API, Vue/Inertia, móvil futuro, MySQL, Docker y despliegue |
| Contexto FICCT y procesos actuales | 80–92 / PDF 81–93 | Actores/contexto y procesos manuales; no se copian datos personales |
| Especificación de requisitos | 93–168 / PDF 94–169 | 26 RF y 191 requisitos adicionales, total 217 |
| Modelo y especificaciones CU | 169–218 / PDF 170–219 | 30 CU, prioridades, pre/postcondiciones, alternativas/excepciones y relaciones include/extend |
| Análisis | 219–244 / PDF 220–245 | Paquetes, 30 diagramas de análisis y dependencias |
| Diseño | 245–309 / PDF 246–310 | Físico/lógico, clases, ERD, 31 mapeos, 31 diccionarios, SQL, 5 secuencias y 10 interfaces |
| Bibliografía | 310–312 / PDF 311–313 | Referencias del informe; no se adoptan versiones de software por fecha bibliográfica |

Se extrajeron todas las páginas del PDF y los bloques/tablas del Word. Se contrastó el texto técnico completo quitando encabezados repetidos, espacios y variaciones tipográficas. Los dos flujos con numeración automática distinta en la extracción (CU18 y CU26) se verificaron contra el PDF: no se identificó una diferencia de contenido operativo entre esos flujos.

Se inspeccionaron las 125 imágenes del Word (logo y figuras 1–124), incluidos textos dentro de diagramas e interfaces. Se renderizaron 95 páginas del PDF que contienen figuras y se contrastaron visualmente páginas clave de dominio, actividades, CU, análisis, arquitectura, clases, ERD, secuencias e interfaces. Se revisaron ampliaciones de los diagramas densos y del cronograma. El PDF contiene también 125 imágenes; la recompresión y orden de recursos por página impiden tratar una diferencia binaria como una diferencia semántica. No se afirma identidad byte a byte entre los archivos.

El modelo resultante conserva 31 tablas, 211 columnas y 63 FK. La sección “Volúmenes” es principalmente un diccionario de campos; la carga cuantitativa se obtiene de R-CC, no de tamaños estimados inventados. Las referencias a páginas de estas notas emplean paginación impresa; el PDF agrega una portada (índice PDF = página impresa +1 en el cuerpo).

## Decisiones establecidas por la petición y el informe

- Solo web por ahora; API preparada y requisitos móviles visibles como diferidos. No implementar ninguna funcionalidad durante Fase 0.
- Stack PHP/Laravel, Vue 3/Inertia/Vite, MySQL; integraciones externas y despliegue futuro definidos en la constitución.
- Workspace único y repositorio oficial confirmado por el usuario: `C:\Users\BlackMaster69\Desktop\ESPACIO-U`, `https://github.com/Dan10104/Proyecto-de-Taller-de-Grado.git`.
- Modelo de clases y estados se preservan; la inclusión de RecursoInstitucional, AsignacionRol y SesionAcceso procede del diseño, no de una ampliación de alcance.
- No se publican documentos originales, capturas personales ni secretos. `.env.example` es únicamente una plantilla segura.

## Inconsistencias, vacíos y puertas de implementación

### D-01 — Discrepancia confirmada: nombre de permiso

| Campo | Registro |
| --- | --- |
| Fuente | Cap. VII, tablas 70/101, figura 109 y script CREATE TABLE permisos; SQL p. 270 (PDF 271). |
| Hallazgo | La declaración usa `código`, mientras UNIQUE refiere `codigo`; el diccionario y ERD usan codigo. El SQL no puede trasladarse sin decidir el identificador exacto. |
| Tratamiento / decisión pendiente | Propuesta: normalizar a `codigo` conforme ERD/diccionario y registrar corrección autorizada. No ejecutar la definición defectuosa. |
| Puerta de implementación | Antes de migración de permisos; Fase 2. |
| Estado | Abierto; no modificar el punto por suposición |

### D-02 — Discrepancia confirmada: estados narrados

| Campo | Registro |
| --- | --- |
| Fuente | IU-05; CU15/CU17/CU18; figura 108 y enums SQL. |
| Hallazgo | La prosa menciona préstamo activo, finalizado, cancelado o vencido; su enum solo admite PENDIENTE, APROBADO, RECHAZADO, ENTREGADO y DEVUELTO. Ocupado/reservado tampoco son estados persistidos del espacio. |
| Tratamiento / decisión pendiente | Conservar enums exactos. Distinguir etiquetas/condiciones derivadas; consultar significado de frases contradictorias antes de implementar transición. Devolución incierta conserva PENDIENTE_REVISION. |
| Puerta de implementación | Antes de transiciones en Fases 4–6 y UI. |
| Estado | Abierto; no modificar el punto por suposición |

### D-03 — Diferencias de alcance entre actores RF y CU

| Campo | Registro |
| --- | --- |
| Fuente | RF6/CU07; RF11/CU14; RF20/CU25; RF23/CU28; RF25/CU30. |
| Hallazgo | Los RF admiten ciertos usuarios adicionales por permisos; CU07/CU14 enumeran Docente/Estudiante, CU25/CU30 Administrador y CU28 Administrador/Encargado. |
| Tratamiento / decisión pendiente | No ampliar ni restringir silenciosamente: fijar la matriz rol–permiso–operación a partir del informe. Conservar ambos alcances en la trazabilidad. |
| Puerta de implementación | Catálogo de permisos en Fase 2; operaciones dependientes en sus fases. |
| Estado | Abierto; no modificar el punto por suposición |

### D-04 — Vacío de diseño: recursos asignados al encargado

| Campo | Registro |
| --- | --- |
| Fuente | Descripción de actores, restricciones de acceso RF/CU; figura 108 y las 31 tablas. |
| Hallazgo | Se exige operar sobre recursos asignados, pero no se identifica una relación persistida encargado–recurso; asignaciones_roles solo asocia usuario y rol. |
| Tratamiento / decisión pendiente | Precisar origen/representación del ámbito y autorización. No conceder todos los recursos ni añadir una tabla por suposición. |
| Puerta de implementación | Antes de autorización por recurso; Fases 2–5. |
| Estado | Abierto; no modificar el punto por suposición |

### D-05 — Vacíos y precisión temporal

| Campo | Registro |
| --- | --- |
| Fuente | RF5–7/10–14; CU06–10/13–18; DB-16/17/18; CHECK SQL. |
| Hallazgo | No se fija por completo qué estados bloquean intervalos, tratamiento de PENDIENTE, frontera entre periodos contiguos, plazos de cancelación, finalización automática ni motivo obligatorio vs cuando corresponda. SQL exige fin>inicio, más estricto que no anterior de DB-16. |
| Tratamiento / decisión pendiente | Definir tabla de transiciones/guardas y colisión antes del algoritmo. Conservar la restricción temporal estricta documentada. Probar concurrencia y revalidación sin asumir una convención de intervalos como oficial. |
| Puerta de implementación | Fases 3–5 antes de reglas de disponibilidad/transición. |
| Estado | Abierto; no modificar el punto por suposición |

### D-06 — Diferencia entre modelo conceptual y diseño de datos

| Campo | Registro |
| --- | --- |
| Fuente | Figura 19 frente a figura 108/109, tablas 97/98, SQL evidencias_trazabilidad/notificaciones; DB-06. |
| Hallazgo | Figura 19 muestra 0..* evidencias por actividad y 0..1 actividad por notificación. SQL impone UNIQUE actividad para evidencia y actividad NOT NULL para notificación. DB-06 habla de sensores de espacios del piloto; el modelo permite espacios sin sensor. |
| Tratamiento / decisión pendiente | Registrar la diferencia de nivel/detalle; confirmar cardinalidad final antes de migrar esas relaciones. No ampliar evidencia a varias por evento ni crear avisos sin actividad por inferencia. Fuera del piloto no exigir sensores a todos los espacios. |
| Puerta de implementación | Fases 7, 9 y 10 según relación afectada. |
| Estado | Abierto; no modificar el punto por suposición |

### D-07 — Decisión técnica pendiente: autenticación y sesión

| Campo | Registro |
| --- | --- |
| Fuente | CU01–02; RNF-10/12/16; IC-04/05; sesiones_acceso. |
| Hallazgo | El informe exige sesión/token con hash, expiración y revocación, pero no selecciona mecanismo Laravel ni duración, almacenamiento de sesión o política de reintentos. CU02 con fallo de red no garantiza revocación remota inmediata. |
| Tratamiento / decisión pendiente | Elegir integración web/API compatible con sesiones_acceso, sin añadir tablas del starter automáticamente. Definir cierre y recuperación ante red caída, duración y controles de acceso. |
| Puerta de implementación | Fase 1 selección; Fase 2 antes de implementar. |
| Estado | Abierto; no modificar el punto por suposición |

### D-08 — Vacío de ejecución de liberación

| Campo | Registro |
| --- | --- |
| Fuente | RF18; CU23 (extiende CU22); politicas_liberacion y evaluaciones_liberacion. |
| Hallazgo | RF18 parte del horario; CU23 se dispara desde monitoreo. La política puede tener usa_iot=false. Faltan disparador temporal sin eventos, tratamiento de antigüedad NULL, tabla de combinación QR/IoT/conflicto y conservación de versiones. |
| Tratamiento / decisión pendiente | Definir evaluación temporal, tolerancias y evidencia suficiente con criterio conservador. No asumir ausencia como libre. Preservar política/version aplicada; no inventar columnas snapshot sin decisión. ALERTA no libera. |
| Puerta de implementación | Fase 8; preparar contrato en Fase 7. |
| Estado | Abierto; no modificar el punto por suposición |

### D-09 — Vacío de contrato QR y correlación

| Campo | Registro |
| --- | --- |
| Fuente | CU11/CU20; figuras 111/121; codigos_qr/validaciones_qr. |
| Hallazgo | No hay CU independiente de emisión/renovación ni política completa de QR obligatorio. Recurso esperado/contexto son obligatorios; código reconocido puede ser NULL. Enlace a entrega/devolución es UNIQUE, aunque puede haber varios intentos. |
| Tratamiento / decisión pendiente | Acordar emisión/vigencia, contexto y registro de rechazo, reintento/replay y enlace de validación confirmada. No crear CRUD extra ni relajar CHECK/UNIQUE por comodidad. |
| Puerta de implementación | Fase 6; interfaces de entrega/devolución en Fase 5. |
| Estado | Abierto; no modificar el punto por suposición |

### D-10 — Vacío de contrato IoT

| Campo | Registro |
| --- | --- |
| Fuente | IC-06/07; RD-40; CU21–22; sensores_iot/eventos_ocupacion. |
| Hallazgo | HTTP/MQTT son alternativas; faltan esquema final, autenticación de dispositivo, unidades/significado de valor, repetidos, eventos tardíos y política de reloj/frescura. |
| Tratamiento / decisión pendiente | Fijar contrato del piloto y pruebas. Preservar espacio histórico, distinguir fecha_evento/fecha_recepcion y aceptar solo sensores habilitados. Elegir transporte sin inventar broker. |
| Puerta de implementación | Antes de integración funcional; Fase 7. |
| Estado | Abierto; no modificar el punto por suposición |

### D-11 — Defecto visual confirmado: entrega

| Campo | Registro |
| --- | --- |
| Fuente | Figura 23, RF13, p. 127 (PDF 128), contrastada con RF13/CU17. |
| Hallazgo | La rama rotulada [Si] de identificación válida conduce a informar inconsistencia; desde ese nodo también parte una conexión a recibir equipamiento. No representa de manera inequívoca el rechazo y la entrega. |
| Tratamiento / decisión pendiente | Usar el texto RF13/CU17 para identificar la intención, consultar/corregir el diagrama antes de convertirlo a flujo ejecutable. Una identificación inválida nunca autoriza entrega. |
| Puerta de implementación | Fase 5; no bloquea la documentación de Fase 0. |
| Estado | Abierto; no modificar el punto por suposición |

### D-12 — Vacío de contrato y secuencia Blockchain

| Campo | Registro |
| --- | --- |
| Fuente | RF19–20; CU24–25; figuras 26/114/123; evidencias_trazabilidad. |
| Hallazgo | RF19 permite operaciones críticas configuradas; CU24 enumera extensiones concretas. Proveedor/red, algoritmo, versión canónica, contenido mínimo y recuperación no están fijados. La prohibición de datos sensibles figura al final de un flujo textual; secuencia 114 la comprueba antes del envío. |
| Tratamiento / decisión pendiente | Verificar privacidad antes de cualquier transmisión. Definir conjunto de operaciones, representación estable, recuperación/reintentos y comprobación externa. SHA-256 del boceto no se adopta automáticamente como única especificación. |
| Puerta de implementación | Fase 9 antes de integración; mínima evidencia local <=3 s. |
| Estado | Abierto; no modificar el punto por suposición |

### D-13 — Contenido residual del cronograma

| Campo | Registro |
| --- | --- |
| Fuente | Figura 4, p. 54 (PDF 55); lista RF final y solicitud actual. |
| Hallazgo | El gráfico menciona aulas/activos, PWA, voz, predicción, Flutter y APK. Parte del vocabulario/funciones no pertenece al alcance vigente de RF o está diferida expresamente. |
| Tratamiento / decisión pendiente | No recuperar entidades antiguas ni añadir PWA/voz/predicción. Mantener web y fases solicitadas; Flutter/Android quedan fuera hasta nueva autorización. |
| Puerta de implementación | Resuelto para alcance de estas fases por instrucción del usuario. |
| Estado | Alcance vigente establecido; no implementar contenido residual |

### D-14 — Bocetos/entidades de análisis sin respaldo persistente

| Campo | Registro |
| --- | --- |
| Fuente | Figuras 103 y 115; CU29/RF24; modelo final de 31 tablas. |
| Hallazgo | El análisis de chatbot muestra conversación; la interfaz de login muestra recordar sesión/recuperación. No existe tabla de conversación ni especificación completa de recuperación en el modelo y CU finales. |
| Tratamiento / decisión pendiente | No generar historial persistente, tabla adicional o función de recuperación solo por el boceto. Aclarar si debe incorporarse; preservar asistencia de orientación. |
| Puerta de implementación | Antes de ampliar Fases 2 o 12; no es autorización de nueva función. |
| Estado | Abierto; no modificar el punto por suposición |

### D-15 — Selección de versiones y entorno pendiente

| Campo | Registro |
| --- | --- |
| Fuente | RD-01–12/36–38; inventario local 2026-10-23. |
| Hallazgo | Informe fija tecnologías, no versiones precisas del backend/compilador. PHP es 8.2.12; existen MySQL 8.0.46 y 9.7.0; XAMPP incluye MariaDB; motor Docker no estaba disponible. |
| Tratamiento / decisión pendiente | En Fase 1 contrastar soporte y compatibilidad con documentación oficial vigente, elegir/fijar versiones y una instancia MySQL. No sustituir MySQL por MariaDB ni instalar scaffold en Fase 0. |
| Puerta de implementación | Fase 1 antes de instalación y lockfiles; motor Docker antes de Fase 14. |
| Estado | Abierto; no modificar el punto por suposición |

### D-16 — Parámetros de tiempo/medición no fijados

| Campo | Registro |
| --- | --- |
| Fuente | RD-25; RNF-01; requisitos de rendimiento. |
| Hallazgo | La UI debe presentar hora de Bolivia, pero no se fija almacenamiento/conversión. RNF-01 incluye login, sin umbral específico R-TR para esa operación. No hay duración/percentil de pruebas ni retención completa de eventos. |
| Tratamiento / decisión pendiente | Documentar zona/normalización, método de medición y retención antes de implementación afectada; no inventar 3 s para login ni sustituir máximos por promedio. |
| Puerta de implementación | Fase 1 decisión horaria; Fases 7/13/14 según parámetro. |
| Estado | Abierto; no modificar el punto por suposición |

### D-17 — Invariantes no garantizadas solo por SQL

| Campo | Registro |
| --- | --- |
| Fuente | DB-17/21; herencia de recursos; asignaciones_roles; UNIQUE/ON DELETE del script. |
| Hallazgo | Índices no impiden solapamientos; cascadas no satisfacen por sí solas preservar historia; asignaciones activas duplicadas y semántica de subtipo no quedan completamente restringidas. |
| Tratamiento / decisión pendiente | Mantener el esquema como referencia, diseñar guardas transaccionales/autorización y pruebas. Cualquier restricción adicional necesita decisión trazable, sin borrar historia ni alterar modelo silenciosamente. |
| Puerta de implementación | Fases 2–10 según tabla/regla. |
| Estado | Abierto; no modificar el punto por suposición |


## Registro de verificación de Fase 0

El entorno y sus pendientes están en [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md). La auditoría de entrega debe comprobar las cantidades anteriores, vínculos internos, terminología, estados y ausencia de código funcional. El inventario solo documenta lo observado; no garantiza conectividad autenticada a MySQL ni disponibilidad de proveedores externos. El resultado efectivo de commit/push se informa al cerrar la fase y puede verificarse en el historial Git.


Controles documentales ejecutados: diez documentos requeridos presentes; 30 fichas CU con todos sus campos; 26 RF y 191 requisitos adicionales sin identificadores omitidos; 31 tablas con las 211 definiciones de columnas y 63 FK contrastadas; enums exactos; 14 fases; enlaces internos válidos. Las huellas de las dos fuentes se comprobaron nuevamente y permanecen sin cambios.

Controles de repositorio ejecutados antes de la entrega: selección explícita de 13 archivos públicos; revisión de nombres/contenido del índice; comprobación de exclusiones para 23 rutas privadas o generadas; plantilla con variables sensibles vacías; `.env` ausente del índice y del workspace. Se revisaron Desktop/Documents en busca de carpetas ESPACIO-U y repositorios con el remoto oficial: se encontró únicamente el workspace indicado. No se creó ninguna copia paralela. La revisión de secretos combinó inspección del contenido y patrones; no se identificó un escáner dedicado instalado. Esto describe el alcance de la revisión y no promete detectar todo secreto imaginable.

No se ejecutaron pruebas funcionales ni de rendimiento de una aplicación inexistente. Las pruebas documentadas en las matrices están planificadas para sus fases. Las decisiones abiertas mantienen su estado; el push de la documentación no las transforma en reglas aprobadas.
