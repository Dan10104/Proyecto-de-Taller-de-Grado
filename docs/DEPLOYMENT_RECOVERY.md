# Respaldo, restauración y rollback

Plan operativo futuro; ningún backup ni restore de Google Cloud se ejecutó en Fase 14. El ensayo local usa únicamente datos sintéticos del Compose y un esquema temporal separado, nunca desarrollo ni testing del host.

## Respaldo y retención

Cloud SQL: backup automático diario 07:00 UTC (03:00 America/La_Paz), al menos siete backups retenidos; binary logging/PITR explícitamente habilitado, siete días de logs, deletion protection y conservar backups al eliminar instancia. Revisar coste y retención antes de producción. Backup adicional antes de cada migration. Comprobar estado exitoso, timestamp, instancia/proyecto y posibilidad de restaurar; que un comando haya sido aceptado no acredita backup usable.

PITR recupera una **instancia nueva**. No probar sobre producción primero. Ventana recuperable depende de backups/logs disponibles y configuración efectiva. [PITR oficial](https://docs.cloud.google.com/sql/docs/mysql/backup-recovery/pitr), [opciones de creación y retención](https://docs.cloud.google.com/sdk/gcloud/reference/sql/instances/create).

Artifact Registry: conservar como mínimo los siete últimos releases aprobados y todos los digests referenciados por revisiones activas o rollback. Retención mínima operativa propuesta 30 días; política de limpieza primero en dry-run, sin borrar candidatos protegidos. Tags SHA inmutables; no tag latest ni sobrescritura. Guardar manifiestos no secretos, SHA/digest, versión de migración y referencias numéricas de secretos con cada release. No generar copias manuales del repositorio.

## Ensayo de restauración

1. Autorizar coste/proyecto e instancia de ensayo aislada. Registrar inicio y punto de recuperación deseado; RTO objetivo del proyecto **4 h**, no validado por el ensayo local breve. RPO objetivo depende de PITR efectivo; no prometer pérdida cero.
2. Seleccionar backup exitoso o timestamp dentro de PITR; crear destino nuevo. No reutilizar nombre productivo ni conectar scheduler/queue a ese destino.
3. Mantener acceso privado/IAM mínimo. Conectar revisión de staging con runtime DML y secretos adecuados; integraciones externas disabled para evitar reenvíos.
4. Verificar 31 tablas académicas/211 columnas/63 FK y cuatro framework, CHECK/índices y migraciones. Comparar conteos/muestras de reservas, préstamos, incidencias, actividad, evidencias y notificaciones. Comprobar cero huérfanos y coherencia de estados.
5. Invalidar sesiones restauradas: limpiar `sessions` e indicar `revocada_en` en sesiones_acceso aún abiertas mediante operación administrativa controlada. No revivir sesiones anteriores al incidente. Revisar trabajos `jobs` restaurados y cache_locks; detener consumidores antes de limpiar locks transitorios. Conservar historia académica.
6. Verificar login, disponibilidad, operaciones, preview/PDF/XLSX, IoT sintético, bitácora y auditoría. Confirmar permisos DML/DDL separados y temporales privados.
7. Trazabilidad: contrastar evidencia local con red antes de reactivar; un restore no revierte Blockchain. Reconciliar búsquedas de registro existente, no reenviar transacciones ciegamente. Notificaciones conservan corte RF21 y destinatarios; recuperar dentro de contrato, no redistribuir historia.
8. Medir tiempo hasta servicio validado, incidencias y datos perdidos; aprobar o corregir RTO. Retirar instancia de ensayo y accesos solo con autorización correspondiente. Guardar evidencia sin datos sensibles.

Ensayo reproducible local: `python scripts/docker/recovery-smoke.py`. Stream mysqldump --single-transaction hacia `espacio_u_restore_check` dentro del MySQL privado Compose; no archivo dump. Rechaza destino ya existente, verifica esquema/conteos, invalida sesiones recuperadas y elimina solo ese esquema sintético. No acredita recuperación Cloud SQL, HA o disponibilidad mensual.

## Rollback de aplicación

Antes de desplegar registrar revisión estable, digest y versiones de secretos compatibles. Crear candidate con 0% del tráfico general (o primer staging IAM privado), ejecutar smoke y solo después mover tráfico. Si falla: pausar triggers si el incidente afecta jobs, devolver tráfico a revisión anterior y verificar salud/negocio. Conservar candidate para diagnóstico privado; no mostrar stack traces.

La imagen anterior debe ser compatible con la migración incremental. **Nunca ejecutar migrate:rollback automáticamente**; el guard existente también bloquea comandos destructivos fuera de testing autorizado. Si el esquema no es compatible, detener escrituras, evaluar corrección hacia adelante o restaurar backup/PITR a instancia nueva con plan de pérdida/reconciliación aprobado. No sobrescribir historial como reacción automática a un fallo web.

Un rollback de código no rota por sí solo APP_KEY, credenciales o contratos externos. Mantener versiones previas necesarias mientras exista posibilidad de rollback; si una versión está comprometida, la seguridad prevalece y se requiere recuperación coordinada sin reactivar el secreto comprometido.

## Monitorización y respuesta

Alertar por 5xx/latencia, reinicios/OOM, conexiones SQL próximas a presupuesto, errores de autenticación SQL, jobs fallidos o sin ejecución reciente, cola acumulada, reconciliadores pendientes, espacio/CPU SQL, backup fallido y ventana PITR insuficiente. Monitorizar volumen de REPORTE_EXPORTADO y errores seguros sin payloads. Cloud Logging recoge stdout/stderr; acceso y retención de logs deben definirse antes del piloto, inicialmente 30 días con acceso administrativo restringido.

Definir responsable/on-call y umbrales con staging real; no presentar métricas locales como SLO medido. OpenAI/Sepolia ausentes son condiciones independientes conocidas, no incidentes que obliguen a detener reservas/préstamos.
