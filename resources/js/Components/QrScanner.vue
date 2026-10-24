<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount, useId } from 'vue';
const props = defineProps({ disabled: Boolean, label: { type: String, default: 'Iniciar cámara' } });
const emit = defineEmits(['decoded', 'cancel']);
const video = ref(null), startButton = ref(null), active = ref(false), starting = ref(false), message = ref('La cámara se inicia únicamente cuando usted lo solicita.');
const statusId = useId();
let stream = null, controls = null, generation = 0;
function release() {
  generation++;
  controls?.stop(); controls = null;
  stream?.getTracks().forEach((track) => track.stop()); stream = null;
  if (video.value) video.value.srcObject = null;
  active.value = false; starting.value = false;
}
function stop() { release(); message.value = 'Cámara detenida. No se realizó otra validación.'; emit('cancel'); nextTick(() => startButton.value?.focus()); }
async function start() {
  release();
  if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) { message.value = 'La cámara requiere un navegador compatible y conexión HTTPS o localhost.'; return; }
  const current = generation;
  starting.value = true; message.value = 'Solicitando acceso a la cámara…';
  try {
    const [{ BrowserQRCodeReader }, { DecodeHintType }] = await Promise.all([import('@zxing/browser'), import('@zxing/library')]);
    if (current !== generation) return;
    const acquired = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
    if (current !== generation) { acquired.getTracks().forEach((track) => track.stop()); return; }
    stream = acquired; active.value = true; await nextTick();
    message.value = 'Apunte al QR del recurso o de esta operación. La lectura se enviará para validación.';
    let handled = false;
    const reader = new BrowserQRCodeReader(new Map([[DecodeHintType.TRY_HARDER, true]]), { delayBetweenScanAttempts: 200 });
    const acquiredControls = await reader.decodeFromStream(acquired, video.value, (result, error, scanner) => {
      if (!result || handled || current !== generation) return;
      handled = true;
      let payload = result.getText();
      scanner.stop(); release(); message.value = 'Código leído. Esperando el resultado del servidor…';
      emit('decoded', payload); payload = null;
    });
    if (current !== generation || handled) acquiredControls.stop(); else controls = acquiredControls;
    starting.value = false;
  } catch (error) {
    if (current !== generation) return;
    release();
    message.value = error?.name === 'NotAllowedError' ? 'Permiso de cámara denegado. Habilítelo en su navegador para escanear.' : error?.name === 'NotFoundError' ? 'No se encontró una cámara disponible.' : 'No se pudo iniciar la cámara. Compruebe el dispositivo y vuelva a intentarlo.';
  }
}
function hidden() { if (document.hidden) release(); }
onMounted(() => { window.addEventListener('pagehide', release); document.addEventListener('visibilitychange', hidden); });
onBeforeUnmount(() => { release(); window.removeEventListener('pagehide', release); document.removeEventListener('visibilitychange', hidden); });
</script>
<template>
 <div class="qr-scanner" :aria-describedby="statusId">
  <p :id="statusId" role="status" aria-live="polite">{{ message }}</p>
  <div v-if="active" class="qr-viewport"><video ref="video" muted playsinline aria-label="Vista previa local de la cámara"/><span aria-hidden="true" class="qr-guide"></span></div>
  <div class="button-group"><button ref="startButton" v-if="!active && !starting" type="button" class="btn btn-primary" :disabled="disabled" @click="start">{{ label }}</button><button v-if="active || starting" type="button" class="btn btn-secondary" @click="stop">Detener cámara</button></div>
  <p class="hint">Las imágenes se procesan en este dispositivo y no se guardan ni se envían. Si el código no se lee, mejore la iluminación y ajuste lentamente la distancia o el ángulo. Puede detener la cámara en cualquier momento.</p>
 </div>
</template>
