# Checklist de despliegue controlado

Fase 14 termina con preparación local. Todos los pasos cloud siguientes permanecen **pendientes de autorización posterior**. No existe despliegue público ni billing validado.

## Puerta de autorización

- [ ] Usuario autoriza proyecto, región, presupuesto, identidades, APIs y alcance de staging; revisar disponibilidad de MYSQL_8_4 y cuotas actuales.
- [ ] Revisar `config.example.json`, plan dry-run y cuatro manifests; resolver placeholders. Config privada en .local, ningún valor secreto.
- [ ] Confirmar coste CPU siempre asignada durante vida de instancia, Cloud SQL persistente, almacenamiento/backups/registry/jobs/scheduler/logs. Min web=0 no significa coste cloud cero.
- [ ] Acordar dominio/URL HTTPS y acceso inicial IAM privado. Publicar para usuarios solo con autorización de exposición explícita.

## Aprovisionamiento autorizado futuro

- [ ] Habilitar APIs necesarias; crear repositorio regional con tags inmutables y cuentas dedicadas. No usar claves JSON.
- [ ] Crear Cloud SQL MYSQL_8_4 con backups/PITR/retención/deletion protection y connector enforcement; sin redes autorizadas amplias. Verificar parámetros efectivos.
- [ ] Crear espacio_u con utf8mb4, usuarios runtime/migration de contraseñas fuertes y grants mínimos. Revisar SHOW GRANTS; runtime no CREATE/ALTER/DROP.
- [ ] Crear secretos y versiones por canal privado; no argumentos CLI con valores, no texto de secreto en manifiestos/chat/logs. Conceder secretAccessor por secreto a identidad concreta.
- [ ] Conceder SQL client a web/jobs/migrate, job invoker al scheduler solo sobre dos jobs, writer de registry solo a build. Operador actAs limitado y revisado.
- [ ] Preparar NOTIFICATIONS_EVENTS_FROM una vez y APP_KEY estable. IOT master solo antes de sensores habilitados; providers externos false.

## Release

1. Verificar backup exitoso reciente y recuperación preparada. Anotar revisión anterior, digest y versión de esquema.
2. Checkout limpio del SHA aprobado; compilar desde locks y digests, repetir tests/auditorías y escaneo. Contexto filtrado por .dockerignore/.gcloudignore. No secretos build-args.
3. Push autorizado a Artifact Registry con tag SHA; resolver digest, actualizar configuración no secreta y renderizar todas las cargas con **ese mismo digest**.
4. Aplicar job migrate con credencial DDL y ejecutar una vez con --wait; revisar éxito. Nunca fresh/refresh/rollback automático.
5. Solo primera instalación: ejecutar SecuritySeeder --force en job temporal con cuenta DML, después security:bootstrap-admin --from-env con secretos temporales de correo/contraseña. Usar copia privada del template job, args explícitos y referencias Secret Manager numéricas; no modificar entrypoint ni scheduler permanente. Verificar que falla si ya hay cuentas; retirar el job y grants/secretos temporales al terminar.
6. Aplicar candidate sin tráfico general o staging IAM privado; validar /up, conexión socket SQL, cookies HTTPS, login/RBAC, sesiones compartidas, QR/HMAC, reservas/préstamos, notificaciones, PDF/XLSX, bitácora y errores seguros.
7. Mover tráfico únicamente tras smoke aprobado. Confirmar headers, URLs, assets, memoria y conexiones; conservar revisión/digest anterior.
8. Activar triggers OAuth Scheduler cada minuto America/La_Paz para schedule:run y queue acotada. Verificar ejecución, roles efectivos, locks e idempotencia; no activar antes de migración/semilla necesarias.
9. Monitorizar errores, cola/reconciliación, latencia, memoria, SQL y backups. Si falla, aplicar [rollback](DEPLOYMENT_RECOVERY.md).
10. Ejecutar ensayo Cloud SQL restore aislado antes de comprometer operación productiva y medir RTO objetivo 4 h. Completar configuración de retención y responsables.

## Comprobaciones de staging aún pendientes

- [ ] Aceptación real de manifests por APIs y permisos IAM efectivos.
- [ ] Creación/push/arranque Cloud Run y conexión Cloud SQL por socket.
- [ ] Dominio/certificado/HTTPS externo, cookies Secure y proxy real.
- [ ] Scheduler OAuth, ejecuciones reales de jobs y presupuesto de conexiones durante rollout.
- [ ] Backup/PITR/restauración cloud, rollback de tráfico y alertas.
- [ ] Medición de cold starts, concurrencia y coste en proyecto elegido.
- [ ] Smoke OpenAI con API key/cuota, independiente y solo si se habilita proveedor.
- [ ] Smoke Sepolia con testETH, independiente; nunca fondos reales.

No confundir estas validaciones pendientes del deployment posterior con fallos locales ocultos. [Evidencia Fase 14](PHASE_14_DEPLOYMENT_PREPARATION.md) enumera exactamente lo ejecutado.
