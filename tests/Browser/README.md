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

## Revisión IoT de Fase 7

La revisión IoT usa las vistas normales de CU21, CU22 y CU06 con sensores, espacios y usuarios sintéticos, el simulador de `scripts/iot/` y solicitudes HTTP firmadas. No añade rutas ni controles de prueba al producto. [Checklist de 27 puntos y resultados](../../docs/IOT_IMPLEMENTATION.md#revisión-manual-completada).

Para repetir: usar la instancia local aislada; crear un sensor en CU21, comprobar provisión sin capturar la clave, ejecutar el simulador y verificar el monitor. Probar duplicado/rechazos, deshabilitación y reasignación con historia. Comprobar roles autorizados y URLs directas denegadas, CU06 mínimo y reserva sin cambios; revisar pantalla móvil/tablet/escritorio, teclado y consola. Finalizar deshabilitando solo los accesos y sensores sintéticos creados para el ensayo, conservando historia y retirando sus secretos temporales del entorno. No imprimir firmas, cuerpos, maestros o claves.
