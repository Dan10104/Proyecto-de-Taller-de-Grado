# Verificación local del escáner QR

Página de pruebas separada: importa QrScanner.vue real y sustituye getUserMedia solo en su documento por MediaStream de canvas. Imágenes sintéticas en memoria, decoder real y endpoints normales con sesión/CSRF. Sin rutas especiales, bypass, input manual de token ni controles de prueba en el producto.

Usar exclusivamente desarrollo local y operaciones sintéticas. Emitir rota el objetivo seleccionado; entrega/devolución sí modifican esos datos. No usar recursos operativos reales. Los IDs iniciales son ejemplos de la revisión, no datos de instalación.

```powershell
. .\scripts\dev-environment.ps1
node node_modules/vite/bin/vite.js build --config tests/Browser/qr-vite.config.js
```

Con Laravel en localhost:8000 y sesión autorizada, abrir `/build/qr-test/tests/Browser/qr-scanner.html`. Seleccionar objetivo/operación/fuente, preparar e iniciar cámara. No compartir capturas QR. Diagnósticos: tiempos, resultado, ID de evidencia, nombres de campos y estado de tracks; nunca valores del payload. Desconocido/denegación/ausencia/ilegibilidad/cancelación/desmontaje permiten verificar rechazo y limpieza.

Para inactivo/vencido, preparar imagen sintética, desactivar por UI o fijar vencimiento del fixture local de forma controlada, y leer después. No modificar un código real. La representación permanece solo en memoria hasta finalizar. Rechazo conserva historia y no muta negocio.

La serie de 30 lecturas usa movimientos leves de ángulo/distancia en canvas; no certifica cámara, impresora ni carga de producción. La prueba física requiere getUserMedia real, permiso, etiqueta vigente y fila nueva comprobable en la instancia correspondiente.

Al terminar cerrar esta pestaña y ejecutar `npm run build`, que elimina public/build/qr-test. Fuente versionada, compilados ignorados. No guardar imágenes, tokens ni exportaciones de cuerpos HTTP.
