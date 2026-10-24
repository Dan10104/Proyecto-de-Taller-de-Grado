<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
const props = defineProps({ readings: { type: Array, default: () => [] }, compact: Boolean });
const elapsed = ref(0);
let received = performance.now(), timer;
watch(() => props.readings, () => { received = performance.now(); elapsed.value = 0; });
onMounted(() => { timer = setInterval(() => { elapsed.value = Math.floor((performance.now() - received) / 1000); }, 1000); });
onBeforeUnmount(() => clearInterval(timer));
const current = computed(() => props.readings.filter(r => elapsed.value <= r.vigente_por_segundos));
</script>
<template>
  <section class="iot-evidence" aria-label="Información complementaria IoT">
    <strong>Ocupación: no determinable</strong>
    <p v-if="!current.length">Sin lecturas recientes suficientes.</p>
    <ul v-else><li v-for="(reading, index) in current" :key="index"><strong>{{ reading.tipo === 'PRESENCIA' ? 'Presencia' : 'Ocupación' }}</strong>: {{ reading.contradiccion ? 'Lecturas contradictorias' : reading.valor ?? 'Valor no informado' }} · lectura reciente<p>Medición: {{ reading.fecha_evento }} · Bolivia</p><p v-if="!compact">Recepción: {{ reading.fecha_recepcion }} · Bolivia</p></li></ul>
    <p class="hint">Información ambiental complementaria. No modifica la disponibilidad ni confirma que el espacio esté libre.</p>
  </section>
</template>
<style scoped>
.iot-evidence { border-top: 1px solid #dce5e4; margin-top: 1rem; padding-top: 1rem; font-size: .9rem; }
.iot-evidence ul { padding-left: 1.2rem; }
.iot-evidence li { margin: .7rem 0; }
.iot-evidence p { overflow-wrap: anywhere; }
</style>
