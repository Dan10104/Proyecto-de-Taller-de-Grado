# Preparación Google Cloud — sin ejecución cloud

Estos archivos describen un despliegue futuro. **Fase 14 no autoriza crear recursos, activar APIs, cambiar IAM, subir imágenes ni desplegar.** `render.py` solo escribe archivos locales; `plan.py` solo imprime argumentos y no tiene modo de ejecución.

1. Copiar `config.example.json` a `.local/deploy/config.json` dentro del workspace. Nunca introducir valores secretos: `secrets` acepta solo nombre y versión numérica de Secret Manager.
2. Completar proyecto/región, URL HTTPS, fecha de activación RF21, SHA Git, digest publicado y revisión anterior. Para el primer staging privado, `previous_revision` es cadena vacía. Resolver todos los `REPLACE_`.
3. Renderizar: `python deploy/gcp/render.py --config .local/deploy/config.json`.
4. Revisar plan: `python deploy/gcp/plan.py --config .local/deploy/config.json --dry-run`. Guardar su JSON privado y revisar cada etapa; no es un script para ejecutar todo de golpe.
5. Validar plantillas: `python deploy/gcp/test_templates.py`. Para inspección sin proyecto real se permite `--allow-placeholders`; no habilita ejecución.

`templates/*.json` son documentos YAML válidos en sintaxis JSON, preparados para `gcloud run services replace` / `gcloud run jobs replace`. Las cuatro cargas usan el mismo digest. Las versiones Secret Manager están fijadas; nunca `latest`. Primero resolver el digest tras el push autorizado y volver a renderizar. El primer servicio mantiene IAM privado; una revisión posterior conserva el tráfico anterior y añade `candidate` sin tráfico general. El plan no concede `allUsers`.

`cloudbuild.yaml` es alternativa opcional de compilación, no pipeline automático. Usar cuenta `espacio-u-build` con escritor del repositorio Artifact Registry y escritor de logs; el operador necesita actAs sobre esa identidad. No invocarlo en esta fase. `.gcloudignore` y `.dockerignore` excluyen credenciales, datos, runtimes locales y manifiestos generados. `docker buildx build --platform=linux/amd64 --push` en el plan es otra alternativa futura, nunca un paso ejecutado ahora.

Orden y decisiones operativas: [arquitectura](../../docs/DEPLOYMENT_ARCHITECTURE.md), [checklist](../../docs/DEPLOYMENT_CHECKLIST.md), [recuperación](../../docs/DEPLOYMENT_RECOVERY.md). No afirmar que la sintaxis validada localmente acredita aceptación del API cloud, IAM efectivo o conexión Cloud SQL: esas comprobaciones corresponden al staging autorizado.
