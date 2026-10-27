<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useAccess, dateLabel } from '../../security';
const props = defineProps({ sensor: Object, comunicacion: String });
const { can } = useAccess();
const credential = ref(''), visible = ref(false), busy = ref(false), message = ref('');
let generation = 0;
function clear() { generation++; credential.value = ''; visible.value = false; }
async function provision() {
  clear(); const attempt = generation; busy.value = true; message.value = '';
  try {
    const csrf = document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='));
    const response = await fetch(`/iot/sensores/${props.sensor.id}/credencial`, { method: 'POST', credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': csrf ? decodeURIComponent(csrf.substring(11)) : '' } });
    if (!response.ok) throw new Error('No se pudo provisionar. Compruebe su sesión, permisos y el estado del sensor.');
    const data = await response.json();
    if (generation === attempt && !document.hidden) credential.value = data.credential;
    data.credential = null;
  } catch (error) { if (generation === attempt) message.value = error.message; }
  finally { busy.value = false; }
}
function state() { clear(); router.patch(`/iot/sensores/${props.sensor.id}/estado`, { estado: props.sensor.estado === 'HABILITADO' ? 'DESHABILITADO' : 'HABILITADO' }); }
function hidden() { if (document.hidden) clear(); }
let removeNavigation;
onMounted(() => { window.addEventListener('pagehide', clear); document.addEventListener('visibilitychange', hidden); removeNavigation = router.on('before', clear); });
onBeforeUnmount(() => { clear(); window.removeEventListener('pagehide', clear); document.removeEventListener('visibilitychange', hidden); removeNavigation?.(); });
</script>
<template>
  <AppLayout title="Detalle del sensor" section="IoT">
    <div class="page-heading"><div><h1>{{ sensor.identificador }}</h1><p>{{ sensor.tipo }}</p></div><Link href="/iot/sensores" class="btn btn-secondary">Volver a sensores</Link></div>
    <section class="panel detail-panel"><h2>Estado y asociación</h2><p><strong>{{ sensor.estado === 'HABILITADO' ? 'Habilitado' : 'Deshabilitado' }}</strong> · {{ comunicacion === 'RECIENTE' ? 'Comunicación reciente' : 'Sin comunicación reciente' }}</p><p>Espacio: <strong>{{ sensor.espacio_actual.recurso.codigo }} · {{ sensor.espacio_actual.recurso.nombre }}</strong></p><p>Última comunicación: {{ sensor.ultima_comunicacion ? dateLabel(sensor.ultima_comunicacion) : 'Sin eventos recibidos' }}</p><p class="hint">La ausencia de comunicación no determina que el espacio esté libre.</p>
      <div class="button-group"><Link v-if="can('iot.sensores.gestionar')" :href="`/iot/sensores/${sensor.id}/editar`" class="btn btn-secondary">Editar o reasociar</Link><button v-if="can('iot.sensores.gestionar')" class="btn btn-secondary" @click="state">{{ sensor.estado === 'HABILITADO' ? 'Deshabilitar sensor' : 'Habilitar sensor' }}</button><Link v-if="can('iot.monitoreo.ver')" :href="`/iot/monitoreo?sensor_id=${sensor.id}`" class="text-link">Ver monitoreo e historial</Link></div>
    </section>
    <section v-if="can('iot.sensores.gestionar')" class="panel form-panel"><h2>Configurar credencial del dispositivo</h2><p>Solicite la credencial únicamente cuando vaya a configurar este sensor. Volver a solicitarla entrega la misma credencial; no la rota.</p><p class="alert">Guarde esta credencial de forma segura para configurar el dispositivo.</p>
      <button class="btn btn-primary" :disabled="busy || sensor.estado !== 'HABILITADO'" @click="provision">{{ busy ? 'Preparando…' : 'Provisionar credencial' }}</button><p v-if="sensor.estado !== 'HABILITADO'" class="hint">El sensor debe estar habilitado.</p>
      <p v-if="message" role="alert">{{ message }}</p>
      <div v-if="credential" class="iot-credential no-print"><label for="iot-credential">Credencial privada de este sensor</label><input id="iot-credential" :type="visible ? 'text' : 'password'" :value="credential" readonly autocomplete="off" spellcheck="false"><div class="button-group"><button class="btn btn-secondary" :aria-pressed="visible" @click="visible = !visible">{{ visible ? 'Ocultar' : 'Mostrar credencial' }}</button><button class="btn btn-secondary" @click="clear">Cerrar credencial</button></div><p class="hint">Se retira de la pantalla al salir o cambiar de pestaña. No se guarda en el historial de navegación.</p></div>
    </section>
  </AppLayout>
</template>
<style scoped>
.iot-credential { margin-top: 1.5rem; display: grid; gap: .8rem; }
.iot-credential input { width: 100%; font-family: monospace; }
@media print { .iot-credential { display: none !important; } }
</style>
