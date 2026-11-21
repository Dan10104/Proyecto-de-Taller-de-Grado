<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import StatusBadge from './StatusBadge.vue';
// «embedded» solo cambia la presentación: omite el veredicto y la nota cuando el contenedor ya los muestra una vez.
const props = defineProps({ readings: { type: Array, default: () => [] }, compact: Boolean, embedded: Boolean });
const elapsed = ref(0);
let received = performance.now(), timer;
watch(() => props.readings, () => { received = performance.now(); elapsed.value = 0; });
onMounted(() => { timer = setInterval(() => { elapsed.value = Math.floor((performance.now() - received) / 1000); }, 1000); });
onBeforeUnmount(() => clearInterval(timer));
const current = computed(() => props.readings.filter(r => elapsed.value <= r.vigente_por_segundos));
</script>
<template>
  <section class="iot-evidence" aria-label="Información complementaria IoT">
    <p v-if="!embedded" class="iot-evidence-verdict"><StatusBadge value="NO_DETERMINABLE" label="Ocupación: no determinable"/></p>
    <p v-if="!current.length" class="iot-evidence-none">Sin lecturas recientes suficientes.</p>
    <template v-else>
      <ul class="iot-evidence-list">
        <li v-for="(reading, index) in current" :key="index" :class="{ 'is-conflict': reading.contradiccion }">
          <span class="iot-evidence-dot" aria-hidden="true"></span>
          <strong>Lectura de {{ reading.tipo === 'PRESENCIA' ? 'presencia' : 'ocupación' }}</strong>
          <span>{{ reading.contradiccion ? 'lecturas contradictorias' : 'reciente' }}</span>
        </li>
      </ul>
      <details class="iot-evidence-details">
        <summary>{{ embedded ? "Ver valores de las lecturas" : "Ver detalles técnicos" }}</summary>
        <dl v-for="(reading, index) in current" :key="index">
          <div><dt>Tipo de lectura</dt><dd>{{ reading.tipo === 'PRESENCIA' ? 'Presencia' : 'Ocupación' }}</dd></div>
          <div><dt>Valor informado</dt><dd class="iot-evidence-value">{{ reading.contradiccion ? 'Lecturas contradictorias' : reading.valor ?? 'Valor no informado' }}</dd></div>
          <div><dt>Medición</dt><dd>{{ reading.fecha_evento }} · Bolivia</dd></div>
          <div v-if="!compact"><dt>Recepción</dt><dd>{{ reading.fecha_recepcion }} · Bolivia</dd></div>
          <div><dt>Vigencia al consultar</dt><dd>{{ reading.vigente_por_segundos }} s</dd></div>
        </dl>
      </details>
    </template>
    <p v-if="!embedded" class="hint">Información ambiental complementaria. No modifica la disponibilidad ni confirma que el espacio esté libre.</p>
  </section>
</template>
