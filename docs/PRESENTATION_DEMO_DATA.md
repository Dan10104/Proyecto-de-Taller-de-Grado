# Datos de demostración institucional: propuesta controlada

## Evidencia y límites

El catálogo local contiene 14 espacios, 3 equipos, 13 pisos, 13 tipos de espacio y 23 usuarios. Veintidós usuarios tienen nombres de pruebas; 18 permanecen inactivos. Hay reservas, préstamos, QR, lecturas y evaluaciones que enlazan esos registros. No son datos desechables independientes.

La [página oficial de Laboratorio de la FICCT](https://www.ficct.uagrm.edu.bo/Unidades/Laboratorio/1) sitúa salas de cómputo en el cuarto piso del módulo 236. La [noticia institucional II/2026](https://uagrm.edu.bo/index.php/carreras/ficct-cii/noticias/-_e27607) confirma aulas, laboratorios y biblioteca. Estas fuentes NO confirman los nombres, capacidades, cantidades, marcas ni asignaciones de este inventario. La distribución siguiente es una propuesta de demo, no un relevamiento de infraestructura.

## Renombrado limitado aprobado y aplicado

Se aprobó renombrar únicamente los 17 recursos existentes y 22 nombres de usuarios ficticios de la base local `espacio_u`, mediante operaciones existentes y su auditoría normal. Conservar IDs, códigos actuales, correos, contraseñas, roles, permisos, estados, fechas, vínculos, capacidades, políticas y registros históricos. No crear usuarios ni habilitar cuentas. Los recursos usan el sufijo «· Demo» para distinguirlos del inventario real. Las referencias técnicas antiguas seguirían visibles donde tienen función de identificación.

| Recurso local | Nombre propuesto (todos con sufijo · Demo) |
| --- | --- |
| 1 | Aula de docencia 101 |
| 3 | Aula de docencia 102 |
| 4 | Sala de trabajo colaborativo |
| 5 | Aula de docencia 201 |
| 6 | Aula de docencia 202 |
| 7 | Laboratorio de redes |
| 8 | Laboratorio de software |
| 9 | Laboratorio de hardware |
| 10 | Laboratorio de cómputo |
| 11 | Sala de proyectos |
| 12 | Sala de tutorías |
| 13 | Aula de docencia 301 |
| 16 | Aula de docencia 302 |
| 17 | Sala de seminarios |
| 2 | Proyector multimedia 01 |
| 14 | Proyector multimedia 02 |
| 15 | Equipo portátil 01 |

Nombres ficticios propuestos, en orden de IDs locales 2–23: Lucía Rojas, Andrés Suárez, Valeria Castro, Diego Vargas, Camila Flores, Martín Salazar, Sofía Méndez, Nicolás Herrera, Paula Aguilar, Gabriel Ortiz, Elena Molina, Sebastián Ríos, Daniela Vega, Mateo Paredes, Renata Quiroga, Tomás Mendoza, Mariana López, Alejandro Soto, Claudia Romero, Javier Morales, Natalia Peña, Rodrigo Céspedes. No corresponden a personas verificadas de la institución. El administrador local conserva su identidad actual.

Reversibilidad: manifiesto privado con únicamente IDs y los nombres anteriores/propuestos; comprobación previa de coincidencia exacta; transacción; comprobación posterior de que no cambian columnas fuera de esa lista. No backup completo ni secretos. La auditoría agregada por las operaciones existentes se conserva. No reescribir historial para disimular que hubo pruebas.

## Catálogo institucional para una carga posterior

Antes de cargar un inventario de defensa consolidado se debe validar el edificio real y aprobar una operación de datos separada. Propuesta: cuatro pisos (1–4), aulas de docencia en 1–3 y laboratorios en 4; 14 ambientes como arriba, agrupados en Aula, Laboratorio y Sala académica. Dos proyectores y un portátil como conjunto mínimo. Marca/modelo se completarán con inventario real o se declararán demostrativos; no se atribuyen compras a la FICCT.

No se aplica ahora consolidación de pisos/tipos: los 13 pisos de QA tienen números técnicos y relaciones existentes. Renombrarlos como cuatro pisos sin reorganizar esas relaciones sería engañoso; la reorganización es una operación de datos diferente a cambiar nombres. Tampoco se cambia la identidad de sensores, códigos de reservas/préstamos, números de serie, evaluaciones, versiones de políticas, descripciones de actividad ni hashes. Las políticas son inmutables por contrato; sus nombres históricos de QA no pueden maquillarse.

## Guion de presentación honesto

1. Mostrar catálogo y disponibilidad con filtros reales; explicar estado operativo frente a intervalo disponible.
2. Dashboard: explicar los valores presentes, incluidos ceros y vencimientos, sin crear cifras para llenar gráficos.
3. IoT: presentar sensores/mediciones actuales; si no hay evidencia reciente, mostrar «no determinable». Ninguna lectura fabricada ni sensor habilitado para la demo.
4. Blockchain: explicar huella local y comprobación externa; «Pendiente» continúa pendiente. No afirmar una transacción Sepolia no realizada.
5. Reportes y bitácora: mostrar datos autorizados y distinguir resumen de actividad del historial. No borrar evidencias antiguas.

Estado: renombrado autorizado expresamente por el usuario y aplicado el 26/10/2026 local mediante SpaceManagementService, EquipmentService y AdministrationService existentes, sin modificar sus fuentes. Se cambiaron solo recursos_institucionales.nombre y usuarios.nombres/apellidos. Comprobación transaccional de las 32 tablas: todas las demás columnas y filas previas coinciden; cinco hashes canónicos críticos coinciden antes/después; 39 entradas normales de edición añadidas a la bitácora. No se añadieron notificaciones, evidencias Blockchain, reservas ni lecturas. El manifiesto reversible y los resultados se conservan en .local/qa/presentation/, fuera de Git. La consolidación de pisos/tipos es solamente estrategia futura, no aplicada ni autorizada por este renombrado.
