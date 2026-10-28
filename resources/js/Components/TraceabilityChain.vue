<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';
import { operations, verificationResults, traceabilityDate } from '../traceability';
const props = defineProps({ evidence: Object, entries: { type: Array, default: () => [] } });
const current = computed(() => props.evidence || props.entries[0]);
const short = value => value ? (value.length > 28 ? `${value.slice(0, 12)}…${value.slice(-10)}` : value) : 'No disponible';
</script>
<template>
  <section class="technology-canvas blockchain-chain" aria-labelledby="chain-title">
    <div class="technology-heading"><div><h2 id="chain-title"><Icon name="network"/>Trazabilidad verificable</h2><p>La operación y su recorrido de evidencia</p></div><span class="technology-label">{{ current ? `Evidencia #${current.id}` : 'Sin evidencias' }}</span></div>
    <template v-if="current">
      <ol class="chain-path" aria-label="Recorrido de la evidencia seleccionada">
        <li class="chain-node chain-blue"><div class="chain-cube" aria-hidden="true"><svg viewBox="0 0 100 100"><path d="M10 28 50 8 90 28 50 50Z"/><path d="M10 28V70L50 92V50Z"/><path d="M50 50 90 28V70L50 92Z"/></svg><Icon :name="current.actividad.reserva_id ? 'calendar' : 'monitor'"/></div><span class="chain-stage">Origen</span><strong>{{ current.actividad.reserva_id ? `Reserva ${current.actividad.reserva_id}` : `Préstamo ${current.actividad.prestamo_id}` }}</strong></li>
        <li class="chain-node chain-cyan"><div class="chain-cube" aria-hidden="true"><svg viewBox="0 0 100 100"><path d="M10 28 50 8 90 28 50 50Z"/><path d="M10 28V70L50 92V50Z"/><path d="M50 50 90 28V70L50 92Z"/></svg><Icon name="activity"/></div><span class="chain-stage">Operación</span><strong>{{ operations[current.actividad.accion] }}</strong><small>{{ traceabilityDate(current.actividad.fecha_hora) }}</small></li>
        <li class="chain-node chain-violet"><div class="chain-cube" aria-hidden="true"><svg viewBox="0 0 100 100"><path d="M10 28 50 8 90 28 50 50Z"/><path d="M10 28V70L50 92V50Z"/><path d="M50 50 90 28V70L50 92Z"/></svg><Icon name="network"/></div><span class="chain-stage">Huella digital</span><strong>{{ current.algoritmo_hash }}</strong><code :title="current.hash">{{ short(current.hash) }}</code></li>
        <li class="chain-node" :class="current.estado === 'REGISTRADA' ? 'chain-green' : 'chain-amber'"><div class="chain-cube" aria-hidden="true"><svg viewBox="0 0 100 100"><path d="M10 28 50 8 90 28 50 50Z"/><path d="M10 28V70L50 92V50Z"/><path d="M50 50 90 28V70L50 92Z"/></svg><Icon name="report"/></div><span class="chain-stage">Registro Blockchain</span><strong>{{ current.estado === 'REGISTRADA' ? 'Registrada' : 'Pendiente' }}</strong><small>{{ current.registrada_externamente_en ? traceabilityDate(current.registrada_externamente_en) : 'Sin confirmación externa' }}</small></li>
        <li class="chain-node" :class="current.ultima_verificacion && current.resultado_verificacion === 'VALIDO' ? 'chain-green' : 'chain-neutral'"><div class="chain-cube" aria-hidden="true"><svg viewBox="0 0 100 100"><path d="M10 28 50 8 90 28 50 50Z"/><path d="M10 28V70L50 92V50Z"/><path d="M50 50 90 28V70L50 92Z"/></svg><Icon name="shield"/></div><span class="chain-stage">Verificación</span><strong>{{ current.ultima_verificacion ? verificationResults[current.resultado_verificacion] : 'Sin verificar' }}</strong><small v-if="current.ultima_verificacion">{{ traceabilityDate(current.ultima_verificacion) }}</small></li>
      </ol>
      <div class="chain-evidence-meta"><span><Icon name="shield"/>{{ current.algoritmo_hash }} · {{ current.version_canonica }}</span><Link v-if="!evidence" :href="`/trazabilidad/${current.id}`">Abrir evidencia #{{ current.id }}<Icon name="arrow"/></Link><span v-else>Actividad #{{ current.registro_actividad_id }}</span></div>
    </template>
    <p v-else class="technology-empty">No hay evidencias para estos filtros. El recorrido aparecerá con el primer registro disponible.</p>
    <div class="technology-foot"><span>Los nodos representan etapas de una evidencia; no bloques enlazados entre operaciones ni una confirmación de red.</span></div>
  </section>
</template>
