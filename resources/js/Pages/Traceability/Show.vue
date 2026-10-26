<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { operations, verificationResults, explanations } from '../../traceability';
import { traceabilityDate } from '../../traceability';
import { useAccess } from '../../security';
const props = defineProps({ evidencia: Object, explorador: String });
const { can } = useAccess();
const form = useForm({});
const run = action => form.post(`/trazabilidad/${props.evidencia.id}/${action}`, { preserveScroll: true });
</script>
<template>
  <AppLayout :title="`Evidencia ${evidencia.id}`">
    <Link href="/trazabilidad" class="back-link">← Evidencias de trazabilidad</Link>
    <div class="page-heading"><div><span class="eyebrow">{{ operations[evidencia.actividad.accion] }}</span><h1>Evidencia {{ evidencia.id }}</h1><p>{{ traceabilityDate(evidencia.actividad.fecha_hora) }}</p></div><div class="actions"><button v-if="can('trazabilidad.evidencias.verificar')" class="btn btn-primary" :disabled="form.processing" @click="run('verificar')">{{ form.processing ? 'Procesando…' : 'Verificar evidencia' }}</button><button v-if="can('trazabilidad.reintentar') && evidencia.estado === 'PENDIENTE'" class="btn btn-secondary" :disabled="form.processing" @click="run('reintentar')">Reintentar registro</button></div></div>
    <section class="panel trace-panel" aria-labelledby="result-title"><h2 id="result-title">{{ evidencia.ultima_verificacion ? verificationResults[evidencia.resultado_verificacion] : 'Sin verificar' }}</h2><p role="status">{{ evidencia.ultima_verificacion ? explanations[evidencia.resultado_verificacion] : 'Seleccione «Verificar evidencia» para comprobar la información disponible.' }}</p><p>Registro externo: <strong>{{ evidencia.estado === 'REGISTRADA' ? 'Registrado' : 'Pendiente' }}</strong></p><p v-if="evidencia.ultima_verificacion">Última comprobación: {{ traceabilityDate(evidencia.ultima_verificacion) }}</p></section>
    <section class="panel trace-panel" aria-labelledby="details-title"><h2 id="details-title">Detalle de la evidencia</h2><dl>
      <dt>Contexto</dt><dd>{{ evidencia.actividad.reserva_id ? `Reserva ${evidencia.actividad.reserva_id}` : `Préstamo ${evidencia.actividad.prestamo_id}` }} · Recurso {{ evidencia.actividad.recurso_id }}</dd>
      <dt>Actividad</dt><dd>{{ evidencia.registro_actividad_id }}</dd><dt>Algoritmo</dt><dd>{{ evidencia.algoritmo_hash }}</dd><dt>Versión</dt><dd>{{ evidencia.version_canonica }}</dd>
      <dt>Hash de la evidencia</dt><dd class="trace-hash">{{ evidencia.hash }}</dd><dt>Preparada localmente</dt><dd>{{ traceabilityDate(evidencia.registrada_localmente_en) }}</dd>
      <dt>Registrada externamente</dt><dd>{{ evidencia.registrada_externamente_en ? traceabilityDate(evidencia.registrada_externamente_en) : 'Pendiente de confirmación' }}</dd>
      <dt>Referencia externa</dt><dd class="trace-hash">{{ evidencia.referencia_externa || 'Aún no disponible' }}</dd>
    </dl><a v-if="explorador" :href="explorador" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">Consultar transacción en Sepolia ↗</a></section>
  </AppLayout>
</template>
<style scoped>.trace-panel{padding:1.5rem;margin-bottom:1rem}.trace-panel h2{margin-top:0}.trace-panel dl{display:grid;grid-template-columns:minmax(9rem,1fr) minmax(0,3fr);gap:1rem}.trace-panel dt{font-weight:600}.trace-panel dd{margin:0}.trace-hash{overflow-wrap:anywhere;font-family:monospace}.actions{display:flex;flex-wrap:wrap;gap:.75rem}@media(max-width:600px){.trace-panel dl{grid-template-columns:1fr;gap:.5rem}.trace-panel dd{margin-bottom:.75rem}}</style>
